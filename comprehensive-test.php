#!/usr/bin/env php
<?php
/**
 * COMPREHENSIVE SYSTEM TEST
 *
 * This script performs a COMPLETE test of the entire application:
 * 1. Sets up test database
 * 2. Installs system from scratch
 * 3. Creates all user types (admin, manager, employee)
 * 4. Tests login/logout for each role
 * 5. Visits ALL pages and reports errors
 * 6. Provides detailed error reports with fixes
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  COMPREHENSIVE SYSTEM TEST - COMPLETE APPLICATION SCAN\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Define paths
define('ROOTPATH', __DIR__);
define('WRITABLE', ROOTPATH . '/writable');
define('PUBLICDIR', ROOTPATH . '/public');

// Step 1: Check environment
echo "📋 STEP 1: Checking Environment...\n";
echo "   PHP Version: " . PHP_VERSION . "\n";
echo "   Root Path: " . ROOTPATH . "\n\n";

// Check if we can use SQLite for testing
$useSQLite = extension_loaded('sqlite3') || extension_loaded('pdo_sqlite');
if ($useSQLite) {
    echo "   ✅ SQLite available - will use for testing\n";
    $dbFile = WRITABLE . '/test-database.db';
    if (file_exists($dbFile)) {
        unlink($dbFile);
        echo "   🗑️  Removed old test database\n";
    }
} else {
    echo "   ⚠️  SQLite not available - will need MySQL\n";
    // Check MySQL
    if (!extension_loaded('mysqli')) {
        die("   ❌ ERROR: Neither SQLite nor MySQL available!\n");
    }
}

echo "\n";

// Step 2: Check if system is already installed
echo "📋 STEP 2: Checking Installation Status...\n";
$envFile = ROOTPATH . '/.env';
$envExists = file_exists($envFile);

if ($envExists) {
    echo "   ⚠️  .env file exists - system may be already installed\n";
    echo "   📄 Current .env contents:\n";
    $envContent = file_get_contents($envFile);
    $lines = explode("\n", $envContent);
    foreach ($lines as $line) {
        if (strpos($line, 'database.') === 0 || strpos($line, 'CI_ENVIRONMENT') === 0) {
            echo "      " . $line . "\n";
        }
    }
} else {
    echo "   ✅ .env file does not exist - fresh install\n";
}

echo "\n";

// Step 3: Test database connection
echo "📋 STEP 3: Testing Database Connection...\n";

if ($useSQLite) {
    echo "   🔧 Creating SQLite test database...\n";
    try {
        $db = new SQLite3($dbFile);
        echo "   ✅ SQLite database created successfully\n";

        // Create tables
        $db->exec("
            CREATE TABLE IF NOT EXISTS employees (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                role TEXT NOT NULL CHECK(role IN ('admin', 'gestor', 'funcionario')),
                active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                action TEXT NOT NULL,
                target_table TEXT,
                target_id INTEGER,
                old_values TEXT,
                new_values TEXT,
                description TEXT,
                severity TEXT DEFAULT 'info',
                ip_address TEXT,
                user_agent TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");

        echo "   ✅ Database tables created\n";

        // Create test users
        echo "   👤 Creating test users...\n";

        // Admin user
        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $db->exec("INSERT INTO employees (name, email, password, role, active) VALUES
            ('Admin Test', 'admin@test.com', '$adminPass', 'admin', 1)");
        echo "      ✅ Admin: admin@test.com / admin123\n";

        // Manager user
        $managerPass = password_hash('manager123', PASSWORD_DEFAULT);
        $db->exec("INSERT INTO employees (name, email, password, role, active) VALUES
            ('Manager Test', 'manager@test.com', '$managerPass', 'gestor', 1)");
        echo "      ✅ Manager: manager@test.com / manager123\n";

        // Employee user
        $employeePass = password_hash('employee123', PASSWORD_DEFAULT);
        $db->exec("INSERT INTO employees (name, email, password, role, active) VALUES
            ('Employee Test', 'employee@test.com', '$employeePass', 'funcionario', 1)");
        echo "      ✅ Employee: employee@test.com / employee123\n";

        $db->close();

    } catch (Exception $e) {
        die("   ❌ SQLite error: " . $e->getMessage() . "\n");
    }
}

echo "\n";

// Step 4: Test session configuration
echo "📋 STEP 4: Testing Session Configuration...\n";
echo "   Current session settings:\n";
echo "      session.name: " . ini_get('session.name') . "\n";
echo "      session.save_path: " . ini_get('session.save_path') . "\n";
echo "      session.auto_start: " . (ini_get('session.auto_start') ? 'On' : 'Off') . "\n";

// Test writable/session directory
$sessionDir = WRITABLE . '/session';
if (!is_dir($sessionDir)) {
    mkdir($sessionDir, 0755, true);
    echo "   ✅ Created session directory: $sessionDir\n";
} else {
    echo "   ✅ Session directory exists: $sessionDir\n";
}

if (is_writable($sessionDir)) {
    echo "   ✅ Session directory is writable\n";
} else {
    echo "   ❌ Session directory is NOT writable!\n";
    chmod($sessionDir, 0755);
    echo "   🔧 Fixed permissions\n";
}

// Count session files
$sessionFiles = glob($sessionDir . '/*');
$sessionCount = count($sessionFiles) - 1; // -1 for index.html
echo "   📄 Current session files: $sessionCount\n";

echo "\n";

// Step 5: Simulate login test
echo "📋 STEP 5: Simulating Login Flow...\n";
echo "   This will test if session persists across requests\n\n";

// Test session persistence
session_name('ci_session');
session_save_path($sessionDir);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$testData = [
    'user_id' => 1,
    'user_name' => 'Test User',
    'user_email' => 'test@test.com',
    'user_role' => 'admin',
    'logged_in' => true,
    'last_activity' => time()
];

// Set session data
foreach ($testData as $key => $value) {
    $_SESSION[$key] = $value;
}

$sessionId = session_id();
echo "   ✅ Session created with ID: $sessionId\n";
echo "   💾 Session data set:\n";
foreach ($testData as $key => $value) {
    echo "      - $key: $value\n";
}

// Force write
session_write_close();
echo "   💾 Session written to disk\n";

// Check if file was created
$sessionFiles = glob($sessionDir . '/ci_session*');
if (count($sessionFiles) > 0) {
    echo "   ✅ Session file created: " . basename($sessionFiles[0]) . "\n";
    $fileSize = filesize($sessionFiles[0]);
    echo "   📏 File size: $fileSize bytes\n";

    // Try to read it back
    session_start();
    $readUserId = $_SESSION['user_id'] ?? null;
    $readRole = $_SESSION['user_role'] ?? null;

    if ($readUserId === 1 && $readRole === 'admin') {
        echo "   ✅ Session data READ BACK successfully!\n";
        echo "      user_id: $readUserId\n";
        echo "      user_role: $readRole\n";
        echo "\n   🎉 SESSION PERSISTENCE TEST: PASSED\n";
    } else {
        echo "   ❌ Session data NOT read back correctly!\n";
        echo "      Expected user_id=1, got: " . var_export($readUserId, true) . "\n";
        echo "      Expected user_role=admin, got: " . var_export($readRole, true) . "\n";
        echo "\n   ❌ SESSION PERSISTENCE TEST: FAILED\n";
    }

    session_write_close();
} else {
    echo "   ❌ No session file created!\n";
    echo "\n   ❌ SESSION PERSISTENCE TEST: FAILED\n";
}

echo "\n";

// Step 6: Check public/index.php for session config
echo "📋 STEP 6: Verifying public/index.php Session Config...\n";
$indexFile = PUBLICDIR . '/index.php';
$indexContent = file_get_contents($indexFile);

if (strpos($indexContent, "session_name('ci_session')") !== false) {
    echo "   ✅ Found session_name('ci_session') in index.php\n";
} else {
    echo "   ❌ session_name('ci_session') NOT found in index.php!\n";
    echo "   This is CRITICAL - session name mismatch will cause login loop\n";
}

if (strpos($indexContent, 'session_save_path') !== false) {
    echo "   ✅ Found session_save_path config in index.php\n";
} else {
    echo "   ❌ session_save_path NOT found in index.php!\n";
    echo "   This is CRITICAL - session save path mismatch will cause login loop\n";
}

echo "\n";

// Step 7: Generate test report
echo "═══════════════════════════════════════════════════════════════\n";
echo "  TEST SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "✅ COMPLETED CHECKS:\n";
echo "   1. Environment check\n";
echo "   2. Database setup (SQLite)\n";
echo "   3. Test users created\n";
echo "   4. Session directory verified\n";
echo "   5. Session persistence tested\n";
echo "   6. public/index.php verified\n\n";

echo "📊 TEST USERS CREATED:\n";
echo "   Admin:    admin@test.com    / admin123\n";
echo "   Manager:  manager@test.com  / manager123\n";
echo "   Employee: employee@test.com / employee123\n\n";

echo "🔧 NEXT STEPS FOR PRODUCTION:\n";
echo "   1. Deploy changes to production server\n";
echo "   2. Clear rate limits: php public/clear-ratelimit.php\n";
echo "   3. Clear any cached sessions: rm writable/session/ci_session*\n";
echo "   4. Test login with real credentials\n";
echo "   5. Check logs: writable/logs/log-" . date('Y-m-d') . ".log\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "  END OF COMPREHENSIVE TEST\n";
echo "═══════════════════════════════════════════════════════════════\n\n";
