<?php
/**
 * Countdown Timer Class
 *
 * Handles countdown timer display for admin and frontend
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Demo_Builder_Countdown
 */
class Demo_Builder_Countdown {

    /**
     * Instance
     *
     * @var Demo_Builder_Countdown
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
     * @return Demo_Builder_Countdown
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
        // Admin countdown
        if (is_admin()) {
            add_action('admin_footer', [$this, 'render_admin_countdown']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        }
        
        // Frontend countdown
        add_action('wp_footer', [$this, 'render_frontend_countdown']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        
        // Admin bar countdown
        add_action('admin_bar_menu', [$this, 'add_admin_bar_countdown'], 999);
        
        // Content filter for before/after content positions
        add_filter('the_content', [$this, 'filter_content_countdown'], 999);
        
        // AJAX for countdown settings
        add_action('wp_ajax_demo_builder_save_countdown_settings', [$this, 'ajax_save_countdown_settings']);
    }

    /**
     * Get countdown settings
     *
     * @param string $context 'admin' or 'frontend'
     * @return array
     */
    public function get_countdown_settings($context = 'admin') {
        $key = 'countdown_' . $context;
        $defaults = [
            'enabled' => ($context === 'admin'),
            'position' => ($context === 'admin') ? 'fixed' : 'before_content',
            'position_x' => '20px',
            'position_y' => '20px',
            'css_selector' => '',
            'message_template' => ($context === 'admin') 
                ? __('Next restore in: {{countdown}}', 'demo-builder')
                : __('Demo resets in: {{countdown}}', 'demo-builder'),
            'overdue_message' => ($context === 'admin')
                ? __('Restore overdue!', 'demo-builder')
                : __('Demo reset imminent!', 'demo-builder'),
            'show_on_mobile' => true,
            'background' => 'linear-gradient(135deg, #A5B4FC 0%, #C4B5FD 100%)',
            'text_color' => '#334155',
            'border_radius' => '12px',
            'font_size' => '14px',
        ];
        
        return array_merge($defaults, $this->settings[$key] ?? []);
    }

    /**
     * Get next restore timestamp
     *
     * @return int|null
     */
    public function get_next_restore() {
        $settings = $this->settings['restore'] ?? [];
        
        // Check scheduled event first
        $scheduled = wp_next_scheduled('demo_builder_auto_restore');
        if ($scheduled) {
            return $scheduled;
        }
        
        return $settings['next_auto_restore'] ?? null;
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        $countdown_settings = $this->get_countdown_settings('admin');
        
        if (empty($countdown_settings['enabled'])) {
            return;
        }
        
        wp_enqueue_script(
            'demo-builder-countdown',
            $this->get_asset_url('js/admin/countdown-timer.js'),
            ['jquery'],
            DEMO_BUILDER_VERSION,
            true
        );
        
        wp_localize_script('demo-builder-countdown', 'demoBuilderCountdown', [
            'nextRestore' => $this->get_next_restore(),
            'serverTime' => time(),
            'settings' => $countdown_settings,
            'i18n' => [
                'days' => __('days', 'demo-builder'),
                'hours' => __('hours', 'demo-builder'),
                'minutes' => __('min', 'demo-builder'),
                'seconds' => __('sec', 'demo-builder'),
            ],
        ]);
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        $countdown_settings = $this->get_countdown_settings('frontend');
        
        if (empty($countdown_settings['enabled'])) {
            return;
        }
        
        // Check if auto-restore is enabled
        $restore_settings = $this->settings['restore'] ?? [];
        if (empty($restore_settings['auto_restore_enabled'])) {
            return;
        }
        
        wp_enqueue_script(
            'demo-builder-countdown-frontend',
            $this->get_asset_url('js/frontend/countdown-timer.js'),
            ['jquery'],
            DEMO_BUILDER_VERSION,
            true
        );
        
        wp_localize_script('demo-builder-countdown-frontend', 'demoBuilderCountdown', [
            'nextRestore' => $this->get_next_restore(),
            'serverTime' => time(),
            'settings' => $countdown_settings,
            'i18n' => [
                'days' => __('days', 'demo-builder'),
                'hours' => __('hours', 'demo-builder'),
                'minutes' => __('min', 'demo-builder'),
                'seconds' => __('sec', 'demo-builder'),
            ],
        ]);
        
        // Add inline styles
        $this->add_countdown_styles($countdown_settings);
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
     * Add countdown styles
     *
     * @param array $settings Countdown settings
     */
    private function add_countdown_styles($settings) {
        $css = "
            .db-countdown {
                background: {$settings['background']};
                color: {$settings['text_color']};
                padding: 8px 16px;
                border-radius: {$settings['border_radius']};
                font-size: {$settings['font_size']};
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                z-index: 99999;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            .db-countdown-fixed {
                position: fixed;
                bottom: {$settings['position_y']};
                right: {$settings['position_x']};
            }
            .db-countdown-icon {
                font-size: 16px;
            }
            .db-countdown-time {
                font-weight: 600;
                font-variant-numeric: tabular-nums;
            }
            .db-countdown-overdue {
                background: linear-gradient(135deg, #FCA5A5 0%, #F87171 100%);
                color: #FFFFFF;
            }
        ";
        
        if (empty($settings['show_on_mobile'])) {
            $css .= "
                @media (max-width: 768px) {
                    .db-countdown { display: none !important; }
                }
            ";
        }
        
        wp_add_inline_style('wp-block-library', $css);
    }

    /**
     * Render admin countdown
     */
    public function render_admin_countdown() {
        $countdown_settings = $this->get_countdown_settings('admin');
        
        if (empty($countdown_settings['enabled'])) {
            return;
        }
        
        if ($countdown_settings['position'] !== 'fixed') {
            return; // Other positions handled differently
        }
        
        $next_restore = $this->get_next_restore();
        if (!$next_restore) {
            return;
        }
        
        echo '<div id="db-admin-countdown" class="db-countdown db-countdown-fixed" style="display:none;"></div>';
    }

    /**
     * Render frontend countdown
     */
    public function render_frontend_countdown() {
        $countdown_settings = $this->get_countdown_settings('frontend');
        
        if (empty($countdown_settings['enabled'])) {
            return;
        }
        
        $restore_settings = $this->settings['restore'] ?? [];
        if (empty($restore_settings['auto_restore_enabled'])) {
            return;
        }
        
        if ($countdown_settings['position'] !== 'fixed') {
            return;
        }
        
        $next_restore = $this->get_next_restore();
        if (!$next_restore) {
            return;
        }
        
        echo '<div id="db-frontend-countdown" class="db-countdown db-countdown-fixed" style="display:none;"></div>';
    }

    /**
     * Add countdown to admin bar
     *
     * @param WP_Admin_Bar $admin_bar Admin bar object
     */
    public function add_admin_bar_countdown($admin_bar) {
        $countdown_settings = $this->get_countdown_settings('admin');
        
        if (empty($countdown_settings['enabled'])) {
            return;
        }
        
        if ($countdown_settings['position'] !== 'admin_bar') {
            return;
        }
        
        $next_restore = $this->get_next_restore();
        if (!$next_restore) {
            return;
        }
        
        $admin_bar->add_node([
            'id' => 'demo-builder-countdown',
            'title' => '<span id="db-adminbar-countdown" class="ab-icon dashicons dashicons-backup"></span> <span class="db-countdown-time">--:--:--</span>',
            'href' => admin_url('admin.php?page=demo-builder-backup'),
            'meta' => [
                'title' => __('Next Demo Restore', 'demo-builder'),
            ],
        ]);
    }

    /**
     * Filter content for before/after positions
     *
     * @param string $content Post content
     * @return string
     */
    public function filter_content_countdown($content) {
        if (!is_singular() || !in_the_loop() || !is_main_query()) {
            return $content;
        }
        
        $countdown_settings = $this->get_countdown_settings('frontend');
        
        if (empty($countdown_settings['enabled'])) {
            return $content;
        }
        
        $restore_settings = $this->settings['restore'] ?? [];
        if (empty($restore_settings['auto_restore_enabled'])) {
            return $content;
        }
        
        $next_restore = $this->get_next_restore();
        if (!$next_restore) {
            return $content;
        }
        
        $countdown_html = '<div class="db-countdown db-countdown-content" id="db-content-countdown" style="display:none;"></div>';
        
        if ($countdown_settings['position'] === 'before_content') {
            return $countdown_html . $content;
        } elseif ($countdown_settings['position'] === 'after_content') {
            return $content . $countdown_html;
        }
        
        return $content;
    }

    /**
     * AJAX: Save countdown settings
     */
    public function ajax_save_countdown_settings() {
        if (!check_ajax_referer('demo_builder_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Security check failed.', 'demo-builder')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'demo-builder')]);
        }
        
        $context = sanitize_text_field($_POST['context'] ?? 'admin');
        $key = 'countdown_' . $context;
        
        $settings = get_option('demo_builder_settings', []);
        
        $settings[$key] = [
            'enabled' => isset($_POST['enabled']) && $_POST['enabled'] === 'true',
            'position' => sanitize_text_field($_POST['position'] ?? 'fixed'),
            'position_x' => sanitize_text_field($_POST['position_x'] ?? '20px'),
            'position_y' => sanitize_text_field($_POST['position_y'] ?? '20px'),
            'css_selector' => sanitize_text_field($_POST['css_selector'] ?? ''),
            'message_template' => sanitize_text_field($_POST['message_template'] ?? ''),
            'overdue_message' => sanitize_text_field($_POST['overdue_message'] ?? ''),
            'show_on_mobile' => isset($_POST['show_on_mobile']) && $_POST['show_on_mobile'] === 'true',
            'background' => sanitize_text_field($_POST['background'] ?? ''),
            'text_color' => sanitize_hex_color($_POST['text_color'] ?? '#334155'),
            'border_radius' => sanitize_text_field($_POST['border_radius'] ?? '12px'),
            'font_size' => sanitize_text_field($_POST['font_size'] ?? '14px'),
        ];
        
        update_option('demo_builder_settings', $settings);
        
        wp_send_json_success(['message' => __('Countdown settings saved!', 'demo-builder')]);
    }
}
