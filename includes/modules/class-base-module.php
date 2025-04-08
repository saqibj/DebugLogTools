<?php
/**
 * Base Module Class
 *
 * @package DebugLogTools
 * @subpackage Modules
 */

namespace DebugLogTools\Modules;

if (!defined('ABSPATH')) {
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
     */
    public function __construct() {
        // Default values that will be overridden by child classes
        $this->module_name = 'Unnamed Module';
        $this->module_description = 'This module has no description.';
    }

    /**
     * Set module ID
     * 
     * @param string $module_id Module ID.
     */
    public function set_module_id($module_id) {
        $this->module_id = $module_id;
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
        if (!function_exists('get_option')) {
            return false;
        }
        $active_modules = \get_option('debug_log_tools_active_modules', array());
        return in_array($this->module_id, $active_modules, true);
    }

    /**
     * Activate module
     *
     * @return bool
     */
    public function activate() {
        if (!function_exists('get_option') || !function_exists('update_option')) {
            return false;
        }
        $active_modules = \get_option('debug_log_tools_active_modules', array());
        if (!in_array($this->module_id, $active_modules, true)) {
            $active_modules[] = $this->module_id;
            return \update_option('debug_log_tools_active_modules', $active_modules);
        }
        return false;
    }

    /**
     * Deactivate module
     *
     * @return bool
     */
    public function deactivate() {
        if (!function_exists('get_option') || !function_exists('update_option')) {
            return false;
        }
        $active_modules = \get_option('debug_log_tools_active_modules', array());
        $key = array_search($this->module_id, $active_modules, true);
        if (false !== $key) {
            unset($active_modules[$key]);
            return \update_option('debug_log_tools_active_modules', array_values($active_modules));
        }
        return false;
    }

    /**
     * Initialize module
     */
    abstract public function init();
} 