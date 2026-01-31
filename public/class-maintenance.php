<?php
/**
 * Maintenance Mode Class
 *
 * Handles maintenance mode display and bypass logic
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Demo_Builder_Maintenance
 */
class Demo_Builder_Maintenance {

    /**
     * Instance
     *
     * @var Demo_Builder_Maintenance
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
     * @return Demo_Builder_Maintenance
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
        $all_settings = get_option('demo_builder_settings', []);
        $this->settings = $all_settings['maintenance'] ?? $this->get_default_settings();
        $this->init_hooks();
    }

    /**
     * Get default settings
     *
     * @return array
     */
    private function get_default_settings() {
        return [
            'enabled' => false,
            'title' => __('Under Maintenance', 'demo-builder'),
            'description' => __('We are currently performing scheduled maintenance. Please check back soon.', 'demo-builder'),
            'logo' => '',
            'background_image' => '',
            'custom_html' => '',
            'show_countdown' => true,
            'admin_bypass' => true,
            'ip_whitelist' => '',
            'bypass_key' => wp_generate_password(16, false),
        ];
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Check maintenance on every request
        add_action('template_redirect', [$this, 'check_maintenance_mode'], 1);
        
        // Admin bar toggle
        add_action('admin_bar_menu', [$this, 'admin_bar_toggle'], 999);
        
        // AJAX handlers
        add_action('wp_ajax_demo_builder_maintenance_toggle', [$this, 'ajax_toggle']);
        add_action('wp_ajax_demo_builder_maintenance_save', [$this, 'ajax_save_settings']);
        
        // Hooks for auto-enable during restore
        add_action('demo_builder_restore_started', [$this, 'enable_maintenance']);
        add_action('demo_builder_restore_completed', [$this, 'disable_maintenance']);
        add_action('demo_builder_restore_failed', [$this, 'disable_maintenance']);
    }

    /**
     * Check if maintenance mode is active
     *
     * @return bool
     */
    public function is_maintenance_mode() {
        return !empty($this->settings['enabled']) || get_option('demo_builder_maintenance_mode') === '1';
    }

    /**
     * Check maintenance mode on page load
     */
    public function check_maintenance_mode() {
        if (!$this->is_maintenance_mode()) {
            return;
        }
        
        // Don't block admin
        if (is_admin()) {
            return;
        }
        
        // Don't block AJAX
        if (wp_doing_ajax()) {
            return;
        }
        
        // Don't block cron
        if (wp_doing_cron()) {
            return;
        }
        
        // Don't block wp-login.php
        if (strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false) {
            return;
        }
        
        // Check bypass
        if ($this->should_bypass()) {
            return;
        }
        
        // Display maintenance page
        $this->display_maintenance_page();
        exit;
    }

    /**
     * Check if current user/request should bypass maintenance
     *
     * @return bool
     */
    private function should_bypass() {
        // Admin bypass
        if (!empty($this->settings['admin_bypass']) && current_user_can('manage_options')) {
            return true;
        }
        
        // IP whitelist bypass
        $client_ip = $this->get_client_ip();
        if ($this->is_ip_whitelisted($client_ip)) {
            return true;
        }
        
        // URL bypass key
        $bypass_key = $this->settings['bypass_key'] ?? '';
        if (!empty($bypass_key) && isset($_GET['bypass']) && $_GET['bypass'] === $bypass_key) {
            // Set cookie for future requests
            setcookie('demo_builder_bypass', $bypass_key, time() + 3600, '/');
            return true;
        }
        
        // Cookie bypass
        if (!empty($bypass_key) && isset($_COOKIE['demo_builder_bypass']) && $_COOKIE['demo_builder_bypass'] === $bypass_key) {
            return true;
        }
        
        return false;
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    private function get_client_ip() {
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Handle comma-separated IPs (X-Forwarded-For)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                return $ip;
            }
        }
        
        return '';
    }

    /**
     * Check if IP is whitelisted
     *
     * @param string $ip IP address
     * @return bool
     */
    private function is_ip_whitelisted($ip) {
        if (empty($this->settings['ip_whitelist'])) {
            return false;
        }
        
        $whitelist = array_map('trim', explode(',', $this->settings['ip_whitelist']));
        
        foreach ($whitelist as $allowed) {
            if ($ip === $allowed) {
                return true;
            }
            // Simple CIDR matching
            if (strpos($allowed, '/') !== false && $this->ip_in_range($ip, $allowed)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if IP is in CIDR range
     *
     * @param string $ip IP address
     * @param string $cidr CIDR range
     * @return bool
     */
    private function ip_in_range($ip, $cidr) {
        list($subnet, $mask) = explode('/', $cidr);
        $mask = (int) $mask;
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ip_long = ip2long($ip);
            $subnet_long = ip2long($subnet);
            $mask_long = -1 << (32 - $mask);
            return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
        }
        
        return false;
    }

    /**
     * Display maintenance page
     */
    private function display_maintenance_page() {
        // Set HTTP status
        status_header(503);
        header('Retry-After: 3600');
        
        // Get settings
        $title = $this->settings['title'] ?? __('Under Maintenance', 'demo-builder');
        $description = $this->settings['description'] ?? '';
        $logo = $this->settings['logo'] ?? '';
        $background_image = $this->settings['background_image'] ?? '';
        $custom_html = $this->settings['custom_html'] ?? '';
        $show_countdown = !empty($this->settings['show_countdown']);
        
        // Get next restore time
        $next_restore = null;
        if ($show_countdown) {
            $next_restore = wp_next_scheduled('demo_builder_scheduled_restore');
        }
        
        // Calculate countdown
        $countdown_initial = '';
        if ($next_restore) {
            $diff = $next_restore - time();
            if ($diff > 0) {
                $hours = floor($diff / 3600);
                $minutes = floor(($diff % 3600) / 60);
                $seconds = $diff % 60;
                $countdown_initial = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            }
        }
        
        // Include template
        include DEMO_BUILDER_PLUGIN_DIR . 'public/views/maintenance.php';
    }

    /**
     * Enable maintenance mode
     */
    public function enable_maintenance() {
        $settings = get_option('demo_builder_settings', []);
        $settings['maintenance']['enabled'] = true;
        update_option('demo_builder_settings', $settings);
        update_option('demo_builder_maintenance_mode', '1');
        $this->settings['enabled'] = true;
    }

    /**
     * Disable maintenance mode
     */
    public function disable_maintenance() {
        $settings = get_option('demo_builder_settings', []);
        $settings['maintenance']['enabled'] = false;
        update_option('demo_builder_settings', $settings);
        delete_option('demo_builder_maintenance_mode');
        $this->settings['enabled'] = false;
    }

    /**
     * Add admin bar toggle
     *
     * @param WP_Admin_Bar $wp_admin_bar
     */
    public function admin_bar_toggle($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $is_active = $this->is_maintenance_mode();
        
        $wp_admin_bar->add_node([
            'id' => 'demo-builder-maintenance',
            'title' => sprintf(
                '<span class="ab-icon dashicons dashicons-hammer" style="margin-top:2px;"></span> %s',
                $is_active ? __('Maintenance: ON', 'demo-builder') : __('Maintenance: OFF', 'demo-builder')
            ),
            'href' => '#',
            'meta' => [
                'class' => $is_active ? 'db-maintenance-active' : '',
                'onclick' => 'demoBuilderToggleMaintenance(); return false;',
            ],
        ]);
        
        // Add inline script
        add_action('admin_footer', [$this, 'admin_bar_script']);
        add_action('wp_footer', [$this, 'admin_bar_script']);
    }

    /**
     * Admin bar toggle script
     */
    public function admin_bar_script() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <script>
        function demoBuilderToggleMaintenance() {
            jQuery.post(ajaxurl || '<?php echo admin_url('admin-ajax.php'); ?>', {
                action: 'demo_builder_maintenance_toggle',
                nonce: '<?php echo wp_create_nonce('demo_builder_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    location.reload();
                }
            });
        }
        </script>
        <style>
        #wp-admin-bar-demo-builder-maintenance.db-maintenance-active .ab-item {
            background: #dc3545 !important;
            color: white !important;
        }
        </style>
        <?php
    }

    // AJAX Handlers

    /**
     * AJAX: Toggle maintenance
     */
    public function ajax_toggle() {
        check_ajax_referer('demo_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'demo-builder')]);
        }
        
        if ($this->is_maintenance_mode()) {
            $this->disable_maintenance();
            wp_send_json_success(['message' => __('Maintenance mode disabled.', 'demo-builder'), 'enabled' => false]);
        } else {
            $this->enable_maintenance();
            wp_send_json_success(['message' => __('Maintenance mode enabled.', 'demo-builder'), 'enabled' => true]);
        }
    }

    /**
     * AJAX: Save settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('demo_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'demo-builder')]);
        }
        
        $maintenance = [
            'enabled' => isset($_POST['enabled']) && $_POST['enabled'] === 'true',
            'title' => sanitize_text_field(wp_unslash($_POST['title'] ?? '')),
            'description' => wp_kses_post(wp_unslash($_POST['description'] ?? '')),
            'logo' => esc_url_raw(wp_unslash($_POST['logo'] ?? '')),
            'background_image' => esc_url_raw(wp_unslash($_POST['background_image'] ?? '')),
            'custom_html' => wp_kses_post(wp_unslash($_POST['custom_html'] ?? '')),
            'show_countdown' => isset($_POST['show_countdown']) && $_POST['show_countdown'] === 'true',
            'admin_bypass' => isset($_POST['admin_bypass']) && $_POST['admin_bypass'] === 'true',
            'ip_whitelist' => sanitize_text_field(wp_unslash($_POST['ip_whitelist'] ?? '')),
            'bypass_key' => sanitize_text_field(wp_unslash($_POST['bypass_key'] ?? '')),
        ];
        
        $settings = get_option('demo_builder_settings', []);
        $settings['maintenance'] = $maintenance;
        update_option('demo_builder_settings', $settings);
        
        // Update maintenance mode option
        if ($maintenance['enabled']) {
            update_option('demo_builder_maintenance_mode', '1');
        } else {
            delete_option('demo_builder_maintenance_mode');
        }
        
        $this->settings = $maintenance;
        
        wp_send_json_success(['message' => __('Maintenance settings saved!', 'demo-builder')]);
    }
}
