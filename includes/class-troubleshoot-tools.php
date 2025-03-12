<?php
/**
 * Troubleshooting Tools Class
 *
 * @package DebugLogTools
 */

if (!defined('ABSPATH')) {
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

        $info = array(
            'wp_version' => get_bloginfo('version'),
            'php_version' => phpversion(),
            'mysql_version' => $wpdb->db_version(),
            'server_software' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_input_vars' => ini_get('max_input_vars'),
            'display_errors' => ini_get('display_errors'),
            'wp_debug' => defined('WP_DEBUG') && WP_DEBUG ? 'Yes' : 'No',
            'wp_debug_log' => defined('WP_DEBUG_LOG') && WP_DEBUG_LOG ? 'Yes' : 'No',
            'wp_debug_display' => defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY ? 'Yes' : 'No'
        );

        // Add multisite status
        if (is_multisite()) {
            $info['multisite'] = 'Yes';
            $info['network_sites'] = get_blog_count();
        }

        return $info;
    }

    /**
     * Check common issues
     *
     * @return array Issues found
     */
    public static function check_common_issues() {
        $issues = array();

        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $issues[] = array(
                'type' => 'warning',
                'message' => esc_html__('Your PHP version is outdated. WordPress recommends using PHP 7.4 or higher.', 'debug-log-tools')
            );
        }

        // Check memory limit
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        if ($memory_limit < 64 * 1024 * 1024) { // 64MB
            $issues[] = array(
                'type' => 'warning',
                'message' => esc_html__('Memory limit is low. Recommended minimum is 64MB.', 'debug-log-tools')
            );
        }

        // Check for development files
        if (file_exists(ABSPATH . 'wp-config-sample.php')) {
            $issues[] = array(
                'type' => 'error',
                'message' => esc_html__('wp-config-sample.php file exists. This should be removed in production.', 'debug-log-tools')
            );
        }

        // Check file permissions
        $wp_content = WP_CONTENT_DIR;
        if (file_exists($wp_content)) {
            $permissions = fileperms($wp_content) & 0777;
            if ($permissions & 0x0004) { // World readable
                $issues[] = array(
                    'type' => 'warning',
                    'message' => esc_html__('wp-content directory permissions are too open.', 'debug-log-tools')
                );
            }
        }

        // Check debug.log file
        $log_file = WP_CONTENT_DIR . '/debug.log';
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            if (!file_exists($log_file)) {
                $issues[] = array(
                    'type' => 'warning',
                    'message' => esc_html__('Debug logging is enabled but debug.log file does not exist.', 'debug-log-tools')
                );
            } elseif (file_exists($log_file) && !is_writable($log_file)) {
                $issues[] = array(
                    'type' => 'error',
                    'message' => esc_html__('debug.log file exists but is not writable.', 'debug-log-tools')
                );
            }
        }

        return $issues;
    }

    /**
     * Get active plugins with their versions
     *
     * @return array Active plugins
     */
    public static function get_active_plugins() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $active_plugins = array();
        
        // Get regular active plugins
        $plugins = (array) get_option('active_plugins', array());
        
        // Add network active plugins for multisite
        if (is_multisite()) {
            $network_plugins = array_keys((array) get_site_option('active_sitewide_plugins', array()));
            $plugins = array_merge($plugins, $network_plugins);
        }

        foreach ($plugins as $plugin) {
            $plugin_path = WP_PLUGIN_DIR . '/' . $plugin;
            if (!file_exists($plugin_path)) {
                continue;
            }
            
            $plugin_data = get_plugin_data($plugin_path);
            if (!empty($plugin_data['Name'])) {
                $active_plugins[] = array(
                    'name' => $plugin_data['Name'],
                    'version' => $plugin_data['Version'],
                    'author' => $plugin_data['Author']
                );
            }
        }

        return $active_plugins;
    }

    /**
     * Handle cron test
     */
    public static function handle_cron_test() {
        set_transient('debug_log_tools_cron_test_completed', time(), 15 * MINUTE_IN_SECONDS);
    }

    /**
     * Test WordPress cron functionality
     *
     * @return bool|string True if working, error message if not
     */
    public static function test_wp_cron() {
        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            return esc_html__('WP Cron is disabled via DISABLE_WP_CRON constant.', 'debug-log-tools');
        }

        $cron_test = get_transient('debug_log_tools_cron_test');
        $cron_completed = get_transient('debug_log_tools_cron_test_completed');
        
        if ($cron_completed !== false) {
            return true;
        }

        if ($cron_test === false) {
            wp_schedule_single_event(time() - 1, 'debug_log_tools_cron_test');
            set_transient('debug_log_tools_cron_test', time(), 5 * MINUTE_IN_SECONDS);
            return esc_html__('Cron test scheduled. Check back in a few minutes.', 'debug-log-tools');
        }

        return esc_html__('Waiting for cron job to complete...', 'debug-log-tools');
    }
} 