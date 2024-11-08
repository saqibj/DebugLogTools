<?php
/*
Plugin Name: Debug Log Tools
Plugin URI: https://github.com/saqibj/DebugLogTools
Description: A WordPress plugin to view, filter, enable/disable, and flush the debug log for troubleshooting.
Version: 3.0.0
Author: Saqib Jawaid
Author URI: https://github.com/saqibj
License: GPL-3.0
Text Domain: debug-log-tools
*/

// Enqueue CSS and JavaScript
function debug_log_tools_enqueue_assets() {
    wp_enqueue_style(
        'debug-log-tools-css',
        plugin_dir_url(__FILE__) . 'css/debug-log-tools.css',
        array(),
        '3.0.0'
    );

    wp_enqueue_script(
        'debug-log-tools-js',
        plugin_dir_url(__FILE__) . 'js/debug-log-tools.js',
        array('jquery'),
        '3.0.0',
        true
    );
}
add_action('admin_enqueue_scripts', 'debug_log_tools_enqueue_assets');

// Display Debug Log
function debug_log_tools_display() {
    if (!current_user_can('manage_options')) {
        return;
    }

    echo '<div class="wrap">';
    echo '<h1>' . __('Debug Log Tools', 'debug-log-tools') . '</h1>';

    // Display Enable Debug Logging Option
    $is_debug_enabled = defined('WP_DEBUG') && WP_DEBUG;
    echo '<form method="post" action="">';
    wp_nonce_field('toggle_debug_log', 'debug_log_tools_nonce');
    echo '<label>';
    echo '<input type="checkbox" name="enable_debug_log" ' . checked($is_debug_enabled, true, false) . '> ';
    echo __('Enable Debug Logging', 'debug-log-tools');
    echo '</label>';
    submit_button(__('Save Changes', 'debug-log-tools'));
    echo '</form>';

    // Flush Log form and debug log display
    echo '<form method="post" action="' . admin_url('admin-post.php') . '">';
    wp_nonce_field('flush_debug_log', 'debug_log_tools_nonce');
    echo '<input type="hidden" name="action" value="debug_log_tools_flush">';
    submit_button(__('Flush Log', 'debug-log-tools'));
    echo '</form>';

    // Display debug log content
    $log_file = WP_CONTENT_DIR . '/debug.log';
    if (file_exists($log_file)) {
        echo '<pre style="background-color: #f4f4f4; padding: 15px;">' . esc_html(file_get_contents($log_file)) . '</pre>';
    } else {
        echo '<p>' . __('No debug log found.', 'debug-log-tools') . '</p>';
    }

    echo '</div>';
}

// Add Admin Menu Item
function debug_log_tools_admin_menu() {
    add_submenu_page(
        'tools.php',
        __('Debug Log Tools', 'debug-log-tools'),
        __('Debug Log Tools', 'debug-log-tools'),
        'manage_options',
        'debug-log-tools',
        'debug_log_tools_display'
    );
}
add_action('admin_menu', 'debug_log_tools_admin_menu');

// Flush Log Function
function debug_log_tools_flush_log() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Unauthorized access', 'debug-log-tools'));
    }

    if (!isset($_POST['debug_log_tools_nonce']) || !wp_verify_nonce($_POST['debug_log_tools_nonce'], 'flush_debug_log')) {
        wp_die(__('Security check failed', 'debug-log-tools'));
    }

    $log_file = WP_CONTENT_DIR . '/debug.log';
    if (file_exists($log_file)) {
        file_put_contents($log_file, ''); // Clear log
    }

    wp_safe_redirect(admin_url('tools.php?page=debug-log-tools&flushed=1'));
    exit;
}
add_action('admin_post_debug_log_tools_flush', 'debug_log_tools_flush_log');

// Display Success Message After Flush
function debug_log_tools_admin_notices() {
    if (isset($_GET['flushed']) && $_GET['flushed'] == '1') {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Debug log flushed successfully.', 'debug-log-tools') . '</p></div>';
    }
}
add_action('admin_notices', 'debug_log_tools_admin_notices');
