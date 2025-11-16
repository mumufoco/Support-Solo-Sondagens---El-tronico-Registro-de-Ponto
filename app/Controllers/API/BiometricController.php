<?php

namespace App\Controllers\API;

use CodeIgniter\RESTful\ResourceController;
use App\Models\BiometricTemplateModel;
use App\Models\EmployeeModel;
use App\Models\UserConsentModel;
use App\Services\DeepFaceService;

/**
 * API Biometric Controller
 *
 * Handles biometric enrollment and management via API
 */
class BiometricController extends ResourceController
{
    protected $modelName = 'App\Models\BiometricTemplateModel';
    protected $format = 'json';

    protected $biometricModel;
    protected $employeeModel;
    protected $consentModel;
    protected $deepfaceService;

    public function __construct()
    {
        $this->biometricModel = new BiometricTemplateModel();
        $this->employeeModel = new EmployeeModel();
        $this->consentModel = new UserConsentModel();
        $this->deepfaceService = new DeepFaceService();
    }

    /**
     * Enroll face biometric
     * POST /api/biometric/enroll/face
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function enrollFace()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        // Check consent
        if (!$this->consentModel->hasConsent($employee->id, 'biometric_data')) {
            return $this->fail('Você precisa consentir com o uso de dados biométricos.', 403, [
                'consent_required' => true,
            ]);
        }

        // Validate input
        $rules = [
            'photo' => 'required|valid_base64_image|max_file_size[5242880]',
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors(), 400);
        }

        $photoBase64 = $this->request->getPost('photo');

        // Call DeepFace API for enrollment
        $result = $this->deepfaceService->enrollFace($employee->id, $photoBase64);

        if (!$result['success']) {
            return $this->fail($result['error'], 400, [
                'details' => $result['details'] ?? null,
            ]);
        }

        // Save biometric template
        // SECURITY: Store only hash/identifier, not physical file path
        // The file path should be managed by DeepFaceService internally
        $templateData = [
            'employee_id' => $employee->id,
            'biometric_type' => 'face',
            'template_data' => null,
            'file_path' => null, // Don't store physical path - security risk
            'image_hash' => $result['image_hash'], // Use hash as identifier
            'enrollment_quality' => $result['confidence'],
            'model_used' => 'VGG-Face',
            'active' => true,
        ];

        $templateId = $this->biometricModel->insert($templateData);

        if (!$templateId) {
            return $this->fail('Erro ao salvar template biométrico.', 500);
        }

        // Update employee record
        $this->employeeModel->update($employee->id, [
            'has_face_biometric' => true,
        ]);

        return $this->respondCreated([
            'success' => true,
            'message' => 'Biometria facial cadastrada com sucesso!',
            'data' => [
                'template_id' => $templateId,
                'quality' => $result['confidence'],
                'facial_area' => $result['facial_area'] ?? null,
            ],
        ]);
    }

    /**
     * Test face recognition
     * POST /api/biometric/test/face
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function testFace()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        // Validate input
        $rules = [
            'photo' => 'required|valid_base64_image|max_file_size[5242880]',
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors(), 400);
        }

        $photoBase64 = $this->request->getPost('photo');

        // Call DeepFace API for recognition
        $result = $this->deepfaceService->recognizeFace($photoBase64);

        if (!$result['success']) {
            return $this->fail($result['error'], 400);
        }

        if (!$result['recognized']) {
            return $this->respond([
                'success' => true,
                'data' => [
                    'recognized' => false,
                    'message' => 'Rosto não reconhecido.',
                ],
            ], 200);
        }

        $recognizedEmployeeId = $result['employee_id'];
        $isCurrentUser = $recognizedEmployeeId === $employee->id;

        return $this->respond([
            'success' => true,
            'data' => [
                'recognized' => true,
                'is_current_user' => $isCurrentUser,
                'similarity' => $result['similarity'],
                'distance' => $result['distance'],
                'message' => $isCurrentUser
                    ? 'Reconhecimento bem-sucedido!'
                    : 'Reconhecido como outro usuário.',
            ],
        ], 200);
    }

    /**
     * Delete face biometric
     * DELETE /api/biometric/face/{id}
     *
     * @param int $id
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function deleteFace($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        $template = $this->biometricModel->find($id);

        if (!$template) {
            return $this->fail('Template não encontrado.', 404);
        }

        // Check ownership
        if ($template->employee_id !== $employee->id) {
            return $this->fail('Acesso negado.', 403);
        }

        // Delete biometric data through DeepFace service
        // SECURITY: Don't use stored file_path - let service manage file deletion
        if ($template->image_hash) {
            $this->deepfaceService->deleteFaceByHash($template->image_hash);
        }

        // Delete template from database
        $this->biometricModel->delete($id);

        // Check if employee still has other face templates
        $remainingTemplates = $this->biometricModel
            ->where('employee_id', $employee->id)
            ->where('biometric_type', 'face')
            ->where('active', true)
            ->countAllResults();

        // Update employee record if no more templates
        if ($remainingTemplates === 0) {
            $this->employeeModel->update($employee->id, [
                'has_face_biometric' => false,
            ]);
        }

        return $this->respondDeleted([
            'success' => true,
            'message' => 'Template biométrico excluído com sucesso.',
        ]);
    }

    /**
     * List biometric templates
     * GET /api/biometric/templates
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function templates()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        $templates = $this->biometricModel
            ->where('employee_id', $employee->id)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        return $this->respond([
            'success' => true,
            'data' => array_map(function ($template) {
                return [
                    'id' => $template->id,
                    'biometric_type' => $template->biometric_type,
                    'enrollment_quality' => $template->enrollment_quality,
                    'model_used' => $template->model_used,
                    'active' => $template->active,
                    'created_at' => $template->created_at,
                ];
            }, $templates),
        ], 200);
    }

    /**
     * Grant biometric consent
     * POST /api/biometric/consent
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function grantConsent()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        // Check if already has consent
        if ($this->consentModel->hasConsent($employee->id, 'biometric_data')) {
            return $this->fail('Você já consentiu com o uso de dados biométricos.', 400);
        }

        // Grant consent
        $consentData = [
            'employee_id' => $employee->id,
            'consent_type' => 'biometric_data',
            'purpose' => 'Registro de ponto eletrônico através de reconhecimento facial e biometria',
            'legal_basis' => 'Consentimento (Art. 7º, I da LGPD)',
            'granted' => true,
            'granted_at' => date('Y-m-d H:i:s'),
            'ip_address' => get_client_ip(),
            'consent_text' => 'Autorizo o tratamento de meus dados biométricos (facial e digital) para fins de registro de ponto eletrônico.',
            'version' => '1.0',
        ];

        $consentId = $this->consentModel->insert($consentData);

        if (!$consentId) {
            return $this->fail('Erro ao registrar consentimento.', 500);
        }

        return $this->respondCreated([
            'success' => true,
            'message' => 'Consentimento registrado com sucesso!',
        ]);
    }

    /**
     * Revoke biometric consent
     * POST /api/biometric/revoke-consent
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function revokeConsent()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        // Revoke consent
        $revoked = $this->consentModel->revoke($employee->id, 'biometric_data');

        if (!$revoked) {
            return $this->fail('Erro ao revogar consentimento.', 500);
        }

        // Deactivate all biometric templates
        $this->biometricModel
            ->where('employee_id', $employee->id)
            ->set(['active' => false])
            ->update();

        // Update employee record
        $this->employeeModel->update($employee->id, [
            'has_face_biometric' => false,
            'has_fingerprint_biometric' => false,
        ]);

        return $this->respond([
            'success' => true,
            'message' => 'Consentimento revogado. Seus dados biométricos foram desativados.',
        ], 200);
    }

    /**
     * Check consent status
     * GET /api/biometric/consent/status
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function consentStatus()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        $hasConsent = $this->consentModel->hasConsent($employee->id, 'biometric_data');

        return $this->respond([
            'success' => true,
            'data' => [
                'has_consent' => $hasConsent,
            ],
        ], 200);
    }

    /**
     * Get authenticated employee from AuthController
     *
     * @return object|null
     */
    protected function getAuthenticatedEmployee(): ?object
    {
        $authController = new \App\Controllers\API\AuthController();
        return $authController->getAuthenticatedEmployee();
    }
}
