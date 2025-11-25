<?php

namespace App\Libraries;

use CodeIgniter\Session\Session;
use Config\Session as SessionConfig;

/**
 * Safe Session Class
 *
 * Extends CodeIgniter's Session class but overrides the configure() method
 * to avoid ini_set() calls that may be disabled or cause "headers already sent" errors
 * in shared hosting environments.
 */
class SafeSession extends Session
{
    /**
     * Configure session settings
     *
     * This override prevents ini_set() calls that can fail in restricted environments
     * or when headers have already been sent. All session configuration should be
     * done in .user.ini, php.ini, or Boot files instead.
     *
     * @return void
     */
    protected function configure(): void
    {
        // Get session configuration
        $config = config('Session');

        // Set cookie parameters directly without ini_set()
        // These are applied when session_start() is called
        if (session_status() === PHP_SESSION_NONE) {
            // Set session name
            if (!empty($config->cookieName)) {
                session_name($config->cookieName);
            }

            // Configure session cookie parameters
            $cookieParams = [
                'lifetime' => $config->expiration,
                'path' => $config->cookiePath ?? '/',
                'domain' => $config->cookieDomain ?? '',
                'secure' => $config->cookieSecure ?? false,
                'httponly' => true, // Always use httponly for security
                'samesite' => $config->cookieSameSite ?? 'Lax'
            ];

            // Set cookie parameters (this works even after headers are sent)
            session_set_cookie_params($cookieParams);
        }

        // Log that we're using safe session configuration
        if (ENVIRONMENT === 'development') {
            log_message('info', 'SafeSession: Using safe session configuration (no ini_set calls)');
        }
    }
}
