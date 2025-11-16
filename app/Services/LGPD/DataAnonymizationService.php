<?php

namespace App\Services\LGPD;

use App\Models\EmployeeModel;
use App\Models\TimePunchModel;
use App\Models\BiometricTemplateModel;
use App\Models\JustificationModel;
use App\Models\ChatMessageModel;
use App\Models\WarningModel;
use App\Models\UserConsentModel;
use App\Models\AuditLogModel;
use App\Models\NotificationModel;
use App\Models\TimesheetConsolidatedModel;

/**
 * DataAnonymizationService
 *
 * Serviço para anonimização de dados pessoais (LGPD Art. 16 - Direito ao Esquecimento)
 * Implementa Comando 13.3 do Plano Inicial
 *
 * Funcionalidades:
 * - Anonimizar dados de funcionário após solicitação
 * - Anonimizar dados automaticamente após período de retenção
 * - Manter integridade referencial
 * - Preservar dados estatísticos
 * - Registrar todas as ações em audit log
 */
class DataAnonymizationService
{
    protected $employeeModel;
    protected $timePunchModel;
    protected $biometricModel;
    protected $justificationModel;
    protected $chatMessageModel;
    protected $warningModel;
    protected $consentModel;
    protected $auditModel;
    protected $notificationModel;
    protected $timesheetModel;

    // Padrões de anonimização
    const ANONYMIZED_NAME = 'Usuário Anonimizado';
    const ANONYMIZED_EMAIL_PATTERN = 'anonimizado_%d@deleted.local';
    const ANONYMIZED_CPF = '000.000.000-00';
    const ANONYMIZED_PHONE = '(00) 00000-0000';
    const ANONYMIZED_CODE = 'ANON-%s';

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->timePunchModel = new TimePunchModel();
        $this->biometricModel = new BiometricTemplateModel();
        $this->justificationModel = new JustificationModel();
        $this->chatMessageModel = new ChatMessageModel();
        $this->warningModel = new WarningModel();
        $this->consentModel = new UserConsentModel();
        $this->auditModel = new AuditLogModel();
        $this->notificationModel = new NotificationModel();
        $this->timesheetModel = new TimesheetConsolidatedModel();
    }

    /**
     * Anonimizar dados de um funcionário
     *
     * @param int $employeeId ID do funcionário
     * @param string|null $requestedBy Quem solicitou (email)
     * @param string|null $reason Motivo da anonimização
     * @return array ['success' => bool, 'message' => string, 'anonymized_count' => int]
     */
    public function anonymizeEmployee(int $employeeId, ?string $requestedBy = null, ?string $reason = null): array
    {
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Validar funcionário
            $employee = $this->employeeModel->find($employeeId);

            if (!$employee) {
                $db->transRollback();
                return [
                    'success' => false,
                    'message' => 'Funcionário não encontrado',
                    'anonymized_count' => 0,
                ];
            }

            // Verificar se já está anonimizado
            if ($this->isAlreadyAnonymized($employee)) {
                $db->transRollback();
                return [
                    'success' => false,
                    'message' => 'Funcionário já está anonimizado',
                    'anonymized_count' => 0,
                ];
            }

            // Armazenar dados originais para audit log
            $originalData = [
                'name' => $employee->name,
                'email' => $employee->email,
                'cpf' => $employee->cpf,
            ];

            $anonymizedCount = 0;

            // 1. Anonimizar dados do funcionário
            $anonymizedCount += $this->anonymizeEmployeeData($employeeId);

            // 2. Anonimizar dados biométricos
            $anonymizedCount += $this->anonymizeBiometricData($employeeId);

            // 3. Anonimizar mensagens de chat
            $anonymizedCount += $this->anonymizeChatMessages($employeeId);

            // 4. Anonimizar informações sensíveis em justificativas
            $anonymizedCount += $this->anonymizeJustifications($employeeId);

            // 5. Anonimizar informações sensíveis em advertências
            $anonymizedCount += $this->anonymizeWarnings($employeeId);

            // 6. Anonimizar dados em logs de auditoria (parcial)
            $anonymizedCount += $this->anonymizeAuditLogs($employeeId);

            // 7. Revogar todos os consentimentos
            $anonymizedCount += $this->revokeAllConsents($employeeId);

            // 8. Limpar notificações
            $anonymizedCount += $this->deleteNotifications($employeeId);

            // 9. Registrar anonimização no audit log
            $this->auditModel->insert([
                'user_id' => null, // Sistema
                'action' => 'ANONYMIZE_EMPLOYEE',
                'entity_type' => 'employees',
                'entity_id' => $employeeId,
                'description' => sprintf(
                    'Anonimização de dados do funcionário #%d. Solicitado por: %s. Motivo: %s',
                    $employeeId,
                    $requestedBy ?? 'Sistema',
                    $reason ?? 'Direito ao esquecimento (LGPD Art. 16)'
                ),
                'old_values' => json_encode($originalData),
                'new_values' => json_encode([
                    'name' => self::ANONYMIZED_NAME,
                    'email' => sprintf(self::ANONYMIZED_EMAIL_PATTERN, $employeeId),
                    'anonymized_at' => date('Y-m-d H:i:s'),
                ]),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'System',
                'level' => 'warning',
            ]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                return [
                    'success' => false,
                    'message' => 'Erro ao processar anonimização',
                    'anonymized_count' => 0,
                ];
            }

            return [
                'success' => true,
                'message' => sprintf('Dados anonimizados com sucesso. %d registros afetados.', $anonymizedCount),
                'anonymized_count' => $anonymizedCount,
            ];

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Erro ao anonimizar funcionário: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erro ao processar anonimização: ' . $e->getMessage(),
                'anonymized_count' => 0,
            ];
        }
    }

    /**
     * Anonimizar dados principais do funcionário
     */
    protected function anonymizeEmployeeData(int $employeeId): int
    {
        $anonymizedEmail = sprintf(self::ANONYMIZED_EMAIL_PATTERN, $employeeId);
        $anonymizedCode = sprintf(self::ANONYMIZED_CODE, uniqid());

        $data = [
            'name' => self::ANONYMIZED_NAME,
            'email' => $anonymizedEmail,
            'cpf' => self::ANONYMIZED_CPF,
            'phone' => self::ANONYMIZED_PHONE,
            'unique_code' => $anonymizedCode,
            'password' => password_hash(bin2hex(random_bytes(32)), PASSWORD_ARGON2ID), // Senha aleatória inacessível
            'active' => false,
            'anonymized_at' => date('Y-m-d H:i:s'),
            'has_face_biometric' => false,
            'has_fingerprint_biometric' => false,
        ];

        return $this->employeeModel->update($employeeId, $data) ? 1 : 0;
    }

    /**
     * Excluir dados biométricos
     */
    protected function anonymizeBiometricData(int $employeeId): int
    {
        $count = 0;

        // Buscar templates biométricos
        $templates = $this->biometricModel
            ->where('employee_id', $employeeId)
            ->findAll();

        foreach ($templates as $template) {
            // Deletar arquivo de foto facial se existir
            if ($template->biometric_type === 'face' && !empty($template->file_path)) {
                $filePath = WRITEPATH . $template->file_path;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Soft delete do template
            $this->biometricModel->update($template->id, [
                'active' => false,
                'template_data' => null,
                'file_path' => null,
                'deleted_at' => date('Y-m-d H:i:s'),
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Anonimizar mensagens de chat
     */
    protected function anonymizeChatMessages(int $employeeId): int
    {
        // Anonimizar mensagens enviadas
        $this->chatMessageModel
            ->where('sender_id', $employeeId)
            ->set([
                'message' => '[Mensagem anonimizada]',
                'deleted' => true,
            ])
            ->update();

        $sentCount = $this->chatMessageModel->affectedRows();

        // Anonimizar mensagens recebidas (opcional - manter para contexto)
        // Aqui podemos escolher não anonimizar para manter histórico de conversas

        return $sentCount;
    }

    /**
     * Anonimizar justificativas
     */
    protected function anonymizeJustifications(int $employeeId): int
    {
        $justifications = $this->justificationModel
            ->where('employee_id', $employeeId)
            ->findAll();

        $count = 0;

        foreach ($justifications as $justification) {
            // Deletar arquivos anexados
            if (!empty($justification->attachment_paths)) {
                $paths = json_decode($justification->attachment_paths, true);
                foreach ($paths as $path) {
                    $filePath = WRITEPATH . $path;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }

            // Anonimizar dados sensíveis
            $this->justificationModel->update($justification->id, [
                'reason' => '[Motivo anonimizado]',
                'attachment_paths' => null,
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Anonimizar advertências
     */
    protected function anonymizeWarnings(int $employeeId): int
    {
        $warnings = $this->warningModel
            ->where('employee_id', $employeeId)
            ->findAll();

        $count = 0;

        foreach ($warnings as $warning) {
            // Deletar arquivos de evidência
            if (!empty($warning->evidence_files)) {
                $paths = json_decode($warning->evidence_files, true);
                foreach ($paths as $path) {
                    $filePath = WRITEPATH . $path;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                }
            }

            // Anonimizar dados sensíveis (manter tipo e data para estatísticas)
            $this->warningModel->update($warning->id, [
                'reason' => '[Motivo anonimizado]',
                'evidence_files' => null,
                'employee_signature' => null,
                'witness_signature' => null,
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Anonimizar logs de auditoria (parcial - manter ações mas remover dados sensíveis)
     */
    protected function anonymizeAuditLogs(int $employeeId): int
    {
        // Anonimizar old_values e new_values que contenham dados pessoais
        $this->auditModel
            ->where('user_id', $employeeId)
            ->set([
                'old_values' => null,
                'new_values' => null,
                'description' => '[Descrição anonimizada]',
            ])
            ->update();

        return $this->auditModel->affectedRows();
    }

    /**
     * Revogar todos os consentimentos
     */
    protected function revokeAllConsents(int $employeeId): int
    {
        $this->consentModel
            ->where('employee_id', $employeeId)
            ->where('granted', true)
            ->where('revoked_at', null)
            ->set([
                'granted' => false,
                'revoked_at' => date('Y-m-d H:i:s'),
                'revocation_reason' => 'Anonimização de dados (LGPD)',
            ])
            ->update();

        return $this->consentModel->affectedRows();
    }

    /**
     * Deletar notificações
     */
    protected function deleteNotifications(int $employeeId): int
    {
        $this->notificationModel
            ->where('employee_id', $employeeId)
            ->delete();

        return $this->notificationModel->affectedRows();
    }

    /**
     * Verificar se funcionário já está anonimizado
     */
    protected function isAlreadyAnonymized($employee): bool
    {
        return $employee->name === self::ANONYMIZED_NAME
            || !empty($employee->anonymized_at);
    }

    /**
     * Agendar anonimização automática após período de retenção
     *
     * @param int $employeeId ID do funcionário
     * @param int $retentionDays Dias de retenção (padrão: 3650 = 10 anos)
     * @return array
     */
    public function scheduleAnonymization(int $employeeId, int $retentionDays = 3650): array
    {
        $employee = $this->employeeModel->find($employeeId);

        if (!$employee) {
            return [
                'success' => false,
                'message' => 'Funcionário não encontrado',
            ];
        }

        // Calcular data de anonimização
        $anonymizationDate = date('Y-m-d', strtotime("+{$retentionDays} days"));

        // Armazenar em campo ou tabela de agendamentos
        $this->employeeModel->update($employeeId, [
            'scheduled_anonymization_date' => $anonymizationDate,
        ]);

        // Registrar no audit log
        $this->auditModel->insert([
            'user_id' => null,
            'action' => 'SCHEDULE_ANONYMIZATION',
            'entity_type' => 'employees',
            'entity_id' => $employeeId,
            'description' => sprintf(
                'Anonimização agendada para %s (%d dias)',
                $anonymizationDate,
                $retentionDays
            ),
            'level' => 'info',
        ]);

        return [
            'success' => true,
            'message' => sprintf('Anonimização agendada para %s', $anonymizationDate),
            'scheduled_date' => $anonymizationDate,
        ];
    }

    /**
     * Processar anonimizações agendadas (executar via CRON diário)
     *
     * @return array
     */
    public function processScheduledAnonymizations(): array
    {
        $today = date('Y-m-d');

        // Buscar funcionários com anonimização agendada para hoje ou anterior
        $employees = $this->employeeModel
            ->where('scheduled_anonymization_date <=', $today)
            ->where('scheduled_anonymization_date IS NOT NULL')
            ->where('anonymized_at', null)
            ->findAll();

        $processed = 0;
        $failed = 0;

        foreach ($employees as $employee) {
            $result = $this->anonymizeEmployee(
                $employee->id,
                'Sistema - Agendamento Automático',
                sprintf('Período de retenção expirado em %s', $employee->scheduled_anonymization_date)
            );

            if ($result['success']) {
                $processed++;
            } else {
                $failed++;
                log_message('error', sprintf(
                    'Falha ao anonimizar funcionário #%d: %s',
                    $employee->id,
                    $result['message']
                ));
            }
        }

        return [
            'success' => true,
            'processed' => $processed,
            'failed' => $failed,
            'message' => sprintf('%d funcionários anonimizados, %d falharam', $processed, $failed),
        ];
    }

    /**
     * Anonimizar dados de um tipo específico
     *
     * @param string $dataType Tipo de dado (biometric, chat, justifications, warnings, audit_logs)
     * @param int $employeeId ID do funcionário
     * @return array
     */
    public function anonymizeDataType(string $dataType, int $employeeId): array
    {
        $count = 0;

        switch ($dataType) {
            case 'biometric':
                $count = $this->anonymizeBiometricData($employeeId);
                break;

            case 'chat':
                $count = $this->anonymizeChatMessages($employeeId);
                break;

            case 'justifications':
                $count = $this->anonymizeJustifications($employeeId);
                break;

            case 'warnings':
                $count = $this->anonymizeWarnings($employeeId);
                break;

            case 'audit_logs':
                $count = $this->anonymizeAuditLogs($employeeId);
                break;

            default:
                return [
                    'success' => false,
                    'message' => 'Tipo de dado inválido',
                    'anonymized_count' => 0,
                ];
        }

        // Registrar no audit log
        $this->auditModel->insert([
            'user_id' => null,
            'action' => 'ANONYMIZE_DATA_TYPE',
            'entity_type' => $dataType,
            'entity_id' => $employeeId,
            'description' => sprintf('Anonimização de dados do tipo %s para funcionário #%d', $dataType, $employeeId),
            'level' => 'warning',
        ]);

        return [
            'success' => true,
            'message' => sprintf('%d registros de %s anonimizados', $count, $dataType),
            'anonymized_count' => $count,
        ];
    }

    /**
     * Obter estatísticas de anonimizações
     *
     * @return array
     */
    public function getAnonymizationStatistics(): array
    {
        $db = \Config\Database::connect();

        // Total anonimizado
        $totalAnonymized = $this->employeeModel
            ->where('anonymized_at IS NOT NULL')
            ->countAllResults();

        // Agendados para anonimização
        $scheduled = $this->employeeModel
            ->where('scheduled_anonymization_date IS NOT NULL')
            ->where('anonymized_at', null)
            ->countAllResults();

        // Anonimizações nos últimos 30 dias
        $recentAnonymizations = $this->employeeModel
            ->where('anonymized_at >=', date('Y-m-d', strtotime('-30 days')))
            ->countAllResults();

        // Próximas anonimizações (próximos 30 dias)
        $upcomingAnonymizations = $this->employeeModel
            ->where('scheduled_anonymization_date >=', date('Y-m-d'))
            ->where('scheduled_anonymization_date <=', date('Y-m-d', strtotime('+30 days')))
            ->where('anonymized_at', null)
            ->countAllResults();

        return [
            'total_anonymized' => $totalAnonymized,
            'scheduled' => $scheduled,
            'recent_anonymizations' => $recentAnonymizations,
            'upcoming_anonymizations' => $upcomingAnonymizations,
        ];
    }
}
