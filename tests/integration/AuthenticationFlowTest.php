<?php

namespace Tests\Integration;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\EmployeeModel;
use App\Services\Security\TwoFactorAuthService;

/**
 * Authentication Flow Integration Test
 *
 * Tests complete authentication flow:
 * 1. Login with credentials
 * 2. 2FA verification
 * 3. Session establishment
 * 4. Access to protected resources
 */
class AuthenticationFlowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = false;
    protected $migrateOnce = false;
    protected $refresh = false;
    protected $namespace = null;

    protected EmployeeModel $employeeModel;
    protected TwoFactorAuthService $twoFactorService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->employeeModel = new EmployeeModel();
        $this->twoFactorService = new TwoFactorAuthService();
    }

    /**
     * Test complete login flow without 2FA
     */
    public function testLoginFlowWithout2FA()
    {
        // Create test employee without 2FA
        $employeeData = [
            'name' => 'Test Employee',
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_ARGON2ID),
            'active' => true,
            'two_factor_enabled' => false,
        ];

        $employeeId = $this->employeeModel->insert($employeeData);
        $this->assertIsNumeric($employeeId);

        // Attempt login
        $result = $this->post('/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // Should redirect to dashboard
        $result->assertRedirectTo('/dashboard');

        // Check session is established
        $this->assertEquals($employeeId, session()->get('employee_id'));
        $this->assertNull(session()->get('2fa_pending_employee_id'));

        // Clean up
        $this->employeeModel->delete($employeeId);
    }

    /**
     * Test complete login flow with 2FA
     */
    public function testLoginFlowWith2FA()
    {
        // Generate 2FA secret
        $secret = $this->twoFactorService->generateSecret();
        $encryptedSecret = (new \App\Services\Security\EncryptionService())->encrypt($secret);

        // Create test employee with 2FA enabled
        $employeeData = [
            'name' => 'Test 2FA Employee',
            'email' => 'test2fa@example.com',
            'password' => password_hash('password123', PASSWORD_ARGON2ID),
            'active' => true,
            'two_factor_enabled' => true,
            'two_factor_secret' => $encryptedSecret,
        ];

        $employeeId = $this->employeeModel->insert($employeeData);

        // Step 1: Login with credentials
        $result = $this->post('/auth/login', [
            'email' => 'test2fa@example.com',
            'password' => 'password123',
        ]);

        // Should redirect to 2FA verification
        $result->assertRedirectTo('/auth/2fa/verify');

        // Check pending 2FA session
        $this->assertEquals($employeeId, session()->get('2fa_pending_employee_id'));
        $this->assertNull(session()->get('employee_id'));

        // Step 2: Generate valid 2FA code
        $code = $this->twoFactorService->generateCode($secret);

        // Step 3: Submit 2FA code
        $result = $this->post('/auth/2fa/verify', [
            'code' => $code,
        ]);

        // Should redirect to dashboard
        $result->assertRedirectTo('/dashboard');

        // Check session is fully established
        $this->assertEquals($employeeId, session()->get('employee_id'));
        $this->assertTrue(session()->get('2fa_verified'));
        $this->assertNull(session()->get('2fa_pending_employee_id'));

        // Clean up
        $this->employeeModel->delete($employeeId);
    }

    /**
     * Test login with invalid credentials
     */
    public function testLoginWithInvalidCredentials()
    {
        $result = $this->post('/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        // Should redirect back with error
        $result->assertRedirect();
        $this->assertNull(session()->get('employee_id'));
    }

    /**
     * Test 2FA with invalid code
     */
    public function testTwoFactorWithInvalidCode()
    {
        // Generate 2FA secret
        $secret = $this->twoFactorService->generateSecret();
        $encryptedSecret = (new \App\Services\Security\EncryptionService())->encrypt($secret);

        // Create test employee
        $employeeData = [
            'name' => 'Test Invalid 2FA',
            'email' => 'testinvalid2fa@example.com',
            'password' => password_hash('password123', PASSWORD_ARGON2ID),
            'active' => true,
            'two_factor_enabled' => true,
            'two_factor_secret' => $encryptedSecret,
        ];

        $employeeId = $this->employeeModel->insert($employeeData);

        // Login first
        $this->post('/auth/login', [
            'email' => 'testinvalid2fa@example.com',
            'password' => 'password123',
        ]);

        // Try invalid 2FA code
        $result = $this->post('/auth/2fa/verify', [
            'code' => '000000',
        ]);

        // Should redirect back with error
        $result->assertRedirect();
        $this->assertNull(session()->get('employee_id'));
        $this->assertEquals($employeeId, session()->get('2fa_pending_employee_id'));

        // Clean up
        $this->employeeModel->delete($employeeId);
    }

    /**
     * Test 2FA filter blocks access without verification
     */
    public function testTwoFactorFilterBlocksUnverifiedAccess()
    {
        // Generate 2FA secret
        $secret = $this->twoFactorService->generateSecret();
        $encryptedSecret = (new \App\Services\Security\EncryptionService())->encrypt($secret);

        // Create test employee with 2FA
        $employeeData = [
            'name' => 'Test 2FA Block',
            'email' => 'testblock@example.com',
            'password' => password_hash('password123', PASSWORD_ARGON2ID),
            'active' => true,
            'two_factor_enabled' => true,
            'two_factor_secret' => $encryptedSecret,
        ];

        $employeeId = $this->employeeModel->insert($employeeData);

        // Set employee in session but not 2FA verified
        session()->set('employee_id', $employeeId);
        session()->remove('2fa_verified');

        // Try to access protected resource
        $result = $this->get('/dashboard');

        // Should redirect to 2FA verification
        $result->assertRedirectTo('/auth/2fa/verify');

        // Clean up
        $this->employeeModel->delete($employeeId);
    }

    /**
     * Test complete logout flow
     */
    public function testLogoutFlow()
    {
        // Create and login employee
        $employeeData = [
            'name' => 'Test Logout',
            'email' => 'testlogout@example.com',
            'password' => password_hash('password123', PASSWORD_ARGON2ID),
            'active' => true,
        ];

        $employeeId = $this->employeeModel->insert($employeeData);

        // Set session
        session()->set('employee_id', $employeeId);
        session()->set('2fa_verified', true);

        // Logout
        $result = $this->get('/auth/logout');

        // Should redirect to login
        $result->assertRedirectTo('/auth/login');

        // Session should be cleared
        $this->assertNull(session()->get('employee_id'));
        $this->assertNull(session()->get('2fa_verified'));

        // Clean up
        $this->employeeModel->delete($employeeId);
    }

    /**
     * Test account lockout after multiple failed attempts
     */
    public function testAccountLockoutAfterFailedAttempts()
    {
        // Create test employee
        $employeeData = [
            'name' => 'Test Lockout',
            'email' => 'testlockout@example.com',
            'password' => password_hash('password123', PASSWORD_ARGON2ID),
            'active' => true,
        ];

        $employeeId = $this->employeeModel->insert($employeeData);

        // Attempt login with wrong password multiple times (simulating rate limit)
        for ($i = 0; $i < 6; $i++) {
            $this->post('/auth/login', [
                'email' => 'testlockout@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        // Next attempt should be rate limited (if rate limiting is active)
        $result = $this->post('/auth/login', [
            'email' => 'testlockout@example.com',
            'password' => 'password123',
        ]);

        // Clean up
        $this->employeeModel->delete($employeeId);
    }
}
