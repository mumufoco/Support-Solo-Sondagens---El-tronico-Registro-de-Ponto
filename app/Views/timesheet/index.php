<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Espelho de Ponto<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
    .day-card {
        border-left: 4px solid #e0e0e0;
        transition: all 0.3s;
    }

    .day-card:hover {
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transform: translateX(5px);
    }

    .day-card.complete {
        border-left-color: #198754;
    }

    .day-card.incomplete {
        border-left-color: #ffc107;
    }

    .day-card.missing {
        border-left-color: #dc3545;
    }

    .punch-time {
        font-size: 1.1rem;
        font-weight: 600;
        font-family: monospace;
    }

    .balance-positive {
        color: #198754;
    }

    .balance-negative {
        color: #dc3545;
    }

    .day-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1">
                <i class="fas fa-calendar-alt me-2"></i>Espelho de Ponto
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Espelho de Ponto</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Month Selector -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <form action="<?= base_url('timesheet') ?>" method="GET" class="row g-3">
                        <div class="col-auto">
                            <label for="month" class="form-label">Selecionar Período:</label>
                        </div>
                        <div class="col-auto">
                            <input type="month" class="form-control" id="month" name="month"
                                   value="<?= $selectedMonth ?? date('Y-m') ?>"
                                   max="<?= date('Y-m') ?>">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Buscar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <div class="btn-group" role="group">
                <a href="<?= base_url('timesheet/export?month=' . ($selectedMonth ?? date('Y-m'))) ?>"
                   class="btn btn-outline-success">
                    <i class="fas fa-file-excel me-2"></i>Exportar Excel
                </a>
                <a href="<?= base_url('timesheet/pdf?month=' . ($selectedMonth ?? date('Y-m'))) ?>"
                   class="btn btn-outline-danger">
                    <i class="fas fa-file-pdf me-2"></i>Gerar PDF
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Horas Trabalhadas</h6>
                    <h3 class="mb-0"><?= $summary['total_hours'] ?? '0:00' ?></h3>
                    <small class="text-muted">De <?= $summary['expected_hours'] ?? '176:00' ?> esperadas</small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Saldo do Período</h6>
                    <h3 class="mb-0 <?= ($summary['balance'] ?? 0) >= 0 ? 'balance-positive' : 'balance-negative' ?>">
                        <?= ($summary['balance'] ?? 0) >= 0 ? '+' : '' ?><?= $summary['balance_formatted'] ?? '0:00' ?>
                    </h3>
                    <small class="text-muted">Diferença total</small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Dias Trabalhados</h6>
                    <h3 class="mb-0"><?= $summary['days_worked'] ?? 0 ?></h3>
                    <small class="text-muted">De <?= $summary['expected_days'] ?? 22 ?> dias úteis</small>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Atrasos</h6>
                    <h3 class="mb-0 text-warning"><?= $summary['late_arrivals'] ?? 0 ?></h3>
                    <small class="text-muted">Registros em atraso</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Records -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Registros Diários
                    </h5>
                    <span class="badge bg-primary"><?= count($dailyRecords ?? []) ?> dias</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($dailyRecords)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="fas fa-calendar-times fa-3x mb-3 opacity-50"></i>
                            <p>Nenhum registro encontrado para este período</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Data</th>
                                        <th>Dia</th>
                                        <th class="text-center">Entrada</th>
                                        <th class="text-center">Início Intervalo</th>
                                        <th class="text-center">Fim Intervalo</th>
                                        <th class="text-center">Saída</th>
                                        <th class="text-center">Total</th>
                                        <th class="text-center">Saldo</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($dailyRecords as $record): ?>
                                        <tr class="<?= $record['is_holiday'] ? 'table-info' : ($record['is_weekend'] ? 'table-secondary' : '') ?>">
                                            <td>
                                                <strong><?= esc($record['date_formatted']) ?></strong>
                                                <?php if ($record['is_holiday']): ?>
                                                    <br><small class="text-muted"><?= esc($record['holiday_name'] ?? 'Feriado') ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    <?= esc($record['day_of_week']) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="punch-time"><?= esc($record['entrada'] ?? '--:--') ?></span>
                                                <?php if (isset($record['entrada_late']) && $record['entrada_late']): ?>
                                                    <br><small class="text-danger">
                                                        <i class="fas fa-exclamation-triangle"></i> Atraso
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="punch-time"><?= esc($record['inicio_intervalo'] ?? '--:--') ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="punch-time"><?= esc($record['fim_intervalo'] ?? '--:--') ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="punch-time"><?= esc($record['saida'] ?? '--:--') ?></span>
                                            </td>
                                            <td class="text-center">
                                                <strong><?= esc($record['total_hours'] ?? '0:00') ?></strong>
                                            </td>
                                            <td class="text-center">
                                                <span class="<?= ($record['balance'] ?? 0) >= 0 ? 'balance-positive' : 'balance-negative' ?>">
                                                    <?= ($record['balance'] ?? 0) >= 0 ? '+' : '' ?><?= esc($record['balance_formatted'] ?? '0:00') ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="<?= base_url("timesheet/day/{$record['date']}") ?>"
                                                       class="btn btn-sm btn-outline-primary"
                                                       title="Ver Detalhes">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($record['missing_punches'] ?? false): ?>
                                                        <a href="<?= base_url("justifications/create?date={$record['date']}") ?>"
                                                           class="btn btn-sm btn-outline-warning"
                                                           title="Justificar">
                                                            <i class="fas fa-file-alt"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="6" class="text-end">Total do Período:</th>
                                        <th class="text-center"><?= $summary['total_hours'] ?? '0:00' ?></th>
                                        <th class="text-center">
                                            <span class="<?= ($summary['balance'] ?? 0) >= 0 ? 'balance-positive' : 'balance-negative' ?>">
                                                <?= ($summary['balance'] ?? 0) >= 0 ? '+' : '' ?><?= $summary['balance_formatted'] ?? '0:00' ?>
                                            </span>
                                        </th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3">Legenda:</h6>
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <span class="badge bg-light text-dark me-2">Dia Normal</span>
                        </div>
                        <div class="col-md-3 mb-2">
                            <span class="badge bg-secondary me-2">Fim de Semana</span>
                        </div>
                        <div class="col-md-3 mb-2">
                            <span class="badge bg-info me-2">Feriado</span>
                        </div>
                        <div class="col-md-3 mb-2">
                            <i class="fas fa-exclamation-triangle text-danger me-1"></i>
                            <small>Atraso ou Falta</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Auto-submit form when month changes
    document.getElementById('month')?.addEventListener('change', function() {
        this.form.submit();
    });
</script>
<?= $this->endSection() ?>
