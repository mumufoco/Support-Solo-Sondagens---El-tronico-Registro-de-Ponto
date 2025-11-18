<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;
use App\Models\AuditLogModel;
use App\Models\UserConsentModel;
use App\Models\SettingModel;

class RegisterController extends BaseController
{
    protected $employeeModel;
    protected $auditModel;
    protected $consentModel;
    protected $settingModel;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->auditModel = new AuditLogModel();
        $this->consentModel = new UserConsentModel();
        $this->settingModel = new SettingModel();
    }

    /**
     * Display registration page (self-registration)
     */
    public function index()
    {
        // Check if self-registration is enabled
        $selfRegistrationEnabled = $this->settingModel->get('self_registration_enabled', false);

        if (!$selfRegistrationEnabled) {
            $this->setError('O auto-cadastro está desativado. Entre em contato com o administrador.');
            return redirect()->to('/auth/login');
        }

        // Redirect if already logged in
        if ($this->isAuthenticated()) {
            return redirect()->to('/dashboard');
        }

        return view('auth/register');
    }

    /**
     * Display employee creation form (manager/admin only)
     */
    public function create()
    {
        // Only managers can create employees
        $this->requireManager();

        return view('employees/create');
    }

    /**
     * Process self-registration
     */
    public function store()
    {
        // Check if self-registration is enabled
        $selfRegistrationEnabled = $this->settingModel->get('self_registration_enabled', false);

        if (!$selfRegistrationEnabled) {
            return $this->respondError('O auto-cadastro está desativado.', null, 403);
        }

        // Validate input
        $rules = [
            'name'         => 'required|min_length[3]|max_length[255]',
            'email'        => 'required|valid_email|is_unique[employees.email]',
            'cpf'          => 'required|exact_length[14]|is_unique[employees.cpf]',
            'password'     => 'required|min_length[12]|strong_password',  // SECURITY FIX: Minimum 12 characters
            'password_confirm' => 'required|matches[password]',
            'lgpd_consent' => 'required|in_list[1]',
            'terms_accepted' => 'required|in_list[1]',
        ];

        $errors = [
            'email' => [
                'is_unique' => 'Este e-mail já está cadastrado.',
            ],
            'cpf' => [
                'is_unique' => 'Este CPF já está cadastrado.',
            ],
            'password' => [
                'strong_password' => 'A senha deve conter pelo menos 8 caracteres, incluindo maiúsculas, minúsculas, números e caracteres especiais.',
            ],
            'password_confirm' => [
                'matches' => 'As senhas não coincidem.',
            ],
            'lgpd_consent' => [
                'required' => 'Você deve concordar com o tratamento de dados pessoais.',
            ],
            'terms_accepted' => [
                'required' => 'Você deve aceitar os termos de uso.',
            ],
        ];

        if (!$this->validate($rules, $errors)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Validate CPF format
        $cpf = $this->request->getPost('cpf');
        if (!$this->validateCPF($cpf)) {
            $this->setError('CPF inválido.');
            return redirect()->back()->withInput();
        }

        // Prepare employee data
        $data = [
            'name'       => $this->request->getPost('name'),
            'email'      => $this->request->getPost('email'),
            'cpf'        => $this->cleanCPF($cpf),
            'password'   => $this->request->getPost('password'),
            'role'       => 'funcionario', // Self-registration always creates funcionario
            'department' => $this->request->getPost('department'),
            'position'   => $this->request->getPost('position'),
            'phone'      => $this->request->getPost('phone'),
            'active'     => false, // Needs admin approval
        ];

        // Start transaction
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Create employee
            $employeeId = $this->employeeModel->insert($data);

            if (!$employeeId) {
                throw new \Exception('Erro ao criar funcionário.');
            }

            // Record LGPD consents
            $this->recordConsents($employeeId);

            // Log registration
            $this->auditModel->log(
                null,
                'EMPLOYEE_SELF_REGISTERED',
                'employees',
                $employeeId,
                null,
                ['name' => $data['name'], 'email' => $data['email']],
                "Auto-cadastro: {$data['name']} ({$data['email']})",
                'info'
            );

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Erro na transação.');
            }

            // Send notification to admin about pending approval
            $this->notifyAdminNewRegistration($employeeId);

            $this->setSuccess('Cadastro realizado com sucesso! Aguarde a aprovação do administrador.');

            return redirect()->to('/auth/login');

        } catch (\Exception $e) {
            $db->transRollback();

            log_message('error', 'Registration error: ' . $e->getMessage());

            $this->setError('Erro ao realizar cadastro. Tente novamente.');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Process employee creation by manager/admin
     */
    public function storeByManager()
    {
        // Only managers can create employees
        $this->requireManager();

        // Validate input
        $rules = [
            'name'       => 'required|min_length[3]|max_length[255]',
            'email'      => 'required|valid_email|is_unique[employees.email]',
            'cpf'        => 'required|exact_length[14]|is_unique[employees.cpf]',
            'password'   => 'required|min_length[12]|strong_password',  // SECURITY FIX: Manager must create strong passwords too
            'role'       => 'required|in_list[admin,gestor,funcionario]',
            'department' => 'required|min_length[2]',
            'position'   => 'required|min_length[2]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Validate CPF
        $cpf = $this->request->getPost('cpf');
        if (!$this->validateCPF($cpf)) {
            $this->setError('CPF inválido.');
            return redirect()->back()->withInput();
        }

        // Check if trying to create admin and user is not admin
        $role = $this->request->getPost('role');
        if ($role === 'admin' && !$this->hasRole('admin')) {
            $this->setError('Apenas administradores podem criar outros administradores.');
            return redirect()->back()->withInput();
        }

        // Prepare employee data
        $data = [
            'name'                 => $this->request->getPost('name'),
            'email'                => $this->request->getPost('email'),
            'cpf'                  => $this->cleanCPF($cpf),
            'password'             => $this->request->getPost('password'),
            'role'                 => $role,
            'department'           => $this->request->getPost('department'),
            'position'             => $this->request->getPost('position'),
            'phone'                => $this->request->getPost('phone'),
            'admission_date'       => $this->request->getPost('admission_date'),
            'daily_hours'          => $this->request->getPost('daily_hours') ?: 8.00,
            'weekly_hours'         => $this->request->getPost('weekly_hours') ?: 44.00,
            'work_start_time'      => $this->request->getPost('work_start_time') ?: '08:00:00',
            'work_end_time'        => $this->request->getPost('work_end_time') ?: '18:00:00',
            'lunch_start_time'     => $this->request->getPost('lunch_start_time') ?: '12:00:00',
            'lunch_end_time'       => $this->request->getPost('lunch_end_time') ?: '13:00:00',
            'active'               => true, // Manager-created employees are active by default
        ];

        // Start transaction
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Create employee
            $employeeId = $this->employeeModel->insert($data);

            if (!$employeeId) {
                throw new \Exception('Erro ao criar funcionário.');
            }

            // Log creation
            $this->logAudit(
                'EMPLOYEE_CREATED',
                'employees',
                $employeeId,
                null,
                $data,
                "Funcionário criado por gestor: {$data['name']} ({$data['email']})"
            );

            $db->transComplete();

            if ($db->transStatus() === false) {
                throw new \Exception('Erro na transação.');
            }

            $this->setSuccess('Funcionário cadastrado com sucesso!');

            return redirect()->to('/employees/' . $employeeId);

        } catch (\Exception $e) {
            $db->transRollback();

            log_message('error', 'Employee creation error: ' . $e->getMessage());

            $this->setError('Erro ao cadastrar funcionário. Tente novamente.');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Validate CPF number
     */
    protected function validateCPF(string $cpf): bool
    {
        // Remove formatting
        $cpf = $this->cleanCPF($cpf);

        // Check if has 11 digits
        if (strlen($cpf) != 11) {
            return false;
        }

        // Check for known invalid CPFs
        $invalidCPFs = [
            '00000000000',
            '11111111111',
            '22222222222',
            '33333333333',
            '44444444444',
            '55555555555',
            '66666666666',
            '77777777777',
            '88888888888',
            '99999999999',
        ];

        if (in_array($cpf, $invalidCPFs)) {
            return false;
        }

        // Validate check digits
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }

        return true;
    }

    /**
     * Clean CPF (remove formatting)
     */
    protected function cleanCPF(string $cpf): string
    {
        return preg_replace('/[^0-9]/', '', $cpf);
    }

    /**
     * Record LGPD consents
     */
    protected function recordConsents(int $employeeId): void
    {
        $consents = [
            [
                'employee_id'   => $employeeId,
                'consent_type'  => 'biometric_data',
                'purpose'       => 'Registro de ponto eletrônico através de reconhecimento facial e biometria',
                'legal_basis'   => 'Consentimento (Art. 7º, I da LGPD)',
                'granted'       => true,
                'granted_at'    => date('Y-m-d H:i:s'),
                'ip_address'    => $this->getClientIp(),
                'consent_text'  => 'Autorizo o tratamento de meus dados biométricos (facial e digital) para fins de registro de ponto eletrônico.',
                'version'       => '1.0',
            ],
            [
                'employee_id'   => $employeeId,
                'consent_type'  => 'personal_data',
                'purpose'       => 'Gerenciamento de jornada de trabalho e cumprimento de obrigações trabalhistas',
                'legal_basis'   => 'Cumprimento de obrigação legal (Art. 7º, II da LGPD)',
                'granted'       => true,
                'granted_at'    => date('Y-m-d H:i:s'),
                'ip_address'    => $this->getClientIp(),
                'consent_text'  => 'Autorizo o tratamento de meus dados pessoais para fins trabalhistas conforme CLT Art. 74.',
                'version'       => '1.0',
            ],
            [
                'employee_id'   => $employeeId,
                'consent_type'  => 'geolocation',
                'purpose'       => 'Validação de local de registro de ponto',
                'legal_basis'   => 'Consentimento (Art. 7º, I da LGPD)',
                'granted'       => true,
                'granted_at'    => date('Y-m-d H:i:s'),
                'ip_address'    => $this->getClientIp(),
                'consent_text'  => 'Autorizo o uso da minha localização para validação do registro de ponto.',
                'version'       => '1.0',
            ],
        ];

        foreach ($consents as $consent) {
            $this->consentModel->insert($consent);
        }
    }

    /**
     * Notify admin about new registration
     */
    protected function notifyAdminNewRegistration(int $employeeId): void
    {
        // Get employee data
        $employee = $this->employeeModel->find($employeeId);

        // Get all admins
        $admins = $this->employeeModel->where('role', 'admin')
            ->where('active', true)
            ->findAll();

        // Create notification for each admin
        $notificationModel = new \App\Models\NotificationModel();

        foreach ($admins as $admin) {
            $notificationModel->insert([
                'employee_id' => $admin->id,
                'title'       => 'Novo cadastro pendente de aprovação',
                'message'     => "O funcionário {$employee->name} ({$employee->email}) solicitou cadastro no sistema.",
                'type'        => 'employee_registration',
                'link'        => '/employees/pending',
                'read'        => false,
            ]);
        }
    }
}
