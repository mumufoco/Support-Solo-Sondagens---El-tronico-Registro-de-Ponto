<?php
/**
 * Index.php SEM exit() - Para diagnóstico
 * Cópia de index.php mas retorna em vez de exit()
 */

use CodeIgniter\Boot;
use Config\Paths;

$minPhpVersion = '8.1';
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    $message = sprintf(
        'Your PHP version must be %s or higher to run CodeIgniter. Current version: %s',
        $minPhpVersion,
        PHP_VERSION,
    );

    header('HTTP/1.1 503 Service Unavailable.', true, 503);
    echo $message;
    return 1;
}

// Define FCPATH
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Ensure current directory
if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}

// Load paths
require FCPATH . '../app/Config/Paths.php';
$paths = new Paths();

// Load autoloader
if (is_file(FCPATH . '../vendor/autoload.php')) {
    require FCPATH . '../vendor/autoload.php';
}

// Load Boot
require $paths->systemDirectory . '/Boot.php';

// *** DIFERENÇA: Retorna em vez de exit() ***
return Boot::bootWeb($paths);
