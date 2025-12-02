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

/*
 *---------------------------------------------------------------
 * CRITICAL SESSION CONFIGURATION
 *---------------------------------------------------------------
 * MUST be set BEFORE CodeIgniter boots to prevent session mismatch.
 *
 * PROBLEM: PHP defaults to session.name='PHPSESSID' and
 * session.save_path='/var/lib/php/sessions', but CodeIgniter
 * expects 'ci_session' and 'writable/session'.
 *
 * If session is started with wrong config, it cannot be changed later,
 * causing login loop (session created with one name/path, read with another).
 */
if (session_status() === PHP_SESSION_NONE) {
    // Set session name to match CodeIgniter config
    session_name('ci_session');

    // Set session save path to CodeIgniter's writable directory
    $sessionPath = dirname(__DIR__) . '/writable/session';
    if (!is_dir($sessionPath)) {
        @mkdir($sessionPath, 0755, true);
    }
    if (is_writable($sessionPath)) {
        session_save_path($sessionPath);
    }
}

// LOAD THE FRAMEWORK BOOTSTRAP FILE
require $paths->systemDirectory . '/Boot.php';

exit(Boot::bootWeb($paths));
