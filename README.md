# Debug Log Tools

A powerful WordPress plugin for managing and analyzing debug logs. Provides advanced logging features, log rotation, and troubleshooting tools.

[![Version](https://img.shields.io/badge/version-3.2.1-blue.svg?style=flat-square)](https://github.com/saqibj/debug-log-tools)
[![License](https://img.shields.io/badge/license-GPL--3.0-blue.svg?style=flat-square)](https://www.gnu.org/licenses/gpl-3.0.txt)
[![WordPress](https://img.shields.io/badge/WordPress-Plugin-0073aa.svg?style=flat-square&logo=wordpress)](https://wordpress.org/)
[![PHP Version Required](https://img.shields.io/badge/php-%3E%3D7.4-8892BF.svg?style=flat-square)](https://php.net/)

**View, filter, and manage WordPress debug logs from your dashboard.**

Current Version: 3.2.1

## Description

Debug Log Tools provides a user-friendly interface for WordPress administrators to view, filter, and manage debug logs without leaving the WordPress dashboard. The plugin offers features like real-time log viewing, filtering by log level or plugin, and easy log management options.

## Features

- View and filter debug logs from the WordPress dashboard
- Live log tailing with real-time updates
- Advanced log filtering and searching
- Automatic log rotation and cleanup
- Performance monitoring and optimization
- Security scanning and monitoring
- Email notifications for critical errors
- Export logs in various formats

## Requirements

- PHP 7.4 or higher
- WordPress 5.0 or higher
- Write permissions to wp-content directory
- PHP error logging enabled

## Installation

1. Upload the plugin files to the `/wp-content/plugins/debug-log-tools` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Tools > Debug Log Tools to configure settings

## Configuration

### Basic Settings

1. Enable/disable debug logging
2. Set log file location
3. Configure log rotation settings
4. Set up email notifications

### Advanced Settings

1. Configure performance monitoring
2. Set up security scanning
3. Customize log filters
4. Configure export settings

## Usage

### Viewing Logs

1. Navigate to Tools > Debug Log Tools
2. Use the filter options to search logs
3. Click on a log entry to view details
4. Use the export button to save logs

### Log Rotation

1. Set maximum log file size
2. Configure backup retention
3. Enable automatic rotation
4. Monitor rotation status

### Performance Monitoring

1. View performance metrics
2. Monitor memory usage
3. Track slow queries
4. Analyze resource usage

## Development

See [CONTRIBUTING.md](CONTRIBUTING.md) for development guidelines.

### Building Assets

```bash
npm install
npm run build
```

### Running Tests

```bash
composer install
vendor/bin/phpunit
```

## Security

- All user input is sanitized
- Proper capability checks
- Secure file operations
- Regular security updates

## Support

- Documentation: [Documentation](https://github.com/saqibj/debug-log-tools/wiki)
- Support Forum: [WordPress.org](https://wordpress.org/support/plugin/debug-log-tools)
- GitHub Issues: [Issues](https://github.com/saqibj/debug-log-tools/issues)

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

This plugin is licensed under the GPL-3.0 license. See [LICENSE](LICENSE) for details.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a complete list of changes.
