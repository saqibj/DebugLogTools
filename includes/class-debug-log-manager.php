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

        try {
            // Create backup
            $backup_path = $wp_config_path . '.backup-' . time();
            if ( ! copy( $wp_config_path, $backup_path ) ) {
                throw new Exception( esc_html__( 'Failed to create backup of wp-config.php', 'debug-log-tools' ) );
            }

            $file_handle = fopen( $wp_config_path, 'r+' );
            if ( ! $file_handle ) {
                throw new Exception( esc_html__( 'Failed to open wp-config.php for writing.', 'debug-log-tools' ) );
            }

            if ( ! flock( $file_handle, LOCK_EX ) ) { // Exclusive lock
                fclose( $file_handle );
                throw new Exception( esc_html__( 'Could not get exclusive lock on wp-config.php.', 'debug-log-tools' ) );
            }

            $config_content = fread( $file_handle, filesize( $wp_config_path ) );
            if ( $config_content === false ) {
                flock( $file_handle, LOCK_UN );
                fclose( $file_handle );
                throw new Exception( esc_html__( 'Failed to read wp-config.php content.', 'debug-log-tools' ) );
            }

            $enable_debug_log_constant = $enable_debug ? 'true' : 'false';

            // Define constants to look for and their replacement patterns
            $constants = array(
                'WP_DEBUG'      => array(
                    'pattern'       => '/^\s*define\s*\(\s*([\'"])WP_DEBUG\1\s*,\s*(true|false)\s*\)\s*;/mi',
                    'replacement'   => "define('WP_DEBUG', {$enable_debug_log_constant});"
                ),
                'WP_DEBUG_LOG'  => array(
                    'pattern'       => '/^\s*define\s*\(\s*([\'"])WP_DEBUG_LOG\1\s*,\s*(true|false)\s*\)\s*;/mi',
                    'replacement'   => "define('WP_DEBUG_LOG', true);" // Always true when enabling debug
                ),
                'WP_DEBUG_DISPLAY' => array( // Optional: Consider controlling WP_DEBUG_DISPLAY as well
                    'pattern'       => '/^\s*define\s*\(\s*([\'"])WP_DEBUG_DISPLAY\1\s*,\s*(true|false)\s*\)\s*;/mi',
                    'replacement'   => "define('WP_DEBUG_DISPLAY', false);" // Recommended false for production-like setups
                ),
            );

            foreach ( $constants as $constant_name => $constant_config ) {
                if ( preg_match( $constant_config['pattern'], $config_content ) ) {
                    $config_content = preg_replace(
                        $constant_config['pattern'],
                        $constant_config['replacement'],
                        $config_content
                    );
                } else {
                    // If constant not found, append it before "That's all, stop editing!" or at the end
                    $insertion_point = '/* That\'s all, stop editing! Happy blogging. */';
                    $constant_string = "\n" . $constant_config['replacement'];
                    if ( strpos( $config_content, $insertion_point ) !== false ) {
                        $config_content = str_replace( $insertion_point, $constant_string . "\n" . $insertion_point, $config_content );
                    } else {
                        // Fallback to appending at the end of the file
                        $config_content .= $constant_string;
                    }
                }
            }

            // Write updated content back to file
            rewind( $file_handle ); // Go to the beginning of the file
            if ( fwrite( $file_handle, $config_content ) === false ) {
                flock( $file_handle, LOCK_UN );
                fclose( $file_handle );
                throw new Exception( esc_html__( 'Failed to write updated content to wp-config.php.', 'debug-log-tools' ) );
            }
            ftruncate( $file_handle, strlen( $config_content ) ); // Truncate file to the new length

            fflush( $file_handle ); // Flush output to disk before renaming

            $temp_file_path = tempnam( dirname( $wp_config_path ), 'wp-config-temp-' );
            if ( $temp_file_path === false ) {
                flock( $file_handle, LOCK_UN );
                fclose( $file_handle );
                throw new Exception( esc_html__( 'Failed to create temporary file for wp-config.php update.', 'debug-log-tools' ) );
            }

            // Write to temporary file
            $temp_file_handle = fopen( $temp_file_path, 'r+' );
            if ( ! $temp_file_handle ) {
                flock( $file_handle, LOCK_UN );
                fclose( $file_handle );
                unlink( $temp_file_path ); // Clean up temporary file
                throw new Exception( esc_html__( 'Failed to open temporary file for writing.', 'debug-log-tools' ) );
            }

            if ( fwrite( $temp_file_handle, $config_content ) === false ) {
                flock( $file_handle, LOCK_UN );
                fclose( $file_handle );
                fclose( $temp_file_handle );
                unlink( $temp_file_path ); // Clean up temporary file
                throw new Exception( esc_html__( 'Failed to write to temporary file.', 'debug-log-tools' ) );
            }

            fflush( $temp_file_handle ); // Ensure all data is written to the temporary file
            fclose( $temp_file_handle ); // Close temporary file handle

            // Rename temporary file to wp-config.php (atomic operation)
            if ( ! rename( $temp_file_path, $wp_config_path ) ) {
                flock( $file_handle, LOCK_UN );
                fclose( $file_handle );
                unlink( $temp_file_path ); // Clean up temporary file
                throw new Exception( esc_html__( 'Failed to rename temporary file to wp-config.php. Please check file permissions.', 'debug-log-tools' ) );
            }

            flock( $file_handle, LOCK_UN );
            fclose( $file_handle );

            @unlink( $backup_path );
            return true;
        } catch ( Exception $e ) {
            // Attempt rollback
            if ( isset( $backup_path ) && file_exists( $backup_path ) ) {
                try {
                    $this->restore_wp_config( $backup_path, $wp_config_path );
                    error_log( sprintf(
                        'Debug Log Tools: wp-config.php update failed, rollback successful. Error: %s',
                        $e->getMessage()
                    ) );
                    throw new Exception( esc_html__( 'wp-config.php update failed and was rolled back.', 'debug-log-tools' ) );
                } catch ( Exception $rollback_exception ) {
                    error_log( sprintf(
                        'Debug Log Tools: wp-config.php update failed, rollback also failed. Original Error: %s, Rollback Error: %s',
                        $e->getMessage(),
                        $rollback_exception->getMessage()
                    ) );
                    throw new Exception( esc_html__( 'wp-config.php update failed and rollback also failed. Please check error logs.', 'debug-log-tools' ) );
                }
            } else {
                // If backup file path is not set or backup file doesn't exist, just log the error and re-throw
                error_log( sprintf(
                    'Debug Log Tools: Error updating wp-config.php, no backup to rollback to. Error: %s',
                    $e->getMessage()
                ) );
                throw $e; // Re-throw the original exception
            }
        }
    }

    /**
     * Restores wp-config.php from a backup file.
     *
     * @param string $backup_filepath The path to the backup file.
     * @param string $wp_config_path  The path to the wp-config.php file.
     *
     * @throws Exception If restoration fails.
     */
    private function restore_wp_config( string $backup_filepath, string $wp_config_path ): void {
        if ( ! file_exists( $backup_filepath ) ) {
            throw new Exception( esc_html__( 'Backup file not found, cannot restore wp-config.php.', 'debug-log-tools' ) );
        }
        if ( ! is_writable( $wp_config_path ) ) {
            throw new Exception( esc_html__( 'wp-config.php is not writable, cannot restore from backup.', 'debug-log-tools' ) );
        }

        if ( ! copy( $backup_filepath, $wp_config_path ) ) {
            throw new Exception( esc_html__( 'Failed to restore wp-config.php from backup.', 'debug-log-tools' ) );
        }
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

    /**
     * Gets the content of the debug log file in chunks with caching and filtering/searching.
     *
     * @param string      $log_filepath    Path to the log file.
     * @param int         $chunk_size      Size of each chunk in bytes.
     * @param int         $cache_expiry     Cache expiration time in seconds. Default is 300 seconds (5 minutes).
     * @param string|null $filter_keywords  Comma-separated keywords to filter log entries.
     * @param string|null $search_term      Term to search for in log entries.
     * @return Generator|string[] Yields chunks of the log file content from cache or file, filtered and searched.
     * @throws Exception If the log file cannot be opened or read.
     */
    public function get_log_content_cached( string $log_filepath, int $chunk_size = 4096, int $cache_expiry = 300, ?string $filter_keywords = null, ?string $search_term = null ): Generator {
        $cache_key = 'debug_log_tools_log_content_' . md5( $log_filepath ) . '_' . md5( $filter_keywords . $search_term );
        $cached_content = get_transient( $cache_key );

        if ( false !== $cached_content ) {
            // Serve from cache
            foreach ( $cached_content as $chunk ) {
                yield $chunk;
            }
            return;
        }

        // If not in cache, read from file, filter/search, and cache
        $log_chunks = [];
        $file_handle = fopen( $log_filepath, 'r' );
        if ( ! $file_handle ) {
            throw new Exception( esc_html__( 'Failed to open debug log file.', 'debug-log-tools' ) );
        }

        $filter_keywords_array = $filter_keywords ? array_map( 'trim', explode( ',', $filter_keywords ) ) : [];

        try {
            $current_chunk_lines = '';
            while ( ! feof( $file_handle ) ) {
                $chunk = fread( $file_handle, $chunk_size );
                $current_chunk_lines .= $chunk;
                $lines = explode( "\n", $current_chunk_lines );
                $current_chunk_lines = array_pop( $lines ); // Hold onto the last (possibly incomplete) line

                foreach ( $lines as $line ) {
                    if ( empty( $line ) ) {
                        continue; // Skip empty lines
                    }

                    $is_filtered = false;
                    if ( ! empty( $filter_keywords_array ) ) {
                        $is_filtered = true;
                        foreach ( $filter_keywords_array as $keyword ) {
                            if ( stripos( $line, $keyword ) !== false ) {
                                $is_filtered = false; // Keyword found, not filtered out
                                break;
                            }
                        }
                        if ( $is_filtered ) {
                            continue; // Skip to next line if filtered out by keywords
                        }
                    }

                    if ( ! empty( $search_term ) && stripos( $line, $search_term ) === false ) {
                        continue; // Skip to next line if search term not found
                    }

                    $log_chunks[] = $line . "\n"; // Re-add newline for caching
                    yield $line . "\n"; // Yield line with newline
                }
            }
            // Process any remaining line in $current_chunk_lines
            if ( ! empty( $current_chunk_lines ) ) {
                $line = $current_chunk_lines;

                $is_filtered = false;
                if ( ! empty( $filter_keywords_array ) ) {
                    $is_filtered = true;
                    foreach ( $filter_keywords_array as $keyword ) {
                        if ( stripos( $line, $keyword ) !== false ) {
                            $is_filtered = false;
                            break;
                        }
                    }
                    if ( $is_filtered ) {
                        // Do not cache or yield if filtered out
                    } else {
                        if ( ! empty( $search_term ) && stripos( $line, $search_term ) === false ) {
                            // Do not cache or yield if search term not found
                        } else {
                            $log_chunks[] = $line . "\n";
                            yield $line . "\n";
                        }
                    }
                } else {
                    if ( ! empty( $search_term ) && stripos( $line, $search_term ) === false ) {
                        // Do not cache or yield if search term not found
                    } else {
                        $log_chunks[] = $line . "\n";
                        yield $line . "\n";
                    }
                }
            }


        } finally {
            fclose( $file_handle );
        }

        set_transient( $cache_key, $log_chunks, $cache_expiry );
    }

    /**
     * Gets new lines added to the debug log file since the last read.
     *
     * @param string $log_filepath Path to the log file.
     * @param int    $last_size    The file size from the last read, to read only new lines.
     * @return array Array of new log lines.
     * @throws Exception If the log file cannot be opened or read.
     */
    public function get_new_log_lines( string $log_filepath, int $last_size ): array {
        if ( ! file_exists( $log_filepath ) ) {
            throw new Exception( esc_html__( 'Debug log file not found.', 'debug-log-tools' ) );
        }
        if ( ! is_readable( $log_filepath ) ) {
            throw new Exception( esc_html__( 'Debug log file is not readable.', 'debug-log-tools' ) );
        }

        $current_size = filesize( $log_filepath );
        if ( $current_size < $last_size ) {
            $last_size = 0; // File has been truncated, reset last size
        }

        $new_lines = [];
        $file_handle = fopen( $log_filepath, 'r' );
        if ( ! $file_handle ) {
            throw new Exception( esc_html__( 'Failed to open debug log file.', 'debug-log-tools' ) );
        }

        try {
            if ( $last_size > 0 ) {
                fseek( $file_handle, $last_size ); // Seek to the last known position
            }

            while ( $line = fgets( $file_handle ) ) {
                $new_lines[] = $line;
            }
        } finally {
            fclose( $file_handle );
        }

        return $new_lines;
    }

    /**
     * Rotates the debug log file if it exceeds a certain size.
     *
     * @param int $max_size Maximum log file size in bytes before rotation (default 10MB).
     * @return bool True if rotation was performed, false otherwise.
     */
    public function rotate_log_file( int $max_size = 10485760 ): bool { // 10MB default max size
        $log_filepath = $this->get_log_file_path();

        if ( ! file_exists( $log_filepath ) ) {
            return false; // No log file to rotate
        }

        if ( filesize( $log_filepath ) < $max_size ) {
            return false; // Log file is not large enough to rotate
        }

        $log_dir = dirname( $log_filepath );
        $log_filename = basename( $log_filepath, '.log' ); // Remove .log extension
        $rotated_log_filename = $log_filename . '-' . date( 'Ymd-His' ) . '.log';
        $rotated_log_filepath = trailingslashit( $log_dir ) . $rotated_log_filename;

        if ( ! rename( $log_filepath, $rotated_log_filepath ) ) {
            error_log( sprintf( 'Debug Log Tools: Failed to rotate log file from %s to %s', $log_filepath, $rotated_log_filepath ) );
            return false; // Rotation failed
        }

        // Create a new empty log file
        if ( ! touch( $log_filepath ) ) {
            error_log( sprintf( 'Debug Log Tools: Failed to create new log file at %s after rotation', $log_filepath ) );
            // Non-critical failure, rotation was mostly successful (old log archived)
        }

        return true; // Rotation successful
    }
} 