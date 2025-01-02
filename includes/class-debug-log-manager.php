<?php
/**
 * Debug Log Manager Class
 *
 * @package DebugLogTools
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Debug_Log_Manager
 */
class Debug_Log_Manager {

    /**
     * Initialize the debug log manager.
     */
    public function __construct() {
        add_action( 'admin_post_debug_log_tools_toggle', array( $this, 'handle_debug_toggle' ) );
    }

    /**
     * Handle debug logging toggle.
     */
    public function handle_debug_toggle() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized access', 'debug-log-tools' ) );
        }

        if ( ! isset( $_POST['debug_log_tools_nonce'] ) || 
             ! wp_verify_nonce( $_POST['debug_log_tools_nonce'], 'toggle_debug_log' ) 
        ) {
            wp_die( esc_html__( 'Security check failed', 'debug-log-tools' ) );
        }

        $enable_debug = isset( $_POST['enable_debug_log'] );
        $this->update_wp_config( $enable_debug );

        wp_safe_redirect( 
            add_query_arg( 
                'debug_updated', 
                $enable_debug ? '1' : '0', 
                admin_url( 'tools.php?page=debug-log-tools' ) 
            ) 
        );
        exit;
    }

    /**
     * Update wp-config.php file.
     *
     * @param bool $enable_debug Whether to enable debug logging.
     */
    private function update_wp_config( $enable_debug ) {
        $wp_config_path = ABSPATH . 'wp-config.php';
        if ( ! file_exists( $wp_config_path ) ) {
            return false;
        }

        $config_content = file_get_contents( $wp_config_path );
        if ( false === $config_content ) {
            return false;
        }

        $debug_constants = array(
            'WP_DEBUG',
            'WP_DEBUG_LOG',
            'WP_DEBUG_DISPLAY'
        );

        foreach ( $debug_constants as $constant ) {
            $pattern = "/define\s*\(\s*['\"]" . $constant . "['\"]\s*,\s*(?:true|false)\s*\);/";
            $replacement = "define('" . $constant . "', " . 
                         ( $enable_debug ? 'true' : 'false' ) . ");";
            
            if ( preg_match( $pattern, $config_content ) ) {
                $config_content = preg_replace( $pattern, $replacement, $config_content );
            } else {
                // Add constants if they don't exist
                $config_content = preg_replace(
                    "/(<\?php)/",
                    "<?php\n\n" . $replacement,
                    $config_content,
                    1
                );
            }
        }

        return file_put_contents( $wp_config_path, $config_content );
    }

    /**
     * Check if debug logging is enabled.
     *
     * @return bool
     */
    public static function is_debug_enabled() {
        return defined( 'WP_DEBUG' ) && WP_DEBUG;
    }
} 