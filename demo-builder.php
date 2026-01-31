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
 * Load Plugin Textdomain - Must be on init or later for WP 6.7+
 */
function demo_builder_load_textdomain() {
    load_plugin_textdomain(
        'demo-builder',
        false,
        dirname(DEMO_BUILDER_PLUGIN_BASENAME) . '/languages/'
    );
}
add_action('init', 'demo_builder_load_textdomain', 1);

/**
 * Initialize Plugin - All initialization on init action
 */
function demo_builder_init() {
    // Initialize main plugin class
    Demo_Builder_Core::get_instance();
    
    // Initialize Scheduled Hooks (for cron)
    Demo_Builder_Scheduled_Hooks::get_instance();
    
    // Initialize Permission Hooks (for demo restrictions)
    Demo_Builder_Permission_Hooks::get_instance();
    
    // Initialize Countdown Timer
    Demo_Builder_Countdown::get_instance();
    
    // Initialize Maintenance Mode
    Demo_Builder_Maintenance::get_instance();
    
    // Admin Classes
    if (is_admin()) {
        Demo_Builder_Admin::get_instance();
        Demo_Builder_Backup::get_instance();
        Demo_Builder_Restore::get_instance();
        Demo_Builder_Demo_Accounts::get_instance();
        Demo_Builder_Performance::get_instance();
        Demo_Builder_Telegram::get_instance();
        Demo_Builder_Upload_Handler::get_instance();
    } else {
        // Public/Frontend Classes
        Demo_Builder_Public::get_instance();
        Demo_Builder_Login_Form::get_instance();
    }
}
add_action('init', 'demo_builder_init', 5);

/**
 * Filter directories to ignore for WordPress Plugin Check (PCP)
 */
add_filter('wp_plugin_check_ignore_directories', function ($dirs) {
    $custom_excludes = ['heraspec', 'documentations', 'tests', 'dist'];
    return array_unique(array_merge($dirs, $custom_excludes));
});

/**
 * Filter files to ignore for WordPress Plugin Check (PCP)
 */
add_filter('wp_plugin_check_ignore_files', function ($files) {
    // Dynamically find all .sh and .md files in directory
    $excluded_pattern_files = glob(plugin_dir_path(__FILE__) . '{*.sh,*.md,.gitignore}', GLOB_BRACE);
    $custom_excludes = $excluded_pattern_files ? array_map('basename', $excluded_pattern_files) : [];

    // Add specific manual excludes and system files
    $custom_excludes = array_merge($custom_excludes, [
        '.DS_Store',
        'Thumbs.db',
        'desktop.ini',
        'error_log',
        '.gitignore',
    ]);
    
    return array_unique(array_merge($files, $custom_excludes));
});
