<?php
/**
 * Template for notifications settings page
 *
 * @package DebugLogTools
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

// Ensure $settings is set
if (!isset($settings) || !is_array($settings)) {
    $settings = get_option('debug_log_notification_settings', array());
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Debug Log Notifications Settings', 'debug-log-tools'); ?></h1>

    <form method="post" action="options.php">
        <?php
        settings_fields('debug_log_notifications');
        do_settings_sections('debug_log_notifications');
        ?>

        <div class="notification-settings-container">
            <h2><?php esc_html_e('Notification Channels', 'debug-log-tools'); ?></h2>
            
            <!-- Email Notifications -->
            <div class="notification-section">
                <h3><?php esc_html_e('Email Notifications', 'debug-log-tools'); ?></h3>
                <label>
                    <input type="checkbox" 
                           name="debug_log_notification_settings[email_notifications]" 
                           value="1" 
                           <?php checked(isset($settings['email_notifications']) && $settings['email_notifications']); ?>>
                    <?php esc_html_e('Enable email notifications', 'debug-log-tools'); ?>
                </label>

                <div class="notification-field">
                    <label for="email_recipients">
                        <?php esc_html_e('Email Recipients', 'debug-log-tools'); ?>
                    </label>
                    <input type="text" 
                           id="email_recipients" 
                           name="debug_log_notification_settings[email_recipients]" 
                           value="<?php echo esc_attr(is_array($settings['email_recipients']) ? implode(',', $settings['email_recipients']) : $settings['email_recipients']); ?>" 
                           class="regular-text">
                    <p class="description">
                        <?php esc_html_e('Comma-separated list of email addresses', 'debug-log-tools'); ?>
                    </p>
                </div>
            </div>

            <!-- Slack Notifications -->
            <div class="notification-section">
                <h3><?php esc_html_e('Slack Notifications', 'debug-log-tools'); ?></h3>
                <label>
                    <input type="checkbox" 
                           name="debug_log_notification_settings[slack_notifications]" 
                           value="1" 
                           <?php checked(isset($settings['slack_notifications']) && $settings['slack_notifications']); ?>>
                    <?php esc_html_e('Enable Slack notifications', 'debug-log-tools'); ?>
                </label>

                <div class="notification-field">
                    <label for="slack_webhook_url">
                        <?php esc_html_e('Slack Webhook URL', 'debug-log-tools'); ?>
                    </label>
                    <input type="url" 
                           id="slack_webhook_url" 
                           name="debug_log_notification_settings[slack_webhook_url]" 
                           value="<?php echo esc_url($settings['slack_webhook_url']); ?>" 
                           class="regular-text">
                    <p class="description">
                        <?php esc_html_e('Enter your Slack incoming webhook URL', 'debug-log-tools'); ?>
                    </p>
                </div>
            </div>

            <!-- Notification Settings -->
            <h2><?php esc_html_e('Notification Settings', 'debug-log-tools'); ?></h2>
            
            <div class="notification-section">
                <div class="notification-field">
                    <label for="notification_threshold">
                        <?php esc_html_e('Notification Threshold', 'debug-log-tools'); ?>
                    </label>
                    <select id="notification_threshold" 
                            name="debug_log_notification_settings[notification_threshold]">
                        <?php
                        $thresholds = array(
                            'debug' => __('Debug', 'debug-log-tools'),
                            'info' => __('Info', 'debug-log-tools'),
                            'warning' => __('Warning', 'debug-log-tools'),
                            'error' => __('Error', 'debug-log-tools'),
                            'critical' => __('Critical', 'debug-log-tools')
                        );
                        foreach ($thresholds as $value => $label) :
                            ?>
                            <option value="<?php echo esc_attr($value); ?>" 
                                    <?php selected($settings['notification_threshold'], $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e('Only send notifications for errors at or above this level', 'debug-log-tools'); ?>
                    </p>
                </div>

                <div class="notification-field">
                    <label for="notification_frequency">
                        <?php esc_html_e('Notification Frequency', 'debug-log-tools'); ?>
                    </label>
                    <select id="notification_frequency" 
                            name="debug_log_notification_settings[notification_frequency]">
                        <?php
                        $frequencies = array(
                            'immediate' => __('Immediate', 'debug-log-tools'),
                            'hourly' => __('Hourly Summary', 'debug-log-tools'),
                            'daily' => __('Daily Summary', 'debug-log-tools')
                        );
                        foreach ($frequencies as $value => $label) :
                            ?>
                            <option value="<?php echo esc_attr($value); ?>" 
                                    <?php selected($settings['notification_frequency'], $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="notification-field">
                    <label for="batch_interval">
                        <?php esc_html_e('Batch Interval (seconds)', 'debug-log-tools'); ?>
                    </label>
                    <input type="number" 
                           id="batch_interval" 
                           name="debug_log_notification_settings[batch_interval]" 
                           value="<?php echo esc_attr($settings['batch_interval']); ?>" 
                           min="300" 
                           step="300">
                    <p class="description">
                        <?php esc_html_e('Minimum 5 minutes (300 seconds)', 'debug-log-tools'); ?>
                    </p>
                </div>
            </div>

            <!-- Error Exclusions -->
            <h2><?php esc_html_e('Error Exclusions', 'debug-log-tools'); ?></h2>
            
            <div class="notification-section">
                <div class="notification-field">
                    <label for="excluded_errors">
                        <?php esc_html_e('Excluded Error Patterns', 'debug-log-tools'); ?>
                    </label>
                    <textarea id="excluded_errors" 
                              name="debug_log_notification_settings[excluded_errors]" 
                              rows="5" 
                              class="large-text"><?php echo esc_textarea(implode("\n", (array) $settings['excluded_errors'])); ?></textarea>
                    <p class="description">
                        <?php esc_html_e('Enter one pattern per line. Notifications will not be sent for errors matching these patterns.', 'debug-log-tools'); ?>
                    </p>
                </div>
            </div>
        </div>

        <?php submit_button(); ?>
    </form>

    <!-- Test Notifications -->
    <div class="notification-test-section">
        <h2><?php esc_html_e('Test Notifications', 'debug-log-tools'); ?></h2>
        <p><?php esc_html_e('Send a test notification to verify your settings:', 'debug-log-tools'); ?></p>
        
        <button type="button" 
                id="test-notification" 
                class="button button-secondary" 
                data-nonce="<?php echo esc_attr(wp_create_nonce('debug_log_test_notification')); ?>">
            <?php esc_html_e('Send Test Notification', 'debug-log-tools'); ?>
        </button>
        
        <div id="test-notification-result" class="notice" style="display: none;"></div>
    </div>
</div>

<style>
.notification-settings-container {
    max-width: 800px;
    margin-top: 20px;
}

.notification-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.notification-field {
    margin: 15px 0;
}

.notification-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.notification-field .description {
    margin-top: 5px;
    color: #666;
}

.notification-test-section {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #ccd0d4;
}

#test-notification-result {
    margin-top: 15px;
}

#test-notification-result.notice-success {
    border-left-color: #46b450;
}

#test-notification-result.notice-error {
    border-left-color: #dc3232;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#test-notification').on('click', function() {
        const button = $(this);
        const resultDiv = $('#test-notification-result');
        
        button.prop('disabled', true);
        resultDiv.removeClass('notice-success notice-error').hide();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'debug_log_test_notification',
                nonce: button.data('nonce')
            },
            success: function(response) {
                if (response.success) {
                    resultDiv
                        .addClass('notice-success')
                        .html('<p>' + response.data + '</p>')
                        .show();
                } else {
                    resultDiv
                        .addClass('notice-error')
                        .html('<p>' + response.data + '</p>')
                        .show();
                }
            },
            error: function() {
                resultDiv
                    .addClass('notice-error')
                    .html('<p><?php echo esc_js(__('An error occurred while sending the test notification.', 'debug-log-tools')); ?></p>')
                    .show();
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
});
</script> 