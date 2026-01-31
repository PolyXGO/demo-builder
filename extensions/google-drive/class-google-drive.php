<?php
/**
 * Google Drive Provider Class
 *
 * Implements Google Drive cloud storage
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Demo_Builder_Google_Drive
 */
class Demo_Builder_Google_Drive extends Demo_Builder_Cloud_Storage {

    /**
     * Instance
     *
     * @var Demo_Builder_Google_Drive
     */
    private static $instance = null;

    /**
     * Option key
     *
     * @var string
     */
    protected $option_key = 'demo_builder_google_drive';

    /**
     * API base URL
     *
     * @var string
     */
    protected $api_url = 'https://www.googleapis.com/drive/v3';

    /**
     * Upload API URL
     *
     * @var string
     */
    protected $upload_url = 'https://www.googleapis.com/upload/drive/v3';

    /**
     * OAuth URLs
     *
     * @var array
     */
    protected $oauth = [
        'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_url' => 'https://oauth2.googleapis.com/token',
    ];

    /**
     * Get instance
     *
     * @return Demo_Builder_Google_Drive
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
     * Initialize hooks
     */
    protected function init_hooks() {
        // AJAX handlers
        add_action('wp_ajax_demo_builder_gdrive_auth_url', [$this, 'ajax_get_auth_url']);
        add_action('wp_ajax_demo_builder_gdrive_disconnect', [$this, 'ajax_disconnect']);
        add_action('wp_ajax_demo_builder_gdrive_upload', [$this, 'ajax_upload']);
        add_action('wp_ajax_demo_builder_gdrive_list', [$this, 'ajax_list_files']);
        add_action('wp_ajax_demo_builder_gdrive_delete', [$this, 'ajax_delete']);
        add_action('wp_ajax_demo_builder_gdrive_quota', [$this, 'ajax_get_quota']);
        add_action('wp_ajax_demo_builder_gdrive_settings', [$this, 'ajax_save_settings']);
        
        // OAuth callback
        add_action('wp_ajax_demo_builder_cloud_callback', [$this, 'handle_oauth_callback']);
        
        // Auto-sync on backup creation
        add_action('demo_builder_backup_created', [$this, 'maybe_auto_sync'], 10, 2);
    }

    /**
     * Get provider name
     *
     * @return string
     */
    public function get_name(): string {
        return __('Google Drive', 'demo-builder');
    }

    /**
     * Get provider slug
     *
     * @return string
     */
    public function get_slug(): string {
        return 'google-drive';
    }

    /**
     * Get provider icon
     *
     * @return string
     */
    public function get_icon(): string {
        return DEMO_BUILDER_PLUGIN_URL . 'extensions/google-drive/assets/icon.svg';
    }

    /**
     * Get authentication URL
     *
     * @return string
     */
    public function get_auth_url(): string {
        $params = [
            'client_id' => $this->settings['client_id'],
            'redirect_uri' => $this->get_redirect_uri(),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/drive.file',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => wp_create_nonce('demo_builder_gdrive_oauth'),
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
                'client_id' => $this->settings['client_id'],
                'client_secret' => $this->settings['client_secret'],
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
                'client_id' => $this->settings['client_id'],
                'client_secret' => $this->settings['client_secret'],
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
            $this->settings['token_expires'] = time() + ($body['expires_in'] ?? 3600);
            $this->save_settings($this->settings);
            return true;
        }

        return false;
    }

    /**
     * Upload file to Google Drive
     *
     * @param string $file_path Local file path
     * @param string $remote_name Remote file name
     * @return array
     */
    public function upload(string $file_path, string $remote_name): array {
        if (!file_exists($file_path)) {
            return ['success' => false, 'error' => __('File not found', 'demo-builder')];
        }

        // Create file metadata
        $metadata = [
            'name' => $remote_name,
            'mimeType' => 'application/zip',
        ];

        if (!empty($this->settings['folder_id'])) {
            $metadata['parents'] = [$this->settings['folder_id']];
        }

        // Multipart upload
        $boundary = wp_generate_password(24, false);
        $delimiter = "--{$boundary}";
        $close_delimiter = "{$delimiter}--";

        $file_content = file_get_contents($file_path);

        $body = "{$delimiter}\r\n";
        $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $body .= wp_json_encode($metadata) . "\r\n";
        $body .= "{$delimiter}\r\n";
        $body .= "Content-Type: application/zip\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= base64_encode($file_content) . "\r\n";
        $body .= $close_delimiter;

        $response = wp_remote_post($this->upload_url . '/files?uploadType=multipart', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->settings['access_token'],
                'Content-Type' => 'multipart/related; boundary=' . $boundary,
            ],
            'body' => $body,
            'timeout' => 300,
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code >= 200 && $code < 300 && isset($body['id'])) {
            $this->log('upload', 'Uploaded: ' . $remote_name);
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
     * Download file from Google Drive
     *
     * @param string $remote_id Remote file ID
     * @param string $local_path Local file path
     * @return array
     */
    public function download(string $remote_id, string $local_path): array {
        $response = wp_remote_get($this->api_url . '/files/' . $remote_id . '?alt=media', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->settings['access_token'],
            ],
            'timeout' => 300,
            'stream' => true,
            'filename' => $local_path,
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);

        if ($code >= 200 && $code < 300) {
            $this->log('download', 'Downloaded: ' . $remote_id);
            return ['success' => true, 'path' => $local_path];
        }

        return ['success' => false, 'error' => __('Download failed', 'demo-builder')];
    }

    /**
     * Delete file from Google Drive
     *
     * @param string $remote_id Remote file ID
     * @return bool
     */
    public function delete(string $remote_id): bool {
        $result = $this->api_request('/files/' . $remote_id, 'DELETE');
        
        if ($result['success']) {
            $this->log('delete', 'Deleted: ' . $remote_id);
        }
        
        return $result['success'];
    }

    /**
     * List files in Google Drive folder
     *
     * @param string $folder Folder ID
     * @return array
     */
    public function list_files(string $folder = ''): array {
        $folder_id = $folder ?: $this->settings['folder_id'];
        
        $query = "mimeType='application/zip' and trashed=false";
        if ($folder_id) {
            $query .= " and '{$folder_id}' in parents";
        }

        $result = $this->api_request('/files', 'GET', [
            'q' => $query,
            'fields' => 'files(id,name,size,createdTime,modifiedTime)',
            'orderBy' => 'createdTime desc',
            'pageSize' => 50,
        ]);

        if ($result['success']) {
            return $result['data']['files'] ?? [];
        }

        return [];
    }

    /**
     * Get storage quota
     *
     * @return array
     */
    public function get_quota(): array {
        $result = $this->api_request('/about', 'GET', [
            'fields' => 'storageQuota',
        ]);

        if ($result['success'] && isset($result['data']['storageQuota'])) {
            $quota = $result['data']['storageQuota'];
            return [
                'used' => (int) ($quota['usage'] ?? 0),
                'limit' => (int) ($quota['limit'] ?? 0),
                'usageInDrive' => (int) ($quota['usageInDrive'] ?? 0),
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
        
        if ($provider !== 'google-drive') {
            return;
        }

        // Verify state
        $state = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : '';
        if (!wp_verify_nonce($state, 'demo_builder_gdrive_oauth')) {
            wp_die(__('Invalid state parameter.', 'demo-builder'));
        }

        $code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : '';
        
        if (empty($code)) {
            $error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : 'Unknown error';
            wp_redirect(admin_url('admin.php?page=demo-builder-settings&tab=cloud&error=' . urlencode($error)));
            exit;
        }

        if ($this->handle_callback($code)) {
            wp_redirect(admin_url('admin.php?page=demo-builder-settings&tab=cloud&connected=google-drive'));
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
            // Update backup metadata with cloud info
            global $wpdb;
            $table = $wpdb->prefix . 'demobuilder_backups';
            $wpdb->update(
                $table,
                ['cloud_id' => $result['file_id'], 'cloud_provider' => 'google-drive'],
                ['id' => $backup_id]
            );

            // Clean old backups if needed
            $this->cleanup_old_backups();
        }
    }

    /**
     * Clean up old backups based on retention policy
     */
    protected function cleanup_old_backups() {
        $max = $this->settings['max_backups'] ?? 5;
        $files = $this->list_files();
        
        if (count($files) > $max) {
            // Sort by created time (oldest first)
            usort($files, function($a, $b) {
                return strtotime($a['createdTime']) - strtotime($b['createdTime']);
            });

            // Delete oldest files
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
        wp_send_json_success(['message' => __('Disconnected from Google Drive.', 'demo-builder')]);
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
                ['cloud_id' => $result['file_id'], 'cloud_provider' => 'google-drive'],
                ['id' => $backup_id]
            );
            wp_send_json_success(['message' => __('Uploaded to Google Drive!', 'demo-builder')]);
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
            wp_send_json_success(['message' => __('File deleted from Google Drive.', 'demo-builder')]);
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
            'client_id' => sanitize_text_field($_POST['client_id'] ?? ''),
            'client_secret' => sanitize_text_field($_POST['client_secret'] ?? ''),
            'folder_id' => sanitize_text_field($_POST['folder_id'] ?? ''),
            'auto_sync' => isset($_POST['auto_sync']) && $_POST['auto_sync'] === 'true',
            'max_backups' => intval($_POST['max_backups'] ?? 5),
        ];

        $this->save_settings($data);
        wp_send_json_success(['message' => __('Settings saved!', 'demo-builder')]);
    }
}
