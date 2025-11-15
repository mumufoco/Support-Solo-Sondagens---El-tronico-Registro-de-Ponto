<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Geofences<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-map-marker-alt me-2"></i>Geofences (Cercas Virtuais)
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                            <li class="breadcrumb-item active">Geofences</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="<?= base_url('geofences/map') ?>" class="btn btn-outline-primary me-2">
                        <i class="fas fa-map me-2"></i>Ver Mapa
                    </a>
                    <a href="<?= base_url('geofences/create') ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Nova Geofence
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
                            <h6 class="text-muted mb-1">Total de Geofences</h6>
                            <h3 class="mb-0"><?= count($geofences) ?></h3>
                        </div>
                        <div class="rounded-circle p-3" style="background-color: #e3f2fd;">
                            <i class="fas fa-map-marked-alt text-primary fa-2x"></i>
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
                            <h6 class="text-muted mb-1">Ativas</h6>
                            <h3 class="mb-0 text-success">
                                <?= count(array_filter($geofences, fn($g) => $g->active)) ?>
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
                            <h6 class="text-muted mb-1">Inativas</h6>
                            <h3 class="mb-0 text-warning">
                                <?= count(array_filter($geofences, fn($g) => !$g->active)) ?>
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
                            <h6 class="text-muted mb-1">Raio Médio</h6>
                            <h3 class="mb-0">
                                <?php
                                $avgRadius = empty($geofences) ? 0 : array_sum(array_map(fn($g) => $g->radius_meters, $geofences)) / count($geofences);
                                echo round($avgRadius);
                                ?> m
                            </h3>
                        </div>
                        <div class="rounded-circle p-3" style="background-color: #fce4ec;">
                            <i class="fas fa-draw-circle text-danger fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Geofences Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Lista de Geofences
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($geofences)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-map-marker-alt fa-4x text-muted mb-3 opacity-50"></i>
                    <h5 class="text-muted">Nenhuma geofence cadastrada</h5>
                    <p class="text-muted mb-4">Crie sua primeira cerca virtual para começar a monitorar localizações</p>
                    <a href="<?= base_url('geofences/create') ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Criar Primeira Geofence
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="geofencesTable">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Nome</th>
                                <th>Descrição</th>
                                <th>Coordenadas</th>
                                <th>Raio</th>
                                <th>Status</th>
                                <th>Criado em</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($geofences as $geofence): ?>
                                <tr>
                                    <td><?= $geofence->id ?></td>
                                    <td>
                                        <strong><?= esc($geofence->name) ?></strong>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= esc($geofence->description ?: '-') ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small class="font-monospace">
                                            <i class="fas fa-location-dot me-1 text-primary"></i>
                                            <?= number_format($geofence->latitude, 6) ?>,
                                            <?= number_format($geofence->longitude, 6) ?>
                                        </small>
                                        <br>
                                        <a href="https://www.google.com/maps?q=<?= $geofence->latitude ?>,<?= $geofence->longitude ?>"
                                           target="_blank"
                                           class="text-decoration-none small">
                                            <i class="fas fa-external-link-alt me-1"></i>Ver no Google Maps
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= $geofence->radius_meters ?> metros
                                        </span>
                                    </td>
                                    <td>
                                        <form action="<?= base_url("geofences/{$geofence->id}/toggle") ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm <?= $geofence->active ? 'btn-success' : 'btn-secondary' ?>">
                                                <i class="fas fa-<?= $geofence->active ? 'check' : 'times' ?> me-1"></i>
                                                <?= $geofence->active ? 'Ativa' : 'Inativa' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($geofence->created_at)) ?>
                                        </small>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group" role="group">
                                            <a href="<?= base_url("geofences/{$geofence->id}") ?>"
                                               class="btn btn-sm btn-outline-info"
                                               title="Ver detalhes">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?= base_url("geofences/{$geofence->id}/edit") ?>"
                                               class="btn btn-sm btn-outline-primary"
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Excluir"
                                                    onclick="deleteGeofence(<?= $geofence->id ?>, '<?= esc($geofence->name) ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
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
                <p class="mb-0">Tem certeza que deseja excluir a geofence <strong id="geofenceName"></strong>?</p>
                <p class="text-muted small mt-2 mb-0">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteForm" method="POST" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function deleteGeofence(id, name) {
        document.getElementById('geofenceName').textContent = name;
        document.getElementById('deleteForm').action = '<?= base_url('geofences') ?>/' + id;

        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }

    // DataTables for better table experience (optional, if you have DataTables)
    <?php if (!empty($geofences)): ?>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $.fn.dataTable !== 'undefined') {
            $('#geofencesTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
                },
                order: [[0, 'desc']],
                pageLength: 25
            });
        }
    });
    <?php endif; ?>
</script>
<?= $this->endSection() ?>
