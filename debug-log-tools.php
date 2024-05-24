<?php
/*
Plugin Name: Debug Log Tools
Description: A collection of tools to manage and view the WordPress debug log.
Version: 1.0
Author: Saqib Jawaid
License: GPL v3 or later
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Add the Debug Log Tools menu.
add_action( 'admin_menu', 'debug_log_tools_menu' );

function debug_log_tools_menu() {
    add_menu_page(
        'Debug Log Tools',
        'Debug Log Tools',
        'manage_options',
        'debug-log-tools',
        'debug_log_tools_page',
        'dashicons-editor-code', // Icon for the menu item (optional).
        99 // Position in the menu.
    );

    // Add submenus.
    add_submenu_page(
        'debug-log-tools',
        'Flush Debug Log',
        'Flush Debug Log',
        'manage_options',
        'flush-debug-log',
        'flush_debug_log_page'
    );

    add_submenu_page(
        'debug-log-tools',
        'Debug Log Viewer',
        'Debug Log Viewer',
        'manage_options',
        'debug-log-viewer',
        'debug_log_viewer_page'
    );
}

// Callback for the main Debug Log Tools page.
function debug_log_tools_page() {
    echo '<div class="wrap">';
    echo '<h1>Debug Log Tools</h1>';
    echo '<p>Welcome to the Debug Log Tools! Choose a tool from the menu above.</p>';
    echo '</div>';
}

// Callback for the "Flush Debug Log" page.
function flush_debug_log_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_POST['flush_debug_log'] ) ) {
        flush_debug_log();
        echo '<div class="notice notice-success is-dismissible"><p>Debug log has been flushed.</p></div>';
    }
    
    echo '<div class="wrap">';
    echo '<h1>Flush Debug Log</h1>';
    echo '<form method="post">';
    echo '<input type="hidden" name="flush_debug_log" value="1">';
    submit_button( 'Flush Debug Log' );
    echo '</form>';
    echo '</div>';
}

// Callback for the "Debug Log Viewer" page.
function debug_log_viewer_page() {
    echo '<div class="wrap">';
    echo '<h1>Debug Log Viewer</h1>';
    echo '<form method="post">';
    echo '<p>Enter the period to display debug log entries:</p>';
    echo '<input type="number" name="log_period" min="1" placeholder="Enter number of days, hours, or minutes">';
    echo '<input type="submit" name="view_log" value="View Log">';
    echo '</form>';

    if ( isset( $_POST['view_log'] ) ) {
        $log_period = isset( $_POST['log_period'] ) ? intval( $_POST['log_period'] ) : 0;
        if ( $log_period > 0 ) {
            display_debug_log( $log_period );
        } else {
            echo '<p>Please enter a valid period.</p>';
        }
    }

    echo '</div>';
}

// Function to flush the debug log.
function flush_debug_log() {
    $log_file = WP_CONTENT_DIR . '/debug.log';

    if ( file_exists( $log_file ) ) {
        file_put_contents( $log_file, '' );
    }
}

// Function to display debug log entries for a given period.
function display_debug_log( $log_period ) {
    $log_file = WP_CONTENT_DIR . '/debug.log';
    
    // Calculate the timestamp for the start of the period.
    $start_timestamp = strtotime( '-' . $log_period . ' days' );

    // Read the debug log file and display entries after the start timestamp.
    if ( file_exists( $log_file ) ) {
        $log_content = file_get_contents( $log_file );
        $log_entries = explode( "\n", $log_content );

        echo '<h2>Debug Log Entries for the Last ' . $log_period . ' Days</h2>';
        echo '<pre>';
        foreach ( $log_entries as $entry ) {
            // Extract the timestamp from the log entry.
            preg_match( '/^\[([^\]]+)\]/', $entry, $matches );
            if ( isset( $matches[1] ) ) {
                $entry_timestamp = strtotime( $matches[1] );
                if ( $entry_timestamp >= $start_timestamp ) {
                    echo $entry . "\n";
                }
            }
        }
        echo '</pre>';
    } else {
        echo '<p>No debug log file found.</p>';
    }
}
?>
