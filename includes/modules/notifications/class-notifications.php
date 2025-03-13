<?php
/**
 * Debug Log Notifications Module
 *
 * @package DebugLogTools
 */

namespace DebugLogTools\Modules;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Notifications
 * Handles notifications for debug log events and errors
 */
class Notifications extends Base_Module {
    /**
     * Module initialization
     */
    public function init() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('debug_log_tools_error_logged', array($this, 'process_notification'), 10, 2);
        add_action('wp_ajax_debug_log_test_notification', array($this, 'handle_test_notification'));
    }

    /**
     * Register notification settings
     */
    public function register_settings() {
        register_setting(
            'debug_log_notifications',
            'debug_log_notification_settings',
            array(
                'type' => 'array',
                'description' => 'Debug Log Notification Settings',
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'default' => array(
                    'email_notifications' => false,
                    'slack_notifications' => false,
                    'notification_threshold' => 'error',
                    'email_recipients' => get_option('admin_email'),
                    'slack_webhook_url' => '',
                    'notification_frequency' => 'immediate',
                    'batch_interval' => 3600,
                    'excluded_errors' => array(),
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
            __('Debug Log Notifications', 'debug-log-tools'),
            __('Notifications', 'debug-log-tools'),
            'manage_options',
            'debug-log-notifications',
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

        $settings = get_option('debug_log_notification_settings');
        include_once plugin_dir_path(__FILE__) . 'templates/notifications-settings.php';
    }

    /**
     * Sanitize settings before saving
     *
     * @param array $input The input array to sanitize.
     * @return array Sanitized settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();

        $sanitized['email_notifications'] = isset($input['email_notifications']) ? (bool) $input['email_notifications'] : false;
        $sanitized['slack_notifications'] = isset($input['slack_notifications']) ? (bool) $input['slack_notifications'] : false;
        
        $valid_thresholds = array('debug', 'info', 'warning', 'error', 'critical');
        $sanitized['notification_threshold'] = in_array($input['notification_threshold'], $valid_thresholds, true) 
            ? $input['notification_threshold'] 
            : 'error';

        $sanitized['email_recipients'] = array_filter(array_map('sanitize_email', explode(',', $input['email_recipients'])));
        $sanitized['slack_webhook_url'] = esc_url_raw($input['slack_webhook_url']);

        $valid_frequencies = array('immediate', 'hourly', 'daily');
        $sanitized['notification_frequency'] = in_array($input['notification_frequency'], $valid_frequencies, true)
            ? $input['notification_frequency']
            : 'immediate';

        $sanitized['batch_interval'] = absint($input['batch_interval']);
        $sanitized['excluded_errors'] = array_filter(array_map('sanitize_text_field', (array) $input['excluded_errors']));

        return $sanitized;
    }

    /**
     * Process notifications for logged errors
     *
     * @param string $message The error message.
     * @param string $level   The error level.
     */
    public function process_notification($message, $level) {
        $settings = get_option('debug_log_notification_settings');
        
        if (!$this->should_send_notification($message, $level, $settings)) {
            return;
        }

        if ($settings['notification_frequency'] === 'immediate') {
            $this->send_notifications($message, $level, $settings);
        } else {
            $this->queue_notification($message, $level);
        }
    }

    /**
     * Check if a notification should be sent
     *
     * @param string $message  The error message.
     * @param string $level    The error level.
     * @param array  $settings The notification settings.
     * @return bool Whether to send the notification
     */
    private function should_send_notification($message, $level, $settings) {
        $level_weights = array(
            'debug' => 0,
            'info' => 1,
            'warning' => 2,
            'error' => 3,
            'critical' => 4
        );

        // Check if the error level meets the threshold
        if ($level_weights[$level] < $level_weights[$settings['notification_threshold']]) {
            return false;
        }

        // Check if the error is excluded
        foreach ($settings['excluded_errors'] as $excluded_error) {
            if (strpos($message, $excluded_error) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Send notifications through configured channels
     *
     * @param string $message  The error message.
     * @param string $level    The error level.
     * @param array  $settings The notification settings.
     */
    private function send_notifications($message, $level, $settings) {
        if ($settings['email_notifications']) {
            $this->send_email_notification($message, $level, $settings);
        }

        if ($settings['slack_notifications']) {
            $this->send_slack_notification($message, $level, $settings);
        }
    }

    /**
     * Send email notification
     *
     * @param string $message  The error message.
     * @param string $level    The error level.
     * @param array  $settings The notification settings.
     */
    private function send_email_notification($message, $level, $settings) {
        $site_name = get_bloginfo('name');
        $subject = sprintf('[%s] Debug Log %s: New Error Detected', $site_name, ucfirst($level));
        
        $body = sprintf(
            "A new %s level error has been detected in the debug log:\n\n%s\n\nSite: %s\nTime: %s",
            ucfirst($level),
            $message,
            get_site_url(),
            current_time('mysql')
        );

        foreach ($settings['email_recipients'] as $recipient) {
            wp_mail($recipient, $subject, $body);
        }
    }

    /**
     * Send Slack notification
     *
     * @param string $message  The error message.
     * @param string $level    The error level.
     * @param array  $settings The notification settings.
     */
    private function send_slack_notification($message, $level, $settings) {
        if (empty($settings['slack_webhook_url'])) {
            return;
        }

        $colors = array(
            'debug' => '#999999',
            'info' => '#3498db',
            'warning' => '#f1c40f',
            'error' => '#e74c3c',
            'critical' => '#c0392b'
        );

        $payload = array(
            'attachments' => array(
                array(
                    'color' => $colors[$level],
                    'title' => sprintf('New %s Level Error Detected', ucfirst($level)),
                    'text' => $message,
                    'fields' => array(
                        array(
                            'title' => 'Site',
                            'value' => get_site_url(),
                            'short' => true
                        ),
                        array(
                            'title' => 'Time',
                            'value' => current_time('mysql'),
                            'short' => true
                        )
                    ),
                    'footer' => 'Debug Log Tools',
                    'ts' => time()
                )
            )
        );

        wp_remote_post($settings['slack_webhook_url'], array(
            'body' => wp_json_encode($payload),
            'headers' => array('Content-Type' => 'application/json'),
            'timeout' => 30
        ));
    }

    /**
     * Queue a notification for batch processing
     *
     * @param string $message The error message.
     * @param string $level   The error level.
     */
    private function queue_notification($message, $level) {
        $queued_notifications = get_option('debug_log_queued_notifications', array());
        
        $queued_notifications[] = array(
            'message' => $message,
            'level' => $level,
            'timestamp' => time()
        );

        update_option('debug_log_queued_notifications', $queued_notifications);
    }

    /**
     * Process queued notifications
     */
    public function process_queued_notifications() {
        $settings = get_option('debug_log_notification_settings');
        $queued_notifications = get_option('debug_log_queued_notifications', array());

        if (empty($queued_notifications)) {
            return;
        }

        $notifications_by_level = array();
        foreach ($queued_notifications as $notification) {
            if (!isset($notifications_by_level[$notification['level']])) {
                $notifications_by_level[$notification['level']] = array();
            }
            $notifications_by_level[$notification['level']][] = $notification['message'];
        }

        foreach ($notifications_by_level as $level => $messages) {
            $summary = sprintf(
                "Summary of %d %s level errors:\n\n%s",
                count($messages),
                ucfirst($level),
                implode("\n\n", $messages)
            );

            $this->send_notifications($summary, $level, $settings);
        }

        delete_option('debug_log_queued_notifications');
    }

    /**
     * Handle test notification AJAX request
     */
    public function handle_test_notification() {
        check_ajax_referer('debug_log_test_notification');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $settings = get_option('debug_log_notification_settings');
        $test_message = 'This is a test notification from Debug Log Tools.';
        
        $this->send_notifications($test_message, 'info', $settings);
        
        wp_send_json_success('Test notification sent successfully');
    }
} 