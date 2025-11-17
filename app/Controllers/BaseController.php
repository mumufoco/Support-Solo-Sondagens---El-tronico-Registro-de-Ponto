<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = ['form', 'url', 'text', 'date'];

    /**
     * Current user session data
     */
    protected $session;

    /**
     * Current authenticated user
     */
    protected $currentUser;

    /**
     * Constructor.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        $this->session = \Config\Services::session();

        // Load current user if authenticated
        if ($this->session->has('user_id')) {
            $employeeModel = new \App\Models\EmployeeModel();
            $this->currentUser = $employeeModel->find($this->session->get('user_id'));
        }
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated(): bool
    {
        return $this->session->has('user_id') && $this->currentUser !== null;
    }

    /**
     * Require authentication
     */
    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            $this->session->setFlashdata('error', 'Você precisa estar logado para acessar esta página.');
            redirect()->to('/auth/login')->send();
            exit;
        }
    }

    /**
     * Check if user has specific role
     */
    protected function hasRole(string $role): bool
    {
        if (!$this->currentUser) {
            return false;
        }

        // Verificar se a propriedade 'role' existe no objeto
        if (!isset($this->currentUser->role)) {
            log_message('error', 'User object missing role property. User ID: ' . ($this->currentUser->id ?? 'unknown'));
            return false;
        }

        return $this->currentUser->role === $role;
    }

    /**
     * Require specific role
     */
    protected function requireRole(string $role): void
    {
        $this->requireAuth();

        if (!$this->hasRole($role)) {
            $this->session->setFlashdata('error', 'Você não tem permissão para acessar esta página.');
            redirect()->to('/dashboard')->send();
            exit;
        }
    }

    /**
     * Check if user is admin or gestor
     */
    protected function isManager(): bool
    {
        return $this->hasRole('admin') || $this->hasRole('gestor');
    }

    /**
     * Require admin or gestor role
     */
    protected function requireManager(): void
    {
        $this->requireAuth();

        if (!$this->isManager()) {
            $this->session->setFlashdata('error', 'Você não tem permissão para acessar esta página.');
            redirect()->to('/dashboard')->send();
            exit;
        }
    }

    /**
     * Set success message
     */
    protected function setSuccess(string $message): void
    {
        $this->session->setFlashdata('success', $message);
    }

    /**
     * Set error message
     */
    protected function setError(string $message): void
    {
        $this->session->setFlashdata('error', $message);
    }

    /**
     * Set warning message
     */
    protected function setWarning(string $message): void
    {
        $this->session->setFlashdata('warning', $message);
    }

    /**
     * Set info message
     */
    protected function setInfo(string $message): void
    {
        $this->session->setFlashdata('info', $message);
    }

    /**
     * Return JSON response
     */
    protected function respondWithJson($data, int $statusCode = 200)
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON($data);
    }

    /**
     * Return success JSON response
     */
    protected function respondSuccess($data = [], string $message = 'Success', int $statusCode = 200)
    {
        return $this->respondWithJson([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $statusCode);
    }

    /**
     * Return error JSON response
     */
    protected function respondError(string $message = 'Error', $errors = null, int $statusCode = 400)
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return $this->respondWithJson($response, $statusCode);
    }

    /**
     * Log audit trail
     */
    protected function logAudit(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        string $level = 'info'
    ): void {
        $auditModel = new \App\Models\AuditLogModel();

        $auditModel->log(
            $this->currentUser->id ?? null,
            $action,
            $entityType,
            $entityId,
            $oldValues,
            $newValues,
            $description,
            $level
        );
    }

    /**
     * Get client IP address
     */
    protected function getClientIp(): string
    {
        return $this->request->getIPAddress();
    }

    /**
     * Get user agent
     */
    protected function getUserAgent(): string
    {
        return $this->request->getUserAgent()->getAgentString();
    }

    /**
     * Validate CSRF token for AJAX requests
     */
    protected function validateCsrf(): bool
    {
        $token = $this->request->getHeaderLine('X-CSRF-TOKEN');

        if (empty($token)) {
            $token = $this->request->getPost('csrf_token');
        }

        return csrf_hash() === $token;
    }
}
