<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Security Headers Filter
 *
 * Adiciona headers de segurança em todas as respostas HTTP
 */
class SecurityHeadersFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // X-Frame-Options: Previne clickjacking
        $response->setHeader('X-Frame-Options', 'DENY');

        // X-Content-Type-Options: Previne MIME-type sniffing
        $response->setHeader('X-Content-Type-Options', 'nosniff');

        // X-XSS-Protection: Proteção XSS
        $response->setHeader('X-XSS-Protection', '1; mode=block');

        // Referrer-Policy
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions-Policy
        $permissionsPolicy = [
            'geolocation=(self)',
            'microphone=()',
            'camera=()',
            'payment=()',
            'usb=()',
        ];
        $response->setHeader('Permissions-Policy', implode(', ', $permissionsPolicy));

        // HSTS - Força HTTPS
        if ($request->isSecure() || ENVIRONMENT === 'production') {
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
