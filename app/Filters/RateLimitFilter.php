<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Rate Limit Filter
 *
 * Protects against abuse by limiting the number of requests per time window
 */
class RateLimitFilter implements FilterInterface
{
    /**
     * Rate limit configuration
     * Can be overridden by passing arguments to the filter
     */
    protected $defaultLimits = [
        'api' => [
            'requests' => 60,  // 60 requests
            'window' => 60,    // per 60 seconds (1 minute)
        ],
        'auth' => [
            'requests' => 5,   // 5 requests
            'window' => 300,   // per 300 seconds (5 minutes)
        ],
        'punch' => [
            'requests' => 10,  // 10 requests
            'window' => 60,    // per 60 seconds (1 minute)
        ],
        'default' => [
            'requests' => 100, // 100 requests
            'window' => 60,    // per 60 seconds (1 minute)
        ],
    ];

    /**
     * Check rate limit before processing request
     *
     * @param RequestInterface $request
     * @param array|null $arguments
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Get rate limit type from arguments or determine from URL
        $limitType = $arguments[0] ?? $this->determineLimitType($request);

        // Get limit configuration
        $config = $this->defaultLimits[$limitType] ?? $this->defaultLimits['default'];

        // Get client identifier (IP address + user ID if authenticated)
        $clientId = $this->getClientIdentifier();

        // Generate rate limit key
        $key = $this->getRateLimitKey($limitType, $clientId);

        // Check rate limit
        $allowed = $this->checkRateLimit($key, $config['requests'], $config['window']);

        if (!$allowed) {
            // Get retry after time
            $retryAfter = $this->getRetryAfter($key, $config['window']);

            // Log rate limit exceeded
            $this->logRateLimitExceeded($clientId, $limitType, current_url());

            // Return 429 Too Many Requests
            if ($request->isAJAX() || $this->isApiRequest($request)) {
                return service('response')
                    ->setJSON([
                        'success' => false,
                        'error' => 'Muitas requisições. Tente novamente em alguns instantes.',
                        'retry_after' => $retryAfter,
                    ])
                    ->setStatusCode(429)
                    ->setHeader('Retry-After', $retryAfter)
                    ->setHeader('X-RateLimit-Limit', $config['requests'])
                    ->setHeader('X-RateLimit-Remaining', 0)
                    ->setHeader('X-RateLimit-Reset', time() + $retryAfter);
            }

            // For regular requests, show error page
            session()->setFlashdata('error', "Muitas requisições. Aguarde {$retryAfter} segundos e tente novamente.");
            return redirect()->back();
        }

        // Add rate limit headers
        $remaining = $this->getRemainingRequests($key, $config['requests']);
        $reset = $this->getResetTime($key, $config['window']);

        // Store headers to add in after()
        $request->rateLimitHeaders = [
            'X-RateLimit-Limit' => $config['requests'],
            'X-RateLimit-Remaining' => max(0, $remaining),
            'X-RateLimit-Reset' => $reset,
        ];

        return null;
    }

    /**
     * Add rate limit headers to response
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array|null $arguments
     * @return ResponseInterface
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Add rate limit headers if available
        if (isset($request->rateLimitHeaders)) {
            foreach ($request->rateLimitHeaders as $header => $value) {
                $response->setHeader($header, (string) $value);
            }
        }

        return $response;
    }

    /**
     * Determine rate limit type from request
     *
     * @param RequestInterface $request
     * @return string
     */
    protected function determineLimitType(RequestInterface $request): string
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        // Check path patterns
        if (strpos($path, '/api/') === 0) {
            return 'api';
        }

        if (strpos($path, '/auth/') === 0) {
            return 'auth';
        }

        if (strpos($path, '/timesheet/punch') !== false) {
            return 'punch';
        }

        return 'default';
    }

    /**
     * Get client identifier
     *
     * @return string
     */
    protected function getClientIdentifier(): string
    {
        $session = session();
        $userId = $session->get('user_id');

        // Use user ID if authenticated, otherwise IP address
        if ($userId) {
            return 'user_' . $userId;
        }

        // Get client IP
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        // Handle proxy IPs (take first IP)
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }

        return 'ip_' . $ip;
    }

    /**
     * Generate rate limit cache key
     *
     * @param string $type
     * @param string $clientId
     * @return string
     */
    protected function getRateLimitKey(string $type, string $clientId): string
    {
        $window = floor(time() / ($this->defaultLimits[$type]['window'] ?? 60));
        return "ratelimit_{$type}_{$clientId}_{$window}";
    }

    /**
     * Check if request is within rate limit
     *
     * @param string $key
     * @param int $maxRequests
     * @param int $window
     * @return bool
     */
    protected function checkRateLimit(string $key, int $maxRequests, int $window): bool
    {
        $cache = \Config\Services::cache();

        // Get current count
        $count = $cache->get($key);

        if ($count === null) {
            // First request in this window
            $cache->save($key, 1, $window);
            return true;
        }

        if ($count >= $maxRequests) {
            // Rate limit exceeded
            return false;
        }

        // Increment counter
        $cache->save($key, $count + 1, $window);
        return true;
    }

    /**
     * Get remaining requests in current window
     *
     * @param string $key
     * @param int $maxRequests
     * @return int
     */
    protected function getRemainingRequests(string $key, int $maxRequests): int
    {
        $cache = \Config\Services::cache();
        $count = $cache->get($key) ?? 0;

        return max(0, $maxRequests - $count);
    }

    /**
     * Get time until rate limit resets
     *
     * @param string $key
     * @param int $window
     * @return int
     */
    protected function getResetTime(string $key, int $window): int
    {
        $currentWindow = floor(time() / $window);
        return ($currentWindow + 1) * $window;
    }

    /**
     * Get retry after seconds
     *
     * @param string $key
     * @param int $window
     * @return int
     */
    protected function getRetryAfter(string $key, int $window): int
    {
        $resetTime = $this->getResetTime($key, $window);
        return max(1, $resetTime - time());
    }

    /**
     * Check if request is an API request
     *
     * @param RequestInterface $request
     * @return bool
     */
    protected function isApiRequest(RequestInterface $request): bool
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        if (strpos($path, '/api/') === 0) {
            return true;
        }

        $accept = $request->getHeaderLine('Accept');
        if (strpos($accept, 'application/json') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Log rate limit exceeded event
     *
     * @param string $clientId
     * @param string $limitType
     * @param string $url
     * @return void
     */
    protected function logRateLimitExceeded(string $clientId, string $limitType, string $url): void
    {
        try {
            log_message('warning', "Rate limit exceeded: {$clientId} - {$limitType} - {$url}");

            // Log to audit if user is authenticated
            $session = session();
            $userId = $session->get('user_id');

            if ($userId) {
                $auditModel = new \App\Models\AuditLogModel();
                $auditModel->log(
                    $userId,
                    'RATE_LIMIT_EXCEEDED',
                    'system',
                    null,
                    null,
                    ['limit_type' => $limitType, 'url' => $url],
                    "Limite de requisições excedido: {$limitType}",
                    'warning'
                );
            }
        } catch (\Exception $e) {
            log_message('error', 'Failed to log rate limit: ' . $e->getMessage());
        }
    }
}
