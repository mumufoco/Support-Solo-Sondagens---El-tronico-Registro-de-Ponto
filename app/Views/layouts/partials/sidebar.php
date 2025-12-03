<?php
$userRole = session()->get('user_role') ?? 'employee';
$currentUri = uri_string();

// Define menu items based on role
$menuItems = [
    'admin' => [
        [
            'label' => 'Dashboard',
            'icon' => 'fa-chart-line',
            'url' => 'admin/dashboard',
            'active' => str_starts_with($currentUri, 'admin/dashboard')
        ],
        [
            'label' => 'Funcionários',
            'icon' => 'fa-users',
            'url' => 'admin/employees',
            'active' => str_starts_with($currentUri, 'admin/employees'),
            'submenu' => [
                ['label' => 'Listar', 'url' => 'admin/employees'],
                ['label' => 'Cadastrar', 'url' => 'admin/employees/create'],
                ['label' => 'Departamentos', 'url' => 'admin/departments'],
            ]
        ],
        [
            'label' => 'Ponto Eletrônico',
            'icon' => 'fa-clock',
            'url' => 'admin/timesheet',
            'active' => str_starts_with($currentUri, 'admin/timesheet'),
            'submenu' => [
                ['label' => 'Registros', 'url' => 'admin/timesheet'],
                ['label' => 'Aprovações', 'url' => 'admin/timesheet/approvals'],
                ['label' => 'Ajustes', 'url' => 'admin/timesheet/adjustments'],
            ]
        ],
        [
            'label' => 'Relatórios',
            'icon' => 'fa-file-chart-line',
            'url' => 'admin/reports',
            'active' => str_starts_with($currentUri, 'admin/reports'),
            'submenu' => [
                ['label' => 'Horas Trabalhadas', 'url' => 'admin/reports/hours'],
                ['label' => 'Banco de Horas', 'url' => 'admin/reports/bank'],
                ['label' => 'Ausências', 'url' => 'admin/reports/absences'],
                ['label' => 'Personalizado', 'url' => 'admin/reports/custom'],
            ]
        ],
        [
            'label' => 'LGPD',
            'icon' => 'fa-shield-halved',
            'url' => 'admin/lgpd',
            'active' => str_starts_with($currentUri, 'admin/lgpd'),
            'submenu' => [
                ['label' => 'Consentimentos', 'url' => 'admin/lgpd/consents'],
                ['label' => 'Solicitações', 'url' => 'admin/lgpd/requests'],
                ['label' => 'Logs de Acesso', 'url' => 'admin/lgpd/logs'],
            ]
        ],
        [
            'label' => 'Configurações',
            'icon' => 'fa-gear',
            'url' => 'admin/settings',
            'active' => str_starts_with($currentUri, 'admin/settings'),
            'submenu' => [
                ['label' => 'Aparência', 'url' => 'admin/settings/appearance'],
                ['label' => 'Sistema', 'url' => 'admin/settings/system'],
                ['label' => 'Autenticação', 'url' => 'admin/settings/authentication'],
                ['label' => 'Certificado Digital', 'url' => 'admin/settings/certificate'],
                ['label' => 'Segurança', 'url' => 'admin/settings/security'],
            ]
        ],
    ],
    'manager' => [
        [
            'label' => 'Dashboard',
            'icon' => 'fa-chart-line',
            'url' => 'manager/dashboard',
            'active' => str_starts_with($currentUri, 'manager/dashboard')
        ],
        [
            'label' => 'Minha Equipe',
            'icon' => 'fa-users',
            'url' => 'manager/team',
            'active' => str_starts_with($currentUri, 'manager/team')
        ],
        [
            'label' => 'Aprovações',
            'icon' => 'fa-check-double',
            'url' => 'manager/approvals',
            'active' => str_starts_with($currentUri, 'manager/approvals')
        ],
        [
            'label' => 'Relatórios',
            'icon' => 'fa-file-chart-line',
            'url' => 'manager/reports',
            'active' => str_starts_with($currentUri, 'manager/reports')
        ],
    ],
    'employee' => [
        [
            'label' => 'Dashboard',
            'icon' => 'fa-home',
            'url' => 'employee/dashboard',
            'active' => str_starts_with($currentUri, 'employee/dashboard')
        ],
        [
            'label' => 'Registrar Ponto',
            'icon' => 'fa-clock',
            'url' => 'employee/punch',
            'active' => str_starts_with($currentUri, 'employee/punch')
        ],
        [
            'label' => 'Meus Registros',
            'icon' => 'fa-list',
            'url' => 'employee/timesheet',
            'active' => str_starts_with($currentUri, 'employee/timesheet')
        ],
        [
            'label' => 'Banco de Horas',
            'icon' => 'fa-clock-rotate-left',
            'url' => 'employee/bank',
            'active' => str_starts_with($currentUri, 'employee/bank')
        ],
    ]
];

$menu = $menuItems[$userRole] ?? $menuItems['employee'];
?>

<aside class="sidebar" id="sidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="<?= base_url('assets/modern/images/logo.svg') ?>" alt="Logo" class="logo-full">
            <img src="<?= base_url('assets/modern/images/logo-icon.svg') ?>" alt="Logo" class="logo-icon">
        </div>
        <button type="button" class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <!-- Sidebar Search -->
    <div class="sidebar-search">
        <div class="search-input-wrapper">
            <i class="fas fa-search"></i>
            <input type="text" class="search-input" id="sidebarSearch" placeholder="Buscar menu...">
        </div>
    </div>

    <!-- Sidebar Navigation -->
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <?php foreach ($menu as $item): ?>
                <li class="nav-item <?= $item['active'] ? 'active' : '' ?> <?= isset($item['submenu']) ? 'has-submenu' : '' ?>">
                    <?php if (isset($item['submenu'])): ?>
                        <a href="#" class="nav-link" data-toggle="submenu">
                            <i class="nav-icon fas <?= $item['icon'] ?>"></i>
                            <span class="nav-text"><?= esc($item['label']) ?></span>
                            <i class="submenu-arrow fas fa-chevron-down"></i>
                        </a>
                        <ul class="submenu">
                            <?php foreach ($item['submenu'] as $subitem): ?>
                                <li class="submenu-item">
                                    <a href="<?= base_url($subitem['url']) ?>" class="submenu-link">
                                        <span><?= esc($subitem['label']) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <a href="<?= base_url($item['url']) ?>" class="nav-link">
                            <i class="nav-icon fas <?= $item['icon'] ?>"></i>
                            <span class="nav-text"><?= esc($item['label']) ?></span>
                        </a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-info">
                <div class="user-name"><?= esc(session()->get('user_name') ?? 'Usuário') ?></div>
                <div class="user-role"><?= esc(ucfirst($userRole)) ?></div>
            </div>
        </div>
    </div>
</aside>
