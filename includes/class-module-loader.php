<?php
/**
 * Module Loader Class
 *
 * @package DebugLogTools
 */

namespace DebugLogTools;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Module_Loader
 */
class Module_Loader {
    /**
     * Loaded modules
     *
     * @var array
     */
    private $modules = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->load_modules();
    }

    /**
     * Initialize the module loader
     */
    public function init() {
        // Initialize all loaded modules
        foreach ($this->modules as $module) {
            if ($module->is_active()) {
                $module->init();
            }
        }
    }

    /**
     * Load all available modules
     */
    private function load_modules() {
        // Ensure the base module class is loaded first
        $base_module_file = DEBUG_LOG_TOOLS_PLUGIN_DIR . 'includes/modules/class-base-module.php';
        if (file_exists($base_module_file)) {
            require_once $base_module_file;
        } else {
            error_log('Debug Log Tools: Base module file not found: ' . $base_module_file);
            return;
        }

        $module_dirs = array(
            'debugging',
            'notifications',
            'performance',
            'security'
        );

        foreach ($module_dirs as $dir) {
            $module_path = DEBUG_LOG_TOOLS_PLUGIN_DIR . 'includes/modules/' . $dir;
            if (is_dir($module_path)) {
                $this->load_module($dir);
            }
        }

        // Activate modules by default if none are active
        $active_modules = get_option('debug_log_tools_active_modules', array());
        if (empty($active_modules)) {
            foreach ($this->modules as $module_id => $module) {
                $module->activate();
            }
        }
    }

    /**
     * Load a specific module
     *
     * @param string $module_dir Module directory name
     */
    private function load_module($module_dir) {
        $module_path = DEBUG_LOG_TOOLS_PLUGIN_DIR . 'includes/modules/' . $module_dir;
        $main_file = $module_path . '/class-' . str_replace('_', '-', $module_dir) . '.php';

        if (file_exists($main_file)) {
            require_once $main_file;
            $class_name = 'DebugLogTools\\Modules\\' . $this->get_module_class_name($module_dir);
            
            if (class_exists($class_name)) {
                // Don't try to instantiate directly - the module class should handle its own constructor
                $reflection = new \ReflectionClass($class_name);
                if ($reflection->isInstantiable()) {
                    try {
                        // Construct module with required params
                        $this->modules[$module_dir] = $reflection->newInstance();
                        // Let the module know it's ID for internal tracking
                        if (method_exists($this->modules[$module_dir], 'set_module_id')) {
                            $this->modules[$module_dir]->set_module_id($module_dir);
                        }
                    } catch (\Exception $e) {
                        error_log(sprintf(
                            'Debug Log Tools: Failed to instantiate module %s: %s',
                            $class_name,
                            $e->getMessage()
                        ));
                    }
                } else {
                    error_log(sprintf(
                        'Debug Log Tools: Module class %s is not instantiable',
                        $class_name
                    ));
                }
            } else {
                error_log(sprintf(
                    'Debug Log Tools: Module class %s not found in file %s',
                    $class_name,
                    $main_file
                ));
            }
        } else {
            error_log(sprintf(
                'Debug Log Tools: Module file not found: %s',
                $main_file
            ));
        }
    }

    /**
     * Get module class name from directory name
     *
     * @param string $module_dir Module directory name
     * @return string Class name
     */
    private function get_module_class_name($module_dir) {
        $words = explode('-', $module_dir);
        $class_name = '';
        foreach ($words as $word) {
            $class_name .= ucfirst($word) . '_';
        }
        return rtrim($class_name, '_');
    }

    /**
     * Render modules management page
     */
    public static function render_modules_page() {
        if (!\current_user_can('manage_options')) {
            return;
        }

        $module_loader = new self();

        // Handle module activation/deactivation
        if (isset($_POST['module_action']) && \check_admin_referer('debug_log_tools_modules')) {
            $module_id = \sanitize_text_field($_POST['module_id']);
            $action = \sanitize_text_field($_POST['module_action']);

            if (isset($module_loader->modules[$module_id])) {
                if ($action === 'activate') {
                    $module_loader->modules[$module_id]->activate();
                } elseif ($action === 'deactivate') {
                    $module_loader->modules[$module_id]->deactivate();
                }
            }
        }

        // Display modules list
        ?>
        <div class="wrap">
            <h1><?php echo \esc_html__('Debug Log Modules', 'debug-log-tools'); ?></h1>
            
            <div class="modules-grid">
                <?php foreach ($module_loader->modules as $module_id => $module): ?>
                    <div class="module-card">
                        <h2><?php echo \esc_html($module->get_module_name()); ?></h2>
                        <p><?php echo \esc_html($module->get_module_description()); ?></p>
                        
                        <form method="post" action="">
                            <?php \wp_nonce_field('debug_log_tools_modules'); ?>
                            <input type="hidden" name="module_id" value="<?php echo \esc_attr($module_id); ?>">
                            
                            <?php if ($module->is_active()): ?>
                                <input type="hidden" name="module_action" value="deactivate">
                                <button type="submit" class="button">
                                    <?php \esc_html_e('Deactivate', 'debug-log-tools'); ?>
                                </button>
                            <?php else: ?>
                                <input type="hidden" name="module_action" value="activate">
                                <button type="submit" class="button button-primary">
                                    <?php \esc_html_e('Activate', 'debug-log-tools'); ?>
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <style>
                .modules-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                    gap: 20px;
                    margin-top: 20px;
                }

                .module-card {
                    background: #fff;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                }

                .module-card h2 {
                    margin-top: 0;
                    color: #1d2327;
                    font-size: 1.3em;
                }

                .module-card p {
                    color: #50575e;
                    margin: 1em 0;
                    min-height: 3em;
                }

                .module-card .button {
                    display: inline-block;
                    min-width: 100px;
                    text-align: center;
                    padding: 8px 16px;
                    height: auto;
                    line-height: 1.4;
                    font-size: 14px;
                    font-weight: 500;
                    border-radius: 4px;
                    transition: all 0.2s ease;
                }

                .module-card .button-primary {
                    background: #2271b1;
                    border-color: #2271b1;
                    color: #fff;
                    text-shadow: none;
                }

                .module-card .button-primary:hover,
                .module-card .button-primary:focus {
                    background: #135e96;
                    border-color: #135e96;
                    color: #fff;
                }

                .module-card .button:not(.button-primary) {
                    background: #f6f7f7;
                    border-color: #2271b1;
                    color: #2271b1;
                }

                .module-card .button:not(.button-primary):hover,
                .module-card .button:not(.button-primary):focus {
                    background: #f0f0f1;
                    border-color: #0a4b78;
                    color: #0a4b78;
                }

                @media (prefers-color-scheme: dark) {
                    .module-card {
                        background: #2c3338;
                        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
                    }

                    .module-card h2 {
                        color: #e2e4e7;
                    }

                    .module-card p {
                        color: #bbc8d4;
                    }

                    .module-card .button:not(.button-primary) {
                        background: #2c3338;
                        border-color: #2271b1;
                        color: #2271b1;
                    }

                    .module-card .button:not(.button-primary):hover,
                    .module-card .button:not(.button-primary):focus {
                        background: #32373c;
                        border-color: #135e96;
                        color: #135e96;
                    }
                }
            </style>
        </div>
        <?php
    }

    /**
     * Get all loaded modules
     *
     * @return array
     */
    public function get_modules() {
        return $this->modules;
    }

    /**
     * Get a specific module
     *
     * @param string $module_id Module ID
     * @return Base_Module|null
     */
    public function get_module($module_id) {
        return isset($this->modules[$module_id]) ? $this->modules[$module_id] : null;
    }
}