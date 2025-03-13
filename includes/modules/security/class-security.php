<?php
/**
 * Debug Log Security Module
 *
 * @package DebugLogTools
 */

namespace DebugLogTools\Modules;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Security
 * Handles security features and monitoring
 */
class Security extends Base_Module {
    /**
     * Security events storage
     *
     * @var array
     */
    private $events = array();

    /**
     * Module initialization
     */
    public function init() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('wp_ajax_debug_log_security_data', array($this, 'get_security_data'));

        // Monitor file access attempts
        add_action('debug_log_file_access', array($this, 'monitor_file_access'), 10, 2);
        
        // Monitor authentication attempts
        add_action('wp_login_failed', array($this, 'log_failed_login'));
        add_action('wp_login', array($this, 'log_successful_login'), 10, 2);
        
        // Monitor file modifications
        add_action('debug_log_file_modified', array($this, 'monitor_file_modification'), 10, 2);

        // Monitor plugin and theme changes
        add_action('activated_plugin', array($this, 'log_plugin_activation'));
        add_action('deactivated_plugin', array($this, 'log_plugin_deactivation'));
        add_action('switch_theme', array($this, 'log_theme_switch'));

        // Monitor core, plugin, and theme updates
        add_action('upgrader_process_complete', array($this, 'log_update'), 10, 2);

        // Monitor user management
        add_action('user_register', array($this, 'log_user_creation'));
        add_action('delete_user', array($this, 'log_user_deletion'));
        add_action('profile_update', array($this, 'log_user_update'));
        add_action('set_user_role', array($this, 'log_role_change'), 10, 3);

        // Monitor security-related options
        add_action('updated_option', array($this, 'monitor_option_changes'), 10, 3);
    }

    /**
     * Register security settings
     */
    public function register_settings() {
        register_setting(
            'debug_log_security',
            'debug_log_security_settings',
            array(
                'type' => 'array',
                'description' => 'Debug Log Security Settings',
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'default' => array(
                    'monitor_file_access' => true,
                    'monitor_login_attempts' => true,
                    'monitor_file_changes' => true,
                    'monitor_plugin_theme_changes' => true,
                    'monitor_updates' => true,
                    'monitor_user_management' => true,
                    'monitor_options' => true,
                    'alert_threshold' => 5,
                    'alert_period' => 300,
                    'ip_blacklist' => array(),
                    'path_blacklist' => array(),
                    'monitored_options' => array(
                        'siteurl',
                        'home',
                        'admin_email',
                        'users_can_register',
                        'default_role',
                        'WPLANG',
                        'permalink_structure',
                    ),
                ),
            )
        );
    }

    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'debug-log-tools',
            __('Security Monitoring', 'debug-log-tools'),
            __('Security', 'debug-log-tools'),
            'manage_options',
            'debug-log-security',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = get_option('debug_log_security_settings');
        include_once plugin_dir_path(__FILE__) . 'templates/security-page.php';
    }

    /**
     * Monitor file access attempts
     *
     * @param string $file    File path.
     * @param string $context Access context.
     */
    public function monitor_file_access($file, $context) {
        $settings = get_option('debug_log_security_settings');
        
        if (!$settings['monitor_file_access']) {
            return;
        }

        $ip = $this->get_client_ip();
        
        // Check if IP is blacklisted
        if (in_array($ip, $settings['ip_blacklist'], true)) {
            $this->log_security_event('blocked_access', sprintf(
                'Blocked file access attempt from blacklisted IP %s to %s',
                $ip,
                $file
            ));
            return;
        }

        // Check if path is blacklisted
        foreach ($settings['path_blacklist'] as $pattern) {
            if (fnmatch($pattern, $file)) {
                $this->log_security_event('blocked_access', sprintf(
                    'Blocked access attempt to blacklisted path %s from %s',
                    $file,
                    $ip
                ));
                return;
            }
        }

        $this->log_security_event('file_access', sprintf(
            'File access attempt: %s (Context: %s) from IP: %s',
            $file,
            $context,
            $ip
        ));
    }

    /**
     * Log failed login attempts
     *
     * @param string $username Username.
     */
    public function log_failed_login($username) {
        $settings = get_option('debug_log_security_settings');
        
        if (!$settings['monitor_login_attempts']) {
            return;
        }

        $ip = $this->get_client_ip();
        
        $this->log_security_event('failed_login', sprintf(
            'Failed login attempt for user "%s" from IP: %s',
            $username,
            $ip
        ));

        // Check for brute force attempts
        $recent_failures = $this->get_recent_events('failed_login', $settings['alert_period']);
        if (count($recent_failures) >= $settings['alert_threshold']) {
            $this->trigger_security_alert('brute_force', sprintf(
                'Possible brute force attack detected. %d failed login attempts in %d seconds from IP: %s',
                count($recent_failures),
                $settings['alert_period'],
                $ip
            ));
        }
    }

    /**
     * Log successful logins
     *
     * @param string  $username Username.
     * @param WP_User $user     User object.
     */
    public function log_successful_login($username, $user) {
        $settings = get_option('debug_log_security_settings');
        
        if (!$settings['monitor_login_attempts']) {
            return;
        }

        $this->log_security_event('successful_login', sprintf(
            'Successful login for user "%s" (ID: %d) from IP: %s',
            $username,
            $user->ID,
            $this->get_client_ip()
        ));
    }

    /**
     * Monitor file modifications
     *
     * @param string $file    File path.
     * @param string $context Modification context.
     */
    public function monitor_file_modification($file, $context) {
        $settings = get_option('debug_log_security_settings');
        
        if (!$settings['monitor_file_changes']) {
            return;
        }

        $this->log_security_event('file_modified', sprintf(
            'File modified: %s (Context: %s)',
            $file,
            $context
        ));
    }

    /**
     * Log plugin activation
     *
     * @param string $plugin Plugin path.
     */
    public function log_plugin_activation($plugin) {
        $settings = get_option('debug_log_security_settings');
        
        if (!$settings['monitor_plugin_theme_changes']) {
            return;
        }

        $this->log_security_event('plugin_activated', sprintf(
            'Plugin activated: %s by user ID: %d',
            $plugin,
            get_current_user_id()
        ));
    }

    /**
     * Log plugin deactivation
     *
     * @param string $plugin Plugin path.
     */
    public function log_plugin_deactivation($plugin) {
        $settings = get_option('debug_log_security_settings');
        
        if (!$settings['monitor_plugin_theme_changes']) {
            return;
        }

        $this->log_security_event('plugin_deactivated', sprintf(
            'Plugin deactivated: %s by user ID: %d',
            $plugin,
            get_current_user_id()
        ));
    }

    /**
     * Log theme switch
     *
     * @param string $new_name New theme name.
     */
    public function log_theme_switch($new_name) {
        $settings = get_option('debug_log_security_settings');
        
        if (!$settings['monitor_plugin_theme_changes']) {
            return;
        }

        $this->log_security_event('theme_switched', sprintf(
            'Theme switched to: %s by user ID: %d',
            $new_name,
            get_current_user_id()
        ));
    }

    /**
     * Log updates
     *
     * @param object $upgrader Upgrader object.
     * @param array  $options  Update options.
     */
    public function log_update($upgrader, $options) {
        $settings = get_option('debug_log_security_settings');
        
        if (!$settings['monitor_updates']) {
            return;
        }

        $type = $options['type'] ?? 'unknown';
        $action = $options['action'] ?? 'unknown';

        if ($action !== 'update') {
            return;
        }

        switch ($type) {
            case 'core':
                $this->log_security_event('core_updated', sprintf(
                    'WordPress core updated to version %s',
                    $upgrader->result['version'] ?? 'unknown'
                ));
                break;

            case 'plugin':
                $plugins = $options['plugins'] ?? array();
                foreach ($plugins as $plugin) {
                    $this->log_security_event('plugin_updated', sprintf(
                        'Plugin updated: %s',
                        $plugin
                    ));
                }
                break;

            case 'theme':
                $themes = $options['themes'] ?? array();
                foreach ($themes as $theme) {
                    $this->log_security_event('theme_updated', sprintf(
                        'Theme updated: %s',
                        $theme
                    ));
                }
                break;
        }
    }

    /**
     * Log user creation
     *
     * @param int $user_id User ID.
     */
    public function log_user_creation($user_id) {
        $settings = get_option('debug_log_security_settings');
        
        if (!$settings['monitor_user_management']) {
            return;
        }

        $user = get_userdata($user_id);
        $this->log_security_event('user_created', sprintf(
            'New user created: %s (ID: %d) by user ID: %d',
            $user->user_login,
            $user_id,
            get_current_user_id()
        ));
    }

    /**
     * Log user deletion
     *
     * @param int $user_id User ID.
     */
    public function log_user_deletion($user_id) {
        $settings = get_option('debug_log_security_settings');
        
        if (!$settings['monitor_user_management']) {
            return;
        }

        $user = get_userdata($user_id);
        $this->log_security_event('user_deleted', sprintf(
            'User deleted: %s (ID: %d) by user ID: %d',
            $user->user_login,
            $user_id,
            get_current_user_id()
        ));
    }

    /**
     * Log user profile update
     *
     * @param int $user_id User ID.
     */
    public function log_user_update($user_id) {
        $settings = get_option('debug_log_security_settings');
        
        if (!$settings['monitor_user_management']) {
            return;
        }

        $user = get_userdata($user_id);
        $this->log_security_event('user_updated', sprintf(
            'User profile updated: %s (ID: %d) by user ID: %d',
            $user->user_login,
            $user_id,
            get_current_user_id()
        ));
    }

    /**
     * Log user role change
     *
     * @param int    $user_id   User ID.
     * @param string $role      New role.
     * @param array  $old_roles Old roles.
     */
    public function log_role_change($user_id, $role, $old_roles) {
        $settings = get_option('debug_log_security_settings');
        
        if (!$settings['monitor_user_management']) {
            return;
        }

        $user = get_userdata($user_id);
        $this->log_security_event('role_changed', sprintf(
            'User role changed for %s (ID: %d) from %s to %s by user ID: %d',
            $user->user_login,
            $user_id,
            implode(', ', $old_roles),
            $role,
            get_current_user_id()
        ));
    }

    /**
     * Monitor changes to security-related options
     *
     * @param string $option    Option name.
     * @param mixed  $old_value Old value.
     * @param mixed  $value     New value.
     */
    public function monitor_option_changes($option, $old_value, $value) {
        $settings = get_option('debug_log_security_settings');
        
        if (!$settings['monitor_options']) {
            return;
        }

        if (!in_array($option, $settings['monitored_options'], true)) {
            return;
        }

        $this->log_security_event('option_changed', sprintf(
            'WordPress option "%s" changed from "%s" to "%s" by user ID: %d',
            $option,
            is_array($old_value) ? json_encode($old_value) : $old_value,
            is_array($value) ? json_encode($value) : $value,
            get_current_user_id()
        ));
    }

    /**
     * Log a security event
     *
     * @param string $type    Event type.
     * @param string $message Event message.
     */
    private function log_security_event($type, $message) {
        $event = array(
            'type' => $type,
            'message' => $message,
            'timestamp' => current_time('mysql'),
            'ip' => $this->get_client_ip(),
        );

        $this->events[] = $event;
        
        // Store in WordPress options for persistence
        $stored_events = get_option('debug_log_security_events', array());
        array_unshift($stored_events, $event);
        
        // Keep only the last 1000 events
        if (count($stored_events) > 1000) {
            array_pop($stored_events);
        }
        
        update_option('debug_log_security_events', $stored_events);

        // Log to debug.log
        error_log(sprintf(
            '[Security Event] %s: %s',
            ucfirst($type),
            $message
        ));
    }

    /**
     * Trigger a security alert
     *
     * @param string $type    Alert type.
     * @param string $message Alert message.
     */
    private function trigger_security_alert($type, $message) {
        $alert = array(
            'type' => $type,
            'message' => $message,
            'timestamp' => current_time('mysql'),
        );

        // Store alert
        $alerts = get_option('debug_log_security_alerts', array());
        array_unshift($alerts, $alert);
        
        // Keep only the last 100 alerts
        if (count($alerts) > 100) {
            array_pop($alerts);
        }
        
        update_option('debug_log_security_alerts', $alerts);

        // Log to debug.log
        error_log(sprintf(
            '[Security Alert] %s: %s',
            ucfirst($type),
            $message
        ));

        // Trigger action for other modules (e.g., notifications)
        do_action('debug_log_security_alert', $type, $message);
    }

    /**
     * Get recent security events
     *
     * @param string $type      Event type.
     * @param int    $timeframe Timeframe in seconds.
     * @return array Recent events
     */
    private function get_recent_events($type, $timeframe) {
        $events = get_option('debug_log_security_events', array());
        $current_time = current_time('timestamp');
        
        return array_filter($events, function($event) use ($type, $timeframe, $current_time) {
            return $event['type'] === $type &&
                   (strtotime($event['timestamp']) > ($current_time - $timeframe));
        });
    }

    /**
     * Get client IP address
     *
     * @return string IP address
     */
    private function get_client_ip() {
        $ip = '';
        
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }
        
        return $ip;
    }

    /**
     * Get security data for AJAX requests
     */
    public function get_security_data() {
        check_ajax_referer('debug_log_security_data');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $data = array(
            'events' => get_option('debug_log_security_events', array()),
            'alerts' => get_option('debug_log_security_alerts', array()),
        );

        wp_send_json_success($data);
    }

    /**
     * Sanitize settings
     *
     * @param array $input Input array.
     * @return array Sanitized settings
     */
    public function sanitize_settings($input) {
        return array(
            'monitor_file_access' => isset($input['monitor_file_access']),
            'monitor_login_attempts' => isset($input['monitor_login_attempts']),
            'monitor_file_changes' => isset($input['monitor_file_changes']),
            'monitor_plugin_theme_changes' => isset($input['monitor_plugin_theme_changes']),
            'monitor_updates' => isset($input['monitor_updates']),
            'monitor_user_management' => isset($input['monitor_user_management']),
            'monitor_options' => isset($input['monitor_options']),
            'alert_threshold' => absint($input['alert_threshold']),
            'alert_period' => absint($input['alert_period']),
            'ip_blacklist' => array_filter(array_map('sanitize_text_field', explode("\n", $input['ip_blacklist']))),
            'path_blacklist' => array_filter(array_map('sanitize_text_field', explode("\n", $input['path_blacklist']))),
            'monitored_options' => array_filter(array_map('sanitize_text_field', (array) $input['monitored_options'])),
        );
    }
} 