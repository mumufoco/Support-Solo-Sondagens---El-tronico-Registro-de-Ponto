<?php
/**
 * Production PHP Configuration
 *
 * This file forces critical PHP settings at runtime using ini_set().
 * It's loaded early in public/index.php to ensure settings are applied
 * even if .htaccess or .user.ini are not read by the server.
 *
 * CRITICAL: Session configuration must be set BEFORE session_start()
 */

// ============================================================================
// SESSION CONFIGURATION (CRITICAL!)
// ============================================================================

// Session save path - use project directory instead of system directory
$sessionPath = __DIR__ . '/../writable/session';

// Create directory if it doesn't exist with proper error handling
if (!is_dir($sessionPath)) {
    // Try to create directory
    if (@mkdir($sessionPath, 0777, true)) {
        // Set permissions explicitly after creation
        @chmod($sessionPath, 0777);

        // Create security index.html
        $indexFile = $sessionPath . '/index.html';
        if (!file_exists($indexFile)) {
            @file_put_contents($indexFile, '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Directory access is forbidden.</h1></body></html>');
        }

        // Create .htaccess for extra security
        $htaccessFile = $sessionPath . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            @file_put_contents($htaccessFile, "Deny from all\n");
        }
    }
}

// Ensure directory has correct permissions
if (is_dir($sessionPath)) {
    @chmod($sessionPath, 0777);
}

// Set session save path (MUST be set before session_start)
if (is_dir($sessionPath)) {
    ini_set('session.save_path', $sessionPath);

    // Verify it's writable by trying to create a test file
    $testFile = $sessionPath . '/.test_' . time();
    if (@touch($testFile)) {
        @unlink($testFile);
    }
}

// Force HTTPS-only cookies in production (CRITICAL for security!)
ini_set('session.cookie_secure', '1');      // Only send cookies over HTTPS
ini_set('session.cookie_httponly', '1');    // Prevent JavaScript access to cookies
ini_set('session.cookie_samesite', 'Lax');  // CSRF protection

// Session garbage collector settings
ini_set('session.gc_probability', '1');     // Probability of running GC
ini_set('session.gc_divisor', '100');       // 1% chance per request
ini_set('session.gc_maxlifetime', '7200');  // 2 hours session lifetime

// ============================================================================
// ERROR HANDLING (Production)
// ============================================================================

// Hide errors from users (display_errors = Off)
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// But log all errors to file
ini_set('log_errors', '1');
$errorLogPath = __DIR__ . '/../writable/logs/php-errors.log';
ini_set('error_log', $errorLogPath);

// Report all errors internally
error_reporting(E_ALL);

// ============================================================================
// PERFORMANCE OPTIMIZATION
// ============================================================================

// Increase execution time for long-running operations
ini_set('max_execution_time', '300');      // 5 minutes

// Increase memory limit for complex operations
ini_set('memory_limit', '256M');

// Enable output compression to reduce bandwidth
ini_set('zlib.output_compression', '1');
ini_set('zlib.output_compression_level', '6');

// ============================================================================
// FILE UPLOAD SETTINGS
// ============================================================================

// Allow file uploads (for biometric images, etc)
ini_set('file_uploads', '1');
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
ini_set('max_file_uploads', '20');

// Upload temporary directory
$uploadTmpDir = __DIR__ . '/../writable/uploads/tmp';
if (!is_dir($uploadTmpDir)) {
    @mkdir($uploadTmpDir, 0777, true);
}
if (is_dir($uploadTmpDir) && is_writable($uploadTmpDir)) {
    ini_set('upload_tmp_dir', $uploadTmpDir);
}

// ============================================================================
// TIMEZONE
// ============================================================================

// Set timezone to Brazil (São Paulo)
date_default_timezone_set('America/Sao_Paulo');

// ============================================================================
// SECURITY HEADERS (Additional layer)
// ============================================================================

// These are also set in .htaccess, but we set them here as a fallback
if (!headers_sent()) {
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');

    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');

    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');

    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
