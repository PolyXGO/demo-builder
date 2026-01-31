<?php
/**
 * Plugin Deactivator
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Demo_Builder_Deactivator
 * 
 * Handles plugin deactivation tasks
 */
class Demo_Builder_Deactivator {

    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
        // Clear scheduled crons
        wp_clear_scheduled_hook('demo_builder_auto_restore');
        wp_clear_scheduled_hook('demo_builder_auto_backup');
        wp_clear_scheduled_hook('demo_builder_cleanup_old_backups');
        
        // Disable maintenance mode if active
        self::disable_maintenance_mode();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Disable maintenance mode
     */
    private static function disable_maintenance_mode() {
        $settings = get_option('demo_builder_settings', []);
        
        if (isset($settings['maintenance']['enabled']) && $settings['maintenance']['enabled']) {
            $settings['maintenance']['enabled'] = false;
            update_option('demo_builder_settings', $settings);
        }
    }
}
