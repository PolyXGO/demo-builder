<?php
/**
 * Download Backup Handler
 *
 * @package DemoBuilder
 */

// Load WordPress
require_once dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/wp-load.php';

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Check nonce
if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'demo_builder_nonce')) {
    wp_die(__('Security check failed.', 'demo-builder'));
}

// Check capability
if (!current_user_can('manage_options')) {
    wp_die(__('Permission denied.', 'demo-builder'));
}

// Get backup ID
$backup_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$backup_id) {
    wp_die(__('Invalid backup ID.', 'demo-builder'));
}

// Get backup from database
global $wpdb;
$table = $wpdb->prefix . 'demobuilder_backups';
$backup = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $backup_id));

if (!$backup) {
    wp_die(__('Backup not found.', 'demo-builder'));
}

if (!file_exists($backup->file_path)) {
    wp_die(__('Backup file not found on disk.', 'demo-builder'));
}

// Get file extension
$extension = pathinfo($backup->file_path, PATHINFO_EXTENSION);

// Set headers for download
nocache_headers();
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . sanitize_file_name($backup->slug . '.' . $extension) . '"');
header('Content-Length: ' . filesize($backup->file_path));
header('Content-Transfer-Encoding: binary');
header('Pragma: public');

// Clear output buffer
while (ob_get_level()) {
    ob_end_clean();
}

// Read file and output
readfile($backup->file_path);
exit;
