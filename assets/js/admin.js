/**
 * Debug Log Tools Admin JavaScript
 */
jQuery(document).ready(function($) {
    // Auto-refresh log content every 30 seconds
    var refreshInterval;
    
    function startAutoRefresh() {
        refreshInterval = setInterval(refreshLogContent, 30000);
    }
    
    function stopAutoRefresh() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    }
    
    function refreshLogContent() {
        $.ajax({
            url: debugLogTools.ajaxurl,
            type: 'POST',
            data: {
                action: 'debug_log_tools_refresh',
                nonce: debugLogTools.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#debug-log-content').text(response.data);
                    // Store as original content for filtering
                    $('#debug-log-content').data('original-content', response.data);
                    // Reapply filters if any are active
                    if ($('#debug-log-search').val() || 
                        $('#debug-log-level').val() !== 'all' || 
                        $('#debug-log-plugin').val() !== 'all') {
                        filterLog();
                    }
                }
            }
        });
    }

    // Start auto-refresh when page loads
    startAutoRefresh();

    // Handle flush log button click
    $('#flush-log-button').on('click', function(e) {
        if (!confirm(debugLogTools.i18n.confirmFlush)) {
            e.preventDefault();
            return;
        }
        
        stopAutoRefresh(); // Stop auto-refresh during operation
        $.ajax({
            url: debugLogTools.ajaxurl,
            type: 'POST',
            data: {
                action: 'debug_log_tools_flush',
                nonce: debugLogTools.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#debug-log-content').empty().text('');
                    $('#debug-log-content').data('original-content', '');
                    showNotice('success', debugLogTools.i18n.flushSuccess);
                } else {
                    showNotice('error', response.data);
                }
                startAutoRefresh(); // Restart auto-refresh
            },
            error: function() {
                showNotice('error', debugLogTools.i18n.flushError);
                startAutoRefresh(); // Restart auto-refresh
            }
        });
    });

    // Handle clear all log button click
    $('#clear-all-log').on('click', function(e) {
        if (!confirm(debugLogTools.i18n.confirmClear)) {
            e.preventDefault();
            return;
        }
        
        stopAutoRefresh(); // Stop auto-refresh during operation
        $.ajax({
            url: debugLogTools.ajaxurl,
            type: 'POST',
            data: {
                action: 'debug_log_tools_clear',
                nonce: debugLogTools.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#debug-log-content').empty().text('');
                    $('#debug-log-content').data('original-content', '');
                    showNotice('success', debugLogTools.i18n.clearSuccess);
                } else {
                    showNotice('error', response.data);
                }
                startAutoRefresh(); // Restart auto-refresh
            },
            error: function() {
                showNotice('error', debugLogTools.i18n.clearError);
                startAutoRefresh(); // Restart auto-refresh
            }
        });
    });

    // Handle clear filtered entries button click
    $('#clear-filtered-log').on('click', function(e) {
        if (!confirm(debugLogTools.i18n.confirmClearFiltered)) {
            e.preventDefault();
            return;
        }
        
        stopAutoRefresh(); // Stop auto-refresh during operation
        var currentContent = $('#debug-log-content').text();
        var originalContent = $('#debug-log-content').data('original-content');
        var newContent = originalContent.split('\n').filter(function(line) {
            return !currentContent.includes(line);
        }).join('\n');
        
        $.ajax({
            url: debugLogTools.ajaxurl,
            type: 'POST',
            data: {
                action: 'debug_log_tools_update',
                nonce: debugLogTools.nonce,
                content: newContent
            },
            success: function(response) {
                if (response.success) {
                    $('#debug-log-content').text(newContent).data('original-content', newContent);
                    // Reset filters
                    $('#debug-log-search').val('');
                    $('#debug-log-level').val('all');
                    $('#debug-log-plugin').val('all');
                    // Hide the clear filtered button
                    $('#clear-filtered-log').hide();
                    $('#clear-all-log').show();
                    showNotice('success', debugLogTools.i18n.clearFilteredSuccess);
                } else {
                    showNotice('error', response.data);
                }
                startAutoRefresh(); // Restart auto-refresh
            },
            error: function() {
                showNotice('error', debugLogTools.i18n.clearFilteredError);
                startAutoRefresh(); // Restart auto-refresh
            }
        });
    });

    // Show notification message
    function showNotice(type, message) {
        var notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after(notice);
        setTimeout(function() {
            notice.fadeOut(function() {
                notice.remove();
            });
        }, 3000);
    }

    // Handle log search and filtering
    function filterLog() {
        var searchText = $('#debug-log-search').val().toLowerCase();
        var selectedLevel = $('#debug-log-level').val();
        var selectedPlugin = $('#debug-log-plugin').val();
        var logContent = $('#debug-log-content').data('original-content') || $('#debug-log-content').text();
        
        // Store original content if not already stored
        if (!$('#debug-log-content').data('original-content')) {
            $('#debug-log-content').data('original-content', logContent);
        }

        var logLines = logContent.split("\n");
        var filteredLines = [];
        var hasFilters = searchText || selectedLevel !== 'all' || selectedPlugin !== 'all';
        
        for (var i = 0; i < logLines.length; i++) {
            var line = logLines[i];
            
            // Skip empty lines
            if (line.trim() === '') continue;
            
            // Apply search filter
            if (searchText && line.toLowerCase().indexOf(searchText) === -1) {
                continue;
            }
            
            // Apply level filter
            if (selectedLevel !== 'all') {
                var levelMatch = false;
                
                switch (selectedLevel) {
                    case 'error':
                        levelMatch = line.indexOf('Fatal error') !== -1 || 
                                   line.indexOf('PHP Error') !== -1 || 
                                   line.indexOf('PHP Exception') !== -1;
                        break;
                    case 'warning':
                        levelMatch = line.indexOf('Warning') !== -1;
                        break;
                    case 'notice':
                        levelMatch = line.indexOf('Notice') !== -1 || 
                                   line.indexOf('Deprecated') !== -1;
                        break;
                }
                
                if (!levelMatch) continue;
            }

            // Apply plugin filter
            if (selectedPlugin !== 'all') {
                // Extract plugin path from the log line
                var pluginMatch = line.match(/\/plugins\/([^\/]+)/);
                if (!pluginMatch || pluginMatch[1] !== selectedPlugin) {
                    continue;
                }
            }
            
            // Style different log levels
            if (line.indexOf('Fatal error') !== -1 || 
                line.indexOf('PHP Error') !== -1 || 
                line.indexOf('PHP Exception') !== -1) {
                line = '<span class="error">' + line + '</span>';
            } else if (line.indexOf('Warning') !== -1) {
                line = '<span class="warning">' + line + '</span>';
            } else if (line.indexOf('Notice') !== -1 || line.indexOf('Deprecated') !== -1) {
                line = '<span class="notice">' + line + '</span>';
            }
            
            filteredLines.push(line);
        }
        
        // Update displayed log
        if (filteredLines.length > 0) {
            $('#debug-log-content').html(filteredLines.join("\n"));
        } else {
            $('#debug-log-content').html('<em>No log entries match your filter.</em>');
        }

        // Show/hide clear buttons based on filter state
        if (hasFilters && filteredLines.length > 0) {
            $('#clear-filtered-log').show();
            $('#clear-all-log').hide();
        } else {
            $('#clear-filtered-log').hide();
            $('#clear-all-log').show();
        }
    }
    
    // Bind events to filter function
    $('#debug-log-search').on('keyup', filterLog);
    $('#debug-log-level').on('change', filterLog);
    $('#debug-log-plugin').on('change', filterLog);

    // Handle download button click
    $('#download-log').on('click', function(e) {
        // The download will be handled by the server-side code
        // Just stop auto-refresh temporarily
        stopAutoRefresh();
        setTimeout(startAutoRefresh, 1000); // Resume after 1 second
    });

    // Add confirmation for Enable/Disable toggle
    const debugToggleForm = document.querySelector('form[action*="debug_log_tools_toggle"]');
    if (debugToggleForm) {
        debugToggleForm.addEventListener('submit', function(e) {
            const isEnabled = document.querySelector('[name="enable_debug_log"]').checked;
            const message = isEnabled 
                ? 'Are you sure you want to ENABLE debug logging? This will start recording all debug information.'
                : 'Are you sure you want to DISABLE debug logging? This will stop recording debug information.';
            
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    }
}); 