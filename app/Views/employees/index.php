<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Funcionários<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-users me-2"></i>Funcionários
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                            <li class="breadcrumb-item active">Funcionários</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="<?= base_url('employees/create') ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Novo Funcionário
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total</h6>
                            <h3 class="mb-0"><?= $pager->getTotal() ?></h3>
                        </div>
                        <div class="rounded-circle p-3" style="background-color: #e3f2fd;">
                            <i class="fas fa-users text-primary fa-2x"></i>
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
                            <h6 class="text-muted mb-1">Ativos</h6>
                            <h3 class="mb-0 text-success">
                                <?= count(array_filter($employees, fn($e) => $e->active)) ?>
                            </h3>
                        </div>
                        <div class="rounded-circle p-3" style="background-color: #e8f5e9;">
                            <i class="fas fa-check-circle text-success fa-2x"></i>
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
                            <h6 class="text-muted mb-1">Inativos</h6>
                            <h3 class="mb-0 text-warning">
                                <?= count(array_filter($employees, fn($e) => !$e->active)) ?>
                            </h3>
                        </div>
                        <div class="rounded-circle p-3" style="background-color: #fff3e0;">
                            <i class="fas fa-pause-circle text-warning fa-2x"></i>
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
                            <h6 class="text-muted mb-1">Com Biometria</h6>
                            <h3 class="mb-0 text-info">
                                <?= count(array_filter($employees, fn($e) => $e->has_face_biometric || $e->has_fingerprint_biometric)) ?>
                            </h3>
                        </div>
                        <div class="rounded-circle p-3" style="background-color: #e0f2f1;">
                            <i class="fas fa-fingerprint text-info fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="<?= base_url('employees') ?>">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Buscar</label>
                        <input type="text" class="form-control" name="search"
                               placeholder="Nome, e-mail, CPF ou código"
                               value="<?= esc($filters['search'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Departamento</label>
                        <select class="form-select" name="department">
                            <option value="">Todos</option>
                            <option value="TI" <?= ($filters['department'] ?? '') === 'TI' ? 'selected' : '' ?>>TI</option>
                            <option value="RH" <?= ($filters['department'] ?? '') === 'RH' ? 'selected' : '' ?>>RH</option>
                            <option value="Vendas" <?= ($filters['department'] ?? '') === 'Vendas' ? 'selected' : '' ?>>Vendas</option>
                            <option value="Financeiro" <?= ($filters['department'] ?? '') === 'Financeiro' ? 'selected' : '' ?>>Financeiro</option>
                            <option value="Operações" <?= ($filters['department'] ?? '') === 'Operações' ? 'selected' : '' ?>>Operações</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Cargo</label>
                        <select class="form-select" name="role">
                            <option value="">Todos</option>
                            <option value="admin" <?= ($filters['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrador</option>
                            <option value="gestor" <?= ($filters['role'] ?? '') === 'gestor' ? 'selected' : '' ?>>Gestor</option>
                            <option value="funcionario" <?= ($filters['role'] ?? '') === 'funcionario' ? 'selected' : '' ?>>Funcionário</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">Todos</option>
                            <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Ativos</option>
                            <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inativos</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Filtro Especial</label>
                        <select class="form-select" name="filter">
                            <option value="">Nenhum</option>
                            <option value="no_biometric" <?= ($filters['filter'] ?? '') === 'no_biometric' ? 'selected' : '' ?>>Sem Biometria</option>
                            <option value="pending_approval" <?= ($filters['filter'] ?? '') === 'pending_approval' ? 'selected' : '' ?>>Pendente Aprovação</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Employees Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($employees)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                    <p class="mb-0">Nenhum funcionário encontrado.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>CPF</th>
                                <th>Departamento</th>
                                <th>Cargo</th>
                                <th>Código Único</th>
                                <th>Biometria</th>
                                <th>Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td>#<?= $employee->id ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar me-2">
                                                <i class="fas fa-user-circle fa-2x text-secondary"></i>
                                            </div>
                                            <div>
                                                <strong><?= esc($employee->name) ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= esc($employee->email) ?></td>
                                    <td><?= formatCPF($employee->cpf ?? '') ?></td>
                                    <td>
                                        <span class="badge bg-secondary"><?= esc($employee->department) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $roleBadges = [
                                            'admin' => 'danger',
                                            'gestor' => 'warning',
                                            'funcionario' => 'info',
                                        ];
                                        $badgeClass = $roleBadges[$employee->role] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($employee->role) ?></span>
                                    </td>
                                    <td>
                                        <code><?= esc($employee->unique_code) ?></code>
                                    </td>
                                    <td>
                                        <?php if ($employee->has_face_biometric): ?>
                                            <i class="fas fa-face-smile text-success me-1" title="Facial"></i>
                                        <?php endif; ?>
                                        <?php if ($employee->has_fingerprint_biometric): ?>
                                            <i class="fas fa-fingerprint text-success" title="Digital"></i>
                                        <?php endif; ?>
                                        <?php if (!$employee->has_face_biometric && !$employee->has_fingerprint_biometric): ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($employee->active): ?>
                                            <span class="badge bg-success">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?= base_url('employees/' . $employee->id) ?>"
                                               class="btn btn-outline-primary"
                                               title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?= base_url('employees/edit/' . $employee->id) ?>"
                                               class="btn btn-outline-warning"
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?= base_url('employees/qrcode/' . $employee->id) ?>"
                                               class="btn btn-outline-info"
                                               title="QR Code">
                                                <i class="fas fa-qrcode"></i>
                                            </a>
                                            <button type="button"
                                                    class="btn btn-outline-danger"
                                                    onclick="confirmDelete(<?= $employee->id ?>, '<?= esc($employee->name) ?>')"
                                                    title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    <?= $pager->links() ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    if (confirm(`Tem certeza que deseja excluir o funcionário "${name}"?\n\nEsta ação não pode ser desfeita.`)) {
        // Send DELETE request
        fetch(`<?= base_url('employees/') ?>${id}`, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Funcionário excluído com sucesso!');
                location.reload();
            } else {
                alert('Erro ao excluir funcionário: ' + (data.message || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erro ao excluir funcionário.');
        });
    }
}
</script>
<?= $this->endSection() ?>
