<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;
use App\Models\SettingModel;
use App\Models\AuditLogModel;

/**
 * Settings Controller
 *
 * Handles system configuration and settings (Admin only)
 */
class SettingsController extends BaseController
{
    protected $employeeModel;
    protected $settingModel;
    protected $auditModel;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->settingModel = new SettingModel();
        $this->auditModel = new AuditLogModel();
        helper(['form']);
    }

    /**
     * Settings dashboard
     * GET /settings
     */
    public function index()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')
                ->with('error', 'Acesso negado. Apenas administradores podem acessar configurações.');
        }

        // Get all settings grouped by category
        $settings = $this->settingModel->findAll();
        $groupedSettings = [];

        foreach ($settings as $setting) {
            $category = $setting->category ?? 'general';
            if (!isset($groupedSettings[$category])) {
                $groupedSettings[$category] = [];
            }
            $groupedSettings[$category][] = $setting;
        }

        return view('settings/index', [
            'employee' => $employee,
            'groupedSettings' => $groupedSettings,
        ]);
    }

    /**
     * Update setting
     * POST /settings/update
     */
    public function update()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        $key = $this->request->getPost('key');
        $value = $this->request->getPost('value');

        if (empty($key)) {
            return redirect()->back()->with('error', 'Chave da configuração é obrigatória.');
        }

        $setting = $this->settingModel->where('key', $key)->first();

        if (!$setting) {
            return redirect()->back()->with('error', 'Configuração não encontrada.');
        }

        $oldValue = $setting->value;

        // Update setting
        $this->settingModel->update($setting->id, ['value' => $value]);

        // Log change
        $this->auditModel->log(
            $employee['id'],
            'SETTING_UPDATED',
            'settings',
            $setting->id,
            ['old_value' => $oldValue],
            ['new_value' => $value],
            "Configuração '{$key}' atualizada de '{$oldValue}' para '{$value}'",
            'info'
        );

        return redirect()->back()->with('success', 'Configuração atualizada com sucesso.');
    }

    /**
     * Company information settings
     * GET /settings/company
     */
    public function company()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        $companySettings = [
            'company_name' => $this->settingModel->get('company_name', 'Empresa Exemplo LTDA'),
            'company_cnpj' => $this->settingModel->get('company_cnpj', '00.000.000/0001-00'),
            'company_address' => $this->settingModel->get('company_address', ''),
            'company_phone' => $this->settingModel->get('company_phone', ''),
            'company_email' => $this->settingModel->get('company_email', ''),
        ];

        return view('settings/company', [
            'employee' => $employee,
            'companySettings' => $companySettings,
        ]);
    }

    /**
     * Update company information
     * POST /settings/company
     */
    public function updateCompany()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        $rules = [
            'company_name' => 'required|min_length[3]|max_length[255]',
            'company_cnpj' => 'required|valid_cnpj',
            'company_address' => 'permit_empty|max_length[500]',
            'company_phone' => 'permit_empty|valid_phone_br',
            'company_email' => 'permit_empty|valid_email',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Update each setting
        $settings = [
            'company_name',
            'company_cnpj',
            'company_address',
            'company_phone',
            'company_email',
        ];

        foreach ($settings as $key) {
            $value = $this->request->getPost($key);
            $this->settingModel->set($key, $value);
        }

        // Log change
        $this->auditModel->log(
            $employee['id'],
            'COMPANY_SETTINGS_UPDATED',
            'settings',
            null,
            null,
            $this->request->getPost(),
            'Informações da empresa atualizadas',
            'info'
        );

        return redirect()->back()->with('success', 'Informações da empresa atualizadas com sucesso.');
    }

    /**
     * Work schedule settings
     * GET /settings/work-schedule
     */
    public function workSchedule()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        $scheduleSettings = [
            'default_daily_hours' => $this->settingModel->get('default_daily_hours', '8'),
            'default_weekly_hours' => $this->settingModel->get('default_weekly_hours', '44'),
            'work_start_time' => $this->settingModel->get('work_start_time', '08:00'),
            'work_end_time' => $this->settingModel->get('work_end_time', '17:00'),
            'lunch_start_time' => $this->settingModel->get('lunch_start_time', '12:00'),
            'lunch_end_time' => $this->settingModel->get('lunch_end_time', '13:00'),
            'tolerance_minutes' => $this->settingModel->get('tolerance_minutes', '10'),
        ];

        return view('settings/work_schedule', [
            'employee' => $employee,
            'scheduleSettings' => $scheduleSettings,
        ]);
    }

    /**
     * Update work schedule
     * POST /settings/work-schedule
     */
    public function updateWorkSchedule()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        $rules = [
            'default_daily_hours' => 'required|numeric|greater_than[0]',
            'default_weekly_hours' => 'required|numeric|greater_than[0]',
            'work_start_time' => 'required|valid_time',
            'work_end_time' => 'required|valid_time',
            'lunch_start_time' => 'required|valid_time',
            'lunch_end_time' => 'required|valid_time',
            'tolerance_minutes' => 'required|numeric|greater_than_equal_to[0]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Update each setting
        $settings = [
            'default_daily_hours',
            'default_weekly_hours',
            'work_start_time',
            'work_end_time',
            'lunch_start_time',
            'lunch_end_time',
            'tolerance_minutes',
        ];

        foreach ($settings as $key) {
            $value = $this->request->getPost($key);
            $this->settingModel->set($key, $value);
        }

        // Log change
        $this->auditModel->log(
            $employee['id'],
            'WORK_SCHEDULE_UPDATED',
            'settings',
            null,
            null,
            $this->request->getPost(),
            'Configurações de horário de trabalho atualizadas',
            'info'
        );

        return redirect()->back()->with('success', 'Horário de trabalho atualizado com sucesso.');
    }

    /**
     * Security settings
     * GET /settings/security
     */
    public function security()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        $securitySettings = [
            'require_strong_password' => $this->settingModel->get('require_strong_password', 'true'),
            'session_timeout' => $this->settingModel->get('session_timeout', '7200'),
            'max_login_attempts' => $this->settingModel->get('max_login_attempts', '5'),
            'login_lockout_duration' => $this->settingModel->get('login_lockout_duration', '300'),
            'enable_two_factor' => $this->settingModel->get('enable_two_factor', 'false'),
        ];

        return view('settings/security', [
            'employee' => $employee,
            'securitySettings' => $securitySettings,
        ]);
    }

    /**
     * Update security settings
     * POST /settings/security
     */
    public function updateSecurity()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        // Update each setting
        $settings = [
            'require_strong_password' => $this->request->getPost('require_strong_password') ? 'true' : 'false',
            'session_timeout' => $this->request->getPost('session_timeout'),
            'max_login_attempts' => $this->request->getPost('max_login_attempts'),
            'login_lockout_duration' => $this->request->getPost('login_lockout_duration'),
            'enable_two_factor' => $this->request->getPost('enable_two_factor') ? 'true' : 'false',
        ];

        foreach ($settings as $key => $value) {
            $this->settingModel->set($key, $value);
        }

        // Log change
        $this->auditModel->log(
            $employee['id'],
            'SECURITY_SETTINGS_UPDATED',
            'settings',
            null,
            null,
            $settings,
            'Configurações de segurança atualizadas',
            'warning'
        );

        return redirect()->back()->with('success', 'Configurações de segurança atualizadas com sucesso.');
    }

    /**
     * Email settings
     * GET /settings/email
     */
    public function email()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        $emailSettings = [
            'email_from_name' => $this->settingModel->get('email_from_name', 'Sistema Ponto Eletrônico'),
            'email_from_address' => $this->settingModel->get('email_from_address', 'noreply@pontoeletronico.com.br'),
            'smtp_host' => $this->settingModel->get('smtp_host', 'smtp.gmail.com'),
            'smtp_port' => $this->settingModel->get('smtp_port', '587'),
            'smtp_username' => $this->settingModel->get('smtp_username', ''),
            'smtp_encryption' => $this->settingModel->get('smtp_encryption', 'tls'),
        ];

        return view('settings/email', [
            'employee' => $employee,
            'emailSettings' => $emailSettings,
        ]);
    }

    /**
     * Update email settings
     * POST /settings/email
     */
    public function updateEmail()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        $rules = [
            'email_from_name' => 'required|min_length[3]',
            'email_from_address' => 'required|valid_email',
            'smtp_host' => 'required',
            'smtp_port' => 'required|numeric',
            'smtp_username' => 'required|valid_email',
            'smtp_password' => 'permit_empty',
            'smtp_encryption' => 'required|in_list[tls,ssl]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Update settings (excluding password if empty)
        $settings = [
            'email_from_name',
            'email_from_address',
            'smtp_host',
            'smtp_port',
            'smtp_username',
            'smtp_encryption',
        ];

        foreach ($settings as $key) {
            $value = $this->request->getPost($key);
            $this->settingModel->set($key, $value);
        }

        // Update password only if provided
        $password = $this->request->getPost('smtp_password');
        if (!empty($password)) {
            $this->settingModel->set('smtp_password', $password);
        }

        // Log change (without password)
        $this->auditModel->log(
            $employee['id'],
            'EMAIL_SETTINGS_UPDATED',
            'settings',
            null,
            null,
            array_filter($this->request->getPost(), fn($k) => $k !== 'smtp_password', ARRAY_FILTER_USE_KEY),
            'Configurações de email atualizadas',
            'info'
        );

        return redirect()->back()->with('success', 'Configurações de email atualizadas com sucesso.');
    }

    /**
     * Test email configuration
     * POST /settings/email/test
     */
    public function testEmail()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        $email = \Config\Services::email();

        $testTo = $this->request->getPost('test_email') ?: $employee['email'];

        $email->setTo($testTo);
        $email->setSubject('Teste de Configuração de Email - Ponto Eletrônico');
        $email->setMessage('Este é um email de teste do Sistema de Ponto Eletrônico. Se você recebeu esta mensagem, a configuração de email está funcionando corretamente.');

        if ($email->send()) {
            return redirect()->back()->with('success', "Email de teste enviado para {$testTo} com sucesso!");
        } else {
            $error = $email->printDebugger(['headers', 'subject', 'body']);
            return redirect()->back()->with('error', 'Erro ao enviar email de teste: ' . $error);
        }
    }

    /**
     * Get authenticated employee from session
     */
    protected function getAuthenticatedEmployee(): ?array
    {
        if (!session()->has('employee_id')) {
            return null;
        }

        $employeeId = session()->get('employee_id');
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
