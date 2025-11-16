<?php

namespace App\Services;

use App\Models\EmployeeModel;
use App\Models\SettingModel;
use App\Models\AuditLogModel;
use CodeIgniter\Email\Email;

/**
 * EmailService
 *
 * Serviço dedicado para envio de e-mails
 * Separado de NotificationService para melhor modularidade (Fase 12)
 *
 * Funcionalidades:
 * - Envio de emails transacionais
 * - Templates configuráveis
 * - Fila de emails (opcional)
 * - Tracking de envios
 * - Suporte a SMTP configurável
 */
class EmailService
{
    protected $email;
    protected $employeeModel;
    protected $settingModel;
    protected $auditModel;

    protected $fromEmail;
    protected $fromName;
    protected $smtpEnabled;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->settingModel = new SettingModel();
        $this->auditModel = new AuditLogModel();

        // Initialize Email library
        $this->email = \Config\Services::email();

        // Load SMTP configuration from settings
        $this->loadEmailConfig();
    }

    /**
     * Load email configuration from settings
     */
    protected function loadEmailConfig(): void
    {
        $this->smtpEnabled = $this->settingModel->get('smtp_enabled', false);
        $this->fromEmail = $this->settingModel->get('smtp_from_email', 'noreply@empresa.com.br');
        $this->fromName = $this->settingModel->get('smtp_from_name', 'Sistema de Ponto');

        if ($this->smtpEnabled) {
            $config = [
                'protocol' => 'smtp',
                'SMTPHost' => $this->settingModel->get('smtp_host', 'localhost'),
                'SMTPPort' => $this->settingModel->get('smtp_port', 587),
                'SMTPUser' => $this->settingModel->get('smtp_user', ''),
                'SMTPPass' => $this->settingModel->get('smtp_pass', ''),
                'SMTPCrypto' => $this->settingModel->get('smtp_encryption', 'tls'),
                'mailType' => 'html',
                'charset' => 'utf-8',
                'newline' => "\r\n",
            ];

            $this->email->initialize($config);
        }
    }

    /**
     * Send email
     *
     * @param string|array $to Recipient email(s)
     * @param string $subject Email subject
     * @param string $message Email body (HTML)
     * @param array $options Optional parameters (cc, bcc, attachments, etc)
     * @return bool Success status
     */
    public function send($to, string $subject, string $message, array $options = []): bool
    {
        try {
            // Set sender
            $this->email->setFrom($this->fromEmail, $this->fromName);

            // Set recipient(s)
            if (is_array($to)) {
                $this->email->setTo($to);
            } else {
                $this->email->setTo($to);
            }

            // Set subject
            $this->email->setSubject($subject);

            // Set message
            $this->email->setMessage($message);

            // Optional: CC
            if (isset($options['cc'])) {
                $this->email->setCC($options['cc']);
            }

            // Optional: BCC
            if (isset($options['bcc'])) {
                $this->email->setBCC($options['bcc']);
            }

            // Optional: Reply-To
            if (isset($options['reply_to'])) {
                $this->email->setReplyTo($options['reply_to']);
            }

            // Optional: Attachments
            if (isset($options['attachments']) && is_array($options['attachments'])) {
                foreach ($options['attachments'] as $attachment) {
                    $this->email->attach($attachment);
                }
            }

            // Send email
            $sent = $this->email->send();

            // Log email send attempt
            $this->logEmailSend($to, $subject, $sent, $this->email->printDebugger(['headers']));

            // Clear email data for next send
            $this->email->clear();

            return $sent;

        } catch (\Exception $e) {
            log_message('error', 'Email send failed: ' . $e->getMessage());
            $this->logEmailSend($to, $subject, false, $e->getMessage());

            // Clear email data
            $this->email->clear();

            return false;
        }
    }

    /**
     * Send email to employee
     *
     * @param int $employeeId Employee ID
     * @param string $subject Email subject
     * @param string $message Email body
     * @param array $options Optional parameters
     * @return bool
     */
    public function sendToEmployee(int $employeeId, string $subject, string $message, array $options = []): bool
    {
        $employee = $this->employeeModel->find($employeeId);

        if (!$employee || empty($employee->email)) {
            log_message('warning', "Cannot send email to employee #{$employeeId}: no valid email");
            return false;
        }

        return $this->send($employee->email, $subject, $message, $options);
    }

    /**
     * Send email to multiple employees
     *
     * @param array $employeeIds Employee IDs
     * @param string $subject Email subject
     * @param string $message Email body
     * @param array $options Optional parameters
     * @return int Count of successfully sent emails
     */
    public function sendToEmployees(array $employeeIds, string $subject, string $message, array $options = []): int
    {
        $sent = 0;

        foreach ($employeeIds as $employeeId) {
            if ($this->sendToEmployee($employeeId, $subject, $message, $options)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Send email to all admins
     *
     * @param string $subject Email subject
     * @param string $message Email body
     * @param array $options Optional parameters
     * @return int Count of successfully sent emails
     */
    public function sendToAdmins(string $subject, string $message, array $options = []): int
    {
        $admins = $this->employeeModel
            ->where('role', 'admin')
            ->where('active', true)
            ->findAll();

        $emails = array_map(fn($admin) => $admin->email, $admins);
        $emails = array_filter($emails); // Remove empty emails

        if (empty($emails)) {
            return 0;
        }

        return $this->send($emails, $subject, $message, $options) ? count($emails) : 0;
    }

    /**
     * Send welcome email to new employee
     *
     * @param int $employeeId Employee ID
     * @param string $temporaryPassword Temporary password
     * @return bool
     */
    public function sendWelcomeEmail(int $employeeId, string $temporaryPassword): bool
    {
        $employee = $this->employeeModel->find($employeeId);

        if (!$employee) {
            return false;
        }

        $subject = 'Bem-vindo ao Sistema de Ponto Eletrônico';

        $message = $this->renderTemplate('welcome', [
            'employee_name' => $employee->name,
            'email' => $employee->email,
            'temporary_password' => $temporaryPassword,
            'login_url' => base_url('auth/login'),
            'company_name' => $this->settingModel->get('company_name', 'Empresa'),
        ]);

        return $this->sendToEmployee($employeeId, $subject, $message);
    }

    /**
     * Send password reset email
     *
     * @param int $employeeId Employee ID
     * @param string $resetToken Reset token
     * @return bool
     */
    public function sendPasswordResetEmail(int $employeeId, string $resetToken): bool
    {
        $employee = $this->employeeModel->find($employeeId);

        if (!$employee) {
            return false;
        }

        $subject = 'Redefinição de Senha - Sistema de Ponto';

        $resetUrl = base_url("auth/reset-password/{$resetToken}");

        $message = $this->renderTemplate('password_reset', [
            'employee_name' => $employee->name,
            'reset_url' => $resetUrl,
            'expires_in' => '24 horas',
        ]);

        return $this->sendToEmployee($employeeId, $subject, $message);
    }

    /**
     * Send punch receipt email
     *
     * @param int $employeeId Employee ID
     * @param array $punchData Punch data
     * @param string|null $pdfPath Optional PDF attachment path
     * @return bool
     */
    public function sendPunchReceipt(int $employeeId, array $punchData, ?string $pdfPath = null): bool
    {
        $employee = $this->employeeModel->find($employeeId);

        if (!$employee) {
            return false;
        }

        $subject = 'Comprovante de Registro de Ponto';

        $message = $this->renderTemplate('punch_receipt', [
            'employee_name' => $employee->name,
            'punch_time' => $punchData['punch_time'],
            'punch_type' => $punchData['punch_type'],
            'nsr' => $punchData['nsr'],
            'hash' => $punchData['hash'] ?? '',
        ]);

        $options = [];
        if ($pdfPath && file_exists($pdfPath)) {
            $options['attachments'] = [$pdfPath];
        }

        return $this->sendToEmployee($employeeId, $subject, $message, $options);
    }

    /**
     * Send justification status email
     *
     * @param int $employeeId Employee ID
     * @param string $status Status (approved|rejected)
     * @param string|null $reason Approval/rejection reason
     * @return bool
     */
    public function sendJustificationStatus(int $employeeId, string $status, ?string $reason = null): bool
    {
        $employee = $this->employeeModel->find($employeeId);

        if (!$employee) {
            return false;
        }

        $subject = $status === 'approved'
            ? 'Justificativa Aprovada'
            : 'Justificativa Rejeitada';

        $message = $this->renderTemplate('justification_status', [
            'employee_name' => $employee->name,
            'status' => $status,
            'reason' => $reason,
        ]);

        return $this->sendToEmployee($employeeId, $subject, $message);
    }

    /**
     * Send monthly timesheet email
     *
     * @param int $employeeId Employee ID
     * @param string $month Month (YYYY-MM)
     * @param string|null $pdfPath Optional PDF attachment path
     * @return bool
     */
    public function sendMonthlyTimesheet(int $employeeId, string $month, ?string $pdfPath = null): bool
    {
        $employee = $this->employeeModel->find($employeeId);

        if (!$employee) {
            return false;
        }

        $formattedMonth = date('m/Y', strtotime($month . '-01'));
        $subject = "Espelho de Ponto - {$formattedMonth}";

        $message = $this->renderTemplate('monthly_timesheet', [
            'employee_name' => $employee->name,
            'month' => $formattedMonth,
            'download_url' => base_url("reports/timesheet/{$month}"),
        ]);

        $options = [];
        if ($pdfPath && file_exists($pdfPath)) {
            $options['attachments'] = [$pdfPath];
        }

        return $this->sendToEmployee($employeeId, $subject, $message, $options);
    }

    /**
     * Send warning notification email
     *
     * @param int $employeeId Employee ID
     * @param string $warningType Warning type
     * @param int $warningId Warning ID
     * @return bool
     */
    public function sendWarningNotification(int $employeeId, string $warningType, int $warningId): bool
    {
        $employee = $this->employeeModel->find($employeeId);

        if (!$employee) {
            return false;
        }

        $subject = 'Advertência Recebida - Assinatura Necessária';

        $message = $this->renderTemplate('warning_notification', [
            'employee_name' => $employee->name,
            'warning_type' => $warningType,
            'sign_url' => base_url("warnings/sign/{$warningId}"),
        ]);

        return $this->sendToEmployee($employeeId, $subject, $message);
    }

    /**
     * Send reminder email
     *
     * @param int $employeeId Employee ID
     * @param string $subject Email subject
     * @param string $reminderMessage Reminder message
     * @return bool
     */
    public function sendReminder(int $employeeId, string $subject, string $reminderMessage): bool
    {
        $employee = $this->employeeModel->find($employeeId);

        if (!$employee) {
            return false;
        }

        $message = $this->renderTemplate('reminder', [
            'employee_name' => $employee->name,
            'reminder_message' => $reminderMessage,
        ]);

        return $this->sendToEmployee($employeeId, $subject, $message);
    }

    /**
     * Render email template
     *
     * @param string $templateName Template name
     * @param array $data Template data
     * @return string Rendered HTML
     */
    protected function renderTemplate(string $templateName, array $data = []): string
    {
        $templatePath = APPPATH . "Views/emails/{$templateName}.php";

        // Check if custom template exists
        if (file_exists($templatePath)) {
            return view("emails/{$templateName}", $data);
        }

        // Fallback to basic template
        return $this->getBasicTemplate($templateName, $data);
    }

    /**
     * Get basic email template (fallback)
     *
     * @param string $templateName Template name
     * @param array $data Template data
     * @return string HTML
     */
    protected function getBasicTemplate(string $templateName, array $data): string
    {
        $companyName = $this->settingModel->get('company_name', 'Empresa');
        $primaryColor = $this->settingModel->get('primary_color', '#667eea');

        $html = '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($templateName) . '</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: ' . $primaryColor . '; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f4f4f4; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
        .button { display: inline-block; padding: 10px 20px; background-color: ' . $primaryColor . '; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>' . htmlspecialchars($companyName) . '</h1>
            <p>Sistema de Ponto Eletrônico</p>
        </div>
        <div class="content">';

        // Template-specific content
        switch ($templateName) {
            case 'welcome':
                $html .= '<h2>Bem-vindo, ' . htmlspecialchars($data['employee_name']) . '!</h2>';
                $html .= '<p>Sua conta foi criada com sucesso.</p>';
                $html .= '<p><strong>E-mail:</strong> ' . htmlspecialchars($data['email']) . '</p>';
                $html .= '<p><strong>Senha Temporária:</strong> ' . htmlspecialchars($data['temporary_password']) . '</p>';
                $html .= '<p><a href="' . $data['login_url'] . '" class="button">Fazer Login</a></p>';
                $html .= '<p><small>Por favor, altere sua senha após o primeiro login.</small></p>';
                break;

            case 'punch_receipt':
                $html .= '<h2>Comprovante de Registro de Ponto</h2>';
                $html .= '<p>Olá, ' . htmlspecialchars($data['employee_name']) . '</p>';
                $html .= '<p>Seu registro de ponto foi registrado com sucesso:</p>';
                $html .= '<ul>';
                $html .= '<li><strong>Data/Hora:</strong> ' . htmlspecialchars($data['punch_time']) . '</li>';
                $html .= '<li><strong>Tipo:</strong> ' . htmlspecialchars($data['punch_type']) . '</li>';
                $html .= '<li><strong>NSR:</strong> ' . htmlspecialchars($data['nsr']) . '</li>';
                $html .= '</ul>';
                break;

            default:
                $html .= '<p>' . htmlspecialchars(json_encode($data)) . '</p>';
        }

        $html .= '
        </div>
        <div class="footer">
            <p>&copy; ' . date('Y') . ' ' . htmlspecialchars($companyName) . '. Todos os direitos reservados.</p>
            <p><small>Este é um email automático. Por favor, não responda.</small></p>
        </div>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Log email send attempt
     *
     * @param string|array $to Recipient(s)
     * @param string $subject Subject
     * @param bool $success Success status
     * @param string|null $details Details/error message
     */
    protected function logEmailSend($to, string $subject, bool $success, ?string $details = null): void
    {
        $recipients = is_array($to) ? implode(', ', $to) : $to;

        $this->auditModel->insert([
            'user_id' => null,
            'action' => $success ? 'EMAIL_SENT' : 'EMAIL_FAILED',
            'entity_type' => 'emails',
            'entity_id' => null,
            'description' => sprintf(
                'Email %s - Para: %s - Assunto: %s',
                $success ? 'enviado' : 'falhou',
                $recipients,
                $subject
            ),
            'old_values' => null,
            'new_values' => json_encode([
                'to' => $recipients,
                'subject' => $subject,
                'success' => $success,
                'details' => $details,
            ]),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'System',
            'level' => $success ? 'info' : 'error',
        ]);
    }

    /**
     * Test email configuration
     *
     * @param string $testEmail Test recipient email
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConfiguration(string $testEmail): array
    {
        $subject = 'Teste de Configuração de E-mail';
        $message = '<h2>Teste de E-mail</h2><p>Se você recebeu este e-mail, a configuração está correta!</p>';

        $sent = $this->send($testEmail, $subject, $message);

        return [
            'success' => $sent,
            'message' => $sent
                ? 'E-mail de teste enviado com sucesso!'
                : 'Falha ao enviar e-mail de teste. Verifique as configurações SMTP.',
            'debug' => $this->email->printDebugger(),
        ];
    }

    /**
     * Get email send statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $db = \Config\Database::connect();

        $totalSent = $db->table('audit_logs')
            ->where('action', 'EMAIL_SENT')
            ->countAllResults();

        $totalFailed = $db->table('audit_logs')
            ->where('action', 'EMAIL_FAILED')
            ->countAllResults();

        $sentToday = $db->table('audit_logs')
            ->where('action', 'EMAIL_SENT')
            ->where('DATE(created_at)', date('Y-m-d'))
            ->countAllResults();

        return [
            'total_sent' => $totalSent,
            'total_failed' => $totalFailed,
            'sent_today' => $sentToday,
            'success_rate' => $totalSent > 0
                ? round(($totalSent / ($totalSent + $totalFailed)) * 100, 2)
                : 0,
        ];
    }
}
