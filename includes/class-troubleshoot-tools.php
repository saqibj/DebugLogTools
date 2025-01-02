<?php
/**
 * Troubleshooting Tools Class
 *
 * @package DebugLogTools
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Troubleshoot_Tools
 */
class Troubleshoot_Tools {

    /**
     * Get system information
     *
     * @return array System information
     */
    public static function get_system_info() {
        global $wpdb;

        return array(
            'wp_version' => get_bloginfo('version'),
            'php_version' => phpversion(),
            'mysql_version' => $wpdb->db_version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'],
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_input_vars' => ini_get('max_input_vars'),
            'display_errors' => ini_get('display_errors'),
            'wp_debug' => defined('WP_DEBUG') && WP_DEBUG,
            'wp_debug_log' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG,
            'wp_debug_display' => defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY
        );
    }

    /**
     * Check common issues
     *
     * @return array Issues found
     */
    public static function check_common_issues() {
        $issues = array();

        // Check PHP version
        if (version_compare(phpversion(), '7.4', '<')) {
            $issues[] = array(
                'type' => 'warning',
                'message' => __('Your PHP version is outdated. WordPress recommends using PHP 7.4 or higher.', 'debug-log-tools')
            );
        }

        // Check memory limit
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        if ($memory_limit < 64 * 1024 * 1024) { // 64MB
            $issues[] = array(
                'type' => 'warning',
                'message' => __('Memory limit is low. Recommended minimum is 64MB.', 'debug-log-tools')
            );
        }

        // Check for development files
        if (file_exists(ABSPATH . 'wp-config-sample.php')) {
            $issues[] = array(
                'type' => 'error',
                'message' => __('wp-config-sample.php file exists. This should be removed in production.', 'debug-log-tools')
            );
        }

        // Check file permissions
        $wp_content = WP_CONTENT_DIR;
        if (substr(sprintf('%o', fileperms($wp_content)), -4) > '0755') {
            $issues[] = array(
                'type' => 'warning',
                'message' => __('wp-content directory permissions are too open.', 'debug-log-tools')
            );
        }

        return $issues;
    }

    /**
     * Get active plugins with their versions
     *
     * @return array Active plugins
     */
    public static function get_active_plugins() {
        $active_plugins = array();
        
        foreach (get_option('active_plugins') as $plugin) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
            $active_plugins[] = array(
                'name' => $plugin_data['Name'],
                'version' => $plugin_data['Version'],
                'author' => $plugin_data['Author']
            );
        }

        return $active_plugins;
    }

    /**
     * Test WordPress cron functionality
     *
     * @return bool|string True if working, error message if not
     */
    public static function test_wp_cron() {
        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            return __('WP Cron is disabled via configuration.', 'debug-log-tools');
        }

        $cron_test = get_transient('debug_log_tools_cron_test');
        if ($cron_test === false) {
            wp_schedule_single_event(time() - 1, 'debug_log_tools_cron_test');
            set_transient('debug_log_tools_cron_test', time(), 5 * MINUTE_IN_SECONDS);
            return __('Cron test scheduled. Check back in a few minutes.', 'debug-log-tools');
        }

        return true;
    }
} 