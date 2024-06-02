# Debug Log Tools

## Description
Debug Log Tools is a WordPress plugin that allows users to view and manage the WordPress debug log with plugin-specific filtering and log flushing capabilities.

## Installation

1. Upload the `debug-log-tools` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to 'Tools' -> 'Debug Log Tools' to use the plugin.

## Features

- **Debug Log Viewer**: View the WordPress debug log with the option to filter entries by a specific plugin.
- **Flush Debug Log**: Clear the contents of the debug log with a confirmation prompt.

## Usage

1. Go to 'Tools' -> 'Debug Log Tools'.
2. Select a plugin from the dropdown menu to filter log entries (optional).
3. Click 'View Log' to display the debug log entries.
4. Click 'Flush Log' to clear the debug log after confirmation.

## Security

- Only users with administrative privileges (`'manage_options'` capability) can access these tools.
- Nonce fields and checks are implemented for security in form submissions.

## License

This project is licensed under the MIT License - see the LICENSE file for details.
