<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * CORS Filter
 *
 * Handles Cross-Origin Resource Sharing (CORS) for API endpoints
 */
class CorsFilter implements FilterInterface
{
    /**
     * Handle CORS preflight requests
     *
     * @param RequestInterface $request
     * @param array|null $arguments
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Handle preflight OPTIONS request
        if ($request->getMethod() === 'OPTIONS') {
            $response = service('response');
            $this->addCorsHeaders($response);

            // Return response immediately for preflight
            return $response
                ->setStatusCode(200)
                ->setBody('');
        }

        return null;
    }

    /**
     * Add CORS headers to response
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param array|null $arguments
     * @return ResponseInterface
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Add CORS headers to all responses
        $this->addCorsHeaders($response);

        return $response;
    }

    /**
     * Add CORS headers to response object
     *
     * @param ResponseInterface $response
     * @return void
     */
    protected function addCorsHeaders(ResponseInterface $response): void
    {
        // Get allowed origins from environment
        $allowedOrigins = $this->getAllowedOrigins();
        $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

        // Check if origin is allowed
        if (in_array($requestOrigin, $allowedOrigins) || in_array('*', $allowedOrigins)) {
            $response->setHeader('Access-Control-Allow-Origin', $requestOrigin ?: '*');
        }

        // Allow credentials (cookies, authorization headers)
        $response->setHeader('Access-Control-Allow-Credentials', 'true');

        // Allowed methods
        $response->setHeader(
            'Access-Control-Allow-Methods',
            'GET, POST, PUT, PATCH, DELETE, OPTIONS'
        );

        // Allowed headers
        $response->setHeader(
            'Access-Control-Allow-Headers',
            'Content-Type, Authorization, X-Requested-With, X-CSRF-TOKEN, Accept, Origin'
        );

        // Exposed headers (headers that browser can access)
        $response->setHeader(
            'Access-Control-Expose-Headers',
            'Content-Length, X-JSON'
        );

        // Cache preflight response for 24 hours
        $response->setHeader('Access-Control-Max-Age', '86400');
    }

    /**
     * Get allowed origins from configuration
     *
     * @return array
     */
    protected function getAllowedOrigins(): array
    {
        // Get from environment variable
        $originsEnv = env('CORS_ALLOWED_ORIGINS', '*');

        // If wildcard, return it
        if ($originsEnv === '*') {
            return ['*'];
        }

        // Split by comma and trim
        $origins = array_map('trim', explode(',', $originsEnv));

        // Default origins for development
        $defaultOrigins = [
            'http://localhost:3000',
            'http://localhost:8080',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:8080',
        ];

        // Merge with default origins in development
        if (ENVIRONMENT === 'development') {
            $origins = array_merge($origins, $defaultOrigins);
        }

        return array_unique($origins);
    }
}
