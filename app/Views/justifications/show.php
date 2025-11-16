<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Detalhes da Justificativa #<?= $justification->id ?><?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
    .attachment-thumbnail {
        cursor: pointer;
        transition: transform 0.2s;
        border-radius: 8px;
        overflow: hidden;
    }

    .attachment-thumbnail:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }

    .attachment-thumbnail img {
        width: 150px;
        height: 150px;
        object-fit: cover;
    }

    .pdf-icon {
        width: 150px;
        height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
    }

    .timeline {
        position: relative;
        padding-left: 30px;
    }

    .timeline::before {
        content: '';
        position: absolute;
        left: 8px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dee2e6;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 1.5rem;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -26px;
        top: 4px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: white;
        border: 3px solid #0d6efd;
    }

    .timeline-item.success::before {
        border-color: #198754;
    }

    .timeline-item.danger::before {
        border-color: #dc3545;
    }

    .timeline-item.warning::before {
        border-color: #ffc107;
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
                        <i class="fas fa-file-alt me-2"></i>Detalhes da Justificativa #<?= $justification->id ?>
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="<?= base_url('justifications') ?>">Justificativas</a></li>
                            <li class="breadcrumb-item active">#<?= $justification->id ?></li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="<?= base_url('justifications') ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Status Alert -->
            <?php
            $statusClass = [
                'pendente' => 'warning',
                'aprovado' => 'success',
                'rejeitado' => 'danger',
            ][$justification->status] ?? 'secondary';

            $statusIcon = [
                'pendente' => 'clock',
                'aprovado' => 'check-circle',
                'rejeitado' => 'times-circle',
            ][$justification->status] ?? 'info-circle';

            $statusText = [
                'pendente' => 'Aguardando Aprovação',
                'aprovado' => 'Aprovada',
                'rejeitado' => 'Rejeitada',
            ][$justification->status] ?? $justification->status;
            ?>

            <div class="alert alert-<?= $statusClass ?> d-flex align-items-center mb-4">
                <i class="fas fa-<?= $statusIcon ?> fa-2x me-3"></i>
                <div>
                    <h5 class="mb-0">Status: <?= $statusText ?></h5>
                    <?php if ($justification->status === 'pendente'): ?>
                        <small>Esta justificativa está aguardando análise do gestor.</small>
                    <?php elseif ($justification->status === 'aprovado'): ?>
                        <small>Aprovada por <?= esc($reviewer->name ?? 'N/A') ?> em <?= date('d/m/Y H:i', strtotime($justification->reviewed_at)) ?></small>
                    <?php elseif ($justification->status === 'rejeitado'): ?>
                        <small>Rejeitada por <?= esc($reviewer->name ?? 'N/A') ?> em <?= date('d/m/Y H:i', strtotime($justification->reviewed_at)) ?></small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Details Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Informações da Justificativa
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted small">Funcionário</label>
                            <p class="mb-0"><strong><?= esc($justificationEmployee->name) ?></strong></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Data da Ocorrência</label>
                            <p class="mb-0"><strong><?= date('d/m/Y', strtotime($justification->justification_date)) ?></strong></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted small">Tipo</label>
                            <p class="mb-0">
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
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Categoria</label>
                            <p class="mb-0">
                                <?php
                                $categories = [
                                    'doenca' => '🏥 Doença',
                                    'compromisso-pessoal' => '📅 Compromisso Pessoal',
                                    'emergencia-familiar' => '🚨 Emergência Familiar',
                                    'outro' => '📝 Outro',
                                ];
                                echo $categories[$justification->category] ?? $justification->category;
                                ?>
                            </p>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="text-muted small">Motivo</label>
                        <div class="p-3 bg-light rounded">
                            <?= nl2br(esc($justification->reason)) ?>
                        </div>
                    </div>

                    <?php if ($justification->status === 'rejeitado' && $justification->rejection_reason): ?>
                        <div class="mb-3">
                            <label class="text-muted small">Motivo da Rejeição</label>
                            <div class="p-3 bg-danger bg-opacity-10 border border-danger rounded">
                                <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                                <?= nl2br(esc($justification->rejection_reason)) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($justification->reviewed_by && isset($reviewer)): ?>
                        <div class="mb-0">
                            <label class="text-muted small">Revisado por</label>
                            <p class="mb-0">
                                <strong><?= esc($reviewer->name) ?></strong>
                                em <?= date('d/m/Y H:i', strtotime($justification->reviewed_at)) ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Attachments Card -->
            <?php
            $attachments = is_string($justification->attachments)
                ? json_decode($justification->attachments, true)
                : $justification->attachments;
            ?>

            <?php if (!empty($attachments) && is_array($attachments)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-paperclip me-2"></i>Anexos (<?= count($attachments) ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php foreach ($attachments as $index => $attachment): ?>
                                <?php
                                $fullPath = WRITEPATH . $attachment;
                                $extension = pathinfo($attachment, PATHINFO_EXTENSION);
                                $isPdf = strtolower($extension) === 'pdf';
                                $fileExists = file_exists($fullPath);
                                ?>

                                <?php if ($fileExists): ?>
                                    <div class="col-md-4">
                                        <div class="attachment-thumbnail" onclick="viewAttachment(<?= $index ?>)">
                                            <?php if ($isPdf): ?>
                                                <div class="pdf-icon">
                                                    <div class="text-center">
                                                        <i class="fas fa-file-pdf fa-4x mb-2"></i>
                                                        <div class="small">PDF</div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <img src="<?= base_url('writable/' . $attachment) ?>"
                                                     alt="Anexo <?= $index + 1 ?>"
                                                     class="img-fluid">
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-center mt-2">
                                            <small class="text-muted">
                                                <?= basename($attachment) ?>
                                            </small>
                                            <br>
                                            <a href="<?= base_url('writable/' . $attachment) ?>"
                                               target="_blank"
                                               class="btn btn-sm btn-outline-primary mt-1">
                                                <i class="fas fa-download me-1"></i>Download
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Action Buttons for Managers/Admins -->
            <?php if ($justification->status === 'pendente' && in_array($employee['role'], ['admin', 'gestor'])): ?>
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-gavel me-2"></i>Ações de Aprovação
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Analise a justificativa e aprove ou rejeite conforme necessário.</p>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <button type="button"
                                        class="btn btn-success w-100"
                                        data-bs-toggle="modal"
                                        data-bs-target="#approveModal">
                                    <i class="fas fa-check-circle me-2"></i>Aprovar Justificativa
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button type="button"
                                        class="btn btn-danger w-100"
                                        data-bs-toggle="modal"
                                        data-bs-target="#rejectModal">
                                    <i class="fas fa-times-circle me-2"></i>Rejeitar Justificativa
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Timeline Card -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>Linha do Tempo
                    </h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <!-- Created -->
                        <div class="timeline-item warning">
                            <div>
                                <strong>Justificativa Criada</strong>
                                <div class="text-muted small">
                                    <?= date('d/m/Y H:i', strtotime($justification->created_at)) ?>
                                </div>
                                <div class="small mt-1">
                                    Por <?= esc($justificationEmployee->name) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Reviewed -->
                        <?php if ($justification->reviewed_at): ?>
                            <div class="timeline-item <?= $justification->status === 'aprovado' ? 'success' : 'danger' ?>">
                                <div>
                                    <strong><?= $justification->status === 'aprovado' ? 'Aprovada' : 'Rejeitada' ?></strong>
                                    <div class="text-muted small">
                                        <?= date('d/m/Y H:i', strtotime($justification->reviewed_at)) ?>
                                    </div>
                                    <?php if ($reviewer): ?>
                                        <div class="small mt-1">
                                            Por <?= esc($reviewer->name) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle me-2"></i>Aprovar Justificativa
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url("justifications/{$justification->id}/approve") ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <p>Tem certeza que deseja <strong>aprovar</strong> esta justificativa?</p>

                    <div class="mb-3">
                        <label for="approve_notes" class="form-label">Observações (Opcional)</label>
                        <textarea class="form-control"
                                  id="approve_notes"
                                  name="notes"
                                  rows="3"
                                  placeholder="Adicione observações sobre a aprovação..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Confirmar Aprovação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-times-circle me-2"></i>Rejeitar Justificativa
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?= base_url("justifications/{$justification->id}/reject") ?>" method="POST" id="rejectForm">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <p>Tem certeza que deseja <strong>rejeitar</strong> esta justificativa?</p>

                    <div class="mb-3">
                        <label for="reject_notes" class="form-label">
                            Motivo da Rejeição <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control"
                                  id="reject_notes"
                                  name="notes"
                                  rows="3"
                                  placeholder="Explique o motivo da rejeição..."
                                  required></textarea>
                        <div class="form-text">
                            O funcionário receberá uma notificação com este motivo.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-2"></i>Confirmar Rejeição
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Attachment Viewer Modal -->
<div class="modal fade" id="attachmentModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-image me-2"></i>Visualizar Anexo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center" id="attachmentViewer">
                <!-- Populated by JavaScript -->
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Attachment viewer
    const attachments = <?= json_encode($attachments ?? []) ?>;

    function viewAttachment(index) {
        const attachment = attachments[index];
        const extension = attachment.split('.').pop().toLowerCase();
        const isPdf = extension === 'pdf';

        const viewer = document.getElementById('attachmentViewer');

        if (isPdf) {
            viewer.innerHTML = `
                <iframe src="<?= base_url('writable/') ?>${attachment}"
                        style="width: 100%; height: 70vh; border: none;">
                </iframe>
                <div class="mt-3">
                    <a href="<?= base_url('writable/') ?>${attachment}"
                       target="_blank"
                       class="btn btn-primary">
                        <i class="fas fa-external-link-alt me-2"></i>Abrir em Nova Aba
                    </a>
                </div>
            `;
        } else {
            viewer.innerHTML = `
                <img src="<?= base_url('writable/') ?>${attachment}"
                     class="img-fluid"
                     style="max-height: 70vh;">
            `;
        }

        const modal = new bootstrap.Modal(document.getElementById('attachmentModal'));
        modal.show();
    }

    // Validate reject form
    document.getElementById('rejectForm')?.addEventListener('submit', function(e) {
        const notes = document.getElementById('reject_notes').value.trim();
        if (notes.length < 10) {
            e.preventDefault();
            alert('O motivo da rejeição deve ter no mínimo 10 caracteres.');
            document.getElementById('reject_notes').focus();
        }
    });
</script>
<?= $this->endSection() ?>
