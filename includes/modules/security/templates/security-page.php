<?php
/**
 * Security Settings Template
 *
 * @package DebugLogTools
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('debug_log_security_settings');
?>

<div class="wrap">
    <h1><?php esc_html_e('Security Monitoring', 'debug-log-tools'); ?></h1>

    <div class="security-dashboard">
        <div class="security-stats">
            <h2><?php esc_html_e('Security Overview', 'debug-log-tools'); ?></h2>
            <div class="stats-grid">
                <?php
                $events = get_option('debug_log_security_events', array());
                $alerts = get_option('debug_log_security_alerts', array());
                $recent_events = array_filter($events, function($event) {
                    return strtotime($event['timestamp']) > (current_time('timestamp') - 86400);
                });
                ?>
                <div class="stat-box">
                    <h3><?php esc_html_e('24h Events', 'debug-log-tools'); ?></h3>
                    <span class="stat-number"><?php echo count($recent_events); ?></span>
                </div>
                <div class="stat-box">
                    <h3><?php esc_html_e('Total Events', 'debug-log-tools'); ?></h3>
                    <span class="stat-number"><?php echo count($events); ?></span>
                </div>
                <div class="stat-box">
                    <h3><?php esc_html_e('Active Alerts', 'debug-log-tools'); ?></h3>
                    <span class="stat-number"><?php echo count($alerts); ?></span>
                </div>
            </div>
        </div>

        <div class="security-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#settings" class="nav-tab nav-tab-active"><?php esc_html_e('Settings', 'debug-log-tools'); ?></a>
                <a href="#events" class="nav-tab"><?php esc_html_e('Events', 'debug-log-tools'); ?></a>
                <a href="#alerts" class="nav-tab"><?php esc_html_e('Alerts', 'debug-log-tools'); ?></a>
            </nav>

            <div class="tab-content">
                <div id="settings" class="tab-pane active">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('debug_log_security');
                        do_settings_sections('debug_log_security');
                        ?>

                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('Monitoring Features', 'debug-log-tools'); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="debug_log_security_settings[monitor_file_access]" value="1" <?php checked($settings['monitor_file_access']); ?>>
                                            <?php esc_html_e('Monitor file access attempts', 'debug-log-tools'); ?>
                                        </label><br>

                                        <label>
                                            <input type="checkbox" name="debug_log_security_settings[monitor_login_attempts]" value="1" <?php checked($settings['monitor_login_attempts']); ?>>
                                            <?php esc_html_e('Monitor login attempts', 'debug-log-tools'); ?>
                                        </label><br>

                                        <label>
                                            <input type="checkbox" name="debug_log_security_settings[monitor_file_changes]" value="1" <?php checked($settings['monitor_file_changes']); ?>>
                                            <?php esc_html_e('Monitor file modifications', 'debug-log-tools'); ?>
                                        </label><br>

                                        <label>
                                            <input type="checkbox" name="debug_log_security_settings[monitor_plugin_theme_changes]" value="1" <?php checked($settings['monitor_plugin_theme_changes']); ?>>
                                            <?php esc_html_e('Monitor plugin and theme changes', 'debug-log-tools'); ?>
                                        </label><br>

                                        <label>
                                            <input type="checkbox" name="debug_log_security_settings[monitor_updates]" value="1" <?php checked($settings['monitor_updates']); ?>>
                                            <?php esc_html_e('Monitor WordPress updates', 'debug-log-tools'); ?>
                                        </label><br>

                                        <label>
                                            <input type="checkbox" name="debug_log_security_settings[monitor_user_management]" value="1" <?php checked($settings['monitor_user_management']); ?>>
                                            <?php esc_html_e('Monitor user management', 'debug-log-tools'); ?>
                                        </label><br>

                                        <label>
                                            <input type="checkbox" name="debug_log_security_settings[monitor_options]" value="1" <?php checked($settings['monitor_options']); ?>>
                                            <?php esc_html_e('Monitor security-related options', 'debug-log-tools'); ?>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php esc_html_e('Alert Settings', 'debug-log-tools'); ?></th>
                                <td>
                                    <label>
                                        <?php esc_html_e('Alert threshold', 'debug-log-tools'); ?>
                                        <input type="number" name="debug_log_security_settings[alert_threshold]" value="<?php echo esc_attr($settings['alert_threshold']); ?>" min="1" class="small-text">
                                    </label>
                                    <p class="description"><?php esc_html_e('Number of events before triggering an alert', 'debug-log-tools'); ?></p>

                                    <label>
                                        <?php esc_html_e('Alert period (seconds)', 'debug-log-tools'); ?>
                                        <input type="number" name="debug_log_security_settings[alert_period]" value="<?php echo esc_attr($settings['alert_period']); ?>" min="1" class="small-text">
                                    </label>
                                    <p class="description"><?php esc_html_e('Time period for counting events (in seconds)', 'debug-log-tools'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php esc_html_e('IP Blacklist', 'debug-log-tools'); ?></th>
                                <td>
                                    <textarea name="debug_log_security_settings[ip_blacklist]" rows="5" cols="50" class="large-text code"><?php echo esc_textarea(implode("\n", $settings['ip_blacklist'])); ?></textarea>
                                    <p class="description"><?php esc_html_e('Enter one IP address per line', 'debug-log-tools'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php esc_html_e('Path Blacklist', 'debug-log-tools'); ?></th>
                                <td>
                                    <textarea name="debug_log_security_settings[path_blacklist]" rows="5" cols="50" class="large-text code"><?php echo esc_textarea(implode("\n", $settings['path_blacklist'])); ?></textarea>
                                    <p class="description"><?php esc_html_e('Enter one path pattern per line. Supports wildcards (*)', 'debug-log-tools'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php esc_html_e('Monitored Options', 'debug-log-tools'); ?></th>
                                <td>
                                    <textarea name="debug_log_security_settings[monitored_options]" rows="5" cols="50" class="large-text code"><?php echo esc_textarea(implode("\n", $settings['monitored_options'])); ?></textarea>
                                    <p class="description"><?php esc_html_e('Enter one option name per line', 'debug-log-tools'); ?></p>
                                </td>
                            </tr>
                        </table>

                        <?php submit_button(); ?>
                    </form>
                </div>

                <div id="events" class="tab-pane">
                    <div class="tablenav top">
                        <div class="alignleft actions">
                            <select id="event-type-filter">
                                <option value=""><?php esc_html_e('All Events', 'debug-log-tools'); ?></option>
                                <option value="file_access"><?php esc_html_e('File Access', 'debug-log-tools'); ?></option>
                                <option value="login"><?php esc_html_e('Login Attempts', 'debug-log-tools'); ?></option>
                                <option value="file_modified"><?php esc_html_e('File Modifications', 'debug-log-tools'); ?></option>
                                <option value="plugin"><?php esc_html_e('Plugin Changes', 'debug-log-tools'); ?></option>
                                <option value="theme"><?php esc_html_e('Theme Changes', 'debug-log-tools'); ?></option>
                                <option value="user"><?php esc_html_e('User Management', 'debug-log-tools'); ?></option>
                                <option value="option"><?php esc_html_e('Option Changes', 'debug-log-tools'); ?></option>
                            </select>
                            <button class="button" id="apply-event-filter"><?php esc_html_e('Filter', 'debug-log-tools'); ?></button>
                        </div>
                        <div class="alignright">
                            <button class="button" id="refresh-events"><?php esc_html_e('Refresh', 'debug-log-tools'); ?></button>
                        </div>
                    </div>

                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col"><?php esc_html_e('Time', 'debug-log-tools'); ?></th>
                                <th scope="col"><?php esc_html_e('Type', 'debug-log-tools'); ?></th>
                                <th scope="col"><?php esc_html_e('Message', 'debug-log-tools'); ?></th>
                                <th scope="col"><?php esc_html_e('IP', 'debug-log-tools'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="security-events">
                            <!-- Events will be loaded dynamically -->
                        </tbody>
                    </table>
                </div>

                <div id="alerts" class="tab-pane">
                    <div class="tablenav top">
                        <div class="alignright">
                            <button class="button" id="refresh-alerts"><?php esc_html_e('Refresh', 'debug-log-tools'); ?></button>
                        </div>
                    </div>

                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col"><?php esc_html_e('Time', 'debug-log-tools'); ?></th>
                                <th scope="col"><?php esc_html_e('Type', 'debug-log-tools'); ?></th>
                                <th scope="col"><?php esc_html_e('Message', 'debug-log-tools'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="security-alerts">
                            <!-- Alerts will be loaded dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div> 