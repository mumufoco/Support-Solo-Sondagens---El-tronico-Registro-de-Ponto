<?php

namespace App\Controllers\Biometric;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;
use App\Models\BiometricTemplateModel;
use App\Models\UserConsentModel;
use App\Models\AuditLogModel;

/**
 * FingerprintController
 *
 * Gerencia o cadastro, teste e exclusão de templates de impressão digital.
 * Integração com SourceAFIS ou dispositivo leitor biométrico.
 */
class FingerprintController extends BaseController
{
    protected $employeeModel;
    protected $biometricModel;
    protected $consentModel;
    protected $auditModel;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->biometricModel = new BiometricTemplateModel();
        $this->consentModel = new UserConsentModel();
        $this->auditModel = new AuditLogModel();
    }

    /**
     * Display fingerprint enrollment form for employee
     * GET /fingerprint/enroll/{employee_id}
     */
    public function enroll($employeeId = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        // Check permissions
        if (!$employee || !in_array($employee['role'], ['admin', 'manager'])) {
            return redirect()->to('/dashboard')
                ->with('error', 'Acesso negado. Apenas administradores podem cadastrar biometrias.');
        }

        // Get employee data
        if ($employeeId === null) {
            return redirect()->to('/employees')
                ->with('error', 'ID do funcionário é obrigatório.');
        }

        $targetEmployee = $this->employeeModel->find($employeeId);

        if (!$targetEmployee) {
            return redirect()->to('/employees')
                ->with('error', 'Funcionário não encontrado.');
        }

        // Check if employee has granted consent
        $hasConsent = $this->consentModel
            ->where('employee_id', $employeeId)
            ->where('consent_type', 'biometric_fingerprint')
            ->where('granted', true)
            ->where('revoked_at', null)
            ->first();

        // Get existing fingerprint templates
        $existingTemplates = $this->biometricModel
            ->where('employee_id', $employeeId)
            ->where('biometric_type', 'fingerprint')
            ->where('active', true)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $data = [
            'employee' => $employee,
            'targetEmployee' => $targetEmployee,
            'hasConsent' => $hasConsent !== null,
            'existingTemplates' => $existingTemplates,
            'title' => 'Cadastro de Impressão Digital',
        ];

        return view('biometric/enroll_fingerprint', $data);
    }

    /**
     * Store fingerprint template
     * POST /fingerprint/enroll
     */
    public function store()
    {
        $employee = $this->getAuthenticatedEmployee();

        // Check permissions
        if (!$employee || !in_array($employee['role'], ['admin', 'manager'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso negado',
            ])->setStatusCode(403);
        }

        // Validate input
        $rules = [
            'employee_id' => 'required|integer',
            'template' => 'required',
            'finger' => 'required|in_list[right_thumb,right_index,right_middle,right_ring,right_pinky,left_thumb,left_index,left_middle,left_ring,left_pinky]',
            'quality' => 'permit_empty|decimal',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $this->validator->getErrors(),
            ])->setStatusCode(400);
        }

        $employeeId = $this->request->getPost('employee_id');
        $template = $this->request->getPost('template');
        $finger = $this->request->getPost('finger');
        $quality = $this->request->getPost('quality', 0.85);

        // Check if employee exists
        $targetEmployee = $this->employeeModel->find($employeeId);

        if (!$targetEmployee) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Funcionário não encontrado',
            ])->setStatusCode(404);
        }

        // Check consent
        $hasConsent = $this->consentModel
            ->where('employee_id', $employeeId)
            ->where('consent_type', 'biometric_fingerprint')
            ->where('granted', true)
            ->where('revoked_at', null)
            ->first();

        if (!$hasConsent) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Funcionário não concedeu consentimento para uso de dados biométricos (LGPD)',
            ])->setStatusCode(403);
        }

        // Check if template for this finger already exists
        $existingTemplate = $this->biometricModel
            ->where('employee_id', $employeeId)
            ->where('biometric_type', 'fingerprint')
            ->where('active', true)
            ->first();

        if ($existingTemplate) {
            $metadata = json_decode($existingTemplate->metadata, true);
            if (isset($metadata['finger']) && $metadata['finger'] === $finger) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Já existe um template cadastrado para este dedo. Exclua o anterior antes de cadastrar novamente.',
                ])->setStatusCode(409);
            }
        }

        // Encrypt template data using CodeIgniter Encrypter
        $encrypter = \Config\Services::encrypter();
        $encryptedTemplate = base64_encode($encrypter->encrypt($template));

        // Generate hash for template
        $templateHash = hash('sha256', $template);

        // Save biometric template
        $templateData = [
            'employee_id' => $employeeId,
            'biometric_type' => 'fingerprint',
            'template_data' => $encryptedTemplate,
            'template_hash' => $templateHash,
            'metadata' => json_encode([
                'finger' => $finger,
                'quality' => $quality,
                'enrolled_by' => $employee['id'],
                'enrolled_by_name' => $employee['name'],
                'device_info' => $this->request->getUserAgent()->getAgentString(),
            ]),
            'enrollment_quality' => $quality,
            'model_used' => 'SourceAFIS', // Or device model
            'active' => true,
        ];

        $db = \Config\Database::connect();
        $db->transStart();

        $templateId = $this->biometricModel->insert($templateData);

        if (!$templateId) {
            $db->transRollback();
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao salvar template biométrico',
            ])->setStatusCode(500);
        }

        // Update employee record
        $this->employeeModel->update($employeeId, [
            'has_fingerprint_biometric' => true,
        ]);

        // Log enrollment
        $this->auditModel->insert([
            'user_id' => $employee['id'],
            'action' => 'ENROLL_FINGERPRINT',
            'entity_type' => 'biometric_templates',
            'entity_id' => $templateId,
            'description' => "Cadastro de impressão digital para funcionário {$targetEmployee->name} (dedo: {$finger})",
            'old_values' => null,
            'new_values' => json_encode([
                'template_id' => $templateId,
                'employee_id' => $employeeId,
                'finger' => $finger,
                'quality' => $quality,
            ]),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->getAgentString(),
            'level' => 'info',
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao processar cadastro',
            ])->setStatusCode(500);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Impressão digital cadastrada com sucesso!',
            'data' => [
                'template_id' => $templateId,
                'finger' => $finger,
                'quality' => $quality,
            ],
        ])->setStatusCode(201);
    }

    /**
     * Delete fingerprint template
     * DELETE /fingerprint/{id}
     */
    public function delete($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        // Check permissions
        if (!$employee || !in_array($employee['role'], ['admin', 'manager'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso negado',
            ])->setStatusCode(403);
        }

        if ($id === null) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'ID do template é obrigatório',
            ])->setStatusCode(400);
        }

        // Find template
        $template = $this->biometricModel->find($id);

        if (!$template) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Template não encontrado',
            ])->setStatusCode(404);
        }

        if ($template->biometric_type !== 'fingerprint') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Este template não é de impressão digital',
            ])->setStatusCode(400);
        }

        // Get employee info for audit log
        $targetEmployee = $this->employeeModel->find($template->employee_id);
        $metadata = json_decode($template->metadata, true);

        $db = \Config\Database::connect();
        $db->transStart();

        // Soft delete (set active = false)
        $deleted = $this->biometricModel->update($id, [
            'active' => false,
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$deleted) {
            $db->transRollback();
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao excluir template',
            ])->setStatusCode(500);
        }

        // Check if employee has other fingerprint templates
        $hasOtherTemplates = $this->biometricModel
            ->where('employee_id', $template->employee_id)
            ->where('biometric_type', 'fingerprint')
            ->where('active', true)
            ->where('id !=', $id)
            ->countAllResults() > 0;

        // Update employee record if no more templates
        if (!$hasOtherTemplates) {
            $this->employeeModel->update($template->employee_id, [
                'has_fingerprint_biometric' => false,
            ]);
        }

        // Log deletion
        $this->auditModel->insert([
            'user_id' => $employee['id'],
            'action' => 'DELETE_FINGERPRINT',
            'entity_type' => 'biometric_templates',
            'entity_id' => $id,
            'description' => "Exclusão de impressão digital do funcionário {$targetEmployee->name} (dedo: {$metadata['finger']})",
            'old_values' => json_encode([
                'template_id' => $id,
                'employee_id' => $template->employee_id,
                'finger' => $metadata['finger'] ?? 'unknown',
            ]),
            'new_values' => null,
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->getAgentString(),
            'level' => 'warning',
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao processar exclusão',
            ])->setStatusCode(500);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Template de impressão digital excluído com sucesso',
        ]);
    }

    /**
     * Test fingerprint recognition
     * POST /fingerprint/test
     */
    public function test()
    {
        $employee = $this->getAuthenticatedEmployee();

        // Check permissions
        if (!$employee || !in_array($employee['role'], ['admin', 'manager'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso negado',
            ])->setStatusCode(403);
        }

        // Validate input
        $rules = [
            'template_id' => 'required|integer',
            'test_template' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $this->validator->getErrors(),
            ])->setStatusCode(400);
        }

        $templateId = $this->request->getPost('template_id');
        $testTemplate = $this->request->getPost('test_template');

        // Find stored template
        $storedTemplate = $this->biometricModel->find($templateId);

        if (!$storedTemplate || $storedTemplate->biometric_type !== 'fingerprint' || !$storedTemplate->active) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Template não encontrado ou inativo',
            ])->setStatusCode(404);
        }

        // Decrypt stored template
        $encrypter = \Config\Services::encrypter();
        $decryptedTemplate = $encrypter->decrypt(base64_decode($storedTemplate->template_data));

        // Compare templates (simplified - in production use SourceAFIS)
        // This is a basic similarity check for demonstration
        $similarity = $this->compareFingerprints($decryptedTemplate, $testTemplate);

        // Threshold for match (typically 0.40-0.60 for fingerprints)
        $threshold = 0.50;
        $matched = $similarity >= $threshold;

        // Log test attempt
        $this->auditModel->insert([
            'user_id' => $employee['id'],
            'action' => 'TEST_FINGERPRINT',
            'entity_type' => 'biometric_templates',
            'entity_id' => $templateId,
            'description' => "Teste de reconhecimento de impressão digital (similaridade: " . round($similarity * 100, 2) . "%)",
            'old_values' => null,
            'new_values' => json_encode([
                'template_id' => $templateId,
                'similarity' => $similarity,
                'matched' => $matched,
            ]),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->getAgentString(),
            'level' => $matched ? 'info' : 'warning',
        ]);

        return $this->response->setJSON([
            'success' => true,
            'matched' => $matched,
            'similarity' => round($similarity * 100, 2), // Percentage
            'threshold' => round($threshold * 100, 2),
            'message' => $matched
                ? 'Impressão digital reconhecida com sucesso!'
                : 'Impressão digital não reconhecida. Similaridade abaixo do threshold.',
        ]);
    }

    /**
     * Compare two fingerprint templates
     *
     * NOTE: This is a simplified comparison for demonstration.
     * In production, use SourceAFIS library for accurate matching.
     *
     * @param string $template1 First template
     * @param string $template2 Second template
     * @return float Similarity score (0.0 to 1.0)
     */
    private function compareFingerprints(string $template1, string $template2): float
    {
        // TODO: Implement actual SourceAFIS comparison
        // For now, use a simple hash comparison

        $hash1 = hash('sha256', $template1);
        $hash2 = hash('sha256', $template2);

        if ($hash1 === $hash2) {
            return 1.0; // Perfect match
        }

        // Calculate Hamming distance for demonstration
        $distance = 0;
        $maxLength = max(strlen($template1), strlen($template2));

        for ($i = 0; $i < $maxLength; $i++) {
            $char1 = $i < strlen($template1) ? $template1[$i] : '';
            $char2 = $i < strlen($template2) ? $template2[$i] : '';

            if ($char1 !== $char2) {
                $distance++;
            }
        }

        // Convert distance to similarity (inverse)
        $similarity = 1.0 - ($distance / $maxLength);

        return max(0.0, min(1.0, $similarity));
    }

    /**
     * Get authenticated employee (helper method)
     */
    private function getAuthenticatedEmployee(): ?array
    {
        $session = session();
        $employeeId = $session->get('employee_id');

        if (!$employeeId) {
            return null;
        }

        $employee = $this->employeeModel->find($employeeId);

        if (!$employee) {
            return null;
        }

        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'email' => $employee->email,
            'role' => $employee->role,
            'department' => $employee->department,
        ];
    }
}
