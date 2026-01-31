<?php
/**
 * Scheduled Hooks Class
 *
 * Handles WP-Cron integration for scheduled restore
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Demo_Builder_Scheduled_Hooks
 */
class Demo_Builder_Scheduled_Hooks {

    /**
     * Instance
     *
     * @var Demo_Builder_Scheduled_Hooks
     */
    private static $instance = null;

    /**
     * Custom schedule intervals
     *
     * @var array
     */
    private $custom_intervals = [];

    /**
     * Get instance
     *
     * @return Demo_Builder_Scheduled_Hooks
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
        $this->define_intervals();
        $this->init_hooks();
    }

    /**
     * Define custom cron intervals
     */
    private function define_intervals() {
        $this->custom_intervals = [
            'demo_builder_30min' => [
                'interval' => 1800,
                'display' => __('Every 30 minutes', 'demo-builder'),
            ],
            'demo_builder_60min' => [
                'interval' => 3600,
                'display' => __('Every 60 minutes', 'demo-builder'),
            ],
            'demo_builder_2hours' => [
                'interval' => 7200,
                'display' => __('Every 2 hours', 'demo-builder'),
            ],
            'demo_builder_24hours' => [
                'interval' => 86400,
                'display' => __('Every 24 hours', 'demo-builder'),
            ],
            'demo_builder_48hours' => [
                'interval' => 172800,
                'display' => __('Every 2 days', 'demo-builder'),
            ],
            'demo_builder_72hours' => [
                'interval' => 259200,
                'display' => __('Every 3 days', 'demo-builder'),
            ],
            'demo_builder_7days' => [
                'interval' => 604800,
                'display' => __('Every 7 days', 'demo-builder'),
            ],
            'demo_builder_14days' => [
                'interval' => 1209600,
                'display' => __('Every 14 days', 'demo-builder'),
            ],
        ];
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add custom cron schedules
        add_filter('cron_schedules', [$this, 'add_cron_schedules']);
        
        // Register cron actions
        add_action('demo_builder_auto_restore', [$this, 'run_scheduled_restore']);
        add_action('demo_builder_auto_backup', [$this, 'run_scheduled_backup']);
        
        // Admin AJAX
        add_action('wp_ajax_demo_builder_save_restore_settings', [$this, 'ajax_save_restore_settings']);
        add_action('wp_ajax_demo_builder_get_countdown_data', [$this, 'ajax_get_countdown_data']);
    }

    /**
     * Add custom cron schedules
     *
     * @param array $schedules Existing schedules
     * @return array
     */
    public function add_cron_schedules($schedules) {
        return array_merge($schedules, $this->custom_intervals);
    }

    /**
     * Get schedule options for dropdown
     *
     * @return array
     */
    public function get_schedule_options() {
        return [
            '30min' => __('Every 30 minutes', 'demo-builder'),
            '60min' => __('Every 60 minutes', 'demo-builder'),
            '2hours' => __('Every 2 hours', 'demo-builder'),
            '24hours' => __('Every 24 hours', 'demo-builder'),
            '48hours' => __('Every 2 days', 'demo-builder'),
            '72hours' => __('Every 3 days', 'demo-builder'),
            '7days' => __('Every 7 days', 'demo-builder'),
            '14days' => __('Every 14 days', 'demo-builder'),
            'custom' => __('Custom hours', 'demo-builder'),
        ];
    }

    /**
     * Get interval in seconds for schedule value
     *
     * @param string $schedule Schedule key
     * @param int $custom_hours Custom hours (for 'custom' schedule)
     * @return int Seconds
     */
    public function get_interval_seconds($schedule, $custom_hours = 0) {
        $map = [
            '30min' => 1800,
            '60min' => 3600,
            '2hours' => 7200,
            '24hours' => 86400,
            '48hours' => 172800,
            '72hours' => 259200,
            '7days' => 604800,
            '14days' => 1209600,
            'custom' => max(1, intval($custom_hours)) * 3600,
        ];
        
        return $map[$schedule] ?? 86400;
    }

    /**
     * Schedule auto restore
     *
     * @param string $schedule Schedule key
     * @param int $custom_hours Custom hours
     */
    public function schedule_auto_restore($schedule, $custom_hours = 0) {
        // Clear existing
        $this->unschedule_auto_restore();
        
        $interval = $this->get_interval_seconds($schedule, $custom_hours);
        $next_time = time() + $interval;
        
        // Map schedule to cron recurrence
        $recurrence_map = [
            '30min' => 'demo_builder_30min',
            '60min' => 'demo_builder_60min',
            '2hours' => 'demo_builder_2hours',
            '24hours' => 'demo_builder_24hours',
            '48hours' => 'demo_builder_48hours',
            '72hours' => 'demo_builder_72hours',
            '7days' => 'demo_builder_7days',
            '14days' => 'demo_builder_14days',
        ];
        
        $recurrence = $recurrence_map[$schedule] ?? 'demo_builder_24hours';
        
        // For custom, use single event and reschedule after completion
        if ($schedule === 'custom') {
            wp_schedule_single_event($next_time, 'demo_builder_auto_restore');
        } else {
            wp_schedule_event($next_time, $recurrence, 'demo_builder_auto_restore');
        }
        
        // Update settings
        $settings = get_option('demo_builder_settings', []);
        $settings['restore']['next_auto_restore'] = $next_time;
        update_option('demo_builder_settings', $settings);
        
        return $next_time;
    }

    /**
     * Unschedule auto restore
     */
    public function unschedule_auto_restore() {
        $timestamp = wp_next_scheduled('demo_builder_auto_restore');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'demo_builder_auto_restore');
        }
        wp_clear_scheduled_hook('demo_builder_auto_restore');
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
        
        // Log start
        if (class_exists('Demo_Builder_Core')) {
            Demo_Builder_Core::get_instance()->log('restore', 'Auto restore started');
        }
        
        $backup_id = $restore_settings['source_backup_id'] ?? null;
        
        // If no specific backup, use latest
        if (!$backup_id) {
            global $wpdb;
            $table = $wpdb->prefix . 'demobuilder_backups';
            $backup = $wpdb->get_row("SELECT id FROM {$table} WHERE status = 'complete' ORDER BY created_at DESC LIMIT 1");
            $backup_id = $backup ? $backup->id : null;
        }
        
        if (!$backup_id) {
            if (class_exists('Demo_Builder_Core')) {
                Demo_Builder_Core::get_instance()->log('error', 'Auto restore failed: No backup available');
            }
            return;
        }
        
        try {
            // Enable maintenance mode
            $this->enable_maintenance_mode();
            
            // Run restore
            if (class_exists('Demo_Builder_Restore')) {
                Demo_Builder_Restore::get_instance()->restore_backup($backup_id);
            }
            
            // Update last restore time
            $settings['restore']['last_auto_restore'] = current_time('mysql');
            update_option('demo_builder_settings', $settings);
            
            // Auto backup after restore if enabled
            if (!empty($restore_settings['auto_backup_after_restore'])) {
                if (class_exists('Demo_Builder_Backup')) {
                    Demo_Builder_Backup::get_instance()->create_backup(
                        'auto-post-restore-' . time(),
                        __('Auto Backup After Restore', 'demo-builder'),
                        'full'
                    );
                }
            }
            
            // Log success
            if (class_exists('Demo_Builder_Core')) {
                Demo_Builder_Core::get_instance()->log('restore', 'Auto restore completed successfully');
            }
            
            // Send Telegram notification
            $this->send_restore_notification(true);
            
        } catch (Exception $e) {
            if (class_exists('Demo_Builder_Core')) {
                Demo_Builder_Core::get_instance()->log('error', 'Auto restore failed: ' . $e->getMessage());
            }
            $this->send_restore_notification(false, $e->getMessage());
        }
        
        // Disable maintenance mode
        $this->disable_maintenance_mode();
        
        // Reschedule for custom interval
        if (($restore_settings['restore_schedule'] ?? '') === 'custom') {
            $custom_hours = $restore_settings['restore_schedule_custom_hours'] ?? 24;
            $this->schedule_auto_restore('custom', $custom_hours);
        }
        
        // Update next restore time
        $next_time = wp_next_scheduled('demo_builder_auto_restore');
        if ($next_time) {
            $settings = get_option('demo_builder_settings', []);
            $settings['restore']['next_auto_restore'] = $next_time;
            update_option('demo_builder_settings', $settings);
        }
    }

    /**
     * Run scheduled backup
     */
    public function run_scheduled_backup() {
        $settings = get_option('demo_builder_settings', []);
        $backup_settings = $settings['backup'] ?? [];
        
        if (empty($backup_settings['auto_backup'])) {
            return;
        }
        
        if (class_exists('Demo_Builder_Backup')) {
            try {
                Demo_Builder_Backup::get_instance()->create_backup(
                    'auto-scheduled-' . time(),
                    __('Scheduled Auto Backup', 'demo-builder'),
                    'full'
                );
                
                if (class_exists('Demo_Builder_Core')) {
                    Demo_Builder_Core::get_instance()->log('backup', 'Scheduled backup completed');
                }
            } catch (Exception $e) {
                if (class_exists('Demo_Builder_Core')) {
                    Demo_Builder_Core::get_instance()->log('error', 'Scheduled backup failed: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Enable maintenance mode
     */
    private function enable_maintenance_mode() {
        $maintenance_file = ABSPATH . '.maintenance';
        $content = '<?php $upgrading = ' . time() . '; ?>';
        file_put_contents($maintenance_file, $content);
    }

    /**
     * Disable maintenance mode
     */
    private function disable_maintenance_mode() {
        $maintenance_file = ABSPATH . '.maintenance';
        if (file_exists($maintenance_file)) {
            unlink($maintenance_file);
        }
    }

    /**
     * Send Telegram notification for restore
     *
     * @param bool $success Success status
     * @param string $error Error message
     */
    private function send_restore_notification($success, $error = '') {
        $settings = get_option('demo_builder_settings', []);
        $telegram = $settings['telegram'] ?? [];
        
        if (empty($telegram['enabled']) || empty($telegram['bot_token']) || empty($telegram['chat_id'])) {
            return;
        }
        
        $site_url = get_site_url();
        $time = current_time('Y-m-d H:i:s');
        
        if ($success) {
            $message = "✅ *Demo Builder - Auto Restore Complete*\n\n";
            $message .= "Site: {$site_url}\n";
            $message .= "Time: {$time}\n";
            $message .= "Status: Success";
        } else {
            $message = "❌ *Demo Builder - Auto Restore Failed*\n\n";
            $message .= "Site: {$site_url}\n";
            $message .= "Time: {$time}\n";
            $message .= "Error: {$error}";
        }
        
        $url = "https://api.telegram.org/bot{$telegram['bot_token']}/sendMessage";
        
        wp_remote_post($url, [
            'body' => [
                'chat_id' => $telegram['chat_id'],
                'text' => $message,
                'parse_mode' => 'Markdown',
            ],
        ]);
    }

    /**
     * AJAX: Save restore settings
     */
    public function ajax_save_restore_settings() {
        if (!check_ajax_referer('demo_builder_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed.', 'demo-builder')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'demo-builder')]);
        }
        
        $settings = get_option('demo_builder_settings', []);
        
        // Update restore settings
        $restore = [
            'auto_restore_enabled' => isset($_POST['auto_restore_enabled']) && $_POST['auto_restore_enabled'] === 'true',
            'restore_schedule' => sanitize_text_field($_POST['restore_schedule'] ?? '24hours'),
            'restore_schedule_custom_hours' => intval($_POST['restore_schedule_custom_hours'] ?? 0),
            'source_backup_id' => intval($_POST['source_backup_id'] ?? 0) ?: null,
            'auto_backup_after_restore' => isset($_POST['auto_backup_after_restore']) && $_POST['auto_backup_after_restore'] === 'true',
            'last_auto_restore' => $settings['restore']['last_auto_restore'] ?? null,
        ];
        
        $settings['restore'] = $restore;
        
        // Handle scheduling
        if ($restore['auto_restore_enabled']) {
            $next_time = $this->schedule_auto_restore(
                $restore['restore_schedule'],
                $restore['restore_schedule_custom_hours']
            );
            $settings['restore']['next_auto_restore'] = $next_time;
        } else {
            $this->unschedule_auto_restore();
            $settings['restore']['next_auto_restore'] = null;
        }
        
        update_option('demo_builder_settings', $settings);
        
        wp_send_json_success([
            'message' => __('Restore settings saved!', 'demo-builder'),
            'next_restore' => $settings['restore']['next_auto_restore'],
        ]);
    }

    /**
     * AJAX: Get countdown data
     */
    public function ajax_get_countdown_data() {
        $settings = get_option('demo_builder_settings', []);
        $restore = $settings['restore'] ?? [];
        
        $next_restore = $restore['next_auto_restore'] ?? null;
        $last_restore = $restore['last_auto_restore'] ?? null;
        
        // If we have a scheduled event, use its timestamp
        $scheduled = wp_next_scheduled('demo_builder_auto_restore');
        if ($scheduled) {
            $next_restore = $scheduled;
        }
        
        wp_send_json_success([
            'enabled' => !empty($restore['auto_restore_enabled']),
            'next_restore' => $next_restore,
            'last_restore' => $last_restore,
            'server_time' => time(),
            'schedule' => $restore['restore_schedule'] ?? '24hours',
        ]);
    }

    /**
     * Get next restore timestamp
     *
     * @return int|null
     */
    public function get_next_restore() {
        return wp_next_scheduled('demo_builder_auto_restore');
    }
}
