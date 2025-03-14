# Changelog
All notable changes to Debug Log Tools will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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