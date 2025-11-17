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
     */
    public bool $forceGlobalSecureRequests = true;

    /**
     * Session Variables
     */
    public string $sessionDriver            = 'CodeIgniter\Session\Handlers\FileHandler';
    public string $sessionCookieName        = 'ponto_session';
    public int    $sessionExpiration        = 7200;
    public string $sessionSavePath          = WRITEPATH . 'session';
    public bool   $sessionMatchIP           = false;
    public int    $sessionTimeToUpdate      = 300;
    public bool   $sessionRegenerateDestroy = false;

    /**
     * Cookie Settings
     */
    public string $cookiePrefix   = '';
    public string $cookieDomain  = '';
    public string $cookiePath    = '/';
    public bool   $cookieSecure  = false;
    public bool   $cookieHTTPOnly = true;
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
