<?php
/**
 * Log File Exception Class
 *
 * Exception class for log file related errors.
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
 * Log file exception class
 */
class Log_File_Exception extends Debug_Log_Exception {
    /**
     * Error code for log file not found
     */
    const FILE_NOT_FOUND = 200;

    /**
     * Error code for log file not readable
     */
    const FILE_NOT_READABLE = 201;

    /**
     * Error code for log file not writable
     */
    const FILE_NOT_WRITABLE = 202;

    /**
     * Error code for log file creation error
     */
    const FILE_CREATION_ERROR = 203;

    /**
     * Error code for log file rotation error
     */
    const FILE_ROTATION_ERROR = 204;

    /**
     * Error code for log file open error
     */
    const FILE_OPEN_ERROR = 205;
} 