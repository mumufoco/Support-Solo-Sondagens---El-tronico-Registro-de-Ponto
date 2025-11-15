# Fase 3: Autenticação e Perfis - Guia de Implementação

## Sistema de Ponto Eletrônico

Este guia documenta a implementação da Fase 3 conforme `plano_Inicial_R2` (Semana 5-6).

---

## 📋 Visão Geral

A Fase 3 implementa:
- ✅ Sistema de autenticação com CodeIgniter Shield
- ✅ 3 perfis de usuário (Admin, Gestor, Funcionário)
- ✅ Dashboards personalizados por perfil
- ✅ Sistema de permissões e filtros

---

## 🔐 Comando 3.1: Sistema de Autenticação

### Pré-requisito: Instalar CodeIgniter Shield

O Shield já está no `composer.json`. Para configurá-lo:

```bash
# 1. Instalar dependências (se ainda não fez)
composer install

# 2. Publicar configurações do Shield
php spark shield:setup

# 3. Executar migrations do Shield
php spark migrate --all
```

### Estrutura de Grupos (Roles)

O Shield usa uma tabela `auth_groups_users` para gerenciar perfis. Criar 3 grupos:

| ID | Group | Permissions | Descrição |
|----|-------|-------------|-----------|
| 1 | admin | * (all) | Administrador - acesso total |
| 2 | gestor | manage.employees, approve.justifications, view.reports | Gestor - gerencia equipe |
| 3 | funcionario | clock.inout, view.own.data | Funcionário - registra ponto |

### Seeders de Grupos

Criar `app/Database/Seeds/AuthGroupsSeeder.php`:

```php
<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AuthGroupsSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'name' => 'admin',
                'description' => 'Administrador - Acesso Total',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'gestor',
                'description' => 'Gestor - Gerencia Equipe',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'funcionario',
                'description' => 'Funcionário - Registro de Ponto',
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];

        $this->db->table('auth_groups')->insertBatch($data);

        // Permissions para cada grupo
        $permissions = [
            // Admin - todas permissões
            ['group' => 'admin', 'permission' => '*'],

            // Gestor
            ['group' => 'gestor', 'permission' => 'manage.employees'],
            ['group' => 'gestor', 'permission' => 'approve.justifications'],
            ['group' => 'gestor', 'permission' => 'view.reports'],
            ['group' => 'gestor', 'permission' => 'clock.inout'],

            // Funcionário
            ['group' => 'funcionario', 'permission' => 'clock.inout'],
            ['group' => 'funcionario', 'permission' => 'view.own.data'],
        ];

        foreach ($permissions as $perm) {
            $groupId = $this->db->table('auth_groups')
                ->where('name', $perm['group'])
                ->get()
                ->getRow()->id;

            $this->db->table('auth_permissions')->insert([
                'name' => $perm['permission'],
                'description' => 'Permission for ' . $perm['group'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $permId = $this->db->insertID();

            $this->db->table('auth_groups_permissions')->insert([
                'group_id' => $groupId,
                'permission_id' => $permId,
            ]);
        }
    }
}
```

### Controllers de Autenticação

#### 1. LoginController.php

```php
<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use CodeIgniter\Shield\Authentication\Authenticators\Session;

class LoginController extends BaseController
{
    protected $session;

    public function __construct()
    {
        $this->session = service('session');
    }

    public function index()
    {
        // Se já está logado, redirecionar para dashboard
        if (auth()->loggedIn()) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login');
    }

    public function attempt()
    {
        $rules = [
            'email' => 'required|valid_email',
            'password' => 'required|min_length[8]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $remember = $this->request->getPost('remember') ?? false;

        // Proteção brute force: verificar tentativas
        $throttle = service('throttle');
        $identifier = 'login_' . $this->request->getIPAddress();

        if ($throttle->check($identifier, 5, MINUTE) === false) {
            return redirect()->back()
                ->with('error', 'Muitas tentativas de login. Aguarde 15 minutos.');
        }

        $throttle->hit($identifier, MINUTE * 15);

        // Tentar autenticar
        $auth = auth();

        $credentials = [
            'email' => $email,
            'password' => $password,
        ];

        $result = $auth->attempt($credentials, $remember);

        if (!$result->isOK()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Credenciais inválidas.');
        }

        // Login bem-sucedido - regenerar session ID
        $this->session->regenerate();

        // Redirecionar para dashboard apropriado
        $user = auth()->user();

        if ($user->inGroup('admin')) {
            return redirect()->to('/admin/dashboard');
        } elseif ($user->inGroup('gestor')) {
            return redirect()->to('/gestor/dashboard');
        } else {
            return redirect()->to('/dashboard');
        }
    }

    public function logout()
    {
        auth()->logout();
        $this->session->destroy();

        return redirect()->to('/login')
            ->with('message', 'Logout realizado com sucesso.');
    }
}
```

#### 2. RegisterController.php

```php
<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;

class RegisterController extends BaseController
{
    public function index()
    {
        return view('auth/register');
    }

    public function create()
    {
        $rules = [
            'name' => 'required|min_length[3]|max_length[255]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'cpf' => 'required|exact_length[14]|is_unique[employees.cpf]|validate_cpf',
            'password' => 'required|min_length[8]|strong_password',
            'password_confirm' => 'required|matches[password]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = $this->request->getPost();

        // Criar usuário no Shield
        $users = auth()->getProvider();

        $user = new \CodeIgniter\Shield\Entities\User([
            'username' => $data['email'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $users->save($user);

        // Adicionar ao grupo 'funcionario' por padrão
        $user->addGroup('funcionario');

        // Criar registro de funcionário
        $employeeModel = new EmployeeModel();

        $employeeData = [
            'user_id' => $user->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'cpf' => $data['cpf'],
            'role' => 'funcionario',
            'active' => true,
            'unique_code' => $this->generateUniqueCode(),
        ];

        $employeeModel->insert($employeeData);

        return redirect()->to('/login')
            ->with('message', 'Cadastro realizado com sucesso! Faça login.');
    }

    private function generateUniqueCode(): string
    {
        $employeeModel = new EmployeeModel();

        do {
            $code = str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        } while ($employeeModel->where('unique_code', $code)->first());

        return $code;
    }
}
```

### Filtros de Autenticação

#### 1. AuthFilter.php

```php
<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!auth()->loggedIn()) {
            return redirect()->to('/login')
                ->with('error', 'Você precisa estar logado para acessar esta página.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Não faz nada no after
    }
}
```

#### 2. AdminFilter.php

```php
<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!auth()->loggedIn()) {
            return redirect()->to('/login');
        }

        $user = auth()->user();

        if (!$user->inGroup('admin')) {
            return redirect()->to('/dashboard')
                ->with('error', 'Acesso negado. Apenas administradores.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Não faz nada
    }
}
```

#### 3. ManagerFilter.php

```php
<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ManagerFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!auth()->loggedIn()) {
            return redirect()->to('/login');
        }

        $user = auth()->user();

        // Admin também pode acessar áreas de gestor
        if (!$user->inGroup('gestor') && !$user->inGroup('admin')) {
            return redirect()->to('/dashboard')
                ->with('error', 'Acesso negado. Apenas gestores.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Não faz nada
    }
}
```

### Configuração de Filtros (app/Config/Filters.php)

Adicionar os filtros no arquivo de configuração:

```php
public $aliases = [
    // ... outros filtros
    'auth' => \App\Filters\AuthFilter::class,
    'admin' => \App\Filters\AdminFilter::class,
    'manager' => \App\Filters\ManagerFilter::class,
];

public $filters = [
    'auth' => [
        'before' => [
            'dashboard/*',
            'admin/*',
            'gestor/*',
            'punch/*',
        ],
    ],
    'admin' => [
        'before' => [
            'admin/*',
        ],
    ],
    'manager' => [
        'before' => [
            'gestor/*',
        ],
    ],
];
```

### Validação Customizada para CPF

Adicionar em `app/Config/Validation.php`:

```php
<?php

namespace App\Config;

use CodeIgniter\Config\BaseConfig;

class Validation extends BaseConfig
{
    public $ruleSets = [
        \CodeIgniter\Validation\Rules::class,
        \CodeIgniter\Validation\FormatRules::class,
        \CodeIgniter\Validation\FileRules::class,
        \CodeIgniter\Validation\CreditCardRules::class,
        \App\Validation\CustomRules::class, // ADICIONAR
    ];

    // ...
}
```

Criar `app/Validation/CustomRules.php`:

```php
<?php

namespace App\Validation;

class CustomRules
{
    public function validate_cpf(?string $str = null): bool
    {
        if (empty($str)) {
            return false;
        }

        // Remove formatação
        $cpf = preg_replace('/[^0-9]/', '', $str);

        // Verifica se tem 11 dígitos
        if (strlen($cpf) != 11) {
            return false;
        }

        // Verifica se não é sequência repetida
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Validação dos dígitos verificadores
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

    public function strong_password(?string $str = null): bool
    {
        if (empty($str)) {
            return false;
        }

        // Mínimo 8 caracteres
        if (strlen($str) < 8) {
            return false;
        }

        // Pelo menos uma letra maiúscula
        if (!preg_match('/[A-Z]/', $str)) {
            return false;
        }

        // Pelo menos uma letra minúscula
        if (!preg_match('/[a-z]/', $str)) {
            return false;
        }

        // Pelo menos um número
        if (!preg_match('/[0-9]/', $str)) {
            return false;
        }

        // Pelo menos um caractere especial
        if (!preg_match('/[^A-Za-z0-9]/', $str)) {
            return false;
        }

        return true;
    }
}
```

---

## 📊 Comando 3.2: Dashboards por Perfil

### 1. AdminDashboard

Criar `app/Controllers/Admin/DashboardController.php`:

```php
<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;
use App\Models\TimePunchModel;
use App\Models\JustificationModel;
use App\Models\UserConsentModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $employeeModel = new EmployeeModel();
        $timePunchModel = new TimePunchModel();
        $justificationModel = new JustificationModel();
        $consentModel = new UserConsentModel();

        // Cards com totais
        $data = [
            'total_employees' => $employeeModel->where('active', true)->countAllResults(),
            'punches_today' => $timePunchModel->where('DATE(punch_time)', date('Y-m-d'))->countAllResults(),
            'pending_justifications' => $justificationModel->where('status', 'pending')->countAllResults(),
            'pending_consents' => $consentModel->where('consent_given', false)->countAllResults(),

            // Marcações últimos 7 dias (para gráfico)
            'punches_last_7_days' => $this->getPunchesLast7Days(),

            // Alertas
            'alerts' => $this->getAlerts(),
        ];

        return view('admin/dashboard', $data);
    }

    private function getPunchesLast7Days(): array
    {
        $timePunchModel = new TimePunchModel();
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $count = $timePunchModel->where('DATE(punch_time)', $date)->countAllResults();

            $data[] = [
                'date' => date('d/m', strtotime($date)),
                'count' => $count,
            ];
        }

        return $data;
    }

    private function getAlerts(): array
    {
        $alerts = [];

        // Exemplo: certificados expirando (implementar conforme necessário)
        // ...

        return $alerts;
    }
}
```

### 2. ManagerDashboard

Criar `app/Controllers/Gestor/DashboardController.php`:

```php
<?php

namespace App\Controllers\Gestor;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;
use App\Models\JustificationModel;
use App\Models\TimePunchModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $employeeModel = new EmployeeModel();
        $justificationModel = new JustificationModel();

        // Pegar funcionários da equipe (implementar lógica de hierarquia)
        $teamEmployees = $employeeModel->where('active', true)->findAll();

        $data = [
            'team_count' => count($teamEmployees),
            'team_employees' => $teamEmployees,

            // Justificativas pendentes
            'pending_justifications' => $justificationModel
                ->where('status', 'pending')
                ->orderBy('created_at', 'DESC')
                ->findAll(10),
        ];

        return view('gestor/dashboard', $data);
    }

    public function approveJustification($id)
    {
        $justificationModel = new JustificationModel();

        $justificationModel->update($id, [
            'status' => 'approved',
            'approved_by' => auth()->user()->id,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()
            ->with('message', 'Justificativa aprovada.');
    }

    public function rejectJustification($id)
    {
        $justificationModel = new JustificationModel();

        $justificationModel->update($id, [
            'status' => 'rejected',
            'approved_by' => auth()->user()->id,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()
            ->with('message', 'Justificativa rejeitada.');
    }
}
```

### 3. EmployeeDashboard

Criar `app/Controllers/DashboardController.php`:

```php
<?php

namespace App\Controllers;

use App\Models\TimePunchModel;
use App\Models\EmployeeModel;
use App\Models\SettingModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $user = auth()->user();
        $employeeModel = new EmployeeModel();
        $timePunchModel = new TimePunchModel();
        $settingModel = new SettingModel();

        // Buscar funcionário
        $employee = $employeeModel->where('user_id', $user->id)->first();

        // Verificar se pode bater ponto (dentro do horário)
        $canPunch = $this->canPunchNow();

        // Resumo do mês
        $monthSummary = $this->getMonthSummary($employee->id);

        // Últimas 10 marcações
        $recentPunches = $timePunchModel
            ->where('employee_id', $employee->id)
            ->orderBy('punch_time', 'DESC')
            ->findAll(10);

        $data = [
            'employee' => $employee,
            'can_punch' => $canPunch,
            'month_summary' => $monthSummary,
            'recent_punches' => $recentPunches,
        ];

        return view('dashboard/employee', $data);
    }

    private function canPunchNow(): bool
    {
        $settingModel = new SettingModel();

        $start = $settingModel->get('work_start_time', '08:00');
        $end = $settingModel->get('work_end_time', '18:00');
        $tolerance = (int)$settingModel->get('time_tolerance_minutes', 15);

        $now = date('H:i');
        $startWithTolerance = date('H:i', strtotime($start . " -$tolerance minutes"));
        $endWithTolerance = date('H:i', strtotime($end . " +$tolerance minutes"));

        return $now >= $startWithTolerance && $now <= $endWithTolerance;
    }

    private function getMonthSummary(int $employeeId): array
    {
        $timePunchModel = new TimePunchModel();

        $month = date('m');
        $year = date('Y');

        $punches = $timePunchModel
            ->where('employee_id', $employeeId)
            ->where('MONTH(punch_time)', $month)
            ->where('YEAR(punch_time)', $year)
            ->findAll();

        // Calcular horas trabalhadas (simplificado)
        $totalHours = count($punches) * 0.5; // Placeholder

        return [
            'worked_hours' => $totalHours,
            'expected_hours' => 160, // 8h x 20 dias
            'balance' => $totalHours - 160,
        ];
    }
}
```

---

## 🎨 Views (Templates Bootstrap 5)

### Login View (`app/Views/auth/login.php`)

```php
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Ponto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h3 class="text-center mb-4">Sistema de Ponto Eletrônico</h3>

                        <?php if (session()->has('error')): ?>
                            <div class="alert alert-danger"><?= session('error') ?></div>
                        <?php endif; ?>

                        <?php if (session()->has('message')): ?>
                            <div class="alert alert-success"><?= session('message') ?></div>
                        <?php endif; ?>

                        <form action="/login" method="POST">
                            <?= csrf_field() ?>

                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Senha</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Lembrar-me</label>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Entrar</button>
                        </form>

                        <div class="text-center mt-3">
                            <a href="/register">Criar conta</a> |
                            <a href="/forgot-password">Esqueci minha senha</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

---

## ✅ Checklist de Implementação

Marque conforme for implementando:

- [ ] Executar `php spark shield:setup`
- [ ] Executar migrations do Shield
- [ ] Criar seeder AuthGroupsSeeder
- [ ] Executar seeder: `php spark db:seed AuthGroupsSeeder`
- [ ] Criar LoginController
- [ ] Criar RegisterController
- [ ] Criar AuthFilter, AdminFilter, ManagerFilter
- [ ] Configurar filtros em `app/Config/Filters.php`
- [ ] Criar CustomRules (CPF, senha forte)
- [ ] Criar AdminDashboardController
- [ ] Criar GestorDashboardController
- [ ] Criar DashboardController (funcionário)
- [ ] Criar views de login e register
- [ ] Criar views dos 3 dashboards
- [ ] Testar login com diferentes perfis
- [ ] Testar filtros de acesso
- [ ] Testar fluxos de aprovação (gestor)

---

## 📚 Próximos Passos

Após concluir a Fase 3:
- **Fase 4**: Registro de Ponto Core (Semana 7-8)
- **Fase 5**: Registro por Código e QR (Semana 9)
- **Fase 6**: Integração Reconhecimento Facial (Semana 10-11)

---

**Data de Criação**: 2025-01-15
