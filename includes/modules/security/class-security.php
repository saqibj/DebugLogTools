<?php
/**
 * Security Module
 *
 * @package DebugLogTools
 * @subpackage Modules
 */

namespace DebugLogTools\Modules;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Security
 */
class Security extends Base_Module {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        
        // Set module properties
        $this->module_name = 'Security';
        $this->module_description = 'Monitor WordPress security and log suspicious activities';
    }

    /**
     * Initialize the module
     */
    public function init() {
        if (!$this->is_active()) {
            return;
        }

        // Initialize module functionality
        \add_action('admin_menu', array($this, 'add_menu_page'));
        
        // Monitor login attempts
        \add_action('wp_login_failed', array($this, 'log_failed_login'));
        
        // Monitor file changes
        \add_action('admin_init', array($this, 'schedule_file_check'));
    }

    /**
     * Add menu page
     */
    public function add_menu_page() {
        \add_submenu_page(
            'tools.php',
            \__('Security Monitoring', 'debug-log-tools'),
            \__('Security', 'debug-log-tools'),
            'manage_options',
            'debug-log-security',
            array($this, 'render_page')
        );
    }

    /**
     * Render module page
     */
    public function render_page() {
        if (!\current_user_can('manage_options')) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php echo \esc_html__('Security Monitoring', 'debug-log-tools'); ?></h1>
            
            <p><?php echo \esc_html__('Monitor WordPress security and identify potential threats.', 'debug-log-tools'); ?></p>
            
            <div class="security-stats">
                <h2><?php echo \esc_html__('Security Overview', 'debug-log-tools'); ?></h2>
                
                <div class="security-summary">
                    <h3><?php echo \esc_html__('Failed Login Attempts', 'debug-log-tools'); ?></h3>
                    <p><?php echo $this->get_failed_login_count(); ?></p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Log failed login attempts
     * 
     * @param string $username The username that was used in the failed login attempt
     */
    public function log_failed_login($username) {
        \error_log(sprintf(
            'Failed login attempt for username: %s | IP: %s',
            \esc_attr($username),
            $this->get_client_ip()
        ));
        
        // Store for stats
        $failed_logins = \get_option('debug_log_failed_logins', array());
        $failed_logins[] = array(
            'time' => \current_time('mysql'),
            'username' => \esc_attr($username),
            'ip' => $this->get_client_ip()
        );
        
        // Keep only the last 100 entries
        if (count($failed_logins) > 100) {
            $failed_logins = array_slice($failed_logins, -100);
        }
        
        \update_option('debug_log_failed_logins', $failed_logins);
    }
    
    /**
     * Schedule file integrity check
     */
    public function schedule_file_check() {
        if (!\wp_next_scheduled('debug_log_file_check')) {
            \wp_schedule_event(time(), 'daily', 'debug_log_file_check');
            \add_action('debug_log_file_check', array($this, 'check_file_integrity'));
        }
    }
    
    /**
     * Check file integrity
     */
    public function check_file_integrity() {
        // Implementation would check core WordPress files against known checksums
        \error_log('Running file integrity check');
    }
    
    /**
     * Get client IP address
     * 
     * @return string The client IP address
     */
    private function get_client_ip() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = \sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = \sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        } else {
            $ip = \sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
        
        return $ip;
    }
    
    /**
     * Get the count of failed login attempts
     * 
     * @return int Count of failed login attempts
     */
    private function get_failed_login_count() {
        $failed_logins = \get_option('debug_log_failed_logins', array());
        return count($failed_logins);
    }
} 