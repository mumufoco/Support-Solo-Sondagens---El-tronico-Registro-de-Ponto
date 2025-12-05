<?= $this->extend('layouts/modern') ?>

<?= $this->section('content') ?>

<?php
use App\Libraries\UI\ComponentBuilder;
use App\Libraries\UI\UIHelper;

$userName = session()->get('user_name') ?? 'Gestor';
?>

<!-- Welcome Section -->
<?= ComponentBuilder::card([
    'class' => 'mb-4',
    'content' => '
        <h2 style="margin: 0 0 8px 0; font-size: var(--font-size-2xl); color: var(--text-primary);">
            Olá, ' . esc($userName) . '! 👋
        </h2>
        <p style="margin: 0; color: var(--text-muted);">
            Aqui está um resumo da sua equipe e atividades pendentes.
        </p>
    '
]) ?>

<!-- Team Statistics -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-lg); margin-bottom: var(--spacing-xl);">

    <?= ComponentBuilder::statCard([
        'value' => $teamStats['total_employees'] ?? '0',
        'label' => 'Funcionários na Equipe',
        'icon' => 'fa-users',
        'color' => 'primary',
        'url' => base_url('employees')
    ]) ?>

    <?= ComponentBuilder::statCard([
        'value' => ($teamStats['attendance_rate'] ?? 0) . '%',
        'label' => 'Taxa de Presença Hoje',
        'icon' => 'fa-check-circle',
        'color' => 'success',
        'trend' => [
            'direction' => 'up',
            'value' => '+2%'
        ]
    ]) ?>

    <?= ComponentBuilder::statCard([
        'value' => $teamStats['pending_approvals'] ?? '0',
        'label' => 'Aprovações Pendentes',
        'icon' => 'fa-clock',
        'color' => 'warning',
        'url' => base_url('justifications')
    ]) ?>

    <?= ComponentBuilder::statCard([
        'value' => $teamStats['absent_today'] ?? '0',
        'label' => 'Ausências Hoje',
        'icon' => 'fa-user-slash',
        'color' => 'danger'
    ]) ?>

</div>

<!-- Main Content Grid -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--spacing-lg); margin-bottom: var(--spacing-xl);">

    <!-- Left Column: Pending Approvals -->
    <div>
        <?= ComponentBuilder::card([
            'title' => 'Justificativas Pendentes',
            'icon' => 'fa-clipboard-list',
            'content' => ComponentBuilder::table([
                'columns' => [
                    ['label' => 'Funcionário', 'key' => 'employee_name'],
                    ['label' => 'Tipo', 'key' => 'type', 'formatter' => function($value) {
                        $types = [
                            'absence' => 'Falta',
                            'late' => 'Atraso',
                            'early_leave' => 'Saída Antecipada',
                            'forgot_punch' => 'Esqueceu de Bater'
                        ];
                        return $types[$value] ?? $value;
                    }],
                    ['label' => 'Data', 'key' => 'date', 'formatter' => fn($v) => UIHelper::formatDate($v)],
                    ['label' => 'Enviado', 'key' => 'created_at', 'formatter' => fn($v) => UIHelper::timeAgo($v)],
                    ['label' => 'Ações', 'key' => 'id', 'class' => 'text-end', 'formatter' => function($id) {
                        return UIHelper::flex([
                            ComponentBuilder::button([
                                'text' => 'Aprovar',
                                'style' => 'success',
                                'size' => 'sm',
                                'url' => base_url("justifications/{$id}"),
                                'icon' => 'fa-check'
                            ]),
                            ComponentBuilder::button([
                                'text' => 'Rejeitar',
                                'style' => 'danger',
                                'size' => 'sm',
                                'url' => base_url("justifications/{$id}"),
                                'icon' => 'fa-times'
                            ])
                        ], 'end', 'center', 'sm');
                    }]
                ],
                'data' => $pendingJustifications ?? []
            ]),
            'actions' => ComponentBuilder::button([
                'text' => 'Ver Todas',
                'url' => base_url('justifications'),
                'style' => 'outline-primary',
                'size' => 'sm'
            ])
        ]) ?>
    </div>

    <!-- Right Column: Quick Actions & Alerts -->
    <div>
        <!-- Quick Actions -->
        <?= ComponentBuilder::card([
            'title' => 'Ações Rápidas',
            'icon' => 'fa-bolt',
            'class' => 'mb-4',
            'content' => '
                <div style="display: flex; flex-direction: column; gap: var(--spacing-sm);">
                    ' . ComponentBuilder::button([
                        'text' => 'Cadastrar Funcionário',
                        'icon' => 'fa-user-plus',
                        'url' => base_url('employees/create'),
                        'style' => 'primary',
                        'class' => 'w-100'
                    ]) . '
                    ' . ComponentBuilder::button([
                        'text' => 'Gerar Relatório',
                        'icon' => 'fa-file-pdf',
                        'url' => base_url('reports'),
                        'style' => 'outline-primary',
                        'class' => 'w-100'
                    ]) . '
                    ' . ComponentBuilder::button([
                        'text' => 'Escalas de Trabalho',
                        'icon' => 'fa-calendar',
                        'url' => base_url('schedules'),
                        'style' => 'outline-primary',
                        'class' => 'w-100'
                    ]) . '
                    ' . ComponentBuilder::button([
                        'text' => 'Advertências',
                        'icon' => 'fa-exclamation-triangle',
                        'url' => base_url('warnings'),
                        'style' => 'outline-warning',
                        'class' => 'w-100'
                    ]) . '
                </div>
            '
        ]) ?>

        <!-- Alerts -->
        <?php if (!empty($alerts)): ?>
            <?= ComponentBuilder::card([
                'title' => 'Alertas',
                'icon' => 'fa-bell',
                'content' => implode('', array_map(function($alert) {
                    return ComponentBuilder::alert([
                        'message' => $alert['message'],
                        'type' => $alert['type'],
                        'dismissible' => true
                    ]);
                }, $alerts))
            ]) ?>
        <?php endif; ?>
    </div>

</div>

<!-- Team Activity -->
<?= ComponentBuilder::card([
    'title' => 'Atividade Recente da Equipe',
    'icon' => 'fa-history',
    'content' => ComponentBuilder::table([
        'columns' => [
            ['label' => 'Funcionário', 'key' => 'employee_name', 'formatter' => function($name, $row) {
                return UIHelper::flex([
                    UIHelper::avatar($name, null, '32px'),
                    '<span style="font-weight: 500;">' . esc($name) . '</span>'
                ], 'start', 'center', 'sm');
            }],
            ['label' => 'Ação', 'key' => 'action'],
            ['label' => 'Hora', 'key' => 'timestamp', 'formatter' => fn($v) => UIHelper::formatDateTime($v)],
            ['label' => 'Status', 'key' => 'status', 'formatter' => function($status) {
                return UIHelper::statusBadge($status);
            }]
        ],
        'data' => $teamActivity ?? []
    ])
]) ?>

<?= $this->endSection() ?>
