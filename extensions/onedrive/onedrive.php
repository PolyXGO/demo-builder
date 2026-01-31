<?php
/**
 * OneDrive Extension
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load base class
require_once DEMO_BUILDER_PLUGIN_DIR . 'extensions/cloud-storage-base/cloud-storage-base.php';

// Load OneDrive provider
require_once __DIR__ . '/class-onedrive.php';

// Initialize
Demo_Builder_OneDrive::get_instance();
