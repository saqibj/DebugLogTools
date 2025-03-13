<?php
/**
 * Debug Log Performance Module
 *
 * @package DebugLogTools
 */

namespace DebugLogTools\Modules;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Performance
 * Handles performance monitoring and logging
 */
class Performance extends Base_Module {
    /**
     * Performance metrics storage
     *
     * @var array
     */
    private $metrics = array();

    /**
     * Timer start points
     *
     * @var array
     */
    private $timers = array();

    /**
     * Module initialization
     */
    public function init() {
        add_action('plugins_loaded', array($this, 'start_monitoring'), -999);
        add_action('shutdown', array($this, 'log_performance_metrics'), 999);
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('wp_ajax_debug_log_performance_data', array($this, 'get_performance_data'));
    }

    /**
     * Start performance monitoring
     */
    public function start_monitoring() {
        // Start measuring total execution time
        $this->start_timer('total_execution');

        // Monitor memory usage
        $this->record_metric('initial_memory', memory_get_usage());
        $this->record_metric('initial_peak_memory', memory_get_peak_usage());

        // Monitor database queries
        add_filter('query', array($this, 'log_query'));

        // Monitor HTTP requests
        add_filter('pre_http_request', array($this, 'start_http_request_timer'), 10, 3);
        add_filter('http_response', array($this, 'log_http_request'), 10, 3);

        // Monitor cache hits and misses
        add_action('wp_cache_add', array($this, 'monitor_cache_operation'), 10, 4);
        add_action('wp_cache_set', array($this, 'monitor_cache_operation'), 10, 4);
        add_action('wp_cache_get', array($this, 'monitor_cache_get'), 10, 2);
    }

    /**
     * Register performance settings
     */
    public function register_settings() {
        register_setting(
            'debug_log_performance',
            'debug_log_performance_settings',
            array(
                'type' => 'array',
                'description' => 'Debug Log Performance Settings',
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'default' => array(
                    'monitor_queries' => true,
                    'monitor_http' => true,
                    'monitor_cache' => true,
                    'slow_query_threshold' => 1.0,
                    'slow_request_threshold' => 5.0,
                    'memory_threshold' => 75,
                    'log_frequency' => 'hourly',
                ),
            )
        );
    }

    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'debug-log-tools',
            __('Performance Monitoring', 'debug-log-tools'),
            __('Performance', 'debug-log-tools'),
            'manage_options',
            'debug-log-performance',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = get_option('debug_log_performance_settings');
        include_once plugin_dir_path(__FILE__) . 'templates/performance-page.php';
    }

    /**
     * Start a timer
     *
     * @param string $name Timer name.
     */
    public function start_timer($name) {
        $this->timers[$name] = microtime(true);
    }

    /**
     * Stop a timer and get elapsed time
     *
     * @param string $name Timer name.
     * @return float Elapsed time in seconds
     */
    public function stop_timer($name) {
        if (!isset($this->timers[$name])) {
            return 0.0;
        }

        $elapsed = microtime(true) - $this->timers[$name];
        unset($this->timers[$name]);
        return round($elapsed, 4);
    }

    /**
     * Record a metric
     *
     * @param string $name  Metric name.
     * @param mixed  $value Metric value.
     */
    public function record_metric($name, $value) {
        $this->metrics[$name] = $value;
    }

    /**
     * Log database query
     *
     * @param string $query The database query.
     * @return string The original query
     */
    public function log_query($query) {
        static $query_count = 0;
        $settings = get_option('debug_log_performance_settings');

        if (!$settings['monitor_queries']) {
            return $query;
        }

        $start_time = microtime(true);
        $this->start_timer("query_{$query_count}");

        add_filter('query_complete', function() use ($query, $query_count, $settings, $start_time) {
            $execution_time = $this->stop_timer("query_{$query_count}");
            
            if ($execution_time >= $settings['slow_query_threshold']) {
                $this->log_slow_query($query, $execution_time);
            }

            if (!isset($this->metrics['queries'])) {
                $this->metrics['queries'] = array();
            }

            $this->metrics['queries'][] = array(
                'query' => $query,
                'time' => $execution_time,
                'timestamp' => date('Y-m-d H:i:s'),
            );
        });

        $query_count++;
        return $query;
    }

    /**
     * Start timing HTTP request
     *
     * @param mixed  $preempt Whether to preempt the request.
     * @param array  $args    Request arguments.
     * @param string $url     Request URL.
     * @return mixed The preempt value
     */
    public function start_http_request_timer($preempt, $args, $url) {
        static $request_count = 0;
        $this->start_timer("http_request_{$request_count}");
        $this->metrics['current_http_request'] = $request_count;
        $request_count++;
        return $preempt;
    }

    /**
     * Log HTTP request
     *
     * @param array  $response Response data.
     * @param array  $args     Request arguments.
     * @param string $url      Request URL.
     * @return array The response data
     */
    public function log_http_request($response, $args, $url) {
        $settings = get_option('debug_log_performance_settings');

        if (!$settings['monitor_http']) {
            return $response;
        }

        $request_id = $this->metrics['current_http_request'];
        $execution_time = $this->stop_timer("http_request_{$request_id}");

        if ($execution_time >= $settings['slow_request_threshold']) {
            $this->log_slow_request($url, $execution_time, $response);
        }

        if (!isset($this->metrics['http_requests'])) {
            $this->metrics['http_requests'] = array();
        }

        $this->metrics['http_requests'][] = array(
            'url' => $url,
            'time' => $execution_time,
            'response_code' => wp_remote_retrieve_response_code($response),
            'timestamp' => date('Y-m-d H:i:s'),
        );

        return $response;
    }

    /**
     * Monitor cache operations
     *
     * @param string $key    Cache key.
     * @param mixed  $value  Cache value.
     * @param int    $expire Expiration time.
     * @param string $group  Cache group.
     */
    public function monitor_cache_operation($key, $value, $expire, $group) {
        $settings = get_option('debug_log_performance_settings');

        if (!$settings['monitor_cache']) {
            return;
        }

        if (!isset($this->metrics['cache_operations'])) {
            $this->metrics['cache_operations'] = array(
                'sets' => 0,
                'adds' => 0,
                'gets' => 0,
                'hits' => 0,
                'misses' => 0,
            );
        }

        $operation = current_filter() === 'wp_cache_add' ? 'adds' : 'sets';
        $this->metrics['cache_operations'][$operation]++;
    }

    /**
     * Monitor cache gets
     *
     * @param mixed  $value Cache value.
     * @param string $key   Cache key.
     * @return mixed The cache value
     */
    public function monitor_cache_get($value, $key) {
        $settings = get_option('debug_log_performance_settings');

        if (!$settings['monitor_cache']) {
            return $value;
        }

        if (!isset($this->metrics['cache_operations'])) {
            $this->metrics['cache_operations'] = array(
                'sets' => 0,
                'adds' => 0,
                'gets' => 0,
                'hits' => 0,
                'misses' => 0,
            );
        }

        $this->metrics['cache_operations']['gets']++;
        
        if ($value === false) {
            $this->metrics['cache_operations']['misses']++;
        } else {
            $this->metrics['cache_operations']['hits']++;
        }

        return $value;
    }

    /**
     * Log performance metrics
     */
    public function log_performance_metrics() {
        $settings = get_option('debug_log_performance_settings');
        
        // Final memory measurements
        $this->record_metric('final_memory', memory_get_usage());
        $this->record_metric('final_peak_memory', memory_get_peak_usage());
        
        // Total execution time
        $this->record_metric('total_execution_time', $this->stop_timer('total_execution'));

        // Calculate memory usage percentage
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        $memory_usage = $this->metrics['final_peak_memory'];
        $memory_percentage = ($memory_usage / $memory_limit) * 100;

        if ($memory_percentage >= $settings['memory_threshold']) {
            $this->log_high_memory_usage($memory_usage, $memory_limit);
        }

        // Store metrics for the admin interface
        update_option('debug_log_performance_metrics', array(
            'timestamp' => current_time('mysql'),
            'metrics' => $this->metrics
        ));

        // Log metrics based on frequency
        if ($this->should_log_metrics()) {
            $this->write_metrics_to_log();
        }
    }

    /**
     * Check if metrics should be logged
     *
     * @return bool Whether to log metrics
     */
    private function should_log_metrics() {
        $settings = get_option('debug_log_performance_settings');
        $last_log = get_option('debug_log_performance_last_log');

        if (!$last_log) {
            return true;
        }

        $interval = 3600; // Default to hourly
        switch ($settings['log_frequency']) {
            case 'daily':
                $interval = 86400;
                break;
            case 'weekly':
                $interval = 604800;
                break;
        }

        return (time() - $last_log) >= $interval;
    }

    /**
     * Write metrics to debug log
     */
    private function write_metrics_to_log() {
        $message = sprintf(
            "Performance Metrics Summary:\n" .
            "- Total Execution Time: %.4f seconds\n" .
            "- Memory Usage: %s / %s (Peak: %s)\n" .
            "- Database Queries: %d (Slow Queries: %d)\n" .
            "- HTTP Requests: %d (Slow Requests: %d)\n" .
            "- Cache Operations: Gets: %d, Sets: %d, Hits: %d, Misses: %d\n",
            $this->metrics['total_execution_time'],
            size_format($this->metrics['final_memory']),
            size_format(wp_convert_hr_to_bytes(ini_get('memory_limit'))),
            size_format($this->metrics['final_peak_memory']),
            count($this->metrics['queries'] ?? array()),
            $this->count_slow_queries(),
            count($this->metrics['http_requests'] ?? array()),
            $this->count_slow_requests(),
            $this->metrics['cache_operations']['gets'] ?? 0,
            $this->metrics['cache_operations']['sets'] ?? 0,
            $this->metrics['cache_operations']['hits'] ?? 0,
            $this->metrics['cache_operations']['misses'] ?? 0
        );

        error_log($message);
        update_option('debug_log_performance_last_log', time());
    }

    /**
     * Log slow database query
     *
     * @param string $query          The database query.
     * @param float  $execution_time Query execution time.
     */
    private function log_slow_query($query, $execution_time) {
        error_log(sprintf(
            "Slow Query Detected (%.4f seconds):\n%s",
            $execution_time,
            $query
        ));
    }

    /**
     * Log slow HTTP request
     *
     * @param string $url            Request URL.
     * @param float  $execution_time Request execution time.
     * @param array  $response       Response data.
     */
    private function log_slow_request($url, $execution_time, $response) {
        error_log(sprintf(
            "Slow HTTP Request Detected (%.4f seconds):\nURL: %s\nResponse Code: %d",
            $execution_time,
            $url,
            wp_remote_retrieve_response_code($response)
        ));
    }

    /**
     * Log high memory usage
     *
     * @param int $usage Memory usage in bytes.
     * @param int $limit Memory limit in bytes.
     */
    private function log_high_memory_usage($usage, $limit) {
        error_log(sprintf(
            "High Memory Usage Detected:\nUsage: %s\nLimit: %s\nPercentage: %.2f%%",
            size_format($usage),
            size_format($limit),
            ($usage / $limit) * 100
        ));
    }

    /**
     * Count slow queries
     *
     * @return int Number of slow queries
     */
    private function count_slow_queries() {
        if (!isset($this->metrics['queries'])) {
            return 0;
        }

        $settings = get_option('debug_log_performance_settings');
        $threshold = $settings['slow_query_threshold'];

        return count(array_filter($this->metrics['queries'], function($query) use ($threshold) {
            return $query['time'] >= $threshold;
        }));
    }

    /**
     * Count slow HTTP requests
     *
     * @return int Number of slow requests
     */
    private function count_slow_requests() {
        if (!isset($this->metrics['http_requests'])) {
            return 0;
        }

        $settings = get_option('debug_log_performance_settings');
        $threshold = $settings['slow_request_threshold'];

        return count(array_filter($this->metrics['http_requests'], function($request) use ($threshold) {
            return $request['time'] >= $threshold;
        }));
    }

    /**
     * Get performance data for AJAX requests
     */
    public function get_performance_data() {
        check_ajax_referer('debug_log_performance_data');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $metrics = get_option('debug_log_performance_metrics');
        wp_send_json_success($metrics);
    }

    /**
     * Sanitize settings
     *
     * @param array $input Input array.
     * @return array Sanitized settings
     */
    public function sanitize_settings($input) {
        return array(
            'monitor_queries' => isset($input['monitor_queries']),
            'monitor_http' => isset($input['monitor_http']),
            'monitor_cache' => isset($input['monitor_cache']),
            'slow_query_threshold' => floatval($input['slow_query_threshold']),
            'slow_request_threshold' => floatval($input['slow_request_threshold']),
            'memory_threshold' => intval($input['memory_threshold']),
            'log_frequency' => in_array($input['log_frequency'], array('hourly', 'daily', 'weekly'), true) 
                ? $input['log_frequency'] 
                : 'hourly',
        );
    }
} 