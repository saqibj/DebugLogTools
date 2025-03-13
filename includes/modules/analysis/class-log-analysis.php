<?php
/**
 * Log Analysis Module
 *
 * @package DebugLogTools
 * @subpackage Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Log_Analysis
 */
class Log_Analysis extends Base_Module {
    /**
     * Log entry types
     */
    const TYPE_ERROR = 'error';
    const TYPE_WARNING = 'warning';
    const TYPE_NOTICE = 'notice';
    const TYPE_INFO = 'info';

    /**
     * Initialize the module
     */
    protected function init() {
        $this->module_id = 'log_analysis';
        $this->module_name = __('Log Analysis', 'debug-log-tools');
        $this->module_description = __('Advanced log analysis and visualization tools', 'debug-log-tools');
    }

    /**
     * Register WordPress hooks
     */
    protected function register_hooks() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_debug_log_analyze', array($this, 'ajax_analyze_log'));
        add_action('wp_ajax_debug_log_export', array($this, 'ajax_export_log'));
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets() {
        wp_enqueue_script(
            'debug-log-analysis',
            DEBUG_LOG_TOOLS_PLUGIN_URL . 'modules/analysis/js/analysis.js',
            array('jquery', 'wp-charts'),
            DEBUG_LOG_TOOLS_VERSION,
            true
        );

        wp_enqueue_style(
            'debug-log-analysis',
            DEBUG_LOG_TOOLS_PLUGIN_URL . 'modules/analysis/css/analysis.css',
            array(),
            DEBUG_LOG_TOOLS_VERSION
        );
    }

    /**
     * Analyze log entries
     *
     * @param string $content Log content
     * @return array Analysis results
     */
    public function analyze_log($content) {
        $entries = $this->parse_log_entries($content);
        $stats = $this->calculate_statistics($entries);
        $trends = $this->analyze_trends($entries);

        return array(
            'entries' => $entries,
            'stats' => $stats,
            'trends' => $trends
        );
    }

    /**
     * Parse log entries
     *
     * @param string $content Log content
     * @return array Parsed entries
     */
    protected function parse_log_entries($content) {
        $entries = array();
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }

            $entry = $this->parse_log_line($line);
            if ($entry) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    /**
     * Parse single log line
     *
     * @param string $line Log line
     * @return array|false Parsed entry or false
     */
    protected function parse_log_line($line) {
        // Standard WordPress error log format
        $pattern = '/\[(.+?)\] (PHP (?:Parse error|Fatal error|Warning|Notice|Deprecated): .+? in .+? on line \d+)/';
        
        if (preg_match($pattern, $line, $matches)) {
            return array(
                'timestamp' => strtotime($matches[1]),
                'message' => $matches[2],
                'type' => $this->determine_entry_type($matches[2])
            );
        }

        return false;
    }

    /**
     * Determine entry type
     *
     * @param string $message Error message
     * @return string Entry type
     */
    protected function determine_entry_type($message) {
        if (stripos($message, 'Fatal error') !== false) {
            return self::TYPE_ERROR;
        } elseif (stripos($message, 'Warning') !== false) {
            return self::TYPE_WARNING;
        } elseif (stripos($message, 'Notice') !== false) {
            return self::TYPE_NOTICE;
        }
        return self::TYPE_INFO;
    }

    /**
     * Calculate statistics
     *
     * @param array $entries Log entries
     * @return array Statistics
     */
    protected function calculate_statistics($entries) {
        $stats = array(
            'total' => count($entries),
            'by_type' => array(
                self::TYPE_ERROR => 0,
                self::TYPE_WARNING => 0,
                self::TYPE_NOTICE => 0,
                self::TYPE_INFO => 0
            ),
            'by_hour' => array_fill(0, 24, 0)
        );

        foreach ($entries as $entry) {
            $stats['by_type'][$entry['type']]++;
            $hour = (int) date('G', $entry['timestamp']);
            $stats['by_hour'][$hour]++;
        }

        return $stats;
    }

    /**
     * Analyze trends
     *
     * @param array $entries Log entries
     * @return array Trends analysis
     */
    protected function analyze_trends($entries) {
        $trends = array(
            'most_common_errors' => array(),
            'peak_hours' => array(),
            'recent_increase' => false
        );

        // Analyze most common errors
        $error_counts = array();
        foreach ($entries as $entry) {
            if ($entry['type'] === self::TYPE_ERROR) {
                $key = md5($entry['message']);
                if (!isset($error_counts[$key])) {
                    $error_counts[$key] = array(
                        'message' => $entry['message'],
                        'count' => 0
                    );
                }
                $error_counts[$key]['count']++;
            }
        }

        // Sort by count
        uasort($error_counts, function($a, $b) {
            return $b['count'] - $a['count'];
        });

        $trends['most_common_errors'] = array_slice($error_counts, 0, 5);

        // Find peak hours
        $stats = $this->calculate_statistics($entries);
        arsort($stats['by_hour']);
        $trends['peak_hours'] = array_slice($stats['by_hour'], 0, 3, true);

        // Check for recent increase
        $recent_count = 0;
        $old_count = 0;
        $threshold = time() - (24 * 3600); // Last 24 hours

        foreach ($entries as $entry) {
            if ($entry['timestamp'] >= $threshold) {
                $recent_count++;
            } else {
                $old_count++;
            }
        }

        $trends['recent_increase'] = ($recent_count > $old_count);

        return $trends;
    }

    /**
     * AJAX handler for log analysis
     */
    public function ajax_analyze_log() {
        check_ajax_referer('debug_log_tools_analyze');

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

        $analysis = $this->analyze_log($content);
        wp_send_json_success($analysis);
    }

    /**
     * AJAX handler for log export
     */
    public function ajax_export_log() {
        check_ajax_referer('debug_log_tools_export');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized access', 'debug-log-tools'));
        }

        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'json';
        $log_file = WP_CONTENT_DIR . '/debug.log';

        if (!file_exists($log_file)) {
            wp_send_json_error(__('Log file not found', 'debug-log-tools'));
        }

        $content = file_get_contents($log_file);
        if ($content === false) {
            wp_send_json_error(__('Failed to read log file', 'debug-log-tools'));
        }

        $entries = $this->parse_log_entries($content);

        switch ($format) {
            case 'csv':
                $export = $this->export_as_csv($entries);
                break;
            case 'json':
            default:
                $export = $this->export_as_json($entries);
                break;
        }

        wp_send_json_success(array(
            'content' => $export,
            'filename' => 'debug-log-' . date('Y-m-d') . '.' . $format
        ));
    }

    /**
     * Export entries as CSV
     *
     * @param array $entries Log entries
     * @return string CSV content
     */
    protected function export_as_csv($entries) {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, array('Timestamp', 'Type', 'Message'));

        foreach ($entries as $entry) {
            fputcsv($output, array(
                date('Y-m-d H:i:s', $entry['timestamp']),
                $entry['type'],
                $entry['message']
            ));
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Export entries as JSON
     *
     * @param array $entries Log entries
     * @return string JSON content
     */
    protected function export_as_json($entries) {
        return wp_json_encode($entries, JSON_PRETTY_PRINT);
    }
} 