<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Authentication Filter
 *
 * Verifies if user is authenticated before accessing protected routes
 */
class AuthFilter implements FilterInterface
{
    /**
     * Check if user is authenticated
     *
     * @param RequestInterface $request
     * @param array|null $arguments
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // Check if user is authenticated
        if (!$session->get('user_id')) {
            // SECURITY FIX: Try to authenticate via remember me cookie
            if ($this->attemptRememberMeLogin($request, $session)) {
                // Successfully logged in via remember me
                return null; // Allow request to proceed
            }

            // SECURITY FIX: Store intended URL with validation to prevent Open Redirect attacks
            // Validate that redirect URL is local and safe before storing
            $intendedUrl = current_url();
            if ($this->isValidRedirectUrl($intendedUrl)) {
                $session->set('redirect_url', $intendedUrl);
            } else {
                // If URL is not valid, don't store it - will redirect to default dashboard
                log_message('security', 'Invalid redirect URL blocked: ' . $intendedUrl);
                $session->remove('redirect_url');
            }

            // Check if it's an AJAX/API request
            if ($request->isAJAX() || $this->isApiRequest($request)) {
                return service('response')
                    ->setJSON([
                        'success' => false,
                        'error' => 'Não autenticado. Faça login para continuar.',
                    ])
                    ->setStatusCode(401);
            }

            // Redirect to login page
            $session->setFlashdata('error', 'Você precisa estar logado para acessar esta página.');
            return redirect()->to('/auth/login');
        }

        // Verify session hasn't expired
        $sessionTimeout = env('SESSION_TIMEOUT', 7200); // 2 hours default
        $lastActivity = $session->get('last_activity');

        if ($lastActivity && (time() - $lastActivity > $sessionTimeout)) {
            // Session expired
            $session->destroy();
            $session->setFlashdata('warning', 'Sua sessão expirou. Faça login novamente.');

            if ($request->isAJAX() || $this->isApiRequest($request)) {
                return service('response')
                    ->setJSON([
                        'success' => false,
                        'error' => 'Sessão expirada.',
                    ])
                    ->setStatusCode(401);
            }

            return redirect()->to('/auth/login');
        }

        // Update last activity
        $session->set('last_activity', time());

        // Check if account is active
        $isActive = $session->get('user_active');
        if ($isActive === false) {
            $session->destroy();
            $session->setFlashdata('error', 'Sua conta foi desativada. Entre em contato com o administrador.');

            if ($request->isAJAX() || $this->isApiRequest($request)) {
                return service('response')
                    ->setJSON([
                        'success' => false,
                        'error' => 'Conta desativada.',
                    ])
                    ->setStatusCode(403);
            }

            return redirect()->to('/auth/login');
        }

        return null; // Allow request to proceed
    }

    /**
     * Do nothing after controller execution
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array|null $arguments
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action needed after request
        return null;
    }

    /**
     * Check if request is an API request
     *
     * @param RequestInterface $request
     * @return bool
     */
    protected function isApiRequest(RequestInterface $request): bool
    {
        // Check if URL starts with /api/
        $uri = $request->getUri();
        $path = $uri->getPath();

        if (strpos($path, '/api/') === 0) {
            return true;
        }

        // Check Accept header
        $accept = $request->getHeaderLine('Accept');
        if (strpos($accept, 'application/json') !== false) {
            return true;
        }

        // Check Content-Type header
        $contentType = $request->getHeaderLine('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            return true;
        }

        return false;
    }

    /**
     * Validate redirect URL to prevent Open Redirect attacks
     *
     * @param string $url
     * @return bool
     */
    protected function isValidRedirectUrl(string $url): bool
    {
        // Empty URLs are not valid
        if (empty($url)) {
            return false;
        }

        // Parse the URL
        $parsedUrl = parse_url($url);

        // If parse_url fails, reject
        if ($parsedUrl === false) {
            return false;
        }

        // Get the base URL from environment
        $baseUrl = base_url();
        $parsedBaseUrl = parse_url($baseUrl);

        // If URL has a scheme or host, verify it matches our base URL
        if (isset($parsedUrl['scheme']) || isset($parsedUrl['host'])) {
            // Check scheme matches (both http or both https)
            if (isset($parsedUrl['scheme']) &&
                (!isset($parsedBaseUrl['scheme']) || $parsedUrl['scheme'] !== $parsedBaseUrl['scheme'])) {
                return false;
            }

            // Check host matches exactly
            if (isset($parsedUrl['host']) &&
                (!isset($parsedBaseUrl['host']) || $parsedUrl['host'] !== $parsedBaseUrl['host'])) {
                return false;
            }

            // Check port matches if present
            if (isset($parsedUrl['port']) &&
                (!isset($parsedBaseUrl['port']) || $parsedUrl['port'] !== $parsedBaseUrl['port'])) {
                // Default ports: 80 for http, 443 for https
                $defaultPort = ($parsedUrl['scheme'] ?? 'http') === 'https' ? 443 : 80;
                $basePort = $parsedBaseUrl['port'] ?? $defaultPort;

                if ($parsedUrl['port'] !== $basePort) {
                    return false;
                }
            }
        }

        // Check for dangerous paths
        $path = $parsedUrl['path'] ?? '/';

        // Block paths that could be used for attacks
        $blockedPaths = [
            '/auth/login',      // Don't redirect back to login
            '/auth/logout',     // Don't redirect back to logout
            '/auth/register',   // Don't redirect to registration
        ];

        foreach ($blockedPaths as $blockedPath) {
            if (strpos($path, $blockedPath) === 0) {
                return false;
            }
        }

        // Block suspicious query parameters that might indicate redirect attacks
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);

            // Check for common redirect parameter names
            $suspiciousParams = ['redirect', 'return', 'returnurl', 'return_url', 'next', 'url', 'goto'];
            foreach ($suspiciousParams as $param) {
                if (isset($queryParams[$param])) {
                    // If there's a redirect parameter in the URL, it might be a chain attack
                    log_message('security', 'Suspicious redirect parameter detected in URL: ' . $param);
                    return false;
                }
            }
        }

        // URL passed all validation checks
        return true;
    }

    /**
     * SECURITY FIX: Attempt to authenticate user via remember me cookie
     *
     * Uses secure selector/verifier pattern with database verification
     *
     * @param RequestInterface $request
     * @param object $session
     * @return bool True if successfully authenticated, false otherwise
     */
    protected function attemptRememberMeLogin(RequestInterface $request, object $session): bool
    {
        // Check if remember_token cookie exists
        $cookieValue = $_COOKIE['remember_token'] ?? null;

        if (empty($cookieValue)) {
            return false;
        }

        // Parse cookie value (format: selector:verifier)
        $parts = explode(':', $cookieValue);

        if (count($parts) !== 2) {
            log_message('warning', 'Invalid remember_token cookie format');
            $this->clearRememberMeCookie();
            return false;
        }

        [$selector, $verifier] = $parts;

        // Validate token using model
        $rememberTokenModel = new \App\Models\RememberTokenModel();
        $employee = $rememberTokenModel->validateToken($selector, $verifier);

        if (!$employee) {
            // Token invalid or expired
            $this->clearRememberMeCookie();
            return false;
        }

        // Token is valid - create session
        $sessionData = [
            'user_id'     => $employee->id,
            'user_name'   => $employee->name,
            'user_email'  => $employee->email,
            'user_role'   => $employee->role,
            'user_active' => $employee->active,
            'logged_in'   => true,
            'remember_me_login' => true, // Flag to indicate auto-login
        ];

        $session->set($sessionData);
        $session->regenerate(); // Prevent session fixation

        // Log the auto-login
        log_message('info', "User {$employee->id} ({$employee->email}) auto-logged in via remember me token");

        // Audit log
        try {
            $auditModel = new \App\Models\AuditLogModel();
            $auditModel->log(
                $employee->id,
                'AUTO_LOGIN_REMEMBER_ME',
                'employees',
                $employee->id,
                null,
                [
                    'ip_address' => get_client_ip(),
                    'user_agent' => get_user_agent(),
                ],
                'Login automático via remember me cookie',
                'info'
            );
        } catch (\Exception $e) {
            log_message('error', 'Failed to log remember me auto-login: ' . $e->getMessage());
        }

        return true;
    }

    /**
     * Clear remember me cookie
     *
     * @return void
     */
    protected function clearRememberMeCookie(): void
    {
        setcookie(
            'remember_token',
            '',
            [
                'expires'  => time() - 3600, // Expired
                'path'     => '/',
                'domain'   => '',
                'secure'   => (ENVIRONMENT === 'production'),
                'httponly' => true,
                'samesite' => 'Strict',
            ]
        );
    }
}
