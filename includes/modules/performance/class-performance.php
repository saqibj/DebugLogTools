<?php
/**
 * Performance Module
 *
 * @package DebugLogTools
 * @subpackage Modules
 */

namespace DebugLogTools\Modules;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Performance
 */
class Performance extends Base_Module {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Set module properties
        $this->module_name = 'Performance';
        $this->module_description = 'Monitor and track WordPress performance';
    }

    /**
     * Initialize the module
     */
    public function init() {
        if (!$this->is_active()) {
            return;
        }

        // Initialize module functionality
        \add_action('admin_menu', array($this, 'add_menu_page'));
    }

    /**
     * Add menu page
     */
    public function add_menu_page() {
        \add_submenu_page(
            'tools.php',
            \__('Performance Monitoring', 'debug-log-tools'),
            \__('Performance', 'debug-log-tools'),
            'manage_options',
            'debug-log-performance',
            array($this, 'render_page')
        );
    }

    /**
     * Render module page
     */
    public function render_page() {
        if (!\current_user_can('manage_options')) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php echo \esc_html__('Performance Monitoring', 'debug-log-tools'); ?></h1>
            
            <p><?php echo \esc_html__('Monitor WordPress performance and identify bottlenecks.', 'debug-log-tools'); ?></p>
            
            <div class="performance-stats">
                <h2><?php echo \esc_html__('Current Performance Metrics', 'debug-log-tools'); ?></h2>
                
                <div class="performance-stat">
                    <h3><?php echo \esc_html__('Memory Usage', 'debug-log-tools'); ?></h3>
                    <p><?php echo \esc_html(\size_format(\memory_get_usage())); ?></p>
                </div>
                
                <div class="performance-stat">
                    <h3><?php echo \esc_html__('Peak Memory Usage', 'debug-log-tools'); ?></h3>
                    <p><?php echo \esc_html(\size_format(\memory_get_peak_usage())); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
} 