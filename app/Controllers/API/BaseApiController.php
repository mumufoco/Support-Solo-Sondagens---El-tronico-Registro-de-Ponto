<?php

namespace App\Controllers\API;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\EmployeeModel;

/**
 * Base API Controller
 *
 * Provides common functionality for all API controllers including:
 * - JWT authentication
 * - Standard JSON responses
 * - Error handling
 * - Authenticated employee retrieval
 *
 * All API controllers should extend this class.
 */
class BaseApiController extends ResourceController
{
    use ResponseTrait;

    /**
     * Authenticated employee (loaded from token)
     * @var object|null
     */
    protected $authenticatedEmployee = null;

    /**
     * Employee model
     * @var EmployeeModel
     */
    protected $employeeModel;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
    }

    /**
     * Get authenticated employee from JWT token
     *
     * Extracts and validates the Bearer token from the Authorization header,
     * decodes the JWT, and loads the employee record.
     *
     * @return object|null Employee object if authenticated, null otherwise
     */
    protected function getAuthenticatedEmployee(): ?object
    {
        // Check if already loaded
        if ($this->authenticatedEmployee !== null) {
            return $this->authenticatedEmployee;
        }

        // Get Authorization header
        $authHeader = $this->request->getHeaderLine('Authorization');

        if (empty($authHeader)) {
            return null;
        }

        // Extract token from "Bearer <token>"
        $token = null;
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }

        if (empty($token)) {
            return null;
        }

        try {
            // Decode JWT token
            $decoded = $this->decodeJWT($token);

            if (!$decoded || !isset($decoded->employee_id)) {
                return null;
            }

            // Load employee
            $employee = $this->employeeModel->find($decoded->employee_id);

            if (!$employee) {
                return null;
            }

            // Check if employee is active
            if (!$employee->active) {
                return null;
            }

            // Cache the authenticated employee
            $this->authenticatedEmployee = $employee;

            return $employee;

        } catch (\Exception $e) {
            log_message('error', 'JWT decode error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Decode JWT token
     *
     * @param string $token JWT token
     * @return object|null Decoded token data
     */
    protected function decodeJWT(string $token): ?object
    {
        try {
            // Get encryption key
            $key = env('app.encryption.key');

            if (empty($key)) {
                throw new \Exception('Encryption key not configured');
            }

            // Remove 'base64:' prefix if present
            if (strpos($key, 'base64:') === 0) {
                $key = base64_decode(substr($key, 7));
            }

            // Simple JWT decode (for production, use firebase/php-jwt library)
            // This is a basic implementation - you should use a proper JWT library
            $parts = explode('.', $token);

            if (count($parts) !== 3) {
                throw new \Exception('Invalid token format');
            }

            [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

            // Decode payload
            $payload = json_decode(base64_decode(strtr($payloadEncoded, '-_', '+/')));

            if (!$payload) {
                throw new \Exception('Invalid payload');
            }

            // Verify signature
            $expectedSignature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $key, true);
            $expectedSignatureEncoded = rtrim(strtr(base64_encode($expectedSignature), '+/', '-_'), '=');

            if (!hash_equals($expectedSignatureEncoded, $signatureEncoded)) {
                throw new \Exception('Invalid signature');
            }

            // Check expiration
            if (isset($payload->exp) && $payload->exp < time()) {
                throw new \Exception('Token expired');
            }

            return $payload;

        } catch (\Exception $e) {
            log_message('error', 'JWT decode failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate JWT token for employee
     *
     * @param int $employeeId Employee ID
     * @param int $expiresIn Expiration time in seconds (default: 24 hours)
     * @return string JWT token
     */
    protected function generateJWT(int $employeeId, int $expiresIn = 86400): string
    {
        // Get encryption key
        $key = env('app.encryption.key');

        if (empty($key)) {
            throw new \Exception('Encryption key not configured');
        }

        // Remove 'base64:' prefix if present
        if (strpos($key, 'base64:') === 0) {
            $key = base64_decode(substr($key, 7));
        }

        // Create header
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];

        // Create payload
        $payload = [
            'employee_id' => $employeeId,
            'iat' => time(),
            'exp' => time() + $expiresIn,
        ];

        // Encode header and payload
        $headerEncoded = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
        $payloadEncoded = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

        // Create signature
        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $key, true);
        $signatureEncoded = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        // Return JWT
        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    /**
     * Require authentication
     *
     * Returns 401 error if not authenticated
     *
     * @return object|null Employee if authenticated, sends 401 response if not
     */
    protected function requireAuth(): ?object
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            $this->response->setStatusCode(401);
            $this->response->setJSON([
                'success' => false,
                'error' => 'Não autenticado. Token inválido ou expirado.',
            ]);
            $this->response->send();
            exit;
        }

        return $employee;
    }

    /**
     * Require specific role
     *
     * Returns 403 error if employee doesn't have the required role
     *
     * @param string $role Required role (admin, manager, employee)
     * @return object|null Employee if authorized, sends 403 response if not
     */
    protected function requireRole(string $role): ?object
    {
        $employee = $this->requireAuth();

        if ($employee->role !== $role) {
            $this->response->setStatusCode(403);
            $this->response->setJSON([
                'success' => false,
                'error' => 'Acesso negado. Permissão insuficiente.',
            ]);
            $this->response->send();
            exit;
        }

        return $employee;
    }

    /**
     * Check if employee has admin or manager role
     *
     * @param object $employee Employee object
     * @return bool
     */
    protected function isManager(object $employee): bool
    {
        return in_array($employee->role, ['admin', 'manager', 'gestor']);
    }

    /**
     * Standard success response
     *
     * @param mixed $data Response data
     * @param string|null $message Optional success message
     * @param int $statusCode HTTP status code (default: 200)
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    protected function respondSuccess($data = null, ?string $message = null, int $statusCode = 200)
    {
        $response = ['success' => true];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return $this->respond($response, $statusCode);
    }

    /**
     * Standard error response
     *
     * @param string $message Error message
     * @param mixed $errors Optional error details
     * @param int $statusCode HTTP status code (default: 400)
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    protected function respondError(string $message, $errors = null, int $statusCode = 400)
    {
        $response = [
            'success' => false,
            'error' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return $this->respond($response, $statusCode);
    }
}
