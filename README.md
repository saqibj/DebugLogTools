# Debug Log Tools

**Version:** 3.1.0  
**Author:** [Saqib Jawaid](https://github.com/saqibj)  
**License:** GPL-3.0  
**Tested up to WordPress 6.x**

## Description

Debug Log Tools is a comprehensive WordPress debugging and troubleshooting plugin designed for developers and administrators. It provides an intuitive interface for managing WordPress debug logs and performing system diagnostics directly from your dashboard.

The plugin simplifies the debugging process by offering real-time log viewing, advanced filtering, and automatic refresh capabilities. It also includes powerful troubleshooting tools that help identify common WordPress issues, analyze system configurations, and monitor plugin compatibility.

### Key Benefits:
- **Simplified Debugging:** View and manage debug logs without leaving WordPress
- **Real-time Monitoring:** Auto-refresh and live search capabilities
- **System Diagnostics:** Comprehensive system information and issue detection
- **Safe Configuration:** Automatic handling of wp-config.php debug settings
- **Performance Optimized:** Safe handling of large log files and efficient processing
- **Developer Friendly:** Detailed environment information and plugin diagnostics

Whether you're debugging a plugin conflict, tracking down a performance issue, or performing routine maintenance, Debug Log Tools provides the essential features needed for effective WordPress troubleshooting.

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
- **System Information:** View detailed WordPress and server configuration
- **Issue Detection:** Automatically detect common WordPress problems
- **Plugin Overview:** List of active plugins and their versions
- **Environment Checks:** Verify PHP settings and file permissions

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
6. Use the troubleshooting tab to view system information and diagnose issues.

## Security

This plugin includes the following security measures:
- **Capability Check:** Only administrators can view or flush the log.
- **Nonce Verification:** All actions require nonce verification to prevent CSRF attacks.
- **Input Sanitization:** All outputs are properly escaped.
- **File Size Protection:** Large files are handled safely to prevent memory issues.

## Changelog

### 3.1.0
- Added advanced debugging module with memory usage monitoring
- Added query performance monitoring and visualization
- Added error backtrace visualization
- Added security monitoring module with event logging
- Added notifications module for error alerts
- Added performance monitoring module
- Added modular architecture for better extensibility
- Added dark mode support for all modules
- Improved UI/UX with modern design
- Enhanced error tracking and logging
- Added real-time data visualization with charts
- Added support for email and Slack notifications
- Added security event monitoring and alerts
- Added performance metrics tracking
- Improved code organization and maintainability

### 3.0.2
- Improved error handling for wp-config.php modifications
- Added better debug log file handling and creation
- Enhanced multisite compatibility
- Added proper cleanup of temporary files
- Improved security with better escaping
- Added more detailed error messages
- Fixed potential file permission issues
- Added debug.log file existence and permission checks
- Added proper plugin data validation
- Improved system information display

### 3.0.1
- Added auto-refresh functionality with 30-second intervals
- Implemented real-time search/filter capability
- Added large file handling (1MB limit with warning)
- Added dark mode support
- Added comprehensive troubleshooting features:
  - System information display
  - Common issue detection
  - Active plugin overview
  - Environment checks
  - PHP configuration analysis
  - File permission scanning
- Added debug logging management:
  - Enable/disable debug logging from admin interface
  - Automatic wp-config.php management
  - Status indicators for debug settings
- Improved error handling for file operations
- Enhanced UI with better control organization
- Added AJAX-based updates for smoother operation
- Added tabbed interface for log and troubleshoot views

### 3.0.0
- Initial public release
- Basic log viewing functionality
- Enable/disable debug logging
- Log flushing capability
- Admin interface integration

## License

This plugin is licensed under the GPL-3.0 License. See the `LICENSE` file for more details.
