<?php
/**
 * Template for the troubleshoot page
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
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