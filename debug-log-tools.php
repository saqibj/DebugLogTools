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
 * Version:     3.1.0
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
define('DEBUG_LOG_TOOLS_VERSION', '3.1.0');
define('DEBUG_LOG_TOOLS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DEBUG_LOG_TOOLS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once plugin_dir_path( __FILE__ ) . 'includes/modules/class-base-module.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-module-loader.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-debug-log-manager.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-troubleshoot-tools.php';

// Initialize Module Loader
$module_loader = new Module_Loader();

// Initialize Debug Log Manager
new Debug_Log_Manager();

/**
 * Enqueue admin scripts and styles
 */
function debug_log_tools_enqueue_assets() {
    $version = DEBUG_LOG_TOOLS_VERSION;
    
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
    if (!file_exists($log_file) && Debug_Log_Manager::is_debug_enabled()) {
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
    $system_info = Troubleshoot_Tools::get_system_info();
    $issues = Troubleshoot_Tools::check_common_issues();
    $active_plugins = Troubleshoot_Tools::get_active_plugins();
    ?>
    <div class="debug-log-tools-troubleshoot">
        <h3><?php esc_html_e('System Information', 'debug-log-tools'); ?></h3>
        <table class="widefat striped">
            <?php foreach ($system_info as $key => $value): ?>
                <tr>
                    <td><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></strong></td>
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
    $debug_enabled = Debug_Log_Manager::is_debug_enabled();
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
                <input type="submit" class="button button-primary" value="<?php esc_attr_e('Save Changes', 'debug-log-tools'); ?>">
            </div>
        </form>

        <?php if (!$debug_enabled): ?>
            <div class="notice notice-warning">
                <p><?php esc_html_e('Debug logging is currently disabled. Enable it to start logging.', 'debug-log-tools'); ?></p>
            </div>
        <?php elseif (!$log_exists): ?>
            <div class="notice notice-info">
                <p><?php esc_html_e('Debug logging is enabled but no log file exists yet. The file will be created when the first error occurs.', 'debug-log-tools'); ?></p>
                <p><?php esc_html_e('To test logging, try visiting a non-existent page on your site.', 'debug-log-tools'); ?></p>
            </div>
        <?php else: ?>
            <div class="debug-log-tools-controls">
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <input type="hidden" name="action" value="debug_log_tools_flush">
                    <?php wp_nonce_field('flush_debug_log', 'debug_log_tools_nonce'); ?>
                    <input type="submit" class="button" value="<?php esc_attr_e('Flush Log', 'debug-log-tools'); ?>">
                </form>
                <input type="text" id="debug-log-search" class="regular-text" 
                       placeholder="<?php esc_attr_e('Search log...', 'debug-log-tools'); ?>">
            </div>
            
            <div class="debug-log-tools-content">
                <pre><?php echo esc_html($log_content); ?></pre>
            </div>
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

// Add AJAX handler
add_action('wp_ajax_debug_log_tools_refresh', 'debug_log_tools_ajax_refresh');

// Register activation hook
register_activation_hook(__FILE__, 'debug_log_tools_activate');

/**
 * Plugin activation callback
 */
function debug_log_tools_activate() {
    // Check WordPress version
    if (version_compare(get_bloginfo('version'), '5.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Debug Log Tools requires WordPress 5.0 or higher.', 'debug-log-tools'));
    }

    // Check PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Debug Log Tools requires PHP 7.4 or higher.', 'debug-log-tools'));
    }

    // Create log file if it doesn't exist and debug is enabled
    if (Debug_Log_Manager::is_debug_enabled()) {
        $log_file = WP_CONTENT_DIR . '/debug.log';
        if (!file_exists($log_file) && is_writable(WP_CONTENT_DIR)) {
            @touch($log_file);
            @chmod($log_file, 0644);
        }
    }
}
