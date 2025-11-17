<?php
/**
 * Emergency Session Fix - Execute via Browser
 *
 * This script fixes the "Unable to create file writable/session" error
 * Access: http://ponto.supportsondagens.com.br/fix-session-error.php
 *
 * IMPORTANT: DELETE THIS FILE AFTER RUNNING!
 */

// Prevent execution if not accessed directly
if (php_sapi_name() === 'cli') {
    die("This script must be run via web browser, not CLI.\n");
}

// Security: Only allow execution once per day
$lockFile = __DIR__ . '/../writable/session-fix.lock';
if (file_exists($lockFile)) {
    $lastRun = (int)file_get_contents($lockFile);
    if (time() - $lastRun < 3600) { // 1 hour
        die('Script already ran recently. Wait 1 hour or delete writable/session-fix.lock to run again.');
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Session Fix</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; line-height: 1.6; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; margin-top: 0; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .step { background: #ecf0f1; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #3498db; }
        .ok { background: #d5f4e6; border-left-color: #27ae60; color: #27ae60; }
        .error { background: #fadbd8; border-left-color: #e74c3c; color: #e74c3c; }
        .warning { background: #fef5e7; border-left-color: #f39c12; color: #f39c12; }
        code { background: #34495e; color: #ecf0f1; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .btn { display: inline-block; padding: 12px 24px; background: #e74c3c; color: white; text-decoration: none; border-radius: 4px; margin-top: 20px; }
        .btn:hover { background: #c0392b; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Emergency Session Fix</h1>
        <p><strong>Error being fixed:</strong> <code>Unable to create file writable/session/ci_session...</code></p>

<?php

$rootPath = __DIR__ . '/..';
$writablePath = $rootPath . '/writable';
$sessionPath = $writablePath . '/session';
$envFile = $rootPath . '/.env';
$phpConfigFile = __DIR__ . '/php-config-production.php';

$fixes = [];
$errors = [];

// Step 1: Check and create writable/session directory
echo '<div class="step">';
echo '<strong>Step 1: Check writable/session directory</strong><br>';

if (!is_dir($sessionPath)) {
    if (@mkdir($sessionPath, 0777, true)) {
        echo '✅ Created directory: writable/session<br>';
        $fixes[] = 'Created writable/session directory';
    } else {
        echo '❌ FAILED to create writable/session<br>';
        $errors[] = 'Cannot create writable/session - check parent directory permissions';
    }
} else {
    echo '✅ Directory exists: writable/session<br>';
}

// Check if writable
if (is_writable($sessionPath)) {
    echo '✅ Directory is writable<br>';
} else {
    if (@chmod($sessionPath, 0777)) {
        echo '✅ Fixed permissions on writable/session<br>';
        $fixes[] = 'Fixed writable/session permissions';
    } else {
        echo '❌ Directory is NOT writable (permission denied)<br>';
        $errors[] = 'writable/session is not writable - manual fix needed';
    }
}

// Create index.html for security
$indexFile = $sessionPath . '/index.html';
if (!file_exists($indexFile)) {
    $indexContent = '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Directory access is forbidden.</h1></body></html>';
    if (@file_put_contents($indexFile, $indexContent)) {
        echo '✅ Created index.html security file<br>';
        $fixes[] = 'Created security index.html';
    }
}

echo '</div>';

// Step 2: Check .env configuration
echo '<div class="step">';
echo '<strong>Step 2: Check .env configuration</strong><br>';

if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);

    // Check session.savePath
    if (preg_match('/session\.savePath\s*=\s*["\']?([^"\'\r\n]*)["\']?/', $envContent, $matches)) {
        $currentPath = trim($matches[1]);
        echo 'Current setting: <code>session.savePath = ' . htmlspecialchars($currentPath) . '</code><br>';

        if (empty($currentPath) || $currentPath === "''") {
            echo '⚠️ Session path is EMPTY - needs fixing<br>';

            // Try to fix it
            $newEnvContent = preg_replace(
                '/session\.savePath\s*=\s*["\']?[^"\'\r\n]*["\']?/',
                "session.savePath = 'writable/session'",
                $envContent
            );

            if (@file_put_contents($envFile, $newEnvContent)) {
                echo '✅ FIXED .env - set session.savePath = \'writable/session\'<br>';
                $fixes[] = 'Fixed .env session.savePath';
            } else {
                echo '❌ FAILED to update .env (permission denied)<br>';
                $errors[] = '.env not writable - manual fix needed';
            }
        } elseif ($currentPath === 'writable/session') {
            echo '✅ Session path is correctly configured<br>';
        } else {
            echo '⚠️ Session path points to: ' . htmlspecialchars($currentPath) . '<br>';
        }
    } else {
        echo '⚠️ session.savePath not found in .env<br>';
        $errors[] = 'session.savePath missing from .env';
    }
} else {
    echo '❌ .env file not found!<br>';
    $errors[] = '.env file missing';
}

echo '</div>';

// Step 3: Check php-config-production.php
echo '<div class="step">';
echo '<strong>Step 3: Check php-config-production.php</strong><br>';

if (file_exists($phpConfigFile)) {
    echo '✅ File exists: public/php-config-production.php<br>';

    // Verify it sets session path
    $configContent = file_get_contents($phpConfigFile);
    if (strpos($configContent, 'session.save_path') !== false) {
        echo '✅ File sets session.save_path correctly<br>';
    } else {
        echo '⚠️ File exists but may be outdated<br>';
        $errors[] = 'php-config-production.php may need update';
    }
} else {
    echo '❌ File missing: public/php-config-production.php<br>';
    echo 'Attempting to create it...<br>';

    $phpConfigContent = <<<'PHP'
<?php
/**
 * Production PHP Configuration
 * Forces critical PHP settings at runtime
 */

// Session save path - use project directory
$sessionPath = __DIR__ . '/../writable/session';

// Create directory if it doesn't exist
if (!is_dir($sessionPath)) {
    @mkdir($sessionPath, 0777, true);
}

// Set session save path (MUST be set before session_start)
if (is_dir($sessionPath) && is_writable($sessionPath)) {
    ini_set('session.save_path', $sessionPath);
}

// Force HTTPS-only cookies in production
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');

// Session garbage collector
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100');
ini_set('session.gc_maxlifetime', '7200');

// Error handling (production)
ini_set('display_errors', '0');
ini_set('log_errors', '1');
$errorLogPath = __DIR__ . '/../writable/logs/php-errors.log';
ini_set('error_log', $errorLogPath);

// Performance
ini_set('max_execution_time', '300');
ini_set('memory_limit', '256M');

// Timezone
date_default_timezone_set('America/Sao_Paulo');
PHP;

    if (@file_put_contents($phpConfigFile, $phpConfigContent)) {
        echo '✅ CREATED php-config-production.php<br>';
        $fixes[] = 'Created php-config-production.php';
    } else {
        echo '❌ FAILED to create php-config-production.php<br>';
        $errors[] = 'Cannot create php-config-production.php';
    }
}

echo '</div>';

// Step 4: Check if index.php loads php-config-production.php
echo '<div class="step">';
echo '<strong>Step 4: Check public/index.php integration</strong><br>';

$indexPhpFile = __DIR__ . '/index.php';
if (file_exists($indexPhpFile)) {
    $indexContent = file_get_contents($indexPhpFile);
    if (strpos($indexContent, 'php-config-production.php') !== false) {
        echo '✅ index.php loads php-config-production.php<br>';
    } else {
        echo '⚠️ index.php does NOT load php-config-production.php<br>';
        echo 'Manual fix needed: Add this code to public/index.php after line 36:<br>';
        echo '<pre>if (file_exists(__DIR__ . \'/php-config-production.php\')) {
    require __DIR__ . \'/php-config-production.php\';
}</pre>';
        $errors[] = 'index.php not loading php-config-production.php';
    }
} else {
    echo '❌ public/index.php not found!<br>';
    $errors[] = 'index.php missing';
}

echo '</div>';

// Step 5: Test session creation
echo '<div class="step">';
echo '<strong>Step 5: Test session file creation</strong><br>';

$testFile = $sessionPath . '/test_' . time() . '.tmp';
if (@touch($testFile)) {
    echo '✅ SUCCESS! Can create files in writable/session<br>';
    @unlink($testFile);
} else {
    echo '❌ FAILED! Cannot create files in writable/session<br>';
    $errors[] = 'Cannot write to writable/session';
}

echo '</div>';

// Summary
echo '<div class="step ' . (count($errors) === 0 ? 'ok' : (count($fixes) > 0 ? 'warning' : 'error')) . '">';
echo '<h2>📋 Summary</h2>';

if (count($fixes) > 0) {
    echo '<strong>Fixes Applied:</strong><ul>';
    foreach ($fixes as $fix) {
        echo '<li>✅ ' . htmlspecialchars($fix) . '</li>';
    }
    echo '</ul>';
}

if (count($errors) > 0) {
    echo '<strong>Remaining Issues:</strong><ul>';
    foreach ($errors as $error) {
        echo '<li>❌ ' . htmlspecialchars($error) . '</li>';
    }
    echo '</ul>';
}

if (count($errors) === 0) {
    echo '<h3 style="color: #27ae60;">✅ ALL CHECKS PASSED!</h3>';
    echo '<p>The session error should be fixed now. Reload your application.</p>';

    // Create lock file
    @file_put_contents($lockFile, time());
} else {
    echo '<h3 style="color: #e74c3c;">⚠️ MANUAL INTERVENTION REQUIRED</h3>';
    echo '<p>Some issues require manual fixing via SSH or cPanel:</p>';
    echo '<ol>';
    echo '<li>Access SSH or cPanel File Manager</li>';
    echo '<li>Fix the issues listed above</li>';
    echo '<li>Run this script again to verify</li>';
    echo '</ol>';
}

echo '</div>';

// Current PHP info
echo '<div class="step">';
echo '<strong>Current PHP Configuration:</strong><br>';
echo 'session.save_path: <code>' . ini_get('session.save_path') . '</code><br>';
echo 'session.save_handler: <code>' . ini_get('session.save_handler') . '</code><br>';
echo 'session.cookie_secure: <code>' . (ini_get('session.cookie_secure') ? 'On' : 'Off') . '</code><br>';
echo 'open_basedir: <code>' . (ini_get('open_basedir') ?: 'None') . '</code><br>';
echo '</div>';

?>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #ecf0f1;">
            <h3 style="color: #e74c3c;">⚠️ SECURITY WARNING</h3>
            <p><strong>DELETE THIS FILE IMMEDIATELY AFTER RUNNING!</strong></p>
            <p>This file contains diagnostic information that should not be public.</p>
            <a href="?delete=1" class="btn">🗑️ Delete This File Now</a>
        </div>

        <?php
        // Self-delete functionality
        if (isset($_GET['delete'])) {
            if (@unlink(__FILE__)) {
                echo '<div class="step ok"><h3>✅ File Deleted Successfully</h3><p>This script has been removed from the server.</p></div>';
                echo '<script>setTimeout(function(){ window.location.href="/"; }, 3000);</script>';
            } else {
                echo '<div class="step error"><h3>❌ Failed to Delete</h3><p>Please delete manually: <code>public/fix-session-error.php</code></p></div>';
            }
        }
        ?>
    </div>
</body>
</html>
