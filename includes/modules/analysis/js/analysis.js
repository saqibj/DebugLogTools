/**
 * Debug Log Analysis JavaScript
 *
 * @package DebugLogTools
 */

/* global jQuery, Chart, ajaxurl */
jQuery(function($) {
    'use strict';

    const LogAnalysis = {
        charts: {},
        
        init: function() {
            this.bindEvents();
            this.initializeCharts();
            this.refreshAnalysis();
        },

        bindEvents: function() {
            $('#analyze-log').on('click', this.refreshAnalysis.bind(this));
            $('#export-log').on('click', this.exportLog.bind(this));
            $('#date-range').on('change', this.filterByDate.bind(this));
            $('#error-type').on('change', this.filterByType.bind(this));
        },

        initializeCharts: function() {
            // Error type distribution chart
            const typeCtx = document.getElementById('error-type-chart').getContext('2d');
            this.charts.typeChart = new Chart(typeCtx, {
                type: 'pie',
                data: {
                    labels: ['Errors', 'Warnings', 'Notices', 'Info'],
                    datasets: [{
                        data: [0, 0, 0, 0],
                        backgroundColor: [
                            '#dc3545',
                            '#ffc107',
                            '#17a2b8',
                            '#28a745'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // Hourly distribution chart
            const hourlyCtx = document.getElementById('hourly-chart').getContext('2d');
            this.charts.hourlyChart = new Chart(hourlyCtx, {
                type: 'bar',
                data: {
                    labels: Array.from({length: 24}, (_, i) => i),
                    datasets: [{
                        label: 'Errors by Hour',
                        data: Array(24).fill(0),
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

        refreshAnalysis: function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'debug_log_analyze',
                    nonce: debugLogTools.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.updateCharts(response.data);
                        this.updateStats(response.data);
                        this.updateTrends(response.data);
                    } else {
                        this.showError(response.data);
                    }
                },
                error: () => {
                    this.showError('Failed to analyze log file');
                }
            });
        },

        updateCharts: function(data) {
            // Update error type chart
            this.charts.typeChart.data.datasets[0].data = [
                data.stats.by_type.error,
                data.stats.by_type.warning,
                data.stats.by_type.notice,
                data.stats.by_type.info
            ];
            this.charts.typeChart.update();

            // Update hourly chart
            this.charts.hourlyChart.data.datasets[0].data = Object.values(data.stats.by_hour);
            this.charts.hourlyChart.update();
        },

        updateStats: function(data) {
            $('#total-entries').text(data.stats.total);
            $('#error-count').text(data.stats.by_type.error);
            $('#warning-count').text(data.stats.by_type.warning);
            $('#notice-count').text(data.stats.by_type.notice);
        },

        updateTrends: function(data) {
            const $trendsList = $('#trends-list').empty();
            
            // Add most common errors
            data.trends.most_common_errors.forEach(error => {
                $trendsList.append(`
                    <li class="trend-item">
                        <strong>${error.count}x:</strong> ${this.escapeHtml(error.message)}
                    </li>
                `);
            });

            // Add peak hours
            const peakHours = Object.entries(data.trends.peak_hours)
                .map(([hour, count]) => `${hour}:00 (${count} entries)`);
            
            $('#peak-hours').text(peakHours.join(', '));

            // Show recent increase warning if applicable
            if (data.trends.recent_increase) {
                $('#recent-increase').removeClass('d-none');
            } else {
                $('#recent-increase').addClass('d-none');
            }
        },

        exportLog: function() {
            const format = $('#export-format').val();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'debug_log_export',
                    format: format,
                    nonce: debugLogTools.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.downloadFile(response.data.content, response.data.filename);
                    } else {
                        this.showError(response.data);
                    }
                },
                error: () => {
                    this.showError('Failed to export log file');
                }
            });
        },

        downloadFile: function(content, filename) {
            const blob = new Blob([content], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            
            a.style.display = 'none';
            a.href = url;
            a.download = filename;
            
            document.body.appendChild(a);
            a.click();
            
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        },

        filterByDate: function() {
            const range = $('#date-range').val();
            const now = new Date();
            let threshold;

            switch (range) {
                case '1h':
                    threshold = now.getTime() - (60 * 60 * 1000);
                    break;
                case '24h':
                    threshold = now.getTime() - (24 * 60 * 60 * 1000);
                    break;
                case '7d':
                    threshold = now.getTime() - (7 * 24 * 60 * 60 * 1000);
                    break;
                case '30d':
                    threshold = now.getTime() - (30 * 24 * 60 * 60 * 1000);
                    break;
                default:
                    threshold = 0;
            }

            this.filterEntries(entry => entry.timestamp * 1000 >= threshold);
        },

        filterByType: function() {
            const type = $('#error-type').val();
            if (type === 'all') {
                this.refreshAnalysis();
            } else {
                this.filterEntries(entry => entry.type === type);
            }
        },

        filterEntries: function(filterFn) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'debug_log_analyze',
                    nonce: debugLogTools.nonce
                },
                success: (response) => {
                    if (response.success) {
                        const filteredData = {
                            entries: response.data.entries.filter(filterFn),
                            stats: this.calculateStats(response.data.entries.filter(filterFn)),
                            trends: this.analyzeTrends(response.data.entries.filter(filterFn))
                        };
                        this.updateCharts(filteredData);
                        this.updateStats(filteredData);
                        this.updateTrends(filteredData);
                    }
                }
            });
        },

        calculateStats: function(entries) {
            // Simplified version of PHP calculate_statistics
            const stats = {
                total: entries.length,
                by_type: {
                    error: 0,
                    warning: 0,
                    notice: 0,
                    info: 0
                },
                by_hour: Array(24).fill(0)
            };

            entries.forEach(entry => {
                stats.by_type[entry.type]++;
                const hour = new Date(entry.timestamp * 1000).getHours();
                stats.by_hour[hour]++;
            });

            return stats;
        },

        analyzeTrends: function(entries) {
            // Simplified version of PHP analyze_trends
            const trends = {
                most_common_errors: [],
                peak_hours: {},
                recent_increase: false
            };

            // Calculate most common errors
            const errorCounts = {};
            entries.forEach(entry => {
                if (entry.type === 'error') {
                    const key = entry.message;
                    errorCounts[key] = (errorCounts[key] || 0) + 1;
                }
            });

            trends.most_common_errors = Object.entries(errorCounts)
                .map(([message, count]) => ({ message, count }))
                .sort((a, b) => b.count - a.count)
                .slice(0, 5);

            // Calculate peak hours
            const hourCounts = this.calculateStats(entries).by_hour;
            trends.peak_hours = Object.fromEntries(
                Object.entries(hourCounts)
                    .sort(([,a], [,b]) => b - a)
                    .slice(0, 3)
            );

            return trends;
        },

        showError: function(message) {
            const $alert = $('<div>')
                .addClass('notice notice-error')
                .html(`<p>${this.escapeHtml(message)}</p>`);

            $('.debug-log-analysis-wrap').prepend($alert);
            
            setTimeout(() => {
                $alert.fadeOut(() => $alert.remove());
            }, 5000);
        },

        escapeHtml: function(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    };

    // Initialize the module
    LogAnalysis.init();
}); 