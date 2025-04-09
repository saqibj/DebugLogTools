<?php
/**
 * Plugin Name: Debug Log Tools
 * Plugin URI: https://wordpress.org/plugins/debug-log-tools/
 * Description: Advanced tools for managing and analyzing WordPress debug logs.
 * Version: 3.2.5
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * Author: Saqib Jawaid
 * Author URI: https://yourwebsite.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: debug-log-tools
 * Domain Path: /languages
 *
 * @package DebugLogTools
 * @author  Saqib Jawaid
 * @version 3.2.5
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    if (file_exists(dirname(__DIR__) . '/wp-load.php')) {
        require_once dirname(__DIR__) . '/wp-load.php';
    } else {
        exit('WordPress not found');
    }
}

// Ensure WordPress is loaded
if (!function_exists('add_action')) {
    exit('WordPress core functions not available');
}

// Define plugin constants
define('DEBUG_LOG_TOOLS_VERSION', '3.2.5');
define('DEBUG_LOG_TOOLS_PLUGIN_FILE', __FILE__);
define('DEBUG_LOG_TOOLS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DEBUG_LOG_TOOLS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DEBUG_LOG_TOOLS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Load plugin dependencies
if (!file_exists(ABSPATH . 'wp-admin/includes/plugin.php')) {
    exit('Required WordPress files not found');
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
    // Base namespace for the plugin
    $namespace = 'DebugLogTools\\';
    
    // If the class doesn't belong to our namespace, skip it
    if (strpos($class, $namespace) !== 0) {
        return;
    }
    
    // Get the relative class path
    $relative_class = substr($class, strlen($namespace));
    
    // Handle module classes
    if (strpos($relative_class, 'Modules\\') === 0) {
        // Remove 'Modules\' from path
        $module_path = str_replace('Modules\\', '', $relative_class);
        
        // Handle base module class
        if ($module_path === 'Base_Module') {
            $file = DEBUG_LOG_TOOLS_PLUGIN_DIR . 'includes/modules/class-base-module.php';
        } else {
            // Convert module class name to file path
            $module_parts = explode('\\', $module_path);
            $class_name = array_pop($module_parts);
            $module_dir = strtolower(implode('/', $module_parts));
            
            if (!empty($module_dir)) {
                $module_dir .= '/';
            }
            
            $file = DEBUG_LOG_TOOLS_PLUGIN_DIR . 'includes/modules/' . $module_dir . 
                    'class-' . str_replace('_', '-', strtolower($class_name)) . '.php';
        }
    } else {
        // Handle regular classes
        $file = DEBUG_LOG_TOOLS_PLUGIN_DIR . 'includes/class-' . 
                str_replace('_', '-', strtolower($relative_class)) . '.php';
    }
    
    // If file exists, require it
    if (file_exists($file)) {
        require_once $file;
    } else {
        error_log(sprintf(
            'Debug Log Tools: Class file not found: %s (Class: %s)',
            $file,
            $class
        ));
    }
});

/**
 * Initialize the plugin.
 */
function debug_log_tools_init() {
    try {
        // Initialize core classes
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

/**
 * Load plugin textdomain.
 */
function debug_log_tools_load_textdomain() {
    load_plugin_textdomain(
        'debug-log-tools',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}

// Hook into WordPress
add_action('init', 'debug_log_tools_load_textdomain');
add_action('plugins_loaded', 'debug_log_tools_init', 20);

/**
 * Plugin activation handler.
 */
function debug_log_tools_activate() {
    // Version checks
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
    $log_dir = trailingslashit(WP_CONTENT_DIR) . 'debug-logs';
    if (!file_exists($log_dir)) {
        wp_mkdir_p($log_dir);
    }

    // Set default options
    $default_options = array(
        'debug_log_tools_version' => DEBUG_LOG_TOOLS_VERSION,
        'debug_log_tools_active_modules' => array(
            'debugging',
            'notifications',
            'performance',
            'security'
        ),
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
 * Deactivation hook handler.
 */
function debug_log_tools_deactivate() {
    // Clear scheduled hooks
    wp_clear_scheduled_hook('debug_log_tools/cleanup');
    wp_clear_scheduled_hook('debug_log_tools/security_scan');
    
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
            'ajaxurl' => admin_url('admin-ajax.php'),
            'i18n'    => array(
                'confirmFlush' => __('Are you sure you want to flush the debug log? This will permanently remove all log entries and cannot be undone.', 'debug-log-tools'),
                'confirmClearFiltered' => __('Are you sure you want to clear the filtered log entries? This will permanently remove all currently filtered entries and cannot be undone.', 'debug-log-tools'),
                'clearFilteredSuccess' => __('Filtered log entries have been cleared successfully.', 'debug-log-tools'),
                'clearFilteredError' => __('Error: Failed to clear filtered log entries.', 'debug-log-tools'),
                'loadingError' => __('Error: Failed to load log entries.', 'debug-log-tools'),
                'savingError' => __('Error: Failed to save changes.', 'debug-log-tools'),
                'refreshing' => __('Refreshing log...', 'debug-log-tools'),
                'noEntries' => __('No log entries found.', 'debug-log-tools')
            )
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
    if (!wp_next_scheduled('debug_log_tools/cleanup')) {
        wp_schedule_event(time(), 'daily', 'debug_log_tools/cleanup');
    }

    // Schedule hourly security scan
    if (!wp_next_scheduled('debug_log_tools/security_scan')) {
        wp_schedule_event(time(), 'hourly', 'debug_log_tools/security_scan');
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
 * Handles the debug log toggle action.
 */
function debug_log_tools_handle_toggle() {
    // Check nonce and user capabilities
    if (!isset($_POST['debug_log_tools_nonce']) || !wp_verify_nonce($_POST['debug_log_tools_nonce'], 'debug_log_tools_toggle')) {
        wp_nonce_ays('debug_log_tools_toggle');
    }
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to perform this action.', 'debug-log-tools'));
    }

    $enable_debug = isset($_POST['enable_debug_log']);
    $redirect_url = admin_url('tools.php?page=debug-log-tools');

    try {
        $debug_log_manager = new \DebugLogTools\Debug_Log_Manager();
        $debug_log_manager->toggle_debug($enable_debug);
        wp_safe_redirect(add_query_arg('debug_updated', $enable_debug ? '1' : '0', $redirect_url));
    } catch (\Exception $e) {
        wp_safe_redirect(add_query_arg('error', '1', $redirect_url));
    }
    exit;
}
add_action('admin_post_debug_log_tools_toggle', 'debug_log_tools_handle_toggle');

/**
 * AJAX callback to get new lines from the debug log file for live tailing.
 */
function debug_log_tools_get_live_log_callback() {
    check_ajax_referer('debug_log_tools_live_log_nonce', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => esc_html__('You do not have sufficient permissions to perform this action.', 'debug-log-tools')]);
    }

    $log_manager = new \DebugLogTools\Debug_Log_Manager();
    $log_file_path = $log_manager->get_log_file_path();

    $last_size = isset($_POST['last_size']) ? intval($_POST['last_size']) : 0;

    try {
        $new_lines = $log_manager->get_new_log_lines($log_file_path, $last_size);
        wp_send_json_success(['lines' => $new_lines, 'current_size' => filesize($log_file_path)]);
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
add_action('wp_ajax_debug_log_tools_get_live_log', 'debug_log_tools_get_live_log_callback');

/**
 * Handle log rotation task.
 */
function debug_log_tools_perform_log_rotation() {
    $log_manager = new \DebugLogTools\Debug_Log_Manager();
    $log_manager->rotate_log_file();
}
add_action('debug_log_tools_daily_log_rotation', 'debug_log_tools_perform_log_rotation');

/**
 * Handle cleanup task.
 */
function debug_log_tools_cleanup() {
    // Clean up old log entries
    $log_dir = trailingslashit(WP_CONTENT_DIR) . 'debug-logs';
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
add_action('debug_log_tools/cleanup', 'debug_log_tools_cleanup');

/**
 * Main plugin class to handle all functionality
 */
class Debug_Log_Tools_Admin {
    /**
     * The single instance of the class.
     *
     * @var Debug_Log_Tools_Admin
     */
    private static $instance = null;

    /**
     * Main Debug_Log_Tools_Admin Instance.
     *
     * Ensures only one instance of Debug_Log_Tools_Admin is loaded or can be loaded.
     *
     * @return Debug_Log_Tools_Admin - Main instance.
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Debug_Log_Tools_Admin Constructor.
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('admin_notices', array($this, 'admin_notices'));
    }

    /**
     * Display admin notices
     */
    public function admin_notices() {
        if (isset($_GET['debug_updated'])) {
            $status = $_GET['debug_updated'] === '1' ? 'enabled' : 'disabled';
            $message = sprintf(
                /* translators: %s: debug status */
                esc_html__('Debug logging has been %s. You may need to reload the page to see the changes.', 'debug-log-tools'),
                $status
            );
            echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
        }

        if (isset($_GET['flushed']) && $_GET['flushed'] == '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . 
                 esc_html__('Debug log flushed successfully. All log entries have been permanently removed.', 'debug-log-tools') . 
                 '</p></div>';
        }
        
        if (isset($_GET['cleared']) && $_GET['cleared'] == '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . 
                 esc_html__('Debug log cleared successfully.', 'debug-log-tools') . 
                 '</p></div>';
        }
        
        if (isset($_GET['clear_error'])) {
            $error_code = intval($_GET['clear_error']);
            $error_message = esc_html__('Error: Could not clear debug log.', 'debug-log-tools');
            
            switch ($error_code) {
                case 1:
                    $error_message = esc_html__('Error: Debug log file does not exist.', 'debug-log-tools');
                    break;
                case 2:
                    $error_message = esc_html__('Error: Debug log file is not writable. Please check file permissions.', 'debug-log-tools');
                    break;
                case 3:
                    $error_message = esc_html__('Error: Failed to write to debug log file.', 'debug-log-tools');
                    break;
            }
            
            echo '<div class="notice notice-error is-dismissible"><p>' . $error_message . '</p></div>';
        }
        
        if (isset($_GET['download_error']) && $_GET['download_error'] == '1') {
            echo '<div class="notice notice-error is-dismissible"><p>' . 
                 esc_html__('Error: Debug log file does not exist or could not be accessed.', 'debug-log-tools') . 
                 '</p></div>';
        }

        if (isset($_GET['error']) && $_GET['error'] == '1') {
            echo '<div class="notice notice-error is-dismissible"><p>' . 
                 esc_html__('Error: An unexpected error occurred while processing your request.', 'debug-log-tools') . 
                 '</p></div>';
        }
    }
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    Debug_Log_Tools_Admin::get_instance();
});

// Add proper cleanup of all plugin data
register_uninstall_hook(__FILE__, 'debug_log_tools_uninstall');

function debug_log_tools_uninstall() {
    if (!defined('WP_UNINSTALL_PLUGIN')) {
        exit;
    }
    
    // Clean up all options with a single prefix
    global $wpdb;
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            'debug_log_tools_%'
        )
    );
    
    // Clean up any custom tables if created
    // Clean up any custom post types if created
    // Clean up any custom taxonomies if created
    
    // Remove log directory
    $log_dir = trailingslashit(WP_CONTENT_DIR) . 'debug-logs';
    if (is_dir($log_dir)) {
        array_map('unlink', glob("$log_dir/*.*"));
        rmdir($log_dir);
    }
}

/**
 * Add main menu and submenu items
 */
function debug_log_tools_add_menu() {
    // Add main menu
    add_menu_page(
        __('Debug Log Tools', 'debug-log-tools'),
        __('Debug Log Tools', 'debug-log-tools'),
        'manage_options',
        'debug-log-tools',
        'debug_log_tools_render_main_page',
        'dashicons-warning',
        80
    );

    // Add submenu items - Note: First submenu must match parent
    add_submenu_page(
        'debug-log-tools',
        __('Debug Log', 'debug-log-tools'),
        __('Debug Log', 'debug-log-tools'),
        'manage_options',
        'debug-log-tools',
        'debug_log_tools_render_main_page'
    );

    add_submenu_page(
        'debug-log-tools',
        __('Settings', 'debug-log-tools'),
        __('Settings', 'debug-log-tools'),
        'manage_options',
        'debug-log-settings',
        'debug_log_tools_render_settings_page'
    );
}
add_action('admin_menu', 'debug_log_tools_add_menu');

/**
 * Render main page
 */
function debug_log_tools_render_main_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Debug Log Tools', 'debug-log-tools'); ?></h1>
        <div class="debug-log-tools-content">
            <?php
            // Get the current tab
            $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'debug-log';
            
            // Display tabs
            ?>
            <h2 class="nav-tab-wrapper debug-log-tools-tabs">
                <a href="?page=debug-log-tools&tab=debug-log" class="nav-tab <?php echo $current_tab === 'debug-log' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Debug Log', 'debug-log-tools'); ?>
                </a>
                <a href="?page=debug-log-tools&tab=troubleshoot" class="nav-tab <?php echo $current_tab === 'troubleshoot' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Troubleshoot', 'debug-log-tools'); ?>
                </a>
            </h2>
            
            <style>
                .debug-log-tools-tabs {
                    margin: 20px 0;
                    padding-bottom: 0;
                    border-bottom: 1px solid #c3c4c7;
                }
                
                .debug-log-tools-tabs .nav-tab {
                    margin-left: 0;
                    margin-right: 5px;
                    padding: 10px 20px;
                    font-size: 14px;
                    font-weight: 500;
                    background: #f0f0f1;
                    border: 1px solid #c3c4c7;
                    border-bottom: none;
                    color: #50575e;
                }

                .debug-log-tools-tabs .nav-tab:hover,
                .debug-log-tools-tabs .nav-tab:focus {
                    background: #fff;
                    color: #135e96;
                    border-color: #135e96;
                    box-shadow: none;
                }

                .debug-log-tools-tabs .nav-tab-active,
                .debug-log-tools-tabs .nav-tab-active:hover,
                .debug-log-tools-tabs .nav-tab-active:focus {
                    background: #fff;
                    color: #1d2327;
                    border-bottom: 1px solid #fff;
                    margin-bottom: -1px;
                }

                @media (prefers-color-scheme: dark) {
                    .debug-log-tools-tabs {
                        border-bottom-color: #1d2327;
                    }

                    .debug-log-tools-tabs .nav-tab {
                        background: #2c3338;
                        border-color: #1d2327;
                        color: #bbc8d4;
                    }

                    .debug-log-tools-tabs .nav-tab:hover,
                    .debug-log-tools-tabs .nav-tab:focus {
                        background: #32373c;
                        color: #72aee6;
                        border-color: #135e96;
                    }

                    .debug-log-tools-tabs .nav-tab-active,
                    .debug-log-tools-tabs .nav-tab-active:hover,
                    .debug-log-tools-tabs .nav-tab-active:focus {
                        background: #32373c;
                        color: #fff;
                        border-bottom-color: #32373c;
                    }
                }
            </style>
            
            <div class="tab-content">
                <?php
                switch ($current_tab) {
                    case 'debug-log':
                        $debug_log_manager = new \DebugLogTools\Debug_Log_Manager();
                        $debug_log_manager->render_log_page();
                        break;
                    case 'troubleshoot':
                        $troubleshoot_tools = new \DebugLogTools\Troubleshoot_Tools();
                        $troubleshoot_tools->render_troubleshoot_page();
                        break;
                }
                ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render settings page
 */
function debug_log_tools_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Debug Log Tools Settings', 'debug-log-tools'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('debug_log_tools_settings');
            do_settings_sections('debug_log_tools_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Add action handlers for log management
add_action('admin_post_debug_log_tools_clear', 'debug_log_tools_handle_clear_log');
add_action('admin_post_debug_log_tools_rotate', 'debug_log_tools_handle_rotate_log');

/**
 * Handle clear log action
 */
function debug_log_tools_handle_clear_log() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to perform this action.', 'debug-log-tools'));
    }

    if (!isset($_POST['debug_log_tools_nonce']) || !wp_verify_nonce($_POST['debug_log_tools_nonce'], 'debug_log_tools_action')) {
        wp_die(esc_html__('Security check failed.', 'debug-log-tools'));
    }

    try {
        $log_manager = new \DebugLogTools\Debug_Log_Manager();
        $log_file = $log_manager->get_log_file_path();
        
        if (file_exists($log_file)) {
            file_put_contents($log_file, '');
            wp_safe_redirect(add_query_arg('cleared', '1', admin_url('admin.php?page=debug-log-tools')));
        } else {
            wp_safe_redirect(add_query_arg('clear_error', '1', admin_url('admin.php?page=debug-log-tools')));
        }
    } catch (Exception $e) {
        wp_safe_redirect(add_query_arg('clear_error', '3', admin_url('admin.php?page=debug-log-tools')));
    }
    exit;
}

/**
 * Handle rotate log action
 */
function debug_log_tools_handle_rotate_log() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to perform this action.', 'debug-log-tools'));
    }

    if (!isset($_POST['debug_log_tools_nonce']) || !wp_verify_nonce($_POST['debug_log_tools_nonce'], 'debug_log_tools_action')) {
        wp_die(esc_html__('Security check failed.', 'debug-log-tools'));
    }

    try {
        $log_manager = new \DebugLogTools\Debug_Log_Manager();
        $log_manager->rotate_log_file();
        wp_safe_redirect(add_query_arg('rotated', '1', admin_url('admin.php?page=debug-log-tools')));
    } catch (Exception $e) {
        wp_safe_redirect(add_query_arg('rotate_error', '1', admin_url('admin.php?page=debug-log-tools')));
    }
    exit;
}

/**
 * Register plugin settings
 */
function debug_log_tools_register_settings() {
    register_setting(
        'debug_log_tools_settings',
        'debug_log_tools_settings',
        array(
            'type' => 'array',
            'description' => 'Debug Log Tools plugin settings',
            'sanitize_callback' => 'debug_log_tools_sanitize_settings',
            'default' => array(
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
        )
    );

    add_settings_section(
        'debug_log_tools_general_section',
        __('General Settings', 'debug-log-tools'),
        'debug_log_tools_general_section_callback',
        'debug_log_tools_settings'
    );

    add_settings_field(
        'debug_log_tools_notifications',
        __('Notifications', 'debug-log-tools'),
        'debug_log_tools_notifications_callback',
        'debug_log_tools_settings',
        'debug_log_tools_general_section'
    );

    add_settings_field(
        'debug_log_tools_performance',
        __('Performance', 'debug-log-tools'),
        'debug_log_tools_performance_callback',
        'debug_log_tools_settings',
        'debug_log_tools_general_section'
    );

    add_settings_field(
        'debug_log_tools_security',
        __('Security', 'debug-log-tools'),
        'debug_log_tools_security_callback',
        'debug_log_tools_settings',
        'debug_log_tools_general_section'
    );
}
add_action('admin_init', 'debug_log_tools_register_settings');

/**
 * Sanitize settings
 */
function debug_log_tools_sanitize_settings($input) {
    $sanitized = array();
    
    // Notifications settings
    if (isset($input['notifications'])) {
        $sanitized['notifications'] = array(
            'enabled' => (bool) ($input['notifications']['enabled'] ?? false),
            'email' => sanitize_email($input['notifications']['email'] ?? get_option('admin_email')),
            'threshold' => absint($input['notifications']['threshold'] ?? 100)
        );
    }

    // Performance settings
    if (isset($input['performance'])) {
        $sanitized['performance'] = array(
            'enabled' => (bool) ($input['performance']['enabled'] ?? false),
            'slow_query_threshold' => (float) ($input['performance']['slow_query_threshold'] ?? 1.0),
            'memory_threshold' => sanitize_text_field($input['performance']['memory_threshold'] ?? '256M')
        );
    }

    // Security settings
    if (isset($input['security'])) {
        $sanitized['security'] = array(
            'enabled' => (bool) ($input['security']['enabled'] ?? false),
            'monitor_login_attempts' => (bool) ($input['security']['monitor_login_attempts'] ?? false),
            'monitor_file_changes' => (bool) ($input['security']['monitor_file_changes'] ?? false)
        );
    }

    return $sanitized;
}

/**
 * General section callback
 */
function debug_log_tools_general_section_callback() {
    echo '<p>' . esc_html__('Configure general settings for Debug Log Tools.', 'debug-log-tools') . '</p>';
}

/**
 * Notifications field callback
 */
function debug_log_tools_notifications_callback() {
    $settings = get_option('debug_log_tools_settings');
    $notifications = $settings['notifications'] ?? array();
    ?>
    <fieldset>
        <label>
            <input type="checkbox" 
                   name="debug_log_tools_settings[notifications][enabled]" 
                   value="1" 
                   <?php checked(isset($notifications['enabled']) && $notifications['enabled']); ?>>
            <?php esc_html_e('Enable notifications', 'debug-log-tools'); ?>
        </label>
        <br>
        <label>
            <?php esc_html_e('Email:', 'debug-log-tools'); ?>
            <input type="email" 
                   name="debug_log_tools_settings[notifications][email]" 
                   value="<?php echo esc_attr($notifications['email'] ?? get_option('admin_email')); ?>"
                   class="regular-text">
        </label>
        <br>
        <label>
            <?php esc_html_e('Threshold:', 'debug-log-tools'); ?>
            <input type="number" 
                   name="debug_log_tools_settings[notifications][threshold]" 
                   value="<?php echo esc_attr($notifications['threshold'] ?? 100); ?>"
                   min="1" 
                   class="small-text">
        </label>
    </fieldset>
    <?php
}

/**
 * Performance field callback
 */
function debug_log_tools_performance_callback() {
    $settings = get_option('debug_log_tools_settings');
    $performance = $settings['performance'] ?? array();
    ?>
    <fieldset>
        <label>
            <input type="checkbox" 
                   name="debug_log_tools_settings[performance][enabled]" 
                   value="1" 
                   <?php checked(isset($performance['enabled']) && $performance['enabled']); ?>>
            <?php esc_html_e('Enable performance monitoring', 'debug-log-tools'); ?>
        </label>
        <br>
        <label>
            <?php esc_html_e('Slow query threshold (seconds):', 'debug-log-tools'); ?>
            <input type="number" 
                   name="debug_log_tools_settings[performance][slow_query_threshold]" 
                   value="<?php echo esc_attr($performance['slow_query_threshold'] ?? 1.0); ?>"
                   min="0.1" 
                   step="0.1" 
                   class="small-text">
        </label>
        <br>
        <label>
            <?php esc_html_e('Memory threshold:', 'debug-log-tools'); ?>
            <input type="text" 
                   name="debug_log_tools_settings[performance][memory_threshold]" 
                   value="<?php echo esc_attr($performance['memory_threshold'] ?? '256M'); ?>"
                   class="small-text">
        </label>
    </fieldset>
    <?php
}

/**
 * Security field callback
 */
function debug_log_tools_security_callback() {
    $settings = get_option('debug_log_tools_settings');
    $security = $settings['security'] ?? array();
    ?>
    <fieldset>
        <label>
            <input type="checkbox" 
                   name="debug_log_tools_settings[security][enabled]" 
                   value="1" 
                   <?php checked(isset($security['enabled']) && $security['enabled']); ?>>
            <?php esc_html_e('Enable security monitoring', 'debug-log-tools'); ?>
        </label>
        <br>
        <label>
            <input type="checkbox" 
                   name="debug_log_tools_settings[security][monitor_login_attempts]" 
                   value="1" 
                   <?php checked(isset($security['monitor_login_attempts']) && $security['monitor_login_attempts']); ?>>
            <?php esc_html_e('Monitor login attempts', 'debug-log-tools'); ?>
        </label>
        <br>
        <label>
            <input type="checkbox" 
                   name="debug_log_tools_settings[security][monitor_file_changes]" 
                   value="1" 
                   <?php checked(isset($security['monitor_file_changes']) && $security['monitor_file_changes']); ?>>
            <?php esc_html_e('Monitor file changes', 'debug-log-tools'); ?>
        </label>
    </fieldset>
    <?php
}