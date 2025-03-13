/**
 * Debug Log Security Module JavaScript
 *
 * @package DebugLogTools
 */

/* global ajaxurl */

const Security = {
    /**
     * Initialize security module
     */
    init: function() {
        this.bindEvents();
        this.loadData();
        this.setupAutoRefresh();
    },

    /**
     * Bind event handlers
     */
    bindEvents: function() {
        // Tab switching
        document.querySelectorAll('.nav-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                this.switchTab(e.target.getAttribute('href').substring(1));
            });
        });

        // Event filtering
        document.getElementById('apply-event-filter').addEventListener('click', () => {
            this.filterEvents();
        });

        // Refresh buttons
        document.getElementById('refresh-events').addEventListener('click', () => {
            this.loadData();
        });

        document.getElementById('refresh-alerts').addEventListener('click', () => {
            this.loadData();
        });
    },

    /**
     * Switch between tabs
     *
     * @param {string} tabId Tab ID to switch to
     */
    switchTab: function(tabId) {
        // Update tab buttons
        document.querySelectorAll('.nav-tab').forEach(tab => {
            tab.classList.remove('nav-tab-active');
            if (tab.getAttribute('href') === '#' + tabId) {
                tab.classList.add('nav-tab-active');
            }
        });

        // Update tab content
        document.querySelectorAll('.tab-pane').forEach(pane => {
            pane.classList.remove('active');
        });
        document.getElementById(tabId).classList.add('active');
    },

    /**
     * Load security data via AJAX
     */
    loadData: function() {
        const nonce = document.getElementById('debug_log_security_nonce').value;

        fetch(ajaxurl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'debug_log_security_data',
                _ajax_nonce: nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateEvents(data.data.events);
                this.updateAlerts(data.data.alerts);
                this.updateStats(data.data.events, data.data.alerts);
            }
        })
        .catch(error => {
            console.error('Error loading security data:', error);
        });
    },

    /**
     * Update events table
     *
     * @param {Array} events Security events
     */
    updateEvents: function(events) {
        const tbody = document.getElementById('security-events');
        const filter = document.getElementById('event-type-filter').value;
        
        // Filter events if needed
        if (filter) {
            events = events.filter(event => event.type.includes(filter));
        }

        // Clear existing rows
        tbody.innerHTML = '';

        // Add event rows
        events.forEach(event => {
            const row = document.createElement('tr');
            
            const timeCell = document.createElement('td');
            timeCell.textContent = this.formatDate(event.timestamp);
            row.appendChild(timeCell);

            const typeCell = document.createElement('td');
            typeCell.textContent = this.formatEventType(event.type);
            row.appendChild(typeCell);

            const messageCell = document.createElement('td');
            messageCell.textContent = event.message;
            row.appendChild(messageCell);

            const ipCell = document.createElement('td');
            ipCell.textContent = event.ip || '-';
            row.appendChild(ipCell);

            tbody.appendChild(row);
        });
    },

    /**
     * Update alerts table
     *
     * @param {Array} alerts Security alerts
     */
    updateAlerts: function(alerts) {
        const tbody = document.getElementById('security-alerts');
        
        // Clear existing rows
        tbody.innerHTML = '';

        // Add alert rows
        alerts.forEach(alert => {
            const row = document.createElement('tr');
            
            const timeCell = document.createElement('td');
            timeCell.textContent = this.formatDate(alert.timestamp);
            row.appendChild(timeCell);

            const typeCell = document.createElement('td');
            typeCell.textContent = this.formatAlertType(alert.type);
            row.appendChild(typeCell);

            const messageCell = document.createElement('td');
            messageCell.textContent = alert.message;
            row.appendChild(messageCell);

            tbody.appendChild(row);
        });
    },

    /**
     * Update statistics
     *
     * @param {Array} events Security events
     * @param {Array} alerts Security alerts
     */
    updateStats: function(events, alerts) {
        const now = new Date();
        const oneDayAgo = new Date(now - 24 * 60 * 60 * 1000);

        const recentEvents = events.filter(event => {
            return new Date(event.timestamp) > oneDayAgo;
        });

        document.querySelector('.stat-box:nth-child(1) .stat-number').textContent = recentEvents.length;
        document.querySelector('.stat-box:nth-child(2) .stat-number').textContent = events.length;
        document.querySelector('.stat-box:nth-child(3) .stat-number').textContent = alerts.length;
    },

    /**
     * Filter events based on selected type
     */
    filterEvents: function() {
        const filter = document.getElementById('event-type-filter').value;
        const rows = document.querySelectorAll('#security-events tr');

        rows.forEach(row => {
            const type = row.querySelector('td:nth-child(2)').textContent;
            if (!filter || type.toLowerCase().includes(filter.toLowerCase())) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    },

    /**
     * Format date for display
     *
     * @param {string} dateString Date string
     * @return {string} Formatted date
     */
    formatDate: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString();
    },

    /**
     * Format event type for display
     *
     * @param {string} type Event type
     * @return {string} Formatted event type
     */
    formatEventType: function(type) {
        return type.split('_').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
    },

    /**
     * Format alert type for display
     *
     * @param {string} type Alert type
     * @return {string} Formatted alert type
     */
    formatAlertType: function(type) {
        return type.split('_').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
    },

    /**
     * Set up auto-refresh
     */
    setupAutoRefresh: function() {
        setInterval(() => {
            this.loadData();
        }, 30000); // Refresh every 30 seconds
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    Security.init();
}); 