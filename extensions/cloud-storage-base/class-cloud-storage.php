<?php
/**
 * Cloud Storage Abstract Class
 *
 * Base class for cloud storage providers
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract Class Demo_Builder_Cloud_Storage
 */
abstract class Demo_Builder_Cloud_Storage implements Demo_Builder_Cloud_Provider_Interface {

    /**
     * Option key for settings
     *
     * @var string
     */
    protected $option_key = '';

    /**
     * Settings
     *
     * @var array
     */
    protected $settings = [];

    /**
     * API base URL
     *
     * @var string
     */
    protected $api_url = '';

    /**
     * Constructor
     */
    public function __construct() {
        $this->load_settings();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    protected function init_hooks() {
        // Override in child classes if needed
    }

    /**
     * Load settings from database
     */
    protected function load_settings() {
        $defaults = $this->get_default_settings();
        $saved = get_option($this->option_key, []);
        $this->settings = wp_parse_args($saved, $defaults);
    }

    /**
     * Get default settings
     *
     * @return array
     */
    protected function get_default_settings(): array {
        return [
            'enabled' => false,
            'client_id' => '',
            'client_secret' => '',
            'access_token' => '',
            'refresh_token' => '',
            'token_expires' => 0,
            'folder_id' => '',
            'folder_name' => '',
            'auto_sync' => false,
            'max_backups' => 5,
        ];
    }

    /**
     * Save settings to database
     *
     * @param array $data Settings data
     * @return bool
     */
    public function save_settings(array $data): bool {
        $this->settings = array_merge($this->settings, $data);
        return update_option($this->option_key, $this->settings);
    }

    /**
     * Get setting value
     *
     * @param string $key Setting key
     * @param mixed $default Default value
     * @return mixed
     */
    protected function get_setting(string $key, $default = null) {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Check if connected
     *
     * @return bool
     */
    public function is_connected(): bool {
        return !empty($this->settings['access_token']);
    }

    /**
     * Disconnect provider
     *
     * @return bool
     */
    public function disconnect(): bool {
        $this->settings['access_token'] = '';
        $this->settings['refresh_token'] = '';
        $this->settings['token_expires'] = 0;
        return $this->save_settings($this->settings);
    }

    /**
     * Check if token needs refresh
     *
     * @return bool
     */
    protected function needs_token_refresh(): bool {
        $expires = $this->settings['token_expires'] ?? 0;
        // Refresh if expires in less than 5 minutes
        return time() > ($expires - 300);
    }

    /**
     * Refresh access token
     *
     * @return bool
     */
    abstract protected function refresh_token(): bool;

    /**
     * Make API request
     *
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param array $data Request data
     * @param array $headers Additional headers
     * @return array Response
     */
    protected function api_request(string $endpoint, string $method = 'GET', array $data = [], array $headers = []): array {
        // Refresh token if needed
        if ($this->is_connected() && $this->needs_token_refresh()) {
            $this->refresh_token();
        }

        $url = $this->api_url . $endpoint;
        
        $args = [
            'method' => $method,
            'timeout' => 60,
            'headers' => array_merge([
                'Authorization' => 'Bearer ' . $this->settings['access_token'],
                'Content-Type' => 'application/json',
            ], $headers),
        ];

        if (!empty($data)) {
            if ($method === 'GET') {
                $url = add_query_arg($data, $url);
            } else {
                $args['body'] = wp_json_encode($data);
            }
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if ($code >= 200 && $code < 300) {
            return [
                'success' => true,
                'data' => $decoded,
            ];
        }

        return [
            'success' => false,
            'error' => $decoded['error']['message'] ?? __('Unknown error', 'demo-builder'),
            'code' => $code,
        ];
    }

    /**
     * Upload file with multipart
     *
     * @param string $file_path Local file path
     * @param string $endpoint Upload endpoint
     * @return array Response
     */
    protected function upload_file(string $file_path, string $endpoint): array {
        if (!file_exists($file_path)) {
            return [
                'success' => false,
                'error' => __('File not found', 'demo-builder'),
            ];
        }

        // This is a simplified upload - providers may need specific implementation
        $file_content = file_get_contents($file_path);
        $file_name = basename($file_path);

        $args = [
            'method' => 'POST',
            'timeout' => 300,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->settings['access_token'],
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => $file_content,
        ];

        $response = wp_remote_request($endpoint, $args);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if ($code >= 200 && $code < 300) {
            return [
                'success' => true,
                'data' => $decoded,
            ];
        }

        return [
            'success' => false,
            'error' => $decoded['error']['message'] ?? __('Upload failed', 'demo-builder'),
        ];
    }

    /**
     * Get redirect URI
     *
     * @return string
     */
    protected function get_redirect_uri(): string {
        return admin_url('admin-ajax.php?action=demo_builder_cloud_callback&provider=' . $this->get_slug());
    }

    /**
     * Log cloud action
     *
     * @param string $action Action type
     * @param string $message Log message
     */
    protected function log(string $action, string $message) {
        if (class_exists('Demo_Builder_Core')) {
            Demo_Builder_Core::get_instance()->log(
                'cloud_' . $this->get_slug(),
                $action . ': ' . $message
            );
        }
    }
}
