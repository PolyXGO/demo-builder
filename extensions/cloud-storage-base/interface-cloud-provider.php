<?php
/**
 * Cloud Provider Interface
 *
 * Defines the contract for cloud storage providers
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface Demo_Builder_Cloud_Provider_Interface
 */
interface Demo_Builder_Cloud_Provider_Interface {

    /**
     * Get provider name
     *
     * @return string
     */
    public function get_name(): string;

    /**
     * Get provider slug
     *
     * @return string
     */
    public function get_slug(): string;

    /**
     * Get provider icon URL
     *
     * @return string
     */
    public function get_icon(): string;

    /**
     * Check if provider is connected
     *
     * @return bool
     */
    public function is_connected(): bool;

    /**
     * Get authentication URL
     *
     * @return string
     */
    public function get_auth_url(): string;

    /**
     * Handle OAuth callback
     *
     * @param string $code Authorization code
     * @return bool
     */
    public function handle_callback(string $code): bool;

    /**
     * Disconnect provider
     *
     * @return bool
     */
    public function disconnect(): bool;

    /**
     * Upload file to cloud
     *
     * @param string $file_path Local file path
     * @param string $remote_name Remote file name
     * @return array Result with success/error
     */
    public function upload(string $file_path, string $remote_name): array;

    /**
     * Download file from cloud
     *
     * @param string $remote_id Remote file ID
     * @param string $local_path Local file path
     * @return array Result with success/error
     */
    public function download(string $remote_id, string $local_path): array;

    /**
     * Delete file from cloud
     *
     * @param string $remote_id Remote file ID
     * @return bool
     */
    public function delete(string $remote_id): bool;

    /**
     * List files in cloud folder
     *
     * @param string $folder Folder path/ID
     * @return array
     */
    public function list_files(string $folder = ''): array;

    /**
     * Get storage quota info
     *
     * @return array
     */
    public function get_quota(): array;

    /**
     * Render settings form
     *
     * @return void
     */
    public function render_settings(): void;

    /**
     * Save settings
     *
     * @param array $data Settings data
     * @return bool
     */
    public function save_settings(array $data): bool;
}
