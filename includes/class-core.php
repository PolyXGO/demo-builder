<?php
/**
 * Core Plugin Class
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Demo_Builder_Core
 * 
 * Main plugin core class - singleton pattern
 */
class Demo_Builder_Core {

    /**
     * Instance
     *
     * @var Demo_Builder_Core
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
     * @return Demo_Builder_Core
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
        $this->load_settings();
        $this->init_hooks();
    }

    /**
     * Load settings
     */
    private function load_settings() {
        $this->settings = get_option('demo_builder_settings', []);
    }

    /**
     * Get settings
     *
     * @param string $key Optional key to get specific setting
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function get_settings($key = null, $default = null) {
        if (null === $key) {
            return $this->settings;
        }
        
        // Support dot notation: 'backup.max_backups'
        $keys = explode('.', $key);
        $value = $this->settings;
        
        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }
        
        return $value;
    }

    /**
     * Update settings
     *
     * @param array $new_settings
     * @return bool
     */
    public function update_settings($new_settings) {
        $this->settings = array_replace_recursive($this->settings, $new_settings);
        return update_option('demo_builder_settings', $this->settings);
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Frontend countdown timer
        if ($this->get_settings('countdown.enabled') && $this->get_settings('countdown.show_on_frontend')) {
            add_action('wp_footer', [$this, 'render_countdown_timer']);
        }
        
        // Maintenance mode
        if ($this->get_settings('maintenance.enabled')) {
            add_action('template_redirect', [$this, 'check_maintenance_mode']);
        }
        
        // Demo account restrictions
        if ($this->get_settings('general.enable_plugin')) {
            add_action('init', [$this, 'init_restrictions']);
        }
    }

    /**
     * Render countdown timer
     */
    public function render_countdown_timer() {
        // Get next restore time
        $next_restore = wp_next_scheduled('demo_builder_auto_restore');
        
        if (!$next_restore) {
            return;
        }
        
        $position = $this->get_settings('countdown.position', 'fixed');
        $message = $this->get_settings('countdown.message', 'Demo resets in {{countdown}}');
        
        echo '<div id="db-countdown-timer" class="db-countdown db-countdown--' . esc_attr($position) . '" data-target="' . esc_attr($next_restore) . '" data-message="' . esc_attr($message) . '"></div>';
    }

    /**
     * Check maintenance mode
     */
    public function check_maintenance_mode() {
        // Skip for admins
        if (current_user_can('manage_options')) {
            $bypass_key = $this->get_settings('maintenance.bypass_key');
            if (!empty($bypass_key) && isset($_GET['bypass']) && $_GET['bypass'] === $bypass_key) {
                return;
            }
            return;
        }
        
        // Check IP whitelist
        $whitelist = $this->get_settings('maintenance.whitelist_ips', []);
        $user_ip = $this->get_user_ip();
        
        if (in_array($user_ip, $whitelist)) {
            return;
        }
        
        // Check bypass key
        $bypass_key = $this->get_settings('maintenance.bypass_key');
        if (!empty($bypass_key) && isset($_GET['bypass']) && $_GET['bypass'] === $bypass_key) {
            return;
        }
        
        // Show maintenance page
        $this->show_maintenance_page();
    }

    /**
     * Show maintenance page
     */
    private function show_maintenance_page() {
        $title = $this->get_settings('maintenance.title', 'Under Maintenance');
        $message = $this->get_settings('maintenance.message', 'We are performing scheduled maintenance. Please check back soon.');
        
        status_header(503);
        header('Retry-After: 3600');
        
        include DEMO_BUILDER_PLUGIN_DIR . 'admin/views/maintenance.php';
        exit;
    }

    /**
     * Get user IP
     *
     * @return string
     */
    private function get_user_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }
        
        return $ip;
    }

    /**
     * Initialize restrictions
     */
    public function init_restrictions() {
        // Will be implemented in Phase 4
    }

    /**
     * Check if current user is demo account
     *
     * @return bool
     */
    public function is_demo_user() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        global $wpdb;
        $user_id = get_current_user_id();
        $table = $wpdb->prefix . 'demobuilder_demo_accounts';
        
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND is_active = 1",
            $user_id
        ));
        
        return $result > 0;
    }

    /**
     * Log event
     *
     * @param string $type Event type
     * @param string $message Message
     * @param array $context Additional context
     * @param string $subtype Event subtype
     */
    public function log($type, $message, $context = [], $subtype = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'demobuilder_logs';
        
        $wpdb->insert($table, [
            'event_type' => sanitize_text_field($type),
            'event_subtype' => sanitize_text_field($subtype),
            'message' => sanitize_textarea_field($message),
            'context' => wp_json_encode($context),
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_user_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? substr(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])), 0, 500) : '',
            'created_at' => current_time('mysql'),
        ]);
    }
}
