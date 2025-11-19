<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;
use App\Services\Biometric\DeepFaceService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * API Controller
 * 
 * Handles general API endpoints for validation, health checks, and DeepFace proxy
 */
class ApiController extends BaseController
{
    protected EmployeeModel $employeeModel;
    protected DeepFaceService $deepFaceService;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->deepFaceService = new DeepFaceService();
    }

    /**
     * Validate employee code
     * POST /api/validate-code
     * 
     * @return ResponseInterface
     */
    public function validateCode(): ResponseInterface
    {
        $code = $this->request->getJSON()->code ?? $this->request->getPost('code');

        if (empty($code)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Código não fornecido',
            ])->setStatusCode(400);
        }

        // Find employee by unique code
        $employee = $this->employeeModel
            ->where('unique_code', strtoupper($code))
            ->where('active', true)
            ->first();

        if (!$employee) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Código inválido ou funcionário inativo',
            ])->setStatusCode(404);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Código validado',
            'data' => [
                'employee_id' => $employee->id,
                'name' => $employee->name,
                'department' => $employee->department,
                'position' => $employee->position,
            ],
        ]);
    }

    /**
     * Health check endpoint
     * GET /api/health
     * 
     * @return ResponseInterface
     */
    public function health(): ResponseInterface
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'server' => [
                'php_version' => phpversion(),
                'environment' => ENVIRONMENT,
            ],
            'database' => [
                'connected' => false,
                'status' => 'not_configured',
            ],
            'services' => [
                'deepface' => $this->checkDeepFaceService(),
            ],
        ];

        // Check database connection
        try {
            $db = \Config\Database::connect();
            $health['database']['connected'] = $db->connID !== false;
            $health['database']['database'] = $db->database;

            // Check if database is actually working
            try {
                $db->query('SELECT 1');
                $health['database']['status'] = 'operational';
            } catch (\Exception $e) {
                $health['database']['status'] = 'error';
                $health['database']['error'] = $e->getMessage();
                $health['status'] = 'degraded';
            }
        } catch (\Exception $e) {
            $health['database']['status'] = 'error';
            $health['database']['error'] = 'Database not configured';
            $health['status'] = 'degraded';
        }

        $statusCode = $health['status'] === 'healthy' ? 200 : 503;

        return $this->response->setJSON($health)->setStatusCode($statusCode);
    }

    /**
     * DeepFace Enroll Proxy
     * POST /api/deepface/enroll
     * 
     * @return ResponseInterface
     */
    public function deepfaceEnroll(): ResponseInterface
    {
        try {
            $employeeId = $this->request->getJSON()->employee_id ?? $this->request->getPost('employee_id');
            $image = $this->request->getJSON()->image ?? $this->request->getPost('image');

            if (empty($employeeId) || empty($image)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Parâmetros insuficientes (employee_id, image)',
                ])->setStatusCode(400);
            }

            // Verify employee exists
            $employee = $this->employeeModel->find($employeeId);
            if (!$employee) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Funcionário não encontrado',
                ])->setStatusCode(404);
            }

            // Call DeepFace service to enroll face
            $result = $this->deepFaceService->enrollFace($employeeId, $image);

            if ($result['success']) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Face cadastrada com sucesso',
                    'data' => $result['data'],
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => $result['message'] ?? 'Erro ao cadastrar face',
                ])->setStatusCode(400);
            }
        } catch (\Exception $e) {
            log_message('error', '[API] DeepFace Enroll Error: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro interno ao processar cadastro facial',
                'error' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ])->setStatusCode(500);
        }
    }

    /**
     * DeepFace Recognition Proxy
     * POST /api/deepface/recognize
     * 
     * @return ResponseInterface
     */
    public function deepfaceRecognize(): ResponseInterface
    {
        try {
            $image = $this->request->getJSON()->image ?? $this->request->getPost('image');

            if (empty($image)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Imagem não fornecida',
                ])->setStatusCode(400);
            }

            // Call DeepFace service to recognize face
            $result = $this->deepFaceService->recognizeFace($image);

            if ($result['success']) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Face reconhecida',
                    'data' => [
                        'employee_id' => $result['employee_id'],
                        'confidence' => $result['confidence'],
                        'employee' => $result['employee'] ?? null,
                    ],
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => $result['message'] ?? 'Face não reconhecida',
                ])->setStatusCode(404);
            }
        } catch (\Exception $e) {
            log_message('error', '[API] DeepFace Recognize Error: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro interno ao processar reconhecimento facial',
                'error' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ])->setStatusCode(500);
        }
    }

    /**
     * Check if DeepFace service is available
     * 
     * @return string
     */
    private function checkDeepFaceService(): string
    {
        try {
            $deepFaceUrl = getenv('DEEPFACE_API_URL') ?: 'http://localhost:5000';
            
            $client = \Config\Services::curlrequest();
            $response = $client->get($deepFaceUrl . '/health', ['timeout' => 3]);

            if ($response->getStatusCode() === 200) {
                return 'operational';
            } else {
                return 'unavailable';
            }
        } catch (\Exception $e) {
            return 'unavailable';
        }
    }
}
