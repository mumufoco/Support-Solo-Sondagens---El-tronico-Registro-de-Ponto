<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Dashboard Gestor<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <h1 class="h3 mb-4"><i class="fas fa-users-cog me-2"></i>Dashboard do Gestor</h1>

    <!-- Resumo da Equipe -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-primary">
                <div class="card-body">
                    <h6 class="text-muted">Membros da Equipe</h6>
                    <h2><?= $team_count ?? 0 ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-success">
                <div class="card-body">
                    <h6 class="text-muted">Presentes Hoje</h6>
                    <h2><?= $team_stats['punched_today'] ?? 0 ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-warning">
                <div class="card-body">
                    <h6 class="text-muted">Justificativas Pendentes</h6>
                    <h2><?= count($pending_justifications ?? []) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Justificativas Pendentes -->
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Justificativas Pendentes</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($pending_justifications)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Funcionário</th>
                                        <th>Data</th>
                                        <th>Tipo</th>
                                        <th>Motivo</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_justifications as $just): ?>
                                        <tr>
                                            <td><?= esc($just->employee_name) ?></td>
                                            <td><?= date('d/m/Y', strtotime($just->date)) ?></td>
                                            <td>
                                                <span class="badge bg-info"><?= esc($just->type) ?></span>
                                            </td>
                                            <td><?= esc(substr($just->reason, 0, 50)) ?>...</td>
                                            <td>
                                                <form action="/gestor/justifications/<?= $just->id ?>/approve" method="POST" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-success btn-sm">
                                                        <i class="fas fa-check me-1"></i>Aprovar
                                                    </button>
                                                </form>
                                                <form action="/gestor/justifications/<?= $just->id ?>/reject" method="POST" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-times me-1"></i>Rejeitar
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0">Nenhuma justificativa pendente.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Botão Bater Ponto -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body text-center">
                    <a href="/punch" class="btn btn-primary btn-lg">
                        <i class="fas fa-fingerprint fa-2x d-block mb-2"></i>
                        BATER PONTO
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
