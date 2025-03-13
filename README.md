# Debug Log Tools

A comprehensive WordPress plugin for managing, analyzing, and monitoring debug logs.

## Description

Debug Log Tools provides a suite of tools to help WordPress developers and site administrators manage and analyze debug logs directly from the WordPress admin dashboard. It simplifies the process of monitoring errors, warnings, and notices, helping you maintain a healthier WordPress site.

## Features

### Modular Architecture
The plugin uses a modular approach, allowing you to enable only the features you need:

- **Debugging Module**: View and analyze debug logs directly from your WordPress dashboard.
- **Notifications Module**: Receive alerts when critical errors occur in your debug logs.
- **Performance Module**: Monitor site performance metrics and identify bottlenecks.
- **Security Module**: Track security-related events and monitor suspicious activities.

### Core Functionality

- **Debug Log Viewer**: Easy-to-use interface for viewing, filtering, and analyzing debug logs.
- **Error Classification**: Automatically categorizes errors by type and severity.
- **Performance Tracking**: Monitor memory usage, script execution time, and database queries.
- **Security Monitoring**: Track failed login attempts and file integrity.
- **Customizable Settings**: Configure which features to enable and how they should behave.

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher

## Installation

1. Upload the `debug-log-tools` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the settings through the 'Debug Log Tools' menu in WordPress admin

## Usage

### Viewing Debug Logs

1. Navigate to "Debug Log" in your WordPress admin menu
2. View, filter, and analyze logs directly in your browser
3. Use the search functionality to find specific errors

### Managing Modules

1. Go to "Tools" → "Debug Log Modules"
2. Enable or disable individual modules based on your needs
3. Configure module-specific settings through their respective pages

### Setting Up Notifications

1. Go to "Tools" → "Notifications"
2. Configure notification settings for debug log events
3. Choose notification channels (email, admin notice, etc.)

### Monitoring Performance

1. Go to "Tools" → "Performance"
2. View current performance metrics
3. Analyze historical performance data

### Security Monitoring

1. Go to "Tools" → "Security"
2. View failed login attempts and other security events
3. Configure security monitoring settings

## Frequently Asked Questions

### Does this plugin modify my wp-config.php file?

The plugin may suggest changes to your wp-config.php file to enable debugging, but it will always ask for confirmation before making any changes.

### Will this plugin slow down my site?

Debug Log Tools is designed to have minimal impact on site performance. However, enabling extensive logging and monitoring features may have a slight performance impact. You can selectively enable only the features you need.

### Is this plugin suitable for production sites?

Yes, but we recommend using it cautiously on production sites. Enable only the necessary features and configure them appropriately for your production environment.

## Screenshots

1. Debug Log Viewer
2. Module Management
3. Performance Monitoring Dashboard
4. Security Events Overview

## Changelog

### 3.1.1
- Fixed module loading issues
- Improved module management
- Added performance and security modules
- Enhanced user interface

### 3.0.0
- Complete rewrite with modular architecture
- Added notification system
- Improved performance monitoring
- Enhanced security features

## Support

For support requests, please create an issue on the [GitHub repository](https://github.com/saqibj/debug-log-tools).

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This plugin is licensed under the GPL v3 or later.

## Credits

Developed by Saqib Jawaid
