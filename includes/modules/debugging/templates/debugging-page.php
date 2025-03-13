<?php
/**
 * Template for the debugging interface
 *
 * @package DebugLogTools
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Debug Log Tools - Advanced Debugging', 'debug-log-tools'); ?></h1>

    <div class="debug-container">
        <div class="debug-controls">
            <button id="refresh-debug" class="debug-button debug-button-primary">
                <?php esc_html_e('Refresh Data', 'debug-log-tools'); ?>
            </button>
            
            <div class="auto-refresh-container">
                <input type="checkbox" id="auto-refresh" name="auto-refresh">
                <label for="auto-refresh" class="auto-refresh-label">
                    <?php esc_html_e('Auto-refresh (30s)', 'debug-log-tools'); ?>
                </label>
            </div>

            <button id="clear-data" class="debug-button debug-button-danger">
                <?php esc_html_e('Clear Data', 'debug-log-tools'); ?>
            </button>
        </div>

        <div class="debug-tabs">
            <a href="#" class="debug-tab-link active" data-target="#memory-tab">
                <?php esc_html_e('Memory Usage', 'debug-log-tools'); ?>
            </a>
            <a href="#" class="debug-tab-link" data-target="#queries-tab">
                <?php esc_html_e('Query Monitor', 'debug-log-tools'); ?>
            </a>
            <a href="#" class="debug-tab-link" data-target="#backtrace-tab">
                <?php esc_html_e('Error Backtraces', 'debug-log-tools'); ?>
            </a>
        </div>

        <div id="memory-tab" class="debug-tab-content active">
            <div class="memory-stats">
                <div class="memory-stat">
                    <div class="memory-stat-label">
                        <?php esc_html_e('Current Memory Usage', 'debug-log-tools'); ?>
                    </div>
                    <div id="memory-current" class="memory-stat-value">0 MB</div>
                </div>

                <div class="memory-stat">
                    <div class="memory-stat-label">
                        <?php esc_html_e('Peak Memory Usage', 'debug-log-tools'); ?>
                    </div>
                    <div id="memory-peak" class="memory-stat-value">0 MB</div>
                </div>

                <div class="memory-stat">
                    <div class="memory-stat-label">
                        <?php esc_html_e('Memory Limit', 'debug-log-tools'); ?>
                    </div>
                    <div id="memory-limit" class="memory-stat-value">0 MB</div>
                </div>
            </div>

            <div id="memory-warning" class="memory-warning d-none">
                <?php esc_html_e('Warning: Memory usage is approaching the limit. Consider increasing the memory limit or optimizing your code.', 'debug-log-tools'); ?>
            </div>

            <div class="chart-container">
                <canvas id="memory-chart"></canvas>
            </div>
        </div>

        <div id="queries-tab" class="debug-tab-content">
            <div class="chart-container">
                <canvas id="query-chart"></canvas>
            </div>

            <div id="query-list">
                <!-- Query items will be dynamically inserted here -->
            </div>
        </div>

        <div id="backtrace-tab" class="debug-tab-content">
            <div id="backtrace-list">
                <!-- Backtrace items will be dynamically inserted here -->
            </div>
        </div>
    </div>
</div> 