jQuery(document).ready(function($) {

    // Handle View Log button click
    $('#dlt-view-log').on('click', function() {
        var plugin = $('#dlt-plugin-filter').val();

        $.ajax({
            url: DebugLogTools.ajax_url,
            method: 'POST',
            data: {
                action: 'dlt_get_log_entries',
                nonce: DebugLogTools.nonce,
                plugin: plugin
            },
            beforeSend: function() {
                $('#dlt-log-viewer').html('<p>Loading...</p>');
            },
            success: function(response) {
                if (response.success) {
                    var logEntries = response.data.entries;
                    var logHtml = '<pre>' + logEntries.join('') + '</pre>';
                    $('#dlt-log-viewer').html(logHtml);
                } else {
                    $('#dlt-log-viewer').html('<p>' + response.data.message + '</p>');
                }
            },
            error: function() {
                $('#dlt-log-viewer').html('<p>An error occurred while fetching the log entries.</p>');
            }
        });
    });

    // Handle Flush Log button click
    $('#dlt-flush-log').on('click', function() {
        if (!confirm('Are you sure you want to flush the debug log?')) {
            return;
        }

        $.ajax({
            url: DebugLogTools.ajax_url,
            method: 'POST',
            data: {
                action: 'dlt_flush_log',
                nonce: DebugLogTools.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    $('#dlt-log-viewer').html('');
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('An error occurred while flushing the log.');
            }
        });
    });

});
