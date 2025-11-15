<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-chart-line"></i> Advertências - <?= esc($targetEmployee->name) ?></h2>
        <a href="/warnings" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>

    <!-- Alert if at limit -->
    <?php if ($atLimit): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5><i class="fas fa-exclamation-triangle"></i> ATENÇÃO - LIMITE ATINGIDO!</h5>
            <p class="mb-0">
                Este funcionário já recebeu <strong>3 advertências</strong>.
                Qualquer nova advertência pode resultar em medidas mais severas, incluindo possível demissão por justa causa.
            </p>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Statistics Cards -->
        <div class="col-md-3 mb-4">
            <div class="card text-center <?= $totalWarnings >= 3 ? 'border-danger' : '' ?>">
                <div class="card-body">
                    <h2 class="<?= $totalWarnings >= 3 ? 'text-danger' : 'text-primary' ?>">
                        <?= $totalWarnings ?>/3
                    </h2>
                    <p class="mb-0">Total de Advertências</p>
                    <div class="progress mt-2" style="height: 10px;">
                        <div class="progress-bar <?= $totalWarnings >= 3 ? 'bg-danger' : 'bg-warning' ?>"
                             style="width: <?= min(100, ($totalWarnings / 3) * 100) ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h2 class="text-warning"><?= $warningsByType['verbal'] ?></h2>
                    <p class="mb-0">Verbais</p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h2 class="text-danger"><?= $warningsByType['escrita'] ?></h2>
                    <p class="mb-0">Escritas</p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h2 class="text-dark"><?= $warningsByType['suspensao'] ?></h2>
                    <p class="mb-0">Suspensões</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Timeline -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-history"></i> Linha do Tempo</h5>
        </div>
        <div class="card-body">
            <?php if (empty($timeline)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p class="text-muted">Nenhuma advertência registrada</p>
                </div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($timeline as $item): ?>
                        <div class="timeline-item mb-4">
                            <div class="timeline-marker">
                                <?php if ($item['type'] === 'verbal'): ?>
                                    <i class="fas fa-circle text-warning"></i>
                                <?php elseif ($item['type'] === 'escrita'): ?>
                                    <i class="fas fa-circle text-danger"></i>
                                <?php else: ?>
                                    <i class="fas fa-circle text-dark"></i>
                                <?php endif; ?>
                            </div>
                            <div class="timeline-content">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">
                                                    <?php
                                                    $types = [
                                                        'verbal' => '<span class="badge bg-warning">VERBAL</span>',
                                                        'escrita' => '<span class="badge bg-danger">ESCRITA</span>',
                                                        'suspensao' => '<span class="badge bg-dark">SUSPENSÃO</span>'
                                                    ];
                                                    echo $types[$item['type']] ?? '';
                                                    ?>
                                                    - <?= date('d/m/Y', strtotime($item['date'])) ?>
                                                </h6>
                                                <p class="mb-1 text-muted small"><?= esc($item['reason_preview']) ?></p>
                                                <?php if ($item['signed_at']): ?>
                                                    <small class="text-success">
                                                        <i class="fas fa-check-circle"></i> Assinado em <?= date('d/m/Y', strtotime($item['signed_at'])) ?>
                                                    </small>
                                                <?php else: ?>
                                                    <small class="text-warning">
                                                        <i class="fas fa-clock"></i>
                                                        <?= $item['status'] === 'pendente-assinatura' ? 'Aguardando assinatura' : ucfirst($item['status']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            <a href="/warnings/<?= $item['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> Ver
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    top: 8px;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -26px;
    top: 24px;
    bottom: -24px;
    width: 2px;
    background-color: #dee2e6;
}

.timeline-content {
    margin-left: 10px;
}
</style>

<?= $this->endSection() ?>
