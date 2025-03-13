<?php
/**
 * Debugging Module
 *
 * @package DebugLogTools
 * @subpackage Modules
 */

namespace DebugLogTools\Modules;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Debugging
 */
class Debugging extends Base_Module {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Set module properties
        $this->module_name = 'Debugging';
        $this->module_description = 'View and manage WordPress debug logs';
    }

    /**
     * Initialize the module
     */
    public function init() {
        if (!$this->is_active()) {
            return;
        }

        // Add menu pages and hooks
        \add_action('admin_menu', array($this, 'add_menu_page'));
    }

    /**
     * Add menu page
     */
    public function add_menu_page() {
        \add_menu_page(
            \__('Debug Log', 'debug-log-tools'),
            \__('Debug Log', 'debug-log-tools'),
            'manage_options',
            'debug-log-view',
            array($this, 'render_log_page'),
            'dashicons-warning',
            80
        );
    }

    /**
     * Render log page
     */
    public function render_log_page() {
        if (!\current_user_can('manage_options')) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php echo \esc_html__('Debug Log Viewer', 'debug-log-tools'); ?></h1>
            
            <div class="debug-log-tools-content">
                <pre><?php echo \esc_html($this->get_log_contents()); ?></pre>
            </div>
        </div>
        <?php
    }

    /**
     * Get log contents
     *
     * @return string
     */
    private function get_log_contents() {
        $log_file = \WP_CONTENT_DIR . '/debug.log';
        
        if (!file_exists($log_file)) {
            return \esc_html__('Debug log file does not exist.', 'debug-log-tools');
        }

        if (!is_readable($log_file)) {
            return \esc_html__('Debug log file is not readable.', 'debug-log-tools');
        }

        $size = filesize($log_file);
        if ($size === 0) {
            return \esc_html__('Debug log file is empty.', 'debug-log-tools');
        }

        // Read the last 100KB of the file
        $max_size = 102400; // 100KB
        $handle = fopen($log_file, 'r');
        
        if ($size > $max_size) {
            fseek($handle, -$max_size, SEEK_END);
            // Skip first incomplete line
            fgets($handle);
        }
        
        $contents = '';
        while (!feof($handle)) {
            $contents .= fgets($handle);
        }
        
        fclose($handle);
        
        return $contents;
    }
} 