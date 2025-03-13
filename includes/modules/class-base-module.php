<?php
/**
 * Base Module Class
 *
 * @package DebugLogTools
 */

namespace DebugLogTools\Modules;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Base_Module
 */
abstract class Base_Module {
    /**
     * Module ID
     *
     * @var string
     */
    protected $module_id;

    /**
     * Module name
     *
     * @var string
     */
    protected $module_name;

    /**
     * Module description
     *
     * @var string
     */
    protected $module_description;

    /**
     * Constructor
     *
     * @param string $module_id Module ID.
     * @param string $module_name Module name.
     * @param string $module_description Module description.
     */
    public function __construct( $module_id, $module_name, $module_description ) {
        $this->module_id = $module_id;
        $this->module_name = $module_name;
        $this->module_description = $module_description;
    }

    /**
     * Get module ID
     *
     * @return string
     */
    public function get_module_id() {
        return $this->module_id;
    }

    /**
     * Get module name
     *
     * @return string
     */
    public function get_module_name() {
        return $this->module_name;
    }

    /**
     * Get module description
     *
     * @return string
     */
    public function get_module_description() {
        return $this->module_description;
    }

    /**
     * Check if module is active
     *
     * @return bool
     */
    public function is_active() {
        $active_modules = \get_option( 'debug_log_tools_active_modules', array() );
        return in_array( $this->module_id, $active_modules, true );
    }

    /**
     * Activate module
     *
     * @return bool
     */
    public function activate() {
        $active_modules = \get_option( 'debug_log_tools_active_modules', array() );
        if ( ! in_array( $this->module_id, $active_modules, true ) ) {
            $active_modules[] = $this->module_id;
            \update_option( 'debug_log_tools_active_modules', $active_modules );
            return true;
        }
        return false;
    }

    /**
     * Deactivate module
     *
     * @return bool
     */
    public function deactivate() {
        $active_modules = \get_option( 'debug_log_tools_active_modules', array() );
        $key = array_search( $this->module_id, $active_modules, true );
        if ( false !== $key ) {
            unset( $active_modules[ $key ] );
            \update_option( 'debug_log_tools_active_modules', array_values( $active_modules ) );
            return true;
        }
        return false;
    }

    /**
     * Initialize module
     */
    abstract public function init();
} 