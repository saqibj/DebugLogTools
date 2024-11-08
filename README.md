# Debug Log Tools

**Version:** 3.0.0  
**Author:** [Saqib Jawaid](https://github.com/saqibj)  
**License:** GPL-3.0  
**Tested up to WordPress 6.x**

## Description

Debug Log Tools is a WordPress plugin that provides administrators with an easy-to-use interface for viewing, filtering, enabling/disabling, and flushing the debug log file. It simplifies troubleshooting by allowing you to manage logs directly from the WordPress dashboard.

## Features

- **View Debug Log:** Displays the contents of `debug.log` for troubleshooting.
- **Enable/Disable Debug Logging:** Allows administrators to toggle debug logging on and off.
- **Flush Log:** Clears the debug log with a simple button click.
- **Admin Notices:** Displays success messages upon log flush.

## Installation

1. Upload the `debug-log-tools` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to 'Tools' -> 'Debug Log Tools' to access the plugin.

## Usage

1. Go to 'Tools' -> 'Debug Log Tools' in the WordPress admin dashboard.
2. To enable or disable debug logging, check or uncheck the "Enable Debug Logging" checkbox and save.
3. To flush the debug log, click the "Flush Log" button. A confirmation message will appear upon successful log flush.
4. View the current debug log content directly on the page.

## Security

This plugin includes the following security measures:
- **Capability Check:** Only administrators can view or flush the log.
- **Nonce Verification:** A nonce is required for log-flushing actions to prevent CSRF attacks.

## License

This plugin is licensed under the GPL-3.0 License. See the `LICENSE` file for more details.
