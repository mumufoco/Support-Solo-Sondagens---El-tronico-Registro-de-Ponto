<?php

namespace App\Controllers\API;

use CodeIgniter\RESTful\ResourceController;
use App\Services\Auth\OAuth2Service;
use App\Models\EmployeeModel;
use App\Services\Security\RateLimitService;

/**
 * OAuth 2.0 Controller
 *
 * Handles OAuth 2.0 token endpoints for mobile API authentication
 *
 * Endpoints:
 * - POST /api/oauth/token - Get access token (password grant)
 * - POST /api/oauth/refresh - Refresh access token
 * - POST /api/oauth/revoke - Revoke access token
 * - GET /api/oauth/tokens - List active tokens
 *
 * @package App\Controllers\API
 */
class OAuth2Controller extends ResourceController
{
    protected $modelName = 'App\Models\EmployeeModel';
    protected $format = 'json';

    protected OAuth2Service $oauth2Service;
    protected EmployeeModel $employeeModel;
    protected RateLimitService $rateLimitService;

    public function __construct()
    {
        $this->oauth2Service = new OAuth2Service();
        $this->employeeModel = new EmployeeModel();
        $this->rateLimitService = new RateLimitService();
    }

    /**
     * Get access token (Password Grant)
     *
     * POST /api/oauth/token
     *
     * Request body:
     * {
     *   "grant_type": "password",
     *   "username": "employee@example.com",
     *   "password": "password123",
     *   "scope": "api.read api.write" (optional)
     * }
     *
     * Response:
     * {
     *   "access_token": "...",
     *   "refresh_token": "...",
     *   "token_type": "Bearer",
     *   "expires_in": 3600,
     *   "scope": "api.read api.write"
     * }
     *
     * @return ResponseInterface
     */
    public function token()
    {
        // Apply rate limiting
        $ip = $this->request->getIPAddress();
        $limitInfo = $this->rateLimitService->attempt('oauth_token:' . $ip, 'login', $ip);

        if (!$limitInfo['allowed']) {
            return $this->failTooManyRequests(
                $this->rateLimitService->getErrorMessage($limitInfo)
            );
        }

        $grantType = $this->request->getPost('grant_type');

        if ($grantType === 'password') {
            return $this->passwordGrant();
        } elseif ($grantType === 'refresh_token') {
            return $this->refreshTokenGrant();
        } else {
            return $this->fail('Unsupported grant_type', 400);
        }
    }

    /**
     * Handle password grant
     *
     * @return ResponseInterface
     */
    protected function passwordGrant()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $scope = $this->request->getPost('scope');

        // Validate input
        if (!$username || !$password) {
            return $this->fail('Missing username or password', 400);
        }

        // Find employee by email
        $employee = $this->employeeModel->where('email', $username)->first();

        if (!$employee) {
            log_message('warning', "OAuth password grant failed: Invalid username {$username}");
            return $this->failUnauthorized('Invalid credentials');
        }

        // Verify password
        if (!password_verify($password, $employee->password)) {
            log_message('warning', "OAuth password grant failed: Invalid password for {$username}");
            return $this->failUnauthorized('Invalid credentials');
        }

        // Check if employee is active
        if (!$employee->active) {
            return $this->failUnauthorized('Account is inactive');
        }

        // Parse scopes
        $scopes = $scope ? explode(' ', $scope) : ['api.read', 'api.write'];

        // Generate device fingerprint
        $deviceFingerprint = OAuth2Service::generateDeviceFingerprint();

        // Generate tokens
        $tokens = $this->oauth2Service->generateTokens($employee->id, $deviceFingerprint, $scopes);

        log_message('info', "OAuth access token granted to employee ID: {$employee->id}");

        return $this->respond($tokens);
    }

    /**
     * Handle refresh token grant
     *
     * @return ResponseInterface
     */
    protected function refreshTokenGrant()
    {
        $refreshToken = $this->request->getPost('refresh_token');

        if (!$refreshToken) {
            return $this->fail('Missing refresh_token', 400);
        }

        // Generate device fingerprint
        $deviceFingerprint = OAuth2Service::generateDeviceFingerprint();

        // Refresh tokens
        $tokens = $this->oauth2Service->refreshAccessToken($refreshToken, $deviceFingerprint);

        if (!$tokens) {
            return $this->failUnauthorized('Invalid or expired refresh token');
        }

        return $this->respond($tokens);
    }

    /**
     * Refresh access token
     *
     * POST /api/oauth/refresh
     *
     * Request body:
     * {
     *   "refresh_token": "..."
     * }
     *
     * Response: Same as /token endpoint
     *
     * @return ResponseInterface
     */
    public function refresh()
    {
        // Apply rate limiting
        $ip = $this->request->getIPAddress();
        $limitInfo = $this->rateLimitService->attempt('oauth_refresh:' . $ip, 'api', $ip);

        if (!$limitInfo['allowed']) {
            return $this->failTooManyRequests(
                $this->rateLimitService->getErrorMessage($limitInfo)
            );
        }

        $refreshToken = $this->request->getPost('refresh_token') ?? $this->request->getJsonVar('refresh_token');

        if (!$refreshToken) {
            return $this->fail('Missing refresh_token', 400);
        }

        // Generate device fingerprint
        $deviceFingerprint = OAuth2Service::generateDeviceFingerprint();

        // Refresh tokens
        $tokens = $this->oauth2Service->refreshAccessToken($refreshToken, $deviceFingerprint);

        if (!$tokens) {
            return $this->failUnauthorized('Invalid or expired refresh token');
        }

        return $this->respond($tokens);
    }

    /**
     * Revoke access token
     *
     * POST /api/oauth/revoke
     *
     * Request body:
     * {
     *   "token": "access_token_here"
     * }
     *
     * Response:
     * {
     *   "success": true,
     *   "message": "Token revoked successfully"
     * }
     *
     * @return ResponseInterface
     */
    public function revoke()
    {
        $token = $this->request->getPost('token') ?? $this->request->getJsonVar('token');

        if (!$token) {
            return $this->fail('Missing token', 400);
        }

        // Validate token first
        $tokenData = $this->oauth2Service->validateAccessToken($token);

        if (!$tokenData) {
            return $this->failUnauthorized('Invalid token');
        }

        // Revoke the token
        $revoked = $this->oauth2Service->revokeAccessToken($tokenData['id']);

        if ($revoked) {
            log_message('info', "OAuth access token revoked: {$tokenData['id']}");

            return $this->respond([
                'success' => true,
                'message' => 'Token revoked successfully',
            ]);
        } else {
            return $this->fail('Failed to revoke token', 500);
        }
    }

    /**
     * List active tokens for current employee
     *
     * GET /api/oauth/tokens
     *
     * Requires authentication via Bearer token
     *
     * Response:
     * {
     *   "tokens": [
     *     {
     *       "id": 1,
     *       "device_fingerprint": "...",
     *       "scopes": ["api.read", "api.write"],
     *       "created_at": "2024-01-24 10:00:00",
     *       "last_used_at": "2024-01-24 12:30:00",
     *       "expires_at": "2024-01-24 13:00:00"
     *     }
     *   ]
     * }
     *
     * @return ResponseInterface
     */
    public function listTokens()
    {
        // Get employee ID from authenticated token
        $employeeId = $this->request->employeeId ?? null;

        if (!$employeeId) {
            return $this->failUnauthorized('Authentication required');
        }

        $tokens = $this->oauth2Service->getActiveTokens($employeeId);

        return $this->respond([
            'tokens' => $tokens,
        ]);
    }

    /**
     * Revoke all tokens for current employee
     *
     * POST /api/oauth/revoke-all
     *
     * Requires authentication via Bearer token
     *
     * Request body (optional):
     * {
     *   "except_current": true  // Keep current device token active
     * }
     *
     * Response:
     * {
     *   "success": true,
     *   "message": "5 tokens revoked",
     *   "count": 5
     * }
     *
     * @return ResponseInterface
     */
    public function revokeAll()
    {
        // Get employee ID from authenticated token
        $employeeId = $this->request->employeeId ?? null;
        $deviceFingerprint = $this->request->deviceFingerprint ?? null;

        if (!$employeeId) {
            return $this->failUnauthorized('Authentication required');
        }

        $exceptCurrent = $this->request->getPost('except_current') ?? $this->request->getJsonVar('except_current');

        // Revoke all tokens (except current device if requested)
        $count = $this->oauth2Service->revokeAllTokens(
            $employeeId,
            $exceptCurrent ? $deviceFingerprint : null
        );

        log_message('info', "Employee ID {$employeeId} revoked all tokens (count: {$count})");

        return $this->respond([
            'success' => true,
            'message' => "{$count} token(s) revoked",
            'count' => $count,
        ]);
    }
}
