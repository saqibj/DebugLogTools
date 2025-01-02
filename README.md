# Debug Log Tools

**Version:** 3.0.1  
**Author:** [Saqib Jawaid](https://github.com/saqibj)  
**License:** GPL-3.0  
**Tested up to WordPress 6.x**

## Description

Debug Log Tools is a WordPress plugin that provides administrators with an easy-to-use interface for viewing, filtering, enabling/disabling, and flushing the debug log file. It simplifies troubleshooting by allowing you to manage logs directly from the WordPress dashboard.

## Features

- **View Debug Log:** Displays the contents of `debug.log` for troubleshooting.
- **Enable/Disable Debug Logging:** Allows administrators to toggle debug logging on and off.
- **Flush Log:** Clears the debug log with a simple button click.
- **Auto-Refresh:** Optional 30-second auto-refresh of log contents.
- **Search/Filter:** Real-time search functionality for log contents.
- **Large File Handling:** Safely handles large log files by displaying the most recent 1MB of data.
- **Dark Mode Support:** Automatic dark mode detection and styling.
- **Debug Control:** Enable or disable WordPress debug logging directly from the admin interface
- **Config File Management:** Automatically updates wp-config.php with appropriate debug settings

## Installation

1. Upload the `debug-log-tools` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to 'Tools' -> 'Debug Log Tools' to access the plugin.

## Usage

1. Go to 'Tools' -> 'Debug Log Tools' in the WordPress admin dashboard.
2. To enable or disable debug logging, check or uncheck the "Enable Debug Logging" checkbox and save.
3. To flush the debug log, click the "Flush Log" button.
4. Use the search box to filter log contents in real-time.
5. Enable auto-refresh to automatically update the log view every 30 seconds.

## Security

This plugin includes the following security measures:
- **Capability Check:** Only administrators can view or flush the log.
- **Nonce Verification:** All actions require nonce verification to prevent CSRF attacks.
- **Input Sanitization:** All outputs are properly escaped.
- **File Size Protection:** Large files are handled safely to prevent memory issues.

## Changelog

### 3.0.1
- Added auto-refresh functionality with 30-second intervals
- Implemented real-time search/filter capability
- Added large file handling (1MB limit with warning)
- Added dark mode support
- Improved error handling for file operations
- Enhanced UI with better control organization
- Added AJAX-based updates for smoother operation
- Added ability to enable/disable debug logging from admin interface
- Added automatic wp-config.php management

### 3.0.0
- Initial public release

## License

This plugin is licensed under the GPL-3.0 License. See the `LICENSE` file for more details.
