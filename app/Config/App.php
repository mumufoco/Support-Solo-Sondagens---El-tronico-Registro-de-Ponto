<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    /**
     * Base Site URL
     */
    public string $baseURL = 'http://localhost:8080/';

    /**
     * Allowed Hostnames
     */
    public array $allowedHostnames = [];

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
    public bool $forceGlobalSecureRequests = false;

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
