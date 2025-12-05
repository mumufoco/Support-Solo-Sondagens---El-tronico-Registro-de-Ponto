<?= $this->extend('layouts/modern') ?>

<?= $this->section('content') ?>

<?php
use App\Libraries\UI\ComponentBuilder;
use App\Libraries\UI\UIHelper;

$userName = session()->get('user_name') ?? 'Funcionário';
$currentStatus = $employeeData['current_status'] ?? 'clocked_out';
?>

<!-- Welcome Section with Quick Punch -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--spacing-lg); margin-bottom: var(--spacing-xl);">

    <!-- Welcome Message -->
    <?= ComponentBuilder::card([
        'content' => '
            <h2 style="margin: 0 0 8px 0; font-size: var(--font-size-2xl); color: var(--text-primary);">
                Bem-vindo, ' . esc($userName) . '! 👋
            </h2>
            <p style="margin: 0 8px 0 0; color: var(--text-muted);">
                ' . date('l, d \d\e F \d\e Y') . ' • ' . date('H:i') . '
            </p>
        '
    ]) ?>

    <!-- Quick Punch Card -->
    <?= ComponentBuilder::card([
        'class' => 'text-center',
        'content' => '
            <div style="padding: var(--spacing-md) 0;">
                ' . ComponentBuilder::button([
                    'text' => $currentStatus === 'clocked_in' ? 'Registrar Saída' : 'Registrar Entrada',
                    'icon' => $currentStatus === 'clocked_in' ? 'fa-sign-out-alt' : 'fa-sign-in-alt',
                    'style' => $currentStatus === 'clocked_in' ? 'danger' : 'success',
                    'url' => base_url('timesheet/punch'),
                    'class' => 'btn-lg w-100'
                ]) . '
                <p style="margin: var(--spacing-sm) 0 0 0; color: var(--text-muted); font-size: var(--font-size-sm);">
                    Status: ' . ComponentBuilder::badge([
                        'text' => $currentStatus === 'clocked_in' ? 'Trabalhando' : 'Fora do Expediente',
                        'style' => $currentStatus === 'clocked_in' ? 'success' : 'secondary'
                    ]) . '
                </p>
            </div>
        '
    ]) ?>

</div>

<!-- Personal Statistics -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-lg); margin-bottom: var(--spacing-xl);">

    <?= ComponentBuilder::statCard([
        'value' => $employeeStats['hours_worked_month'] ?? '0h',
        'label' => 'Horas Trabalhadas (Mês)',
        'icon' => 'fa-clock',
        'color' => 'primary'
    ]) ?>

    <?= ComponentBuilder::statCard([
        'value' => $employeeStats['balance_hours'] ?? '+0h',
        'label' => 'Banco de Horas',
        'icon' => 'fa-balance-scale',
        'color' => ($employeeStats['balance_hours_numeric'] ?? 0) >= 0 ? 'success' : 'warning',
        'url' => base_url('timesheet/balance')
    ]) ?>

    <?= ComponentBuilder::statCard([
        'value' => $employeeStats['attendance_rate'] ?? '100%',
        'label' => 'Taxa de Presença',
        'icon' => 'fa-chart-line',
        'color' => 'info'
    ]) ?>

    <?= ComponentBuilder::statCard([
        'value' => $employeeStats['pending_justifications'] ?? '0',
        'label' => 'Justificativas Pendentes',
        'icon' => 'fa-file-alt',
        'color' => 'warning',
        'url' => base_url('justifications')
    ]) ?>

</div>

<!-- Main Content Grid -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--spacing-lg); margin-bottom: var(--spacing-xl);">

    <!-- Left Column: Recent Punches -->
    <div>
        <?= ComponentBuilder::card([
            'title' => 'Registros de Hoje',
            'icon' => 'fa-list',
            'content' => ComponentBuilder::table([
                'columns' => [
                    ['label' => 'Tipo', 'key' => 'type', 'formatter' => function($type) {
                        $types = [
                            'clock_in' => ['text' => 'Entrada', 'icon' => 'fa-sign-in-alt', 'color' => 'success'],
                            'clock_out' => ['text' => 'Saída', 'icon' => 'fa-sign-out-alt', 'color' => 'danger'],
                            'break_start' => ['text' => 'Início Intervalo', 'icon' => 'fa-pause', 'color' => 'warning'],
                            'break_end' => ['text' => 'Fim Intervalo', 'icon' => 'fa-play', 'color' => 'info']
                        ];
                        $config = $types[$type] ?? ['text' => $type, 'icon' => 'fa-clock', 'color' => 'secondary'];
                        return UIHelper::flex([
                            '<i class="fas ' . $config['icon'] . ' text-' . $config['color'] . '"></i>',
                            '<span>' . $config['text'] . '</span>'
                        ], 'start', 'center', 'sm');
                    }],
                    ['label' => 'Horário', 'key' => 'timestamp', 'formatter' => fn($v) => UIHelper::formatDateTime($v, 'H:i')],
                    ['label' => 'Localização', 'key' => 'location', 'formatter' => function($location) {
                        return $location ? '<i class="fas fa-map-marker-alt"></i> ' . esc($location) : '-';
                    }],
                    ['label' => 'Status', 'key' => 'status', 'formatter' => function($status) {
                        return UIHelper::statusBadge($status);
                    }]
                ],
                'data' => $todayPunches ?? []
            ]),
            'actions' => ComponentBuilder::button([
                'text' => 'Ver Histórico Completo',
                'url' => base_url('timesheet/history'),
                'style' => 'outline-primary',
                'size' => 'sm',
                'icon' => 'fa-history'
            ])
        ]) ?>

        <!-- Weekly Summary -->
        <?= ComponentBuilder::card([
            'title' => 'Resumo Semanal',
            'icon' => 'fa-calendar-week',
            'class' => 'mt-4',
            'content' => '
                <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: var(--spacing-sm);">
                    ' . implode('', array_map(function($day) {
                        $isToday = $day['is_today'] ?? false;
                        $hasWork = !empty($day['hours']);

                        return '
                            <div style="
                                text-align: center;
                                padding: var(--spacing-sm);
                                border-radius: var(--radius-md);
                                background: ' . ($isToday ? 'var(--color-primary)' : 'var(--bg-surface)') . ';
                                color: ' . ($isToday ? 'white' : 'var(--text-primary)') . ';
                            ">
                                <div style="font-size: var(--font-size-xs); font-weight: 600; margin-bottom: 4px;">
                                    ' . $day['name'] . '
                                </div>
                                <div style="font-size: var(--font-size-lg); font-weight: 700;">
                                    ' . ($hasWork ? $day['hours'] : '-') . '
                                </div>
                            </div>
                        ';
                    }, $weekSummary ?? [
                        ['name' => 'Seg', 'hours' => '8h', 'is_today' => false],
                        ['name' => 'Ter', 'hours' => '8h', 'is_today' => false],
                        ['name' => 'Qua', 'hours' => '7h', 'is_today' => false],
                        ['name' => 'Qui', 'hours' => '8h', 'is_today' => false],
                        ['name' => 'Sex', 'hours' => '4h', 'is_today' => true],
                        ['name' => 'Sáb', 'hours' => '', 'is_today' => false],
                        ['name' => 'Dom', 'hours' => '', 'is_today' => false]
                    ])) . '
                </div>
            '
        ]) ?>
    </div>

    <!-- Right Column: Quick Actions & Notifications -->
    <div>
        <!-- Quick Actions -->
        <?= ComponentBuilder::card([
            'title' => 'Ações Rápidas',
            'icon' => 'fa-bolt',
            'class' => 'mb-4',
            'content' => '
                <div style="display: flex; flex-direction: column; gap: var(--spacing-sm);">
                    ' . ComponentBuilder::button([
                        'text' => 'Solicitar Justificativa',
                        'icon' => 'fa-file-alt',
                        'url' => base_url('justifications/create'),
                        'style' => 'primary',
                        'class' => 'w-100'
                    ]) . '
                    ' . ComponentBuilder::button([
                        'text' => 'Meu Banco de Horas',
                        'icon' => 'fa-balance-scale',
                        'url' => base_url('timesheet/balance'),
                        'style' => 'outline-primary',
                        'class' => 'w-100'
                    ]) . '
                    ' . ComponentBuilder::button([
                        'text' => 'Histórico Completo',
                        'icon' => 'fa-history',
                        'url' => base_url('timesheet/history'),
                        'style' => 'outline-primary',
                        'class' => 'w-100'
                    ]) . '
                    ' . ComponentBuilder::button([
                        'text' => 'Meu Perfil',
                        'icon' => 'fa-user',
                        'url' => base_url('profile'),
                        'style' => 'outline-secondary',
                        'class' => 'w-100'
                    ]) . '
                </div>
            '
        ]) ?>

        <!-- Upcoming Events -->
        <?= ComponentBuilder::card([
            'title' => 'Próximos Eventos',
            'icon' => 'fa-calendar',
            'class' => 'mb-4',
            'content' => !empty($upcomingEvents) ?
                implode('', array_map(function($event) {
                    return '
                        <div style="
                            padding: var(--spacing-sm);
                            border-left: 3px solid var(--color-primary);
                            background: var(--bg-surface);
                            margin-bottom: var(--spacing-sm);
                            border-radius: var(--radius-sm);
                        ">
                            <div style="font-weight: 600; color: var(--text-primary);">' . esc($event['title']) . '</div>
                            <div style="font-size: var(--font-size-sm); color: var(--text-muted); margin-top: 4px;">
                                <i class="fas fa-calendar"></i> ' . UIHelper::formatDate($event['date']) . '
                            </div>
                        </div>
                    ';
                }, $upcomingEvents)) :
                UIHelper::emptyState('Nenhum evento agendado', 'calendar')
        ]) ?>

        <!-- Notifications -->
        <?php if (!empty($notifications)): ?>
            <?= ComponentBuilder::card([
                'title' => 'Notificações',
                'icon' => 'fa-bell',
                'content' => implode('', array_map(function($notification) {
                    return ComponentBuilder::alert([
                        'message' => $notification['message'],
                        'type' => $notification['type'] ?? 'info',
                        'dismissible' => true
                    ]);
                }, array_slice($notifications, 0, 3)))
            ]) ?>
        <?php endif; ?>
    </div>

</div>

<?= $this->endSection() ?>
