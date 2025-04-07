# Changelog
All notable changes to Debug Log Tools will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.2.2] - 2024-04-07
### Changed
- Enhanced error recovery mechanisms
- Optimized log file reading operations
- Improved file system monitoring efficiency
- Enhanced caching mechanism for log operations

## [3.2.1] - 2024-04-06
### Fixed
- Fixed performance issues with large log files
- Improved error handling in log file operations
- Fixed memory leaks in log rotation
- Improved caching mechanism for log statistics

### Changed
- Optimized log file reading operations
- Enhanced error recovery mechanisms
- Improved file system monitoring

## [3.2.0] - 2024-04-05
### Added
- Log Filtering and Searching
- Live Log Tail feature
- Log Rotation based on file size
### Changed
- Improved install and uninstall processes

## [3.1.6] - 2024-03-22
### Added
- Strict type checking and improved security
- Enhanced error handling and logging
- Performance optimizations with caching
- Atomic file operations for safety
- Better AJAX response handling

### Fixed
- wp-config.php modification reliability
- File permission handling
- Error message consistency

## [3.1.5] - 2024-03-21
### Added
- Confirmation dialogs for critical actions
### Fixed
- Debug log enable/disable functionality
- Improved wp-config.php modification handling

## [3.1.4] - 2024-03-20
### Fixed
- Fixed linter errors related to WordPress core functions
- Improved code organization and structure
- Enhanced error handling in log management functions

## [3.1.3] - 2024-03-19
### Added
- New "Clear Filtered Entries" button that appears when filters are active
- Ability to clear only filtered log entries while preserving the rest
- Plugin name filter dropdown to filter logs by specific plugins

### Changed
- Enhanced log filtering system to preserve original content while filtering
- Improved button visibility logic based on filter state
- Updated success and error messages for better clarity

## [3.1.2] - 2024-03-18
### Added
- Handlers for clearing and downloading the debug log
- Plugin name filter dropdown to filter logs by specific plugins

### Fixed
- Namespace issues for class references in the main plugin file
- Various linter errors related to undefined functions

### Improved
- User interface for the debug log viewer, including search and filter functionality
- Admin notices to provide feedback on log clearing and download actions
- CSS and JavaScript files for better styling and functionality

## [3.1.1] - 2024-03-17
### Added
- Search functionality in log viewer
- Log level filtering (Errors, Warnings, Notices)
- Download log functionality

### Fixed
- Issue with log file permissions
- Problem with empty log display

## [3.1.0] - 2024-03-16
### Added
- Initial release
- Basic log viewing functionality
- Enable/disable debug logging
- Clear log functionality 