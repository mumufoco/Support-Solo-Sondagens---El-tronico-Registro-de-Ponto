<?= $this->extend('layouts/modern') ?>

<?= $this->section('title') ?>Turnos de Trabalho<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
use App\Libraries\UI\ComponentBuilder;
use App\Libraries\UI\UIHelper;
?>

<!-- Page Header -->
<div style="margin-bottom: var(--spacing-xl);">
    <?= ComponentBuilder::card([
        'content' => UIHelper::flex([
            '<div>
                <h2 style="margin: 0 0 8px 0; font-size: var(--font-size-2xl); color: var(--text-primary);">
                    <i class="fas fa-clock me-2"></i>Turnos de Trabalho
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="' . base_url('dashboard') . '">Dashboard</a></li>
                        <li class="breadcrumb-item active">Turnos</li>
                    </ol>
                </nav>
            </div>',
            '<div style="display: flex; gap: var(--spacing-sm);">
                ' . ComponentBuilder::button([
                    'text' => 'Estatísticas',
                    'icon' => 'fa-chart-bar',
                    'url' => base_url('shifts/statistics'),
                    'style' => 'outline-primary',
                ]) . '
                ' . ComponentBuilder::button([
                    'text' => 'Novo Turno',
                    'icon' => 'fa-plus',
                    'url' => base_url('shifts/create'),
                    'style' => 'primary',
                ]) . '
            </div>'
        ], 'between', 'center')
    ]) ?>
</div>

<!-- Statistics Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-lg); margin-bottom: var(--spacing-xl);">

    <?= ComponentBuilder::statCard([
        'value' => $statistics['total_shifts'] ?? 0,
        'label' => 'Total de Turnos',
        'icon' => 'fa-clock',
        'color' => 'primary'
    ]) ?>

    <?= ComponentBuilder::statCard([
        'value' => $statistics['active_shifts'] ?? 0,
        'label' => 'Turnos Ativos',
        'icon' => 'fa-check-circle',
        'color' => 'success'
    ]) ?>

    <?= ComponentBuilder::statCard([
        'value' => $statistics['employees_scheduled'] ?? 0,
        'label' => 'Funcionários Escalados',
        'icon' => 'fa-users',
        'color' => 'info'
    ]) ?>

    <?= ComponentBuilder::statCard([
        'value' => $statistics['upcoming_schedules'] ?? 0,
        'label' => 'Escalas (Próximos 30 dias)',
        'icon' => 'fa-calendar',
        'color' => 'warning'
    ]) ?>

</div>

<!-- Filters -->
<div style="margin-bottom: var(--spacing-lg);">
    <?= ComponentBuilder::card([
        'title' => 'Filtros',
        'icon' => 'fa-filter',
        'collapsible' => true,
        'collapsed' => empty($filters['type']) && empty($filters['status']) && empty($filters['search']),
        'content' => '
            <form method="GET" action="' . base_url('shifts') . '">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-md); margin-bottom: var(--spacing-md);">

                    <div>
                        <label for="type" class="form-label">Tipo de Turno</label>
                        <select name="type" id="type" class="form-select">
                            <option value="">Todos os tipos</option>
                            <option value="morning" ' . (($filters['type'] ?? '') === 'morning' ? 'selected' : '') . '>Manhã</option>
                            <option value="afternoon" ' . (($filters['type'] ?? '') === 'afternoon' ? 'selected' : '') . '>Tarde</option>
                            <option value="night" ' . (($filters['type'] ?? '') === 'night' ? 'selected' : '') . '>Noite</option>
                            <option value="custom" ' . (($filters['type'] ?? '') === 'custom' ? 'selected' : '') . '>Personalizado</option>
                        </select>
                    </div>

                    <div>
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Todos</option>
                            <option value="active" ' . (($filters['status'] ?? '') === 'active' ? 'selected' : '') . '>Ativos</option>
                            <option value="inactive" ' . (($filters['status'] ?? '') === 'inactive' ? 'selected' : '') . '>Inativos</option>
                        </select>
                    </div>

                    <div>
                        <label for="search" class="form-label">Buscar</label>
                        <input type="text" name="search" id="search" class="form-control"
                            placeholder="Nome ou descrição..."
                            value="' . esc($filters['search'] ?? '') . '">
                    </div>

                    <div style="display: flex; align-items: flex-end; gap: var(--spacing-sm);">
                        ' . ComponentBuilder::button([
                            'text' => 'Filtrar',
                            'icon' => 'fa-search',
                            'style' => 'primary',
                            'type' => 'submit'
                        ]) . '
                        <a href="' . base_url('shifts') . '" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Limpar
                        </a>
                    </div>

                </div>
            </form>
        '
    ]) ?>
</div>

<!-- Shifts Table -->
<?php if (empty($shifts)): ?>
    <div style="padding: var(--spacing-xl); text-align: center;">
        <?= UIHelper::emptyState('Nenhum turno encontrado', 'clock') ?>
        <p class="text-muted mt-3">Crie o primeiro turno para começar a gerenciar escalas de trabalho.</p>
        <?= ComponentBuilder::button([
            'text' => 'Criar Primeiro Turno',
            'icon' => 'fa-plus',
            'url' => base_url('shifts/create'),
            'style' => 'primary',
            'class' => 'mt-3'
        ]) ?>
    </div>
<?php else: ?>
    <?= ComponentBuilder::card([
        'title' => 'Lista de Turnos',
        'icon' => 'fa-list',
        'content' => ComponentBuilder::table([
            'columns' => [
                [
                    'label' => 'Turno',
                    'key' => 'name',
                    'formatter' => function($name, $row) {
                        $typeColors = [
                            'morning' => 'warning',
                            'afternoon' => 'primary',
                            'night' => 'dark',
                            'custom' => 'success'
                        ];
                        $typeLabels = [
                            'morning' => 'Manhã',
                            'afternoon' => 'Tarde',
                            'night' => 'Noite',
                            'custom' => 'Personalizado'
                        ];

                        return '
                            <div style="display: flex; align-items: center; gap: var(--spacing-sm);">
                                <div style="width: 24px; height: 24px; border-radius: 4px; background: ' . ($row->color ?? '#6C757D') . ';"></div>
                                <div>
                                    <strong>' . esc($name) . '</strong><br>
                                    <small class="text-muted">' . ($typeLabels[$row->type] ?? $row->type) . '</small>
                                </div>
                            </div>
                        ';
                    }
                ],
                [
                    'label' => 'Horário',
                    'key' => 'start_time',
                    'formatter' => function($start, $row) {
                        return '
                            <div>
                                <i class="fas fa-clock text-muted"></i>
                                <strong>' . substr($start, 0, 5) . '</strong> até <strong>' . substr($row->end_time, 0, 5) . '</strong>
                            </div>
                        ';
                    }
                ],
                [
                    'label' => 'Duração',
                    'key' => 'duration',
                    'formatter' => function($duration) {
                        return '<span class="badge bg-info">' . number_format($duration, 1) . 'h</span>';
                    }
                ],
                [
                    'label' => 'Intervalo',
                    'key' => 'break_duration',
                    'formatter' => function($minutes) {
                        if ($minutes == 0) return '<span class="text-muted">Sem intervalo</span>';
                        $hours = floor($minutes / 60);
                        $mins = $minutes % 60;
                        if ($hours > 0) {
                            return $mins > 0 ? "{$hours}h {$mins}min" : "{$hours}h";
                        }
                        return "{$mins}min";
                    }
                ],
                [
                    'label' => 'Funcionários',
                    'key' => 'employee_count',
                    'formatter' => function($count) {
                        if ($count == 0) return '<span class="text-muted">0</span>';
                        return '<span class="badge bg-primary">' . $count . '</span>';
                    }
                ],
                [
                    'label' => 'Status',
                    'key' => 'active',
                    'formatter' => function($active) {
                        return UIHelper::statusBadge($active ? 'active' : 'inactive');
                    }
                ],
                [
                    'label' => 'Ações',
                    'key' => 'id',
                    'formatter' => function($id, $row) {
                        return '
                            <div class="btn-group" role="group">
                                <a href="' . base_url('shifts/' . $id) . '" class="btn btn-sm btn-outline-primary" title="Ver Detalhes">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="' . base_url('shifts/' . $id . '/edit') . '" class="btn btn-sm btn-outline-secondary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="' . base_url('shifts/' . $id . '/clone') . '" style="display: inline;">
                                    <button type="submit" class="btn btn-sm btn-outline-info" title="Clonar">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </form>
                                <form method="POST" action="' . base_url('shifts/' . $id) . '" style="display: inline;"
                                    onsubmit="return confirm(\'Tem certeza que deseja excluir este turno?\');">
                                    <input type="hidden" name="_method" value="DELETE">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        ';
                    }
                ]
            ],
            'data' => $shifts
        ])
    ]) ?>
<?php endif; ?>

<?= $this->endSection() ?>
