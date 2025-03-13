<?php
/**
 * Template for log analysis page
 *
 * @package DebugLogTools
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="debug-log-analysis-wrap">
    <div class="analysis-controls">
        <select id="date-range" class="regular-text">
            <option value="all"><?php esc_html_e('All Time', 'debug-log-tools'); ?></option>
            <option value="1h"><?php esc_html_e('Last Hour', 'debug-log-tools'); ?></option>
            <option value="24h"><?php esc_html_e('Last 24 Hours', 'debug-log-tools'); ?></option>
            <option value="7d"><?php esc_html_e('Last 7 Days', 'debug-log-tools'); ?></option>
            <option value="30d"><?php esc_html_e('Last 30 Days', 'debug-log-tools'); ?></option>
        </select>

        <select id="error-type" class="regular-text">
            <option value="all"><?php esc_html_e('All Types', 'debug-log-tools'); ?></option>
            <option value="error"><?php esc_html_e('Errors Only', 'debug-log-tools'); ?></option>
            <option value="warning"><?php esc_html_e('Warnings Only', 'debug-log-tools'); ?></option>
            <option value="notice"><?php esc_html_e('Notices Only', 'debug-log-tools'); ?></option>
            <option value="info"><?php esc_html_e('Info Only', 'debug-log-tools'); ?></option>
        </select>

        <button id="analyze-log" class="button button-primary">
            <?php esc_html_e('Analyze Log', 'debug-log-tools'); ?>
        </button>

        <button id="export-log" class="button">
            <?php esc_html_e('Export Log', 'debug-log-tools'); ?>
        </button>
    </div>

    <div id="recent-increase" class="notice notice-warning d-none">
        <p><?php esc_html_e('There has been a significant increase in errors in the last 24 hours.', 'debug-log-tools'); ?></p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value" id="total-entries">0</div>
            <div class="stat-label"><?php esc_html_e('Total Entries', 'debug-log-tools'); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-value error-stat" id="error-count">0</div>
            <div class="stat-label"><?php esc_html_e('Errors', 'debug-log-tools'); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-value warning-stat" id="warning-count">0</div>
            <div class="stat-label"><?php esc_html_e('Warnings', 'debug-log-tools'); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-value notice-stat" id="notice-count">0</div>
            <div class="stat-label"><?php esc_html_e('Notices', 'debug-log-tools'); ?></div>
        </div>
    </div>

    <div class="charts-container">
        <div class="chart-wrapper">
            <h3><?php esc_html_e('Error Type Distribution', 'debug-log-tools'); ?></h3>
            <canvas id="error-type-chart"></canvas>
        </div>

        <div class="chart-wrapper">
            <h3><?php esc_html_e('Errors by Hour', 'debug-log-tools'); ?></h3>
            <canvas id="hourly-chart"></canvas>
        </div>
    </div>

    <div class="trends-section">
        <h3><?php esc_html_e('Trends & Insights', 'debug-log-tools'); ?></h3>
        
        <div class="trend-info">
            <h4><?php esc_html_e('Most Common Errors', 'debug-log-tools'); ?></h4>
            <ul id="trends-list"></ul>
        </div>

        <div class="trend-info">
            <h4><?php esc_html_e('Peak Error Hours', 'debug-log-tools'); ?></h4>
            <p id="peak-hours"></p>
        </div>
    </div>

    <div class="export-options">
        <h3><?php esc_html_e('Export Options', 'debug-log-tools'); ?></h3>
        <div class="export-controls">
            <select id="export-format" class="regular-text">
                <option value="json"><?php esc_html_e('JSON Format', 'debug-log-tools'); ?></option>
                <option value="csv"><?php esc_html_e('CSV Format', 'debug-log-tools'); ?></option>
            </select>
            <button id="export-log" class="button">
                <?php esc_html_e('Export', 'debug-log-tools'); ?>
            </button>
        </div>
    </div>
</div> 