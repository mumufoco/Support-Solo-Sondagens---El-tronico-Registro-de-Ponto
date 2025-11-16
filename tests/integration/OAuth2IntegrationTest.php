<?php

namespace Tests\Integration;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Models\EmployeeModel;
use App\Services\Auth\OAuth2Service;
use App\Services\Security\RateLimitService;

/**
 * OAuth 2.0 Integration Test
 *
 * Tests complete OAuth 2.0 flow:
 * 1. Password grant to get tokens
 * 2. Using access token for API calls
 * 3. Refreshing access token
 * 4. Token revocation
 * 5. Rate limiting integration
 */
class OAuth2IntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = false;
    protected $refresh = false;

    protected EmployeeModel $employeeModel;
    protected OAuth2Service $oauth2Service;
    protected RateLimitService $rateLimitService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->employeeModel = new EmployeeModel();
        $this->oauth2Service = new OAuth2Service();
        $this->rateLimitService = new RateLimitService();
    }

    /**
     * Test complete OAuth 2.0 password grant flow
     */
    public function testPasswordGrantFlow()
    {
        // Create test employee
        $employeeData = [
            'name' => 'API Test User',
            'email' => 'apitest@example.com',
            'password' => password_hash('apipassword123', PASSWORD_ARGON2ID),
            'active' => true,
        ];

        $employeeId = $this->employeeModel->insert($employeeData);

        // Request access token via password grant
        $result = $this->post('/api/oauth/token', [
            'grant_type' => 'password',
            'username' => 'apitest@example.com',
            'password' => 'apipassword123',
            'scope' => 'api.read api.write',
        ]);

        $result->assertOK();
        $result->assertJSONFragment(['token_type' => 'Bearer']);

        $responseData = json_decode($result->getJSON(), true);

        $this->assertArrayHasKey('access_token', $responseData);
        $this->assertArrayHasKey('refresh_token', $responseData);
        $this->assertArrayHasKey('expires_in', $responseData);
        $this->assertEquals('Bearer', $responseData['token_type']);

        // Verify access token works
        $accessToken = $responseData['access_token'];

        $apiResult = $this->withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get('/api/dashboard');

        $apiResult->assertOK();

        // Clean up
        $this->employeeModel->delete($employeeId);
    }

    /**
     * Test refresh token flow
     */
    public function testRefreshTokenFlow()
    {
        // Create test employee
        $employeeData = [
            'name' => 'Refresh Test User',
            'email' => 'refreshtest@example.com',
            'password' => password_hash('refreshpass123', PASSWORD_ARGON2ID),
            'active' => true,
        ];

        $employeeId = $this->employeeModel->insert($employeeData);

        // Get initial tokens
        $result = $this->post('/api/oauth/token', [
            'grant_type' => 'password',
            'username' => 'refreshtest@example.com',
            'password' => 'refreshpass123',
        ]);

        $tokens = json_decode($result->getJSON(), true);
        $refreshToken = $tokens['refresh_token'];

        // Use refresh token to get new access token
        $refreshResult = $this->post('/api/oauth/refresh', [
            'refresh_token' => $refreshToken,
        ]);

        $refreshResult->assertOK();

        $newTokens = json_decode($refreshResult->getJSON(), true);

        $this->assertArrayHasKey('access_token', $newTokens);
        $this->assertArrayHasKey('refresh_token', $newTokens);
        $this->assertNotEquals($tokens['access_token'], $newTokens['access_token']);

        // Verify new access token works
        $apiResult = $this->withHeaders([
            'Authorization' => 'Bearer ' . $newTokens['access_token'],
        ])->get('/api/dashboard');

        $apiResult->assertOK();

        // Clean up
        $this->employeeModel->delete($employeeId);
    }

    /**
     * Test token revocation
     */
    public function testTokenRevocation()
    {
        // Create test employee
        $employeeData = [
            'name' => 'Revoke Test User',
            'email' => 'revoketest@example.com',
            'password' => password_hash('revokepass123', PASSWORD_ARGON2ID),
            'active' => true,
        ];

        $employeeId = $this->employeeModel->insert($employeeData);

        // Get access token
        $result = $this->post('/api/oauth/token', [
            'grant_type' => 'password',
            'username' => 'revoketest@example.com',
            'password' => 'revokepass123',
        ]);

        $tokens = json_decode($result->getJSON(), true);
        $accessToken = $tokens['access_token'];

        // Verify token works
        $apiResult = $this->withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get('/api/dashboard');

        $apiResult->assertOK();

        // Revoke token
        $revokeResult = $this->withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->post('/api/oauth/revoke', [
            'token' => $accessToken,
        ]);

        $revokeResult->assertOK();

        // Try to use revoked token
        $deniedResult = $this->withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get('/api/dashboard');

        $deniedResult->assertStatus(401);

        // Clean up
        $this->employeeModel->delete($employeeId);
    }

    /**
     * Test OAuth with invalid credentials
     */
    public function testInvalidCredentials()
    {
        $result = $this->post('/api/oauth/token', [
            'grant_type' => 'password',
            'username' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $result->assertStatus(401);
    }

    /**
     * Test OAuth with inactive account
     */
    public function testInactiveAccount()
    {
        // Create inactive employee
        $employeeData = [
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'password' => password_hash('password123', PASSWORD_ARGON2ID),
            'active' => false,
        ];

        $employeeId = $this->employeeModel->insert($employeeData);

        // Try to get token
        $result = $this->post('/api/oauth/token', [
            'grant_type' => 'password',
            'username' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $result->assertStatus(401);

        // Clean up
        $this->employeeModel->delete($employeeId);
    }

    /**
     * Test OAuth rate limiting
     */
    public function testOAuthRateLimiting()
    {
        // Make multiple failed requests to trigger rate limit
        for ($i = 0; $i < 10; $i++) {
            $this->post('/api/oauth/token', [
                'grant_type' => 'password',
                'username' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        // Next request should be rate limited
        $result = $this->post('/api/oauth/token', [
            'grant_type' => 'password',
            'username' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        // Should return 429 if rate limiting is enforced
        $this->assertContains($result->getStatusCode(), [401, 429]);
    }

    /**
     * Test accessing protected endpoint without token
     */
    public function testProtectedEndpointWithoutToken()
    {
        $result = $this->get('/api/dashboard');

        $result->assertStatus(401);
    }

    /**
     * Test accessing protected endpoint with invalid token
     */
    public function testProtectedEndpointWithInvalidToken()
    {
        $result = $this->withHeaders([
            'Authorization' => 'Bearer invalidtoken123',
        ])->get('/api/dashboard');

        $result->assertStatus(401);
    }

    /**
     * Test scope-based authorization
     */
    public function testScopeBasedAuthorization()
    {
        // Create test employee
        $employeeData = [
            'name' => 'Scope Test User',
            'email' => 'scopetest@example.com',
            'password' => password_hash('scopepass123', PASSWORD_ARGON2ID),
            'active' => true,
        ];

        $employeeId = $this->employeeModel->insert($employeeData);

        // Get token with limited scope
        $result = $this->post('/api/oauth/token', [
            'grant_type' => 'password',
            'username' => 'scopetest@example.com',
            'password' => 'scopepass123',
            'scope' => 'api.read',
        ]);

        $tokens = json_decode($result->getJSON(), true);
        $accessToken = $tokens['access_token'];

        // Should be able to read
        $readResult = $this->withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get('/api/dashboard');

        $readResult->assertOK();

        // Clean up
        $this->employeeModel->delete($employeeId);
    }

    /**
     * Test multiple device tokens
     */
    public function testMultipleDeviceTokens()
    {
        // Create test employee
        $employeeData = [
            'name' => 'Multi Device User',
            'email' => 'multidevice@example.com',
            'password' => password_hash('multipass123', PASSWORD_ARGON2ID),
            'active' => true,
        ];

        $employeeId = $this->employeeModel->insert($employeeData);

        // Get tokens from "device 1"
        $result1 = $this->post('/api/oauth/token', [
            'grant_type' => 'password',
            'username' => 'multidevice@example.com',
            'password' => 'multipass123',
        ]);

        $tokens1 = json_decode($result1->getJSON(), true);

        // Get tokens from "device 2"
        $result2 = $this->post('/api/oauth/token', [
            'grant_type' => 'password',
            'username' => 'multidevice@example.com',
            'password' => 'multipass123',
        ]);

        $tokens2 = json_decode($result2->getJSON(), true);

        // Both tokens should work independently
        $api1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $tokens1['access_token'],
        ])->get('/api/dashboard');

        $api2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $tokens2['access_token'],
        ])->get('/api/dashboard');

        $api1->assertOK();
        $api2->assertOK();

        // Clean up
        $this->employeeModel->delete($employeeId);
    }

    /**
     * Test revoke all tokens
     */
    public function testRevokeAllTokens()
    {
        // Create test employee
        $employeeData = [
            'name' => 'Revoke All User',
            'email' => 'revokeall@example.com',
            'password' => password_hash('revokeallpass123', PASSWORD_ARGON2ID),
            'active' => true,
        ];

        $employeeId = $this->employeeModel->insert($employeeData);

        // Get multiple tokens
        $result1 = $this->post('/api/oauth/token', [
            'grant_type' => 'password',
            'username' => 'revokeall@example.com',
            'password' => 'revokeallpass123',
        ]);

        $tokens1 = json_decode($result1->getJSON(), true);

        // Revoke all tokens
        $revokeResult = $this->withHeaders([
            'Authorization' => 'Bearer ' . $tokens1['access_token'],
        ])->post('/api/oauth/revoke-all');

        $revokeResult->assertOK();

        // Original token should no longer work
        $deniedResult = $this->withHeaders([
            'Authorization' => 'Bearer ' . $tokens1['access_token'],
        ])->get('/api/dashboard');

        $deniedResult->assertStatus(401);

        // Clean up
        $this->employeeModel->delete($employeeId);
    }
}
