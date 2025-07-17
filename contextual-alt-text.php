<?php

/**
 * Plugin Name:     Contextual Alt Text
 * Description:     Automatically generate context-aware alt text for images using various AI providers, including Hugging Face. Generates descriptions based on image content and the surrounding post/page context.
 * Version:         3.0.0
 * Author:          Fuyuan Cheng
 * Author URI:      https://github.com/gloomcheng
 * Original Author: Valerio Monti
 * Original URI:    https://www.vmweb.it
 * Text Domain:     contextual-alt-text
 * Domain Path:     /languages
 * License:         GPL v3
 * Requires PHP:    7.4
 * Requires WP:     6.0
 * Namespace:       ContextualAltText
 */

use ContextualAltText\App\Setup;
use ContextualAltText\App\Admin\PluginOptions;
use ContextualAltText\App\Admin\MediaLibrary;
use ContextualAltText\App\Admin\BulkGenerator;
use ContextualAltText\App\Logging\DBLogger;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!defined('CONTEXTUAL_ALT_TEXT_PLUGIN_FILE')) {
    define('CONTEXTUAL_ALT_TEXT_PLUGIN_FILE', __FILE__);
}

define('CONTEXTUAL_ALT_TEXT_FILE_ABSPATH', __FILE__);
define('CONTEXTUAL_ALT_TEXT_ABSPATH', dirname(__FILE__));
define('CONTEXTUAL_ALT_TEXT_URL', plugin_dir_url(__FILE__));
define('CONTEXTUAL_ALT_TEXT_LANGUAGES_RELATIVE_PATH', dirname(plugin_basename(__FILE__)) . '/languages/');

require CONTEXTUAL_ALT_TEXT_ABSPATH . '/vendor/autoload.php';

/**
 * Plugin activation hook
 */
function contextual_alt_text_activate() {
    // Create database tables
    DBLogger::make()->createLogTable();
    
    // Set default options if they don't exist
    if (!get_option('cat_enable_plugin')) {
        add_option('cat_enable_plugin', false);
    }
}
register_activation_hook(__FILE__, 'contextual_alt_text_activate');

/**
 * Plugin deactivation hook
 */
function contextual_alt_text_deactivate() {
    // Don't delete tables or options on deactivation
    // Only clean up on uninstall
}
register_deactivation_hook(__FILE__, 'contextual_alt_text_deactivate');

Setup::getInstance();
PluginOptions::getInstance();
MediaLibrary::register();
BulkGenerator::register();
