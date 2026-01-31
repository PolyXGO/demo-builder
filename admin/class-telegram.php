<?php
/**
 * Telegram Notifications Class
 *
 * Handles Telegram bot integration for notifications
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Demo_Builder_Telegram
 */
class Demo_Builder_Telegram {

    /**
     * Instance
     *
     * @var Demo_Builder_Telegram
     */
    private static $instance = null;

    /**
     * Settings
     *
     * @var array
     */
    private $settings = [];

    /**
     * Telegram API URL
     *
     * @var string
     */
    private $api_url = 'https://api.telegram.org/bot';

    /**
     * Get instance
     *
     * @return Demo_Builder_Telegram
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
        $this->settings = $all_settings['telegram'] ?? $this->get_default_settings();
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
            'bot_token' => '',
            'chat_id' => '',
            'notify_backup_start' => true,
            'notify_backup_success' => true,
            'notify_backup_failed' => true,
            'notify_restore_start' => true,
            'notify_restore_success' => true,
            'notify_restore_failed' => true,
            'notify_cloud_sync' => false,
            'notify_low_disk' => true,
        ];
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // AJAX handlers
        add_action('wp_ajax_demo_builder_telegram_test', [$this, 'ajax_test_message']);
        add_action('wp_ajax_demo_builder_telegram_validate', [$this, 'ajax_validate_token']);
        add_action('wp_ajax_demo_builder_telegram_save', [$this, 'ajax_save_settings']);
        
        // Event hooks
        add_action('demo_builder_backup_started', [$this, 'on_backup_started'], 10, 2);
        add_action('demo_builder_backup_created', [$this, 'on_backup_success'], 10, 2);
        add_action('demo_builder_backup_failed', [$this, 'on_backup_failed'], 10, 2);
        add_action('demo_builder_restore_started', [$this, 'on_restore_started'], 10, 1);
        add_action('demo_builder_restore_completed', [$this, 'on_restore_success'], 10, 1);
        add_action('demo_builder_restore_failed', [$this, 'on_restore_failed'], 10, 2);
        add_action('demo_builder_cloud_uploaded', [$this, 'on_cloud_sync'], 10, 3);
        add_action('demo_builder_low_disk_space', [$this, 'on_low_disk'], 10, 1);
    }

    /**
     * Check if notifications are enabled
     *
     * @return bool
     */
    public function is_enabled() {
        return !empty($this->settings['enabled']) 
            && !empty($this->settings['bot_token']) 
            && !empty($this->settings['chat_id']);
    }

    /**
     * Validate bot token
     *
     * @param string $token Bot token
     * @return array
     */
    public function validate_token($token) {
        $url = $this->api_url . $token . '/getMe';
        
        $response = wp_remote_get($url, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message(),
            ];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['ok']) && $body['ok']) {
            return [
                'success' => true,
                'bot' => $body['result'],
            ];
        }
        
        return [
            'success' => false,
            'error' => $body['description'] ?? __('Invalid token', 'demo-builder'),
        ];
    }

    /**
     * Send message
     *
     * @param string $message Message text
     * @param string $parse_mode Parse mode (HTML, Markdown)
     * @return array
     */
    public function send_message($message, $parse_mode = 'HTML') {
        if (!$this->is_enabled()) {
            return ['success' => false, 'error' => 'Notifications not enabled'];
        }
        
        $url = $this->api_url . $this->settings['bot_token'] . '/sendMessage';
        
        $response = wp_remote_post($url, [
            'body' => [
                'chat_id' => $this->settings['chat_id'],
                'text' => $message,
                'parse_mode' => $parse_mode,
                'disable_web_page_preview' => true,
            ],
            'timeout' => 10,
        ]);
        
        if (is_wp_error($response)) {
            $this->log('error', 'Send failed: ' . $response->get_error_message());
            return [
                'success' => false,
                'error' => $response->get_error_message(),
            ];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['ok']) && $body['ok']) {
            return ['success' => true, 'result' => $body['result']];
        }
        
        $this->log('error', 'Send failed: ' . ($body['description'] ?? 'Unknown'));
        return [
            'success' => false,
            'error' => $body['description'] ?? __('Failed to send message', 'demo-builder'),
        ];
    }

    /**
     * Format notification message
     *
     * @param string $event Event type
     * @param string $status Status
     * @param array $details Additional details
     * @return string
     */
    private function format_message($event, $status, $details = []) {
        $site_url = home_url();
        $site_name = get_bloginfo('name');
        $time = wp_date('Y-m-d H:i:s');
        
        $message = "🔧 <b>Demo Builder Notification</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "📌 <b>Event:</b> {$event}\n";
        $message .= "🔘 <b>Status:</b> {$status}\n";
        $message .= "🕐 <b>Time:</b> {$time}\n";
        $message .= "🌐 <b>Site:</b> {$site_name}\n\n";
        
        if (!empty($details)) {
            foreach ($details as $key => $value) {
                $message .= "• <b>{$key}:</b> {$value}\n";
            }
            $message .= "\n";
        }
        
        $message .= "━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "<a href=\"{$site_url}\">{$site_url}</a>";
        
        return $message;
    }

    // Event Handlers

    /**
     * On backup started
     *
     * @param string $name Backup name
     * @param string $type Backup type
     */
    public function on_backup_started($name, $type = 'manual') {
        if (empty($this->settings['notify_backup_start'])) {
            return;
        }
        
        $message = $this->format_message(
            '🔄 Backup Started',
            'In Progress',
            [
                'Name' => $name,
                'Type' => ucfirst($type),
            ]
        );
        
        $this->send_message($message);
    }

    /**
     * On backup success
     *
     * @param int $backup_id Backup ID
     * @param string $file_path Backup file path
     */
    public function on_backup_success($backup_id, $file_path) {
        if (empty($this->settings['notify_backup_success'])) {
            return;
        }
        
        $size = file_exists($file_path) ? size_format(filesize($file_path)) : 'Unknown';
        $name = basename($file_path);
        
        $message = $this->format_message(
            '✅ Backup Completed',
            'Success',
            [
                'File' => $name,
                'Size' => $size,
            ]
        );
        
        $this->send_message($message);
    }

    /**
     * On backup failed
     *
     * @param string $name Backup name
     * @param string $error Error message
     */
    public function on_backup_failed($name, $error) {
        if (empty($this->settings['notify_backup_failed'])) {
            return;
        }
        
        $message = $this->format_message(
            '❌ Backup Failed',
            'Error',
            [
                'Name' => $name,
                'Error' => $error,
            ]
        );
        
        $this->send_message($message);
    }

    /**
     * On restore started
     *
     * @param string $backup_name Backup name being restored
     */
    public function on_restore_started($backup_name) {
        if (empty($this->settings['notify_restore_start'])) {
            return;
        }
        
        $message = $this->format_message(
            '🔄 Restore Started',
            'In Progress',
            [
                'Backup' => $backup_name,
            ]
        );
        
        $this->send_message($message);
    }

    /**
     * On restore success
     *
     * @param string $backup_name Backup name restored
     */
    public function on_restore_success($backup_name) {
        if (empty($this->settings['notify_restore_success'])) {
            return;
        }
        
        $message = $this->format_message(
            '✅ Restore Completed',
            'Success',
            [
                'Backup' => $backup_name,
            ]
        );
        
        $this->send_message($message);
    }

    /**
     * On restore failed
     *
     * @param string $backup_name Backup name
     * @param string $error Error message
     */
    public function on_restore_failed($backup_name, $error) {
        if (empty($this->settings['notify_restore_failed'])) {
            return;
        }
        
        $message = $this->format_message(
            '❌ Restore Failed',
            'Error',
            [
                'Backup' => $backup_name,
                'Error' => $error,
            ]
        );
        
        $this->send_message($message);
    }

    /**
     * On cloud sync
     *
     * @param string $provider Provider name
     * @param string $file_name File name
     * @param string $file_id Remote file ID
     */
    public function on_cloud_sync($provider, $file_name, $file_id) {
        if (empty($this->settings['notify_cloud_sync'])) {
            return;
        }
        
        $message = $this->format_message(
            '☁️ Cloud Sync',
            'Synced',
            [
                'Provider' => ucfirst($provider),
                'File' => $file_name,
            ]
        );
        
        $this->send_message($message);
    }

    /**
     * On low disk space
     *
     * @param int $available Available bytes
     */
    public function on_low_disk($available) {
        if (empty($this->settings['notify_low_disk'])) {
            return;
        }
        
        $message = $this->format_message(
            '⚠️ Low Disk Space',
            'Warning',
            [
                'Available' => size_format($available),
            ]
        );
        
        $this->send_message($message);
    }

    /**
     * Log message
     *
     * @param string $type Log type
     * @param string $message Log message
     */
    private function log($type, $message) {
        if (class_exists('Demo_Builder_Core')) {
            Demo_Builder_Core::get_instance()->log('telegram', "[{$type}] {$message}");
        }
    }

    // AJAX Handlers

    /**
     * AJAX: Test message
     */
    public function ajax_test_message() {
        check_ajax_referer('demo_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'demo-builder')]);
        }
        
        // Temporarily use posted values for test
        $temp_token = isset($_POST['bot_token']) ? sanitize_text_field(wp_unslash($_POST['bot_token'])) : '';
        $temp_chat = isset($_POST['chat_id']) ? sanitize_text_field(wp_unslash($_POST['chat_id'])) : '';
        
        if (empty($temp_token) || empty($temp_chat)) {
            wp_send_json_error(['message' => __('Bot token and chat ID are required.', 'demo-builder')]);
        }
        
        $url = $this->api_url . $temp_token . '/sendMessage';
        
        $test_message = $this->format_message(
            '🧪 Test Message',
            'OK',
            ['Message' => __('If you see this, Telegram notifications are working!', 'demo-builder')]
        );
        
        $response = wp_remote_post($url, [
            'body' => [
                'chat_id' => $temp_chat,
                'text' => $test_message,
                'parse_mode' => 'HTML',
            ],
            'timeout' => 10,
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['ok']) && $body['ok']) {
            wp_send_json_success(['message' => __('Test message sent! Check your Telegram.', 'demo-builder')]);
        } else {
            wp_send_json_error(['message' => $body['description'] ?? __('Failed to send test message.', 'demo-builder')]);
        }
    }

    /**
     * AJAX: Validate token
     */
    public function ajax_validate_token() {
        check_ajax_referer('demo_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'demo-builder')]);
        }
        
        $token = isset($_POST['bot_token']) ? sanitize_text_field(wp_unslash($_POST['bot_token'])) : '';
        
        if (empty($token)) {
            wp_send_json_error(['message' => __('Bot token is required.', 'demo-builder')]);
        }
        
        $result = $this->validate_token($token);
        
        if ($result['success']) {
            wp_send_json_success([
                'message' => sprintf(__('Valid token! Bot: @%s', 'demo-builder'), $result['bot']['username']),
                'bot' => $result['bot'],
            ]);
        } else {
            wp_send_json_error(['message' => $result['error']]);
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
        
        $telegram = [
            'enabled' => isset($_POST['enabled']) && $_POST['enabled'] === 'true',
            'bot_token' => sanitize_text_field(wp_unslash($_POST['bot_token'] ?? '')),
            'chat_id' => sanitize_text_field(wp_unslash($_POST['chat_id'] ?? '')),
            'notify_backup_start' => isset($_POST['notify_backup_start']) && $_POST['notify_backup_start'] === 'true',
            'notify_backup_success' => isset($_POST['notify_backup_success']) && $_POST['notify_backup_success'] === 'true',
            'notify_backup_failed' => isset($_POST['notify_backup_failed']) && $_POST['notify_backup_failed'] === 'true',
            'notify_restore_start' => isset($_POST['notify_restore_start']) && $_POST['notify_restore_start'] === 'true',
            'notify_restore_success' => isset($_POST['notify_restore_success']) && $_POST['notify_restore_success'] === 'true',
            'notify_restore_failed' => isset($_POST['notify_restore_failed']) && $_POST['notify_restore_failed'] === 'true',
            'notify_cloud_sync' => isset($_POST['notify_cloud_sync']) && $_POST['notify_cloud_sync'] === 'true',
            'notify_low_disk' => isset($_POST['notify_low_disk']) && $_POST['notify_low_disk'] === 'true',
        ];
        
        $settings = get_option('demo_builder_settings', []);
        $settings['telegram'] = $telegram;
        update_option('demo_builder_settings', $settings);
        
        // Update instance settings
        $this->settings = $telegram;
        
        wp_send_json_success(['message' => __('Telegram settings saved!', 'demo-builder')]);
    }
}

// Initialize
Demo_Builder_Telegram::get_instance();
