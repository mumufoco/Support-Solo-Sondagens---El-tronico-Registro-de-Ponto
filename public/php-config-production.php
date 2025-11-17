<?php

/**
 * Production PHP Configuration Bootstrap
 * Sistema de Ponto Eletrônico
 *
 * This file forces critical PHP settings for production environment
 * Include this at the top of public/index.php
 */

// Force HTTPS cookies for session (CRITICAL!)
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');

// Session garbage collector
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100');
ini_set('session.gc_maxlifetime', '7200');

// Session save path (use project directory)
$session_path = dirname(__DIR__) . '/writable/session';
if (is_dir($session_path) && is_writable($session_path)) {
    ini_set('session.save_path', $session_path);
}

// Error handling (production)
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', dirname(__DIR__) . '/writable/logs/php-errors.log');

// Performance
ini_set('max_execution_time', '300');
ini_set('memory_limit', '256M');
ini_set('post_max_size', '64M');
ini_set('upload_max_filesize', '64M');

// Security
ini_set('expose_php', '0');
ini_set('allow_url_include', '0');
