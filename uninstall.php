<?php
/**
 * Uninstall Debug Log Tools
 *
 * @package DebugLogTools
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
$options = array(
    'debug_log_tools_version',
    'debug_log_tools_module_debugging_active',
    'debug_log_tools_module_notifications_active',
    'debug_log_tools_module_performance_active',
    'debug_log_tools_module_security_active',
    'debug_log_tools_notification_settings',
    'debug_log_tools_performance_settings',
    'debug_log_tools_security_settings'
);

foreach ($options as $option) {
    delete_option($option);
    delete_site_option($option); // For multisite
}

// Clean up any transients
delete_transient('debug_log_tools_notifications_queue');
delete_transient('debug_log_tools_performance_data');
delete_transient('debug_log_tools_security_events');

// Option name to delete
$option_name = 'debug_log_tools_rotation_max_size';

// Delete plugin options from options table
delete_option( $option_name );

// For site options in multisite (if applicable, not used in current plugin, but good practice to include for future)
// delete_site_option( $option_name );

// Optional: Delete log files on uninstall (CAUTION: Data deletion)
// For now, we are NOT implementing automatic log file deletion on uninstall for safety reasons.
// If you decide to add this feature, it should be behind a user-controlled setting.

// Example of how you might delete log files if you choose to implement this (USE WITH CAUTION):
/*
$log_manager = new Debug_Log_Manager(); // Assuming Debug_Log_Manager class is accessible here
$log_filepath = $log_manager->get_log_file_path();
if ( file_exists( $log_filepath ) ) {
    unlink( $log_filepath ); // Delete main log file
}
// Add logic to delete rotated log files if needed (e.g., by iterating through a directory and deleting files matching a pattern)
*/ 