<?php
/**
 * Emergency DotEnv Class Fix
 *
 * Fixes InvalidArgumentException error when loading DotEnv
 * Access: http://ponto.supportsondagens.com.br/fix-dotenv-class.php
 *
 * IMPORTANT: DELETE THIS FILE AFTER USE!
 */

// Security check
$lockFile = __DIR__ . '/../writable/dotenv-fix.lock';
if (file_exists($lockFile)) {
    $lastRun = (int)file_get_contents($lockFile);
    if (time() - $lastRun < 1800) { // 30 minutes
        die('Script ran recently. Wait 30 minutes or delete writable/dotenv-fix.lock');
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix DotEnv InvalidArgumentException</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; line-height: 1.6; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; margin-top: 0; border-bottom: 3px solid #e74c3c; padding-bottom: 10px; }
        .step { background: #ecf0f1; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #3498db; }
        .ok { background: #d5f4e6; border-left-color: #27ae60; }
        .error { background: #fadbd8; border-left-color: #e74c3c; }
        .warning { background: #fef5e7; border-left-color: #f39c12; }
        code { background: #34495e; color: #ecf0f1; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        .btn { display: inline-block; padding: 12px 24px; background: #e74c3c; color: white; text-decoration: none; border-radius: 4px; margin-top: 10px; }
        .btn:hover { background: #c0392b; }
        .btn-green { background: #27ae60; }
        .btn-green:hover { background: #229954; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix: DotEnv InvalidArgumentException</h1>
        <p><strong>Error:</strong> <code>Class 'CodeIgniter\Exceptions\InvalidArgumentException' not found</code></p>

<?php

$rootPath = __DIR__ . '/..';
$fixes = [];
$errors = [];
$warnings = [];

// Step 1: Check vendor/autoload.php
echo '<div class="step">';
echo '<strong>Step 1: Verify Composer Autoload</strong><br>';

$autoloadFile = $rootPath . '/vendor/autoload.php';
if (file_exists($autoloadFile)) {
    echo '✅ vendor/autoload.php exists<br>';

    // Check if it's a recent file
    $fileAge = time() - filemtime($autoloadFile);
    if ($fileAge > 604800) { // 7 days
        echo '⚠️ Autoload file is ' . round($fileAge / 86400) . ' days old<br>';
        $warnings[] = 'Autoload may be outdated - run composer dump-autoload';
    } else {
        echo '✅ Autoload file is recent (' . round($fileAge / 3600) . ' hours old)<br>';
    }
} else {
    echo '❌ vendor/autoload.php NOT FOUND!<br>';
    echo 'You MUST run: <code>composer install</code> via SSH<br>';
    $errors[] = 'Composer autoload missing - manual fix required';
}

echo '</div>';

// Step 2: Check InvalidArgumentException class
echo '<div class="step">';
echo '<strong>Step 2: Verify InvalidArgumentException Class</strong><br>';

$exceptionFile = $rootPath . '/vendor/codeigniter4/framework/system/Exceptions/InvalidArgumentException.php';
if (file_exists($exceptionFile)) {
    echo '✅ InvalidArgumentException.php exists<br>';
    echo 'Location: <code>vendor/codeigniter4/framework/system/Exceptions/</code><br>';

    // Check if class can be loaded
    if (file_exists($autoloadFile)) {
        require_once $autoloadFile;

        if (class_exists('CodeIgniter\Exceptions\InvalidArgumentException')) {
            echo '✅ Class can be autoloaded successfully<br>';
        } else {
            echo '❌ Class EXISTS but CANNOT be autoloaded!<br>';
            echo 'This indicates a problem with Composer autoloader<br>';
            $errors[] = 'Class autoload broken - regenerate autoloader';
        }
    }
} else {
    echo '❌ InvalidArgumentException.php NOT FOUND!<br>';
    echo 'CodeIgniter 4 framework is not properly installed.<br>';
    $errors[] = 'CodeIgniter framework incomplete - run composer install';
}

echo '</div>';

// Step 3: Check .env file
echo '<div class="step">';
echo '<strong>Step 3: Verify .env Configuration</strong><br>';

$envFile = $rootPath . '/.env';
if (file_exists($envFile)) {
    echo '✅ .env file exists<br>';

    // Check for syntax errors
    $envContent = file_get_contents($envFile);

    // Check line endings
    if (strpos($envContent, "\r\n") !== false) {
        echo '⚠️ Windows line endings (CRLF) detected<br>';
        echo 'Converting to Unix (LF)...<br>';

        $envContent = str_replace("\r\n", "\n", $envContent);
        if (@file_put_contents($envFile, $envContent)) {
            echo '✅ Converted to LF line endings<br>';
            $fixes[] = 'Fixed .env line endings';
        } else {
            echo '❌ Could not convert line endings (permission denied)<br>';
            $errors[] = '.env not writable';
        }
    } else {
        echo '✅ Line endings are correct (LF)<br>';
    }

    // Check critical values
    if (preg_match('/CI_ENVIRONMENT\s*=\s*(\w+)/', $envContent, $matches)) {
        echo 'Environment: <code>' . htmlspecialchars($matches[1]) . '</code><br>';
    } else {
        echo '⚠️ CI_ENVIRONMENT not set!<br>';
        $warnings[] = 'CI_ENVIRONMENT missing from .env';
    }

} else {
    echo '❌ .env file NOT FOUND!<br>';

    // Try to create from env or .env.example
    if (file_exists($rootPath . '/env')) {
        if (@copy($rootPath . '/env', $envFile)) {
            echo '✅ Created .env from env template<br>';
            $fixes[] = 'Created .env file';
        } else {
            echo '❌ Could not create .env (permission denied)<br>';
            $errors[] = 'Cannot create .env - check permissions';
        }
    } else {
        echo '❌ No template found to create .env<br>';
        $errors[] = '.env missing and no template available';
    }
}

echo '</div>';

// Step 4: Test class loading with custom autoloader
echo '<div class="step">';
echo '<strong>Step 4: Test Manual Class Loading</strong><br>';

if (file_exists($exceptionFile) && file_exists($autoloadFile)) {
    echo 'Attempting to manually require the class...<br>';

    try {
        require_once $autoloadFile;

        if (!class_exists('CodeIgniter\Exceptions\InvalidArgumentException')) {
            // Manually load the class
            require_once $exceptionFile;
        }

        if (class_exists('CodeIgniter\Exceptions\InvalidArgumentException')) {
            echo '✅ Class loaded successfully!<br>';
        } else {
            echo '❌ Class still cannot be loaded<br>';
            $errors[] = 'Manual class loading failed';
        }
    } catch (Exception $e) {
        echo '❌ Error loading class: ' . htmlspecialchars($e->getMessage()) . '<br>';
        $errors[] = 'Exception during class loading';
    }
} else {
    echo '⚠️ Cannot test - missing required files<br>';
}

echo '</div>';

// Step 5: Check PHP version and extensions
echo '<div class="step">';
echo '<strong>Step 5: System Requirements</strong><br>';

echo 'PHP Version: <code>' . PHP_VERSION . '</code>';
if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
    echo ' ✅<br>';
} else {
    echo ' ❌ (Requires >= 8.1)<br>';
    $errors[] = 'PHP version too old';
}

$requiredExts = ['intl', 'mbstring', 'json'];
$missingExts = [];
foreach ($requiredExts as $ext) {
    if (extension_loaded($ext)) {
        echo '✅ Extension: ' . $ext . '<br>';
    } else {
        echo '❌ Extension MISSING: ' . $ext . '<br>';
        $missingExts[] = $ext;
    }
}

if (!empty($missingExts)) {
    $errors[] = 'Missing PHP extensions: ' . implode(', ', $missingExts);
}

echo '</div>';

// Summary
echo '<div class="step ' . (count($errors) === 0 ? 'ok' : 'error') . '">';
echo '<h2>📋 Summary</h2>';

if (count($fixes) > 0) {
    echo '<strong>Fixes Applied:</strong><ul>';
    foreach ($fixes as $fix) {
        echo '<li>✅ ' . htmlspecialchars($fix) . '</li>';
    }
    echo '</ul>';
}

if (count($warnings) > 0) {
    echo '<strong>Warnings:</strong><ul>';
    foreach ($warnings as $warning) {
        echo '<li>⚠️ ' . htmlspecialchars($warning) . '</li>';
    }
    echo '</ul>';
}

if (count($errors) > 0) {
    echo '<strong>Critical Errors:</strong><ul>';
    foreach ($errors as $error) {
        echo '<li>❌ ' . htmlspecialchars($error) . '</li>';
    }
    echo '</ul>';

    echo '<h3 style="color: #e74c3c;">🚨 MANUAL FIX REQUIRED</h3>';
    echo '<p>This error requires SSH access to fix:</p>';
    echo '<pre>cd /home/supportson/public_html/ponto
composer install --no-dev --optimize-autoloader
composer dump-autoload --optimize
chmod -R 775 writable/</pre>';

} else {
    echo '<h3 style="color: #27ae60;">✅ ALL CHECKS PASSED!</h3>';
    echo '<p>The InvalidArgumentException error should be resolved.</p>';
    echo '<a href="/" class="btn btn-green">Test Application →</a>';

    // Create lock file
    @file_put_contents($lockFile, time());
}

echo '</div>';

// Diagnostic info
echo '<div class="step">';
echo '<strong>Diagnostic Information:</strong><br>';
echo 'Script Path: <code>' . __DIR__ . '</code><br>';
echo 'Root Path: <code>' . $rootPath . '</code><br>';
echo 'Autoload: <code>' . ($autoloadFile . (file_exists($autoloadFile) ? ' (EXISTS)' : ' (MISSING)')) . '</code><br>';
echo 'Server Software: <code>' . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . '</code><br>';
echo '</div>';

?>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #ecf0f1;">
            <h3 style="color: #e74c3c;">⚠️ SECURITY WARNING</h3>
            <p><strong>DELETE THIS FILE AFTER USE!</strong></p>
            <a href="?delete=1" class="btn">🗑️ Delete This File Now</a>
        </div>

        <?php
        if (isset($_GET['delete'])) {
            if (@unlink(__FILE__)) {
                echo '<div class="step ok"><h3>✅ File Deleted</h3><p>Script removed successfully.</p></div>';
                echo '<script>setTimeout(function(){ window.location.href="/"; }, 2000);</script>';
            } else {
                echo '<div class="step error"><h3>❌ Delete Failed</h3><p>Remove manually: <code>public/fix-dotenv-class.php</code></p></div>';
            }
        }
        ?>
    </div>
</body>
</html>
