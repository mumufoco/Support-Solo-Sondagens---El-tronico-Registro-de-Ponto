<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Justificativas<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<style>
    .status-badge {
        font-size: 0.85rem;
        padding: 0.4rem 0.8rem;
        font-weight: 500;
    }

    .attachment-preview {
        max-width: 100%;
        max-height: 500px;
        object-fit: contain;
    }

    .file-icon {
        font-size: 3rem;
        color: #dc3545;
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
                        <i class="fas fa-file-alt me-2"></i>Justificativas
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                            <li class="breadcrumb-item active">Justificativas</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="<?= base_url('justifications/create') ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Nova Justificativa
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
                            <h3 class="mb-0"><?= $counts['all'] ?? 0 ?></h3>
                        </div>
                        <div class="rounded-circle p-3" style="background-color: #e3f2fd;">
                            <i class="fas fa-list text-primary fa-2x"></i>
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
                            <h6 class="text-muted mb-1">Pendentes</h6>
                            <h3 class="mb-0 text-warning"><?= $counts['pending'] ?? 0 ?></h3>
                        </div>
                        <div class="rounded-circle p-3" style="background-color: #fff3e0;">
                            <i class="fas fa-clock text-warning fa-2x"></i>
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
                            <h6 class="text-muted mb-1">Aprovadas</h6>
                            <h3 class="mb-0 text-success"><?= $counts['approved'] ?? 0 ?></h3>
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
                            <h6 class="text-muted mb-1">Rejeitadas</h6>
                            <h3 class="mb-0 text-danger"><?= $counts['rejected'] ?? 0 ?></h3>
                        </div>
                        <div class="rounded-circle p-3" style="background-color: #fce4ec;">
                            <i class="fas fa-times-circle text-danger fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" action="<?= base_url('justifications') ?>" class="row g-2 align-items-center">
                <div class="col-auto">
                    <label class="form-label mb-0 small text-muted">Filtrar por status:</label>
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Todas</option>
                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pendentes</option>
                        <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Aprovadas</option>
                        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejeitadas</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Justifications Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-table me-2"></i>Lista de Justificativas
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($justifications)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-3 opacity-50"></i>
                    <h5 class="text-muted">Nenhuma justificativa encontrada</h5>
                    <p class="text-muted mb-4">Crie sua primeira justificativa clicando no botão acima</p>
                    <a href="<?= base_url('justifications/create') ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Nova Justificativa
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="justificationsTable">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <?php if (in_array($employee['role'], ['admin', 'gestor'])): ?>
                                    <th>Funcionário</th>
                                <?php endif; ?>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Categoria</th>
                                <th>Motivo</th>
                                <th>Anexos</th>
                                <th>Status</th>
                                <th>Enviado em</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($justifications as $justification): ?>
                                <tr>
                                    <td><?= $justification->id ?></td>
                                    <?php if (in_array($employee['role'], ['admin', 'gestor'])): ?>
                                        <td>
                                            <strong><?= esc($justification->employee_name ?? 'N/A') ?></strong>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <?= date('d/m/Y', strtotime($justification->justification_date)) ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php
                                            $types = [
                                                'falta' => 'Falta',
                                                'atraso' => 'Atraso',
                                                'saida-antecipada' => 'Saída Antecipada',
                                            ];
                                            echo $types[$justification->justification_type] ?? $justification->justification_type;
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            <?php
                                            $categories = [
                                                'doenca' => 'Doença',
                                                'compromisso-pessoal' => 'Compromisso Pessoal',
                                                'emergencia-familiar' => 'Emergência Familiar',
                                                'outro' => 'Outro',
                                            ];
                                            echo $categories[$justification->category] ?? $justification->category;
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= mb_substr($justification->reason, 0, 50) ?>...
                                        </small>
                                    </td>
                                    <td>
                                        <?php
                                        $attachments = is_string($justification->attachments)
                                            ? json_decode($justification->attachments, true)
                                            : $justification->attachments;
                                        ?>
                                        <?php if (!empty($attachments) && is_array($attachments)): ?>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    onclick="viewAttachments(<?= $justification->id ?>)">
                                                <i class="fas fa-paperclip me-1"></i>
                                                <?= count($attachments) ?> arquivo(s)
                                            </button>
                                        <?php else: ?>
                                            <small class="text-muted">-</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badgeClass = [
                                            'pendente' => 'bg-warning',
                                            'aprovado' => 'bg-success',
                                            'rejeitado' => 'bg-danger',
                                        ][$justification->status] ?? 'bg-secondary';

                                        $statusText = [
                                            'pendente' => 'Pendente',
                                            'aprovado' => 'Aprovado',
                                            'rejeitado' => 'Rejeitado',
                                        ][$justification->status] ?? $justification->status;
                                        ?>
                                        <span class="badge <?= $badgeClass ?> status-badge">
                                            <?= $statusText ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($justification->created_at)) ?>
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group" role="group">
                                            <a href="<?= base_url("justifications/{$justification->id}") ?>"
                                               class="btn btn-sm btn-outline-info"
                                               title="Ver detalhes">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($justification->status === 'pendente' && $justification->employee_id === $employee['id']): ?>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="Excluir"
                                                        onclick="deleteJustification(<?= $justification->id ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
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

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">Tem certeza que deseja excluir esta justificativa?</p>
                <p class="text-muted small mt-2 mb-0">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteForm" method="POST" class="d-inline">
                    <input type="hidden" name="_method" value="DELETE">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Attachments Modal -->
<div class="modal fade" id="attachmentsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-paperclip me-2"></i>Anexos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="attachmentsContent">
                <!-- Populated by JavaScript -->
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
    // DataTables initialization
    <?php if (!empty($justifications)): ?>
    $(document).ready(function() {
        $('#justificationsTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
            },
            order: [[0, 'desc']],
            pageLength: 25,
            columnDefs: [
                { orderable: false, targets: [-1] } // Last column (actions)
            ]
        });
    });
    <?php endif; ?>

    // Delete justification
    function deleteJustification(id) {
        document.getElementById('deleteForm').action = '<?= base_url('justifications') ?>/' + id;
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }

    // View attachments
    function viewAttachments(id) {
        // Fetch justification data
        fetch('<?= base_url('justifications') ?>/' + id)
            .then(response => response.text())
            .then(html => {
                // Extract attachments from response (simple approach)
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                // For now, show simple message (will be enhanced in show.php)
                document.getElementById('attachmentsContent').innerHTML =
                    '<p class="text-muted">Clique em "Ver detalhes" para visualizar os anexos.</p>';

                const modal = new bootstrap.Modal(document.getElementById('attachmentsModal'));
                modal.show();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao carregar anexos.');
            });
    }
</script>
<?= $this->endSection() ?>
