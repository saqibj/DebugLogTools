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
 * Version:     3.1.2
 * Author:      Saqib Jawaid
 * Author URI:  https://github.com/saqibj
 * Text Domain: debug-log-tools
 * License:     GPL-3.0
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Requires at least: 5.0
 * Requires PHP: 7.4
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('DEBUG_LOG_TOOLS_VERSION', '3.1.2');
define('DEBUG_LOG_TOOLS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DEBUG_LOG_TOOLS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DEBUG_LOG_TOOLS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Direct includes for core classes
require_once DEBUG_LOG_TOOLS_PLUGIN_DIR . 'includes/class-module-loader.php';
require_once DEBUG_LOG_TOOLS_PLUGIN_DIR . 'includes/class-debug-log-manager.php';
require_once DEBUG_LOG_TOOLS_PLUGIN_DIR . 'includes/class-troubleshoot-tools.php';
require_once DEBUG_LOG_TOOLS_PLUGIN_DIR . 'includes/modules/class-base-module.php';

// Autoloader for module classes
spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'DebugLogTools\\Modules\\';
    $base_dir = DEBUG_LOG_TOOLS_PLUGIN_DIR . 'includes/modules/';

    // Check if the class uses the namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Extract module name
    $words = explode('\\', $relative_class);
    $class_name = end($words);
    
    // Convert class name to file name (e.g., Debugging => class-debugging.php)
    $file_name = 'class-' . strtolower($class_name) . '.php';
    
    // For module classes, use modules/name/class-name.php format
    // Example: DebugLogTools\Modules\Debugging => includes/modules/debugging/class-debugging.php
    if (count($words) === 1) {
        $module_name = strtolower($class_name);
        $file = $base_dir . $module_name . '/' . $file_name;
        
        // Log the attempt
        error_log("Debug Log Tools: Attempting to load module class from: " . $file);
        
        if (file_exists($file)) {
            require_once $file;
        } else {
            error_log("Debug Log Tools: Module file not found: " . $file);
        }
    }
});

/**
 * Initialize the plugin.
 */
function debug_log_tools_init() {
    // Load text domain
    load_plugin_textdomain('debug-log-tools', false, dirname(DEBUG_LOG_TOOLS_PLUGIN_BASENAME) . '/languages');

    // Initialize core classes
    try {
        $module_loader = new \DebugLogTools\Module_Loader();
        $module_loader->init();

        $debug_log_manager = new \DebugLogTools\Debug_Log_Manager();
        $debug_log_manager->init();

        $troubleshoot_tools = new \DebugLogTools\Troubleshoot_Tools();
        $troubleshoot_tools->init();
    } catch (\Exception $e) {
        error_log('Debug Log Tools initialization error: ' . $e->getMessage());
    }
}
add_action('plugins_loaded', 'debug_log_tools_init');

/**
 * Activation hook.
 */
function debug_log_tools_activate() {
    // Check WordPress version
    if (version_compare(get_bloginfo('version'), '5.0', '<')) {
        deactivate_plugins(DEBUG_LOG_TOOLS_PLUGIN_BASENAME);
        wp_die(
            esc_html__('Debug Log Tools requires WordPress version 5.0 or higher.', 'debug-log-tools'),
            'Plugin Activation Error',
            array('back_link' => true)
        );
    }

    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        deactivate_plugins(DEBUG_LOG_TOOLS_PLUGIN_BASENAME);
        wp_die(
            esc_html__('Debug Log Tools requires PHP version 7.4 or higher.', 'debug-log-tools'),
            'Plugin Activation Error',
            array('back_link' => true)
        );
    }

    // Create log directory if it doesn't exist
    $log_dir = WP_CONTENT_DIR . '/debug-logs';
    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
    }

    // Set default options
    $default_options = array(
        'debug_log_tools_version' => DEBUG_LOG_TOOLS_VERSION,
        'debug_log_tools_active_modules' => array('debugging', 'notifications', 'performance', 'security'),
        'debug_log_tools_settings' => array(
            'notifications' => array(
                'enabled' => true,
                'email' => get_option('admin_email'),
                'threshold' => 100
            ),
            'performance' => array(
                'enabled' => true,
                'slow_query_threshold' => 1.0,
                'memory_threshold' => '256M'
            ),
            'security' => array(
                'enabled' => true,
                'monitor_login_attempts' => true,
                'monitor_file_changes' => true
            )
        )
    );

    foreach ($default_options as $option => $value) {
        if (false === get_option($option)) {
            add_option($option, $value);
        }
    }

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'debug_log_tools_activate');

/**
 * Deactivation hook.
 */
function debug_log_tools_deactivate() {
    // Clear scheduled hooks
    wp_clear_scheduled_hook('debug_log_tools_cleanup');
    wp_clear_scheduled_hook('debug_log_tools_security_scan');
    wp_clear_scheduled_hook('debug_log_tools_performance_check');

    // Clear transients
    delete_transient('debug_log_tools_notifications');
    delete_transient('debug_log_tools_performance_data');
    delete_transient('debug_log_tools_security_events');

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'debug_log_tools_deactivate');

/**
 * Enqueue plugin assets.
 */
function debug_log_tools_enqueue_assets() {
    $version = DEBUG_LOG_TOOLS_VERSION;
    $screen = get_current_screen();
    
    if (!$screen || 'toplevel_page_debug-log-tools' !== $screen->id) {
        return;
    }

    // Core plugin assets
    wp_enqueue_style(
        'debug-log-tools-admin',
        DEBUG_LOG_TOOLS_PLUGIN_URL . 'assets/css/admin.css',
        array(),
        $version
    );

    wp_enqueue_script(
        'debug-log-tools-admin',
        DEBUG_LOG_TOOLS_PLUGIN_URL . 'assets/js/admin.js',
        array('jquery'),
        $version,
        true
    );
    
    wp_localize_script(
        'debug-log-tools-admin',
        'debugLogTools',
        array(
            'nonce'   => wp_create_nonce('debug_log_tools_refresh'),
            'ajaxurl' => admin_url('admin-ajax.php')
        )
    );

    // Module-specific assets
    if (isset($_GET['tab'])) {
        $current_tab = sanitize_text_field($_GET['tab']);
        
        switch ($current_tab) {
            case 'debugging':
                wp_enqueue_style(
                    'debug-log-tools-debugging',
                    DEBUG_LOG_TOOLS_PLUGIN_URL . 'includes/modules/debugging/css/debugging.css',
                    array(),
                    $version
                );
                wp_enqueue_script(
                    'debug-log-tools-debugging',
                    DEBUG_LOG_TOOLS_PLUGIN_URL . 'includes/modules/debugging/js/debugging.js',
                    array('jquery', 'debug-log-tools-admin'),
                    $version,
                    true
                );
                break;

            case 'security':
                wp_enqueue_style(
                    'debug-log-tools-security',
                    DEBUG_LOG_TOOLS_PLUGIN_URL . 'includes/modules/security/css/security.css',
                    array(),
                    $version
                );
                wp_enqueue_script(
                    'debug-log-tools-security',
                    DEBUG_LOG_TOOLS_PLUGIN_URL . 'includes/modules/security/js/security.js',
                    array('jquery', 'debug-log-tools-admin'),
                    $version,
                    true
                );
                break;
        }
    }
}
add_action('admin_enqueue_scripts', 'debug_log_tools_enqueue_assets');

/**
 * Initialize scheduled tasks.
 */
function debug_log_tools_init_scheduled_tasks() {
    // Schedule daily cleanup
    if (!wp_next_scheduled('debug_log_tools_cleanup')) {
        wp_schedule_event(time(), 'daily', 'debug_log_tools_cleanup');
    }

    // Schedule hourly security scan
    if (!wp_next_scheduled('debug_log_tools_security_scan')) {
        wp_schedule_event(time(), 'hourly', 'debug_log_tools_security_scan');
    }

    // Schedule performance check every 15 minutes
    if (!wp_next_scheduled('debug_log_tools_performance_check')) {
        wp_schedule_event(time(), 'debug_log_tools_15min', 'debug_log_tools_performance_check');
    }
}
add_action('init', 'debug_log_tools_init_scheduled_tasks');

/**
 * Add custom cron schedule.
 */
function debug_log_tools_add_cron_schedule($schedules) {
    $schedules['debug_log_tools_15min'] = array(
        'interval' => 15 * MINUTE_IN_SECONDS,
        'display' => esc_html__('Every 15 Minutes', 'debug-log-tools')
    );
    return $schedules;
}
add_filter('cron_schedules', 'debug_log_tools_add_cron_schedule');

/**
 * Handle cleanup task.
 */
function debug_log_tools_cleanup() {
    // Clean up old log entries
    $log_dir = WP_CONTENT_DIR . '/debug-logs';
    if (is_dir($log_dir)) {
        $files = glob($log_dir . '/*.log');
        $now = time();
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= 30 * DAY_IN_SECONDS) {
                    unlink($file);
                }
            }
        }
    }

    // Clean up expired transients
    global $wpdb;
    $wpdb->query(
        "DELETE FROM $wpdb->options 
        WHERE option_name LIKE '_transient_debug_log_tools_%' 
        AND option_name NOT LIKE '_transient_timeout_debug_log_tools_%'"
    );
}
add_action('debug_log_tools_cleanup', 'debug_log_tools_cleanup');

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
        $contents = debug_log_tools_get_log_contents($log_file);
        if ( false === $contents ) {
            wp_send_json_error( esc_html__( 'Error reading log file.', 'debug-log-tools' ) );
        }
        wp_send_json_success( esc_html( $contents ) );
    }
    wp_send_json_error( esc_html__( 'Log file not found.', 'debug-log-tools' ) );
}

function debug_log_tools_get_log_contents($log_file) {
    // Try to create log file if it doesn't exist and debug is enabled
    if (!file_exists($log_file)) {
        $wp_content_dir = dirname($log_file);
        if (is_writable($wp_content_dir)) {
            @touch($log_file);
            @chmod($log_file, 0644);
        }
    }

    if (!file_exists($log_file)) {
        return '';
    }

    $size = filesize($log_file);
    $max_size = 1024 * 1024; // 1MB

    $handle = fopen($log_file, 'r');
    if ($size > $max_size) {
        fseek($handle, -$max_size, SEEK_END);
        // Skip first incomplete line
        fgets($handle);
    }
    $contents = fread($handle, $max_size);
    fclose($handle);
    
    return $contents;
}

// Display Debug Log
function debug_log_tools_display() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'log';
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Debug Log Tools', 'debug-log-tools'); ?></h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="?page=debug-log-tools&tab=log" 
               class="nav-tab <?php echo $active_tab == 'log' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Debug Log', 'debug-log-tools'); ?>
            </a>
            <a href="?page=debug-log-tools&tab=troubleshoot" 
               class="nav-tab <?php echo $active_tab == 'troubleshoot' ? 'nav-tab-active' : ''; ?>">
                <?php esc_html_e('Troubleshoot', 'debug-log-tools'); ?>
            </a>
        </h2>

        <?php
        if ($active_tab == 'troubleshoot') {
            debug_log_tools_display_troubleshoot();
        } else {
            debug_log_tools_display_log();
        }
        ?>
    </div>
    <?php
}

function debug_log_tools_display_troubleshoot() {
    $system_info = \DebugLogTools\Troubleshoot_Tools::get_system_info();
    $issues = \DebugLogTools\Troubleshoot_Tools::check_common_issues();
    $active_plugins = \DebugLogTools\Troubleshoot_Tools::get_active_plugins();
    ?>
    <div class="debug-log-tools-troubleshoot">
        <h3><?php esc_html_e('System Information', 'debug-log-tools'); ?></h3>
        <table class="widefat striped">
            <?php foreach ($system_info as $key => $value): ?>
                <tr>
                    <td><strong><?php echo esc_html($key); ?></strong></td>
                    <td><?php echo esc_html($value); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h3><?php esc_html_e('Active Plugins', 'debug-log-tools'); ?></h3>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Plugin', 'debug-log-tools'); ?></th>
                    <th><?php esc_html_e('Version', 'debug-log-tools'); ?></th>
                    <th><?php esc_html_e('Author', 'debug-log-tools'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($active_plugins as $plugin): ?>
                    <tr>
                        <td><?php echo esc_html($plugin['name']); ?></td>
                        <td><?php echo esc_html($plugin['version']); ?></td>
                        <td><?php echo esc_html($plugin['author']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!empty($issues)): ?>
            <h3><?php esc_html_e('Detected Issues', 'debug-log-tools'); ?></h3>
            <div class="debug-log-tools-issues">
                <?php foreach ($issues as $issue): ?>
                    <div class="notice notice-<?php echo esc_attr($issue['type']); ?>">
                        <p><?php echo esc_html($issue['message']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

// Add this function after debug_log_tools_display_troubleshoot()
function debug_log_tools_display_log() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $log_file = WP_CONTENT_DIR . '/debug.log';
    $log_exists = file_exists($log_file);
    $log_content = debug_log_tools_get_log_contents($log_file);
    $debug_enabled = \DebugLogTools\Debug_Log_Manager::is_debug_enabled();
    ?>
    <div class="debug-log-tools-wrap">
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="debug_log_tools_toggle">
            <?php wp_nonce_field('toggle_debug_log', 'debug_log_tools_nonce'); ?>
            <div class="debug-log-tools-controls">
                <label>
                    <input type="checkbox" name="enable_debug_log" <?php checked($debug_enabled); ?>>
                    <?php esc_html_e('Enable Debug Logging', 'debug-log-tools'); ?>
                </label>
                <button type="submit" class="button">
                    <?php echo $debug_enabled 
                        ? esc_html__('Disable Debug Log', 'debug-log-tools') 
                        : esc_html__('Enable Debug Log', 'debug-log-tools'); ?>
                </button>
                <?php if ($log_exists): ?>
                    <a href="<?php echo esc_url(admin_url('admin-post.php?action=debug_log_tools_clear&_wpnonce=' . wp_create_nonce('clear_debug_log'))); ?>" class="button">
                        <?php esc_html_e('Clear Log', 'debug-log-tools'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin-post.php?action=debug_log_tools_download&_wpnonce=' . wp_create_nonce('download_debug_log'))); ?>" class="button">
                        <?php esc_html_e('Download Log', 'debug-log-tools'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($log_exists && !empty($log_content)): ?>
            <div class="debug-log-tools-content">
                <div class="debug-log-tools-filters">
                    <input type="text" id="debug-log-search" placeholder="<?php esc_attr_e('Search in log...', 'debug-log-tools'); ?>" class="regular-text">
                    <select id="debug-log-level">
                        <option value="all"><?php esc_html_e('All Levels', 'debug-log-tools'); ?></option>
                        <option value="error"><?php esc_html_e('Errors', 'debug-log-tools'); ?></option>
                        <option value="warning"><?php esc_html_e('Warnings', 'debug-log-tools'); ?></option>
                        <option value="notice"><?php esc_html_e('Notices', 'debug-log-tools'); ?></option>
                    </select>
                </div>
                <pre id="debug-log-content" class="debug-log-tools-log"><?php echo esc_html($log_content); ?></pre>
            </div>
        <?php elseif ($debug_enabled && !$log_exists): ?>
            <p class="debug-log-empty">
                <?php esc_html_e('Debug logging is enabled, but the log file does not exist yet. It will be created when WordPress logs its first debug message.', 'debug-log-tools'); ?>
            </p>
        <?php elseif ($debug_enabled && empty($log_content)): ?>
            <p class="debug-log-empty">
                <?php esc_html_e('Debug logging is enabled, but the log file is empty.', 'debug-log-tools'); ?>
            </p>
        <?php else: ?>
            <p class="debug-log-empty">
                <?php esc_html_e('Debug logging is currently disabled. Enable it to start capturing debug information.', 'debug-log-tools'); ?>
            </p>
        <?php endif; ?>
    </div>
    <?php
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

/**
 * Clear the debug log file
 */
function debug_log_tools_clear_log() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Unauthorized access', 'debug-log-tools'));
    }

    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'clear_debug_log')) {
        wp_die(__('Security check failed', 'debug-log-tools'));
    }

    $log_file = WP_CONTENT_DIR . '/debug.log';
    
    // Check if file exists
    if (!file_exists($log_file)) {
        wp_safe_redirect(admin_url('tools.php?page=debug-log-tools&clear_error=1'));
        exit;
    }
    
    // Check if file is writable
    if (!is_writable($log_file)) {
        wp_safe_redirect(admin_url('tools.php?page=debug-log-tools&clear_error=2'));
        exit;
    }
    
    // Try to clear the log
    $result = @file_put_contents($log_file, '');
    if ($result === false) {
        wp_safe_redirect(admin_url('tools.php?page=debug-log-tools&clear_error=3'));
        exit;
    }

    // Success
    wp_safe_redirect(admin_url('tools.php?page=debug-log-tools&cleared=1'));
    exit;
}
add_action('admin_post_debug_log_tools_clear', 'debug_log_tools_clear_log');

/**
 * Download the debug log file
 */
function debug_log_tools_download_log() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Unauthorized access', 'debug-log-tools'));
    }

    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'download_debug_log')) {
        wp_die(__('Security check failed', 'debug-log-tools'));
    }

    $log_file = WP_CONTENT_DIR . '/debug.log';
    if (!file_exists($log_file) || !is_readable($log_file)) {
        wp_safe_redirect(admin_url('tools.php?page=debug-log-tools&download_error=1'));
        exit;
    }

    // Disable any active plugins that might interfere with download
    $plugins_to_disable = array('wp-super-cache', 'w3-total-cache');
    foreach ($plugins_to_disable as $plugin) {
        if (function_exists('is_plugin_active') && is_plugin_active($plugin . '/' . $plugin . '.php')) {
            // Temporarily disable the plugin
            @define('DONOTCACHEPAGE', true);
        }
    }

    // Disable WordPress compression
    @ini_set('zlib.output_compression', 'Off');
    
    // End any previous output buffering
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Set headers for file download
    nocache_headers(); // This will prevent caching
    header('Content-Description: File Transfer');
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="debug-log-' . date('Y-m-d-H-i-s') . '.txt"');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($log_file));
    
    // Read the file and output its contents
    readfile($log_file);
    exit;
}
add_action('admin_post_debug_log_tools_download', 'debug_log_tools_download_log');

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
    
    if ( isset( $_GET['cleared'] ) && $_GET['cleared'] == '1' ) {
        echo '<div class="notice notice-success is-dismissible"><p>' . 
             esc_html__( 'Debug log cleared successfully.', 'debug-log-tools' ) . 
             '</p></div>';
    }
    
    if ( isset( $_GET['clear_error'] ) ) {
        $error_code = intval($_GET['clear_error']);
        $error_message = esc_html__( 'Error: Could not clear debug log.', 'debug-log-tools' );
        
        switch ( $error_code ) {
            case 1:
                $error_message = esc_html__( 'Error: Debug log file does not exist.', 'debug-log-tools' );
                break;
            case 2:
                $error_message = esc_html__( 'Error: Debug log file is not writable. Please check file permissions.', 'debug-log-tools' );
                break;
            case 3:
                $error_message = esc_html__( 'Error: Failed to write to debug log file.', 'debug-log-tools' );
                break;
        }
        
        echo '<div class="notice notice-error is-dismissible"><p>' . $error_message . '</p></div>';
    }
    
    if ( isset( $_GET['download_error'] ) && $_GET['download_error'] == '1' ) {
        echo '<div class="notice notice-error is-dismissible"><p>' . 
             esc_html__( 'Error: Debug log file does not exist or could not be accessed.', 'debug-log-tools' ) . 
             '</p></div>';
    }
}
add_action('admin_notices', 'debug_log_tools_admin_notices');

// Add AJAX handler
add_action('wp_ajax_debug_log_tools_refresh', 'debug_log_tools_ajax_refresh');

/**
 * Enqueue admin scripts and styles.
 */
function debug_log_tools_admin_enqueue_scripts($hook) {
    if ($hook === 'tools_page_debug-log-tools') {
        wp_enqueue_style(
            'debug-log-tools',
            DEBUG_LOG_TOOLS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            DEBUG_LOG_TOOLS_VERSION
        );
        
        wp_enqueue_script(
            'debug-log-tools',
            DEBUG_LOG_TOOLS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            DEBUG_LOG_TOOLS_VERSION,
            true
        );
    }
}
add_action('admin_enqueue_scripts', 'debug_log_tools_admin_enqueue_scripts');
