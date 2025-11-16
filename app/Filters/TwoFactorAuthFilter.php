<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\EmployeeModel;

/**
 * Two-Factor Authentication Filter
 *
 * Ensures 2FA is verified for employees who have it enabled
 */
class TwoFactorAuthFilter implements FilterInterface
{
    /**
     * Routes that should bypass 2FA check
     *
     * @var array
     */
    protected $allowedRoutes = [
        'auth/login',
        'auth/logout',
        'auth/2fa/verify',
        'auth/2fa/setup',
        'auth/2fa/enable',
    ];

    /**
     * Check if 2FA is required and verified
     *
     * @param RequestInterface $request
     * @param array|null $arguments
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Get current route
        $uri = $request->getUri();
        $path = $uri->getPath();

        // Skip check for allowed routes
        foreach ($this->allowedRoutes as $route) {
            if (strpos($path, $route) !== false) {
                return null;
            }
        }

        // Check if user is logged in
        $employeeId = session()->get('employee_id');

        if (!$employeeId) {
            // Not logged in, let AuthFilter handle it
            return null;
        }

        // Check if there's a pending 2FA verification
        $pending2FA = session()->get('2fa_pending_employee_id');

        if ($pending2FA) {
            // User needs to complete 2FA verification
            return redirect()->to('/auth/2fa/verify');
        }

        // Check if employee has 2FA enabled
        $employeeModel = new EmployeeModel();
        $employee = $employeeModel->find($employeeId);

        if (!$employee) {
            return redirect()->to('/auth/login')
                ->with('error', 'Sessão inválida.');
        }

        // If 2FA is enabled, check if it's been verified in this session
        if ($employee->two_factor_enabled) {
            $verified = session()->get('2fa_verified');

            if (!$verified) {
                // Set pending 2FA and redirect to verification
                session()->set('2fa_pending_employee_id', $employeeId);
                session()->remove('employee_id');

                return redirect()->to('/auth/2fa/verify')
                    ->with('info', 'Por favor, complete a verificação 2FA.');
            }
        }

        return null;
    }

    /**
     * After filter (not used)
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
}
