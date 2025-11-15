<?php

namespace App\Controllers\Biometric;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;
use App\Models\BiometricTemplateModel;
use App\Models\UserConsentModel;
use App\Models\SettingModel;

class FaceRecognitionController extends BaseController
{
    protected $employeeModel;
    protected $biometricModel;
    protected $consentModel;
    protected $settingModel;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->biometricModel = new BiometricTemplateModel();
        $this->consentModel = new UserConsentModel();
        $this->settingModel = new SettingModel();
    }

    /**
     * Display biometric enrollment page
     */
    public function index()
    {
        $this->requireAuth();

        // Check if user has granted biometric consent
        $hasConsent = $this->consentModel->hasConsent($this->currentUser->id, 'biometric_data');

        // Get existing biometric templates
        $faceTemplates = $this->biometricModel
            ->where('employee_id', $this->currentUser->id)
            ->where('biometric_type', 'face')
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $fingerprintTemplates = $this->biometricModel
            ->where('employee_id', $this->currentUser->id)
            ->where('biometric_type', 'fingerprint')
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $data = [
            'hasConsent' => $hasConsent,
            'faceTemplates' => $faceTemplates,
            'fingerprintTemplates' => $fingerprintTemplates,
            'currentUser' => $this->currentUser,
        ];

        return view('biometric/enrollment', $data);
    }

    /**
     * Enroll face biometric
     */
    public function enrollFace()
    {
        $this->requireAuth();

        // Check consent
        if (!$this->consentModel->hasConsent($this->currentUser->id, 'biometric_data')) {
            return $this->respondError('Você precisa consentir com o uso de dados biométricos.', null, 403);
        }

        // Validate input
        $rules = [
            'photo' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->respondError('Foto é obrigatória.', $this->validator->getErrors(), 400);
        }

        $photoBase64 = $this->request->getPost('photo');

        // Call DeepFace API for enrollment
        $deepfaceUrl = $this->settingModel->get('deepface_api_url', 'http://localhost:5000');

        try {
            $client = \Config\Services::curlrequest();

            $response = $client->post($deepfaceUrl . '/enroll', [
                'json' => [
                    'employee_id' => $this->currentUser->id,
                    'photo' => $photoBase64,
                ],
                'timeout' => 15,
            ]);

            $result = json_decode($response->getBody(), true);

            if (!$result['success']) {
                return $this->respondError($result['error'] ?? 'Erro ao processar imagem.', null, 400);
            }

            // Save biometric template
            $templateData = [
                'employee_id'        => $this->currentUser->id,
                'biometric_type'     => 'face',
                'template_data'      => null, // DeepFace stores the image file
                'file_path'          => $result['face_path'],
                'image_hash'         => $result['image_hash'],
                'enrollment_quality' => $result['confidence'],
                'model_used'         => $this->settingModel->get('deepface_model', 'VGG-Face'),
                'active'             => true,
            ];

            $templateId = $this->biometricModel->insert($templateData);

            if (!$templateId) {
                return $this->respondError('Erro ao salvar template biométrico.', null, 500);
            }

            // Update employee record
            $this->employeeModel->update($this->currentUser->id, [
                'has_face_biometric' => true,
            ]);

            // Log enrollment
            $this->logAudit(
                'BIOMETRIC_ENROLLED',
                'biometric_templates',
                $templateId,
                null,
                ['type' => 'face', 'quality' => $result['confidence']],
                'Cadastro de biometria facial concluído'
            );

            return $this->respondSuccess([
                'template_id' => $templateId,
                'quality' => $result['confidence'],
                'facial_area' => $result['facial_area'],
            ], 'Biometria facial cadastrada com sucesso!', 201);

        } catch (\Exception $e) {
            log_message('error', 'DeepFace enrollment error: ' . $e->getMessage());

            return $this->respondError('Erro ao conectar com serviço de reconhecimento facial.', null, 500);
        }
    }

    /**
     * Enroll fingerprint biometric
     */
    public function enrollFingerprint()
    {
        $this->requireAuth();

        // Check consent
        if (!$this->consentModel->hasConsent($this->currentUser->id, 'biometric_data')) {
            return $this->respondError('Você precisa consentir com o uso de dados biométricos.', null, 403);
        }

        // Validate input
        $rules = [
            'template' => 'required',
            'finger'   => 'required|in_list[right_thumb,right_index,left_thumb,left_index]',
        ];

        if (!$this->validate($rules)) {
            return $this->respondError('Dados inválidos.', $this->validator->getErrors(), 400);
        }

        $template = $this->request->getPost('template');
        $finger = $this->request->getPost('finger');

        // Encrypt template data
        $encryptedTemplate = $this->encryptTemplate($template);

        // Save biometric template
        $templateData = [
            'employee_id'        => $this->currentUser->id,
            'biometric_type'     => 'fingerprint',
            'template_data'      => $encryptedTemplate,
            'metadata'           => json_encode(['finger' => $finger]),
            'enrollment_quality' => $this->request->getPost('quality', 0.85),
            'active'             => true,
        ];

        $templateId = $this->biometricModel->insert($templateData);

        if (!$templateId) {
            return $this->respondError('Erro ao salvar template biométrico.', null, 500);
        }

        // Update employee record
        $this->employeeModel->update($this->currentUser->id, [
            'has_fingerprint_biometric' => true,
        ]);

        // Log enrollment
        $this->logAudit(
            'BIOMETRIC_ENROLLED',
            'biometric_templates',
            $templateId,
            null,
            ['type' => 'fingerprint', 'finger' => $finger],
            "Cadastro de biometria digital concluído: {$finger}"
        );

        return $this->respondSuccess([
            'template_id' => $templateId,
            'finger' => $finger,
        ], 'Biometria digital cadastrada com sucesso!', 201);
    }

    /**
     * Delete biometric template
     */
    public function deleteTemplate(int $templateId)
    {
        $this->requireAuth();

        $template = $this->biometricModel->find($templateId);

        if (!$template) {
            return $this->respondError('Template não encontrado.', null, 404);
        }

        // Check ownership or admin
        if ($template->employee_id !== $this->currentUser->id && !$this->hasRole('admin')) {
            return $this->respondError('Você não tem permissão para excluir este template.', null, 403);
        }

        // Delete file if exists
        if ($template->file_path && file_exists($template->file_path)) {
            unlink($template->file_path);
        }

        // Delete template
        $this->biometricModel->delete($templateId);

        // Check if employee still has other templates of same type
        $remainingTemplates = $this->biometricModel
            ->where('employee_id', $template->employee_id)
            ->where('biometric_type', $template->biometric_type)
            ->where('active', true)
            ->countAllResults();

        // Update employee record if no more templates
        if ($remainingTemplates === 0) {
            $updateData = $template->biometric_type === 'face'
                ? ['has_face_biometric' => false]
                : ['has_fingerprint_biometric' => false];

            $this->employeeModel->update($template->employee_id, $updateData);
        }

        // Log deletion
        $this->logAudit(
            'BIOMETRIC_DELETED',
            'biometric_templates',
            $templateId,
            ['type' => $template->biometric_type],
            null,
            "Template biométrico excluído: {$template->biometric_type}"
        );

        return $this->respondSuccess(null, 'Template biométrico excluído com sucesso.');
    }

    /**
     * Grant biometric consent
     */
    public function grantConsent()
    {
        $this->requireAuth();

        // Check if already has consent
        if ($this->consentModel->hasConsent($this->currentUser->id, 'biometric_data')) {
            return $this->respondError('Você já consentiu com o uso de dados biométricos.', null, 400);
        }

        // Grant consent
        $consentData = [
            'employee_id'   => $this->currentUser->id,
            'consent_type'  => 'biometric_data',
            'purpose'       => 'Registro de ponto eletrônico através de reconhecimento facial e biometria',
            'legal_basis'   => 'Consentimento (Art. 7º, I da LGPD)',
            'granted'       => true,
            'granted_at'    => date('Y-m-d H:i:s'),
            'ip_address'    => $this->getClientIp(),
            'consent_text'  => 'Autorizo o tratamento de meus dados biométricos (facial e digital) para fins de registro de ponto eletrônico.',
            'version'       => '1.0',
        ];

        $consentId = $this->consentModel->insert($consentData);

        if (!$consentId) {
            return $this->respondError('Erro ao registrar consentimento.', null, 500);
        }

        // Log consent
        $this->logAudit(
            'CONSENT_GRANTED',
            'user_consents',
            $consentId,
            null,
            ['consent_type' => 'biometric_data'],
            'Consentimento para dados biométricos concedido'
        );

        return $this->respondSuccess(null, 'Consentimento registrado com sucesso!', 201);
    }

    /**
     * Revoke biometric consent
     */
    public function revokeConsent()
    {
        $this->requireAuth();

        // Revoke consent
        $revoked = $this->consentModel->revoke($this->currentUser->id, 'biometric_data');

        if (!$revoked) {
            return $this->respondError('Erro ao revogar consentimento.', null, 500);
        }

        // Deactivate all biometric templates
        $this->biometricModel
            ->where('employee_id', $this->currentUser->id)
            ->set(['active' => false])
            ->update();

        // Update employee record
        $this->employeeModel->update($this->currentUser->id, [
            'has_face_biometric' => false,
            'has_fingerprint_biometric' => false,
        ]);

        // Log revocation
        $this->logAudit(
            'CONSENT_REVOKED',
            'user_consents',
            null,
            null,
            ['consent_type' => 'biometric_data'],
            'Consentimento para dados biométricos revogado - templates desativados'
        );

        return $this->respondSuccess(null, 'Consentimento revogado. Seus dados biométricos foram desativados.');
    }

    /**
     * Test facial recognition
     */
    public function testRecognition()
    {
        $this->requireAuth();

        // Validate input
        $rules = [
            'photo' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->respondError('Foto é obrigatória.', $this->validator->getErrors(), 400);
        }

        $photoBase64 = $this->request->getPost('photo');

        // Call DeepFace API for recognition
        $deepfaceUrl = $this->settingModel->get('deepface_api_url', 'http://localhost:5000');
        $threshold = $this->settingModel->get('deepface_threshold', 0.40);

        try {
            $client = \Config\Services::curlrequest();

            $response = $client->post($deepfaceUrl . '/recognize', [
                'json' => [
                    'photo' => $photoBase64,
                    'threshold' => $threshold,
                ],
                'timeout' => 10,
            ]);

            $result = json_decode($response->getBody(), true);

            if (!$result['success']) {
                return $this->respondError('Erro ao processar reconhecimento.', null, 500);
            }

            if (!$result['recognized']) {
                return $this->respondSuccess([
                    'recognized' => false,
                ], 'Rosto não reconhecido.');
            }

            $recognizedEmployeeId = (int) $result['employee_id'];
            $isCurrentUser = $recognizedEmployeeId === $this->currentUser->id;

            return $this->respondSuccess([
                'recognized' => true,
                'is_current_user' => $isCurrentUser,
                'similarity' => $result['similarity'],
                'distance' => $result['distance'],
            ], $isCurrentUser ? 'Reconhecimento bem-sucedido!' : 'Reconhecido como outro usuário.');

        } catch (\Exception $e) {
            log_message('error', 'DeepFace recognition test error: ' . $e->getMessage());

            return $this->respondError('Erro ao conectar com serviço de reconhecimento facial.', null, 500);
        }
    }

    /**
     * Manage biometrics (admin/manager)
     */
    public function manage()
    {
        $this->requireManager();

        $employeeId = $this->request->getGet('employee_id');

        if ($employeeId) {
            // Get specific employee's biometrics
            $employee = $this->employeeModel->find($employeeId);

            if (!$employee) {
                $this->setError('Funcionário não encontrado.');
                return redirect()->back();
            }

            // Check department access for managers
            if ($this->hasRole('gestor') && $employee->department !== $this->currentUser->department) {
                $this->setError('Você não tem permissão para gerenciar este funcionário.');
                return redirect()->back();
            }

            $templates = $this->biometricModel
                ->where('employee_id', $employeeId)
                ->orderBy('created_at', 'DESC')
                ->findAll();

            $data = [
                'employee' => $employee,
                'templates' => $templates,
            ];

            return view('biometric/manage_employee', $data);
        }

        // List all employees with biometric status
        $query = $this->employeeModel->where('active', true);

        if ($this->hasRole('gestor')) {
            $query->where('department', $this->currentUser->department);
        }

        $employees = $query->findAll();

        $data = [
            'employees' => $employees,
        ];

        return view('biometric/manage', $data);
    }

    /**
     * Encrypt biometric template
     */
    protected function encryptTemplate(string $template): string
    {
        $encrypter = \Config\Services::encrypter();
        return base64_encode($encrypter->encrypt($template));
    }

    /**
     * Decrypt biometric template
     */
    protected function decryptTemplate(string $encryptedTemplate): string
    {
        $encrypter = \Config\Services::encrypter();
        return $encrypter->decrypt(base64_decode($encryptedTemplate));
    }
}
