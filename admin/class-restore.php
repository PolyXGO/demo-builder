<?php
/**
 * Restore Class
 *
 * Handles backup restoration operations
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Demo_Builder_Restore
 */
class Demo_Builder_Restore {

    /**
     * Instance
     *
     * @var Demo_Builder_Restore
     */
    private static $instance = null;

    /**
     * Backup directory
     *
     * @var string
     */
    private $backup_dir;

    /**
     * Get instance
     *
     * @return Demo_Builder_Restore
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->backup_dir = DEMO_BUILDER_BACKUP_DIR;
        $this->init_hooks();
    }

    /**
     * Initialize AJAX hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_demo_builder_restore_backup', [$this, 'ajax_restore_backup']);
        add_action('wp_ajax_demo_builder_preview_backup', [$this, 'ajax_preview_backup']);
        
        // Scheduled restore
        add_action('demo_builder_scheduled_restore', [$this, 'run_scheduled_restore']);
        add_action('demo_builder_auto_restore', [$this, 'run_auto_restore']);
    }

    /**
     * Verify AJAX request
     *
     * @return bool
     */
    private function verify_ajax() {
        if (!check_ajax_referer('demo_builder_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed.', 'demo-builder')]);
            return false;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'demo-builder')]);
            return false;
        }
        
        return true;
    }

    /**
     * AJAX: Restore backup
     */
    public function ajax_restore_backup() {
        $this->verify_ajax();
        
        $backup_id = isset($_POST['backup_id']) ? intval($_POST['backup_id']) : 0;
        
        if (!$backup_id) {
            wp_send_json_error(['message' => __('Invalid backup ID.', 'demo-builder')]);
        }
        
        try {
            $result = $this->restore_backup($backup_id);
            
            if ($result) {
                wp_send_json_success([
                    'message' => __('Backup restored successfully!', 'demo-builder'),
                ]);
            } else {
                wp_send_json_error(['message' => __('Failed to restore backup.', 'demo-builder')]);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Restore backup
     *
     * @param int $backup_id Backup ID
     * @param array $options Restore options
     * @return bool
     */
    public function restore_backup($backup_id, $options = []) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'demobuilder_backups';
        $backup = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $backup_id));
        
        if (!$backup) {
            throw new Exception(__('Backup not found.', 'demo-builder'));
        }
        
        if (!file_exists($backup->file_path)) {
            throw new Exception(__('Backup file not found on disk.', 'demo-builder'));
        }
        
        // Log start
        if (class_exists('Demo_Builder_Core')) {
            Demo_Builder_Core::get_instance()->log('restore', "Restore started: {$backup->name}");
        }
        
        // Open ZIP
        $zip = new ZipArchive();
        if ($zip->open($backup->file_path) !== true) {
            throw new Exception(__('Failed to open backup file.', 'demo-builder'));
        }
        
        // Get manifest
        $manifest_content = $zip->getFromName('manifest.json');
        $manifest = $manifest_content ? json_decode($manifest_content, true) : [];
        
        // Restore database
        if ($backup->backup_type === 'full' || $backup->backup_type === 'database') {
            $sql_content = $zip->getFromName('database.sql');
            if ($sql_content) {
                $this->restore_database($sql_content, $options);
            }
        }
        
        // Restore files
        if ($backup->backup_type === 'full' || $backup->backup_type === 'files') {
            $this->restore_files($zip, $options);
        }
        
        $zip->close();
        
        // Update last restore time
        update_option('demo_builder_last_restore', current_time('mysql'));
        
        // Log completion
        if (class_exists('Demo_Builder_Core')) {
            Demo_Builder_Core::get_instance()->log('restore', "Restore completed: {$backup->name}");
        }
        
        // Clear caches
        $this->clear_caches();
        
        return true;
    }

    /**
     * Restore database from SQL
     *
     * @param string $sql SQL content
     * @param array $options Options
     */
    private function restore_database($sql, $options = []) {
        global $wpdb;
        
        // Disable foreign key checks
        $wpdb->query("SET FOREIGN_KEY_CHECKS = 0");
        
        // Split SQL into statements
        $statements = $this->split_sql_statements($sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            
            // Skip empty and comment lines
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            
            // Execute statement
            $wpdb->query($statement);
        }
        
        // Re-enable foreign key checks
        $wpdb->query("SET FOREIGN_KEY_CHECKS = 1");
    }

    /**
     * Split SQL into individual statements
     *
     * @param string $sql SQL content
     * @return array
     */
    private function split_sql_statements($sql) {
        $statements = [];
        $current = '';
        $delimiter = ';';
        $in_string = false;
        $string_char = '';
        
        $length = strlen($sql);
        
        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            
            // Handle string quotes
            if (!$in_string && ($char === "'" || $char === '"')) {
                $in_string = true;
                $string_char = $char;
            } elseif ($in_string && $char === $string_char) {
                // Check for escaped quote
                if ($i + 1 < $length && $sql[$i + 1] === $string_char) {
                    $current .= $char;
                    $i++;
                } else {
                    $in_string = false;
                }
            }
            
            // Check for delimiter
            if (!$in_string && $char === $delimiter) {
                $statements[] = $current;
                $current = '';
                continue;
            }
            
            $current .= $char;
        }
        
        // Add remaining
        if (trim($current)) {
            $statements[] = $current;
        }
        
        return $statements;
    }

    /**
     * Restore files from ZIP
     *
     * @param ZipArchive $zip ZIP archive
     * @param array $options Options
     */
    private function restore_files($zip, $options = []) {
        $wp_content = WP_CONTENT_DIR;
        
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            
            // Skip database and manifest
            if ($filename === 'database.sql' || $filename === 'manifest.json') {
                continue;
            }
            
            // Skip demo-builder plugin (don't overwrite self)
            if (strpos($filename, 'wp-content/plugins/demo-builder/') === 0) {
                continue;
            }
            
            // Calculate destination path
            if (strpos($filename, 'wp-content/') === 0) {
                $dest_path = $wp_content . '/' . substr($filename, strlen('wp-content/'));
            } else {
                continue; // Only restore wp-content files
            }
            
            // Create directory if needed
            $dest_dir = dirname($dest_path);
            if (!file_exists($dest_dir)) {
                wp_mkdir_p($dest_dir);
            }
            
            // Check if it's a directory
            if (substr($filename, -1) === '/') {
                if (!file_exists($dest_path)) {
                    wp_mkdir_p($dest_path);
                }
                continue;
            }
            
            // Extract file
            $content = $zip->getFromIndex($i);
            if ($content !== false) {
                file_put_contents($dest_path, $content);
            }
        }
    }

    /**
     * Clear caches after restore
     */
    private function clear_caches() {
        // Clear WordPress object cache
        wp_cache_flush();
        
        // Clear transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_%'");
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear popular cache plugins
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache(); // WP Super Cache
        }
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all(); // W3 Total Cache
        }
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain(); // WP Rocket
        }
        if (class_exists('LiteSpeed_Cache_API')) {
            LiteSpeed_Cache_API::purge_all(); // LiteSpeed Cache
        }
    }

    /**
     * AJAX: Preview backup contents
     */
    public function ajax_preview_backup() {
        $this->verify_ajax();
        
        $backup_id = isset($_POST['backup_id']) ? intval($_POST['backup_id']) : 0;
        
        if (!$backup_id) {
            wp_send_json_error(['message' => __('Invalid backup ID.', 'demo-builder')]);
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'demobuilder_backups';
        $backup = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $backup_id));
        
        if (!$backup || !file_exists($backup->file_path)) {
            wp_send_json_error(['message' => __('Backup file not found.', 'demo-builder')]);
        }
        
        $zip = new ZipArchive();
        if ($zip->open($backup->file_path) !== true) {
            wp_send_json_error(['message' => __('Failed to open backup file.', 'demo-builder')]);
        }
        
        $files = [];
        $has_database = false;
        $manifest = null;
        
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $filename = $stat['name'];
            
            if ($filename === 'database.sql') {
                $has_database = true;
            } elseif ($filename === 'manifest.json') {
                $manifest = json_decode($zip->getFromIndex($i), true);
            } else {
                $files[] = [
                    'name' => $filename,
                    'size' => $stat['size'],
                ];
            }
        }
        
        $zip->close();
        
        wp_send_json_success([
            'has_database' => $has_database,
            'manifest' => $manifest,
            'files_count' => count($files),
            'files_sample' => array_slice($files, 0, 50),
        ]);
    }

    /**
     * Run scheduled restore
     */
    public function run_scheduled_restore() {
        $settings = get_option('demo_builder_settings', []);
        $restore_settings = $settings['restore'] ?? [];
        
        if (empty($restore_settings['auto_restore_enabled'])) {
            return;
        }
        
        $backup_id = $restore_settings['auto_restore_backup_id'] ?? 0;
        
        if (!$backup_id) {
            // Use first available backup
            global $wpdb;
            $table = $wpdb->prefix . 'demobuilder_backups';
            $backup = $wpdb->get_row("SELECT id FROM {$table} WHERE status = 'complete' ORDER BY created_at DESC LIMIT 1");
            
            if ($backup) {
                $backup_id = $backup->id;
            }
        }
        
        if ($backup_id) {
            try {
                $this->restore_backup($backup_id);
            } catch (Exception $e) {
                // Log error
                if (class_exists('Demo_Builder_Core')) {
                    Demo_Builder_Core::get_instance()->log('error', "Scheduled restore failed: " . $e->getMessage());
                }
            }
        }
        
        // Reschedule next restore
        $this->schedule_next_restore();
    }

    /**
     * Run auto restore (cron)
     */
    public function run_auto_restore() {
        $this->run_scheduled_restore();
    }

    /**
     * Schedule next restore
     */
    public function schedule_next_restore() {
        $settings = get_option('demo_builder_settings', []);
        $restore_settings = $settings['restore'] ?? [];
        
        if (empty($restore_settings['auto_restore_enabled'])) {
            return;
        }
        
        $interval = $restore_settings['restore_interval'] ?? 'daily';
        
        $intervals = [
            'hourly' => HOUR_IN_SECONDS,
            'twicedaily' => 12 * HOUR_IN_SECONDS,
            'daily' => DAY_IN_SECONDS,
            'weekly' => WEEK_IN_SECONDS,
        ];
        
        $next_time = time() + ($intervals[$interval] ?? DAY_IN_SECONDS);
        
        // Clear existing
        wp_clear_scheduled_hook('demo_builder_auto_restore');
        
        // Schedule new
        wp_schedule_single_event($next_time, 'demo_builder_auto_restore');
        
        // Update settings
        $settings['restore']['next_restore_time'] = $next_time;
        update_option('demo_builder_settings', $settings);
    }

    /**
     * Get next restore time
     *
     * @return int|false
     */
    public function get_next_restore_time() {
        return wp_next_scheduled('demo_builder_auto_restore');
    }
}
