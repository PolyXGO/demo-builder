<?php
/**
 * Public-facing functionality
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Demo_Builder_Public
 */
class Demo_Builder_Public {

    /**
     * Instance
     *
     * @var Demo_Builder_Public
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
     * @return Demo_Builder_Public
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
        // Enqueue frontend scripts/styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Demo user restrictions
        add_action('init', [$this, 'maybe_apply_demo_restrictions']);
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_assets() {
        // Only enqueue if needed
        $countdown_settings = $this->settings['countdown_frontend'] ?? [];
        
        if (!empty($countdown_settings['enabled'])) {
            // Countdown styles are added inline by class-countdown.php
        }
    }

    /**
     * Apply demo restrictions for demo users
     */
    public function maybe_apply_demo_restrictions() {
        // Check if current user is a demo user
        if (!$this->is_demo_user()) {
            return;
        }
        
        // Apply restrictions (will be implemented in Phase 5)
    }

    /**
     * Check if current user is a demo user
     *
     * @return bool
     */
    public function is_demo_user() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user = wp_get_current_user();
        
        // Check if user was created by demo builder
        $is_demo = get_user_meta($user->ID, '_demo_builder_demo_user', true);
        
        return !empty($is_demo);
    }

    /**
     * Get demo user session info
     *
     * @return array|null
     */
    public function get_demo_session_info() {
        if (!$this->is_demo_user()) {
            return null;
        }
        
        $user_id = get_current_user_id();
        
        global $wpdb;
        $table = $wpdb->prefix . 'demobuilder_demo_accounts';
        
        $account = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE user_id = %d",
            $user_id
        ));
        
        if (!$account) {
            return null;
        }
        
        return [
            'username' => $account->username,
            'created_at' => $account->created_at,
            'expires_at' => $account->expires_at,
            'is_expired' => strtotime($account->expires_at) < time(),
        ];
    }
}

// Initialize on frontend only
if (!is_admin()) {
    add_action('init', function() {
        Demo_Builder_Public::get_instance();
    }, 5);
}
