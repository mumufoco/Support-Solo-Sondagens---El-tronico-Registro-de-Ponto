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

// Force critical PHP settings for production environment
// This ensures session cookies are secure even if .htaccess/.user.ini don't work
if (file_exists(__DIR__ . '/php-config-production.php')) {
    require __DIR__ . '/php-config-production.php';
}

// Load exception classes before Boot (fixes InvalidArgumentException error)
if (file_exists(__DIR__ . '/bootstrap-exceptions.php')) {
    require __DIR__ . '/bootstrap-exceptions.php';
}

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
$composerAutoload = FCPATH . '../vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;
}

// LOAD THE FRAMEWORK BOOTSTRAP FILE
require $paths->systemDirectory . '/Boot.php';

exit(Boot::bootWeb($paths));
