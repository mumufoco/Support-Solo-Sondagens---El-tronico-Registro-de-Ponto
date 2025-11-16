<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Services\Auth\OAuth2Service;

/**
 * OAuth 2.0 Authentication Filter
 *
 * Validates Bearer tokens for API endpoints
 *
 * Usage in app/Config/Filters.php:
 * - Add to $aliases: 'oauth2' => \App\Filters\OAuth2Filter::class
 * - Apply to API routes that require authentication
 *
 * Example:
 * $routes->group('api', ['filter' => 'oauth2'], function($routes) {
 *     $routes->get('profile', 'API\ProfileController::index');
 *     $routes->post('timesheet/punch', 'API\TimesheetController::punch');
 * });
 *
 * The filter will:
 * 1. Extract Bearer token from Authorization header
 * 2. Validate token with OAuth2Service
 * 3. Attach employee_id and token data to request
 * 4. Return 401 if token is invalid
 *
 * Access authenticated data in controllers:
 * - $this->request->employeeId
 * - $this->request->tokenData
 * - $this->request->deviceFingerprint
 *
 * @package App\Filters
 */
class OAuth2Filter implements FilterInterface
{
    /**
     * OAuth2 service
     * @var OAuth2Service
     */
    protected OAuth2Service $oauth2Service;

    /**
     * Endpoints that should bypass authentication
     *
     * @var array
     */
    protected array $publicEndpoints = [
        'api/oauth/token',
        'api/oauth/refresh',
        'api/docs',
        'api/health',
        'api/ping',
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->oauth2Service = new OAuth2Service();
    }

    /**
     * Validate Bearer token before processing request
     *
     * @param RequestInterface $request
     * @param array|null $arguments
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Get current path
        $uri = $request->getUri();
        $path = trim($uri->getPath(), '/');

        // Check if endpoint is public
        foreach ($this->publicEndpoints as $publicEndpoint) {
            if (strpos($path, $publicEndpoint) !== false) {
                return null;
            }
        }

        // Extract Bearer token from Authorization header
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader) {
            return $this->unauthorizedResponse('Missing Authorization header');
        }

        // Parse Bearer token
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $this->unauthorizedResponse('Invalid Authorization header format. Use: Bearer {token}');
        }

        $accessToken = $matches[1];

        if (!$accessToken) {
            return $this->unauthorizedResponse('Missing access token');
        }

        // Generate device fingerprint for validation
        $deviceFingerprint = OAuth2Service::generateDeviceFingerprint();

        // Validate access token
        $tokenData = $this->oauth2Service->validateAccessToken($accessToken, $deviceFingerprint);

        if (!$tokenData) {
            log_message('warning', "Invalid OAuth access token attempt from IP: {$request->getIPAddress()}");
            return $this->unauthorizedResponse('Invalid or expired access token');
        }

        // Check if specific scope is required (from arguments)
        if ($arguments && isset($arguments[0])) {
            $requiredScope = $arguments[0];

            if (!$this->oauth2Service->hasScope($tokenData, $requiredScope)) {
                log_message('warning', "Insufficient scope for employee ID: {$tokenData['employee_id']}, required: {$requiredScope}");
                return $this->forbiddenResponse("Insufficient permissions. Required scope: {$requiredScope}");
            }
        }

        // Attach employee ID and token data to request
        $request->employeeId = $tokenData['employee_id'];
        $request->tokenData = $tokenData;
        $request->deviceFingerprint = $deviceFingerprint;

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

    /**
     * Generate 401 Unauthorized response
     *
     * @param string $message Error message
     * @return ResponseInterface
     */
    protected function unauthorizedResponse(string $message): ResponseInterface
    {
        $response = service('response');

        $response->setStatusCode(401, 'Unauthorized');
        $response->setJSON([
            'error' => true,
            'message' => $message,
        ]);

        return $response;
    }

    /**
     * Generate 403 Forbidden response
     *
     * @param string $message Error message
     * @return ResponseInterface
     */
    protected function forbiddenResponse(string $message): ResponseInterface
    {
        $response = service('response');

        $response->setStatusCode(403, 'Forbidden');
        $response->setJSON([
            'error' => true,
            'message' => $message,
        ]);

        return $response;
    }

    /**
     * Add public endpoint
     *
     * @param string $endpoint
     * @return void
     */
    public function addPublicEndpoint(string $endpoint): void
    {
        if (!in_array($endpoint, $this->publicEndpoints, true)) {
            $this->publicEndpoints[] = $endpoint;
        }
    }

    /**
     * Get public endpoints
     *
     * @return array
     */
    public function getPublicEndpoints(): array
    {
        return $this->publicEndpoints;
    }
}
