<?php

namespace Tests\Integration;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use App\Services\Security\RateLimitService;
use App\Filters\SecurityHeadersFilter;

/**
 * Security Integration Test
 *
 * Tests security features working together:
 * 1. Security headers on all responses
 * 2. Rate limiting enforcement
 * 3. HTTPS enforcement (HSTS)
 * 4. CSP policies
 * 5. XSS protection
 */
class SecurityIntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = false;
    protected $refresh = false;

    protected RateLimitService $rateLimitService;
    protected SecurityHeadersFilter $securityHeadersFilter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rateLimitService = new RateLimitService();
        $this->securityHeadersFilter = new SecurityHeadersFilter();
    }

    /**
     * Test security headers are present on all responses
     */
    public function testSecurityHeadersPresent()
    {
        $result = $this->get('/dashboard');

        // Check essential security headers
        $this->assertTrue($result->response()->hasHeader('Content-Security-Policy'));
        $this->assertTrue($result->response()->hasHeader('X-Frame-Options'));
        $this->assertTrue($result->response()->hasHeader('X-Content-Type-Options'));
        $this->assertTrue($result->response()->hasHeader('X-XSS-Protection'));
        $this->assertTrue($result->response()->hasHeader('Referrer-Policy'));
        $this->assertTrue($result->response()->hasHeader('Permissions-Policy'));
    }

    /**
     * Test CSP header prevents inline scripts (in principle)
     */
    public function testContentSecurityPolicyHeader()
    {
        $result = $this->get('/dashboard');

        $csp = $result->response()->getHeaderLine('Content-Security-Policy');

        // Should contain important directives
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
    }

    /**
     * Test X-Frame-Options prevents clickjacking
     */
    public function testXFrameOptionsHeader()
    {
        $result = $this->get('/dashboard');

        $xFrameOptions = $result->response()->getHeaderLine('X-Frame-Options');

        $this->assertEquals('DENY', $xFrameOptions);
    }

    /**
     * Test X-Content-Type-Options prevents MIME sniffing
     */
    public function testXContentTypeOptionsHeader()
    {
        $result = $this->get('/dashboard');

        $xContentType = $result->response()->getHeaderLine('X-Content-Type-Options');

        $this->assertEquals('nosniff', $xContentType);
    }

    /**
     * Test Referrer-Policy controls referrer information
     */
    public function testReferrerPolicyHeader()
    {
        $result = $this->get('/dashboard');

        $referrerPolicy = $result->response()->getHeaderLine('Referrer-Policy');

        $this->assertEquals('strict-origin-when-cross-origin', $referrerPolicy);
    }

    /**
     * Test Permissions-Policy restricts browser features
     */
    public function testPermissionsPolicyHeader()
    {
        $result = $this->get('/dashboard');

        $permissionsPolicy = $result->response()->getHeaderLine('Permissions-Policy');

        // Should restrict dangerous features
        $this->assertStringContainsString('camera=()', $permissionsPolicy);
        $this->assertStringContainsString('microphone=()', $permissionsPolicy);
        $this->assertStringContainsString('geolocation=()', $permissionsPolicy);
    }

    /**
     * Test rate limiting blocks excessive requests
     */
    public function testRateLimitingEnforcement()
    {
        // Make requests to trigger rate limit
        $ip = '192.168.1.100';
        $key = 'test_endpoint:' . $ip;

        // Make many requests
        for ($i = 0; $i < 101; $i++) {
            $result = $this->rateLimitService->attempt($key, 'general', $ip);

            if ($i < 100) {
                $this->assertTrue($result['allowed'], "Request {$i} should be allowed");
            } else {
                $this->assertFalse($result['allowed'], "Request {$i} should be blocked");
            }
        }
    }

    /**
     * Test rate limit headers are included
     */
    public function testRateLimitHeadersPresent()
    {
        // This would require the filter to be active
        // Testing the service directly
        $ip = '192.168.1.101';
        $result = $this->rateLimitService->attempt('test_headers:' . $ip, 'api', $ip);

        $headers = $this->rateLimitService->getHeaders($result);

        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
    }

    /**
     * Test rate limit whitelisting works
     */
    public function testRateLimitWhitelisting()
    {
        // Localhost should be whitelisted
        $this->assertTrue($this->rateLimitService->isWhitelisted('127.0.0.1'));
        $this->assertTrue($this->rateLimitService->isWhitelisted('::1'));

        // Random IP should not be whitelisted
        $this->assertFalse($this->rateLimitService->isWhitelisted('192.168.1.100'));
    }

    /**
     * Test security headers on API responses
     */
    public function testSecurityHeadersOnAPIResponses()
    {
        // Create test employee
        $employeeModel = new \App\Models\EmployeeModel();
        $employeeData = [
            'name' => 'API Security Test',
            'email' => 'apisectest@example.com',
            'password' => password_hash('password123', PASSWORD_ARGON2ID),
            'active' => true,
        ];

        $employeeId = $employeeModel->insert($employeeData);

        // Get OAuth token
        $tokenResult = $this->post('/api/oauth/token', [
            'grant_type' => 'password',
            'username' => 'apisectest@example.com',
            'password' => 'password123',
        ]);

        $tokens = json_decode($tokenResult->getJSON(), true);

        // Make API request
        $apiResult = $this->withHeaders([
            'Authorization' => 'Bearer ' . $tokens['access_token'],
        ])->get('/api/dashboard');

        // Check security headers are present on API responses too
        $this->assertTrue($apiResult->response()->hasHeader('Content-Security-Policy'));
        $this->assertTrue($apiResult->response()->hasHeader('X-Content-Type-Options'));

        // Clean up
        $employeeModel->delete($employeeId);
    }

    /**
     * Test different rate limits for different endpoint types
     */
    public function testDifferentRateLimitsForEndpoints()
    {
        $ip = '192.168.1.102';

        // Login endpoint: 5 attempts per 15 minutes
        $loginResult = $this->rateLimitService->attempt('login_test:' . $ip, 'login', $ip);
        $this->assertEquals(5, $loginResult['max_attempts']);

        // API endpoint: 60 attempts per minute
        $apiResult = $this->rateLimitService->attempt('api_test:' . $ip, 'api', $ip);
        $this->assertEquals(60, $apiResult['max_attempts']);

        // Password reset: 3 attempts per hour
        $resetResult = $this->rateLimitService->attempt('reset_test:' . $ip, 'password_reset', $ip);
        $this->assertEquals(3, $resetResult['max_attempts']);

        // 2FA verify: 5 attempts per 10 minutes
        $twoFAResult = $this->rateLimitService->attempt('2fa_test:' . $ip, '2fa_verify', $ip);
        $this->assertEquals(5, $twoFAResult['max_attempts']);
    }

    /**
     * Test CSP allows necessary resources
     */
    public function testCSPAllowsNecessaryResources()
    {
        $filter = $this->securityHeadersFilter;

        $csp = $filter->getCSP();

        // Should allow self
        $this->assertStringContainsString("default-src 'self'", $csp);

        // Should allow inline styles (needed for common frameworks)
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline'", $csp);

        // Should block objects
        $this->assertStringContainsString("object-src 'none'", $csp);
    }

    /**
     * Test security filter doesn't break file downloads
     */
    public function testSecurityHeadersOnFileDownloads()
    {
        // File downloads might need special handling
        // This is a placeholder for actual file download test
        $this->assertTrue(true);
    }

    /**
     * Test rate limit reset works
     */
    public function testRateLimitReset()
    {
        $ip = '192.168.1.103';
        $key = 'reset_test:' . $ip;

        // Exhaust rate limit
        for ($i = 0; $i < 5; $i++) {
            $this->rateLimitService->attempt($key, 'login', $ip);
        }

        // Should be blocked
        $result = $this->rateLimitService->attempt($key, 'login', $ip);
        $this->assertFalse($result['allowed']);

        // Reset rate limit
        $this->rateLimitService->reset($key, 'login');

        // Should be allowed again
        $result = $this->rateLimitService->attempt($key, 'login', $ip);
        $this->assertTrue($result['allowed']);
    }

    /**
     * Test custom rate limit configuration
     */
    public function testCustomRateLimitConfiguration()
    {
        // Set custom limit
        $this->rateLimitService->setLimit('custom_test', 10, 5);

        $limit = $this->rateLimitService->getLimit('custom_test');

        $this->assertEquals(10, $limit['max_attempts']);
        $this->assertEquals(5, $limit['decay_minutes']);

        // Test it works
        $ip = '192.168.1.104';
        $result = $this->rateLimitService->attempt('custom:' . $ip, 'custom_test', $ip);

        $this->assertEquals(10, $result['max_attempts']);
    }

    /**
     * Test security headers configuration flexibility
     */
    public function testSecurityHeadersCustomization()
    {
        $filter = new SecurityHeadersFilter();

        // Test custom CSP
        $filter->setCSP([
            'default-src' => ["'self'"],
            'script-src' => ["'self'", 'https://cdn.example.com'],
        ]);

        $csp = $filter->getCSP();

        $this->assertStringContainsString("script-src 'self' https://cdn.example.com", $csp);

        // Test frame options
        $filter->allowSameOriginFrames();
        $headers = $filter->getHeaders();

        $this->assertEquals('SAMEORIGIN', $headers['X-Frame-Options']);
    }

    /**
     * Test combined security: rate limit + headers
     */
    public function testCombinedSecurityFeatures()
    {
        // This test validates that both rate limiting and security headers
        // work together without conflicts

        $ip = '192.168.1.105';

        // Make request that should have both rate limiting and security headers
        for ($i = 0; $i < 3; $i++) {
            $result = $this->rateLimitService->attempt('combined:' . $ip, 'general', $ip);

            // Rate limiting should work
            $this->assertTrue($result['allowed']);

            // Headers should be available
            $headers = $this->rateLimitService->getHeaders($result);
            $this->assertIsArray($headers);
            $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        }

        // Security headers filter should work independently
        $filter = new SecurityHeadersFilter();
        $secHeaders = $filter->getHeaders();

        $this->assertArrayHasKey('Content-Security-Policy', $secHeaders);
        $this->assertArrayHasKey('X-Frame-Options', $secHeaders);
    }
}
