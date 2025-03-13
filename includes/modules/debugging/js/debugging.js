/**
 * Debug Log Debugging JavaScript
 *
 * @package DebugLogTools
 */

/* global jQuery, debugLogDebugging */
jQuery(function($) {
    'use strict';

    const Debugging = {
        charts: {},
        refreshInterval: null,

        init: function() {
            this.bindEvents();
            this.initializeCharts();
            this.startAutoRefresh();
        },

        bindEvents: function() {
            $('#refresh-debug').on('click', this.refreshData.bind(this));
            $('#auto-refresh').on('change', this.toggleAutoRefresh.bind(this));
            $('#clear-data').on('click', this.clearData.bind(this));
            
            // Tab switching
            $('.debug-tab-link').on('click', function(e) {
                e.preventDefault();
                const target = $(this).data('target');
                this.switchTab(target);
            }.bind(this));
        },

        initializeCharts: function() {
            // Memory usage chart
            const memoryCtx = document.getElementById('memory-chart').getContext('2d');
            this.charts.memoryChart = new Chart(memoryCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Used', 'Available'],
                    datasets: [{
                        data: [0, 100],
                        backgroundColor: ['#dc3545', '#28a745']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // Query time distribution chart
            const queryCtx = document.getElementById('query-chart').getContext('2d');
            this.charts.queryChart = new Chart(queryCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Query Execution Time (s)',
                        data: [],
                        backgroundColor: '#007bff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },

        refreshData: function() {
            this.getMemoryUsage();
            this.getBacktraces();
            this.getQueries();
        },

        getMemoryUsage: function() {
            $.ajax({
                url: debugLogDebugging.ajaxurl,
                type: 'POST',
                data: {
                    action: 'debug_log_memory_usage',
                    nonce: debugLogDebugging.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateMemoryChart(response.data);
                    }
                }
            });
        },

        updateMemoryChart: function(data) {
            const used = (data.current / data.wp_limit) * 100;
            const available = 100 - used;

            this.charts.memoryChart.data.datasets[0].data = [used, available];
            this.charts.memoryChart.update();

            $('#memory-current').text(this.formatBytes(data.current));
            $('#memory-peak').text(this.formatBytes(data.peak));
            $('#memory-limit').text(this.formatBytes(data.wp_limit));
            
            if (used > 80) {
                $('#memory-warning').removeClass('d-none');
            } else {
                $('#memory-warning').addClass('d-none');
            }
        },

        getBacktraces: function() {
            $.ajax({
                url: debugLogDebugging.ajaxurl,
                type: 'POST',
                data: {
                    action: 'debug_log_backtrace',
                    nonce: debugLogDebugging.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateBacktraces(response.data);
                    }
                }
            });
        },

        updateBacktraces: function(data) {
            const $container = $('#backtrace-list').empty();
            
            data.forEach(trace => {
                const $trace = $('<div>')
                    .addClass('backtrace-item')
                    .append(
                        $('<div>')
                            .addClass('backtrace-error')
                            .text(trace.error)
                    )
                    .append(
                        $('<pre>')
                            .addClass('backtrace-details')
                            .text(trace.trace.join('\n'))
                    );
                
                $container.append($trace);
            });
        },

        getQueries: function() {
            $.ajax({
                url: debugLogDebugging.ajaxurl,
                type: 'POST',
                data: {
                    action: 'debug_log_query_monitor',
                    nonce: debugLogDebugging.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateQueries(response.data);
                    }
                }
            });
        },

        updateQueries: function(data) {
            const $container = $('#query-list').empty();
            
            // Update chart
            this.charts.queryChart.data.labels = data.map(q => q.timestamp);
            this.charts.queryChart.data.datasets[0].data = data.map(q => q.execution_time);
            this.charts.queryChart.update();
            
            // Update list
            data.forEach(query => {
                const $query = $('<div>')
                    .addClass('query-item')
                    .append(
                        $('<div>')
                            .addClass('query-time')
                            .text(`${query.execution_time.toFixed(2)}s`)
                    )
                    .append(
                        $('<div>')
                            .addClass('query-timestamp')
                            .text(query.timestamp)
                    )
                    .append(
                        $('<pre>')
                            .addClass('query-sql')
                            .text(this.formatSQL(query.query))
                    );
                
                $container.append($query);
            });
        },

        formatSQL: function(sql) {
            // Basic SQL formatting
            return sql
                .replace(/\s+/g, ' ')
                .replace(/\(\s*/g, '(')
                .replace(/\s*\)/g, ')')
                .replace(/\s*=\s*/g, ' = ')
                .replace(/\s*,\s*/g, ', ')
                .replace(/\s+AND\s+/gi, '\nAND ')
                .replace(/\s+OR\s+/gi, '\nOR ')
                .replace(/\s+WHERE\s+/gi, '\nWHERE ')
                .replace(/\s+FROM\s+/gi, '\nFROM ')
                .replace(/\s+LEFT JOIN\s+/gi, '\nLEFT JOIN ')
                .replace(/\s+INNER JOIN\s+/gi, '\nINNER JOIN ')
                .replace(/\s+ORDER BY\s+/gi, '\nORDER BY ')
                .replace(/\s+GROUP BY\s+/gi, '\nGROUP BY ')
                .replace(/\s+LIMIT\s+/gi, '\nLIMIT ')
                .trim();
        },

        formatBytes: function(bytes) {
            const units = ['B', 'KB', 'MB', 'GB'];
            let size = bytes;
            let unit = 0;
            
            while (size >= 1024 && unit < units.length - 1) {
                size /= 1024;
                unit++;
            }
            
            return `${size.toFixed(2)} ${units[unit]}`;
        },

        switchTab: function(target) {
            $('.debug-tab-content').hide();
            $(target).show();
            $('.debug-tab-link').removeClass('active');
            $(`[data-target="${target}"]`).addClass('active');
        },

        startAutoRefresh: function() {
            if ($('#auto-refresh').is(':checked')) {
                this.refreshInterval = setInterval(this.refreshData.bind(this), 30000);
            }
        },

        toggleAutoRefresh: function() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
                this.refreshInterval = null;
            }
            
            if ($('#auto-refresh').is(':checked')) {
                this.startAutoRefresh();
            }
        },

        clearData: function() {
            $('#backtrace-list').empty();
            $('#query-list').empty();
            this.charts.queryChart.data.labels = [];
            this.charts.queryChart.data.datasets[0].data = [];
            this.charts.queryChart.update();
        }
    };

    // Initialize debugging features
    Debugging.init();
}); 