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
        // SECURITY FIX: Enhanced security headers to prevent common web attacks

        // X-Frame-Options: Previne clickjacking
        $response->setHeader('X-Frame-Options', 'DENY');

        // X-Content-Type-Options: Previne MIME-type sniffing
        $response->setHeader('X-Content-Type-Options', 'nosniff');

        // X-XSS-Protection: Proteção XSS (legacy, mas ainda útil para navegadores antigos)
        $response->setHeader('X-XSS-Protection', '1; mode=block');

        // Referrer-Policy: Controla informações de referrer enviadas
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        // SECURITY FIX: Content Security Policy (CSP)
        // Prevents XSS, data injection, and other code injection attacks
        $cspDirectives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' ws: wss:",
            "media-src 'self'",
            "object-src 'none'",
            "frame-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "upgrade-insecure-requests",
        ];

        // Only add CSP in production or if explicitly enabled
        if (ENVIRONMENT === 'production' || env('ENABLE_CSP', false)) {
            $response->setHeader('Content-Security-Policy', implode('; ', $cspDirectives));
        } else {
            // In development, use report-only mode
            $response->setHeader('Content-Security-Policy-Report-Only', implode('; ', $cspDirectives));
        }

        // Permissions-Policy: Control browser features and APIs
        $permissionsPolicy = [
            'geolocation=(self)',
            'microphone=()',
            'camera=(self)',  // Allow camera for biometric
            'payment=()',
            'usb=()',
            'interest-cohort=()',  // Disable FLoC tracking
        ];
        $response->setHeader('Permissions-Policy', implode(', ', $permissionsPolicy));

        // SECURITY FIX: HSTS - Força HTTPS em produção
        // Prevents SSL stripping attacks and ensures all connections use HTTPS
        if ($request->isSecure() || ENVIRONMENT === 'production') {
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // SECURITY FIX: Remove server identification headers
        // Prevents fingerprinting and reduces information disclosure
        $response->removeHeader('Server');
        $response->removeHeader('X-Powered-By');

        return $response;
    }
}
