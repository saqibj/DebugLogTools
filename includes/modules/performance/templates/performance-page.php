<?php
/**
 * Template for performance monitoring page
 *
 * @package DebugLogTools
 */

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

// Ensure $settings is set
if (!isset($settings) || !is_array($settings)) {
    $settings = get_option('debug_log_performance_settings', array());
}

$metrics = get_option('debug_log_performance_metrics', array());
?>

<div class="wrap">
    <h1><?php esc_html_e('Performance Monitoring', 'debug-log-tools'); ?></h1>

    <div class="performance-container">
        <!-- Performance Overview -->
        <div class="performance-section">
            <h2><?php esc_html_e('Performance Overview', 'debug-log-tools'); ?></h2>
            <div class="performance-overview">
                <div class="performance-stat">
                    <h3><?php esc_html_e('Execution Time', 'debug-log-tools'); ?></h3>
                    <div class="stat-value" id="execution-time">
                        <?php echo isset($metrics['metrics']['total_execution_time']) ? 
                            esc_html(number_format($metrics['metrics']['total_execution_time'], 4)) . 's' : 
                            '---'; ?>
                    </div>
                </div>

                <div class="performance-stat">
                    <h3><?php esc_html_e('Memory Usage', 'debug-log-tools'); ?></h3>
                    <div class="stat-value" id="memory-usage">
                        <?php 
                        if (isset($metrics['metrics']['final_memory'])) {
                            echo esc_html(size_format($metrics['metrics']['final_memory']));
                            echo ' / ';
                            echo esc_html(size_format(wp_convert_hr_to_bytes(ini_get('memory_limit'))));
                        } else {
                            echo '---';
                        }
                        ?>
                    </div>
                </div>

                <div class="performance-stat">
                    <h3><?php esc_html_e('Database Queries', 'debug-log-tools'); ?></h3>
                    <div class="stat-value" id="query-count">
                        <?php echo isset($metrics['metrics']['queries']) ? 
                            esc_html(count($metrics['metrics']['queries'])) : 
                            '0'; ?>
                    </div>
                </div>

                <div class="performance-stat">
                    <h3><?php esc_html_e('HTTP Requests', 'debug-log-tools'); ?></h3>
                    <div class="stat-value" id="http-count">
                        <?php echo isset($metrics['metrics']['http_requests']) ? 
                            esc_html(count($metrics['metrics']['http_requests'])) : 
                            '0'; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Charts -->
        <div class="performance-section">
            <h2><?php esc_html_e('Performance Charts', 'debug-log-tools'); ?></h2>
            <div class="performance-charts">
                <div class="chart-container">
                    <canvas id="memory-chart"></canvas>
                </div>
                <div class="chart-container">
                    <canvas id="query-chart"></canvas>
                </div>
            </div>
        </div>

        <!-- Performance Settings -->
        <div class="performance-section">
            <h2><?php esc_html_e('Monitoring Settings', 'debug-log-tools'); ?></h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('debug_log_performance');
                do_settings_sections('debug_log_performance');
                ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Monitoring', 'debug-log-tools'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       name="debug_log_performance_settings[monitor_queries]" 
                                       value="1" 
                                       <?php checked($settings['monitor_queries'] ?? false); ?>>
                                <?php esc_html_e('Monitor Database Queries', 'debug-log-tools'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" 
                                       name="debug_log_performance_settings[monitor_http]" 
                                       value="1" 
                                       <?php checked($settings['monitor_http'] ?? false); ?>>
                                <?php esc_html_e('Monitor HTTP Requests', 'debug-log-tools'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox" 
                                       name="debug_log_performance_settings[monitor_cache]" 
                                       value="1" 
                                       <?php checked($settings['monitor_cache'] ?? false); ?>>
                                <?php esc_html_e('Monitor Cache Operations', 'debug-log-tools'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('Thresholds', 'debug-log-tools'); ?></th>
                        <td>
                            <label>
                                <?php esc_html_e('Slow Query Threshold (seconds)', 'debug-log-tools'); ?>
                                <input type="number" 
                                       name="debug_log_performance_settings[slow_query_threshold]" 
                                       value="<?php echo esc_attr($settings['slow_query_threshold'] ?? 1.0); ?>" 
                                       step="0.1" 
                                       min="0.1">
                            </label>
                            <br>
                            <label>
                                <?php esc_html_e('Slow Request Threshold (seconds)', 'debug-log-tools'); ?>
                                <input type="number" 
                                       name="debug_log_performance_settings[slow_request_threshold]" 
                                       value="<?php echo esc_attr($settings['slow_request_threshold'] ?? 5.0); ?>" 
                                       step="0.5" 
                                       min="0.5">
                            </label>
                            <br>
                            <label>
                                <?php esc_html_e('Memory Usage Threshold (%)', 'debug-log-tools'); ?>
                                <input type="number" 
                                       name="debug_log_performance_settings[memory_threshold]" 
                                       value="<?php echo esc_attr($settings['memory_threshold'] ?? 75); ?>" 
                                       min="1" 
                                       max="100">
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('Logging Frequency', 'debug-log-tools'); ?></th>
                        <td>
                            <select name="debug_log_performance_settings[log_frequency]">
                                <?php
                                $frequencies = array(
                                    'hourly' => __('Hourly', 'debug-log-tools'),
                                    'daily' => __('Daily', 'debug-log-tools'),
                                    'weekly' => __('Weekly', 'debug-log-tools')
                                );
                                foreach ($frequencies as $value => $label) :
                                    ?>
                                    <option value="<?php echo esc_attr($value); ?>" 
                                            <?php selected($settings['log_frequency'] ?? 'hourly', $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>

        <!-- Performance Data -->
        <div class="performance-section">
            <h2><?php esc_html_e('Performance Data', 'debug-log-tools'); ?></h2>
            
            <div class="tablist" role="tablist">
                <button id="tab-queries" 
                        class="tab-button active" 
                        role="tab" 
                        aria-selected="true" 
                        aria-controls="panel-queries">
                    <?php esc_html_e('Database Queries', 'debug-log-tools'); ?>
                </button>
                <button id="tab-http" 
                        class="tab-button" 
                        role="tab" 
                        aria-selected="false" 
                        aria-controls="panel-http">
                    <?php esc_html_e('HTTP Requests', 'debug-log-tools'); ?>
                </button>
                <button id="tab-cache" 
                        class="tab-button" 
                        role="tab" 
                        aria-selected="false" 
                        aria-controls="panel-cache">
                    <?php esc_html_e('Cache Operations', 'debug-log-tools'); ?>
                </button>
            </div>

            <div id="panel-queries" class="tab-panel active" role="tabpanel" aria-labelledby="tab-queries">
                <div class="performance-table-container">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Query', 'debug-log-tools'); ?></th>
                                <th><?php esc_html_e('Time (s)', 'debug-log-tools'); ?></th>
                                <th><?php esc_html_e('Timestamp', 'debug-log-tools'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="query-list">
                            <!-- Query data will be dynamically inserted here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="panel-http" class="tab-panel" role="tabpanel" aria-labelledby="tab-http">
                <div class="performance-table-container">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('URL', 'debug-log-tools'); ?></th>
                                <th><?php esc_html_e('Time (s)', 'debug-log-tools'); ?></th>
                                <th><?php esc_html_e('Response', 'debug-log-tools'); ?></th>
                                <th><?php esc_html_e('Timestamp', 'debug-log-tools'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="http-list">
                            <!-- HTTP request data will be dynamically inserted here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div id="panel-cache" class="tab-panel" role="tabpanel" aria-labelledby="tab-cache">
                <div class="performance-table-container">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Operation', 'debug-log-tools'); ?></th>
                                <th><?php esc_html_e('Count', 'debug-log-tools'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="cache-list">
                            <!-- Cache operation data will be dynamically inserted here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.performance-container {
    max-width: 1200px;
    margin-top: 20px;
}

.performance-section {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.performance-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.performance-stat {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
}

.performance-stat h3 {
    margin: 0 0 10px 0;
    color: #23282d;
    font-size: 14px;
}

.stat-value {
    font-size: 24px;
    font-weight: 600;
    color: #007cba;
}

.performance-charts {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.chart-container {
    position: relative;
    height: 300px;
}

.tablist {
    margin-bottom: 20px;
    border-bottom: 1px solid #ccd0d4;
}

.tab-button {
    padding: 10px 15px;
    margin-right: 5px;
    background: none;
    border: 1px solid transparent;
    border-bottom: none;
    cursor: pointer;
    color: #555;
}

.tab-button:hover {
    color: #007cba;
}

.tab-button.active {
    background: #fff;
    border-color: #ccd0d4;
    border-bottom-color: #fff;
    margin-bottom: -1px;
    color: #007cba;
}

.tab-panel {
    display: none;
}

.tab-panel.active {
    display: block;
}

.performance-table-container {
    margin-top: 20px;
    max-height: 400px;
    overflow-y: auto;
}

/* Responsive Design */
@media (max-width: 782px) {
    .performance-overview {
        grid-template-columns: 1fr;
    }

    .performance-charts {
        grid-template-columns: 1fr;
    }

    .chart-container {
        height: 250px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab Switching
    $('.tab-button').on('click', function() {
        const targetId = $(this).attr('aria-controls');
        
        $('.tab-button').removeClass('active').attr('aria-selected', 'false');
        $('.tab-panel').removeClass('active');
        
        $(this).addClass('active').attr('aria-selected', 'true');
        $(`#${targetId}`).addClass('active');
    });

    // Initialize Charts
    const memoryCtx = document.getElementById('memory-chart').getContext('2d');
    const queryCtx = document.getElementById('query-chart').getContext('2d');

    const memoryChart = new Chart(memoryCtx, {
        type: 'doughnut',
        data: {
            labels: ['Used', 'Available'],
            datasets: [{
                data: [0, 100],
                backgroundColor: ['#e74c3c', '#2ecc71']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Memory Usage'
                }
            }
        }
    });

    const queryChart = new Chart(queryCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Query Execution Time',
                data: [],
                borderColor: '#3498db',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Time (seconds)'
                    }
                }
            }
        }
    });

    // Update Performance Data
    function updatePerformanceData() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'debug_log_performance_data',
                nonce: '<?php echo wp_create_nonce('debug_log_performance_data'); ?>'
            },
            success: function(response) {
                if (response.success && response.data) {
                    const data = response.data;
                    
                    // Update Overview Stats
                    if (data.metrics.total_execution_time) {
                        $('#execution-time').text(data.metrics.total_execution_time.toFixed(4) + 's');
                    }
                    
                    if (data.metrics.final_memory) {
                        const memoryLimit = <?php echo wp_convert_hr_to_bytes(ini_get('memory_limit')); ?>;
                        const usedMemory = data.metrics.final_memory;
                        const usedPercentage = (usedMemory / memoryLimit) * 100;
                        
                        memoryChart.data.datasets[0].data = [usedPercentage, 100 - usedPercentage];
                        memoryChart.update();
                    }

                    // Update Query Data
                    if (data.metrics.queries) {
                        const queryData = data.metrics.queries.slice(-20); // Last 20 queries
                        queryChart.data.labels = queryData.map(q => q.timestamp);
                        queryChart.data.datasets[0].data = queryData.map(q => q.time);
                        queryChart.update();

                        const queryList = $('#query-list').empty();
                        data.metrics.queries.forEach(query => {
                            queryList.append(`
                                <tr>
                                    <td><code>${escapeHtml(query.query)}</code></td>
                                    <td>${query.time.toFixed(4)}</td>
                                    <td>${query.timestamp}</td>
                                </tr>
                            `);
                        });
                    }

                    // Update HTTP Request Data
                    if (data.metrics.http_requests) {
                        const httpList = $('#http-list').empty();
                        data.metrics.http_requests.forEach(request => {
                            httpList.append(`
                                <tr>
                                    <td>${escapeHtml(request.url)}</td>
                                    <td>${request.time.toFixed(4)}</td>
                                    <td>${request.response_code}</td>
                                    <td>${request.timestamp}</td>
                                </tr>
                            `);
                        });
                    }

                    // Update Cache Data
                    if (data.metrics.cache_operations) {
                        const cacheList = $('#cache-list').empty();
                        const ops = data.metrics.cache_operations;
                        
                        cacheList.append(`
                            <tr><td>Gets</td><td>${ops.gets || 0}</td></tr>
                            <tr><td>Sets</td><td>${ops.sets || 0}</td></tr>
                            <tr><td>Adds</td><td>${ops.adds || 0}</td></tr>
                            <tr><td>Hits</td><td>${ops.hits || 0}</td></tr>
                            <tr><td>Misses</td><td>${ops.misses || 0}</td></tr>
                        `);
                    }
                }
            }
        });
    }

    // Helper function to escape HTML
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Initial update and set interval
    updatePerformanceData();
    setInterval(updatePerformanceData, 30000); // Update every 30 seconds
});
</script> 