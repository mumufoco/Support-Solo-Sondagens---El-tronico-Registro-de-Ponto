<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Detalhes do Dia<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1">
                <i class="fas fa-calendar-day me-2"></i>Detalhes do Dia
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('timesheet') ?>">Espelho de Ponto</a></li>
                    <li class="breadcrumb-item active"><?= esc($date_formatted) ?></li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Punches Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-fingerprint me-2"></i>Registros de Ponto
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($punches)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                            <p>Nenhum registro de ponto neste dia</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($punches as $punch): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-2 text-center">
                                            <i class="fas fa-<?=
                                                $punch['type'] === 'entrada' ? 'arrow-right text-success' :
                                                ($punch['type'] === 'saida' ? 'arrow-left text-danger' :
                                                ($punch['type'] === 'inicio_intervalo' ? 'coffee text-warning' : 'utensils text-info'))
                                            ?> fa-3x"></i>
                                        </div>
                                        <div class="col-md-10">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <h6 class="text-muted mb-1">Tipo</h6>
                                                    <h5 class="mb-0"><?= ucfirst(str_replace('_', ' ', $punch['type'])) ?></h5>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <h6 class="text-muted mb-1">Horário</h6>
                                                    <h5 class="mb-0 font-monospace"><?= esc($punch['time']) ?></h5>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4 mb-2">
                                                    <small class="text-muted">Método:</small>
                                                    <p class="mb-0">
                                                        <span class="badge bg-secondary"><?= ucfirst($punch['method']) ?></span>
                                                    </p>
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <small class="text-muted">NSR:</small>
                                                    <p class="mb-0 font-monospace"><?= esc($punch['nsr']) ?></p>
                                                </div>
                                                <div class="col-md-4 mb-2">
                                                    <small class="text-muted">Status:</small>
                                                    <p class="mb-0">
                                                        <?php if ($punch['verified']): ?>
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check-circle"></i> Verificado
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning">
                                                                <i class="fas fa-exclamation-triangle"></i> Não Verificado
                                                            </span>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <?php if (isset($punch['latitude']) && isset($punch['longitude'])): ?>
                                                <div class="mt-2 pt-2 border-top">
                                                    <small class="text-muted">
                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                        Localização: <?= esc($punch['latitude']) ?>, <?= esc($punch['longitude']) ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Summary Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>Resumo do Dia
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Data:</span>
                                <strong><?= esc($date_formatted) ?></strong>
                            </div>
                        </li>
                        <li class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Dia da Semana:</span>
                                <strong><?= esc($day_of_week) ?></strong>
                            </div>
                        </li>
                        <li class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Total de Registros:</span>
                                <strong><?= count($punches ?? []) ?></strong>
                            </div>
                        </li>
                        <li class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Horas Trabalhadas:</span>
                                <strong><?= esc($total_hours ?? '0:00') ?></strong>
                            </div>
                        </li>
                        <li class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Horas Esperadas:</span>
                                <strong><?= esc($expected_hours ?? '8:00') ?></strong>
                            </div>
                        </li>
                        <li>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Saldo:</span>
                                <strong class="<?= ($balance ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= ($balance ?? 0) >= 0 ? '+' : '' ?><?= esc($balance_formatted ?? '0:00') ?>
                                </strong>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Actions Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-tools me-2"></i>Ações
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <?php if ($can_justify ?? false): ?>
                            <a href="<?= base_url("justifications/create?date={$date}") ?>"
                               class="btn btn-warning">
                                <i class="fas fa-file-alt me-2"></i>Enviar Justificativa
                            </a>
                        <?php endif; ?>
                        <a href="<?= base_url('timesheet') ?>" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Voltar ao Espelho
                        </a>
                        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Imprimir
                        </button>
                    </div>
                </div>
            </div>

            <!-- Timeline Visual -->
            <?php if (!empty($punches)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>Linha do Tempo
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <?php foreach ($punches as $index => $punch): ?>
                                <div class="timeline-item mb-3 pb-3 <?= $index < count($punches) - 1 ? 'border-bottom' : '' ?>">
                                    <div class="d-flex align-items-center">
                                        <div class="timeline-marker me-3">
                                            <div class="rounded-circle d-inline-block" style="width: 12px; height: 12px; background-color: <?=
                                                $punch['type'] === 'entrada' ? '#198754' :
                                                ($punch['type'] === 'saida' ? '#dc3545' :
                                                ($punch['type'] === 'inicio_intervalo' ? '#ffc107' : '#0dcaf0'))
                                            ?>;"></div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <strong><?= ucfirst(str_replace('_', ' ', $punch['type'])) ?></strong>
                                                <span class="badge bg-light text-dark"><?= esc($punch['time']) ?></span>
                                            </div>
                                            <small class="text-muted">via <?= ucfirst($punch['method']) ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
