<?php
/**
 * Google Drive Extension
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load base class
require_once DEMO_BUILDER_PLUGIN_DIR . 'extensions/cloud-storage-base/cloud-storage-base.php';

// Load Google Drive provider
require_once __DIR__ . '/class-google-drive.php';

// Initialize
Demo_Builder_Google_Drive::get_instance();
