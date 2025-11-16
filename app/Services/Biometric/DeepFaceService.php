<?php

namespace App\Services\Biometric;

use App\Models\SettingModel;
use App\Models\BiometricTemplateModel;
use App\Models\EmployeeModel;

/**
 * DeepFace Service
 *
 * Handles communication with DeepFace Python API for facial recognition
 */
class DeepFaceService
{
    protected $settingModel;
    protected $biometricModel;
    protected $employeeModel;
    protected $apiUrl;
    protected $timeout;

    public function __construct()
    {
        $this->settingModel = new SettingModel();
        $this->biometricModel = new BiometricTemplateModel();
        $this->employeeModel = new EmployeeModel();

        // Load API configuration
        $this->apiUrl = $this->settingModel->get('deepface_api_url', 'http://localhost:5000');
        $this->timeout = (int) $this->settingModel->get('deepface_timeout', 15);
    }

    /**
     * Check if DeepFace API is healthy
     *
     * @return array
     */
    public function healthCheck(): array
    {
        try {
            $client = \Config\Services::curlrequest();

            $response = $client->get($this->apiUrl . '/health', [
                'timeout' => 5,
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody(), true);

            return [
                'success' => $statusCode === 200,
                'status' => $body['status'] ?? 'unknown',
                'version' => $body['version'] ?? null,
                'models_loaded' => $body['models_loaded'] ?? false,
            ];
        } catch (\Exception $e) {
            log_message('error', 'DeepFace health check failed: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Não foi possível conectar ao serviço de reconhecimento facial.',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Enroll a face for an employee
     *
     * @param int $employeeId
     * @param string $photoBase64
     * @return array
     */
    public function enrollFace(int $employeeId, string $photoBase64): array
    {
        try {
            // Validate employee exists
            $employee = $this->employeeModel->find($employeeId);

            if (!$employee) {
                return [
                    'success' => false,
                    'error' => 'Funcionário não encontrado.',
                ];
            }

            // Call DeepFace API
            $client = \Config\Services::curlrequest();

            $response = $client->post($this->apiUrl . '/enroll', [
                'json' => [
                    'employee_id' => $employeeId,
                    'photo' => $photoBase64,
                ],
                'timeout' => $this->timeout,
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $result = json_decode($response->getBody(), true);

            if ($statusCode !== 200 && $statusCode !== 201) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Erro ao processar imagem.',
                    'details' => $result['details'] ?? null,
                ];
            }

            if (!$result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Falha no cadastro facial.',
                ];
            }

            return [
                'success' => true,
                'face_path' => $result['face_path'],
                'image_hash' => $result['image_hash'],
                'confidence' => $result['confidence'] ?? 0.95,
                'facial_area' => $result['facial_area'] ?? null,
            ];

        } catch (\Exception $e) {
            log_message('error', 'DeepFace enrollment error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Erro ao conectar com serviço de reconhecimento facial.',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Recognize a face and return matching employee
     *
     * @param string $photoBase64
     * @param float|null $customThreshold
     * @return array
     */
    public function recognizeFace(string $photoBase64, ?float $customThreshold = null): array
    {
        try {
            // Get threshold from settings or use custom
            $threshold = $customThreshold ?? $this->settingModel->get('deepface_threshold', 0.40);

            // Call DeepFace API
            $client = \Config\Services::curlrequest();

            $response = $client->post($this->apiUrl . '/recognize', [
                'json' => [
                    'photo' => $photoBase64,
                    'threshold' => $threshold,
                ],
                'timeout' => $this->timeout,
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $result = json_decode($response->getBody(), true);

            if ($statusCode !== 200) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Erro ao processar imagem.',
                    'details' => $result['details'] ?? null,
                ];
            }

            if (!$result['success']) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Falha no reconhecimento.',
                ];
            }

            if (!$result['recognized']) {
                return [
                    'success' => true,
                    'recognized' => false,
                    'message' => 'Nenhum rosto reconhecido.',
                ];
            }

            // Get employee data
            $employee = $this->employeeModel->find($result['employee_id']);

            return [
                'success' => true,
                'recognized' => true,
                'employee_id' => $result['employee_id'],
                'employee' => $employee,
                'similarity' => $result['similarity'],
                'distance' => $result['distance'],
                'model' => $result['model'] ?? 'VGG-Face',
                'threshold_used' => $threshold,
            ];

        } catch (\Exception $e) {
            log_message('error', 'DeepFace recognition error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Erro ao conectar com serviço de reconhecimento facial.',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify if a face matches a specific employee
     *
     * @param int $employeeId
     * @param string $photoBase64
     * @return array
     */
    public function verifyFace(int $employeeId, string $photoBase64): array
    {
        try {
            // Get employee's face template
            $template = $this->biometricModel
                ->where('employee_id', $employeeId)
                ->where('biometric_type', 'face')
                ->where('active', true)
                ->orderBy('created_at', 'DESC')
                ->first();

            if (!$template) {
                return [
                    'success' => false,
                    'error' => 'Funcionário não possui cadastro facial.',
                ];
            }

            // Call DeepFace API
            $client = \Config\Services::curlrequest();

            $response = $client->post($this->apiUrl . '/verify', [
                'json' => [
                    'employee_id' => $employeeId,
                    'photo' => $photoBase64,
                ],
                'timeout' => $this->timeout,
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $result = json_decode($response->getBody(), true);

            if ($statusCode !== 200) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Erro ao processar imagem.',
                ];
            }

            return [
                'success' => true,
                'verified' => $result['verified'] ?? false,
                'similarity' => $result['similarity'] ?? 0,
                'distance' => $result['distance'] ?? 1.0,
                'threshold' => $result['threshold'] ?? 0.40,
            ];

        } catch (\Exception $e) {
            log_message('error', 'DeepFace verification error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Erro ao conectar com serviço de reconhecimento facial.',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Analyze facial attributes (age, gender, emotion, race)
     *
     * @param string $photoBase64
     * @return array
     */
    public function analyzeFace(string $photoBase64): array
    {
        try {
            // Call DeepFace API
            $client = \Config\Services::curlrequest();

            $response = $client->post($this->apiUrl . '/analyze', [
                'json' => [
                    'photo' => $photoBase64,
                ],
                'timeout' => $this->timeout,
                'http_errors' => false,
            ]);

            $statusCode = $response->getStatusCode();
            $result = json_decode($response->getBody(), true);

            if ($statusCode !== 200) {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Erro ao processar imagem.',
                ];
            }

            return [
                'success' => true,
                'analysis' => $result['analysis'] ?? [],
            ];

        } catch (\Exception $e) {
            log_message('error', 'DeepFace analysis error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Erro ao conectar com serviço de reconhecimento facial.',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete face enrollment for an employee
     *
     * @param int $employeeId
     * @return array
     */
    public function deleteFaceEnrollment(int $employeeId): array
    {
        try {
            // Get employee's face templates
            $templates = $this->biometricModel
                ->where('employee_id', $employeeId)
                ->where('biometric_type', 'face')
                ->findAll();

            if (empty($templates)) {
                return [
                    'success' => true,
                    'message' => 'Nenhum cadastro facial encontrado.',
                ];
            }

            // Delete face files
            $deletedCount = 0;
            foreach ($templates as $template) {
                if ($template->file_path && file_exists($template->file_path)) {
                    if (unlink($template->file_path)) {
                        $deletedCount++;
                    }
                }
            }

            // Delete database records
            $this->biometricModel
                ->where('employee_id', $employeeId)
                ->where('biometric_type', 'face')
                ->delete();

            // Update employee record
            $this->employeeModel->update($employeeId, [
                'has_face_biometric' => false,
            ]);

            return [
                'success' => true,
                'deleted_files' => $deletedCount,
                'deleted_records' => count($templates),
            ];

        } catch (\Exception $e) {
            log_message('error', 'DeepFace delete enrollment error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Erro ao excluir cadastro facial.',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get DeepFace API statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        // Count enrolled faces
        $enrolledCount = $this->biometricModel
            ->where('biometric_type', 'face')
            ->where('active', true)
            ->countAllResults();

        // Count employees without face
        $withoutFaceCount = $this->employeeModel
            ->where('active', true)
            ->where('has_face_biometric', false)
            ->countAllResults();

        // Get API health
        $health = $this->healthCheck();

        return [
            'enrolled_faces' => $enrolledCount,
            'employees_without_face' => $withoutFaceCount,
            'api_status' => $health['success'] ? 'online' : 'offline',
            'api_url' => $this->apiUrl,
            'model' => $this->settingModel->get('deepface_model', 'VGG-Face'),
            'threshold' => $this->settingModel->get('deepface_threshold', 0.40),
        ];
    }

    /**
     * Validate base64 image
     *
     * @param string $base64
     * @return array
     */
    public function validateImage(string $base64): array
    {
        try {
            // Remove data URI prefix if present
            if (strpos($base64, 'data:image') === 0) {
                $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
            }

            // Decode base64
            $imageData = base64_decode($base64, true);

            if ($imageData === false) {
                return [
                    'valid' => false,
                    'error' => 'Base64 inválido.',
                ];
            }

            // Check file size (max 5MB)
            $size = strlen($imageData);
            if ($size > 5 * 1024 * 1024) {
                return [
                    'valid' => false,
                    'error' => 'Imagem muito grande. Máximo: 5MB.',
                ];
            }

            // Verify it's a valid image
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageData);

            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];

            if (!in_array($mimeType, $allowedTypes)) {
                return [
                    'valid' => false,
                    'error' => 'Formato de imagem inválido. Use JPG ou PNG.',
                ];
            }

            return [
                'valid' => true,
                'size' => $size,
                'mime_type' => $mimeType,
            ];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => 'Erro ao validar imagem.',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get available models
     *
     * @return array
     */
    public function getAvailableModels(): array
    {
        return [
            'VGG-Face' => [
                'name' => 'VGG-Face',
                'accuracy' => 99.65,
                'threshold' => 0.40,
                'recommended' => true,
            ],
            'Facenet' => [
                'name' => 'Facenet',
                'accuracy' => 99.65,
                'threshold' => 0.40,
                'recommended' => true,
            ],
            'Facenet512' => [
                'name' => 'Facenet512',
                'accuracy' => 99.65,
                'threshold' => 0.30,
                'recommended' => false,
            ],
            'OpenFace' => [
                'name' => 'OpenFace',
                'accuracy' => 92.92,
                'threshold' => 0.10,
                'recommended' => false,
            ],
            'DeepFace' => [
                'name' => 'DeepFace',
                'accuracy' => 97.35,
                'threshold' => 0.23,
                'recommended' => false,
            ],
            'DeepID' => [
                'name' => 'DeepID',
                'accuracy' => 97.45,
                'threshold' => 0.015,
                'recommended' => false,
            ],
            'ArcFace' => [
                'name' => 'ArcFace',
                'accuracy' => 99.41,
                'threshold' => 0.68,
                'recommended' => true,
            ],
            'Dlib' => [
                'name' => 'Dlib',
                'accuracy' => 99.38,
                'threshold' => 0.07,
                'recommended' => false,
            ],
        ];
    }
}
