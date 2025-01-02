<?php
/**
 * Debug Log Tools
 *
 * @package     DebugLogTools
 * @author      Saqib Jawaid
 * @copyright   2024 Saqib Jawaid
 * @license     GPL-3.0
 *
 * @wordpress-plugin
 * Plugin Name: Debug Log Tools
 * Plugin URI:  https://github.com/saqibj/debug-log-tools
 * Description: View, filter, and manage WordPress debug logs from your dashboard.
 * Version:     3.0.1
 * Author:      Saqib Jawaid
 * Author URI:  https://github.com/saqibj
 * Text Domain: debug-log-tools
 * License:     GPL-3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include required files
require_once plugin_dir_path( __FILE__ ) . 'includes/class-debug-log-manager.php';

// Initialize Debug Log Manager
new Debug_Log_Manager();

/**
 * Enqueue admin scripts and styles
 */
function debug_log_tools_enqueue_assets() {
    $version = '3.0.1';
    
    wp_enqueue_style(
        'debug-log-tools-css',
        plugins_url( 'css/debug-log-tools.css', __FILE__ ),
        array(),
        $version
    );
    
    wp_enqueue_script(
        'debug-log-tools-js',
        plugins_url( 'js/debug-log-tools.js', __FILE__ ),
        array( 'jquery' ),
        $version,
        true
    );
    
    wp_localize_script(
        'debug-log-tools-js',
        'debugLogTools',
        array(
            'nonce'   => wp_create_nonce( 'debug_log_tools_refresh' ),
            'ajaxurl' => admin_url( 'admin-ajax.php' )
        )
    );
}
add_action('admin_enqueue_scripts', 'debug_log_tools_enqueue_assets');

/**
 * AJAX handler for log refresh
 */
function debug_log_tools_ajax_refresh() {
    check_ajax_referer( 'debug_log_tools_refresh', 'nonce' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( esc_html__( 'Unauthorized access.', 'debug-log-tools' ) );
    }

    $log_file = WP_CONTENT_DIR . '/debug.log';
    if ( file_exists( $log_file ) ) {
        $contents = file_get_contents( $log_file );
        if ( false === $contents ) {
            wp_send_json_error( esc_html__( 'Error reading log file.', 'debug-log-tools' ) );
        }
        wp_send_json_success( esc_html( $contents ) );
    }
    wp_send_json_error( esc_html__( 'Log file not found.', 'debug-log-tools' ) );
}

// Display Debug Log
function debug_log_tools_display() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $log_file = WP_CONTENT_DIR . '/debug.log';
    $max_display_size = 1024 * 1024; // 1MB limit

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__( 'Debug Log Tools', 'debug-log-tools' ) . '</h1>';

    // Display Enable Debug Logging Option
    $is_debug_enabled = Debug_Log_Manager::is_debug_enabled();
    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
    wp_nonce_field( 'toggle_debug_log', 'debug_log_tools_nonce' );
    echo '<input type="hidden" name="action" value="debug_log_tools_toggle">';
    echo '<label>';
    echo '<input type="checkbox" name="enable_debug_log" ' . 
         checked( $is_debug_enabled, true, false ) . '> ';
    echo esc_html__( 'Enable Debug Logging', 'debug-log-tools' );
    echo '</label>';
    submit_button( __( 'Save Changes', 'debug-log-tools' ) );
    echo '</form>';

    // Flush Log form and debug log display
    echo '<form method="post" action="' . admin_url('admin-post.php') . '">';
    wp_nonce_field('flush_debug_log', 'debug_log_tools_nonce');
    echo '<input type="hidden" name="action" value="debug_log_tools_flush">';
    submit_button(__('Flush Log', 'debug-log-tools'));
    echo '</form>';

    // Add search box
    echo '<div class="debug-log-controls">';
    echo '<input type="text" id="log-search" placeholder="' . 
        esc_attr__('Search log...', 'debug-log-tools') . 
        '" class="regular-text">';
    
    // Add auto-refresh toggle
    echo '<div class="auto-refresh-control">';
    echo '<label>';
    echo '<input type="checkbox" id="auto-refresh-toggle"> ';
    echo __('Auto-refresh (30s)', 'debug-log-tools');
    echo '</label>';
    echo '</div>';
    echo '</div>';

    // Display debug log content
    if (file_exists($log_file)) {
        $file_size = filesize($log_file);
        if ($file_size > $max_display_size) {
            echo '<div class="notice notice-warning"><p>' . 
                sprintf(__('Log file is large (%s MB). Showing last 1MB of data.', 'debug-log-tools'), 
                number_format($file_size / (1024 * 1024), 2)) . 
                '</p></div>';
            
            $contents = file_get_contents($log_file, false, null, $file_size - $max_display_size);
        } else {
            $contents = file_get_contents($log_file);
        }
        
        if ($contents === false) {
            echo '<div class="notice notice-error"><p>' . __('Error reading log file.', 'debug-log-tools') . '</p></div>';
        } else {
            echo '<pre class="debug-log-content">' . esc_html($contents) . '</pre>';
        }
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
    if ( isset( $_GET['debug_updated'] ) ) {
        $status = $_GET['debug_updated'] === '1' ? 'enabled' : 'disabled';
        $message = sprintf(
            /* translators: %s: debug status */
            esc_html__( 'Debug logging has been %s. You may need to reload the page to see the changes.', 'debug-log-tools' ),
            $status
        );
        echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
    }

    if ( isset( $_GET['flushed'] ) && $_GET['flushed'] == '1' ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . 
             esc_html__( 'Debug log flushed successfully.', 'debug-log-tools' ) . 
             '</p></div>';
    }
}
add_action('admin_notices', 'debug_log_tools_admin_notices');
