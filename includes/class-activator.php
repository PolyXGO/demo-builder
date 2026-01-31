<?php
/**
 * Plugin Activator
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Demo_Builder_Activator
 * 
 * Handles plugin activation tasks
 */
class Demo_Builder_Activator {

    /**
     * Activate the plugin
     */
    public static function activate() {
        self::create_tables();
        self::create_backup_directory();
        self::set_default_options();
        self::set_version();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        // Backups table
        $table_backups = $wpdb->prefix . 'demobuilder_backups';
        $sql_backups = "CREATE TABLE {$table_backups} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            description TEXT,
            backup_type VARCHAR(50) DEFAULT 'full',
            file_path VARCHAR(500),
            file_size BIGINT UNSIGNED DEFAULT 0,
            db_size BIGINT UNSIGNED DEFAULT 0,
            files_count INT UNSIGNED DEFAULT 0,
            status VARCHAR(50) DEFAULT 'pending',
            metadata LONGTEXT,
            created_by BIGINT UNSIGNED,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            from_directory TINYINT(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_created_at (created_at),
            KEY idx_created_by (created_by),
            KEY idx_status (status),
            KEY idx_backup_type (backup_type)
        ) {$charset_collate};";
        
        dbDelta($sql_backups);
        
        // Demo accounts table
        $table_accounts = $wpdb->prefix . 'demobuilder_demo_accounts';
        $sql_accounts = "CREATE TABLE {$table_accounts} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            display_name VARCHAR(255),
            role_name VARCHAR(100),
            description TEXT,
            credentials TEXT,
            sort_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_user_id_unique (user_id),
            KEY idx_is_active (is_active),
            KEY idx_sort_order (sort_order)
        ) {$charset_collate};";
        
        dbDelta($sql_accounts);
        
        // Logs table
        $table_logs = $wpdb->prefix . 'demobuilder_logs';
        $sql_logs = "CREATE TABLE {$table_logs} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_type VARCHAR(50) NOT NULL,
            event_subtype VARCHAR(50),
            message TEXT,
            context LONGTEXT,
            user_id BIGINT UNSIGNED,
            ip_address VARCHAR(45),
            user_agent VARCHAR(500),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_event_type (event_type),
            KEY idx_event_subtype (event_subtype),
            KEY idx_created_at (created_at),
            KEY idx_user_id (user_id),
            KEY idx_composite (event_type, created_at)
        ) {$charset_collate};";
        
        dbDelta($sql_logs);
        
        // Cloud queue table
        $table_cloud = $wpdb->prefix . 'demobuilder_cloud_queue';
        $sql_cloud = "CREATE TABLE {$table_cloud} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            backup_id BIGINT UNSIGNED NOT NULL,
            provider VARCHAR(50) NOT NULL,
            status VARCHAR(50) DEFAULT 'pending',
            cloud_file_id VARCHAR(255),
            cloud_path VARCHAR(500),
            attempts INT DEFAULT 0,
            last_attempt DATETIME,
            last_error TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_backup_id (backup_id),
            KEY idx_provider (provider),
            KEY idx_status (status),
            KEY idx_composite (provider, status)
        ) {$charset_collate};";
        
        dbDelta($sql_cloud);
    }

    /**
     * Create backup directory
     */
    private static function create_backup_directory() {
        $backup_dir = WP_CONTENT_DIR . '/backups/demo-builder/';
        
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }
        
        // Create .htaccess to protect backups
        $htaccess = $backup_dir . '.htaccess';
        if (!file_exists($htaccess)) {
            $content = "# Protect backup files\n";
            $content .= "Order deny,allow\n";
            $content .= "Deny from all\n";
            file_put_contents($htaccess, $content);
        }
        
        // Create index.php for extra security
        $index = $backup_dir . 'index.php';
        if (!file_exists($index)) {
            file_put_contents($index, "<?php\n// Silence is golden.\n");
        }
    }

    /**
     * Set default options
     */
    private static function set_default_options() {
        $default_settings = [
            'general' => [
                'enable_plugin' => true,
                'super_admin_only' => true,
            ],
            'backup' => [
                'auto_backup' => false,
                'backup_schedule' => 'daily',
                'max_backups' => 10,
                'exclude_inactive_plugins' => true,
                'exclude_inactive_themes' => true,
                'exclude_revisions' => true,
                'exclude_transients' => true,
                'exclude_spam_comments' => true,
                'excluded_tables' => [],
                'excluded_directories' => [],
            ],
            'restore' => [
                'auto_restore' => false,
                'restore_schedule' => 'daily',
                'source_backup_id' => null,
                'maintenance_mode' => true,
            ],
            'countdown' => [
                'enabled' => false,
                'position' => 'fixed',
                'show_on_admin' => true,
                'show_on_frontend' => false,
                'message' => 'Demo resets in {{countdown}}',
                'overdue_message' => 'Demo reset in progress...',
            ],
            'demo_panel' => [
                'enabled' => false,
                'show_on_login' => true,
            ],
            'telegram' => [
                'enabled' => false,
                'bot_token' => '',
                'chat_id' => '',
                'notify_backup' => true,
                'notify_restore' => true,
            ],
            'permissions' => [
                'restrict_plugin_management' => true,
                'restrict_user_management' => true,
                'restrict_settings' => true,
            ],
            'maintenance' => [
                'enabled' => false,
                'title' => 'Under Maintenance',
                'message' => 'We are performing scheduled maintenance. Please check back soon.',
                'bypass_key' => '',
                'whitelist_ips' => [],
            ],
        ];
        
        // Only add if not exists
        if (!get_option('demo_builder_settings')) {
            add_option('demo_builder_settings', $default_settings);
        }
    }

    /**
     * Set plugin version
     */
    private static function set_version() {
        update_option('demo_builder_version', DEMO_BUILDER_VERSION);
    }
}
