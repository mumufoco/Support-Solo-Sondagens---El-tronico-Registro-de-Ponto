<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    /**
     * Base Site URL
     *
     * Automatically configured from environment variable.
     * Falls back to localhost if not set.
     */
    public string $baseURL = '';

    /**
     * Allowed Hostnames
     */
    public array $allowedHostnames = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Set baseURL from environment or use auto-detection
        $this->baseURL = env('app.baseURL', $this->baseURL);

        // If still empty, auto-detect from server
        if (empty($this->baseURL)) {
            $this->baseURL = $this->detectBaseURL();
        }

        // Force HTTPS in production environment
        $this->forceGlobalSecureRequests = env('app.forceGlobalSecureRequests', ENVIRONMENT === 'production');

        // Secure cookies in production OR when using HTTPS
        if (ENVIRONMENT === 'production' || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')) {
            $this->cookieSecure = true;
        }
    }

    /**
     * Auto-detect base URL from server variables
     */
    private function detectBaseURL(): string
    {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $protocol . '://' . $host . '/';
    }

    /**
     * Index Page
     */
    public string $indexPage = '';

    /**
     * URI Protocol
     */
    public string $uriProtocol = 'REQUEST_URI';

    /**
     * Default Locale
     */
    public string $defaultLocale = 'pt-BR';

    /**
     * Negotiate Locale
     */
    public bool $negotiateLocale = false;

    /**
     * Supported Locales
     */
    public array $supportedLocales = ['pt-BR'];

    /**
     * Application Timezone
     */
    public string $appTimezone = 'America/Sao_Paulo';

    /**
     * Default Character Set
     */
    public string $charset = 'UTF-8';

    /**
     * Force Global Secure Requests
     * PRODUCTION: Should always be true (forces HTTPS)
     * DEVELOPMENT: Can be false for local testing
     */
    public bool $forceGlobalSecureRequests = false; // Will be set in constructor

    /**
     * Session Variables
     *
     * IMPORTANT: Session configuration has been moved to Config/Session.php
     * as per CodeIgniter 4.5+ best practices. DO NOT configure session
     * settings here as it may cause conflicts with Session.php.
     *
     * Session settings are now in: app/Config/Session.php
     */

    /**
     * Cookie Settings
     *
     * SECURITY: cookieSecure will be set to true in constructor for production
     * SECURITY: cookieHTTPOnly is ALWAYS true to prevent XSS attacks
     */
    public string $cookiePrefix   = '';
    public string $cookieDomain  = '';
    public string $cookiePath    = '/';
    public bool   $cookieSecure  = false; // Set to true in constructor for production
    public bool   $cookieHTTPOnly = true;  // ALWAYS true for security
    public ?string $cookieSameSite = 'Lax';

    /**
     * Reverse Proxy IPs
     */
    public array $proxyIPs = [];

    /**
     * Content Security Policy
     */
    public bool $CSPEnabled = false;
}
