<?php
/**
 * Debugging Module
 *
 * @package DebugLogTools
 * @subpackage Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Debugging
 */
class Debugging extends Base_Module {
    /**
     * Initialize the module
     */
    protected function init() {
        $this->module_id = 'debugging';
        $this->module_name = __('Advanced Debugging', 'debug-log-tools');
        $this->module_description = __('Advanced debugging tools including backtrace visualization and query monitoring', 'debug-log-tools');
    }

    /**
     * Register WordPress hooks
     */
    protected function register_hooks() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_debug_log_backtrace', array($this, 'ajax_get_backtrace'));
        add_action('wp_ajax_debug_log_query_monitor', array($this, 'ajax_monitor_queries'));
        add_action('wp_ajax_debug_log_memory_usage', array($this, 'ajax_get_memory_usage'));
        
        // Hook into query execution if monitoring is enabled
        if ($this->is_query_monitoring_enabled()) {
            add_filter('query', array($this, 'monitor_query'), 10, 1);
        }

        // Hook into error handling if backtrace is enabled
        if ($this->is_backtrace_enabled()) {
            add_action('wp_error', array($this, 'capture_backtrace'), 10, 1);
        }
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        wp_enqueue_script(
            'debug-log-debugging',
            DEBUG_LOG_TOOLS_PLUGIN_URL . 'modules/debugging/js/debugging.js',
            array('jquery'),
            DEBUG_LOG_TOOLS_VERSION,
            true
        );

        wp_enqueue_style(
            'debug-log-debugging',
            DEBUG_LOG_TOOLS_PLUGIN_URL . 'modules/debugging/css/debugging.css',
            array(),
            DEBUG_LOG_TOOLS_VERSION
        );

        wp_localize_script(
            'debug-log-debugging',
            'debugLogDebugging',
            array(
                'nonce' => wp_create_nonce('debug_log_debugging'),
                'ajaxurl' => admin_url('admin-ajax.php')
            )
        );
    }

    /**
     * Check if query monitoring is enabled
     *
     * @return bool
     */
    public function is_query_monitoring_enabled() {
        return get_option('debug_log_tools_query_monitoring', false);
    }

    /**
     * Check if backtrace capture is enabled
     *
     * @return bool
     */
    public function is_backtrace_enabled() {
        return get_option('debug_log_tools_backtrace', false);
    }

    /**
     * Monitor database query
     *
     * @param string $query SQL query
     * @return string
     */
    public function monitor_query($query) {
        global $wpdb;
        
        $start_time = microtime(true);
        $result = $wpdb->query($query);
        $end_time = microtime(true);
        
        $execution_time = $end_time - $start_time;
        
        if ($execution_time > 1.0) { // Log slow queries (>1s)
            $this->log_slow_query($query, $execution_time);
        }
        
        return $query;
    }

    /**
     * Log slow query
     *
     * @param string $query SQL query
     * @param float $execution_time Query execution time
     */
    private function log_slow_query($query, $execution_time) {
        $log_entry = sprintf(
            "[%s] Slow Query (%fs): %s\n",
            current_time('mysql'),
            $execution_time,
            $query
        );
        
        error_log($log_entry);
    }

    /**
     * Capture error backtrace
     *
     * @param WP_Error $error WordPress error object
     */
    public function capture_backtrace($error) {
        if (!is_wp_error($error)) {
            return;
        }
        
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        array_shift($backtrace); // Remove this function from trace
        
        $log_entry = sprintf(
            "[%s] Error: %s\nBacktrace:\n%s\n",
            current_time('mysql'),
            $error->get_error_message(),
            $this->format_backtrace($backtrace)
        );
        
        error_log($log_entry);
    }

    /**
     * Format backtrace for logging
     *
     * @param array $backtrace Debug backtrace array
     * @return string
     */
    private function format_backtrace($backtrace) {
        $output = '';
        foreach ($backtrace as $index => $trace) {
            $output .= sprintf(
                "#%d %s(%d): %s%s%s()\n",
                $index,
                isset($trace['file']) ? $trace['file'] : 'unknown file',
                isset($trace['line']) ? $trace['line'] : 0,
                isset($trace['class']) ? $trace['class'] : '',
                isset($trace['type']) ? $trace['type'] : '',
                $trace['function']
            );
        }
        return $output;
    }

    /**
     * AJAX handler for backtrace request
     */
    public function ajax_get_backtrace() {
        check_ajax_referer('debug_log_debugging', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized access', 'debug-log-tools'));
        }
        
        $log_file = WP_CONTENT_DIR . '/debug.log';
        if (!file_exists($log_file)) {
            wp_send_json_error(__('Log file not found', 'debug-log-tools'));
        }
        
        $content = file_get_contents($log_file);
        if ($content === false) {
            wp_send_json_error(__('Failed to read log file', 'debug-log-tools'));
        }
        
        $backtraces = $this->parse_backtraces($content);
        wp_send_json_success($backtraces);
    }

    /**
     * Parse backtraces from log content
     *
     * @param string $content Log content
     * @return array
     */
    private function parse_backtraces($content) {
        $backtraces = array();
        $lines = explode("\n", $content);
        
        $current_trace = array();
        $in_backtrace = false;
        
        foreach ($lines as $line) {
            if (strpos($line, 'Error:') !== false && strpos($line, 'Backtrace:') !== false) {
                $in_backtrace = true;
                $current_trace = array(
                    'error' => trim(str_replace('Error:', '', substr($line, 0, strpos($line, 'Backtrace:')))),
                    'trace' => array()
                );
            } elseif ($in_backtrace && preg_match('/^#\d+/', $line)) {
                $current_trace['trace'][] = trim($line);
            } elseif ($in_backtrace && empty($line)) {
                if (!empty($current_trace)) {
                    $backtraces[] = $current_trace;
                }
                $in_backtrace = false;
                $current_trace = array();
            }
        }
        
        return $backtraces;
    }

    /**
     * AJAX handler for query monitoring
     */
    public function ajax_monitor_queries() {
        check_ajax_referer('debug_log_debugging', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized access', 'debug-log-tools'));
        }
        
        $log_file = WP_CONTENT_DIR . '/debug.log';
        if (!file_exists($log_file)) {
            wp_send_json_error(__('Log file not found', 'debug-log-tools'));
        }
        
        $content = file_get_contents($log_file);
        if ($content === false) {
            wp_send_json_error(__('Failed to read log file', 'debug-log-tools'));
        }
        
        $queries = $this->parse_slow_queries($content);
        wp_send_json_success($queries);
    }

    /**
     * Parse slow queries from log content
     *
     * @param string $content Log content
     * @return array
     */
    private function parse_slow_queries($content) {
        $queries = array();
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            if (strpos($line, 'Slow Query') !== false) {
                if (preg_match('/\[(.*?)\] Slow Query \((.*?)s\): (.*)/', $line, $matches)) {
                    $queries[] = array(
                        'timestamp' => $matches[1],
                        'execution_time' => floatval($matches[2]),
                        'query' => $matches[3]
                    );
                }
            }
        }
        
        return $queries;
    }

    /**
     * AJAX handler for memory usage
     */
    public function ajax_get_memory_usage() {
        check_ajax_referer('debug_log_debugging', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized access', 'debug-log-tools'));
        }
        
        $memory_usage = array(
            'current' => memory_get_usage(),
            'peak' => memory_get_peak_usage(),
            'limit' => wp_convert_hr_to_bytes(ini_get('memory_limit')),
            'wp_limit' => wp_convert_hr_to_bytes(WP_MEMORY_LIMIT),
            'percentage' => (memory_get_usage() / wp_convert_hr_to_bytes(WP_MEMORY_LIMIT)) * 100
        );
        
        wp_send_json_success($memory_usage);
    }
} 