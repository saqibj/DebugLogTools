<?php
/**
 * Base Module Class
 *
 * @package DebugLogTools
 * @subpackage Modules
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract class Base_Module
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
        $this->init();
        $this->register_hooks();
    }

    /**
     * Initialize the module
     */
    abstract protected function init();

    /**
     * Register WordPress hooks
     */
    abstract protected function register_hooks();

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
        return (bool) get_option('debug_log_tools_module_' . $this->module_id . '_active', true);
    }

    /**
     * Activate module
     *
     * @return bool
     */
    public function activate() {
        return update_option('debug_log_tools_module_' . $this->module_id . '_active', true);
    }

    /**
     * Deactivate module
     *
     * @return bool
     */
    public function deactivate() {
        return update_option('debug_log_tools_module_' . $this->module_id . '_active', false);
    }
} 