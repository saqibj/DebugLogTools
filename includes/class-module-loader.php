<?php
/**
 * Module Loader Class
 *
 * @package DebugLogTools
 */

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
        add_action('admin_menu', array($this, 'add_modules_page'));
    }

    /**
     * Load all available modules
     */
    private function load_modules() {
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
    }

    /**
     * Load a specific module
     *
     * @param string $module_dir Module directory name
     */
    private function load_module($module_dir) {
        $module_path = DEBUG_LOG_TOOLS_PLUGIN_DIR . 'includes/modules/' . $module_dir;
        $main_file = $module_path . '/class-' . str_replace('-', '-', $module_dir) . '.php';

        if (file_exists($main_file)) {
            require_once $main_file;
            $class_name = $this->get_module_class_name($module_dir);
            
            if (class_exists($class_name)) {
                $this->modules[$module_dir] = new $class_name();
            }
        }
    }

    /**
     * Get module class name from directory name
     *
     * @param string $module_dir Module directory name
     * @return string Class name
     */
    private function get_module_class_name($module_dir) {
        return str_replace(' ', '_', ucwords(str_replace('-', ' ', $module_dir)));
    }

    /**
     * Add modules management page
     */
    public function add_modules_page() {
        add_submenu_page(
            'tools.php',
            __('Debug Log Modules', 'debug-log-tools'),
            __('Debug Log Modules', 'debug-log-tools'),
            'manage_options',
            'debug-log-modules',
            array($this, 'render_modules_page')
        );
    }

    /**
     * Render modules management page
     */
    public function render_modules_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle module activation/deactivation
        if (isset($_POST['module_action']) && check_admin_referer('debug_log_tools_modules')) {
            $module_id = sanitize_text_field($_POST['module_id']);
            $action = sanitize_text_field($_POST['module_action']);

            if (isset($this->modules[$module_id])) {
                if ($action === 'activate') {
                    $this->modules[$module_id]->activate();
                } elseif ($action === 'deactivate') {
                    $this->modules[$module_id]->deactivate();
                }
            }
        }

        // Display modules list
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Debug Log Modules', 'debug-log-tools'); ?></h1>
            
            <div class="modules-grid">
                <?php foreach ($this->modules as $module_id => $module): ?>
                    <div class="module-card">
                        <h2><?php echo esc_html($module->get_module_name()); ?></h2>
                        <p><?php echo esc_html($module->get_module_description()); ?></p>
                        
                        <form method="post" action="">
                            <?php wp_nonce_field('debug_log_tools_modules'); ?>
                            <input type="hidden" name="module_id" value="<?php echo esc_attr($module_id); ?>">
                            
                            <?php if ($module->is_active()): ?>
                                <input type="hidden" name="module_action" value="deactivate">
                                <button type="submit" class="button">
                                    <?php esc_html_e('Deactivate', 'debug-log-tools'); ?>
                                </button>
                            <?php else: ?>
                                <input type="hidden" name="module_action" value="activate">
                                <button type="submit" class="button button-primary">
                                    <?php esc_html_e('Activate', 'debug-log-tools'); ?>
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
                    border-radius: 4px;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                }

                .module-card h2 {
                    margin-top: 0;
                }

                @media (prefers-color-scheme: dark) {
                    .module-card {
                        background: #2b2b2b;
                        color: #f0f0f0;
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