<?php
/**
 * Template for the log viewer page
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
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
                <button type="button" class="button" id="clear-all-log">
                    <?php esc_html_e('Clear Log', 'debug-log-tools'); ?>
                </button>
                <button type="button" class="button button-secondary" id="clear-filtered-log" style="display: none;">
                    <?php esc_html_e('Clear Filtered Entries', 'debug-log-tools'); ?>
                </button>
                <a href="#" class="button" id="download-log">
                    <?php esc_html_e('Download Log', 'debug-log-tools'); ?>
                </a>
                <button type="button" class="button button-link-delete" id="flush-log-button">
                    <?php esc_html_e('Flush Log', 'debug-log-tools'); ?>
                </button>
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
                <select id="debug-log-plugin">
                    <option value="all"><?php esc_html_e('All Plugins', 'debug-log-tools'); ?></option>
                    <?php
                    // Get all active plugins
                    $active_plugins = get_option('active_plugins');
                    foreach ($active_plugins as $plugin) {
                        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
                        echo '<option value="' . esc_attr(dirname($plugin)) . '">' . 
                             esc_html($plugin_data['Name']) . '</option>';
                    }
                    ?>
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