<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;
use App\Models\AuditLogModel;

class LoginController extends BaseController
{
    protected ?EmployeeModel $employeeModel = null;
    protected ?AuditLogModel $auditModel = null;

    public function __construct()
    {
        // Check if using JSON database (no MySQL)
        if (!file_exists(ROOTPATH . 'writable/INSTALLED')) {
            try {
                $this->employeeModel = new EmployeeModel();
                $this->auditModel = new AuditLogModel();
            } catch (\Exception $e) {
                // Database not configured
            }
        }
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
            'password' => 'required|min_length[12]',
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

            if ($this->auditModel) {
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
            }

            return redirect()->back();
        }

        // Find user by email
        // Check if using JSON database
        if (file_exists(ROOTPATH . 'writable/INSTALLED')) {
            $user = $this->findUserFromJson($email);
        } else {
            $user = $this->employeeModel ? $this->employeeModel->findByEmail($email) : null;
        }

        if (!$user) {
            $this->incrementLoginAttempts($email);
            $this->setError('E-mail ou senha inválidos.');

            if ($this->auditModel) {
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
            }

            return redirect()->back()->withInput();
        }

        // Check if user is active
        $isActive = is_object($user) ? $user->active : ($user['active'] ?? 1);
        if (!$isActive) {
            $this->setError('Sua conta está inativa. Entre em contato com o administrador.');

            $userId = is_object($user) ? $user->id : ($user['id'] ?? null);
            if ($this->auditModel) {
                $this->auditModel->log(
                    $userId,
                    'LOGIN_FAILED',
                    'employees',
                    $userId,
                    null,
                    null,
                    'Tentativa de login com conta inativa',
                    'warning'
                );
            }

            return redirect()->back()->withInput();
        }

        // Verify password
        $userPassword = is_object($user) ? $user->password : ($user['password'] ?? '');
        $passwordValid = false;

        if (file_exists(ROOTPATH . 'writable/INSTALLED')) {
            // JSON mode - verify directly
            $passwordValid = password_verify($password, $userPassword);
        } else {
            // MySQL mode - use model method
            $passwordValid = $this->employeeModel ? $this->employeeModel->verifyPassword($password, $userPassword) : false;
        }

        if (!$passwordValid) {
            $this->incrementLoginAttempts($email);
            $this->setError('E-mail ou senha inválidos.');

            $userId = is_object($user) ? $user->id : ($user['id'] ?? null);
            if ($this->auditModel) {
                $this->auditModel->log(
                    $userId,
                    'LOGIN_FAILED',
                    'employees',
                    $userId,
                    null,
                    null,
                    'Tentativa de login com senha incorreta',
                    'warning'
                );
            }

            return redirect()->back()->withInput();
        }

        // Clear login attempts
        $this->clearLoginAttempts($email);

        // Extract user data (works for both object and array)
        $userId = is_object($user) ? $user->id : ($user['id'] ?? null);
        $userName = is_object($user) ? ($user->name ?? $user->full_name ?? '') : ($user['name'] ?? $user['full_name'] ?? '');
        $userEmail = is_object($user) ? $user->email : ($user['email'] ?? '');
        $userRole = is_object($user) ? $user->role : ($user['role'] ?? 'funcionario');

        // Create session
        $sessionData = [
            'user_id'   => $userId,
            'user_name' => $userName,
            'user_email' => $userEmail,
            'user_role'  => $userRole,
            'logged_in'  => true,
        ];

        $this->session->set($sessionData);

        // SECURITY: Regenerate session ID to prevent session fixation
        // This should ALSO be done whenever user role/privileges change
        // See: regenerateSessionOnRoleChange() method
        $this->session->regenerate();

        // Set remember me cookie if requested
        if ($remember && $userId) {
            $this->setRememberMeCookie($userId);
        }

        // Log successful login
        if ($this->auditModel) {
            $this->auditModel->log(
                $userId,
                'LOGIN',
                'employees',
                $userId,
                null,
                null,
                'Login bem-sucedido',
                'info'
            );
        }

        $welcomeName = $userName ?: 'Usuário';
        $this->setSuccess("Bem-vindo(a), {$welcomeName}!");

        // Redirect based on role
        return $this->redirectByRole($userRole);
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
     * SECURITY FIX: Set secure remember me cookie
     *
     * Uses selector/verifier pattern with database storage
     * Tokens are hashed in database for security
     */
    protected function setRememberMeCookie(int $userId): void
    {
        $rememberTokenModel = new \App\Models\RememberTokenModel();

        // Generate secure token (returns selector and verifier)
        $tokenData = $rememberTokenModel->generateToken($userId, 30); // 30 days

        if (!$tokenData) {
            log_message('error', 'Failed to generate remember me token for user ' . $userId);
            return;
        }

        // Combine selector:verifier for cookie storage
        $cookieValue = $tokenData['selector'] . ':' . $tokenData['verifier'];

        // Set cookie with security flags
        setcookie(
            'remember_token',
            $cookieValue,
            [
                'expires'  => time() + (30 * 24 * 60 * 60), // 30 days
                'path'     => '/',
                'domain'   => '',
                'secure'   => (ENVIRONMENT === 'production'), // HTTPS only in production
                'httponly' => true, // Prevent JavaScript access
                'samesite' => 'Strict', // CSRF protection
            ]
        );

        log_message('info', 'Remember me token created for employee ' . $userId);
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

    /**
     * Find user from JSON database
     */
    protected function findUserFromJson(string $email): ?array
    {
        $employeesFile = ROOTPATH . 'writable/database/employees.json';

        if (!file_exists($employeesFile)) {
            return null;
        }

        $employees = json_decode(file_get_contents($employeesFile), true);

        if (!is_array($employees)) {
            return null;
        }

        foreach ($employees as $employee) {
            if (isset($employee['email']) && $employee['email'] === $email) {
                return $employee;
            }
        }

        return null;
    }
}
