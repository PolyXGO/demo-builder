<?php
/**
 * Login Form Integration
 *
 * Displays demo account selector on wp-login.php
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Demo_Builder_Login_Form
 */
class Demo_Builder_Login_Form {

    /**
     * Instance
     *
     * @var Demo_Builder_Login_Form
     */
    private static $instance = null;

    /**
     * Settings
     *
     * @var array
     */
    private $settings = [];

    /**
     * Get instance
     *
     * @return Demo_Builder_Login_Form
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
        $this->settings = get_option('demo_builder_settings', []);
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add demo selector to login form
        add_action('login_form', [$this, 'render_demo_selector']);
        
        // Enqueue login form assets
        add_action('login_enqueue_scripts', [$this, 'enqueue_login_assets']);
        
        // AJAX for public demo accounts
        add_action('wp_ajax_nopriv_demo_builder_get_public_accounts', [$this, 'ajax_get_public_accounts']);
        add_action('wp_ajax_demo_builder_get_public_accounts', [$this, 'ajax_get_public_accounts']);
    }

    /**
     * Check if demo selector should be displayed
     *
     * @return bool
     */
    private function should_display() {
        $login_settings = $this->settings['login'] ?? [];
        
        if (isset($login_settings['enabled']) && !$login_settings['enabled']) {
            return false;
        }
        
        // Check if there are active demo accounts
        $accounts = $this->get_public_accounts();
        
        return !empty($accounts);
    }

    /**
     * Get public demo accounts
     *
     * @return array
     */
    public function get_public_accounts() {
        global $wpdb;
        $table = $wpdb->prefix . 'demobuilder_demo_accounts';
        
        $accounts = $wpdb->get_results(
            "SELECT a.id, a.role_name, a.password_plain, a.message, u.user_login, u.display_name 
             FROM {$table} a 
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
             WHERE a.is_active = 1 
             ORDER BY a.sort_order ASC"
        );
        
        return $accounts;
    }

    /**
     * Enqueue login assets
     */
    public function enqueue_login_assets() {
        if (!$this->should_display()) {
            return;
        }
        
        wp_enqueue_script(
            'demo-builder-login',
            $this->get_asset_url('js/frontend/login-form.js'),
            ['jquery'],
            DEMO_BUILDER_VERSION,
            true
        );
        
        wp_localize_script('demo-builder-login', 'demoBuilderLogin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'accounts' => $this->get_public_accounts(),
            'i18n' => [
                'selectAccount' => __('Select Demo Account', 'demo-builder'),
                'loginAs' => __('Login as', 'demo-builder'),
            ],
        ]);
        
        // Add inline styles
        $this->add_login_styles();
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
     * Add login form styles
     */
    private function add_login_styles() {
        $css = $this->get_login_css();
        wp_add_inline_style('login', $css);
    }

    /**
     * Get login CSS
     *
     * @return string
     */
    private function get_login_css() {
        return '
            .db-login-selector {
                background: linear-gradient(135deg, #F0F4FF 0%, #FDF4FF 100%);
                border-radius: 12px;
                padding: 20px;
                margin-bottom: 20px;
                border: 1px solid #E0E7FF;
            }
            .db-login-selector-title {
                font-size: 14px;
                font-weight: 600;
                color: #4338CA;
                margin-bottom: 15px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .db-login-selector-title:before {
                content: "👤";
            }
            .db-login-accounts {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .db-login-account {
                background: #FFFFFF;
                border: 1px solid #E0E7FF;
                border-radius: 8px;
                padding: 12px 15px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: space-between;
                transition: all 0.2s ease;
            }
            .db-login-account:hover {
                border-color: #818CF8;
                background: #F0F4FF;
                transform: translateY(-1px);
            }
            .db-login-account-info {
                display: flex;
                flex-direction: column;
                gap: 2px;
            }
            .db-login-account-name {
                font-weight: 600;
                color: #1E293B;
                font-size: 14px;
            }
            .db-login-account-role {
                font-size: 12px;
                color: #64748B;
            }
            .db-login-account-badge {
                background: linear-gradient(135deg, #A5B4FC 0%, #C4B5FD 100%);
                color: #312E81;
                padding: 4px 10px;
                border-radius: 20px;
                font-size: 11px;
                font-weight: 600;
            }
            .db-login-account-message {
                font-size: 11px;
                color: #64748B;
                margin-top: 4px;
                font-style: italic;
            }
        ';
    }

    /**
     * Render demo selector
     */
    public function render_demo_selector() {
        if (!$this->should_display()) {
            return;
        }
        
        $accounts = $this->get_public_accounts();
        
        if (empty($accounts)) {
            return;
        }
        
        include DEMO_BUILDER_PLUGIN_DIR . 'public/views/login-selector.php';
    }

    /**
     * AJAX: Get public accounts
     */
    public function ajax_get_public_accounts() {
        $accounts = $this->get_public_accounts();
        
        wp_send_json_success(['accounts' => $accounts]);
    }
}
