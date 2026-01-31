<?php
/**
 * Backup Class
 *
 * Handles database and file backup operations
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Demo_Builder_Backup
 */
class Demo_Builder_Backup {

    /**
     * Instance
     *
     * @var Demo_Builder_Backup
     */
    private static $instance = null;

    /**
     * Backup directory
     *
     * @var string
     */
    private $backup_dir;

    /**
     * Excluded tables
     *
     * @var array
     */
    private $excluded_tables = [];

    /**
     * Excluded directories
     *
     * @var array
     */
    private $excluded_dirs = [];

    /**
     * Get instance
     *
     * @return Demo_Builder_Backup
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
        add_action('wp_ajax_demo_builder_create_backup', [$this, 'ajax_create_backup']);
        add_action('wp_ajax_demo_builder_delete_backup', [$this, 'ajax_delete_backup']);
        add_action('wp_ajax_demo_builder_get_backups', [$this, 'ajax_get_backups']);
        add_action('wp_ajax_demo_builder_upload_backup', [$this, 'ajax_upload_backup']);
        add_action('wp_ajax_demo_builder_get_directory_sizes', [$this, 'ajax_get_directory_sizes']);
        add_action('wp_ajax_demo_builder_get_excludable_items', [$this, 'ajax_get_excludable_items']);
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
     * AJAX: Create backup
     */
    public function ajax_create_backup() {
        $this->verify_ajax();
        
        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : 'full';
        $options = isset($_POST['options']) ? $_POST['options'] : [];
        
        // Generate name if empty
        if (empty($name)) {
            $name = 'backup-' . date('Y-m-d-His');
        }
        
        $slug = sanitize_title($name) . '-' . time();
        
        try {
            $result = $this->create_backup($slug, $name, $type, $options);
            
            if ($result) {
                wp_send_json_success([
                    'message' => __('Backup created successfully!', 'demo-builder'),
                    'backup' => $result
                ]);
            } else {
                wp_send_json_error(['message' => __('Failed to create backup.', 'demo-builder')]);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }

    /**
     * Create backup
     *
     * @param string $slug Unique slug
     * @param string $name Display name
     * @param string $type Backup type (full, database, files)
     * @param array $options Options
     * @return array|false
     */
    public function create_backup($slug, $name, $type = 'full', $options = []) {
        global $wpdb;
        
        // Ensure backup directory exists
        if (!file_exists($this->backup_dir)) {
            wp_mkdir_p($this->backup_dir);
        }
        
        $backup_path = $this->backup_dir . $slug . '.zip';
        $db_size = 0;
        $files_count = 0;
        $file_size = 0;
        
        // Create ZIP archive
        $zip = new ZipArchive();
        if ($zip->open($backup_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception(__('Failed to create ZIP file.', 'demo-builder'));
        }
        
        // Backup database
        if ($type === 'full' || $type === 'database') {
            $sql_content = $this->export_database($options);
            $db_size = strlen($sql_content);
            $zip->addFromString('database.sql', $sql_content);
        }
        
        // Backup files
        if ($type === 'full' || $type === 'files') {
            $files_count = $this->add_files_to_zip($zip, $options);
        }
        
        // Add manifest
        $manifest = [
            'name' => $name,
            'slug' => $slug,
            'type' => $type,
            'created_at' => current_time('mysql'),
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => phpversion(),
            'site_url' => get_site_url(),
            'home_url' => get_home_url(),
            'table_prefix' => $wpdb->prefix,
            'options' => $options,
        ];
        $zip->addFromString('manifest.json', wp_json_encode($manifest, JSON_PRETTY_PRINT));
        
        $zip->close();
        
        // Get file size
        if (file_exists($backup_path)) {
            $file_size = filesize($backup_path);
        }
        
        // Save to database
        $table = $wpdb->prefix . 'demobuilder_backups';
        $wpdb->insert($table, [
            'name' => $name,
            'slug' => $slug,
            'backup_type' => $type,
            'file_path' => $backup_path,
            'file_size' => $file_size,
            'db_size' => $db_size,
            'files_count' => $files_count,
            'status' => 'complete',
            'metadata' => wp_json_encode($manifest),
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
        ]);
        
        $backup_id = $wpdb->insert_id;
        
        // Log
        if (class_exists('Demo_Builder_Core')) {
            Demo_Builder_Core::get_instance()->log('backup', "Backup created: {$name}", [
                'type' => $type,
                'size' => $file_size,
            ]);
        }
        
        return [
            'id' => $backup_id,
            'name' => $name,
            'slug' => $slug,
            'backup_type' => $type,
            'file_size' => $file_size,
            'db_size' => $db_size,
            'files_count' => $files_count,
            'status' => 'complete',
            'created_at' => current_time('mysql'),
        ];
    }

    /**
     * Export database to SQL
     *
     * @param array $options Export options
     * @return string SQL content
     */
    private function export_database($options = []) {
        global $wpdb;
        
        $settings = get_option('demo_builder_settings', []);
        $backup_settings = $settings['backup'] ?? [];
        
        $sql = "-- Demo Builder Database Backup\n";
        $sql .= "-- Generated: " . current_time('mysql') . "\n";
        $sql .= "-- WordPress Version: " . get_bloginfo('version') . "\n\n";
        $sql .= "SET NAMES utf8mb4;\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        // Get tables
        $tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}%'");
        
        // Build exclusion list
        $excluded = $this->get_excluded_tables($options);
        
        foreach ($tables as $table) {
            // Skip excluded tables
            if (in_array($table, $excluded)) {
                continue;
            }
            
            // Table structure
            $create = $wpdb->get_row("SHOW CREATE TABLE `{$table}`", ARRAY_N);
            $sql .= "-- Table: {$table}\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $create[1] . ";\n\n";
            
            // Table data
            $rows = $wpdb->get_results("SELECT * FROM `{$table}`", ARRAY_A);
            
            if (!empty($rows)) {
                // Get column names
                $columns = array_keys($rows[0]);
                $column_list = '`' . implode('`, `', $columns) . '`';
                
                // Chunk inserts for large tables
                $chunks = array_chunk($rows, 100);
                
                foreach ($chunks as $chunk) {
                    $values = [];
                    foreach ($chunk as $row) {
                        $row_values = [];
                        foreach ($row as $value) {
                            if ($value === null) {
                                $row_values[] = 'NULL';
                            } else {
                                $row_values[] = "'" . $wpdb->_real_escape($value) . "'";
                            }
                        }
                        $values[] = '(' . implode(', ', $row_values) . ')';
                    }
                    $sql .= "INSERT INTO `{$table}` ({$column_list}) VALUES\n" . implode(",\n", $values) . ";\n";
                }
                $sql .= "\n";
            }
        }
        
        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        
        // Handle exclusions
        if (!empty($backup_settings['exclude_revisions'])) {
            // Remove revisions from exported SQL (already excluded in query if implemented)
        }
        
        return $sql;
    }

    /**
     * Get excluded tables
     *
     * @param array $options Options
     * @return array
     */
    private function get_excluded_tables($options = []) {
        global $wpdb;
        
        $settings = get_option('demo_builder_settings', []);
        $backup_settings = $settings['backup'] ?? [];
        
        $excluded = [];
        
        // Always exclude Demo Builder tables from demo restore backups
        // (but include them in migration backups)
        
        // Exclude transients
        if (!empty($backup_settings['exclude_transients'])) {
            // Transients are in wp_options, handled separately
        }
        
        // Exclude session tables
        $excluded[] = $wpdb->prefix . 'woocommerce_sessions';
        
        // Exclude cache tables
        $excluded[] = $wpdb->prefix . 'object_cache';
        
        // Custom excluded tables from settings
        if (!empty($backup_settings['excluded_tables'])) {
            $custom = array_map('trim', $backup_settings['excluded_tables']);
            $excluded = array_merge($excluded, $custom);
        }
        
        return array_unique($excluded);
    }

    /**
     * Add files to ZIP
     *
     * @param ZipArchive $zip ZIP archive
     * @param array $options Options
     * @return int Files count
     */
    private function add_files_to_zip($zip, $options = []) {
        $settings = get_option('demo_builder_settings', []);
        $backup_settings = $settings['backup'] ?? [];
        
        $count = 0;
        $base_path = WP_CONTENT_DIR;
        
        // Directories to backup
        $directories = [
            'uploads' => $base_path . '/uploads',
            'themes' => $base_path . '/themes',
            'plugins' => $base_path . '/plugins',
        ];
        
        // Filter based on options
        if (isset($options['directories']) && is_array($options['directories'])) {
            $selected = $options['directories'];
            $directories = array_filter($directories, function($key) use ($selected) {
                return in_array($key, $selected);
            }, ARRAY_FILTER_USE_KEY);
        }
        
        // Get exclusion patterns
        $exclude_patterns = $this->get_exclusion_patterns($options);
        
        foreach ($directories as $name => $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($iterator as $file) {
                $file_path = $file->getPathname();
                $relative_path = 'wp-content/' . $name . '/' . substr($file_path, strlen($dir) + 1);
                
                // Check exclusions
                if ($this->should_exclude($file_path, $relative_path, $exclude_patterns)) {
                    continue;
                }
                
                if ($file->isDir()) {
                    $zip->addEmptyDir($relative_path);
                } else {
                    $zip->addFile($file_path, $relative_path);
                    $count++;
                }
            }
        }
        
        return $count;
    }

    /**
     * Get exclusion patterns
     *
     * @param array $options Options
     * @return array
     */
    private function get_exclusion_patterns($options = []) {
        $settings = get_option('demo_builder_settings', []);
        $backup_settings = $settings['backup'] ?? [];
        
        $patterns = [
            // Always exclude
            'demo-builder', // Self
            '.git',
            'node_modules',
            '.DS_Store',
            'Thumbs.db',
            
            // Cache directories
            'cache',
            'wp-cache',
            
            // Log files
            '*.log',
            'debug.log',
            
            // Temp files
            '*.tmp',
            '*.bak',
            '*.swp',
            
            // Backup directories (prevent recursion)
            'backups',
        ];
        
        // Inactive plugins
        if (!empty($backup_settings['exclude_inactive_plugins'])) {
            $active_plugins = get_option('active_plugins', []);
            $all_plugins = get_plugins();
            
            foreach ($all_plugins as $plugin_file => $plugin_data) {
                if (!in_array($plugin_file, $active_plugins)) {
                    $plugin_dir = dirname($plugin_file);
                    if ($plugin_dir !== '.') {
                        $patterns[] = 'plugins/' . $plugin_dir;
                    }
                }
            }
        }
        
        // Inactive themes
        if (!empty($backup_settings['exclude_inactive_themes'])) {
            $active_theme = get_template();
            $parent_theme = get_template();
            $child_theme = get_stylesheet();
            
            $all_themes = wp_get_themes();
            
            foreach ($all_themes as $theme_slug => $theme) {
                if ($theme_slug !== $active_theme && $theme_slug !== $child_theme && $theme_slug !== $parent_theme) {
                    $patterns[] = 'themes/' . $theme_slug;
                }
            }
        }
        
        // Custom patterns
        if (!empty($backup_settings['excluded_directories'])) {
            $custom = array_map('trim', $backup_settings['excluded_directories']);
            $patterns = array_merge($patterns, $custom);
        }
        
        return $patterns;
    }

    /**
     * Check if file/dir should be excluded
     *
     * @param string $file_path Absolute path
     * @param string $relative_path Relative path in ZIP
     * @param array $patterns Exclusion patterns
     * @return bool
     */
    private function should_exclude($file_path, $relative_path, $patterns) {
        foreach ($patterns as $pattern) {
            // Glob pattern
            if (strpos($pattern, '*') !== false) {
                if (fnmatch($pattern, basename($file_path))) {
                    return true;
                }
            } else {
                // Directory/file name match
                if (strpos($relative_path, $pattern) !== false) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * AJAX: Delete backup
     */
    public function ajax_delete_backup() {
        $this->verify_ajax();
        
        $backup_id = isset($_POST['backup_id']) ? intval($_POST['backup_id']) : 0;
        
        if (!$backup_id) {
            wp_send_json_error(['message' => __('Invalid backup ID.', 'demo-builder')]);
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'demobuilder_backups';
        
        // Get backup
        $backup = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $backup_id));
        
        if (!$backup) {
            wp_send_json_error(['message' => __('Backup not found.', 'demo-builder')]);
        }
        
        // Delete file
        if (!empty($backup->file_path) && file_exists($backup->file_path)) {
            unlink($backup->file_path);
        }
        
        // Delete from database
        $wpdb->delete($table, ['id' => $backup_id]);
        
        // Log
        if (class_exists('Demo_Builder_Core')) {
            Demo_Builder_Core::get_instance()->log('backup', "Backup deleted: {$backup->name}");
        }
        
        wp_send_json_success(['message' => __('Backup deleted successfully!', 'demo-builder')]);
    }

    /**
     * AJAX: Get backups list
     */
    public function ajax_get_backups() {
        $this->verify_ajax();
        
        global $wpdb;
        $table = $wpdb->prefix . 'demobuilder_backups';
        
        $backups = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 100");
        
        // Check file existence
        foreach ($backups as &$backup) {
            $backup->file_exists = !empty($backup->file_path) && file_exists($backup->file_path);
        }
        
        wp_send_json_success(['backups' => $backups]);
    }

    /**
     * AJAX: Upload backup
     */
    public function ajax_upload_backup() {
        $this->verify_ajax();
        
        if (empty($_FILES['backup_file'])) {
            wp_send_json_error(['message' => __('No file uploaded.', 'demo-builder')]);
        }
        
        $file = $_FILES['backup_file'];
        
        // Validate file type
        $allowed_types = ['application/zip', 'application/x-zip-compressed', 'application/sql', 'text/sql'];
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(['message' => __('Invalid file type. Only ZIP and SQL files are allowed.', 'demo-builder')]);
        }
        
        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $slug = 'uploaded-' . time();
        $filename = $slug . '.' . $ext;
        $dest_path = $this->backup_dir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $dest_path)) {
            wp_send_json_error(['message' => __('Failed to save uploaded file.', 'demo-builder')]);
        }
        
        // Determine backup type
        $type = ($ext === 'sql') ? 'database' : 'full';
        
        // Parse manifest if ZIP
        $manifest = [];
        if ($ext === 'zip') {
            $zip = new ZipArchive();
            if ($zip->open($dest_path) === true) {
                $manifest_content = $zip->getFromName('manifest.json');
                if ($manifest_content) {
                    $manifest = json_decode($manifest_content, true);
                }
                $zip->close();
            }
        }
        
        // Get name from manifest or generate
        $name = !empty($manifest['name']) ? $manifest['name'] : pathinfo($file['name'], PATHINFO_FILENAME);
        
        // Save to database
        global $wpdb;
        $table = $wpdb->prefix . 'demobuilder_backups';
        
        $wpdb->insert($table, [
            'name' => $name,
            'slug' => $slug,
            'backup_type' => $type,
            'file_path' => $dest_path,
            'file_size' => filesize($dest_path),
            'status' => 'complete',
            'metadata' => wp_json_encode($manifest),
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'from_directory' => 1,
        ]);
        
        $backup_id = $wpdb->insert_id;
        
        wp_send_json_success([
            'message' => __('Backup uploaded successfully!', 'demo-builder'),
            'backup' => [
                'id' => $backup_id,
                'name' => $name,
                'slug' => $slug,
                'backup_type' => $type,
                'file_size' => filesize($dest_path),
                'status' => 'complete',
                'created_at' => current_time('mysql'),
            ]
        ]);
    }

    /**
     * AJAX: Get directory sizes
     */
    public function ajax_get_directory_sizes() {
        $this->verify_ajax();
        
        $sizes = [
            'uploads' => $this->get_directory_size(WP_CONTENT_DIR . '/uploads'),
            'themes' => $this->get_directory_size(WP_CONTENT_DIR . '/themes'),
            'plugins' => $this->get_directory_size(WP_CONTENT_DIR . '/plugins'),
        ];
        
        // Database size
        global $wpdb;
        $db_size = 0;
        $tables = $wpdb->get_results("SHOW TABLE STATUS LIKE '{$wpdb->prefix}%'");
        foreach ($tables as $table) {
            $db_size += $table->Data_length + $table->Index_length;
        }
        $sizes['database'] = $db_size;
        
        // Format sizes
        $formatted = [];
        foreach ($sizes as $key => $size) {
            $formatted[$key] = [
                'bytes' => $size,
                'formatted' => size_format($size),
            ];
        }
        
        wp_send_json_success(['sizes' => $formatted]);
    }

    /**
     * Get directory size
     *
     * @param string $dir Directory path
     * @return int Size in bytes
     */
    private function get_directory_size($dir) {
        $size = 0;
        
        if (!is_dir($dir)) {
            return $size;
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        
        return $size;
    }

    /**
     * AJAX: Get excludable items
     */
    public function ajax_get_excludable_items() {
        $this->verify_ajax();
        
        // Get inactive plugins
        $active_plugins = get_option('active_plugins', []);
        $all_plugins = get_plugins();
        $inactive_plugins = [];
        
        foreach ($all_plugins as $plugin_file => $plugin_data) {
            if (!in_array($plugin_file, $active_plugins)) {
                $inactive_plugins[] = [
                    'file' => $plugin_file,
                    'name' => $plugin_data['Name'],
                ];
            }
        }
        
        // Get inactive themes
        $active_theme = get_template();
        $child_theme = get_stylesheet();
        $all_themes = wp_get_themes();
        $inactive_themes = [];
        
        foreach ($all_themes as $theme_slug => $theme) {
            if ($theme_slug !== $active_theme && $theme_slug !== $child_theme) {
                $inactive_themes[] = [
                    'slug' => $theme_slug,
                    'name' => $theme->get('Name'),
                ];
            }
        }
        
        // Get database tables
        global $wpdb;
        $tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}%'");
        
        wp_send_json_success([
            'inactive_plugins' => $inactive_plugins,
            'inactive_themes' => $inactive_themes,
            'database_tables' => $tables,
        ]);
    }

    /**
     * Download backup
     *
     * @param int $backup_id Backup ID
     */
    public function download_backup($backup_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'demobuilder_backups';
        
        $backup = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $backup_id));
        
        if (!$backup || !file_exists($backup->file_path)) {
            wp_die(__('Backup file not found.', 'demo-builder'));
        }
        
        // Send file
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $backup->slug . '.zip"');
        header('Content-Length: ' . filesize($backup->file_path));
        header('Pragma: public');
        
        readfile($backup->file_path);
        exit;
    }
}
