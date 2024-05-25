<?php
/*
Plugin Name: Debug Log Tools
Description: Tools for viewing and managing the WordPress debug log.
Version: 1.0
Author: Saqib Jawaid
*/

// Add menu item
function dlt_add_admin_menu() {
    add_management_page('Debug Log Tools', 'Debug Log Tools', 'manage_options', 'debug_log_tools', 'dlt_display_admin_page');
}
add_action('admin_menu', 'dlt_add_admin_menu');

// Admin page content
function dlt_display_admin_page() {
    ?>
    <div class="wrap">
        <h2>Debug Log Tools</h2>
        <div>
            <h3>Debug Log Viewer</h3>
            <p><a href="<?php echo esc_url( admin_url('tools.php?page=debug_log_tools&action=view_log') ); ?>" target="_blank">View Debug Log</a></p>
            <h3>Flush Debug Log</h3>
            <form method="post">
                <?php wp_nonce_field('flush_debug_log', '_wpnonce_flush_debug_log'); ?>
                <input type="hidden" name="action" value="flush_log">
                <input type="submit" value="Flush Debug Log" class="button" onclick="return confirm('Are you sure you want to flush the debug log? This action cannot be undone.');">
            </form>
        </div>
    </div>
    <?php
}

// Handle get or post actions
function dlt_handle_actions() {
    if (isset($_GET['action']) && $_GET['action'] === 'view_log') {
        $log_contents = file_get_contents(WP_CONTENT_DIR . '/debug.log');
        echo '<pre>' . esc_html($log_contents) . '</pre>';
        exit;
    }

    if (isset($_POST['action']) && $_POST['action'] === 'flush_log' && check_admin_referer('flush_debug_log', '_wpnonce_flush_debug_log')) {
        file_put_contents(WP_CONTENT_DIR . '/debug.log', '');
        add_action('admin_notices', function() {
            echo '<div class="updated"><p>Debug log has been successfully flushed.</p></div>';
        });
    }
}
add_action('admin_init', 'dlt_handle_actions');
