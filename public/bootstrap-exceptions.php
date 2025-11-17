<?php
/**
 * Emergency Exception Classes Loader
 *
 * This file manually loads critical exception classes that may not be
 * autoloaded correctly before DotEnv initialization.
 *
 * LOAD THIS BEFORE: Boot::bootWeb()
 */

// Get the system path
$systemPath = __DIR__ . '/../vendor/codeigniter4/framework/system';

// CRITICAL: Ensure writable/session directory exists before framework boots
// This prevents "Unable to create file writable/session/ci_session..." errors
$sessionPath = __DIR__ . '/../writable/session';
if (!is_dir($sessionPath)) {
    @mkdir($sessionPath, 0777, true);
    @chmod($sessionPath, 0777);
}

// Critical exception classes that MUST be loaded before DotEnv
$criticalClasses = [
    'CodeIgniter\Exceptions\ExceptionInterface' => '/Exceptions/ExceptionInterface.php',
    'CodeIgniter\Exceptions\DebugTraceableTrait' => '/Exceptions/DebugTraceableTrait.php',
    'CodeIgniter\Exceptions\HTTPExceptionInterface' => '/Exceptions/HTTPExceptionInterface.php',
    'CodeIgniter\Exceptions\HasExitCodeInterface' => '/Exceptions/HasExitCodeInterface.php',
    'CodeIgniter\Exceptions\FrameworkException' => '/Exceptions/FrameworkException.php',
    'CodeIgniter\Exceptions\InvalidArgumentException' => '/Exceptions/InvalidArgumentException.php',
    'CodeIgniter\Exceptions\CriticalError' => '/Exceptions/CriticalError.php',
    'CodeIgniter\Exceptions\ConfigException' => '/Exceptions/ConfigException.php',
    'CodeIgniter\Exceptions\LogicException' => '/Exceptions/LogicException.php',
    'CodeIgniter\Exceptions\RuntimeException' => '/Exceptions/RuntimeException.php',
];

// Load each class if not already loaded
foreach ($criticalClasses as $className => $filePath) {
    if (!class_exists($className, false) && !interface_exists($className, false) && !trait_exists($className, false)) {
        $fullPath = $systemPath . $filePath;
        if (file_exists($fullPath)) {
            require_once $fullPath;
        }
    }
}

// Verify critical classes are now available
if (!class_exists('CodeIgniter\Exceptions\InvalidArgumentException')) {
    die('CRITICAL ERROR: Cannot load InvalidArgumentException class. Check vendor/codeigniter4/framework/system/Exceptions/ directory.');
}
