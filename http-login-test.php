#!/usr/bin/env php
<?php
/**
 * HTTP LOGIN TEST - Simulates real browser requests
 *
 * This script uses CodeIgniter's framework to simulate REAL HTTP requests
 * and test if login actually works.
 */

// Define FCPATH first
define('FCPATH', __DIR__ . '/public/');

// Bootstrap CodeIgniter
$_SERVER['CI_ENVIRONMENT'] = 'development';
define('ENVIRONMENT', 'development');

// Load paths
require __DIR__ . '/app/Config/Paths.php';
$paths = new Config\Paths();

// Load autoloader
require __DIR__ . '/vendor/autoload.php';

// Load Boot
require $paths->systemDirectory . '/Boot.php';

// Create app using Boot
use CodeIgniter\Boot;
$app = Boot::bootWeb($paths);

echo "\n";
echo "══════════════════════════════════════════════════════════════\n";
echo "  HTTP LOGIN TEST - REAL REQUEST SIMULATION\n";
echo "══════════════════════════════════════════════════════════════\n\n";

// Test 1: Get login page
echo "TEST 1: GET /auth/login\n";
echo "─────────────────────────────────────────\n";

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/auth/login';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

try {
    $request = \Config\Services::request();
    echo "✅ Request service created\n";
    echo "   URI: " . $request->getUri() . "\n";
    echo "   Method: " . $request->getMethod() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Check session service
echo "TEST 2: Session Service\n";
echo "─────────────────────────────────────────\n";

try {
    $session = \Config\Services::session();
    echo "✅ Session service created\n";
    echo "   Driver: " . get_class($session) . "\n";
    echo "   Session ID: " . session_id() . "\n";
    echo "   Session name: " . session_name() . "\n";
    echo "   Session save path: " . session_save_path() . "\n";

    // Test session operations
    $session->set('test_key', 'test_value');
    $retrieved = $session->get('test_key');

    if ($retrieved === 'test_value') {
        echo "✅ Session read/write works!\n";
    } else {
        echo "❌ Session read/write FAILED\n";
        echo "   Set: test_value\n";
        echo "   Got: " . var_export($retrieved, true) . "\n";
    }

    // Check session file
    $sessionDir = dirname(__DIR__) . '/writable/session';
    $files = glob($sessionDir . '/*session*');
    if (count($files) > 0) {
        echo "✅ Session file created: " . basename($files[0]) . "\n";
        echo "   Size: " . filesize($files[0]) . " bytes\n";
    } else {
        echo "❌ No session file created\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n";

// Test 3: Simulate POST login
echo "TEST 3: POST /auth/login (Simulate Login)\n";
echo "─────────────────────────────────────────\n";

try {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/auth/login';
    $_POST['email'] = 'admin@test.com';
    $_POST['password'] = 'admin123';

    // Load models
    $employeeModel = new \App\Models\EmployeeModel();

    // Check if test user exists
    $user = $employeeModel->where('email', 'admin@test.com')->first();

    if ($user) {
        echo "✅ Test user exists in database\n";
        echo "   ID: " . $user->id . "\n";
        echo "   Name: " . $user->name . "\n";
        echo "   Role: " . $user->role . "\n";

        // Test password
        if (password_verify('admin123', $user->password)) {
            echo "✅ Password verification works\n";

            // Simulate session creation (like LoginController does)
            $sessionData = [
                'user_id'       => $user->id,
                'user_name'     => $user->name,
                'user_email'    => $user->email,
                'user_role'     => $user->role,
                'user_active'   => (bool) $user->active,
                'last_activity' => time(),
                'logged_in'     => true,
            ];

            // Regenerate and set
            $session->regenerate();
            $session->set($sessionData);

            $newSessionId = session_id();
            echo "✅ Session created with ID: $newSessionId\n";

            // Verify data persists
            $verifyUserId = $session->get('user_id');
            $verifyRole = $session->get('user_role');

            if ($verifyUserId === $user->id && $verifyRole === $user->role) {
                echo "✅ Session data verified:\n";
                echo "   user_id: $verifyUserId\n";
                echo "   user_role: $verifyRole\n";
                echo "\n🎉 LOGIN SIMULATION: SUCCESS\n";
            } else {
                echo "❌ Session data verification FAILED:\n";
                echo "   Expected user_id={$user->id}, got: " . var_export($verifyUserId, true) . "\n";
                echo "   Expected user_role={$user->role}, got: " . var_export($verifyRole, true) . "\n";
                echo "\n❌ LOGIN SIMULATION: FAILED\n";
            }
        } else {
            echo "❌ Password verification failed\n";
        }
    } else {
        echo "⚠️  Test user does not exist in database\n";
        echo "   You need to create it first via installer or SQL\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n";

// Test 4: Simulate redirect (new request with session)
echo "TEST 4: Simulate Redirect (New Request)\n";
echo "─────────────────────────────────────────\n";

try {
    // Create NEW session instance (simulates new HTTP request)
    $session2 = \Config\Services::session(null, true); // Force new instance

    echo "New session ID: " . session_id() . "\n";

    $userId = $session2->get('user_id');
    $userRole = $session2->get('user_role');

    if ($userId && $userRole) {
        echo "✅ Session data persisted across \"requests\":\n";
        echo "   user_id: $userId\n";
        echo "   user_role: $userRole\n";
        echo "\n🎉 SESSION PERSISTENCE: SUCCESS\n";
    } else {
        echo "❌ Session data NOT persisted:\n";
        echo "   user_id: " . var_export($userId, true) . "\n";
        echo "   user_role: " . var_export($userRole, true) . "\n";
        echo "\n❌ SESSION PERSISTENCE: FAILED\n";
        echo "\nThis is the ROOT CAUSE of the login loop!\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";
echo "══════════════════════════════════════════════════════════════\n";
echo "  TEST COMPLETE\n";
echo "══════════════════════════════════════════════════════════════\n\n";
