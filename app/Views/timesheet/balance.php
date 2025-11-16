<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Saldo de Horas<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<!-- Chart.js CSS -->
<style>
    .balance-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .balance-card.positive {
        background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }

    .balance-card.negative {
        background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
    }

    .balance-card.neutral {
        background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%);
    }

    .balance-amount {
        font-size: 3.5rem;
        font-weight: 700;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
    }

    .stat-box {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s;
    }

    .stat-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    }

    .stat-box .stat-icon {
        font-size: 2.5rem;
        opacity: 0.2;
        position: absolute;
        right: 1rem;
        top: 1rem;
    }

    .chart-container {
        position: relative;
        height: 400px;
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .period-tabs {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .period-tab {
        flex: 1;
        padding: 0.5rem 1rem;
        border: none;
        background: #f8f9fa;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .period-tab.active {
        background: #0d6efd;
        color: white;
    }

    .period-tab:hover:not(.active) {
        background: #e9ecef;
    }

    .alert-banner {
        border-left: 4px solid;
        padding: 1rem 1.5rem;
        margin-bottom: 1rem;
        border-radius: 5px;
    }

    .alert-banner.warning {
        background-color: #fff3cd;
        border-color: #ffc107;
        color: #856404;
    }

    .alert-banner.danger {
        background-color: #f8d7da;
        border-color: #dc3545;
        color: #721c24;
    }

    .alert-banner.success {
        background-color: #d1e7dd;
        border-color: #198754;
        color: #0f5132;
    }

    .export-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .table-compact {
        font-size: 0.9rem;
    }

    .badge-incomplete {
        background-color: #ffc107;
        color: #000;
    }

    .badge-violation {
        background-color: #dc3545;
    }

    .badge-justified {
        background-color: #17a2b8;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-chart-line me-2"></i>Saldo de Horas
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                            <li class="breadcrumb-item active">Saldo de Horas</li>
                        </ol>
                    </nav>
                </div>
                <div class="export-buttons">
                    <a href="<?= base_url('timesheet/export?format=pdf&employee_id=' . $viewingEmployee->id . '&period=' . $period) ?>"
                       class="btn btn-danger"
                       title="Exportar PDF">
                        <i class="fas fa-file-pdf me-2"></i>PDF
                    </a>
                    <a href="<?= base_url('timesheet/export?format=excel&employee_id=' . $viewingEmployee->id . '&period=' . $period) ?>"
                       class="btn btn-success"
                       title="Exportar Excel">
                        <i class="fas fa-file-excel me-2"></i>Excel
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Employee Selector (for managers) -->
    <?php if (in_array($employee['role'], ['admin', 'gestor']) && !empty($employees)): ?>
        <div class="card mb-4">
            <div class="card-body py-2">
                <form method="GET" action="<?= base_url('timesheet/balance') ?>" class="row g-2 align-items-center">
                    <div class="col-auto">
                        <label class="form-label mb-0 small text-muted">Visualizar funcionário:</label>
                    </div>
                    <div class="col-auto">
                        <select name="employee_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Meu saldo</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp->id ?>" <?= ($viewingEmployee->id == $emp->id) ? 'selected' : '' ?>>
                                    <?= esc($emp->name) ?> - <?= esc($emp->position ?? 'N/A') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="period" value="<?= $period ?>">
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Viewing employee info -->
    <div class="mb-3">
        <h5>
            <i class="fas fa-user me-2"></i><?= esc($viewingEmployee->name) ?>
            <small class="text-muted">- <?= esc($viewingEmployee->position ?? 'N/A') ?></small>
        </h5>
    </div>

    <!-- Alerts -->
    <?php if ($balance['balance'] < -10): ?>
        <div class="alert-banner danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Atenção!</strong> Você possui saldo negativo superior a 10 horas. Entre em contato com o RH para regularizar.
        </div>
    <?php elseif ($balance['balance'] > 40): ?>
        <div class="alert-banner warning">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Aviso:</strong> Você possui mais de 40 horas extras acumuladas. Considere solicitar compensação.
        </div>
    <?php elseif (!empty($incompleteDays)): ?>
        <div class="alert-banner warning">
            <i class="fas fa-exclamation-circle me-2"></i>
            <strong>Marcações incompletas:</strong> Você possui <?= count($incompleteDays) ?> dia(s) com marcações incompletas.
            <a href="<?= base_url('timesheet') ?>">Verificar</a>
        </div>
    <?php endif; ?>

    <!-- Balance Card -->
    <div class="row mb-4">
        <div class="col-md-12">
            <?php
            $balanceClass = 'neutral';
            $balanceIcon = 'fa-clock';
            $balanceLabel = 'Saldo de Horas';

            if ($balance['balance'] > 0) {
                $balanceClass = 'positive';
                $balanceIcon = 'fa-arrow-up';
                $balanceLabel = 'Horas Extras';
            } elseif ($balance['balance'] < 0) {
                $balanceClass = 'negative';
                $balanceIcon = 'fa-arrow-down';
                $balanceLabel = 'Horas Devidas';
            }
            ?>
            <div class="balance-card <?= $balanceClass ?>">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="mb-2">
                            <i class="fas <?= $balanceIcon ?> me-2"></i><?= $balanceLabel ?>
                        </h3>
                        <div class="balance-amount">
                            <?php
                            $balanceText = number_format(abs($balance['balance']), 2);
                            if ($balance['balance'] > 0) {
                                echo '+' . $balanceText . 'h';
                            } elseif ($balance['balance'] < 0) {
                                echo '-' . $balanceText . 'h';
                            } else {
                                echo $balanceText . 'h';
                            }
                            ?>
                        </div>
                        <p class="mb-0 mt-3 opacity-75">
                            Última atualização: <?= date('d/m/Y H:i') ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="mb-3">
                            <div class="small opacity-75">Horas Extras</div>
                            <h4 class="mb-0">+<?= number_format($balance['extra'], 2) ?>h</h4>
                        </div>
                        <div>
                            <div class="small opacity-75">Horas Devidas</div>
                            <h4 class="mb-0">-<?= number_format($balance['owed'], 2) ?>h</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-box position-relative">
                <i class="fas fa-calendar-check stat-icon text-primary"></i>
                <h6 class="text-muted mb-1">Dias Trabalhados</h6>
                <h3 class="mb-0 text-primary"><?= $statistics['total_days'] ?></h3>
                <small class="text-muted">Últimos <?= $period ?> dias</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box position-relative">
                <i class="fas fa-exclamation-triangle stat-icon text-warning"></i>
                <h6 class="text-muted mb-1">Dias Incompletos</h6>
                <h3 class="mb-0 text-warning"><?= $statistics['incomplete_days'] ?></h3>
                <?php if ($statistics['incomplete_days'] > 0): ?>
                    <small class="text-muted">
                        <a href="?irregularities=1&period=<?= $period ?>">Ver detalhes</a>
                    </small>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box position-relative">
                <i class="fas fa-clock stat-icon text-info"></i>
                <h6 class="text-muted mb-1">Média Diária</h6>
                <h3 class="mb-0 text-info"><?= number_format($statistics['avg_worked'], 2) ?>h</h3>
                <small class="text-muted">Esperado: <?= number_format($statistics['total_expected'] / max($statistics['total_days'], 1), 2) ?>h</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box position-relative">
                <i class="fas fa-check-circle stat-icon text-success"></i>
                <h6 class="text-muted mb-1">Dias Justificados</h6>
                <h3 class="mb-0 text-success"><?= $statistics['justified_days'] ?></h3>
                <?php if ($statistics['justified_days'] > 0): ?>
                    <small class="text-muted">Com justificativa aprovada</small>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Chart -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-chart-area me-2"></i>Evolução do Saldo
            </h5>
        </div>
        <div class="card-body">
            <!-- Period tabs -->
            <div class="period-tabs">
                <button class="period-tab <?= $period == '30' ? 'active' : '' ?>" onclick="changePeriod(30)">
                    Últimos 30 dias
                </button>
                <button class="period-tab <?= $period == '60' ? 'active' : '' ?>" onclick="changePeriod(60)">
                    Últimos 60 dias
                </button>
                <button class="period-tab <?= $period == '90' ? 'active' : '' ?>" onclick="changePeriod(90)">
                    Últimos 90 dias
                </button>
            </div>

            <div class="chart-container">
                <canvas id="balanceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Detailed Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-table me-2"></i>Registros Detalhados
            </h5>
            <div>
                <a href="?period=<?= $period ?>&employee_id=<?= $this->request->getGet('employee_id') ?? '' ?>"
                   class="btn btn-sm <?= !$irregularitiesOnly ? 'btn-primary' : 'btn-outline-primary' ?>">
                    Todos
                </a>
                <a href="?period=<?= $period ?>&irregularities=1&employee_id=<?= $this->request->getGet('employee_id') ?? '' ?>"
                   class="btn btn-sm <?= $irregularitiesOnly ? 'btn-warning' : 'btn-outline-warning' ?>">
                    Apenas Irregularidades
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($records)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-3 opacity-50"></i>
                    <h5 class="text-muted">Nenhum registro encontrado</h5>
                    <p class="text-muted">Não há dados para o período selecionado.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-compact align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Data</th>
                                <th>Entrada</th>
                                <th>Saída</th>
                                <th>Intervalo</th>
                                <th>Trabalhado</th>
                                <th>Esperado</th>
                                <th>Extra</th>
                                <th>Devidas</th>
                                <th>Status</th>
                                <th>Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                                <tr>
                                    <td>
                                        <strong><?= date('d/m/Y', strtotime($record->date)) ?></strong>
                                        <br><small class="text-muted"><?= strftime('%A', strtotime($record->date)) ?></small>
                                    </td>
                                    <td><?= $record->first_punch ?? '-' ?></td>
                                    <td><?= $record->last_punch ?? '-' ?></td>
                                    <td><?= number_format($record->total_interval, 2) ?>h</td>
                                    <td>
                                        <strong><?= number_format($record->total_worked, 2) ?>h</strong>
                                    </td>
                                    <td><?= number_format($record->expected, 2) ?>h</td>
                                    <td>
                                        <?php if ($record->extra > 0): ?>
                                            <span class="text-success">
                                                +<?= number_format($record->extra, 2) ?>h
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($record->owed > 0): ?>
                                            <span class="text-danger">
                                                -<?= number_format($record->owed, 2) ?>h
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($record->incomplete): ?>
                                            <span class="badge badge-incomplete">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Incompleto
                                            </span>
                                        <?php elseif ($record->justified): ?>
                                            <span class="badge badge-justified">
                                                <i class="fas fa-check me-1"></i>Justificado
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i>OK
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($record->interval_violation > 0): ?>
                                            <span class="badge badge-violation ms-1">
                                                <i class="fas fa-exclamation-circle me-1"></i>Intervalo
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php if ($record->notes): ?>
                                                <?= esc(mb_substr($record->notes, 0, 50)) ?>
                                                <?= strlen($record->notes) > 50 ? '...' : '' ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="4" class="text-end">Totais:</th>
                                <th><?= number_format($statistics['total_worked'], 2) ?>h</th>
                                <th><?= number_format($statistics['total_expected'], 2) ?>h</th>
                                <th class="text-success">+<?= number_format($statistics['total_extra'], 2) ?>h</th>
                                <th class="text-danger">-<?= number_format($statistics['total_owed'], 2) ?>h</th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    // Evolution data from PHP
    const evolutionData = <?= json_encode($evolution) ?>;

    // Prepare chart data
    const labels = evolutionData.map(item => {
        const date = new Date(item.date);
        return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
    });

    const balanceData = evolutionData.map(item => item.balance);
    const extraData = evolutionData.map(item => item.extra);
    const owedData = evolutionData.map(item => item.owed);

    // Chart configuration
    const ctx = document.getElementById('balanceChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Saldo Total',
                    data: balanceData,
                    borderColor: 'rgb(13, 110, 253)',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                },
                {
                    label: 'Horas Extras (acumulado)',
                    data: extraData,
                    borderColor: 'rgb(25, 135, 84)',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0.4,
                },
                {
                    label: 'Horas Devidas (acumulado)',
                    data: owedData,
                    borderColor: 'rgb(220, 53, 69)',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0.4,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            const value = context.parsed.y;
                            if (value >= 0) {
                                label += '+' + value.toFixed(2) + 'h';
                            } else {
                                label += value.toFixed(2) + 'h';
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    ticks: {
                        callback: function(value) {
                            return value.toFixed(1) + 'h';
                        }
                    },
                    grid: {
                        color: function(context) {
                            if (context.tick.value === 0) {
                                return 'rgba(0, 0, 0, 0.3)';
                            }
                            return 'rgba(0, 0, 0, 0.05)';
                        },
                        lineWidth: function(context) {
                            if (context.tick.value === 0) {
                                return 2;
                            }
                            return 1;
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            }
        }
    });

    // Period change function
    function changePeriod(days) {
        const urlParams = new URLSearchParams(window.location.search);
        urlParams.set('period', days);
        window.location.search = urlParams.toString();
    }
</script>
<?= $this->endSection() ?>
