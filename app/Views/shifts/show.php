<?= $this->extend('layouts/modern') ?>

<?= $this->section('title') ?><?= esc($shift->name) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
use App\Libraries\UI\ComponentBuilder;
use App\Libraries\UI\UIHelper;

$typeLabels = [
    'morning' => 'Manhã',
    'afternoon' => 'Tarde',
    'night' => 'Noite',
    'custom' => 'Personalizado'
];
?>

<!-- Page Header -->
<div style="margin-bottom: var(--spacing-xl);">
    <?= ComponentBuilder::card([
        'content' => UIHelper::flex([
            '<div>
                <div style="display: flex; align-items: center; gap: var(--spacing-md); margin-bottom: 8px;">
                    <div style="width: 32px; height: 32px; border-radius: 6px; background: ' . ($shift->color ?? '#6C757D') . ';"></div>
                    <h2 style="margin: 0; font-size: var(--font-size-2xl); color: var(--text-primary);">
                        ' . esc($shift->name) . '
                    </h2>
                    ' . UIHelper::statusBadge($shift->active ? 'active' : 'inactive') . '
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="' . base_url('dashboard') . '">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="' . base_url('shifts') . '">Turnos</a></li>
                        <li class="breadcrumb-item active">' . esc($shift->name) . '</li>
                    </ol>
                </nav>
            </div>',
            '<div style="display: flex; gap: var(--spacing-sm);">
                ' . ComponentBuilder::button([
                    'text' => 'Editar',
                    'icon' => 'fa-edit',
                    'url' => base_url('shifts/' . $shift->id . '/edit'),
                    'style' => 'primary',
                ]) . '
                <form method="POST" action="' . base_url('shifts/' . $shift->id . '/clone') . '" style="display: inline;">
                    ' . ComponentBuilder::button([
                        'text' => 'Clonar',
                        'icon' => 'fa-copy',
                        'style' => 'outline-secondary',
                        'type' => 'submit'
                    ]) . '
                </form>
                ' . ComponentBuilder::button([
                    'text' => 'Voltar',
                    'icon' => 'fa-arrow-left',
                    'url' => base_url('shifts'),
                    'style' => 'outline-secondary',
                ]) . '
            </div>'
        ], 'between', 'center')
    ]) ?>
</div>

<!-- Statistics Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-lg); margin-bottom: var(--spacing-xl);">

    <?= ComponentBuilder::statCard([
        'value' => number_format($duration, 1) . 'h',
        'label' => 'Duração Total',
        'icon' => 'fa-clock',
        'color' => 'primary'
    ]) ?>

    <?= ComponentBuilder::statCard([
        'value' => count($assignedEmployees),
        'label' => 'Funcionários Escalados',
        'icon' => 'fa-users',
        'color' => 'success',
        'url' => '#assigned-employees'
    ]) ?>

    <?= ComponentBuilder::statCard([
        'value' => $statistics['total_schedules'] ?? 0,
        'label' => 'Escalas Criadas',
        'icon' => 'fa-calendar-check',
        'color' => 'info'
    ]) ?>

    <?= ComponentBuilder::statCard([
        'value' => $statistics['upcoming_schedules'] ?? 0,
        'label' => 'Escalas Futuras',
        'icon' => 'fa-calendar',
        'color' => 'warning'
    ]) ?>

</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--spacing-lg);">

    <!-- Left Column -->
    <div>
        <!-- Shift Details -->
        <?= ComponentBuilder::card([
            'title' => 'Detalhes do Turno',
            'icon' => 'fa-info-circle',
            'content' => '
                <table class="table table-borderless">
                    <tbody>
                        <tr>
                            <th style="width: 30%;">Tipo</th>
                            <td>' . ComponentBuilder::badge([
                                'text' => $typeLabels[$shift->type] ?? $shift->type,
                                'style' => match($shift->type) {
                                    'morning' => 'warning',
                                    'afternoon' => 'primary',
                                    'night' => 'dark',
                                    'custom' => 'success',
                                    default => 'secondary'
                                }
                            ]) . '</td>
                        </tr>
                        <tr>
                            <th>Horário</th>
                            <td>
                                <i class="fas fa-clock text-muted me-2"></i>
                                <strong>' . substr($shift->start_time, 0, 5) . '</strong> até <strong>' . substr($shift->end_time, 0, 5) . '</strong>
                            </td>
                        </tr>
                        <tr>
                            <th>Duração</th>
                            <td><strong>' . number_format($duration, 1) . 'h</strong></td>
                        </tr>
                        <tr>
                            <th>Intervalo</th>
                            <td>' . ($shift->break_duration > 0 ? $shift->break_duration . ' minutos' : 'Sem intervalo') . '</td>
                        </tr>
                        <tr>
                            <th>Cor</th>
                            <td>
                                <div style="display: flex; align-items: center; gap: var(--spacing-sm);">
                                    <div style="width: 24px; height: 24px; border-radius: 4px; background: ' . ($shift->color ?? '#6C757D') . '; border: 1px solid #ddd;"></div>
                                    <code>' . ($shift->color ?? '#6C757D') . '</code>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>' . UIHelper::statusBadge($shift->active ? 'active' : 'inactive') . '</td>
                        </tr>
                        <tr>
                            <th>Criado em</th>
                            <td>' . UIHelper::formatDateTime($shift->created_at) . '</td>
                        </tr>
                        ' . ($shift->updated_at ? '
                        <tr>
                            <th>Última atualização</th>
                            <td>' . UIHelper::formatDateTime($shift->updated_at) . '</td>
                        </tr>
                        ' : '') . '
                    </tbody>
                </table>

                ' . ($shift->description ? '
                <div class="mt-3 pt-3 border-top">
                    <h6 class="text-muted mb-2">Descrição:</h6>
                    <p class="mb-0">' . nl2br(esc($shift->description)) . '</p>
                </div>
                ' : '') . '
            '
        ]) ?>

        <!-- Assigned Employees -->
        <div id="assigned-employees" class="mt-4">
            <?= ComponentBuilder::card([
                'title' => 'Funcionários Escalados (' . count($assignedEmployees) . ')',
                'icon' => 'fa-users',
                'content' => count($assignedEmployees) > 0 ?
                    ComponentBuilder::table([
                        'columns' => [
                            [
                                'label' => 'Funcionário',
                                'key' => 'name',
                                'formatter' => function($name, $row) {
                                    return UIHelper::flex([
                                        UIHelper::avatar($name),
                                        '<div>
                                            <strong>' . esc($name) . '</strong><br>
                                            <small class="text-muted">' . esc($row->position ?? 'N/A') . '</small>
                                        </div>'
                                    ], 'start', 'center', 'sm');
                                }
                            ],
                            [
                                'label' => 'Departamento',
                                'key' => 'department',
                                'formatter' => fn($dept) => esc($dept ?? 'N/A')
                            ],
                            [
                                'label' => 'Próxima Escala',
                                'key' => 'next_schedule',
                                'formatter' => function($date) {
                                    if (!$date) return '<span class="text-muted">Nenhuma</span>';
                                    return UIHelper::formatDate($date);
                                }
                            ],
                            [
                                'label' => 'Total de Escalas',
                                'key' => 'total_schedules',
                                'formatter' => fn($count) => '<span class="badge bg-primary">' . ($count ?? 0) . '</span>'
                            ]
                        ],
                        'data' => $assignedEmployees
                    ]) :
                    UIHelper::emptyState('Nenhum funcionário escalado neste turno', 'users')
            ]) ?>
        </div>
    </div>

    <!-- Right Column -->
    <div>
        <!-- Quick Actions -->
        <?= ComponentBuilder::card([
            'title' => 'Ações Rápidas',
            'icon' => 'fa-bolt',
            'class' => 'mb-4',
            'content' => '
                <div style="display: flex; flex-direction: column; gap: var(--spacing-sm);">
                    ' . ComponentBuilder::button([
                        'text' => 'Criar Escala',
                        'icon' => 'fa-calendar-plus',
                        'url' => base_url('schedules/create?shift_id=' . $shift->id),
                        'style' => 'primary',
                        'class' => 'w-100'
                    ]) . '
                    ' . ComponentBuilder::button([
                        'text' => 'Ver Todas as Escalas',
                        'icon' => 'fa-calendar',
                        'url' => base_url('schedules?shift_id=' . $shift->id),
                        'style' => 'outline-primary',
                        'class' => 'w-100'
                    ]) . '
                    ' . ComponentBuilder::button([
                        'text' => 'Editar Turno',
                        'icon' => 'fa-edit',
                        'url' => base_url('shifts/' . $shift->id . '/edit'),
                        'style' => 'outline-secondary',
                        'class' => 'w-100'
                    ]) . '
                    <form method="POST" action="' . base_url('shifts/' . $shift->id . '/clone') . '">
                        ' . ComponentBuilder::button([
                            'text' => 'Clonar Turno',
                            'icon' => 'fa-copy',
                            'style' => 'outline-info',
                            'class' => 'w-100',
                            'type' => 'submit'
                        ]) . '
                    </form>
                    <hr>
                    <form method="POST" action="' . base_url('shifts/' . $shift->id) . '"
                        onsubmit="return confirm(\'Tem certeza que deseja excluir este turno?\');">
                        <input type="hidden" name="_method" value="DELETE">
                        ' . ComponentBuilder::button([
                            'text' => 'Excluir Turno',
                            'icon' => 'fa-trash',
                            'style' => 'outline-danger',
                            'class' => 'w-100',
                            'type' => 'submit'
                        ]) . '
                    </form>
                </div>
            '
        ]) ?>

        <!-- Statistics -->
        <?= ComponentBuilder::card([
            'title' => 'Estatísticas',
            'icon' => 'fa-chart-bar',
            'content' => '
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Escalas Totais</span>
                        <strong>' . ($statistics['total_schedules'] ?? 0) . '</strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Escalas Futuras</span>
                        <strong>' . ($statistics['upcoming_schedules'] ?? 0) . '</strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Escalas Concluídas</span>
                        <strong>' . ($statistics['completed_schedules'] ?? 0) . '</strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Escalas Canceladas</span>
                        <strong>' . ($statistics['cancelled_schedules'] ?? 0) . '</strong>
                    </div>
                </div>
            '
        ]) ?>
    </div>

</div>

<?= $this->endSection() ?>
