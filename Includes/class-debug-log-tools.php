<?php
if (!defined('ABSPATH')) {
    exit;
}

class Debug_Log_Tools {

    public function init() {
        // Register admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // AJAX actions
        add_action('wp_ajax_dlt_get_log_entries', array($this, 'get_log_entries'));
        add_action('wp_ajax_dlt_flush_log', array($this, 'flush_log'));
    }

    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            'Debug Log Tools',
            'Debug Log Tools',
            'manage_options',
            'debug-log-tools',
            array($this, 'render_admin_page')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'tools_page_debug-log-tools') {
            return;
        }

        wp_enqueue_style('debug-log-tools-admin', DEBUG_LOG_TOOLS_URL . 'css/admin-style.css');
        wp_enqueue_script('debug-log-tools-admin', DEBUG_LOG_TOOLS_URL . 'js/admin-script.js', array('jquery'), null, true);

        wp_localize_script('debug-log-tools-admin', 'DebugLogTools', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('debug_log_tools_nonce'),
        ));
    }

    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Debug Log Tools', 'debug-log-tools'); ?></h1>
            <form id="debug-log-tools-form">
                <label for="dlt-plugin-filter"><?php esc_html_e('Select Plugin:', 'debug-log-tools'); ?></label>
                <select id="dlt-plugin-filter" name="plugin">
                    <option value=""><?php esc_html_e('All Plugins', 'debug-log-tools'); ?></option>
                    <?php
                    foreach (get_plugins() as $plugin_path => $plugin) {
                        echo '<option value="' . esc_attr($plugin_path) . '">' . esc_html($plugin['Name']) . '</option>';
                    }
                    ?>
                </select>
                <button type="button" id="dlt-view-log" class="button button-primary"><?php esc_html_e('View Log', 'debug-log-tools'); ?></button>
                <button type="button" id="dlt-flush-log" class="button button-secondary"><?php esc_html_e('Flush Log', 'debug-log-tools'); ?></button>
            </form>
            <div id="dlt-log-viewer"></div>
        </div>
        <?php
    }

    public function get_log_entries() {
        check_ajax_referer('debug_log_tools_nonce', 'nonce');

        $plugin = isset($_POST['plugin']) ? sanitize_text_field($_POST['plugin']) : '';

        $log_file = WP_CONTENT_DIR . '/debug.log';

        if (!file_exists($log_file)) {
            wp_send_json_error(array('message' => __('Debug log file does not exist.', 'debug-log-tools')));
        }

        $log_entries = file($log_file);

        if ($plugin) {
            $log_entries = array_filter($log_entries, function($entry) use ($plugin) {
                return strpos($entry, $plugin) !== false;
            });
        }

        wp_send_json_success(array('entries' => array_slice($log_entries, -500)));
    }

    public function flush_log() {
        check_ajax_referer('debug_log_tools_nonce', 'nonce');

        $log_file = WP_CONTENT_DIR . '/debug.log';

        if (file_exists($log_file)) {
            file_put_contents($log_file, '');
            wp_send_json_success(array('message' => __('Debug log flushed successfully.', 'debug-log-tools')));
        } else {
            wp_send_json_error(array('message' => __('Debug log file does not exist.', 'debug-log-tools')));
        }
    }
}
