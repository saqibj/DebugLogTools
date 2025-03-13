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