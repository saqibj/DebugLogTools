<?php
/**
 * Debug Log Manager Class
 *
 * @package DebugLogTools
 */

namespace DebugLogTools;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

declare(strict_types=1);

/**
 * Class Debug_Log_Manager
 */
class Debug_Log_Manager {

    const MAX_LOG_SIZE = 1048576; // 1MB
    const CACHE_KEY = 'debug_log_content';
    const CACHE_TTL = 60; // 1 minute

    /**
     * Initialize the debug log manager.
     */
    public function __construct() {
        \add_action( 'admin_post_debug_log_tools_toggle', array( $this, 'handle_debug_toggle' ) );
    }

    /**
     * Initialize the debug log manager
     */
    public function init() {
        // Additional initialization if needed
    }

    /**
     * Handle debug logging toggle.
     */
    public function handle_debug_toggle() {
        if ( ! \current_user_can( 'manage_options' ) ) {
            \wp_die( \esc_html__( 'Unauthorized access', 'debug-log-tools' ) );
        }

        if ( ! isset( $_POST['debug_log_tools_nonce'] ) || 
             ! \wp_verify_nonce( $_POST['debug_log_tools_nonce'], 'toggle_debug_log' ) 
        ) {
            \wp_die( \esc_html__( 'Security check failed', 'debug-log-tools' ) );
        }

        $enable_debug = isset( $_POST['enable_debug_log'] );
        
        try {
            $this->update_wp_config( $enable_debug );
            
            if ( $enable_debug ) {
                $log_file = \WP_CONTENT_DIR . '/debug.log';
                if ( ! file_exists( $log_file ) && is_writable( \WP_CONTENT_DIR ) ) {
                    if ( ! @touch( $log_file ) || ! @chmod( $log_file, 0644 ) ) {
                        throw new Exception( 'Failed to create log file' );
                    }
                }
            }
            
            \wp_safe_redirect( 
                \add_query_arg( 
                    'debug_updated', 
                    $enable_debug ? '1' : '0', 
                    \admin_url( 'tools.php?page=debug-log-tools' ) 
                ) 
            );
            exit;
        } catch ( \Exception $e ) {
            error_log( 'Debug Log Tools Error: ' . $e->getMessage() );
            \wp_die( \esc_html( $e->getMessage() ) );
        }
    }

    /**
     * Update wp-config.php file.
     *
     * @param bool $enable_debug Whether to enable debug logging.
     * @throws Exception If file operations fail.
     */
    private function update_wp_config( $enable_debug ) {
        $wp_config_path = ABSPATH . 'wp-config.php';
        if ( ! file_exists( $wp_config_path ) ) {
            throw new Exception( esc_html__( 'wp-config.php not found.', 'debug-log-tools' ) );
        }
        
        if ( ! is_writable( $wp_config_path ) ) {
            throw new Exception( esc_html__( 'wp-config.php is not writable.', 'debug-log-tools' ) );
        }

        // Create backup
        $backup_path = $wp_config_path . '.backup-' . time();
        if ( ! copy( $wp_config_path, $backup_path ) ) {
            throw new Exception( esc_html__( 'Failed to create backup of wp-config.php', 'debug-log-tools' ) );
        }

        $config_content = file_get_contents( $wp_config_path );
        if ( false === $config_content ) {
            @unlink( $backup_path );
            throw new Exception( esc_html__( 'Failed to read wp-config.php', 'debug-log-tools' ) );
        }

        // Normalize line endings
        $config_content = str_replace("\r\n", "\n", $config_content);
        $config_content = str_replace("\r", "\n", $config_content);

        $debug_constants = array(
            'WP_DEBUG' => $enable_debug ? 'true' : 'false',
            'WP_DEBUG_LOG' => $enable_debug ? 'true' : 'false',
            'WP_DEBUG_DISPLAY' => 'false'
        );

        foreach ( $debug_constants as $constant => $value ) {
            // Check for existing define
            $pattern = "/define\s*\(\s*['\"]" . preg_quote( $constant, '/' ) . "['\"]\s*,\s*(?:true|false)\s*\);/";
            if ( preg_match( $pattern, $config_content ) ) {
                $config_content = preg_replace( $pattern, "define('$constant', $value);", $config_content );
            } else {
                // Add new define after the opening PHP tag
                $config_content = preg_replace(
                    "/(<\?php)/i",
                    "<?php\n\n// Debug Log Tools\n" . "define('$constant', $value);",
                    $config_content,
                    1
                );
            }
        }

        // Ensure proper line endings
        $config_content = str_replace("\n", PHP_EOL, $config_content);

        if ( false === file_put_contents( $wp_config_path, $config_content ) ) {
            // Restore backup
            @copy( $backup_path, $wp_config_path );
            @unlink( $backup_path );
            throw new Exception( esc_html__( 'Failed to write to wp-config.php', 'debug-log-tools' ) );
        }

        @unlink( $backup_path );
        return true;
    }

    /**
     * Check if debug logging is enabled.
     *
     * @return bool
     */
    public static function is_debug_enabled() {
        return defined( 'WP_DEBUG' ) && WP_DEBUG && 
               defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG;
    }

    private function safe_file_put_contents($path, $content) {
        $temp = tempnam(dirname($path), 'tmp');
        if (false === file_put_contents($temp, $content)) {
            unlink($temp);
            throw new Exception('Failed to write temporary file');
        }
        
        if (!rename($temp, $path)) {
            unlink($temp);
            throw new Exception('Failed to move temporary file');
        }
        
        return true;
    }
} 