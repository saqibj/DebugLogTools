<?php
/**
 * Debug Log Manager Test Class
 *
 * @package DebugLogTools
 * @subpackage Tests
 */

namespace DebugLogTools\Tests;

use DebugLogTools\Debug_Log_Manager;
use DebugLogTools\Exceptions\Config_Exception;
use DebugLogTools\Exceptions\Log_File_Exception;

class Debug_Log_Manager_Test extends \WP_UnitTestCase {

    /**
     * @var Debug_Log_Manager
     */
    private $manager;

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        $this->manager = new Debug_Log_Manager();
    }

    /**
     * Test is_debug_enabled method
     */
    public function test_is_debug_enabled() {
        // Test when debug is disabled
        $this->assertFalse(Debug_Log_Manager::is_debug_enabled());

        // Test when debug is enabled
        define('WP_DEBUG', true);
        define('WP_DEBUG_LOG', true);
        $this->assertTrue(Debug_Log_Manager::is_debug_enabled());
    }

    /**
     * Test get_log_file_path method
     */
    public function test_get_log_file_path() {
        $expected = WP_CONTENT_DIR . '/debug.log';
        $this->assertEquals($expected, $this->manager->get_log_file_path());
    }

    /**
     * Test handle_debug_toggle method
     */
    public function test_handle_debug_toggle() {
        // Test without proper permissions
        $this->expectException(\WPDieException::class);
        $this->manager->handle_debug_toggle();

        // Test with proper permissions but invalid nonce
        wp_set_current_user(1);
        $_POST['debug_log_tools_nonce'] = 'invalid';
        $this->expectException(\WPDieException::class);
        $this->manager->handle_debug_toggle();
    }

    /**
     * Test cleanup_old_logs method
     */
    public function test_cleanup_old_logs() {
        // Create test log files
        $log_dir = dirname($this->manager->get_log_file_path());
        for ($i = 0; $i < 10; $i++) {
            touch($log_dir . '/debug.log.' . $i);
        }

        // Run cleanup
        $this->manager->cleanup_old_logs(5);

        // Check if only 5 most recent files remain
        $files = glob($log_dir . '/debug.log.*');
        $this->assertLessThanOrEqual(5, count($files));
    }

    /**
     * Test get_log_stats method
     */
    public function test_get_log_stats() {
        $stats = $this->manager->get_log_stats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('size', $stats);
        $this->assertArrayHasKey('modified', $stats);
        $this->assertArrayHasKey('is_writable', $stats);
        $this->assertArrayHasKey('backup_count', $stats);
        $this->assertArrayHasKey('last_rotation', $stats);
    }

    /**
     * Clean up test environment
     */
    public function tearDown(): void {
        // Clean up test files
        $log_dir = dirname($this->manager->get_log_file_path());
        array_map('unlink', glob($log_dir . '/debug.log.*'));
        
        parent::tearDown();
    }
} 