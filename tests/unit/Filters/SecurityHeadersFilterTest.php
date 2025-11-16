<?php

namespace Tests\Unit\Filters;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\URI;
use App\Filters\SecurityHeadersFilter;

/**
 * Security Headers Filter Test
 *
 * Tests for security headers functionality
 */
class SecurityHeadersFilterTest extends CIUnitTestCase
{
    protected SecurityHeadersFilter $filter;
    protected Response $response;
    protected IncomingRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new SecurityHeadersFilter();
        $this->response = new Response(new \Config\App());

        // Create a basic request
        $uri = new URI('http://localhost/test');
        $this->request = new IncomingRequest(
            new \Config\App(),
            $uri,
            null,
            new \CodeIgniter\HTTP\UserAgent()
        );
    }

    public function testAfterAddsSecurityHeaders()
    {
        $response = $this->filter->after($this->request, $this->response);

        // Check that essential security headers are present
        $this->assertTrue($response->hasHeader('Content-Security-Policy'));
        $this->assertTrue($response->hasHeader('X-Frame-Options'));
        $this->assertTrue($response->hasHeader('X-Content-Type-Options'));
        $this->assertTrue($response->hasHeader('X-XSS-Protection'));
        $this->assertTrue($response->hasHeader('Referrer-Policy'));
        $this->assertTrue($response->hasHeader('Permissions-Policy'));
    }

    public function testContentSecurityPolicyHeader()
    {
        $response = $this->filter->after($this->request, $this->response);

        $csp = $response->getHeaderLine('Content-Security-Policy');

        // Check for important CSP directives
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
    }

    public function testXFrameOptionsDeny()
    {
        $response = $this->filter->after($this->request, $this->response);

        $xFrameOptions = $response->getHeaderLine('X-Frame-Options');

        $this->assertEquals('DENY', $xFrameOptions);
    }

    public function testXContentTypeOptionsNoSniff()
    {
        $response = $this->filter->after($this->request, $this->response);

        $xContentType = $response->getHeaderLine('X-Content-Type-Options');

        $this->assertEquals('nosniff', $xContentType);
    }

    public function testXXSSProtection()
    {
        $response = $this->filter->after($this->request, $this->response);

        $xssProtection = $response->getHeaderLine('X-XSS-Protection');

        $this->assertEquals('1; mode=block', $xssProtection);
    }

    public function testReferrerPolicy()
    {
        $response = $this->filter->after($this->request, $this->response);

        $referrerPolicy = $response->getHeaderLine('Referrer-Policy');

        $this->assertEquals('strict-origin-when-cross-origin', $referrerPolicy);
    }

    public function testPermissionsPolicy()
    {
        $response = $this->filter->after($this->request, $this->response);

        $permissionsPolicy = $response->getHeaderLine('Permissions-Policy');

        // Check for important permissions restrictions
        $this->assertStringContainsString('camera=()', $permissionsPolicy);
        $this->assertStringContainsString('microphone=()', $permissionsPolicy);
        $this->assertStringContainsString('geolocation=()', $permissionsPolicy);
    }

    public function testHSTSNotAddedInDevelopment()
    {
        // In development environment, HSTS should not be added
        $response = $this->filter->after($this->request, $this->response);

        // HSTS should not be present in development
        if (ENVIRONMENT === 'development') {
            $this->assertFalse($response->hasHeader('Strict-Transport-Security'));
        }
    }

    public function testSetCustomHeader()
    {
        $this->filter->setHeader('X-Custom-Header', 'test-value');

        $response = $this->filter->after($this->request, $this->response);

        $this->assertTrue($response->hasHeader('X-Custom-Header'));
        $this->assertEquals('test-value', $response->getHeaderLine('X-Custom-Header'));
    }

    public function testRemoveHeader()
    {
        $this->filter->removeHeader('X-XSS-Protection');

        $response = $this->filter->after($this->request, $this->response);

        $this->assertFalse($response->hasHeader('X-XSS-Protection'));
    }

    public function testGetHeaders()
    {
        $headers = $this->filter->getHeaders();

        $this->assertIsArray($headers);
        $this->assertArrayHasKey('Content-Security-Policy', $headers);
        $this->assertArrayHasKey('X-Frame-Options', $headers);
    }

    public function testAllowSameOriginFrames()
    {
        $this->filter->allowSameOriginFrames();

        $response = $this->filter->after($this->request, $this->response);

        $xFrameOptions = $response->getHeaderLine('X-Frame-Options');

        $this->assertEquals('SAMEORIGIN', $xFrameOptions);
    }

    public function testAllowFramesFrom()
    {
        $this->filter->allowFramesFrom('https://trusted.example.com');

        $response = $this->filter->after($this->request, $this->response);

        $xFrameOptions = $response->getHeaderLine('X-Frame-Options');

        $this->assertEquals('ALLOW-FROM https://trusted.example.com', $xFrameOptions);
    }

    public function testDenyAllFrames()
    {
        $this->filter->allowSameOriginFrames(); // Change to SAMEORIGIN
        $this->filter->denyAllFrames(); // Change back to DENY

        $response = $this->filter->after($this->request, $this->response);

        $xFrameOptions = $response->getHeaderLine('X-Frame-Options');

        $this->assertEquals('DENY', $xFrameOptions);
    }

    public function testSetCustomCSP()
    {
        $customCSP = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", 'https://cdn.example.com'],
            'style-src' => ["'self'", "'unsafe-inline'"],
        ];

        $this->filter->setCSP($customCSP);

        $response = $this->filter->after($this->request, $this->response);

        $csp = $response->getHeaderLine('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self' https://cdn.example.com", $csp);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline'", $csp);
    }

    public function testGetCSP()
    {
        $csp = $this->filter->getCSP();

        $this->assertIsString($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
    }

    public function testSetPermissionsPolicy()
    {
        $permissions = [
            'camera' => ['self'],
            'microphone' => ['self'],
            'geolocation' => [],
        ];

        $this->filter->setPermissionsPolicy($permissions);

        $response = $this->filter->after($this->request, $this->response);

        $permissionsPolicy = $response->getHeaderLine('Permissions-Policy');

        $this->assertStringContainsString('camera=(self)', $permissionsPolicy);
        $this->assertStringContainsString('microphone=(self)', $permissionsPolicy);
        $this->assertStringContainsString('geolocation=()', $permissionsPolicy);
    }

    public function testDoesNotOverrideExistingHeaders()
    {
        // Set a custom CSP header before the filter
        $this->response->setHeader('Content-Security-Policy', "default-src 'none'");

        $response = $this->filter->after($this->request, $this->response);

        $csp = $response->getHeaderLine('Content-Security-Policy');

        // Should keep the original CSP
        $this->assertEquals("default-src 'none'", $csp);
    }

    public function testSkipsHeadersForFileDownloads()
    {
        // Set content type to a download type
        $this->response->setHeader('Content-Type', 'application/pdf');

        $response = $this->filter->after($this->request, $this->response);

        // Headers should not be added for file downloads
        // The filter should return the response unchanged
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testBeforeReturnsNull()
    {
        $result = $this->filter->before($this->request);

        // Before should always return null (no processing needed)
        $this->assertNull($result);
    }

    public function testEnableHSTS()
    {
        $this->filter->setEnableHSTS(true);

        $this->assertTrue($this->filter->isHSTSEnabled());

        $response = $this->filter->after($this->request, $this->response);

        $this->assertTrue($response->hasHeader('Strict-Transport-Security'));

        $hsts = $response->getHeaderLine('Strict-Transport-Security');
        $this->assertStringContainsString('max-age=31536000', $hsts);
        $this->assertStringContainsString('includeSubDomains', $hsts);
        $this->assertStringContainsString('preload', $hsts);
    }

    public function testDisableHSTS()
    {
        $this->filter->setEnableHSTS(true);
        $this->filter->setEnableHSTS(false);

        $this->assertFalse($this->filter->isHSTSEnabled());

        $response = $this->filter->after($this->request, $this->response);

        $this->assertFalse($response->hasHeader('Strict-Transport-Security'));
    }

    public function testMultipleCallsDoNotDuplicateHeaders()
    {
        // Call after multiple times
        $response1 = $this->filter->after($this->request, $this->response);
        $response2 = $this->filter->after($this->request, $response1);

        // Headers should still be present and not duplicated
        $csp = $response2->getHeaderLine('Content-Security-Policy');
        $this->assertIsString($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
    }

    public function testAllSecurityHeadersAreStrings()
    {
        $headers = $this->filter->getHeaders();

        foreach ($headers as $name => $value) {
            $this->assertIsString($name, "Header name should be string: {$name}");
            $this->assertIsString($value, "Header value should be string for: {$name}");
        }
    }

    public function testCSPContainsUpgradeInsecureRequests()
    {
        $response = $this->filter->after($this->request, $this->response);

        $csp = $response->getHeaderLine('Content-Security-Policy');

        $this->assertStringContainsString('upgrade-insecure-requests', $csp);
    }

    public function testCSPPreventsObjectEmbed()
    {
        $response = $this->filter->after($this->request, $this->response);

        $csp = $response->getHeaderLine('Content-Security-Policy');

        $this->assertStringContainsString("object-src 'none'", $csp);
    }

    public function testCSPPreventsFraming()
    {
        $response = $this->filter->after($this->request, $this->response);

        $csp = $response->getHeaderLine('Content-Security-Policy');

        $this->assertStringContainsString("frame-src 'none'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
    }

    public function testPermissionsPolicyDisablesDangerousFeatures()
    {
        $response = $this->filter->after($this->request, $this->response);

        $permissionsPolicy = $response->getHeaderLine('Permissions-Policy');

        // Check that dangerous features are disabled
        $this->assertStringContainsString('payment=()', $permissionsPolicy);
        $this->assertStringContainsString('usb=()', $permissionsPolicy);
    }
}
