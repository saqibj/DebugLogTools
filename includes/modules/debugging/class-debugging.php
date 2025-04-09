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
        if (function_exists('__')) {
            $this->module_name = \__('Debugging', 'debug-log-tools');
            $this->module_description = \__('View and manage WordPress debug logs', 'debug-log-tools');
        } else {
            $this->module_name = 'Debugging';
            $this->module_description = 'View and manage WordPress debug logs';
        }
    }

    /**
     * Initialize the module
     */
    public function init() {
        if (!$this->is_active()) {
            return;
        }

        // Add menu pages and hooks
        if (function_exists('add_action')) {
            \add_action('admin_menu', array($this, 'add_menu_page'));
        }
    }

    /**
     * Add menu page
     */
    public function add_menu_page() {
        if (!function_exists('add_submenu_page') || !function_exists('__')) {
            return;
        }

        \add_submenu_page(
            'debug-log-tools',  // Parent menu slug
            \__('Debug Log', 'debug-log-tools'),
            \__('Debug Log', 'debug-log-tools'),
            'manage_options',
            'debug-log-view',
            array($this, 'render_log_page')
        );
    }

    /**
     * Render log page
     */
    public function render_log_page() {
        if (!function_exists('current_user_can') || !\current_user_can('manage_options')) {
            return;
        }

        if (!function_exists('esc_html__') || !function_exists('esc_html')) {
            echo '<div class="wrap"><h1>Debug Log Viewer</h1><div class="debug-log-tools-content"><pre>';
            echo htmlspecialchars($this->get_log_contents());
            echo '</pre></div></div>';
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
        if (!defined('WP_CONTENT_DIR')) {
            return 'Error: WP_CONTENT_DIR is not defined.';
        }

        $log_file = \WP_CONTENT_DIR . '/debug.log';
        
        if (!file_exists($log_file)) {
            return function_exists('esc_html__') 
                ? \esc_html__('Debug log file does not exist.', 'debug-log-tools')
                : 'Debug log file does not exist.';
        }

        if (!is_readable($log_file)) {
            return function_exists('esc_html__')
                ? \esc_html__('Debug log file is not readable.', 'debug-log-tools')
                : 'Debug log file is not readable.';
        }

        $contents = file_get_contents($log_file);
        if (false === $contents) {
            return function_exists('esc_html__')
                ? \esc_html__('Failed to read debug log file.', 'debug-log-tools')
                : 'Failed to read debug log file.';
        }

        return $contents;
    }
} 