<?php
/**
 * Plugin Uninstall Handler
 *
 * @package DemoBuilder
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete plugin options
delete_option('demo_builder_settings');
delete_option('demo_builder_version');

// Drop custom tables
$tables = [
    $wpdb->prefix . 'demobuilder_backups',
    $wpdb->prefix . 'demobuilder_demo_accounts',
    $wpdb->prefix . 'demobuilder_logs',
    $wpdb->prefix . 'demobuilder_cloud_queue',
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

// Delete backup directory (optional - commented out for safety)
// $backup_dir = WP_CONTENT_DIR . '/backups/demo-builder/';
// if (is_dir($backup_dir)) {
//     require_once ABSPATH . 'wp-admin/includes/file.php';
//     WP_Filesystem();
//     global $wp_filesystem;
//     $wp_filesystem->rmdir($backup_dir, true);
// }

// Clear scheduled crons
wp_clear_scheduled_hook('demo_builder_auto_restore');
wp_clear_scheduled_hook('demo_builder_auto_backup');
wp_clear_scheduled_hook('demo_builder_cleanup_old_backups');
