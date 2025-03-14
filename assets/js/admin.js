/**
 * Debug Log Tools Admin JavaScript
 */
jQuery(document).ready(function($) {
    // Handle flush log button click
    $('#flush-log-button').on('click', function(e) {
        e.preventDefault();
        if (confirm(debugLogTools.i18n.confirmFlush)) {
            $('#flush-log-form').submit();
        }
    });

    // Handle clear filtered entries button click
    $('#clear-filtered-log').on('click', function(e) {
        e.preventDefault();
        if (confirm(debugLogTools.i18n.confirmClearFiltered)) {
            var currentContent = $('#debug-log-content').text();
            var originalContent = $('#debug-log-content').data('original-content');
            var newContent = originalContent.split('\n').filter(function(line) {
                return !currentContent.includes(line);
            }).join('\n');
            
            // Save the new content back to the log file
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
                        // Update the displayed content and original content
                        $('#debug-log-content').text(newContent).data('original-content', newContent);
                        // Reset filters
                        $('#debug-log-search').val('');
                        $('#debug-log-level').val('all');
                        $('#debug-log-plugin').val('all');
                        // Hide the clear filtered button
                        $('#clear-filtered-log').hide();
                        $('#clear-all-log').show();
                        // Show success message
                        showNotice('success', debugLogTools.i18n.clearFilteredSuccess);
                    } else {
                        showNotice('error', response.data);
                    }
                },
                error: function() {
                    showNotice('error', debugLogTools.i18n.clearFilteredError);
                }
            });
        }
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
}); 