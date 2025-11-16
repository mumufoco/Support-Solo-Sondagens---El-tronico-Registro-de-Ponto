<?php

namespace App\Services\LGPD;

use App\Models\EmployeeModel;
use App\Models\UserConsentModel;
use App\Models\AuditLogModel;
use App\Models\AttendanceModel;
use App\Models\BiometricRecordModel;
use App\Models\VacationModel;
use App\Models\WarningModel;
use CodeIgniter\I18n\Time;

/**
 * DataExportService
 *
 * Serviço para portabilidade de dados pessoais (LGPD Art. 19)
 * Implementa Comando 13.2 do Plano Inicial
 */
class DataExportService
{
    protected EmployeeModel $employeeModel;
    protected UserConsentModel $consentModel;
    protected AuditLogModel $auditModel;
    protected AttendanceModel $attendanceModel;
    protected BiometricRecordModel $biometricModel;
    protected VacationModel $vacationModel;
    protected WarningModel $warningModel;

    protected string $exportPath;
    protected int $expirationHours = 48;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->consentModel = new UserConsentModel();
        $this->auditModel = new AuditLogModel();
        $this->attendanceModel = new AttendanceModel();
        $this->biometricModel = new BiometricRecordModel();
        $this->vacationModel = new VacationModel();
        $this->warningModel = new WarningModel();

        $this->exportPath = WRITEPATH . 'exports/lgpd/';

        // Create export directory if it doesn't exist
        if (!is_dir($this->exportPath)) {
            mkdir($this->exportPath, 0755, true);
        }
    }

    /**
     * Export all user data (LGPD Art. 19)
     *
     * @param int $employeeId Employee ID
     * @param string|null $requestedBy Who requested the export (email)
     * @return array ['success' => bool, 'message' => string, 'export_id' => string|null]
     */
    public function exportUserData(int $employeeId, ?string $requestedBy = null): array
    {
        try {
            // Validate employee
            $employee = $this->employeeModel->find($employeeId);
            if (!$employee) {
                return [
                    'success' => false,
                    'message' => 'Funcionário não encontrado',
                    'export_id' => null,
                ];
            }

            $exportId = $this->generateExportId($employeeId);
            $exportDir = $this->exportPath . $exportId . '/';
            mkdir($exportDir, 0755, true);

            // Collect data from all tables
            $data = [
                '@context' => 'https://schema.org',
                '@type' => 'Person',
                'identifier' => (string)$employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'telephone' => $employee->phone ?? null,
                'jobTitle' => $employee->position ?? null,
                'worksFor' => [
                    '@type' => 'Organization',
                    'name' => env('COMPANY_NAME', 'Empresa'),
                ],
                'exportDate' => date('c'),
                'exportPurpose' => 'LGPD Art. 19 - Portabilidade de Dados',
                'personalData' => $this->collectPersonalData($employee),
                'consents' => $this->collectConsents($employeeId),
                'attendanceRecords' => $this->collectAttendance($employeeId),
                'biometricData' => $this->collectBiometricData($employeeId),
                'vacations' => $this->collectVacations($employeeId),
                'warnings' => $this->collectWarnings($employeeId),
                'auditLog' => $this->collectAuditLog($employeeId),
            ];

            // Save JSON-LD file
            $jsonFile = $exportDir . 'data.json';
            file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Create README
            $this->createReadme($exportDir, $employee);

            // Generate random password for ZIP
            $password = $this->generatePassword();

            // Create encrypted ZIP
            $zipFile = $this->exportPath . $exportId . '.zip';
            $zipResult = $this->createEncryptedZip($exportDir, $zipFile, $password);

            if (!$zipResult) {
                return [
                    'success' => false,
                    'message' => 'Erro ao criar arquivo ZIP',
                    'export_id' => null,
                ];
            }

            // Clean up temporary directory
            $this->deleteDirectory($exportDir);

            // Store export metadata
            $this->storeExportMetadata($exportId, $employeeId, $requestedBy);

            // Send emails
            $this->sendDownloadEmail($employee, $exportId);
            $this->sendPasswordEmail($employee, $password);

            // Audit log
            $this->auditModel->log(
                $employeeId,
                'EXPORT',
                'employees',
                $employeeId,
                null,
                [
                    'export_id' => $exportId,
                    'requested_by' => $requestedBy,
                    'file_size' => filesize($zipFile),
                ],
                "Exportação de dados LGPD solicitada por " . ($requestedBy ?? 'próprio funcionário'),
                'info'
            );

            return [
                'success' => true,
                'message' => 'Exportação realizada com sucesso. Verifique seu e-mail.',
                'export_id' => $exportId,
            ];

        } catch (\Exception $e) {
            log_message('error', 'DataExportService::exportUserData() error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao exportar dados: ' . $e->getMessage(),
                'export_id' => null,
            ];
        }
    }

    /**
     * Collect personal data
     */
    protected function collectPersonalData(object $employee): array
    {
        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'email' => $employee->email,
            'cpf' => $employee->cpf ?? null,
            'rg' => $employee->rg ?? null,
            'birth_date' => $employee->birth_date ?? null,
            'phone' => $employee->phone ?? null,
            'address' => $employee->address ?? null,
            'city' => $employee->city ?? null,
            'state' => $employee->state ?? null,
            'postal_code' => $employee->postal_code ?? null,
            'position' => $employee->position ?? null,
            'department' => $employee->department ?? null,
            'admission_date' => $employee->admission_date ?? null,
            'status' => $employee->status ?? null,
            'created_at' => $employee->created_at ?? null,
            'updated_at' => $employee->updated_at ?? null,
        ];
    }

    /**
     * Collect consents
     */
    protected function collectConsents(int $employeeId): array
    {
        $consents = $this->consentModel->getByEmployee($employeeId);
        $result = [];

        foreach ($consents as $consent) {
            $result[] = [
                '@type' => 'ConsentAction',
                'consentType' => $consent->consent_type,
                'purpose' => $consent->purpose,
                'legalBasis' => $consent->legal_basis,
                'granted' => $consent->granted,
                'grantedAt' => $consent->granted_at,
                'revokedAt' => $consent->revoked_at,
                'version' => $consent->version,
                'ipAddress' => $consent->ip_address,
            ];
        }

        return $result;
    }

    /**
     * Collect attendance records
     */
    protected function collectAttendance(int $employeeId): array
    {
        $db = \Config\Database::connect();
        $records = $db->table('attendance')
            ->where('employee_id', $employeeId)
            ->orderBy('clock_in', 'DESC')
            ->limit(1000)
            ->get()
            ->getResult();

        $result = [];

        foreach ($records as $record) {
            $result[] = [
                '@type' => 'WorkAttendance',
                'id' => $record->id,
                'date' => $record->date ?? null,
                'clockIn' => $record->clock_in ?? null,
                'clockOut' => $record->clock_out ?? null,
                'breakStart' => $record->break_start ?? null,
                'breakEnd' => $record->break_end ?? null,
                'totalHours' => $record->total_hours ?? null,
                'status' => $record->status ?? null,
                'location' => $record->location ?? null,
                'latitude' => $record->latitude ?? null,
                'longitude' => $record->longitude ?? null,
            ];
        }

        return $result;
    }

    /**
     * Collect biometric data (anonymized)
     */
    protected function collectBiometricData(int $employeeId): array
    {
        $records = $this->biometricModel
            ->where('employee_id', $employeeId)
            ->findAll();

        $result = [];

        foreach ($records as $record) {
            // IMPORTANT: Biometric templates are anonymized for export
            // Only metadata is included, not the actual biometric template
            $result[] = [
                '@type' => 'BiometricRecord',
                'id' => $record->id,
                'recordType' => $record->record_type,
                'templateHash' => hash('sha256', $record->template_hash ?? ''), // Double hash for anonymization
                'quality' => $record->quality ?? null,
                'device' => $record->device_info ?? null,
                'capturedAt' => $record->created_at ?? null,
                'note' => 'Template biométrico anonimizado para proteção de dados sensíveis',
            ];
        }

        return $result;
    }

    /**
     * Collect vacations
     */
    protected function collectVacations(int $employeeId): array
    {
        $db = \Config\Database::connect();
        $records = $db->table('vacations')
            ->where('employee_id', $employeeId)
            ->orderBy('start_date', 'DESC')
            ->get()
            ->getResult();

        $result = [];

        foreach ($records as $record) {
            $result[] = [
                '@type' => 'VacationRequest',
                'id' => $record->id,
                'startDate' => $record->start_date ?? null,
                'endDate' => $record->end_date ?? null,
                'days' => $record->days ?? null,
                'status' => $record->status ?? null,
                'requestDate' => $record->request_date ?? null,
                'approvedAt' => $record->approved_at ?? null,
                'approvedBy' => $record->approved_by ?? null,
            ];
        }

        return $result;
    }

    /**
     * Collect warnings
     */
    protected function collectWarnings(int $employeeId): array
    {
        $warnings = $this->warningModel
            ->where('employee_id', $employeeId)
            ->orderBy('occurrence_date', 'DESC')
            ->findAll();

        $result = [];

        foreach ($warnings as $warning) {
            $result[] = [
                '@type' => 'WarningAction',
                'id' => $warning->id,
                'warningType' => $warning->warning_type ?? null,
                'reason' => $warning->reason ?? null,
                'occurrenceDate' => $warning->occurrence_date ?? null,
                'status' => $warning->status ?? null,
                'issuedBy' => $warning->issued_by ?? null,
                'createdAt' => $warning->created_at ?? null,
            ];
        }

        return $result;
    }

    /**
     * Collect audit log
     */
    protected function collectAuditLog(int $employeeId): array
    {
        $logs = $this->auditModel->getByUser($employeeId, 500);
        $result = [];

        foreach ($logs as $log) {
            $result[] = [
                '@type' => 'AuditAction',
                'action' => $log->action,
                'entityType' => $log->entity_type,
                'entityId' => $log->entity_id,
                'description' => $log->description,
                'level' => $log->level,
                'ipAddress' => $log->ip_address,
                'timestamp' => $log->created_at,
            ];
        }

        return $result;
    }

    /**
     * Create README file for export
     */
    protected function createReadme(string $dir, object $employee): void
    {
        $content = <<<EOF
# Exportação de Dados Pessoais - LGPD

## Informações do Titular
- **Nome:** {$employee->name}
- **E-mail:** {$employee->email}
- **Data da Exportação:** {date('d/m/Y H:i:s')}

## Finalidade
Esta exportação foi realizada em conformidade com a Lei Geral de Proteção de Dados (LGPD - Lei nº 13.709/2018), especificamente o Art. 19, que garante o direito à portabilidade dos dados.

## Conteúdo
O arquivo `data.json` contém todos os seus dados pessoais armazenados em nosso sistema, incluindo:

1. **Dados Pessoais:** Nome, CPF, RG, contatos, endereço
2. **Consentimentos:** Histórico de consentimentos concedidos e revogados
3. **Registros de Ponto:** Horários de entrada, saída e intervalos
4. **Dados Biométricos:** Metadados de registros biométricos (templates anonimizados)
5. **Férias:** Histórico de solicitações e aprovações
6. **Advertências:** Registros disciplinares, se houver
7. **Log de Auditoria:** Histórico de acessos e alterações aos seus dados

## Formato
Os dados estão no formato JSON-LD (JSON for Linking Data), seguindo o padrão schema.org, que facilita a interoperabilidade e reutilização dos dados.

## Validade
Este arquivo de exportação estará disponível para download por **48 horas** a partir da data de geração. Após este período, será automaticamente deletado por questões de segurança.

## Dúvidas
Em caso de dúvidas sobre seus dados ou esta exportação, entre em contato com nosso Encarregado de Proteção de Dados (DPO):
- E-mail: {env('DPO_EMAIL', 'dpo@empresa.com')}

---
*Gerado automaticamente pelo Sistema de Gestão de Ponto Eletrônico*
EOF;

        file_put_contents($dir . 'README.txt', $content);
    }

    /**
     * Create encrypted ZIP file
     */
    protected function createEncryptedZip(string $sourceDir, string $zipFile, string $password): bool
    {
        try {
            $zip = new \ZipArchive();

            if ($zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                return false;
            }

            // Set encryption
            $zip->setPassword($password);

            // Add files
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sourceDir),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($sourceDir));

                    $zip->addFile($filePath, $relativePath);
                    $zip->setEncryptionName($relativePath, \ZipArchive::EM_AES_256);
                }
            }

            $zip->close();

            return true;

        } catch (\Exception $e) {
            log_message('error', 'Failed to create encrypted ZIP: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate unique export ID
     */
    protected function generateExportId(int $employeeId): string
    {
        return 'export_' . $employeeId . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(8));
    }

    /**
     * Generate secure random password
     */
    protected function generatePassword(int $length = 16): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $password;
    }

    /**
     * Store export metadata
     */
    protected function storeExportMetadata(string $exportId, int $employeeId, ?string $requestedBy): void
    {
        $db = \Config\Database::connect();

        $db->table('data_exports')->insert([
            'export_id' => $exportId,
            'employee_id' => $employeeId,
            'requested_by' => $requestedBy,
            'status' => 'completed',
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$this->expirationHours} hours")),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Send download link email
     */
    protected function sendDownloadEmail(object $employee, string $exportId): void
    {
        $email = \Config\Services::email();

        $downloadUrl = base_url("lgpd/download-export/{$exportId}");
        $expiresAt = date('d/m/Y H:i', strtotime("+{$this->expirationHours} hours"));

        $email->setTo($employee->email);
        $email->setSubject('[LGPD] Sua exportação de dados está pronta');
        $email->setMessage("
            <h2>Exportação de Dados - LGPD</h2>
            <p>Olá, {$employee->name}!</p>
            <p>Sua solicitação de exportação de dados pessoais foi processada com sucesso.</p>

            <h3>Instruções para Download:</h3>
            <ol>
                <li>Clique no link abaixo para fazer o download do arquivo ZIP:</li>
                <li><a href=\"{$downloadUrl}\" style=\"color: #007bff; font-weight: bold;\">{$downloadUrl}</a></li>
                <li>Você receberá um <strong>e-mail separado</strong> com a senha para descompactar o arquivo.</li>
            </ol>

            <p><strong>IMPORTANTE:</strong></p>
            <ul>
                <li>O link de download estará disponível até: <strong>{$expiresAt}</strong></li>
                <li>Após este prazo, o arquivo será automaticamente deletado por segurança</li>
                <li>A senha foi enviada em um e-mail separado para aumentar a segurança</li>
            </ul>

            <p>Se você não solicitou esta exportação, entre em contato imediatamente com nosso DPO.</p>

            <hr>
            <p style=\"font-size: 12px; color: #666;\">
                Este e-mail foi enviado em conformidade com a LGPD (Lei nº 13.709/2018).<br>
                DPO: " . env('DPO_EMAIL', 'dpo@empresa.com') . "
            </p>
        ");

        try {
            $email->send();
        } catch (\Exception $e) {
            log_message('error', 'Failed to send download email: ' . $e->getMessage());
        }
    }

    /**
     * Send password email (separate for security)
     */
    protected function sendPasswordEmail(object $employee, string $password): void
    {
        $email = \Config\Services::email();

        $email->setTo($employee->email);
        $email->setSubject('[LGPD] Senha para sua exportação de dados');
        $email->setMessage("
            <h2>Senha de Acesso - Exportação LGPD</h2>
            <p>Olá, {$employee->name}!</p>
            <p>Esta é a senha para descompactar o arquivo ZIP da sua exportação de dados:</p>

            <div style=\"background: #f8f9fa; padding: 20px; margin: 20px 0; border-left: 4px solid #007bff;\">
                <p style=\"margin: 0; font-size: 18px; font-weight: bold; font-family: monospace;\">{$password}</p>
            </div>

            <p><strong>Instruções:</strong></p>
            <ol>
                <li>Faça o download do arquivo ZIP usando o link enviado no e-mail anterior</li>
                <li>Ao descompactar, use a senha acima</li>
                <li>Dentro você encontrará seus dados em formato JSON-LD</li>
            </ol>

            <p><strong>ATENÇÃO:</strong></p>
            <ul>
                <li>Esta senha é única e foi gerada especificamente para esta exportação</li>
                <li>Não compartilhe esta senha com ninguém</li>
                <li>Por segurança, a senha foi enviada separadamente do link de download</li>
            </ul>

            <hr>
            <p style=\"font-size: 12px; color: #666;\">
                Este e-mail foi enviado em conformidade com a LGPD (Lei nº 13.709/2018).<br>
                DPO: " . env('DPO_EMAIL', 'dpo@empresa.com') . "
            </p>
        ");

        try {
            $email->send();
        } catch (\Exception $e) {
            log_message('error', 'Failed to send password email: ' . $e->getMessage());
        }
    }

    /**
     * Delete directory recursively
     */
    protected function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        return rmdir($dir);
    }

    /**
     * Clean up expired exports (to be run by cron job)
     */
    public function cleanupExpiredExports(): int
    {
        $db = \Config\Database::connect();

        // Get expired exports
        $expired = $db->table('data_exports')
            ->where('expires_at <', date('Y-m-d H:i:s'))
            ->where('status', 'completed')
            ->get()
            ->getResult();

        $deleted = 0;

        foreach ($expired as $export) {
            $zipFile = $this->exportPath . $export->export_id . '.zip';

            if (file_exists($zipFile)) {
                unlink($zipFile);
                $deleted++;

                log_message('info', "Deleted expired export: {$export->export_id}");
            }

            // Update status
            $db->table('data_exports')
                ->where('id', $export->id)
                ->update(['status' => 'expired']);
        }

        return $deleted;
    }
}
