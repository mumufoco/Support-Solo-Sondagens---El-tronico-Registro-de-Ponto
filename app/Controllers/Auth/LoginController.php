<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;
use App\Models\AuditLogModel;

class LoginController extends BaseController
{
    protected $employeeModel;
    protected $auditModel;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->auditModel = new AuditLogModel();
    }

    /**
     * Display login page
     */
    public function index()
    {
        // Redirect if already logged in
        if ($this->isAuthenticated()) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login');
    }

    /**
     * Process login
     */
    public function authenticate()
    {
        // Validate input
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[6]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $remember = $this->request->getPost('remember') === '1';

        // Check brute force protection
        if ($this->isBruteForceBlocked($email)) {
            $this->setError('Muitas tentativas de login. Tente novamente em 15 minutos.');

            $this->auditModel->log(
                null,
                'LOGIN_BLOCKED',
                'employees',
                null,
                null,
                null,
                "Tentativa de login bloqueada por brute force: {$email}",
                'warning'
            );

            return redirect()->back();
        }

        // Find user by email
        $user = $this->employeeModel->findByEmail($email);

        if (!$user) {
            $this->incrementLoginAttempts($email);
            $this->setError('E-mail ou senha inválidos.');

            $this->auditModel->log(
                null,
                'LOGIN_FAILED',
                'employees',
                null,
                null,
                null,
                "Tentativa de login com e-mail inexistente: {$email}",
                'warning'
            );

            return redirect()->back()->withInput();
        }

        // Check if user is active
        if (!$user->active) {
            $this->setError('Sua conta está inativa. Entre em contato com o administrador.');

            $this->auditModel->log(
                $user->id,
                'LOGIN_FAILED',
                'employees',
                $user->id,
                null,
                null,
                'Tentativa de login com conta inativa',
                'warning'
            );

            return redirect()->back()->withInput();
        }

        // Verify password
        if (!$this->employeeModel->verifyPassword($password, $user->password)) {
            $this->incrementLoginAttempts($email);
            $this->setError('E-mail ou senha inválidos.');

            $this->auditModel->log(
                $user->id,
                'LOGIN_FAILED',
                'employees',
                $user->id,
                null,
                null,
                'Tentativa de login com senha incorreta',
                'warning'
            );

            return redirect()->back()->withInput();
        }

        // Clear login attempts
        $this->clearLoginAttempts($email);

        // Create session
        $sessionData = [
            'user_id'   => $user->id,
            'user_name' => $user->name,
            'user_email' => $user->email,
            'user_role'  => $user->role,
            'logged_in'  => true,
        ];

        $this->session->set($sessionData);

        // Regenerate session ID for security
        $this->session->regenerate();

        // Set remember me cookie if requested
        if ($remember) {
            $this->setRememberMeCookie($user->id);
        }

        // Log successful login
        $this->auditModel->log(
            $user->id,
            'LOGIN',
            'employees',
            $user->id,
            null,
            null,
            'Login bem-sucedido',
            'info'
        );

        $this->setSuccess("Bem-vindo(a), {$user->name}!");

        // Redirect based on role
        return $this->redirectByRole($user->role);
    }

    /**
     * Check if IP is blocked by brute force protection
     */
    protected function isBruteForceBlocked(string $email): bool
    {
        $key = 'login_attempts_' . md5($email . $this->getClientIp());
        $attempts = $this->session->get($key, 0);

        return $attempts >= 5;
    }

    /**
     * Increment login attempts
     */
    protected function incrementLoginAttempts(string $email): void
    {
        $key = 'login_attempts_' . md5($email . $this->getClientIp());
        $attempts = $this->session->get($key, 0);
        $this->session->set($key, $attempts + 1);

        // Set expiration (15 minutes)
        if ($attempts === 0) {
            $this->session->markAsTempdata($key, 900); // 15 minutes
        }
    }

    /**
     * Clear login attempts
     */
    protected function clearLoginAttempts(string $email): void
    {
        $key = 'login_attempts_' . md5($email . $this->getClientIp());
        $this->session->remove($key);
    }

    /**
     * Set remember me cookie
     */
    protected function setRememberMeCookie(int $userId): void
    {
        $token = bin2hex(random_bytes(32));

        // Store token in session for 30 days
        $this->session->set('remember_token', $token);
        $this->session->markAsTempdata('remember_token', 2592000); // 30 days

        // Set cookie
        setcookie(
            'remember_token',
            $token,
            time() + 2592000, // 30 days
            '/',
            '',
            true, // secure
            true  // httponly
        );
    }

    /**
     * Redirect user based on role
     */
    protected function redirectByRole(string $role)
    {
        $redirects = [
            'admin'       => '/dashboard/admin',
            'gestor'      => '/dashboard/manager',
            'funcionario' => '/dashboard/employee',
        ];

        $redirect = $redirects[$role] ?? '/dashboard';

        return redirect()->to($redirect);
    }
}
