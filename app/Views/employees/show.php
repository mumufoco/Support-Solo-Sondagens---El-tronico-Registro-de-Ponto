<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?><?= esc($employee->name) ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-user me-2"></i><?= esc($employee->name) ?>
                        <?php if ($employee->active): ?>
                            <span class="badge bg-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inativo</span>
                        <?php endif; ?>
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="<?= base_url('employees') ?>">Funcionários</a></li>
                            <li class="breadcrumb-item active"><?= esc($employee->name) ?></li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="<?= base_url('employees') ?>" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-2"></i>Voltar
                    </a>
                    <a href="<?= base_url('employees/edit/' . $employee->id) ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-2"></i>Editar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Registros de Ponto</h6>
                            <h3 class="mb-0"><?= $statistics['total_punches'] ?? 0 ?></h3>
                        </div>
                        <div class="rounded-circle p-3" style="background-color: #e3f2fd;">
                            <i class="fas fa-clock text-primary fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Saldo de Horas</h6>
                            <h3 class="mb-0 <?= ($statistics['balance'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= ($statistics['balance'] ?? 0) >= 0 ? '+' : '' ?><?= number_format($statistics['balance'] ?? 0, 1) ?>h
                            </h3>
                        </div>
                        <div class="rounded-circle p-3" style="background-color: <?= ($statistics['balance'] ?? 0) >= 0 ? '#e8f5e9' : '#ffebee' ?>;">
                            <i class="fas fa-chart-line <?= ($statistics['balance'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?> fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Justificativas</h6>
                            <h3 class="mb-0"><?= $statistics['total_justifications'] ?? 0 ?></h3>
                        </div>
                        <div class="rounded-circle p-3" style="background-color: #fff3e0;">
                            <i class="fas fa-file-alt text-warning fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Advertências</h6>
                            <h3 class="mb-0 text-danger"><?= $statistics['total_warnings'] ?? 0 ?></h3>
                        </div>
                        <div class="rounded-circle p-3" style="background-color: #ffebee;">
                            <i class="fas fa-exclamation-triangle text-danger fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Personal Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>Informações Pessoais</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Nome Completo</label>
                            <p class="mb-0"><strong><?= esc($employee->name) ?></strong></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">E-mail</label>
                            <p class="mb-0"><?= esc($employee->email) ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">CPF</label>
                            <p class="mb-0"><?= formatCPF($employee->cpf ?? '') ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Telefone</label>
                            <p class="mb-0"><?= esc($employee->phone ?? '-') ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Código Único</label>
                            <p class="mb-0"><code class="bg-light p-2 rounded"><?= esc($employee->unique_code) ?></code></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Status</label>
                            <p class="mb-0">
                                <?php if ($employee->active): ?>
                                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Inativo</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Work Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i>Informações de Trabalho</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Departamento</label>
                            <p class="mb-0"><span class="badge bg-secondary"><?= esc($employee->department) ?></span></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Cargo</label>
                            <p class="mb-0"><strong><?= esc($employee->position) ?></strong></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Nível de Acesso</label>
                            <p class="mb-0">
                                <?php
                                $roleBadges = [
                                    'admin' => 'danger',
                                    'gestor' => 'warning',
                                    'funcionario' => 'info',
                                ];
                                $badgeClass = $roleBadges[$employee->role] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($employee->role) ?></span>
                            </p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Horas Diárias Esperadas</label>
                            <p class="mb-0"><?= $employee->expected_hours_daily ?? 8 ?>h</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Horário de Entrada</label>
                            <p class="mb-0"><?= $employee->work_schedule_start ?? '08:00' ?></p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Horário de Saída</label>
                            <p class="mb-0"><?= $employee->work_schedule_end ?? '17:00' ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Punches -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Registros Recentes</h5>
                    <a href="<?= base_url('timesheet/employee/' . $employee->id) ?>" class="btn btn-sm btn-outline-primary">
                        Ver Todos
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentPunches)): ?>
                        <p class="text-muted text-center mb-0">Nenhum registro de ponto encontrado.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Data/Hora</th>
                                        <th>Tipo</th>
                                        <th>Método</th>
                                        <th>Local</th>
                                        <th>NSR</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentPunches as $punch): ?>
                                        <tr>
                                            <td><?= date('d/m/Y H:i:s', strtotime($punch->punch_time)) ?></td>
                                            <td>
                                                <?php
                                                $typeBadges = [
                                                    'entrada' => 'success',
                                                    'saida' => 'danger',
                                                    'intervalo_inicio' => 'warning',
                                                    'intervalo_fim' => 'info',
                                                ];
                                                $badgeClass = $typeBadges[$punch->punch_type] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst(str_replace('_', ' ', $punch->punch_type)) ?></span>
                                            </td>
                                            <td><small><?= ucfirst($punch->method) ?></small></td>
                                            <td>
                                                <?php if ($punch->within_geofence): ?>
                                                    <i class="fas fa-check-circle text-success" title="Dentro da área"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-exclamation-circle text-warning" title="Fora da área"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><code><?= str_pad($punch->nsr, 10, '0', STR_PAD_LEFT) ?></code></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Justifications -->
            <?php if (!empty($recentJustifications)): ?>
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Justificativas Recentes</h5>
                        <a href="<?= base_url('justifications?employee=' . $employee->id) ?>" class="btn btn-sm btn-outline-primary">
                            Ver Todas
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php foreach ($recentJustifications as $justification): ?>
                                <a href="<?= base_url('justifications/' . $justification->id) ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?= ucfirst($justification->type) ?></h6>
                                        <small><?= date('d/m/Y', strtotime($justification->date)) ?></small>
                                    </div>
                                    <p class="mb-1 small"><?= esc(substr($justification->reason, 0, 100)) ?>...</p>
                                    <small>
                                        <?php
                                        $statusBadges = [
                                            'pending' => 'warning',
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                        ];
                                        $badgeClass = $statusBadges[$justification->status] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($justification->status) ?></span>
                                    </small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- QR Code -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-qrcode me-2"></i>QR Code</h6>
                </div>
                <div class="card-body text-center">
                    <img src="<?= base_url('employees/qrcode/' . $employee->id) ?>"
                         alt="QR Code"
                         class="img-fluid mb-3"
                         style="max-width: 200px;">
                    <div class="d-grid gap-2">
                        <a href="<?= base_url('employees/qrcode/' . $employee->id . '/download') ?>"
                           class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-download me-2"></i>Baixar QR Code
                        </a>
                        <a href="<?= base_url('employees/qrcode/' . $employee->id . '/print') ?>"
                           class="btn btn-sm btn-outline-secondary"
                           target="_blank">
                            <i class="fas fa-print me-2"></i>Imprimir
                        </a>
                    </div>
                </div>
            </div>

            <!-- Biometric Status -->
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-fingerprint me-2"></i>Biometria</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><i class="fas fa-face-smile me-2"></i>Facial</span>
                            <?php if ($employee->has_face_biometric): ?>
                                <span class="badge bg-success">Cadastrada</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Não cadastrada</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($biometricTemplates['face'])): ?>
                            <small class="text-muted">
                                Cadastrada em: <?= date('d/m/Y', strtotime($biometricTemplates['face']->created_at)) ?>
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><i class="fas fa-fingerprint me-2"></i>Digital</span>
                            <?php if ($employee->has_fingerprint_biometric): ?>
                                <span class="badge bg-success">Cadastrada</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Não cadastrada</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($biometricTemplates['fingerprint'])): ?>
                            <small class="text-muted">
                                Cadastrada em: <?= date('d/m/Y', strtotime($biometricTemplates['fingerprint']->created_at)) ?>
                            </small>
                        <?php endif; ?>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="<?= base_url('biometric/manage/' . $employee->id) ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-cog me-2"></i>Gerenciar Biometria
                        </a>
                    </div>
                </div>
            </div>

            <!-- System Dates -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-calendar me-2"></i>Datas do Sistema</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <small class="text-muted">Cadastrado em:</small><br>
                        <strong><?= date('d/m/Y H:i', strtotime($employee->created_at)) ?></strong>
                    </p>
                    <p class="mb-0">
                        <small class="text-muted">Última atualização:</small><br>
                        <strong><?= date('d/m/Y H:i', strtotime($employee->updated_at)) ?></strong>
                    </p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>Ações Rápidas</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= base_url('employees/edit/' . $employee->id) ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit me-2"></i>Editar Funcionário
                        </a>
                        <a href="<?= base_url('timesheet/employee/' . $employee->id) ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-clock me-2"></i>Ver Folha de Ponto
                        </a>
                        <a href="<?= base_url('reports/employee/' . $employee->id) ?>" class="btn btn-sm btn-outline-info">
                            <i class="fas fa-file-pdf me-2"></i>Gerar Relatório
                        </a>
                        <a href="<?= base_url('warnings/create?employee=' . $employee->id) ?>" class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>Nova Advertência
                        </a>
                        <?php if ($employee->active): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    onclick="confirmDeactivate(<?= $employee->id ?>)">
                                <i class="fas fa-user-times me-2"></i>Desativar Funcionário
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-sm btn-outline-success"
                                    onclick="confirmActivate(<?= $employee->id ?>)">
                                <i class="fas fa-user-check me-2"></i>Ativar Funcionário
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDeactivate(id) {
    if (confirm('Tem certeza que deseja desativar este funcionário?\n\nEle não poderá mais acessar o sistema.')) {
        window.location.href = `<?= base_url('employees/deactivate/') ?>${id}`;
    }
}

function confirmActivate(id) {
    if (confirm('Tem certeza que deseja ativar este funcionário?')) {
        window.location.href = `<?= base_url('employees/activate/') ?>${id}`;
    }
}
</script>
<?= $this->endSection() ?>
