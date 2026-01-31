<?php
/**
 * OneDrive Provider Class
 *
 * Implements Microsoft OneDrive cloud storage
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Demo_Builder_OneDrive
 */
class Demo_Builder_OneDrive extends Demo_Builder_Cloud_Storage {

    /**
     * Instance
     *
     * @var Demo_Builder_OneDrive
     */
    private static $instance = null;

    /**
     * Option key
     *
     * @var string
     */
    protected $option_key = 'demo_builder_onedrive';

    /**
     * API base URL
     *
     * @var string
     */
    protected $api_url = 'https://graph.microsoft.com/v1.0';

    /**
     * OAuth URLs
     *
     * @var array
     */
    protected $oauth = [
        'auth_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
        'token_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
    ];

    /**
     * Get instance
     *
     * @return Demo_Builder_OneDrive
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
    public function __construct() {
        parent::__construct();
    }

    /**
     * Get default settings
     *
     * @return array
     */
    protected function get_default_settings(): array {
        return [
            'enabled' => false,
            'app_id' => '',
            'app_secret' => '',
            'access_token' => '',
            'refresh_token' => '',
            'token_expires' => 0,
            'folder_path' => '/DemoBuilder',
            'auto_sync' => false,
            'max_backups' => 5,
        ];
    }

    /**
     * Initialize hooks
     */
    protected function init_hooks() {
        // AJAX handlers
        add_action('wp_ajax_demo_builder_onedrive_auth_url', [$this, 'ajax_get_auth_url']);
        add_action('wp_ajax_demo_builder_onedrive_disconnect', [$this, 'ajax_disconnect']);
        add_action('wp_ajax_demo_builder_onedrive_upload', [$this, 'ajax_upload']);
        add_action('wp_ajax_demo_builder_onedrive_list', [$this, 'ajax_list_files']);
        add_action('wp_ajax_demo_builder_onedrive_delete', [$this, 'ajax_delete']);
        add_action('wp_ajax_demo_builder_onedrive_quota', [$this, 'ajax_get_quota']);
        add_action('wp_ajax_demo_builder_onedrive_settings', [$this, 'ajax_save_settings']);
        
        // OAuth callback
        add_action('wp_ajax_demo_builder_cloud_callback', [$this, 'handle_oauth_callback'], 20);
        
        // Auto-sync on backup creation
        add_action('demo_builder_backup_created', [$this, 'maybe_auto_sync'], 20, 2);
    }

    /**
     * Get provider name
     *
     * @return string
     */
    public function get_name(): string {
        return __('OneDrive', 'demo-builder');
    }

    /**
     * Get provider slug
     *
     * @return string
     */
    public function get_slug(): string {
        return 'onedrive';
    }

    /**
     * Get provider icon
     *
     * @return string
     */
    public function get_icon(): string {
        return DEMO_BUILDER_PLUGIN_URL . 'extensions/onedrive/assets/icon.svg';
    }

    /**
     * Get authentication URL
     *
     * @return string
     */
    public function get_auth_url(): string {
        $params = [
            'client_id' => $this->settings['app_id'],
            'redirect_uri' => $this->get_redirect_uri(),
            'response_type' => 'code',
            'scope' => 'Files.ReadWrite.All offline_access',
            'response_mode' => 'query',
            'state' => wp_create_nonce('demo_builder_onedrive_oauth'),
        ];

        return $this->oauth['auth_url'] . '?' . http_build_query($params);
    }

    /**
     * Handle OAuth callback
     *
     * @param string $code Authorization code
     * @return bool
     */
    public function handle_callback(string $code): bool {
        $response = wp_remote_post($this->oauth['token_url'], [
            'body' => [
                'code' => $code,
                'client_id' => $this->settings['app_id'],
                'client_secret' => $this->settings['app_secret'],
                'redirect_uri' => $this->get_redirect_uri(),
                'grant_type' => 'authorization_code',
            ],
        ]);

        if (is_wp_error($response)) {
            $this->log('oauth', 'Token exchange failed: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['access_token'])) {
            $this->settings['access_token'] = $body['access_token'];
            $this->settings['refresh_token'] = $body['refresh_token'] ?? $this->settings['refresh_token'];
            $this->settings['token_expires'] = time() + ($body['expires_in'] ?? 3600);
            $this->save_settings($this->settings);
            
            // Create folder if not exists
            $this->ensure_folder_exists();
            
            $this->log('oauth', 'Connected successfully');
            return true;
        }

        $this->log('oauth', 'Token exchange failed: ' . ($body['error_description'] ?? 'Unknown error'));
        return false;
    }

    /**
     * Refresh access token
     *
     * @return bool
     */
    protected function refresh_token(): bool {
        if (empty($this->settings['refresh_token'])) {
            return false;
        }

        $response = wp_remote_post($this->oauth['token_url'], [
            'body' => [
                'client_id' => $this->settings['app_id'],
                'client_secret' => $this->settings['app_secret'],
                'refresh_token' => $this->settings['refresh_token'],
                'grant_type' => 'refresh_token',
            ],
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['access_token'])) {
            $this->settings['access_token'] = $body['access_token'];
            $this->settings['refresh_token'] = $body['refresh_token'] ?? $this->settings['refresh_token'];
            $this->settings['token_expires'] = time() + ($body['expires_in'] ?? 3600);
            $this->save_settings($this->settings);
            return true;
        }

        return false;
    }

    /**
     * Ensure folder exists
     */
    protected function ensure_folder_exists() {
        $folder_path = ltrim($this->settings['folder_path'] ?? '/DemoBuilder', '/');
        
        $result = $this->api_request('/me/drive/root:/' . $folder_path);
        
        if (!$result['success']) {
            // Create folder
            $this->api_request('/me/drive/root/children', 'POST', [
                'name' => $folder_path,
                'folder' => new stdClass(),
            ]);
        }
    }

    /**
     * Upload file to OneDrive
     *
     * @param string $file_path Local file path
     * @param string $remote_name Remote file name
     * @return array
     */
    public function upload(string $file_path, string $remote_name): array {
        if (!file_exists($file_path)) {
            return ['success' => false, 'error' => __('File not found', 'demo-builder')];
        }

        $file_size = filesize($file_path);
        $folder_path = ltrim($this->settings['folder_path'] ?? '/DemoBuilder', '/');
        
        // For files < 4MB, use simple upload
        if ($file_size < 4 * 1024 * 1024) {
            return $this->simple_upload($file_path, $folder_path, $remote_name);
        }

        // For larger files, use upload session
        return $this->chunked_upload($file_path, $folder_path, $remote_name);
    }

    /**
     * Simple upload for small files
     *
     * @param string $file_path Local path
     * @param string $folder Folder path
     * @param string $name File name
     * @return array
     */
    protected function simple_upload(string $file_path, string $folder, string $name): array {
        $url = $this->api_url . '/me/drive/root:/' . $folder . '/' . $name . ':/content';
        
        $response = wp_remote_request($url, [
            'method' => 'PUT',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->settings['access_token'],
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => file_get_contents($file_path),
            'timeout' => 300,
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (($code === 200 || $code === 201) && isset($body['id'])) {
            $this->log('upload', 'Uploaded: ' . $name);
            return [
                'success' => true,
                'file_id' => $body['id'],
                'name' => $body['name'],
            ];
        }

        return [
            'success' => false,
            'error' => $body['error']['message'] ?? __('Upload failed', 'demo-builder'),
        ];
    }

    /**
     * Chunked upload for large files
     *
     * @param string $file_path Local path
     * @param string $folder Folder path
     * @param string $name File name
     * @return array
     */
    protected function chunked_upload(string $file_path, string $folder, string $name): array {
        // Create upload session
        $session_url = $this->api_url . '/me/drive/root:/' . $folder . '/' . $name . ':/createUploadSession';
        
        $session_response = wp_remote_post($session_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->settings['access_token'],
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode([
                'item' => ['@microsoft.graph.conflictBehavior' => 'replace'],
            ]),
        ]);

        if (is_wp_error($session_response)) {
            return ['success' => false, 'error' => $session_response->get_error_message()];
        }

        $session = json_decode(wp_remote_retrieve_body($session_response), true);
        
        if (!isset($session['uploadUrl'])) {
            return ['success' => false, 'error' => __('Failed to create upload session', 'demo-builder')];
        }

        // Upload in chunks
        $upload_url = $session['uploadUrl'];
        $file_size = filesize($file_path);
        $chunk_size = 5 * 1024 * 1024; // 5MB chunks
        $handle = fopen($file_path, 'rb');
        $offset = 0;
        $result = null;

        while ($offset < $file_size) {
            $chunk = fread($handle, $chunk_size);
            $chunk_length = strlen($chunk);
            $end = $offset + $chunk_length - 1;

            $response = wp_remote_request($upload_url, [
                'method' => 'PUT',
                'headers' => [
                    'Content-Length' => $chunk_length,
                    'Content-Range' => "bytes {$offset}-{$end}/{$file_size}",
                ],
                'body' => $chunk,
                'timeout' => 300,
            ]);

            if (is_wp_error($response)) {
                fclose($handle);
                return ['success' => false, 'error' => $response->get_error_message()];
            }

            $code = wp_remote_retrieve_response_code($response);
            $body = json_decode(wp_remote_retrieve_body($response), true);

            if ($code === 200 || $code === 201) {
                $result = $body;
                break;
            }

            $offset += $chunk_length;
        }

        fclose($handle);

        if ($result && isset($result['id'])) {
            $this->log('upload', 'Uploaded (chunked): ' . $name);
            return [
                'success' => true,
                'file_id' => $result['id'],
                'name' => $result['name'],
            ];
        }

        return ['success' => false, 'error' => __('Chunked upload failed', 'demo-builder')];
    }

    /**
     * Download file from OneDrive
     *
     * @param string $remote_id Remote file ID
     * @param string $local_path Local file path
     * @return array
     */
    public function download(string $remote_id, string $local_path): array {
        $result = $this->api_request('/me/drive/items/' . $remote_id);
        
        if (!$result['success']) {
            return $result;
        }

        $download_url = $result['data']['@microsoft.graph.downloadUrl'] ?? null;
        
        if (!$download_url) {
            return ['success' => false, 'error' => __('Download URL not available', 'demo-builder')];
        }

        $response = wp_remote_get($download_url, [
            'timeout' => 300,
            'stream' => true,
            'filename' => $local_path,
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }

        $this->log('download', 'Downloaded: ' . $remote_id);
        return ['success' => true, 'path' => $local_path];
    }

    /**
     * Delete file from OneDrive
     *
     * @param string $remote_id Remote file ID
     * @return bool
     */
    public function delete(string $remote_id): bool {
        $result = $this->api_request('/me/drive/items/' . $remote_id, 'DELETE');
        
        if ($result['success']) {
            $this->log('delete', 'Deleted: ' . $remote_id);
        }
        
        return $result['success'];
    }

    /**
     * List files in OneDrive folder
     *
     * @param string $folder Folder path
     * @return array
     */
    public function list_files(string $folder = ''): array {
        $folder_path = $folder ?: ltrim($this->settings['folder_path'] ?? '/DemoBuilder', '/');
        
        $result = $this->api_request('/me/drive/root:/' . $folder_path . ':/children', 'GET', [
            '$filter' => "file ne null",
            '$orderby' => 'createdDateTime desc',
            '$top' => 50,
            '$select' => 'id,name,size,createdDateTime,lastModifiedDateTime',
        ]);

        if ($result['success'] && isset($result['data']['value'])) {
            return $result['data']['value'];
        }

        return [];
    }

    /**
     * Get storage quota
     *
     * @return array
     */
    public function get_quota(): array {
        $result = $this->api_request('/me/drive');

        if ($result['success'] && isset($result['data']['quota'])) {
            $quota = $result['data']['quota'];
            return [
                'used' => (int) ($quota['used'] ?? 0),
                'limit' => (int) ($quota['total'] ?? 0),
                'remaining' => (int) ($quota['remaining'] ?? 0),
            ];
        }

        return [];
    }

    /**
     * Render settings form
     */
    public function render_settings(): void {
        include __DIR__ . '/views/settings.php';
    }

    /**
     * Handle OAuth callback (admin-ajax)
     */
    public function handle_oauth_callback() {
        $provider = isset($_GET['provider']) ? sanitize_text_field($_GET['provider']) : '';
        
        if ($provider !== 'onedrive') {
            return;
        }

        // Verify state
        $state = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : '';
        if (!wp_verify_nonce($state, 'demo_builder_onedrive_oauth')) {
            wp_die(__('Invalid state parameter.', 'demo-builder'));
        }

        $code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : '';
        
        if (empty($code)) {
            $error = isset($_GET['error_description']) ? sanitize_text_field($_GET['error_description']) : 'Unknown error';
            wp_redirect(admin_url('admin.php?page=demo-builder-settings&tab=cloud&error=' . urlencode($error)));
            exit;
        }

        if ($this->handle_callback($code)) {
            wp_redirect(admin_url('admin.php?page=demo-builder-settings&tab=cloud&connected=onedrive'));
        } else {
            wp_redirect(admin_url('admin.php?page=demo-builder-settings&tab=cloud&error=token_exchange_failed'));
        }
        exit;
    }

    /**
     * Maybe auto-sync backup
     *
     * @param int $backup_id Backup ID
     * @param string $backup_path Backup file path
     */
    public function maybe_auto_sync($backup_id, $backup_path) {
        if (!$this->is_connected() || empty($this->settings['auto_sync'])) {
            return;
        }

        $result = $this->upload($backup_path, basename($backup_path));
        
        if ($result['success']) {
            global $wpdb;
            $table = $wpdb->prefix . 'demobuilder_backups';
            $wpdb->update(
                $table,
                ['cloud_id' => $result['file_id'], 'cloud_provider' => 'onedrive'],
                ['id' => $backup_id]
            );

            $this->cleanup_old_backups();
        }
    }

    /**
     * Clean up old backups
     */
    protected function cleanup_old_backups() {
        $max = $this->settings['max_backups'] ?? 5;
        $files = $this->list_files();
        
        if (count($files) > $max) {
            usort($files, function($a, $b) {
                return strtotime($a['createdDateTime']) - strtotime($b['createdDateTime']);
            });

            $to_delete = array_slice($files, 0, count($files) - $max);
            foreach ($to_delete as $file) {
                $this->delete($file['id']);
            }
        }
    }

    // AJAX Handlers

    /**
     * AJAX: Get auth URL
     */
    public function ajax_get_auth_url() {
        check_ajax_referer('demo_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'demo-builder')]);
        }

        wp_send_json_success(['url' => $this->get_auth_url()]);
    }

    /**
     * AJAX: Disconnect
     */
    public function ajax_disconnect() {
        check_ajax_referer('demo_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'demo-builder')]);
        }

        $this->disconnect();
        wp_send_json_success(['message' => __('Disconnected from OneDrive.', 'demo-builder')]);
    }

    /**
     * AJAX: Upload backup
     */
    public function ajax_upload() {
        check_ajax_referer('demo_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'demo-builder')]);
        }

        $backup_id = isset($_POST['backup_id']) ? intval($_POST['backup_id']) : 0;
        
        global $wpdb;
        $table = $wpdb->prefix . 'demobuilder_backups';
        $backup = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $backup_id));
        
        if (!$backup) {
            wp_send_json_error(['message' => __('Backup not found.', 'demo-builder')]);
        }

        $result = $this->upload($backup->file_path, $backup->filename);
        
        if ($result['success']) {
            $wpdb->update(
                $table,
                ['cloud_id' => $result['file_id'], 'cloud_provider' => 'onedrive'],
                ['id' => $backup_id]
            );
            wp_send_json_success(['message' => __('Uploaded to OneDrive!', 'demo-builder')]);
        } else {
            wp_send_json_error(['message' => $result['error']]);
        }
    }

    /**
     * AJAX: List files
     */
    public function ajax_list_files() {
        check_ajax_referer('demo_builder_nonce', 'nonce');
        
        $files = $this->list_files();
        wp_send_json_success(['files' => $files]);
    }

    /**
     * AJAX: Delete file
     */
    public function ajax_delete() {
        check_ajax_referer('demo_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'demo-builder')]);
        }

        $file_id = isset($_POST['file_id']) ? sanitize_text_field($_POST['file_id']) : '';
        
        if ($this->delete($file_id)) {
            wp_send_json_success(['message' => __('File deleted from OneDrive.', 'demo-builder')]);
        } else {
            wp_send_json_error(['message' => __('Failed to delete file.', 'demo-builder')]);
        }
    }

    /**
     * AJAX: Get quota
     */
    public function ajax_get_quota() {
        check_ajax_referer('demo_builder_nonce', 'nonce');
        
        $quota = $this->get_quota();
        wp_send_json_success(['quota' => $quota]);
    }

    /**
     * AJAX: Save settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('demo_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'demo-builder')]);
        }

        $data = [
            'app_id' => sanitize_text_field($_POST['app_id'] ?? ''),
            'app_secret' => sanitize_text_field($_POST['app_secret'] ?? ''),
            'folder_path' => sanitize_text_field($_POST['folder_path'] ?? '/DemoBuilder'),
            'auto_sync' => isset($_POST['auto_sync']) && $_POST['auto_sync'] === 'true',
            'max_backups' => intval($_POST['max_backups'] ?? 5),
        ];

        $this->save_settings($data);
        wp_send_json_success(['message' => __('Settings saved!', 'demo-builder')]);
    }
}
