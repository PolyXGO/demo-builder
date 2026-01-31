<?php
/**
 * Cloud Storage Base Extension
 *
 * Loads the cloud storage interface and abstract base class
 *
 * @package DemoBuilder
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Load interface
require_once __DIR__ . '/interface-cloud-provider.php';

// Load abstract base class
require_once __DIR__ . '/class-cloud-storage.php';
