/**
 * Debug Log Tools Admin JavaScript
 */
jQuery(document).ready(function($) {
    // Handle log search
    $('#debug-log-search').on('keyup', function() {
        var searchText = $(this).val().toLowerCase();
        var logContent = $('#debug-log-content').text();
        var logLines = logContent.split("\n");
        var filteredLines = [];
        
        // Apply level filter
        var selectedLevel = $('#debug-log-level').val();
        
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
    });
    
    // Handle log level filter
    $('#debug-log-level').on('change', function() {
        $('#debug-log-search').trigger('keyup');
    });
}); 