<?php
/**
 * Plugin Name: Demo Builder
 * Plugin URI: https://github.com/PolyXGO/demo-builder
 * Description: Comprehensive backup, restore, and demo site management for WordPress with cloud storage integration.
 * Version: 1.0.0
 * Author: PolyXGO
 * Author URI: https://polyxgo.com
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: demo-builder
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin Constants
 */
define('DEMO_BUILDER_VERSION', '1.0.0');
define('DEMO_BUILDER_PLUGIN_FILE', __FILE__);
define('DEMO_BUILDER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DEMO_BUILDER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DEMO_BUILDER_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('DEMO_BUILDER_BACKUP_DIR', WP_CONTENT_DIR . '/backups/demo-builder/');

/**
 * Activation Hook
 */
function demo_builder_activate() {
    require_once DEMO_BUILDER_PLUGIN_DIR . 'includes/class-activator.php';
    Demo_Builder_Activator::activate();
}
register_activation_hook(__FILE__, 'demo_builder_activate');

/**
 * Deactivation Hook
 */
function demo_builder_deactivate() {
    require_once DEMO_BUILDER_PLUGIN_DIR . 'includes/class-deactivator.php';
    Demo_Builder_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'demo_builder_deactivate');

/**
 * Load Plugin Textdomain
 */
function demo_builder_load_textdomain() {
    load_plugin_textdomain(
        'demo-builder',
        false,
        dirname(DEMO_BUILDER_PLUGIN_BASENAME) . '/languages/'
    );
}
add_action('plugins_loaded', 'demo_builder_load_textdomain');

/**
 * Autoloader for Plugin Classes
 */
spl_autoload_register(function ($class) {
    // Only autoload our classes
    if (strpos($class, 'Demo_Builder') !== 0) {
        return;
    }
    
    // Convert class name to filename
    $class_file = strtolower(str_replace('_', '-', $class));
    $class_file = 'class-' . str_replace('demo-builder-', '', $class_file);
    
    // Check includes directory
    $includes_path = DEMO_BUILDER_PLUGIN_DIR . 'includes/' . $class_file . '.php';
    if (file_exists($includes_path)) {
        require_once $includes_path;
        return;
    }
    
    // Check includes/hooks directory
    $hooks_path = DEMO_BUILDER_PLUGIN_DIR . 'includes/hooks/' . $class_file . '.php';
    if (file_exists($hooks_path)) {
        require_once $hooks_path;
        return;
    }
    
    // Check admin directory
    $admin_path = DEMO_BUILDER_PLUGIN_DIR . 'admin/' . $class_file . '.php';
    if (file_exists($admin_path)) {
        require_once $admin_path;
        return;
    }
    
    // Check public directory
    $public_path = DEMO_BUILDER_PLUGIN_DIR . 'public/' . $class_file . '.php';
    if (file_exists($public_path)) {
        require_once $public_path;
        return;
    }
});

/**
 * Initialize Plugin
 */
function demo_builder_init() {
    // Initialize main plugin class
    if (class_exists('Demo_Builder_Core')) {
        Demo_Builder_Core::get_instance();
    }
    
    // Initialize Scheduled Hooks (for cron)
    if (class_exists('Demo_Builder_Scheduled_Hooks')) {
        Demo_Builder_Scheduled_Hooks::get_instance();
    }
    
    // Initialize Permission Hooks (for demo restrictions)
    if (class_exists('Demo_Builder_Permission_Hooks')) {
        Demo_Builder_Permission_Hooks::get_instance();
    }
    
    // Initialize Countdown Timer
    if (class_exists('Demo_Builder_Countdown')) {
        Demo_Builder_Countdown::get_instance();
    }
}
add_action('plugins_loaded', 'demo_builder_init', 20);

/**
 * Admin Init
 */
function demo_builder_admin_init() {
    if (is_admin() && class_exists('Demo_Builder_Admin')) {
        Demo_Builder_Admin::get_instance();
    }
    
    // Initialize Backup class
    if (is_admin() && class_exists('Demo_Builder_Backup')) {
        Demo_Builder_Backup::get_instance();
    }
    
    // Initialize Restore class
    if (is_admin() && class_exists('Demo_Builder_Restore')) {
        Demo_Builder_Restore::get_instance();
    }
}
add_action('admin_init', 'demo_builder_admin_init');

/**
 * Frontend Init
 */
function demo_builder_frontend_init() {
    if (!is_admin() && class_exists('Demo_Builder_Public')) {
        Demo_Builder_Public::get_instance();
    }
}
add_action('init', 'demo_builder_frontend_init', 10);
