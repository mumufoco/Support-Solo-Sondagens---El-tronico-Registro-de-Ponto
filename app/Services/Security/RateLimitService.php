<?php

namespace App\Services\Security;

use CodeIgniter\Cache\CacheInterface;
use CodeIgniter\Config\Services;

/**
 * Rate Limit Service
 *
 * Implements rate limiting to prevent brute force attacks and API abuse
 *
 * Features:
 * - Per-IP rate limiting
 * - Per-user rate limiting
 * - Per-endpoint rate limiting
 * - Configurable limits and windows
 * - Redis support (with file fallback)
 * - IP whitelisting
 *
 * @package App\Services\Security
 */
class RateLimitService
{
    /**
     * Cache instance
     * @var CacheInterface
     */
    protected CacheInterface $cache;

    /**
     * Rate limit configurations
     * @var array
     */
    protected array $limits = [
        // Login attempts: 5 per 15 minutes per IP
        'login' => [
            'max_attempts' => 5,
            'decay_minutes' => 15,
        ],
        // API requests: 60 per minute per IP
        'api' => [
            'max_attempts' => 60,
            'decay_minutes' => 1,
        ],
        // Password reset: 3 per hour per IP
        'password_reset' => [
            'max_attempts' => 3,
            'decay_minutes' => 60,
        ],
        // 2FA verification: 5 per 10 minutes per IP
        '2fa_verify' => [
            'max_attempts' => 5,
            'decay_minutes' => 10,
        ],
        // General endpoint: 100 per minute per IP
        'general' => [
            'max_attempts' => 100,
            'decay_minutes' => 1,
        ],
    ];

    /**
     * Whitelisted IP addresses (no rate limiting)
     * @var array
     */
    protected array $whitelist = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cache = Services::cache();
        $this->loadWhitelist();
    }

    /**
     * Load IP whitelist from configuration
     */
    protected function loadWhitelist(): void
    {
        $whitelist = getenv('RATE_LIMIT_WHITELIST');

        if ($whitelist) {
            $this->whitelist = array_map('trim', explode(',', $whitelist));
        }

        // Always whitelist localhost
        $this->whitelist[] = '127.0.0.1';
        $this->whitelist[] = '::1';
    }

    /**
     * Check if IP is whitelisted
     *
     * @param string $ip IP address
     * @return bool
     */
    public function isWhitelisted(string $ip): bool
    {
        return in_array($ip, $this->whitelist, true);
    }

    /**
     * Attempt to perform an action (check and increment)
     *
     * @param string $key Unique key for the action
     * @param string $limitType Type of limit (login, api, etc.)
     * @param string|null $ip IP address (optional, will auto-detect)
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_at' => int]
     */
    public function attempt(string $key, string $limitType = 'general', ?string $ip = null): array
    {
        $ip = $ip ?? $this->getClientIp();

        // Check whitelist
        if ($this->isWhitelisted($ip)) {
            return [
                'allowed' => true,
                'remaining' => 999999,
                'reset_at' => time() + 3600,
                'whitelisted' => true,
            ];
        }

        // Get limit config
        $config = $this->limits[$limitType] ?? $this->limits['general'];
        $maxAttempts = $config['max_attempts'];
        $decayMinutes = $config['decay_minutes'];

        // Generate cache key
        $cacheKey = $this->getCacheKey($key, $limitType);

        // Get current attempts
        $attempts = $this->cache->get($cacheKey);

        if ($attempts === null) {
            // First attempt
            $attempts = 0;
        }

        // Increment attempts
        $attempts++;

        // Calculate TTL (time to live)
        $ttl = $decayMinutes * 60;

        // Store attempts
        $this->cache->save($cacheKey, $attempts, $ttl);

        // Calculate reset time
        $resetAt = time() + $ttl;

        // Check if limit exceeded
        $allowed = $attempts <= $maxAttempts;
        $remaining = max(0, $maxAttempts - $attempts);

        // Log if limit exceeded
        if (!$allowed) {
            log_message('warning', "Rate limit exceeded for key: {$key}, type: {$limitType}, IP: {$ip}, attempts: {$attempts}");
        }

        return [
            'allowed' => $allowed,
            'remaining' => $remaining,
            'reset_at' => $resetAt,
            'attempts' => $attempts,
            'max_attempts' => $maxAttempts,
        ];
    }

    /**
     * Check if action is allowed without incrementing
     *
     * @param string $key Unique key
     * @param string $limitType Type of limit
     * @param string|null $ip IP address
     * @return bool
     */
    public function check(string $key, string $limitType = 'general', ?string $ip = null): bool
    {
        $ip = $ip ?? $this->getClientIp();

        // Check whitelist
        if ($this->isWhitelisted($ip)) {
            return true;
        }

        // Get limit config
        $config = $this->limits[$limitType] ?? $this->limits['general'];
        $maxAttempts = $config['max_attempts'];

        // Generate cache key
        $cacheKey = $this->getCacheKey($key, $limitType);

        // Get current attempts
        $attempts = $this->cache->get($cacheKey) ?? 0;

        return $attempts < $maxAttempts;
    }

    /**
     * Get remaining attempts
     *
     * @param string $key Unique key
     * @param string $limitType Type of limit
     * @param string|null $ip IP address
     * @return int Remaining attempts
     */
    public function remaining(string $key, string $limitType = 'general', ?string $ip = null): int
    {
        $ip = $ip ?? $this->getClientIp();

        // Check whitelist
        if ($this->isWhitelisted($ip)) {
            return 999999;
        }

        // Get limit config
        $config = $this->limits[$limitType] ?? $this->limits['general'];
        $maxAttempts = $config['max_attempts'];

        // Generate cache key
        $cacheKey = $this->getCacheKey($key, $limitType);

        // Get current attempts
        $attempts = $this->cache->get($cacheKey) ?? 0;

        return max(0, $maxAttempts - $attempts);
    }

    /**
     * Reset rate limit for a key
     *
     * @param string $key Unique key
     * @param string $limitType Type of limit
     * @return bool
     */
    public function reset(string $key, string $limitType = 'general'): bool
    {
        $cacheKey = $this->getCacheKey($key, $limitType);
        return $this->cache->delete($cacheKey);
    }

    /**
     * Clear all rate limits (use with caution)
     *
     * @return bool
     */
    public function clearAll(): bool
    {
        return $this->cache->clean();
    }

    /**
     * Get time until reset
     *
     * @param string $key Unique key
     * @param string $limitType Type of limit
     * @return int Seconds until reset (0 if not limited)
     */
    public function getResetTime(string $key, string $limitType = 'general'): int
    {
        $cacheKey = $this->getCacheKey($key, $limitType);

        // Try to get metadata from cache
        $info = $this->cache->getMetadata($cacheKey);

        if ($info && isset($info['expire'])) {
            return max(0, $info['expire'] - time());
        }

        return 0;
    }

    /**
     * Get cache key for rate limit
     *
     * @param string $key Unique key
     * @param string $limitType Type of limit
     * @return string
     */
    protected function getCacheKey(string $key, string $limitType): string
    {
        return 'rate_limit:' . $limitType . ':' . md5($key);
    }

    /**
     * Get client IP address
     *
     * Handles proxy headers (X-Forwarded-For, etc.)
     *
     * @return string
     */
    public function getClientIp(): string
    {
        $request = Services::request();

        // Check for proxy headers
        $headers = [
            'HTTP_CF_CONNECTING_IP',    // Cloudflare
            'HTTP_X_FORWARDED_FOR',     // Standard proxy
            'HTTP_X_REAL_IP',           // Nginx proxy
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',              // Direct connection
        ];

        foreach ($headers as $header) {
            $ip = $request->getServer($header);

            if ($ip) {
                // Handle comma-separated IPs (X-Forwarded-For can contain multiple)
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]); // First IP is the client
                }

                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0'; // Fallback
    }

    /**
     * Add IP to whitelist
     *
     * @param string $ip IP address
     * @return void
     */
    public function addToWhitelist(string $ip): void
    {
        if (!in_array($ip, $this->whitelist, true)) {
            $this->whitelist[] = $ip;
        }
    }

    /**
     * Remove IP from whitelist
     *
     * @param string $ip IP address
     * @return void
     */
    public function removeFromWhitelist(string $ip): void
    {
        $this->whitelist = array_filter($this->whitelist, fn($whitelisted) => $whitelisted !== $ip);
    }

    /**
     * Get current whitelist
     *
     * @return array
     */
    public function getWhitelist(): array
    {
        return $this->whitelist;
    }

    /**
     * Set custom limit configuration
     *
     * @param string $type Limit type
     * @param int $maxAttempts Maximum attempts
     * @param int $decayMinutes Decay time in minutes
     * @return void
     */
    public function setLimit(string $type, int $maxAttempts, int $decayMinutes): void
    {
        $this->limits[$type] = [
            'max_attempts' => $maxAttempts,
            'decay_minutes' => $decayMinutes,
        ];
    }

    /**
     * Get limit configuration
     *
     * @param string $type Limit type
     * @return array|null
     */
    public function getLimit(string $type): ?array
    {
        return $this->limits[$type] ?? null;
    }

    /**
     * Generate HTTP headers for rate limit info
     *
     * @param array $limitInfo Result from attempt()
     * @return array HTTP headers
     */
    public function getHeaders(array $limitInfo): array
    {
        return [
            'X-RateLimit-Limit' => $limitInfo['max_attempts'] ?? 0,
            'X-RateLimit-Remaining' => $limitInfo['remaining'] ?? 0,
            'X-RateLimit-Reset' => $limitInfo['reset_at'] ?? 0,
        ];
    }

    /**
     * Check if currently rate limited
     *
     * @param string $key Unique key
     * @param string $limitType Type of limit
     * @param string|null $ip IP address
     * @return bool True if currently limited
     */
    public function isLimited(string $key, string $limitType = 'general', ?string $ip = null): bool
    {
        return !$this->check($key, $limitType, $ip);
    }

    /**
     * Get formatted error message
     *
     * @param array $limitInfo Result from attempt()
     * @return string
     */
    public function getErrorMessage(array $limitInfo): string
    {
        if (isset($limitInfo['whitelisted']) && $limitInfo['whitelisted']) {
            return 'IP whitelisted - no rate limit';
        }

        $resetIn = $limitInfo['reset_at'] - time();
        $minutes = ceil($resetIn / 60);

        return "Muitas tentativas. Tente novamente em {$minutes} minuto(s).";
    }
}
