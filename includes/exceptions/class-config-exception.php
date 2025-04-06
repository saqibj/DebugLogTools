<?php
/**
 * Configuration Exception Class
 *
 * Exception class for configuration related errors.
 *
 * @package    DebugLogTools
 * @subpackage Exceptions
 * @since      3.2.1
 */

namespace DebugLogTools\Exceptions;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Configuration exception class
 */
class Config_Exception extends Debug_Log_Exception {
    /**
     * Error code for wp-config.php not writable
     */
    const CONFIG_NOT_WRITABLE = 100;

    /**
     * Error code for wp-config.php not found
     */
    const CONFIG_NOT_FOUND = 101;

    /**
     * Error code for wp-config.php read error
     */
    const CONFIG_READ_ERROR = 102;

    /**
     * Error code for wp-config.php write error
     */
    const CONFIG_WRITE_ERROR = 103;
} 