/**
 * Debug Log Tools Frontend JavaScript
 *
 * @package DebugLogTools
 */

/* global jQuery, ajaxurl, debugLogTools */

jQuery( function( $ ) {
    'use strict';
    
    let refreshInterval;
    
    /**
     * Handle auto-refresh toggle
     */
    $( '#auto-refresh-toggle' ).on( 'change', function() {
        if ( $( this ).is( ':checked' ) ) {
            refreshInterval = setInterval( refreshLog, 30000 );
        } else {
            clearInterval( refreshInterval );
        }
    } );

    /**
     * Refresh log content via AJAX
     */
    function refreshLog() {
        $.ajax( {
            url: ajaxurl,
            data: {
                action: 'debug_log_tools_refresh',
                nonce: debugLogTools.nonce
            },
            success: function( response ) {
                if ( response.success ) {
                    $( '.debug-log-content' ).html( response.data );
                }
            }
        } );
    }
} );
