<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Home extends BaseController
{
    /**
     * Home page - Redirect to appropriate location based on authentication
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function index()
    {
        // If user is authenticated, redirect to dashboard
        if ($this->isAuthenticated()) {
            $user = $this->currentUser;

            // Redirect based on role
            if ($user && isset($user->role)) {
                switch ($user->role) {
                    case 'admin':
                        return redirect()->to('/dashboard/admin');
                    case 'manager':
                        return redirect()->to('/dashboard/manager');
                    case 'employee':
                        return redirect()->to('/dashboard/employee');
                    default:
                        return redirect()->to('/dashboard');
                }
            }

            // Default dashboard if no role specified
            return redirect()->to('/dashboard');
        }

        // Not authenticated - redirect to login
        return redirect()->to('/auth/login');
    }
}
