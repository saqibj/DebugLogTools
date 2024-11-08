// JavaScript for Debug Log Tools Plugin - Version 3.0.0

// Ensure jQuery is loaded and run after the DOM is ready
jQuery(document).ready(function($) {
    // Dismiss admin notice when clicked
    $('.notice.is-dismissible').on('click', '.notice-dismiss', function() {
        $(this).closest('.notice').fadeOut();
    });
});
