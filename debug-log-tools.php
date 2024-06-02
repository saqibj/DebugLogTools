<?php
/**
 * Plugin Name: Debug Log Tools
 * Description: A plugin to view and manage the WordPress debug log with plugin-specific filtering and log flushing.
 * Version: 2.0
 * Author: Saqib Jawaid
 * License: MIT
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin path
define('DEBUG_LOG_TOOLS_PATH', plugin_dir_path(__FILE__));
define('DEBUG_LOG_TOOLS_URL', plugin_dir_url(__FILE__));

// Include the core class
require_once DEBUG_LOG_TOOLS_PATH . 'includes/class-debug-log-tools.php';

// Initialize the plugin
function debug_log_tools_init() {
    $debug_log_tools = new Debug_Log_Tools();
    $debug_log_tools->init();
}
add_action('plugins_loaded', 'debug_log_tools_init');
