<?php
/**
 * Debug Log Exception Class
 *
 * Base exception class for Debug Log Tools plugin.
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
 * Base exception class for Debug Log Tools
 */
class Debug_Log_Exception extends \Exception {
    /**
     * Constructor.
     *
     * @param string     $message  The Exception message.
     * @param int        $code     The Exception code.
     * @param \Throwable $previous The previous throwable used for exception chaining.
     */
    public function __construct($message = "", $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
} 