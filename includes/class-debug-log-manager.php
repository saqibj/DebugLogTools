<?php
/**
 * Debug Log Manager Class
 *
 * Manages WordPress debug log functionality including viewing, rotating, and cleaning logs.
 * Provides methods for enabling/disabling debug logging, log file management, and log analysis.
 *
 * @package    DebugLogTools
 * @subpackage Classes
 * @author     Saqib Jawaid
 * @since      1.0.0
 */

namespace DebugLogTools;

use DebugLogTools\Exceptions\Config_Exception;
use DebugLogTools\Exceptions\Log_File_Exception;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

declare(strict_types=1);

/**
 * Class Debug_Log_Manager
 *
 * Core class responsible for managing WordPress debug log functionality.
 * Handles log file operations, configuration updates, and maintenance tasks.
 *
 * @since      1.0.0
 * @since      2.0.0 Added log rotation functionality
 * @since      3.0.0 Added log analysis and filtering features
 *
 * @package    DebugLogTools
 * @subpackage Classes
 */
class Debug_Log_Manager {

    /**
     * Maximum size for log files in bytes (1MB)
     *
     * Used as threshold for log rotation to prevent excessive file sizes.
     *
     * @since 2.0.0
     * @var   int
     */
    const MAX_LOG_SIZE = 1024 * 1024;

    /**
     * Cache key for log content
     *
     * Used for caching log file contents to reduce disk I/O.
     *
     * @since 3.0.0
     * @var   string
     */
    const CACHE_KEY = 'debug_log_content';

    /**
     * Cache TTL in seconds (1 minute)
     *
     * Determines how long log content should be cached.
     *
     * @since 3.0.0
     * @var   int
     */
    const CACHE_TTL = 60;

    /**
     * Maximum number of lines to keep in memory for log rotation
     *
     * @since 3.2.1
     * @var   int
     */
    const MAX_LINES_IN_MEMORY = 10000;

    /**
     * Maximum file size to process in one go (5MB)
     *
     * @since 3.2.1
     * @var   int
     */
    const MAX_PROCESS_SIZE = 5 * 1024 * 1024;

    /**
     * Initialize the debug log manager.
     *
     * Sets up action hooks and initializes core functionality.
     *
     * @since 1.0.0
     */
    public function __construct() {
        \add_action('admin_post_debug_log_tools_toggle', [$this, 'handle_debug_toggle']);
        \add_action('admin_init', [$this, 'maybe_rotate_log']);
    }

    /**
     * Initialize the manager
     *
     * Sets up cleanup schedules and additional hooks.
     *
     * @since 1.0.0
     * @return void
     */
    public function init() {
        \add_action('debug_log_tools/cleanup', [$this, 'cleanup_old_logs']);
    }

    /**
     * Check if debug logging is enabled
     *
     * Verifies if both WP_DEBUG and WP_DEBUG_LOG constants are enabled.
     *
     * @since  1.0.0
     * @return bool True if debug logging is enabled, false otherwise.
     */
    public static function is_debug_enabled(): bool {
        return defined('\WP_DEBUG') && \WP_DEBUG && 
               defined('\WP_DEBUG_LOG') && \WP_DEBUG_LOG;
    }

    /**
     * Get the path to the debug log file
     *
     * Returns the full system path to WordPress debug.log file.
     *
     * @since  1.0.0
     * @return string Full path to the debug log file
     */
    public function get_log_file_path(): string {
        return \trailingslashit(\WP_CONTENT_DIR) . 'debug.log';
    }

    /**
     * Handle debug logging toggle
     *
     * Processes the form submission for enabling/disabling debug logging.
     * Performs security checks, updates wp-config.php, and handles log file creation.
     *
     * @since 1.0.0
     * @return void
     * @throws Log_File_Exception If log file creation fails
     * @throws Config_Exception If wp-config.php update fails
     */
    public function handle_debug_toggle() {
        if (!\current_user_can('manage_options')) {
            \wp_die(
                \esc_html_x(
                    'You do not have sufficient permissions to access this page.',
                    'permission error',
                    'debug-log-tools'
                ),
                403
            );
        }

        if (!isset($_POST['debug_log_tools_nonce']) || 
            !\wp_verify_nonce($_POST['debug_log_tools_nonce'], 'toggle_debug_log')
        ) {
            \wp_die(
                \esc_html_x(
                    'Security check failed.',
                    'nonce verification error',
                    'debug-log-tools'
                ),
                403
            );
        }

        $enable_debug = isset($_POST['enable_debug_log']);
        
        try {
            $this->update_wp_config($enable_debug);
            
            if ($enable_debug) {
                $log_file = $this->get_log_file_path();
                if (!file_exists($log_file) && is_writable(\WP_CONTENT_DIR)) {
                    if (!@touch($log_file) || !@chmod($log_file, 0644)) {
                        throw new Log_File_Exception(
                            \esc_html_x(
                                'Failed to create log file.',
                                'file creation error',
                                'debug-log-tools'
                            ),
                            Log_File_Exception::FILE_CREATION_ERROR
                        );
                    }
                }
            }
            
            \wp_safe_redirect(
                \add_query_arg(
                    'debug_updated',
                    $enable_debug ? '1' : '0',
                    \admin_url('tools.php?page=debug-log-tools')
                )
            );
            exit;
        } catch (Config_Exception $e) {
            \wp_die(
                \esc_html($e->getMessage()),
                \esc_html_x(
                    'Configuration Error',
                    'error title',
                    'debug-log-tools'
                ),
                array('back_link' => true, 'response' => 500)
            );
        } catch (Log_File_Exception $e) {
            \wp_die(
                \esc_html($e->getMessage()),
                \esc_html_x(
                    'Log File Error',
                    'error title',
                    'debug-log-tools'
                ),
                array('back_link' => true, 'response' => 500)
            );
        }
    }

    /**
     * Update wp-config.php with debug settings
     *
     * Updates WordPress debug constants in wp-config.php.
     * When debug logging is enabled, WP_DEBUG_DISPLAY is always set to false
     * to prevent errors from being displayed while still logging them.
     *
     * @since 1.0.0
     * @param bool $enable_debug Whether to enable debug logging
     * @throws Config_Exception If wp-config.php cannot be modified
     */
    private function update_wp_config($enable_debug) {
        $config_path = $this->locate_wp_config();
        
        if (!$config_path || !is_writable($config_path)) {
            throw new Config_Exception(
                \esc_html__('wp-config.php is not writable.', 'debug-log-tools'),
                Config_Exception::CONFIG_NOT_WRITABLE
            );
        }

        $config_content = file_get_contents($config_path);
        if (false === $config_content) {
            throw new Config_Exception(
                \esc_html__('Failed to read wp-config.php.', 'debug-log-tools'),
                Config_Exception::CONFIG_READ_ERROR
            );
        }

        // Define the debug constants and their values
        $debug_settings = array(
            'WP_DEBUG' => $enable_debug ? 'true' : 'false',
            'WP_DEBUG_LOG' => $enable_debug ? 'true' : 'false',
            'WP_DEBUG_DISPLAY' => 'false' // Always false to prevent display while still logging
        );

        // Create backup
        $backup_path = $config_path . '.bak';
        if (!copy($config_path, $backup_path)) {
            throw new Config_Exception(
                \esc_html__('Failed to create backup of wp-config.php.', 'debug-log-tools'),
                Config_Exception::CONFIG_WRITE_ERROR
            );
        }

        try {
            foreach ($debug_settings as $constant => $value) {
                $pattern = "/define\s*\(\s*['\"]" . $constant . "['\"]\s*,\s*(true|false)\s*\)/i";
                $replacement = "define('" . $constant . "', " . $value . ")";

                if (preg_match($pattern, $config_content)) {
                    $config_content = preg_replace($pattern, $replacement, $config_content);
                } else {
                    // Add constants if they don't exist, after the opening PHP tag
                    $config_content = preg_replace(
                        "/(<\?php)/i",
                        "<?php\ndefine('" . $constant . "', " . $value . ");",
                        $config_content,
                        1
                    );
                }
            }

            // Write changes atomically using a temporary file
            $temp_path = $config_path . '.tmp';
            if (false === file_put_contents($temp_path, $config_content)) {
                throw new Config_Exception(
                    \esc_html__('Failed to write temporary config file.', 'debug-log-tools'),
                    Config_Exception::CONFIG_WRITE_ERROR
                );
            }

            // Ensure proper permissions
            chmod($temp_path, fileperms($config_path));

            // Rename temporary file to actual config file
            if (!rename($temp_path, $config_path)) {
                unlink($temp_path);
                throw new Config_Exception(
                    \esc_html__('Failed to update wp-config.php.', 'debug-log-tools'),
                    Config_Exception::CONFIG_WRITE_ERROR
                );
            }
        } catch (\Exception $e) {
            // Restore backup if something went wrong
            if (file_exists($backup_path)) {
                copy($backup_path, $config_path);
            }
            throw $e;
        } finally {
            // Clean up backup file
            if (file_exists($backup_path)) {
                unlink($backup_path);
            }
        }
    }

    /**
     * Locate wp-config.php file
     *
     * Attempts to find the WordPress configuration file in standard locations.
     * Checks both in ABSPATH and one directory up from ABSPATH.
     *
     * @since 1.0.0
     * @return string|false Path to wp-config.php or false if not found
     */
    private function locate_wp_config() {
        $config_path = ABSPATH . 'wp-config.php';
        
        if ( ! file_exists( $config_path ) ) {
            $config_path = dirname( ABSPATH ) . '/wp-config.php';
        }
        
        return file_exists( $config_path ) ? $config_path : false;
    }

    /**
     * Maybe rotate the log file if it's too large
     *
     * Checks the current log file size and initiates rotation if it exceeds MAX_LOG_SIZE.
     * This prevents the log file from growing too large and impacting performance.
     *
     * @since 2.0.0
     * @return void
     */
    public function maybe_rotate_log() {
        $log_file = $this->get_log_file_path();
        
        if ( ! file_exists( $log_file ) ) {
            return;
        }

        $size = filesize( $log_file );
        if ( $size > self::MAX_LOG_SIZE ) {
            $this->rotate_log();
        }
    }

    /**
     * Rotate the log file with performance optimizations
     *
     * @since 2.0.0
     * @return void
     * @throws Log_File_Exception If rotation operations fail
     */
    private function rotate_log() {
        $log_file = $this->get_log_file_path();
        $backup_file = $log_file . '.' . date('Y-m-d-H-i-s');

        // Use streams for better memory efficiency
        $read_handle = @fopen($log_file, 'r');
        $write_handle = @fopen($backup_file, 'w');

        if (!$read_handle || !$write_handle) {
            throw new Log_File_Exception(
                \esc_html__('Failed to open log files for rotation.', 'debug-log-tools'),
                Log_File_Exception::FILE_ROTATION_ERROR
            );
        }

        try {
            // Copy content in chunks
            while (!feof($read_handle)) {
                $chunk = fread($read_handle, 8192);
                if ($chunk === false || fwrite($write_handle, $chunk) === false) {
                    throw new Log_File_Exception(
                        \esc_html__('Failed to write to backup log file.', 'debug-log-tools'),
                        Log_File_Exception::FILE_ROTATION_ERROR
                    );
                }
            }

            // Truncate the original file
            fclose($read_handle);
            $read_handle = fopen($log_file, 'w');
            if ($read_handle === false) {
                throw new Log_File_Exception(
                    \esc_html__('Failed to truncate log file.', 'debug-log-tools'),
                    Log_File_Exception::FILE_ROTATION_ERROR
                );
            }

            // Clean up old logs asynchronously
            wp_schedule_single_event(time(), 'debug_log_tools_cleanup_logs');
        } finally {
            if (is_resource($read_handle)) {
                fclose($read_handle);
            }
            if (is_resource($write_handle)) {
                fclose($write_handle);
            }
        }
    }

    /**
     * Clean up old log files with performance optimizations
     *
     * Implements batched processing and memory-efficient file operations.
     * Uses background processing for large cleanup operations.
     *
     * @since 2.0.0
     * @param int $batch_size Number of files to process per batch
     * @return void
     */
    public function cleanup_old_logs(int $batch_size = 50) {
        $log_dir = dirname($this->get_log_file_path());
        
        // Get all backup files
        $files = glob($log_dir . '/debug.log.*');
        if (!is_array($files)) {
            return;
        }

        // Sort files by modification time
        $file_times = array();
        foreach ($files as $file) {
            $file_times[$file] = filemtime($file);
        }
        arsort($file_times); // Sort newest to oldest

        // Keep track of processed files
        $processed = 0;
        $total_files = count($file_times);

        // Process files in batches
        foreach ($file_times as $file => $mtime) {
            // Keep the 5 most recent files
            if ($processed < 5) {
                $processed++;
                continue;
            }

            // Delete the file
            if (@unlink($file)) {
                $processed++;
            }

            // If we've hit our batch limit, schedule the next batch
            if ($processed % $batch_size === 0 && $processed < $total_files) {
                \wp_schedule_single_event(
                    time() + 60,
                    'debug_log_tools_cleanup_logs',
                    array($batch_size)
                );
                break;
            }

            // Implement rate limiting
            if ($processed % 10 === 0) {
                usleep(10000); // 10ms pause every 10 files
            }
        }

        // Clear file stat cache to prevent memory leaks
        clearstatcache();
    }

    /**
     * Get log file statistics with caching
     *
     * @since 3.2.1
     * @return array Log file statistics
     */
    public function get_log_stats(): array {
        $cache_key = 'debug_log_tools_stats';
        
        // Try object cache first
        if (wp_using_ext_object_cache()) {
            $stats = wp_cache_get($cache_key, 'debug-log-tools');
            if (false !== $stats) {
                return $stats;
            }
        } else {
            $stats = get_transient($cache_key);
            if (false !== $stats) {
                return $stats;
            }
        }

        $log_file = $this->get_log_file_path();
        $stats = array(
            'size' => file_exists($log_file) ? filesize($log_file) : 0,
            'modified' => file_exists($log_file) ? filemtime($log_file) : 0,
            'is_writable' => is_writable($log_file),
            'backup_count' => count(glob(dirname($log_file) . '/debug.log.*')),
            'last_rotation' => get_option('debug_log_tools_last_rotation', 0)
        );

        // Cache the results
        if (wp_using_ext_object_cache()) {
            wp_cache_set($cache_key, $stats, 'debug-log-tools', 5 * MINUTE_IN_SECONDS);
        } else {
            set_transient($cache_key, $stats, 5 * MINUTE_IN_SECONDS);
        }

        return $stats;
    }

    /**
     * Safely write contents to a file using a temporary file
     *
     * Uses an atomic operation to write file contents by first writing to a temporary file
     * and then moving it to the target location. This prevents partial writes and corruption.
     *
     * @since 3.0.0
     * @param string $path    The target file path
     * @param string $content The content to write
     * @return bool True on success
     * @throws Log_File_Exception If file operations fail
     */
    private function safe_file_put_contents($path, $content) {
        $temp = tempnam(dirname($path), 'tmp');
        if (false === file_put_contents($temp, $content)) {
            unlink($temp);
            throw new Log_File_Exception(
                \esc_html__('Failed to write temporary file.', 'debug-log-tools'),
                Log_File_Exception::FILE_WRITE_ERROR
            );
        }
        
        if (!rename($temp, $path)) {
            unlink($temp);
            throw new Log_File_Exception(
                \esc_html__('Failed to move temporary file.', 'debug-log-tools'),
                Log_File_Exception::FILE_WRITE_ERROR
            );
        }
        
        return true;
    }

    /**
     * Gets the content of the debug log file in chunks with caching and filtering/searching.
     *
     * Implements memory-efficient streaming with configurable chunk sizes and caching.
     * Uses generators to prevent loading entire file into memory.
     * Implements rate limiting to prevent server overload.
     *
     * @param string      $log_filepath    Path to the log file.
     * @param int         $chunk_size      Size of each chunk in bytes.
     * @param int         $cache_expiry    Cache expiration time in seconds.
     * @param string|null $filter_keywords Comma-separated keywords to filter log entries.
     * @param string|null $search_term     Term to search for in log entries.
     * @return Generator|string[] Yields chunks of the log file content.
     * @throws Log_File_Exception If file operations fail.
     */
    public function get_log_content_cached(
        string $log_filepath,
        int $chunk_size = 4096,
        int $cache_expiry = 300,
        ?string $filter_keywords = null,
        ?string $search_term = null
    ): Generator {
        // Generate unique cache key based on file path and filters
        $cache_key = 'debug_log_tools_log_content_' . md5(
            $log_filepath . $filter_keywords . $search_term
        );

        // Try to get from cache first
        $cached_content = \get_transient($cache_key);
        if (false !== $cached_content) {
            foreach ($cached_content as $chunk) {
                yield $chunk;
            }
            return;
        }

        // Validate file size before processing
        $file_size = filesize($log_filepath);
        if ($file_size > self::MAX_PROCESS_SIZE) {
            $chunk_size = max($chunk_size, (int)($file_size / self::MAX_LINES_IN_MEMORY));
        }

        // Initialize storage for processed chunks
        $log_chunks = [];
        $total_size = 0;

        // Open file with better error handling
        $file_handle = @fopen($log_filepath, 'r');
        if (!$file_handle) {
            throw new Log_File_Exception(
                \esc_html__('Failed to open debug log file.', 'debug-log-tools'),
                Log_File_Exception::FILE_OPEN_ERROR
            );
        }

        try {
            // Process file in chunks
            while (!feof($file_handle) && $total_size < self::MAX_PROCESS_SIZE) {
                $chunk = fread($file_handle, $chunk_size);
                if ($chunk === false) {
                    break;
                }

                $total_size += strlen($chunk);
                $lines = $this->process_log_chunk(
                    $chunk,
                    $filter_keywords,
                    $search_term
                );

                foreach ($lines as $line) {
                    $log_chunks[] = $line;
                    yield $line;

                    // Prevent memory exhaustion
                    if (count($log_chunks) >= self::MAX_LINES_IN_MEMORY) {
                        array_shift($log_chunks);
                    }
                }

                // Implement rate limiting
                if ($total_size % (1024 * 1024) === 0) { // Every 1MB
                    usleep(10000); // 10ms pause
                }
            }

            // Cache the processed chunks
            if (!empty($log_chunks)) {
                \set_transient($cache_key, $log_chunks, $cache_expiry);
            }
        } finally {
            fclose($file_handle);
        }
    }

    /**
     * Process a chunk of log content with filtering and searching
     *
     * @param string      $chunk          The chunk of log content to process
     * @param string|null $filter_keywords Comma-separated filter keywords
     * @param string|null $search_term     Search term to look for
     * @return array Processed and filtered lines
     */
    private function process_log_chunk(
        string $chunk,
        ?string $filter_keywords,
        ?string $search_term
    ): array {
        $lines = explode("\n", $chunk);
        $processed_lines = [];
        $filter_keywords_array = $filter_keywords 
            ? array_map('trim', explode(',', $filter_keywords))
            : [];

        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }

            // Apply keyword filtering
            if (!empty($filter_keywords_array)) {
                $matches_filter = false;
                foreach ($filter_keywords_array as $keyword) {
                    if (stripos($line, $keyword) !== false) {
                        $matches_filter = true;
                        break;
                    }
                }
                if (!$matches_filter) {
                    continue;
                }
            }

            // Apply search term filtering
            if (!empty($search_term) && stripos($line, $search_term) === false) {
                continue;
            }

            $processed_lines[] = $line . "\n";
        }

        return $processed_lines;
    }

    /**
     * Gets new lines added to the debug log file since the last read.
     *
     * Reads and returns only the new content added to the log file since the last check.
     * Handles file truncation by resetting the position counter when file size decreases.
     * Provides efficient way to implement live log tailing functionality.
     *
     * @since 3.0.0
     * @param string $log_filepath Path to the log file.
     * @param int    $last_size    The file size from the last read, to read only new lines.
     * @return array Array of new log lines.
     * @throws Log_File_Exception If the log file cannot be accessed
     */
    public function get_new_log_lines( string $log_filepath, int $last_size ): array {
        if ( ! file_exists( $log_filepath ) ) {
            throw new Log_File_Exception(
                \esc_html__( 'Debug log file not found.', 'debug-log-tools' ),
                Log_File_Exception::FILE_NOT_FOUND
            );
        }
        if ( ! is_readable( $log_filepath ) ) {
            throw new Log_File_Exception(
                \esc_html__( 'Debug log file is not readable.', 'debug-log-tools' ),
                Log_File_Exception::FILE_NOT_READABLE
            );
        }

        $current_size = filesize( $log_filepath );
        if ( $current_size < $last_size ) {
            $last_size = 0; // File has been truncated, reset last size
        }

        $new_lines = [];
        $file_handle = fopen( $log_filepath, 'r' );
        if ( ! $file_handle ) {
            throw new Log_File_Exception(
                \esc_html__( 'Failed to open debug log file.', 'debug-log-tools' ),
                Log_File_Exception::FILE_OPEN_ERROR
            );
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
     * Toggle debug logging
     *
     * Updates the WordPress configuration file to enable or disable debug logging.
     * Modifies WP_DEBUG and WP_DEBUG_LOG constants in wp-config.php.
     * Creates the constants if they don't exist, otherwise updates existing values.
     *
     * @since 1.0.0
     * @param bool $enable Whether to enable debug logging
     * @throws \Exception If wp-config.php is not writable or update fails
     * @return void
     */
    public function toggle_debug($enable) {
        $wp_config_path = ABSPATH . 'wp-config.php';
        
        if (!is_writable($wp_config_path)) {
            throw new \Exception(__('wp-config.php is not writable', 'debug-log-tools'));
        }

        $wp_config = file_get_contents($wp_config_path);
        
        // Replace existing debug constants
        $patterns = array(
            '/define\(\s*\'WP_DEBUG\',\s*(true|false)\s*\);/',
            '/define\(\s*\'WP_DEBUG_LOG\',\s*(true|false)\s*\);/'
        );
        
        $replacements = array(
            "define('WP_DEBUG', " . ($enable ? 'true' : 'false') . ");",
            "define('WP_DEBUG_LOG', " . ($enable ? 'true' : 'false') . ");"
        );
        
        // If constants don't exist, add them before "That's all, stop editing"
        if (!preg_match('/define\(\s*\'WP_DEBUG\'/i', $wp_config)) {
            $wp_config = preg_replace(
                '/\/\* That\'s all, stop editing! Happy publishing. \*\//',
                "define('WP_DEBUG', " . ($enable ? 'true' : 'false') . ");\n" .
                "define('WP_DEBUG_LOG', " . ($enable ? 'true' : 'false') . ");\n" .
                "/* That's all, stop editing! Happy publishing. */",
                $wp_config
            );
        } else {
            $wp_config = preg_replace($patterns, $replacements, $wp_config);
        }

        if (!file_put_contents($wp_config_path, $wp_config)) {
            throw new \Exception(__('Failed to update wp-config.php', 'debug-log-tools'));
        }
    }

    /**
     * Rotate the log file
     *
     * Creates a backup of the current log file and starts a new one.
     * Uses atomic operations and proper error handling.
     *
     * @since 3.2.2
     * @throws Log_File_Exception If rotation operations fail
     */
    public function rotate_log_file() {
        $log_file = $this->get_log_file_path();
        
        if (!file_exists($log_file)) {
            return;
        }

        $backup_dir = trailingslashit(WP_CONTENT_DIR) . 'debug-logs/backups';
        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
        }

        $backup_file = $backup_dir . '/debug-' . date('Y-m-d-H-i-s') . '.log';

        try {
            // Use streams for better memory efficiency
            $read_handle = @fopen($log_file, 'r');
            $write_handle = @fopen($backup_file, 'w');

            if (!$read_handle || !$write_handle) {
                throw new Log_File_Exception(
                    esc_html__('Failed to open log files for rotation.', 'debug-log-tools'),
                    Log_File_Exception::FILE_ROTATION_ERROR
                );
            }

            // Copy content in chunks
            while (!feof($read_handle)) {
                $chunk = fread($read_handle, 8192);
                if ($chunk === false || fwrite($write_handle, $chunk) === false) {
                    throw new Log_File_Exception(
                        esc_html__('Failed to write to backup log file.', 'debug-log-tools'),
                        Log_File_Exception::FILE_ROTATION_ERROR
                    );
                }
            }

            // Close handles before truncating
            fclose($read_handle);
            fclose($write_handle);

            // Truncate the original file atomically
            $temp = tempnam(dirname($log_file), 'tmp');
            if (false === $temp || !rename($temp, $log_file)) {
                throw new Log_File_Exception(
                    esc_html__('Failed to truncate log file.', 'debug-log-tools'),
                    Log_File_Exception::FILE_ROTATION_ERROR
                );
            }

            // Schedule cleanup of old backups
            if (!wp_next_scheduled('debug_log_tools_cleanup_backups')) {
                wp_schedule_single_event(time() + DAY_IN_SECONDS, 'debug_log_tools_cleanup_backups');
            }

        } catch (Exception $e) {
            // Clean up resources
            if (isset($read_handle) && is_resource($read_handle)) {
                fclose($read_handle);
            }
            if (isset($write_handle) && is_resource($write_handle)) {
                fclose($write_handle);
            }
            if (isset($temp) && file_exists($temp)) {
                unlink($temp);
            }
            
            // Log error and rethrow
            error_log('Debug Log Tools: Log rotation failed - ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Clean up old backup log files
     *
     * Removes backup files older than 30 days
     */
    public function cleanup_backup_logs() {
        $backup_dir = trailingslashit(WP_CONTENT_DIR) . 'debug-logs/backups';
        if (!is_dir($backup_dir)) {
            return;
        }

        $files = glob($backup_dir . '/debug-*.log');
        $now = time();
        
        foreach ($files as $file) {
            if (is_file($file) && $now - filemtime($file) >= 30 * DAY_IN_SECONDS) {
                @unlink($file);
            }
        }
    }

    /**
     * Get log file contents safely
     *
     * @param string $log_file Path to log file
     * @param int    $max_size Maximum size to read in bytes
     * @return string Log contents
     * @throws Log_File_Exception If file operations fail
     */
    private function get_log_contents_safely($log_file, $max_size = self::MAX_LOG_SIZE) {
        if (!file_exists($log_file)) {
            throw new Log_File_Exception(
                esc_html__('Log file does not exist.', 'debug-log-tools'),
                Log_File_Exception::FILE_NOT_FOUND
            );
        }

        if (!is_readable($log_file)) {
            throw new Log_File_Exception(
                esc_html__('Log file is not readable.', 'debug-log-tools'),
                Log_File_Exception::FILE_NOT_READABLE
            );
        }

        $handle = fopen($log_file, 'r');
        if (false === $handle) {
            throw new Log_File_Exception(
                esc_html__('Failed to open log file.', 'debug-log-tools'),
                Log_File_Exception::FILE_OPEN_ERROR
            );
        }

        try {
            $size = filesize($log_file);
            if ($size > $max_size) {
                if (-1 === fseek($handle, -$max_size, SEEK_END)) {
                    throw new Log_File_Exception(
                        esc_html__('Failed to seek in log file.', 'debug-log-tools'),
                        Log_File_Exception::FILE_SEEK_ERROR
                    );
                }
                // Skip first incomplete line
                fgets($handle);
            }

            $contents = '';
            while (!feof($handle)) {
                $chunk = fread($handle, 8192); // Read in chunks
                if (false === $chunk) {
                    throw new Log_File_Exception(
                        esc_html__('Failed to read from log file.', 'debug-log-tools'),
                        Log_File_Exception::FILE_READ_ERROR
                    );
                }
                $contents .= $chunk;
            }

            return $contents;
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
    }
} 