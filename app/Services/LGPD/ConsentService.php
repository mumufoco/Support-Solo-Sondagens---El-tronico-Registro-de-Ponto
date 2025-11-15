<?php

namespace App\Services\LGPD;

use App\Models\UserConsentModel;
use App\Models\AuditLogModel;
use App\Models\EmployeeModel;
use App\Models\BiometricRecordModel;
use CodeIgniter\I18n\Time;

/**
 * ConsentService
 *
 * Serviço para gestão de consentimentos LGPD
 * Implementa Comando 13.1 do Plano Inicial
 */
class ConsentService
{
    protected UserConsentModel $consentModel;
    protected AuditLogModel $auditModel;
    protected EmployeeModel $employeeModel;
    protected BiometricRecordModel $biometricModel;

    public function __construct()
    {
        $this->consentModel = new UserConsentModel();
        $this->auditModel = new AuditLogModel();
        $this->employeeModel = new EmployeeModel();
        $this->biometricModel = new BiometricRecordModel();
    }

    /**
     * Grant consent with full audit trail
     *
     * @param int $employeeId Employee ID
     * @param string $consentType Type of consent
     * @param string $purpose Purpose of data processing
     * @param string $consentText Full consent text presented to user
     * @param string|null $legalBasis LGPD legal basis (e.g., "Art. 11, II")
     * @param string $version Version of consent term
     * @return array ['success' => bool, 'message' => string, 'consent_id' => int|null]
     */
    public function grant(
        int $employeeId,
        string $consentType,
        string $purpose,
        string $consentText,
        ?string $legalBasis = null,
        string $version = '1.0'
    ): array {
        try {
            // Validate employee exists
            $employee = $this->employeeModel->find($employeeId);
            if (!$employee) {
                return [
                    'success' => false,
                    'message' => 'Funcionário não encontrado',
                    'consent_id' => null,
                ];
            }

            // Validate consent type
            $validTypes = [
                'biometric_face',
                'biometric_fingerprint',
                'geolocation',
                'data_processing',
                'marketing',
                'data_sharing'
            ];

            if (!in_array($consentType, $validTypes)) {
                return [
                    'success' => false,
                    'message' => 'Tipo de consentimento inválido',
                    'consent_id' => null,
                ];
            }

            // Grant consent using model
            $result = $this->consentModel->grant(
                $employeeId,
                $consentType,
                $purpose,
                $consentText,
                $legalBasis,
                $version
            );

            if (!$result) {
                return [
                    'success' => false,
                    'message' => 'Erro ao registrar consentimento',
                    'consent_id' => null,
                ];
            }

            // Get the newly created consent
            $consent = $this->consentModel->getActiveConsent($employeeId, $consentType);

            // Audit log
            $this->auditModel->log(
                $employeeId,
                'GRANT_CONSENT',
                'user_consents',
                $consent->id ?? null,
                null,
                [
                    'consent_type' => $consentType,
                    'purpose' => $purpose,
                    'version' => $version,
                    'granted_at' => date('Y-m-d H:i:s'),
                ],
                "Consentimento '{$consentType}' concedido por {$employee->name}",
                'info'
            );

            // Send notification to DPO/Admin
            $this->notifyConsentGranted($employee, $consentType, $consent);

            return [
                'success' => true,
                'message' => 'Consentimento registrado com sucesso',
                'consent_id' => $consent->id ?? null,
            ];

        } catch (\Exception $e) {
            log_message('error', 'ConsentService::grant() error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao processar consentimento: ' . $e->getMessage(),
                'consent_id' => null,
            ];
        }
    }

    /**
     * Revoke consent with biometric data deletion
     *
     * @param int $employeeId Employee ID
     * @param string $consentType Type of consent to revoke
     * @param string|null $reason Reason for revocation
     * @return array ['success' => bool, 'message' => string, 'deleted_records' => int]
     */
    public function revoke(
        int $employeeId,
        string $consentType,
        ?string $reason = null
    ): array {
        try {
            // Get current consent
            $consent = $this->consentModel->getActiveConsent($employeeId, $consentType);

            if (!$consent) {
                return [
                    'success' => false,
                    'message' => 'Consentimento não encontrado ou já revogado',
                    'deleted_records' => 0,
                ];
            }

            $employee = $this->employeeModel->find($employeeId);

            // Store old values for audit
            $oldValues = [
                'consent_type' => $consent->consent_type,
                'granted' => $consent->granted,
                'granted_at' => $consent->granted_at,
            ];

            // Revoke consent
            $result = $this->consentModel->revoke($employeeId, $consentType);

            if (!$result) {
                return [
                    'success' => false,
                    'message' => 'Erro ao revogar consentimento',
                    'deleted_records' => 0,
                ];
            }

            $deletedRecords = 0;

            // Delete biometric data if applicable (LGPD Art. 16)
            if (in_array($consentType, ['biometric_face', 'biometric_fingerprint'])) {
                $deletedRecords = $this->deleteBiometricData($employeeId, $consentType);
            }

            // Audit log
            $this->auditModel->log(
                $employeeId,
                'REVOKE_CONSENT',
                'user_consents',
                $consent->id,
                $oldValues,
                [
                    'granted' => false,
                    'revoked_at' => date('Y-m-d H:i:s'),
                    'reason' => $reason,
                    'deleted_biometric_records' => $deletedRecords,
                ],
                "Consentimento '{$consentType}' revogado por {$employee->name}. Motivo: " . ($reason ?? 'Não informado'),
                'warning'
            );

            // Send notification to DPO/Admin
            $this->notifyConsentRevoked($employee, $consentType, $reason, $deletedRecords);

            return [
                'success' => true,
                'message' => 'Consentimento revogado com sucesso',
                'deleted_records' => $deletedRecords,
            ];

        } catch (\Exception $e) {
            log_message('error', 'ConsentService::revoke() error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao revogar consentimento: ' . $e->getMessage(),
                'deleted_records' => 0,
            ];
        }
    }

    /**
     * Delete biometric data for employee
     * LGPD Art. 16 - Right to deletion
     *
     * @param int $employeeId Employee ID
     * @param string $consentType Type of biometric consent
     * @return int Number of records deleted
     */
    protected function deleteBiometricData(int $employeeId, string $consentType): int
    {
        $recordType = match($consentType) {
            'biometric_face' => 'face',
            'biometric_fingerprint' => 'fingerprint',
            default => null,
        };

        if (!$recordType) {
            return 0;
        }

        // Get all biometric records of this type
        $records = $this->biometricModel
            ->where('employee_id', $employeeId)
            ->where('record_type', $recordType)
            ->findAll();

        $count = count($records);

        // Delete records (hard delete for biometric data)
        $this->biometricModel
            ->where('employee_id', $employeeId)
            ->where('record_type', $recordType)
            ->delete();

        // Audit each deletion
        foreach ($records as $record) {
            $this->auditModel->log(
                $employeeId,
                'DELETE_BIOMETRIC',
                'biometric_records',
                $record->id,
                [
                    'record_type' => $record->record_type,
                    'template_hash' => substr($record->template_hash, 0, 20) . '...',
                ],
                null,
                "Dado biométrico deletado após revogação de consentimento '{$consentType}'",
                'warning'
            );
        }

        log_message('info', "Deleted {$count} biometric records (type: {$recordType}) for employee {$employeeId}");

        return $count;
    }

    /**
     * Get all consents for employee
     *
     * @param int $employeeId Employee ID
     * @return array List of consents with status
     */
    public function getEmployeeConsents(int $employeeId): array
    {
        $consents = $this->consentModel->getByEmployee($employeeId);
        $pending = $this->consentModel->getPending($employeeId);

        return [
            'active' => array_filter($consents, fn($c) => $c->granted && !$c->revoked_at),
            'revoked' => array_filter($consents, fn($c) => !$c->granted || $c->revoked_at),
            'pending' => $pending,
            'all' => $consents,
        ];
    }

    /**
     * Check if employee has specific consent
     *
     * @param int $employeeId Employee ID
     * @param string $consentType Type of consent
     * @return bool True if consent is active
     */
    public function hasConsent(int $employeeId, string $consentType): bool
    {
        return $this->consentModel->hasConsent($employeeId, $consentType);
    }

    /**
     * Generate ANPD compliance report
     * Relatório para Autoridade Nacional de Proteção de Dados
     *
     * @param string|null $startDate Start date (Y-m-d)
     * @param string|null $endDate End date (Y-m-d)
     * @return array Report data
     */
    public function generateANPDReport(?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $endDate ?? date('Y-m-d');

        // Get all consent activities in period
        $db = \Config\Database::connect();

        // Consents granted
        $granted = $db->table('user_consents')
            ->where('granted_at >=', $startDate . ' 00:00:00')
            ->where('granted_at <=', $endDate . ' 23:59:59')
            ->where('granted', true)
            ->get()
            ->getResult();

        // Consents revoked
        $revoked = $db->table('user_consents')
            ->where('revoked_at >=', $startDate . ' 00:00:00')
            ->where('revoked_at <=', $endDate . ' 23:59:59')
            ->where('granted', false)
            ->get()
            ->getResult();

        // Group by consent type
        $grantedByType = [];
        $revokedByType = [];

        foreach ($granted as $consent) {
            $type = $consent->consent_type;
            $grantedByType[$type] = ($grantedByType[$type] ?? 0) + 1;
        }

        foreach ($revoked as $consent) {
            $type = $consent->consent_type;
            $revokedByType[$type] = ($revokedByType[$type] ?? 0) + 1;
        }

        // Get audit logs for data access/export
        $accessLogs = $this->auditModel->search([
            'action' => 'EXPORT',
            'start_date' => $startDate . ' 00:00:00',
            'end_date' => $endDate . ' 23:59:59',
            'limit' => 1000,
        ]);

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'consents' => [
                'granted' => [
                    'total' => count($granted),
                    'by_type' => $grantedByType,
                ],
                'revoked' => [
                    'total' => count($revoked),
                    'by_type' => $revokedByType,
                ],
            ],
            'data_access' => [
                'exports' => count($accessLogs),
            ],
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Send notification when consent is granted
     *
     * @param object $employee Employee data
     * @param string $consentType Type of consent
     * @param object $consent Consent record
     * @return void
     */
    protected function notifyConsentGranted(object $employee, string $consentType, object $consent): void
    {
        $email = \Config\Services::email();

        $consentLabels = [
            'biometric_face' => 'Biometria Facial',
            'biometric_fingerprint' => 'Biometria Digital',
            'geolocation' => 'Geolocalização',
            'data_processing' => 'Processamento de Dados',
            'marketing' => 'Marketing',
            'data_sharing' => 'Compartilhamento de Dados',
        ];

        $label = $consentLabels[$consentType] ?? $consentType;

        $email->setTo(env('DPO_EMAIL', 'dpo@empresa.com'));
        $email->setSubject('[LGPD] Novo Consentimento Concedido');
        $email->setMessage("
            <h3>Novo Consentimento LGPD</h3>
            <p><strong>Funcionário:</strong> {$employee->name} (ID: {$employee->id})</p>
            <p><strong>Tipo:</strong> {$label}</p>
            <p><strong>Data:</strong> {$consent->granted_at}</p>
            <p><strong>IP:</strong> {$consent->ip_address}</p>
            <p><strong>Versão do Termo:</strong> {$consent->version}</p>
        ");

        try {
            $email->send();
        } catch (\Exception $e) {
            log_message('error', 'Failed to send consent granted notification: ' . $e->getMessage());
        }
    }

    /**
     * Send notification when consent is revoked
     *
     * @param object $employee Employee data
     * @param string $consentType Type of consent
     * @param string|null $reason Revocation reason
     * @param int $deletedRecords Number of deleted records
     * @return void
     */
    protected function notifyConsentRevoked(
        object $employee,
        string $consentType,
        ?string $reason,
        int $deletedRecords
    ): void {
        $email = \Config\Services::email();

        $consentLabels = [
            'biometric_face' => 'Biometria Facial',
            'biometric_fingerprint' => 'Biometria Digital',
            'geolocation' => 'Geolocalização',
            'data_processing' => 'Processamento de Dados',
            'marketing' => 'Marketing',
            'data_sharing' => 'Compartilhamento de Dados',
        ];

        $label = $consentLabels[$consentType] ?? $consentType;

        $email->setTo(env('DPO_EMAIL', 'dpo@empresa.com'));
        $email->setSubject('[LGPD] Consentimento Revogado - Ação Necessária');
        $email->setMessage("
            <h3>Consentimento LGPD Revogado</h3>
            <p><strong>Funcionário:</strong> {$employee->name} (ID: {$employee->id})</p>
            <p><strong>Tipo:</strong> {$label}</p>
            <p><strong>Data da Revogação:</strong> " . date('Y-m-d H:i:s') . "</p>
            <p><strong>Motivo:</strong> " . ($reason ?? 'Não informado') . "</p>
            <p><strong>Registros Biométricos Deletados:</strong> {$deletedRecords}</p>
            <hr>
            <p><em>Esta é uma notificação automática para conformidade LGPD.</em></p>
        ");

        try {
            $email->send();
        } catch (\Exception $e) {
            log_message('error', 'Failed to send consent revoked notification: ' . $e->getMessage());
        }
    }
}
