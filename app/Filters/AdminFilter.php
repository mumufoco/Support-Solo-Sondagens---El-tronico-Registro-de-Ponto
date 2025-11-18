<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Admin Filter
 *
 * Verifies if user has admin role before accessing admin-only routes
 */
class AdminFilter implements FilterInterface
{
    /**
     * Check if user is admin
     *
     * @param RequestInterface $request
     * @param array|null $arguments
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // First check if user is authenticated
        if (!$session->get('user_id')) {
            $session->set('redirect_url', current_url());
            $session->setFlashdata('error', 'Você precisa estar logado para acessar esta página.');

            if ($request->isAJAX() || $this->isApiRequest($request)) {
                return service('response')
                    ->setJSON([
                        'success' => false,
                        'error' => 'Não autenticado.',
                    ])
                    ->setStatusCode(401);
            }

            return redirect()->to('/auth/login');
        }

        // Check if user has admin role
        // SECURITY FIX: Verify role from database, not just session
        // This prevents privilege escalation if session is compromised
        $userId = $session->get('user_id');

        $employeeModel = new \App\Models\EmployeeModel();
        $user = $employeeModel->find($userId);

        if (!$user || !$user->active) {
            $session->destroy();
            $session->setFlashdata('error', 'Sua conta não está mais ativa.');
            return redirect()->to('/auth/login');
        }

        // Verify role from database (source of truth)
        if ($user->role !== 'admin') {
            // Update session if role changed
            if ($session->get('user_role') !== $user->role) {
                $session->set('user_role', $user->role);
                log_message('warning', "User {$userId} role mismatch detected - session updated");
            }

            // Log unauthorized access attempt
            $this->logUnauthorizedAccess($userId, current_url());

            $session->setFlashdata('error', 'Você não tem permissão para acessar esta área. Acesso restrito a administradores.');

            if ($request->isAJAX() || $this->isApiRequest($request)) {
                return service('response')
                    ->setJSON([
                        'success' => false,
                        'error' => 'Acesso negado. Permissão de administrador necessária.',
                    ])
                    ->setStatusCode(403);
            }

            // Redirect to user's dashboard based on role
            $dashboardUrl = $this->getDashboardUrl($user->role);
            return redirect()->to($dashboardUrl);
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
     * Get dashboard URL based on user role
     *
     * @param string $role
     * @return string
     */
    protected function getDashboardUrl(string $role): string
    {
        switch ($role) {
            case 'gestor':
                return '/dashboard/manager';
            case 'funcionario':
                return '/dashboard/employee';
            default:
                return '/dashboard';
        }
    }

    /**
     * Log unauthorized access attempt
     *
     * @param int $userId
     * @param string $url
     * @return void
     */
    protected function logUnauthorizedAccess(int $userId, string $url): void
    {
        try {
            $auditModel = new \App\Models\AuditLogModel();

            $auditModel->log(
                $userId,
                'UNAUTHORIZED_ACCESS_ATTEMPT',
                'system',
                null,
                null,
                ['url' => $url, 'required_role' => 'admin'],
                "Tentativa de acesso não autorizado à área de administrador: {$url}",
                'warning'
            );
        } catch (\Exception $e) {
            log_message('error', 'Failed to log unauthorized access: ' . $e->getMessage());
        }
    }
}
