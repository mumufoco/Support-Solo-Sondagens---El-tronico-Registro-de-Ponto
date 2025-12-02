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

        // CRITICAL DEBUG: Log session state in AuthFilter
        log_message('debug', '[AUTHFILTER] Request to: ' . current_url());
        log_message('debug', '[AUTHFILTER] Session ID: ' . session_id());
        log_message('debug', '[AUTHFILTER] Has user_id: ' . ($session->get('user_id') ? 'YES' : 'NO'));

        // Check if user is authenticated
        if (!$session->get('user_id')) {
            log_message('warning', '[AUTHFILTER] No user_id in session, redirecting to login');
            // Store intended URL for redirect after login
            $session->set('redirect_url', current_url());

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

        // NOTE: Session expiration is handled by CodeIgniter's session handler
        // No need for manual timeout checking here - it can cause premature logout

        // Update last activity for application-level tracking
        $session->set('last_activity', time());

        // NOTE: Account active status is checked at login
        // Checking on every request and destroying session can cause loops
        // If account needs to be disabled, admin should force logout from their panel

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
}
