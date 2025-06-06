<?php
/**
 * Troubleshooting Tools Class
 *
 * @package DebugLogTools
 */

namespace DebugLogTools;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Troubleshoot_Tools
 */
class Troubleshoot_Tools {

    /**
     * Initialize the troubleshoot tools
     */
    public function init() {
        // Additional initialization if needed
    }

    /**
     * Get system information
     *
     * @return array System information
     */
    public static function get_system_info() {
        global $wpdb;

        $info = array(
            'wp_version' => \get_bloginfo('version'),
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
        if (\is_multisite()) {
            $info['multisite'] = 'Yes';
            $info['network_sites'] = \get_blog_count();
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
                'message' => \esc_html__('Your PHP version is outdated. WordPress recommends using PHP 7.4 or higher.', 'debug-log-tools')
            );
        }

        // Check memory limit
        $memory_limit = \wp_convert_hr_to_bytes(ini_get('memory_limit'));
        if ($memory_limit < 64 * 1024 * 1024) { // 64MB
            $issues[] = array(
                'type' => 'warning',
                'message' => \esc_html__('Memory limit is low. Recommended minimum is 64MB.', 'debug-log-tools')
            );
        }

        // Check for development files
        if (file_exists(ABSPATH . 'wp-config-sample.php')) {
            $issues[] = array(
                'type' => 'error',
                'message' => \esc_html__('wp-config-sample.php file exists. This should be removed in production.', 'debug-log-tools')
            );
        }

        // Check file permissions
        $wp_content = \WP_CONTENT_DIR;
        if (file_exists($wp_content)) {
            $permissions = fileperms($wp_content) & 0777;
            if ($permissions & 0x0004) { // World readable
                $issues[] = array(
                    'type' => 'warning',
                    'message' => \esc_html__('wp-content directory permissions are too open.', 'debug-log-tools')
                );
            }
        }

        // Check debug.log file
        $log_file = \WP_CONTENT_DIR . '/debug.log';
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            if (!file_exists($log_file)) {
                $issues[] = array(
                    'type' => 'warning',
                    'message' => \esc_html__('Debug logging is enabled but debug.log file does not exist.', 'debug-log-tools')
                );
            } elseif (file_exists($log_file) && !is_writable($log_file)) {
                $issues[] = array(
                    'type' => 'error',
                    'message' => \esc_html__('debug.log file exists but is not writable.', 'debug-log-tools')
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
        $plugins = (array) \get_option('active_plugins', array());
        
        // Add network active plugins for multisite
        if (\is_multisite()) {
            $network_plugins = array_keys((array) \get_site_option('active_sitewide_plugins', array()));
            $plugins = array_merge($plugins, $network_plugins);
        }

        foreach ($plugins as $plugin) {
            $plugin_path = \WP_PLUGIN_DIR . '/' . $plugin;
            if (!file_exists($plugin_path)) {
                continue;
            }
            
            $plugin_data = \get_plugin_data($plugin_path);
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
        \set_transient('debug_log_tools_cron_test_completed', time(), 15 * MINUTE_IN_SECONDS);
    }

    /**
     * Test WordPress cron functionality
     *
     * @return bool|string True if working, error message if not
     */
    public static function test_wp_cron() {
        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            return \esc_html__('WP Cron is disabled via DISABLE_WP_CRON constant.', 'debug-log-tools');
        }

        $cron_test = \get_transient('debug_log_tools_cron_test');
        $cron_completed = \get_transient('debug_log_tools_cron_test_completed');
        
        if ($cron_completed !== false) {
            return true;
        }

        if ($cron_test === false) {
            \wp_schedule_single_event(time() - 1, 'debug_log_tools_cron_test');
            \set_transient('debug_log_tools_cron_test', time(), 5 * MINUTE_IN_SECONDS);
            return \esc_html__('Cron test scheduled. Check back in a few minutes.', 'debug-log-tools');
        }

        return \esc_html__('Waiting for cron job to complete...', 'debug-log-tools');
    }

    /**
     * Get plugin data safely.
     *
     * @param string $plugin_file Path to the plugin file.
     * @return array|false Plugin data array or false on failure.
     */
    private function get_plugin_data($plugin_file) {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        if (!function_exists('get_plugin_data')) {
            return false;
        }

        return \get_plugin_data($plugin_file);
    }

    /**
     * Check file permissions.
     *
     * @param string $file_path Path to the file.
     * @return array Array with status and message.
     */
    private function check_file_permissions($file_path) {
        if (!file_exists($file_path)) {
            return array(
                'status' => 'error',
                'message' => \esc_html__('File does not exist.', 'debug-log-tools')
            );
        }

        $permissions = fileperms($file_path);
        $is_readable = is_readable($file_path);
        $is_writable = is_writable($file_path);

        return array(
            'status' => ($is_readable && $is_writable) ? 'success' : 'warning',
            'message' => sprintf(
                \esc_html__('Permissions: %s, Readable: %s, Writable: %s', 'debug-log-tools'),
                substr(sprintf('%o', $permissions), -4),
                $is_readable ? \esc_html__('Yes', 'debug-log-tools') : \esc_html__('No', 'debug-log-tools'),
                $is_writable ? \esc_html__('Yes', 'debug-log-tools') : \esc_html__('No', 'debug-log-tools')
            )
        );
    }

    /**
     * Run system diagnostics.
     *
     * @return array Array of diagnostic results.
     */
    public function run_diagnostics() {
        $results = array();

        // Check WordPress version
        $results['wordpress_version'] = array(
            'status' => 'success',
            'message' => \get_bloginfo('version')
        );

        // Check PHP version
        $results['php_version'] = array(
            'status' => version_compare(PHP_VERSION, '7.4', '>=') ? 'success' : 'warning',
            'message' => PHP_VERSION
        );

        // Check wp-config.php
        $wp_config_path = ABSPATH . 'wp-config.php';
        $results['wp_config'] = $this->check_file_permissions($wp_config_path);

        // Check debug log file
        $debug_log_path = \WP_CONTENT_DIR . '/debug.log';
        $results['debug_log'] = $this->check_file_permissions($debug_log_path);

        // Check plugin directory
        $plugin_dir = \plugin_dir_path(dirname(__FILE__));
        $results['plugin_dir'] = $this->check_file_permissions($plugin_dir);

        // Check active plugins
        $active_plugins = \get_option('active_plugins');
        $results['active_plugins'] = array(
            'status' => 'success',
            'message' => count($active_plugins) . ' plugins active'
        );

        // Check memory limits
        $memory_limit = ini_get('memory_limit');
        $results['memory_limit'] = array(
            'status' => 'success',
            'message' => $memory_limit
        );

        // Check max execution time
        $max_execution_time = ini_get('max_execution_time');
        $results['max_execution_time'] = array(
            'status' => 'success',
            'message' => $max_execution_time . ' seconds'
        );

        return $results;
    }

    /**
     * Render troubleshoot page
     */
    public function render_troubleshoot_page() {
        if (!\current_user_can('manage_options')) {
            return;
        }

        $system_info = self::get_system_info();
        $issues = self::check_common_issues();
        $active_plugins = self::get_active_plugins();
        ?>
        <div class="wrap debug-log-tools-troubleshoot">
            <h2><?php \esc_html_e('Troubleshooting Information', 'debug-log-tools'); ?></h2>

            <div class="troubleshoot-section">
                <h3><?php \esc_html_e('System Information', 'debug-log-tools'); ?></h3>
                <table class="widefat" style="margin-bottom: 20px;">
                    <tbody>
                        <?php foreach ($system_info as $key => $value): ?>
                            <tr>
                                <td style="width: 30%;">
                                    <strong><?php echo \esc_html(ucwords(str_replace('_', ' ', $key))); ?></strong>
                                </td>
                                <td><?php echo \esc_html($value); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($issues)): ?>
            <div class="troubleshoot-section">
                <h3><?php \esc_html_e('Detected Issues', 'debug-log-tools'); ?></h3>
                <div class="notice-list">
                    <?php foreach ($issues as $issue): ?>
                        <div class="notice notice-<?php echo \esc_attr($issue['type']); ?> inline">
                            <p><?php echo \esc_html($issue['message']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="troubleshoot-section">
                <h3><?php \esc_html_e('Active Plugins', 'debug-log-tools'); ?></h3>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php \esc_html_e('Plugin Name', 'debug-log-tools'); ?></th>
                            <th><?php \esc_html_e('Version', 'debug-log-tools'); ?></th>
                            <th><?php \esc_html_e('Author', 'debug-log-tools'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($active_plugins as $plugin): ?>
                            <tr>
                                <td><?php echo \esc_html($plugin['name']); ?></td>
                                <td><?php echo \esc_html($plugin['version']); ?></td>
                                <td><?php echo \esc_html($plugin['author']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <style>
                .debug-log-tools-troubleshoot .troubleshoot-section {
                    margin-bottom: 30px;
                }
                .debug-log-tools-troubleshoot .notice-list {
                    margin: 20px 0;
                }
                .debug-log-tools-troubleshoot .notice {
                    margin: 5px 0;
                }
                .debug-log-tools-troubleshoot table {
                    background: #fff;
                    border-radius: 4px;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                }
                .debug-log-tools-troubleshoot th,
                .debug-log-tools-troubleshoot td {
                    padding: 12px 15px;
                }
                @media (prefers-color-scheme: dark) {
                    .debug-log-tools-troubleshoot table {
                        background: #2c3338;
                        color: #e2e4e7;
                    }
                    .debug-log-tools-troubleshoot th {
                        color: #e2e4e7;
                    }
                }
            </style>
        </div>
        <?php
    }
} 