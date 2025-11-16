<?php

namespace Tests\Unit\Services\Security;

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\Security\RateLimitService;
use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Config\Services;

/**
 * Rate Limit Service Test
 *
 * Tests for rate limiting functionality
 */
class RateLimitServiceTest extends CIUnitTestCase
{
    protected RateLimitService $service;
    protected CacheInterface $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RateLimitService();
        $this->cache = Services::cache();

        // Clean cache before each test
        $this->cache->clean();
    }

    protected function tearDown(): void
    {
        // Clean cache after each test
        $this->cache->clean();
        parent::tearDown();
    }

    public function testAttemptFirstRequest()
    {
        $result = $this->service->attempt('test_key', 'general', '192.168.1.100');

        // First attempt should be allowed
        $this->assertTrue($result['allowed']);
        $this->assertEquals(99, $result['remaining']); // 100 max - 1 attempt
        $this->assertEquals(1, $result['attempts']);
        $this->assertEquals(100, $result['max_attempts']);
        $this->assertArrayHasKey('reset_at', $result);
    }

    public function testAttemptMultipleRequests()
    {
        $key = 'test_multi';
        $ip = '192.168.1.101';

        // Make 3 attempts
        $result1 = $this->service->attempt($key, 'general', $ip);
        $result2 = $this->service->attempt($key, 'general', $ip);
        $result3 = $this->service->attempt($key, 'general', $ip);

        $this->assertTrue($result1['allowed']);
        $this->assertEquals(99, $result1['remaining']);

        $this->assertTrue($result2['allowed']);
        $this->assertEquals(98, $result2['remaining']);

        $this->assertTrue($result3['allowed']);
        $this->assertEquals(97, $result3['remaining']);
    }

    public function testAttemptExceedsLimit()
    {
        $key = 'test_exceed';
        $ip = '192.168.1.102';

        // Login limit is 5 attempts
        // Make 6 attempts
        for ($i = 1; $i <= 6; $i++) {
            $result = $this->service->attempt($key, 'login', $ip);

            if ($i <= 5) {
                $this->assertTrue($result['allowed'], "Attempt {$i} should be allowed");
            } else {
                $this->assertFalse($result['allowed'], "Attempt {$i} should be blocked");
                $this->assertEquals(0, $result['remaining']);
            }
        }
    }

    public function testWhitelistedIpAlwaysAllowed()
    {
        // Localhost is always whitelisted
        $result = $this->service->attempt('test_whitelist', 'login', '127.0.0.1');

        $this->assertTrue($result['allowed']);
        $this->assertEquals(999999, $result['remaining']);
        $this->assertTrue($result['whitelisted']);

        // Make 100 attempts - should still be allowed
        for ($i = 0; $i < 100; $i++) {
            $result = $this->service->attempt('test_whitelist', 'login', '127.0.0.1');
            $this->assertTrue($result['allowed']);
        }
    }

    public function testIsWhitelisted()
    {
        // Localhost should be whitelisted
        $this->assertTrue($this->service->isWhitelisted('127.0.0.1'));
        $this->assertTrue($this->service->isWhitelisted('::1'));

        // Random IP should not be whitelisted
        $this->assertFalse($this->service->isWhitelisted('192.168.1.100'));
    }

    public function testAddToWhitelist()
    {
        $ip = '10.0.0.50';

        $this->assertFalse($this->service->isWhitelisted($ip));

        $this->service->addToWhitelist($ip);

        $this->assertTrue($this->service->isWhitelisted($ip));
    }

    public function testRemoveFromWhitelist()
    {
        $ip = '10.0.0.51';

        $this->service->addToWhitelist($ip);
        $this->assertTrue($this->service->isWhitelisted($ip));

        $this->service->removeFromWhitelist($ip);
        $this->assertFalse($this->service->isWhitelisted($ip));
    }

    public function testGetWhitelist()
    {
        $whitelist = $this->service->getWhitelist();

        // Should contain localhost
        $this->assertContains('127.0.0.1', $whitelist);
        $this->assertContains('::1', $whitelist);
    }

    public function testCheckWithoutIncrementing()
    {
        $key = 'test_check';
        $ip = '192.168.1.103';

        // Check should return true initially
        $this->assertTrue($this->service->check($key, 'general', $ip));

        // Make 100 attempts (max for general)
        for ($i = 0; $i < 100; $i++) {
            $this->service->attempt($key, 'general', $ip);
        }

        // Check should now return false
        $this->assertFalse($this->service->check($key, 'general', $ip));

        // Verify attempt is still blocked
        $result = $this->service->attempt($key, 'general', $ip);
        $this->assertFalse($result['allowed']);
    }

    public function testRemaining()
    {
        $key = 'test_remaining';
        $ip = '192.168.1.104';

        // Initially should have max remaining
        $this->assertEquals(100, $this->service->remaining($key, 'general', $ip));

        // Make 5 attempts
        for ($i = 0; $i < 5; $i++) {
            $this->service->attempt($key, 'general', $ip);
        }

        // Should have 95 remaining
        $this->assertEquals(95, $this->service->remaining($key, 'general', $ip));
    }

    public function testReset()
    {
        $key = 'test_reset';
        $ip = '192.168.1.105';

        // Make 5 attempts
        for ($i = 0; $i < 5; $i++) {
            $this->service->attempt($key, 'login', $ip);
        }

        // Should be at limit (5 max for login)
        $result = $this->service->attempt($key, 'login', $ip);
        $this->assertFalse($result['allowed']);

        // Reset the limit
        $this->service->reset($key, 'login');

        // Should be allowed again
        $result = $this->service->attempt($key, 'login', $ip);
        $this->assertTrue($result['allowed']);
    }

    public function testDifferentLimitTypes()
    {
        $key = 'test_types';
        $ip = '192.168.1.106';

        // Test login limit (5 attempts)
        $loginLimit = $this->service->getLimit('login');
        $this->assertEquals(5, $loginLimit['max_attempts']);
        $this->assertEquals(15, $loginLimit['decay_minutes']);

        // Test API limit (60 attempts)
        $apiLimit = $this->service->getLimit('api');
        $this->assertEquals(60, $apiLimit['max_attempts']);
        $this->assertEquals(1, $apiLimit['decay_minutes']);

        // Test password reset limit (3 attempts)
        $passwordResetLimit = $this->service->getLimit('password_reset');
        $this->assertEquals(3, $passwordResetLimit['max_attempts']);
        $this->assertEquals(60, $passwordResetLimit['decay_minutes']);

        // Test 2FA verify limit (5 attempts)
        $twoFALimit = $this->service->getLimit('2fa_verify');
        $this->assertEquals(5, $twoFALimit['max_attempts']);
        $this->assertEquals(10, $twoFALimit['decay_minutes']);
    }

    public function testSetCustomLimit()
    {
        $this->service->setLimit('custom', 50, 30);

        $limit = $this->service->getLimit('custom');
        $this->assertEquals(50, $limit['max_attempts']);
        $this->assertEquals(30, $limit['decay_minutes']);

        // Test with the custom limit
        $key = 'test_custom';
        $ip = '192.168.1.107';

        $result = $this->service->attempt($key, 'custom', $ip);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(50, $result['max_attempts']);
    }

    public function testGetHeaders()
    {
        $limitInfo = [
            'max_attempts' => 100,
            'remaining' => 95,
            'reset_at' => time() + 60,
        ];

        $headers = $this->service->getHeaders($limitInfo);

        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);

        $this->assertEquals(100, $headers['X-RateLimit-Limit']);
        $this->assertEquals(95, $headers['X-RateLimit-Remaining']);
    }

    public function testIsLimited()
    {
        $key = 'test_is_limited';
        $ip = '192.168.1.108';

        // Initially not limited
        $this->assertFalse($this->service->isLimited($key, 'login', $ip));

        // Make 5 attempts (login max)
        for ($i = 0; $i < 5; $i++) {
            $this->service->attempt($key, 'login', $ip);
        }

        // Now should be limited
        $this->assertTrue($this->service->isLimited($key, 'login', $ip));
    }

    public function testGetErrorMessage()
    {
        $limitInfo = [
            'reset_at' => time() + 120,
        ];

        $message = $this->service->getErrorMessage($limitInfo);

        // Should contain error message in Portuguese
        $this->assertStringContainsString('Muitas tentativas', $message);
        $this->assertStringContainsString('minuto', $message);
    }

    public function testGetErrorMessageWhitelisted()
    {
        $limitInfo = [
            'whitelisted' => true,
        ];

        $message = $this->service->getErrorMessage($limitInfo);

        $this->assertEquals('IP whitelisted - no rate limit', $message);
    }

    public function testUnknownLimitTypeFallsBackToGeneral()
    {
        $result = $this->service->attempt('test_unknown', 'unknown_type', '192.168.1.109');

        // Should use general limit (100 attempts)
        $this->assertTrue($result['allowed']);
        $this->assertEquals(100, $result['max_attempts']);
    }

    public function testConcurrentRequestsFromDifferentIPs()
    {
        $key = 'test_concurrent';

        // Make requests from different IPs
        $result1 = $this->service->attempt($key, 'login', '192.168.1.1');
        $result2 = $this->service->attempt($key, 'login', '192.168.1.2');
        $result3 = $this->service->attempt($key, 'login', '192.168.1.3');

        // All should be allowed (different IPs = different limits)
        $this->assertTrue($result1['allowed']);
        $this->assertTrue($result2['allowed']);
        $this->assertTrue($result3['allowed']);

        // Each should have 4 remaining (5 max - 1 attempt)
        $this->assertEquals(4, $result1['remaining']);
        $this->assertEquals(4, $result2['remaining']);
        $this->assertEquals(4, $result3['remaining']);
    }

    public function testRateLimitKeyIsolation()
    {
        $ip = '192.168.1.110';

        // Make attempts with different keys
        $result1 = $this->service->attempt('key1', 'login', $ip);
        $result2 = $this->service->attempt('key2', 'login', $ip);

        // Both should be allowed with full remaining count
        $this->assertTrue($result1['allowed']);
        $this->assertTrue($result2['allowed']);
        $this->assertEquals(4, $result1['remaining']);
        $this->assertEquals(4, $result2['remaining']);
    }

    public function testGetResetTime()
    {
        $key = 'test_reset_time';
        $ip = '192.168.1.111';

        // Make first attempt
        $this->service->attempt($key, 'general', $ip);

        // Get reset time
        $resetTime = $this->service->getResetTime($key, 'general');

        // Should be a positive number (seconds until reset)
        $this->assertGreaterThanOrEqual(0, $resetTime);

        // Should be less than or equal to the decay time (60 seconds for general)
        $this->assertLessThanOrEqual(60, $resetTime);
    }
}
