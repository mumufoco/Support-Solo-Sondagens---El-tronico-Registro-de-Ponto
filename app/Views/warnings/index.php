<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-exclamation-triangle text-warning"></i> Advertências</h2>
        <?php if (in_array($employee['role'], ['admin', 'gestor'])): ?>
            <a href="/warnings/create" class="btn btn-danger">
                <i class="fas fa-plus"></i> Nova Advertência
            </a>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <select name="warning_type" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= $warningType === 'all' ? 'selected' : '' ?>>Todos</option>
                        <option value="verbal" <?= $warningType === 'verbal' ? 'selected' : '' ?>>Verbal (<?= $counts['verbal'] ?>)</option>
                        <option value="escrita" <?= $warningType === 'escrita' ? 'selected' : '' ?>>Escrita (<?= $counts['escrita'] ?>)</option>
                        <option value="suspensao" <?= $warningType === 'suspensao' ? 'selected' : '' ?>>Suspensão (<?= $counts['suspensao'] ?>)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Todos</option>
                        <option value="pendente-assinatura" <?= $status === 'pendente-assinatura' ? 'selected' : '' ?>>Pendente (<?= $counts['pendente'] ?>)</option>
                        <option value="assinado" <?= $status === 'assinado' ? 'selected' : '' ?>>Assinado (<?= $counts['assinado'] ?>)</option>
                        <option value="recusado" <?= $status === 'recusado' ? 'selected' : '' ?>>Recusado (<?= $counts['recusado'] ?>)</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Warnings Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($warnings)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Nenhuma advertência encontrada</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Funcionário</th>
                                <th>Tipo</th>
                                <th>Motivo</th>
                                <th>Emitida por</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($warnings as $warning): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($warning->occurrence_date)) ?></td>
                                    <td>
                                        <strong><?= esc($warning->employee_name) ?></strong>
                                        <a href="/warnings/dashboard/<?= $warning->employee_id ?>" class="btn btn-sm btn-link">
                                            <i class="fas fa-chart-line"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <?php
                                        $badges = [
                                            'verbal' => '<span class="badge bg-warning">Verbal</span>',
                                            'escrita' => '<span class="badge bg-danger">Escrita</span>',
                                            'suspensao' => '<span class="badge bg-dark">Suspensão</span>'
                                        ];
                                        echo $badges[$warning->warning_type] ?? '';
                                        ?>
                                    </td>
                                    <td><?= mb_substr($warning->reason, 0, 50) ?>...</td>
                                    <td><?= esc($warning->issuer_name) ?></td>
                                    <td>
                                        <?php
                                        $statusBadges = [
                                            'pendente-assinatura' => '<span class="badge bg-secondary">Pendente</span>',
                                            'assinado' => '<span class="badge bg-success">Assinado</span>',
                                            'recusado' => '<span class="badge bg-danger">Recusado</span>'
                                        ];
                                        echo $statusBadges[$warning->status] ?? '';
                                        ?>
                                    </td>
                                    <td>
                                        <a href="/warnings/<?= $warning->id ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($warning->pdf_path): ?>
                                            <a href="/warnings/<?= $warning->id ?>/download" class="btn btn-sm btn-secondary">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?= $pager->links() ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
