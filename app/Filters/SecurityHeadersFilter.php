<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Security Headers Filter
 *
 * Adds security headers to all HTTP responses to protect against common web vulnerabilities
 *
 * Headers implemented:
 * - Content-Security-Policy (CSP): Prevents XSS attacks
 * - HTTP Strict Transport Security (HSTS): Enforces HTTPS
 * - X-Frame-Options: Prevents clickjacking
 * - X-Content-Type-Options: Prevents MIME-sniffing
 * - X-XSS-Protection: Legacy XSS protection
 * - Referrer-Policy: Controls referrer information
 * - Permissions-Policy: Controls browser features
 *
 * Usage in app/Config/Filters.php:
 * - Add to $aliases: 'securityheaders' => \App\Filters\SecurityHeadersFilter::class
 * - Add to $globals['after'] to apply to all responses
 *
 * Configuration can be customized via environment variables or
 * by extending this class and overriding the $headers property.
 *
 * @package App\Filters
 */
class SecurityHeadersFilter implements FilterInterface
{
    /**
     * Security headers configuration
     *
     * @var array
     */
    protected array $headers = [];

    /**
     * Enable HSTS (HTTP Strict Transport Security)
     * Only enable in production with HTTPS
     *
     * @var bool
     */
    protected bool $enableHSTS = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->initializeHeaders();
    }

    /**
     * Initialize security headers based on environment
     *
     * @return void
     */
    protected function initializeHeaders(): void
    {
        // Check if we're in production and using HTTPS
        $isProduction = ENVIRONMENT === 'production';
        $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

        // Enable HSTS only in production with HTTPS
        $this->enableHSTS = $isProduction && $isHttps;

        // Content Security Policy
        // Strict CSP to prevent XSS attacks
        $this->headers['Content-Security-Policy'] = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'", // Needed for CodeIgniter and common JS frameworks
            "style-src 'self' 'unsafe-inline'", // Needed for inline styles
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "media-src 'self'",
            "object-src 'none'",
            "frame-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "upgrade-insecure-requests",
        ]);

        // X-Frame-Options
        // Prevents clickjacking attacks
        $this->headers['X-Frame-Options'] = 'DENY';

        // X-Content-Type-Options
        // Prevents MIME-sniffing attacks
        $this->headers['X-Content-Type-Options'] = 'nosniff';

        // X-XSS-Protection
        // Legacy XSS protection (modern browsers use CSP)
        $this->headers['X-XSS-Protection'] = '1; mode=block';

        // Referrer-Policy
        // Controls how much referrer information is sent
        $this->headers['Referrer-Policy'] = 'strict-origin-when-cross-origin';

        // Permissions-Policy (formerly Feature-Policy)
        // Controls browser features and APIs
        $this->headers['Permissions-Policy'] = implode(', ', [
            'accelerometer=()',
            'camera=()',
            'geolocation=()',
            'gyroscope=()',
            'magnetometer=()',
            'microphone=()',
            'payment=()',
            'usb=()',
        ]);

        // HTTP Strict Transport Security (HSTS)
        // Only add if HTTPS is enabled
        if ($this->enableHSTS) {
            // 1 year (31536000 seconds) with includeSubDomains and preload
            $this->headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains; preload';
        }

        // Load custom headers from environment
        $this->loadCustomHeaders();
    }

    /**
     * Load custom headers from environment variables
     *
     * @return void
     */
    protected function loadCustomHeaders(): void
    {
        // Allow overriding CSP via environment
        $customCSP = getenv('SECURITY_CSP');
        if ($customCSP) {
            $this->headers['Content-Security-Policy'] = $customCSP;
        }

        // Allow overriding Referrer-Policy
        $customReferrer = getenv('SECURITY_REFERRER_POLICY');
        if ($customReferrer) {
            $this->headers['Referrer-Policy'] = $customReferrer;
        }

        // Allow disabling frame protection for specific use cases
        $allowFrames = getenv('SECURITY_ALLOW_FRAMES');
        if ($allowFrames === 'true') {
            $this->headers['X-Frame-Options'] = 'SAMEORIGIN';
        }
    }

    /**
     * Before filter (not used)
     *
     * @param RequestInterface $request
     * @param array|null $arguments
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    /**
     * Add security headers to response
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array|null $arguments
     * @return ResponseInterface
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Don't add headers to file downloads
        $contentType = $response->getHeaderLine('Content-Type');
        if ($this->isFileDownload($contentType)) {
            return $response;
        }

        // Add all security headers
        foreach ($this->headers as $header => $value) {
            // Only add if not already set
            if (!$response->hasHeader($header)) {
                $response->setHeader($header, $value);
            }
        }

        // Log HSTS status in development
        if (ENVIRONMENT === 'development' && !$this->enableHSTS) {
            log_message('debug', 'HSTS not enabled: Requires production environment with HTTPS');
        }

        return $response;
    }

    /**
     * Check if response is a file download
     *
     * Skip some security headers for file downloads
     *
     * @param string $contentType
     * @return bool
     */
    protected function isFileDownload(string $contentType): bool
    {
        $downloadTypes = [
            'application/octet-stream',
            'application/pdf',
            'application/zip',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument',
        ];

        foreach ($downloadTypes as $type) {
            if (strpos($contentType, $type) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set custom header
     *
     * @param string $name Header name
     * @param string $value Header value
     * @return void
     */
    public function setHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    /**
     * Remove header
     *
     * @param string $name Header name
     * @return void
     */
    public function removeHeader(string $name): void
    {
        unset($this->headers[$name]);
    }

    /**
     * Get all configured headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Enable HSTS (for testing purposes)
     *
     * @param bool $enable
     * @return void
     */
    public function setEnableHSTS(bool $enable): void
    {
        $this->enableHSTS = $enable;

        if ($enable) {
            $this->headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains; preload';
        } else {
            unset($this->headers['Strict-Transport-Security']);
        }
    }

    /**
     * Check if HSTS is enabled
     *
     * @return bool
     */
    public function isHSTSEnabled(): bool
    {
        return $this->enableHSTS;
    }

    /**
     * Set custom CSP
     *
     * @param array $directives CSP directives
     * @return void
     */
    public function setCSP(array $directives): void
    {
        $csp = [];
        foreach ($directives as $directive => $sources) {
            if (is_array($sources)) {
                $csp[] = $directive . ' ' . implode(' ', $sources);
            } else {
                $csp[] = $directive . ' ' . $sources;
            }
        }

        $this->headers['Content-Security-Policy'] = implode('; ', $csp);
    }

    /**
     * Get current CSP
     *
     * @return string
     */
    public function getCSP(): string
    {
        return $this->headers['Content-Security-Policy'] ?? '';
    }

    /**
     * Set Permissions-Policy
     *
     * @param array $permissions Array of permission => allowed origins
     * @return void
     */
    public function setPermissionsPolicy(array $permissions): void
    {
        $policies = [];
        foreach ($permissions as $feature => $origins) {
            if (is_array($origins)) {
                $policies[] = $feature . '=(' . implode(' ', $origins) . ')';
            } else {
                $policies[] = $feature . '=(' . $origins . ')';
            }
        }

        $this->headers['Permissions-Policy'] = implode(', ', $policies);
    }

    /**
     * Allow frames from same origin
     *
     * @return void
     */
    public function allowSameOriginFrames(): void
    {
        $this->headers['X-Frame-Options'] = 'SAMEORIGIN';
    }

    /**
     * Allow frames from specific origin
     *
     * @param string $origin
     * @return void
     */
    public function allowFramesFrom(string $origin): void
    {
        $this->headers['X-Frame-Options'] = "ALLOW-FROM {$origin}";
    }

    /**
     * Deny all frames (default)
     *
     * @return void
     */
    public function denyAllFrames(): void
    {
        $this->headers['X-Frame-Options'] = 'DENY';
    }
}
