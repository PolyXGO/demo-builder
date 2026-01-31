<?php
/**
 * Admin Class
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Demo_Builder_Admin
 * 
 * Handles admin menu, pages, and assets
 */
class Demo_Builder_Admin {

    /**
     * Instance
     *
     * @var Demo_Builder_Admin
     */
    private static $instance = null;

    /**
     * Menu slug
     *
     * @var string
     */
    const MENU_SLUG = 'demo-builder';

    /**
     * Get instance
     *
     * @return Demo_Builder_Admin
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
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_ajax_demo_builder_save_settings', [$this, 'ajax_save_settings']);
    }

    /**
     * Register admin menu
     */
    public function register_admin_menu() {
        // Check capability
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Main menu
        add_menu_page(
            __('Demo Builder', 'demo-builder'),
            __('Demo Builder', 'demo-builder'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'render_dashboard_page'],
            'dashicons-database-view',
            65
        );
        
        // Dashboard submenu (same as main)
        add_submenu_page(
            self::MENU_SLUG,
            __('Dashboard', 'demo-builder'),
            __('Dashboard', 'demo-builder'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'render_dashboard_page']
        );
        
        // Demo Accounts
        add_submenu_page(
            self::MENU_SLUG,
            __('Demo Accounts', 'demo-builder'),
            __('Demo Accounts', 'demo-builder'),
            'manage_options',
            self::MENU_SLUG . '-accounts',
            [$this, 'render_accounts_page']
        );
        
        // Backup & Restore
        add_submenu_page(
            self::MENU_SLUG,
            __('Backup & Restore', 'demo-builder'),
            __('Backup & Restore', 'demo-builder'),
            'manage_options',
            self::MENU_SLUG . '-backup',
            [$this, 'render_backup_page']
        );
        
        // Settings
        add_submenu_page(
            self::MENU_SLUG,
            __('Settings', 'demo-builder'),
            __('Settings', 'demo-builder'),
            'manage_options',
            self::MENU_SLUG . '-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public function enqueue_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, self::MENU_SLUG) === false) {
            return;
        }
        
        $version = DEMO_BUILDER_VERSION;
        
        // Libraries (Copied to dist, but maintained in lib/ subfolder)
        wp_enqueue_style(
            'sweetalert2',
            $this->get_asset_url('lib/sweetalert2/sweetalert2.min.css'),
            [],
            '11.0.0'
        );
        
        wp_enqueue_script(
            'sweetalert2',
            $this->get_asset_url('lib/sweetalert2/sweetalert2.min.js'),
            [],
            '11.0.0',
            true
        );
        
        wp_enqueue_script(
            'vue',
            $this->get_asset_url('lib/vue/vue.global.prod.js'),
            [],
            '3.4.27',
            true
        );
        
        // Plugin styles
        wp_enqueue_style(
            'demo-builder-admin',
            $this->get_asset_url('css/admin.css'),
            ['sweetalert2'],
            $version
        );
        
        // Plugin scripts
        wp_enqueue_script(
            'demo-builder-admin',
            $this->get_asset_url('js/admin/common.js'),
            ['jquery', 'sweetalert2', 'vue'],
            $version,
            true
        );
        
        // Localize script
        wp_localize_script('demo-builder-admin', 'demoBuilderData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('demo_builder_nonce'),
            'pluginUrl' => DEMO_BUILDER_PLUGIN_URL,
            'i18n' => $this->get_i18n_strings(),
        ]);
        
        // Page-specific scripts
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        
        if ($page === self::MENU_SLUG . '-backup') {
            wp_enqueue_script(
                'demo-builder-upload-chunked',
                $this->get_asset_url('js/admin/upload-chunked.js'),
                ['jquery'],
                $version,
                true
            );
            
            wp_enqueue_script(
                'demo-builder-backup',
                $this->get_asset_url('js/admin/backup-restore.js'),
                ['demo-builder-admin', 'demo-builder-upload-chunked'],
                $version,
                true
            );
        }
        
        if ($page === self::MENU_SLUG . '-accounts') {
            wp_enqueue_script(
                'demo-builder-accounts',
                $this->get_asset_url('js/admin/demo-accounts.js'),
                ['demo-builder-admin'],
                $version,
                true
            );
        }
        
        if ($page === self::MENU_SLUG . '-settings') {
            wp_enqueue_script(
                'demo-builder-settings',
                $this->get_asset_url('js/admin/settings.js'),
                ['demo-builder-admin'],
                $version,
                true
            );
        }
    }

    /**
     * Get asset URL, prioritizing dist/ version if it exists
     *
     * @param string $path Relative path from assets/
     * @return string
     */
    private function get_asset_url($path) {
        $is_lib = strpos($path, 'lib/') === 0;
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        
        // If it's a plugin asset (not lib) and doesn't already have .min., try to find minified version in dist
        if (!$is_lib && ($ext === 'js' || $ext === 'css') && strpos($path, '.min.') === false) {
            $min_path = str_replace(".{$ext}", ".min.{$ext}", $path);
            if (file_exists(DEMO_BUILDER_PLUGIN_DIR . 'dist/assets/' . $min_path)) {
                return DEMO_BUILDER_PLUGIN_URL . 'dist/assets/' . $min_path;
            }
        }
        
        // Check if dist/ version exists at all
        if (file_exists(DEMO_BUILDER_PLUGIN_DIR . 'dist/assets/' . $path)) {
            return DEMO_BUILDER_PLUGIN_URL . 'dist/assets/' . $path;
        }
        
        // Fallback to original assets/
        return DEMO_BUILDER_PLUGIN_URL . 'assets/' . $path;
    }

    /**
     * Get i18n strings for JavaScript
     *
     * @return array
     */
    private function get_i18n_strings() {
        return [
            // General
            'confirm' => __('Confirm', 'demo-builder'),
            'cancel' => __('Cancel', 'demo-builder'),
            'save' => __('Save', 'demo-builder'),
            'delete' => __('Delete', 'demo-builder'),
            'edit' => __('Edit', 'demo-builder'),
            'loading' => __('Loading...', 'demo-builder'),
            'pleaseWait' => __('Please wait...', 'demo-builder'),
            'yes' => __('Yes', 'demo-builder'),
            'no' => __('No', 'demo-builder'),
            
            // Success messages
            'saved' => __('Saved successfully!', 'demo-builder'),
            'deleted' => __('Deleted successfully!', 'demo-builder'),
            'created' => __('Created successfully!', 'demo-builder'),
            'updated' => __('Updated successfully!', 'demo-builder'),
            
            // Error messages
            'errorGeneric' => __('An error occurred. Please try again.', 'demo-builder'),
            'networkError' => __('Network error. Please check your connection.', 'demo-builder'),
            
            // Confirmations
            'confirmDelete' => __('Are you sure you want to delete this?', 'demo-builder'),
            'cannotUndo' => __('This action cannot be undone.', 'demo-builder'),
            
            // Backup specific
            'backupCreating' => __('Creating backup...', 'demo-builder'),
            'backupCreated' => __('Backup created successfully!', 'demo-builder'),
            'restoreInProgress' => __('Restore in progress...', 'demo-builder'),
            'restoreComplete' => __('Restore completed!', 'demo-builder'),
            'confirmRestore' => __('Are you sure you want to restore this backup?', 'demo-builder'),
            'confirmDeleteBackup' => __('Are you sure you want to delete this backup?', 'demo-builder'),
            
            // Demo accounts
            'generateAccounts' => __('Generate demo accounts?', 'demo-builder'),
            'truncateAccounts' => __('Remove all demo accounts?', 'demo-builder'),
        ];
    }

    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        include DEMO_BUILDER_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Render accounts page
     */
    public function render_accounts_page() {
        include DEMO_BUILDER_PLUGIN_DIR . 'admin/views/demo-accounts.php';
    }

    /**
     * Render backup page
     */
    public function render_backup_page() {
        include DEMO_BUILDER_PLUGIN_DIR . 'admin/views/backup-restore.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        include DEMO_BUILDER_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * AJAX save settings
     */
    public function ajax_save_settings() {
        // Verify nonce
        if (!check_ajax_referer('demo_builder_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed.', 'demo-builder')]);
        }
        
        // Check capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'demo-builder')]);
        }
        
        // Get settings from request
        $tab = isset($_POST['tab']) ? sanitize_text_field(wp_unslash($_POST['tab'])) : '';
        $settings = isset($_POST['settings']) ? $_POST['settings'] : [];
        
        if (empty($tab) || empty($settings)) {
            wp_send_json_error(['message' => __('Invalid request.', 'demo-builder')]);
        }
        
        // Sanitize settings based on tab
        $sanitized = $this->sanitize_settings($tab, $settings);
        
        // Update settings
        $current = get_option('demo_builder_settings', []);
        $current[$tab] = $sanitized;
        
        update_option('demo_builder_settings', $current);
        
        // Log
        if (class_exists('Demo_Builder_Core')) {
            Demo_Builder_Core::get_instance()->log('settings', "Settings updated: {$tab}", $sanitized);
        }
        
        wp_send_json_success(['message' => __('Settings saved successfully!', 'demo-builder')]);
    }

    /**
     * Sanitize settings
     *
     * @param string $tab
     * @param array $settings
     * @return array
     */
    private function sanitize_settings($tab, $settings) {
        $sanitized = [];
        
        foreach ($settings as $key => $value) {
            $key = sanitize_key($key);
            
            if (is_array($value)) {
                $sanitized[$key] = array_map('sanitize_text_field', $value);
            } elseif (is_bool($value) || $value === 'true' || $value === 'false') {
                $sanitized[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            } elseif (is_numeric($value)) {
                $sanitized[$key] = intval($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
}
