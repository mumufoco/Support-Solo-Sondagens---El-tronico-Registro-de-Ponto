<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\AuditLogModel;

class LogoutController extends BaseController
{
    protected $auditModel;

    public function __construct()
    {
        // Check if using JSON database (no MySQL)
        if (file_exists(WRITEPATH . 'INSTALLED')) {
            return; // JSON mode - model not needed
        }

        try {
            $this->auditModel = new AuditLogModel();
        } catch (\Exception $e) {
            // Database not configured
        }
    }

    /**
     * Process logout
     */
    public function index()
    {
        // Check if user is authenticated
        if (!$this->isAuthenticated()) {
            return redirect()->to('/auth/login');
        }

        // Get user info before destroying session
        $userId = $this->session->get('user_id');
        $userName = $this->session->get('user_name');

        // Log logout (only if database is configured)
        if ($this->auditModel) {
            try {
                $this->auditModel->log(
                    $userId,
                    'LOGOUT',
                    'employees',
                    $userId,
                    null,
                    null,
                    "Logout: {$userName}",
                    'info'
                );
            } catch (\Exception $e) {
                // Ignore audit log errors
            }
        }

        // Clear remember me cookie
        $this->clearRememberMeCookie();

        // Destroy session
        $this->session->destroy();

        // Set flash message for next request
        session()->setFlashdata('success', 'Você saiu do sistema com sucesso.');

        return redirect()->to('/auth/login');
    }

    /**
     * Clear remember me cookie
     */
    protected function clearRememberMeCookie(): void
    {
        // Remove session token
        $this->session->remove('remember_token');

        // Clear cookie
        setcookie(
            'remember_token',
            '',
            time() - 3600,
            '/',
            '',
            true, // secure
            true  // httponly
        );
    }
}
