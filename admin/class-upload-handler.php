<?php
/**
 * Upload Handler Class
 *
 * Handles chunked file uploads for large backup files
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Demo_Builder_Upload_Handler
 */
class Demo_Builder_Upload_Handler {

    /**
     * Instance
     *
     * @var Demo_Builder_Upload_Handler
     */
    private static $instance = null;

    /**
     * Temp directory base
     *
     * @var string
     */
    private $temp_dir;

    /**
     * Get instance
     *
     * @return Demo_Builder_Upload_Handler
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
        $upload_dir = wp_upload_dir();
        $this->temp_dir = $upload_dir['basedir'] . '/demo-builder-temp';
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_demo_builder_upload_chunk', [$this, 'handle_chunk_upload']);
        add_action('wp_ajax_demo_builder_finalize_upload', [$this, 'finalize_upload']);
        add_action('wp_ajax_demo_builder_get_upload_limits', [$this, 'get_upload_limits']);
        add_action('wp_ajax_demo_builder_cancel_upload', [$this, 'cancel_upload']);
    }

    /**
     * Handle single chunk upload
     */
    public function handle_chunk_upload() {
        check_ajax_referer('demo_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'demo-builder')]);
        }

        // Validate required parameters
        $upload_id = isset($_POST['uploadId']) ? sanitize_text_field(wp_unslash($_POST['uploadId'])) : '';
        $chunk_index = isset($_POST['chunkIndex']) ? intval($_POST['chunkIndex']) : -1;
        $total_chunks = isset($_POST['totalChunks']) ? intval($_POST['totalChunks']) : 0;
        $filename = isset($_POST['filename']) ? sanitize_file_name(wp_unslash($_POST['filename'])) : '';

        if (empty($upload_id) || $chunk_index < 0 || $total_chunks <= 0 || empty($filename)) {
            wp_send_json_error(['message' => __('Invalid upload parameters.', 'demo-builder')]);
        }

        // Verify file upload
        if (!isset($_FILES['chunk']) || $_FILES['chunk']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => __('Chunk upload failed.', 'demo-builder')]);
        }

        // Create temp directory for this upload
        $upload_temp_dir = $this->temp_dir . '/' . $upload_id;
        if (!file_exists($upload_temp_dir)) {
            wp_mkdir_p($upload_temp_dir);
        }

        // Save chunk
        $chunk_file = $upload_temp_dir . '/chunk_' . str_pad($chunk_index, 5, '0', STR_PAD_LEFT);
        if (!move_uploaded_file($_FILES['chunk']['tmp_name'], $chunk_file)) {
            wp_send_json_error(['message' => __('Failed to save chunk.', 'demo-builder')]);
        }

        // Save upload metadata
        $meta_file = $upload_temp_dir . '/meta.json';
        $meta = [
            'filename' => $filename,
            'total_chunks' => $total_chunks,
            'chunks_received' => $this->count_chunks($upload_temp_dir),
            'upload_id' => $upload_id,
            'started_at' => file_exists($meta_file) 
                ? json_decode(file_get_contents($meta_file), true)['started_at'] 
                : time(),
        ];
        file_put_contents($meta_file, wp_json_encode($meta));

        // Log progress
        $this->log('chunk', "Chunk {$chunk_index}/{$total_chunks} received for {$filename}");

        wp_send_json_success([
            'chunk' => $chunk_index,
            'received' => $meta['chunks_received'],
            'total' => $total_chunks,
        ]);
    }

    /**
     * Finalize chunked upload
     */
    public function finalize_upload() {
        check_ajax_referer('demo_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'demo-builder')]);
        }

        $upload_id = isset($_POST['uploadId']) ? sanitize_text_field(wp_unslash($_POST['uploadId'])) : '';
        
        if (empty($upload_id)) {
            wp_send_json_error(['message' => __('Invalid upload ID.', 'demo-builder')]);
        }

        $upload_temp_dir = $this->temp_dir . '/' . $upload_id;
        $meta_file = $upload_temp_dir . '/meta.json';
        
        if (!file_exists($meta_file)) {
            wp_send_json_error(['message' => __('Upload not found.', 'demo-builder')]);
        }

        $meta = json_decode(file_get_contents($meta_file), true);
        $filename = $meta['filename'];
        $total_chunks = $meta['total_chunks'];
        $chunks_received = $this->count_chunks($upload_temp_dir);

        // Verify all chunks received
        if ($chunks_received < $total_chunks) {
            wp_send_json_error([
                'message' => sprintf(
                    __('Missing chunks: %d of %d received.', 'demo-builder'),
                    $chunks_received,
                    $total_chunks
                ),
            ]);
        }

        // Create backup directory
        $backup_dir = WP_CONTENT_DIR . '/backups/demo-builder';
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }

        // Final file path
        $final_path = $backup_dir . '/' . $filename;
        
        // Merge chunks
        $final_handle = fopen($final_path, 'wb');
        if (!$final_handle) {
            wp_send_json_error(['message' => __('Cannot create final file.', 'demo-builder')]);
        }

        $chunks = glob($upload_temp_dir . '/chunk_*');
        sort($chunks);

        foreach ($chunks as $chunk_file) {
            $chunk_handle = fopen($chunk_file, 'rb');
            if ($chunk_handle) {
                stream_copy_to_stream($chunk_handle, $final_handle);
                fclose($chunk_handle);
            }
            unlink($chunk_file);
        }

        fclose($final_handle);

        // Cleanup temp directory
        unlink($meta_file);
        @rmdir($upload_temp_dir);

        $this->log('finalize', "Upload finalized: {$filename} (" . size_format(filesize($final_path)) . ')');

        wp_send_json_success([
            'message' => __('Upload complete!', 'demo-builder'),
            'file' => $final_path,
            'filename' => $filename,
            'size' => filesize($final_path),
        ]);
    }

    /**
     * Get PHP upload limits
     */
    public function get_upload_limits() {
        check_ajax_referer('demo_builder_nonce', 'nonce');

        $limits = [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'max_upload_bytes' => $this->parse_size(ini_get('upload_max_filesize')),
            'max_post_bytes' => $this->parse_size(ini_get('post_max_size')),
        ];

        // Determine effective limit
        $limits['effective_limit'] = min(
            $limits['max_upload_bytes'],
            $limits['max_post_bytes']
        );
        $limits['effective_limit_formatted'] = size_format($limits['effective_limit']);

        // Add suggestion if limits are low
        $limits['suggestions'] = [];
        if ($limits['max_upload_bytes'] < 128 * 1024 * 1024) {
            $limits['suggestions'][] = 'upload_max_filesize = 128M';
        }
        if ($limits['max_post_bytes'] < 128 * 1024 * 1024) {
            $limits['suggestions'][] = 'post_max_size = 128M';
        }
        if ($this->parse_size(ini_get('memory_limit')) < 256 * 1024 * 1024) {
            $limits['suggestions'][] = 'memory_limit = 256M';
        }
        if (intval(ini_get('max_execution_time')) < 300 && intval(ini_get('max_execution_time')) > 0) {
            $limits['suggestions'][] = 'max_execution_time = 300';
        }

        wp_send_json_success($limits);
    }

    /**
     * Cancel ongoing upload
     */
    public function cancel_upload() {
        check_ajax_referer('demo_builder_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied.', 'demo-builder')]);
        }

        $upload_id = isset($_POST['uploadId']) ? sanitize_text_field(wp_unslash($_POST['uploadId'])) : '';
        
        if (empty($upload_id)) {
            wp_send_json_error(['message' => __('Invalid upload ID.', 'demo-builder')]);
        }

        $upload_temp_dir = $this->temp_dir . '/' . $upload_id;
        
        if (file_exists($upload_temp_dir)) {
            $this->recursive_delete($upload_temp_dir);
            $this->log('cancel', "Upload cancelled: {$upload_id}");
        }

        wp_send_json_success(['message' => __('Upload cancelled.', 'demo-builder')]);
    }

    /**
     * Count chunks in directory
     *
     * @param string $dir Directory path
     * @return int
     */
    private function count_chunks($dir) {
        $chunks = glob($dir . '/chunk_*');
        return count($chunks);
    }

    /**
     * Parse size string to bytes
     *
     * @param string $size Size string (e.g., '128M')
     * @return int
     */
    private function parse_size($size) {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $size = (int) $size;
        
        switch ($last) {
            case 'g':
                $size *= 1024;
                // fallthrough
            case 'm':
                $size *= 1024;
                // fallthrough
            case 'k':
                $size *= 1024;
        }
        
        return $size;
    }

    /**
     * Recursively delete directory
     *
     * @param string $dir Directory path
     */
    private function recursive_delete($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->recursive_delete($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    /**
     * Log message
     *
     * @param string $type Log type
     * @param string $message Log message
     */
    private function log($type, $message) {
        if (class_exists('Demo_Builder_Core')) {
            Demo_Builder_Core::get_instance()->log('upload', "[{$type}] {$message}");
        }
    }

    /**
     * Cleanup old temp uploads
     */
    public static function cleanup_temp() {
        $instance = self::get_instance();
        $temp_dir = $instance->temp_dir;
        
        if (!is_dir($temp_dir)) {
            return;
        }
        
        // Remove uploads older than 24 hours
        $dirs = glob($temp_dir . '/*', GLOB_ONLYDIR);
        foreach ($dirs as $dir) {
            $meta_file = $dir . '/meta.json';
            if (file_exists($meta_file)) {
                $meta = json_decode(file_get_contents($meta_file), true);
                if (isset($meta['started_at']) && (time() - $meta['started_at']) > 86400) {
                    $instance->recursive_delete($dir);
                }
            } elseif ((time() - filemtime($dir)) > 86400) {
                $instance->recursive_delete($dir);
            }
        }
    }
}

// Initialize
Demo_Builder_Upload_Handler::get_instance();
