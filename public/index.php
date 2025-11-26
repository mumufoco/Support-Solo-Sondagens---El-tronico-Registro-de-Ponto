<?php

/**
 * Sistema de Ponto Eletrônico Brasileiro
 *
 * Entry point for the application
 *
 * @package    PontoEletronico
 * @author     Mumufoco Team
 * @copyright  2024 Mumufoco
 * @license    MIT
 * @link       https://github.com/mumufoco/ponto-eletronico
 */

use CodeIgniter\Boot;
use Config\Paths;

/*
 *---------------------------------------------------------------
 * CHECK PHP VERSION
 *---------------------------------------------------------------
 */

$minPhpVersion = '8.1'; // If you update this, don't forget to update `spark`.
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    $message = sprintf(
        'Your PHP version must be %s or higher to run CodeIgniter. Current version: %s',
        $minPhpVersion,
        PHP_VERSION,
    );

    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo $message;

    exit(1);
}

/*
 *---------------------------------------------------------------
 * FIX SESSION CONFIGURATION (BEFORE ANYTHING ELSE)
 *---------------------------------------------------------------
 * This fixes "session.gc_divisor must be greater than 0" error
 * that occurs in shared hosting environments where PHP is
 * misconfigured. We configure sessions via PHP code instead
 * of relying on .user.ini which may be ignored or overridden.
 */

// Only configure if session hasn't started yet
if (session_status() === PHP_SESSION_NONE) {
    // Disable warnings temporarily to suppress ini_set errors
    $originalErrorReporting = error_reporting();
    error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

    // Try to fix session configuration via ini_set (may fail on shared hosting)
    if (function_exists('ini_set')) {
        // Session garbage collection - FIX THE MAIN ERROR
        @ini_set('session.gc_probability', '1');
        @ini_set('session.gc_divisor', '100');  // MUST be > 0
        @ini_set('session.gc_maxlifetime', '7200');

        // Session security
        @ini_set('session.use_strict_mode', '1');
        @ini_set('session.use_only_cookies', '1');
        @ini_set('session.cookie_httponly', '1');
        @ini_set('session.cookie_samesite', 'Lax');

        // Session save path
        $sessionPath = dirname(__DIR__) . '/writable/session';
        if (is_dir($sessionPath) && is_writable($sessionPath)) {
            @ini_set('session.save_path', $sessionPath);
        }
    }

    // Restore original error reporting
    error_reporting($originalErrorReporting);
}

/*
 *---------------------------------------------------------------
 * DEFINE ENVIRONMENT CONSTANT EARLY
 *---------------------------------------------------------------
 */

// Define ENVIRONMENT constant before anything else to prevent "Undefined constant" errors
// This is normally done by Boot.php, but we need it earlier for error handling
if (!defined('ENVIRONMENT')) {
    // Try to load from .env file
    $envFile = __DIR__ . '/../.env';
    $environment = 'production'; // Default to production for safety

    if (file_exists($envFile)) {
        $envContent = file_get_contents($envFile);
        if (preg_match('/^\s*CI_ENVIRONMENT\s*=\s*["\']?(\w+)["\']?\s*$/m', $envContent, $matches)) {
            $environment = $matches[1];
        }
    }

    define('ENVIRONMENT', $environment);
}

/*
 *---------------------------------------------------------------
 * LOAD PRODUCTION PHP CONFIGURATION
 *---------------------------------------------------------------
 */

// Removed: php-config-production.php has been deleted
// Session and PHP configuration is now handled by .user.ini
// This prevents conflicts with CodeIgniter's session management

/*
 *---------------------------------------------------------------
 * SET THE CURRENT DIRECTORY
 *---------------------------------------------------------------
 */

// Path to the front controller (this file)
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Ensure the current directory is pointing to the front controller's directory
if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}

/*
 *---------------------------------------------------------------
 * BOOTSTRAP THE APPLICATION
 *---------------------------------------------------------------
 * This process sets up the path constants, loads and registers
 * our autoloader, along with Composer's, loads our constants
 * and fires up an environment-specific bootstrapping.
 */

// LOAD OUR PATHS CONFIG FILE
// This is the line that might need to be changed, depending on your folder structure.
require FCPATH . '../app/Config/Paths.php';
// ^^^ Change this line if you move your application folder

$paths = new Paths();

// LOAD COMPOSER AUTOLOADER
// This must be loaded before Boot.php to ensure all classes are available
if (is_file(FCPATH . '../vendor/autoload.php')) {
    require FCPATH . '../vendor/autoload.php';
}

// Removed: bootstrap-exceptions.php has been deleted
// Exception classes are now properly loaded via Composer autoloader
// This prevents duplicate loading and potential conflicts

// LOAD THE FRAMEWORK BOOTSTRAP FILE
require $paths->systemDirectory . '/Boot.php';

exit(Boot::bootWeb($paths));
