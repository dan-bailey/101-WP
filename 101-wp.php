<?php
/**
 * Plugin Name: 101-WP
 * Plugin URI: https://danbailey.net
 * Description: Manage your 101 Things in 1001 Days lists
 * Version: 1.0.0
 * Author: Dan Bailey
 * Author URI: https://danbailey.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: 101-wp
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_101_VERSION', '1.0.0');
define('WP_101_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_101_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_101_PLUGIN_FILE', __FILE__);

// Include required files
require_once WP_101_PLUGIN_DIR . 'includes/class-post-type.php';
require_once WP_101_PLUGIN_DIR . 'includes/class-meta-boxes.php';
require_once WP_101_PLUGIN_DIR . 'includes/class-frontend.php';
require_once WP_101_PLUGIN_DIR . 'includes/class-gutenberg-blocks.php';

/**
 * Initialize the plugin
 */
function wp_101_init() {
    // Register post type
    WP_101_Post_Type::init();

    // Initialize meta boxes
    WP_101_Meta_Boxes::init();

    // Initialize frontend
    WP_101_Frontend::init();

    // Initialize Gutenberg blocks
    WP_101_Gutenberg_Blocks::init();
}
add_action('plugins_loaded', 'wp_101_init');

/**
 * Activation hook
 */
function wp_101_activate() {
    // Register post type to flush rewrite rules
    WP_101_Post_Type::register_post_type();

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'wp_101_activate');

/**
 * Deactivation hook
 */
function wp_101_deactivate() {
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'wp_101_deactivate');
