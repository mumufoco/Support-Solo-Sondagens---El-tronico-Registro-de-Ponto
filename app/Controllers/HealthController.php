<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Health Check Controller
 *
 * Endpoint para monitoramento da saúde do sistema
 * Utilizado para verificações de uptime, monitoramento e CI/CD
 *
 * @author Claude (Anthropic)
 * @since 2025-11-18
 */
class HealthController extends BaseController
{
    /**
     * Endpoint principal de health check
     * GET /health
     *
     * @return ResponseInterface JSON com status do sistema
     */
    public function index(): ResponseInterface
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'writable' => $this->checkWritableDirectories(),
            'cache' => $this->checkCache(),
            'session' => $this->checkSession(),
            'environment' => $this->checkEnvironment(),
        ];

        $healthy = !in_array(false, $checks, true);

        $response = [
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'environment' => ENVIRONMENT,
            'version' => \CodeIgniter\CodeIgniter::CI_VERSION,
            'checks' => $checks,
        ];

        $statusCode = $healthy ? 200 : 503;

        return $this->response
            ->setJSON($response)
            ->setStatusCode($statusCode);
    }

    /**
     * Verifica conexão com banco de dados
     */
    private function checkDatabase(): array
    {
        // Check if using JSON database
        if (file_exists(ROOTPATH . 'writable/INSTALLED')) {
            return [
                'status' => 'ok',
                'driver' => 'JSON',
                'database' => 'File Storage',
                'info' => 'Using JSON file storage',
            ];
        }

        try {
            $db = \Config\Database::connect();

            // Tenta fazer uma query simples
            $db->query('SELECT 1');

            return [
                'status' => 'ok',
                'driver' => $db->DBDriver,
                'database' => $db->database,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica diretórios writable
     */
    private function checkWritableDirectories(): array
    {
        $directories = [
            'writable/cache' => WRITEPATH . 'cache',
            'writable/logs' => WRITEPATH . 'logs',
            'writable/session' => WRITEPATH . 'session',
            'writable/uploads' => WRITEPATH . 'uploads',
            'storage' => ROOTPATH . 'storage',
        ];

        $results = [];
        $allWritable = true;

        foreach ($directories as $name => $path) {
            $isWritable = is_dir($path) && is_writable($path);
            $results[$name] = $isWritable ? 'ok' : 'not writable';

            if (!$isWritable) {
                $allWritable = false;
            }
        }

        return [
            'status' => $allWritable ? 'ok' : 'error',
            'directories' => $results,
        ];
    }

    /**
     * Verifica sistema de cache
     */
    private function checkCache(): array
    {
        try {
            $cache = \Config\Services::cache();

            // Tenta salvar e recuperar um valor
            $testKey = 'health_check_' . time();
            $testValue = 'test';

            $cache->save($testKey, $testValue, 60);
            $retrieved = $cache->get($testKey);
            $cache->delete($testKey);

            if ($retrieved === $testValue) {
                return [
                    'status' => 'ok',
                    'handler' => get_class($cache),
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Cache read/write test failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica sistema de sessão
     */
    private function checkSession(): array
    {
        try {
            $session = \Config\Services::session();

            // Tenta salvar e recuperar um valor da sessão
            $testKey = 'health_check_' . time();
            $testValue = 'test';

            $session->set($testKey, $testValue);
            $retrieved = $session->get($testKey);
            $session->remove($testKey);

            if ($retrieved === $testValue) {
                return [
                    'status' => 'ok',
                    'driver' => config('Session')->driver,
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Session read/write test failed',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica configurações do ambiente
     */
    private function checkEnvironment(): array
    {
        $issues = [];

        // Verificar PHP version
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            $issues[] = 'PHP version should be >= 8.1.0 (current: ' . PHP_VERSION . ')';
        }

        // Verificar extensões críticas
        $requiredExtensions = ['mysqli', 'mbstring', 'intl', 'json', 'xml'];
        foreach ($requiredExtensions as $ext) {
            if (!extension_loaded($ext)) {
                $issues[] = "Missing PHP extension: {$ext}";
            }
        }

        // Verificar .env
        if (!file_exists(ROOTPATH . '.env')) {
            $issues[] = '.env file not found';
        }

        // Verificar encryption key
        if (empty(config('Encryption')->key)) {
            $issues[] = 'Encryption key not set';
        }

        return [
            'status' => empty($issues) ? 'ok' : 'warning',
            'php_version' => PHP_VERSION,
            'issues' => $issues,
        ];
    }

    /**
     * Endpoint detalhado para debugging
     * GET /health/detailed
     *
     * @return ResponseInterface
     */
    public function detailed(): ResponseInterface
    {
        // Apenas permitir em ambiente de desenvolvimento
        if (ENVIRONMENT === 'production') {
            return $this->response
                ->setJSON(['error' => 'Not available in production'])
                ->setStatusCode(403);
        }

        $info = [
            'system' => [
                'os' => php_uname(),
                'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'php_version' => PHP_VERSION,
                'codeigniter_version' => \CodeIgniter\CodeIgniter::CI_VERSION,
            ],
            'database' => $this->getDatabaseInfo(),
            'extensions' => get_loaded_extensions(),
            'config' => [
                'base_url' => config('App')->baseURL,
                'environment' => ENVIRONMENT,
                'timezone' => date_default_timezone_get(),
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
            ],
        ];

        return $this->response->setJSON($info);
    }

    /**
     * Obtém informações detalhadas do banco de dados
     */
    private function getDatabaseInfo(): array
    {
        try {
            $db = \Config\Database::connect();

            return [
                'driver' => $db->DBDriver,
                'database' => $db->database,
                'hostname' => $db->hostname,
                'port' => $db->port,
                'version' => $db->getVersion(),
                'connected' => $db->connID ? true : false,
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
}
