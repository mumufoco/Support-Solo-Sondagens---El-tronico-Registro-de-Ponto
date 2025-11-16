<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="mb-1"><i class="fas fa-clipboard-list text-primary"></i> Auditoria e Logs</h2>
            <p class="text-muted">Visualize e analise todos os logs de auditoria do sistema</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-left-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total de Logs</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['total']) ?></div>
                        </div>
                        <div>
                            <i class="fas fa-database fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-left-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Logs Hoje</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['today']) ?></div>
                        </div>
                        <div>
                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-left-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Esta Semana</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['this_week']) ?></div>
                        </div>
                        <div>
                            <i class="fas fa-calendar-week fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm border-left-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Críticos (30d)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['critical']) ?></div>
                        </div>
                        <div>
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Charts -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-users"></i> Usuários Mais Ativos (30 dias)</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($stats['active_users'])): ?>
                        <p class="text-muted text-center">Sem dados</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($stats['active_users'] as $user): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= esc($user['name']) ?>
                                    <span class="badge badge-primary badge-pill"><?= number_format($user['count']) ?> ações</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Ações Mais Comuns (30 dias)</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($stats['common_actions'])): ?>
                        <p class="text-muted text-center">Sem dados</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($stats['common_actions'] as $action): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="badge badge-secondary"><?= esc($action->action) ?></span>
                                    <span class="badge badge-info badge-pill"><?= number_format($action->count) ?>x</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-filter"></i> Filtros</h6>
                    <button class="btn btn-sm btn-secondary" onclick="clearFilters()">
                        <i class="fas fa-times"></i> Limpar Filtros
                    </button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_user_id">Usuário</label>
                                <select class="form-control" id="filter_user_id">
                                    <option value="">Todos</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?= $user->id ?>"><?= esc($user->name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="filter_action">Ação</label>
                                <select class="form-control" id="filter_action">
                                    <option value="">Todas</option>
                                    <?php foreach ($actions as $action): ?>
                                        <option value="<?= esc($action['action']) ?>"><?= esc($action['action']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="filter_entity">Entidade</label>
                                <select class="form-control" id="filter_entity">
                                    <option value="">Todas</option>
                                    <?php foreach ($entities as $entity): ?>
                                        <option value="<?= esc($entity['entity_type']) ?>"><?= esc($entity['entity_type']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="filter_level">Nível</label>
                                <select class="form-control" id="filter_level">
                                    <option value="">Todos</option>
                                    <?php foreach ($levels as $level): ?>
                                        <option value="<?= $level ?>"><?= strtoupper($level) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_start_date">Data Início</label>
                                <input type="date" class="form-control" id="filter_start_date">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_end_date">Data Fim</label>
                                <input type="date" class="form-control" id="filter_end_date">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button class="btn btn-primary btn-block" onclick="applyFilters()">
                                    <i class="fas fa-search"></i> Filtrar
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <a href="/audit/export" class="btn btn-success btn-block">
                                    <i class="fas fa-file-csv"></i> Exportar CSV
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Audit Logs Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Registros de Auditoria</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="auditTable" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuário</th>
                                    <th>Ação</th>
                                    <th>Entidade</th>
                                    <th>Descrição</th>
                                    <th>Nível</th>
                                    <th>IP</th>
                                    <th>Data/Hora</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- DataTables will populate this -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Detalhes do Log de Auditoria</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="detailsContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Carregando...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
let table;

$(document).ready(function() {
    // Initialize DataTable
    table = $('#auditTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/audit/data',
            type: 'POST',
            data: function(d) {
                d.filter_user_id = $('#filter_user_id').val();
                d.filter_action = $('#filter_action').val();
                d.filter_entity = $('#filter_entity').val();
                d.filter_level = $('#filter_level').val();
                d.filter_start_date = $('#filter_start_date').val();
                d.filter_end_date = $('#filter_end_date').val();
            }
        },
        columns: [
            { data: 'id' },
            { data: 'user' },
            { data: 'action', orderable: false },
            { data: 'entity' },
            { data: 'description' },
            { data: 'level', orderable: false },
            { data: 'ip_address' },
            { data: 'created_at' },
            {
                data: 'details',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return `<button class="btn btn-sm btn-info" onclick="viewDetails(${data})">
                        <i class="fas fa-eye"></i> Ver
                    </button>`;
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json'
        }
    });
});

function applyFilters() {
    table.ajax.reload();
}

function clearFilters() {
    $('#filter_user_id').val('');
    $('#filter_action').val('');
    $('#filter_entity').val('');
    $('#filter_level').val('');
    $('#filter_start_date').val('');
    $('#filter_end_date').val('');
    table.ajax.reload();
}

async function viewDetails(id) {
    $('#detailsModal').modal('show');
    $('#detailsContent').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Carregando...</span>
            </div>
        </div>
    `);

    try {
        const response = await fetch(`/audit/details/${id}`);
        const result = await response.json();

        if (result.success) {
            const log = result.log;
            const emp = result.employee;

            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>ID:</strong> ${log.id}</p>
                        <p><strong>Ação:</strong> <span class="badge badge-primary">${log.action}</span></p>
                        <p><strong>Entidade:</strong> ${log.entity_type} ${log.entity_id ? '#' + log.entity_id : ''}</p>
                        <p><strong>Nível:</strong> <span class="badge badge-${log.level === 'critical' ? 'danger' : log.level === 'error' ? 'danger' : log.level === 'warning' ? 'warning' : 'info'}">${log.level.toUpperCase()}</span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Usuário:</strong> ${emp ? emp.name + ' (' + emp.email + ')' : 'Sistema'}</p>
                        <p><strong>Data/Hora:</strong> ${log.created_at}</p>
                        <p><strong>IP:</strong> ${log.ip_address || '-'}</p>
                        <p><strong>URL:</strong> <small>${log.url || '-'}</small></p>
                        <p><strong>Método HTTP:</strong> ${log.method || '-'}</p>
                    </div>
                </div>
                <hr>
                <p><strong>Descrição:</strong></p>
                <p class="text-muted">${log.description || '-'}</p>
            `;

            if (log.old_values || log.new_values) {
                html += '<hr><h6>Alterações de Dados:</h6>';

                if (log.old_values) {
                    html += '<p><strong>Valores Antigos:</strong></p>';
                    html += '<pre class="bg-light p-2 rounded">' + JSON.stringify(log.old_values, null, 2) + '</pre>';
                }

                if (log.new_values) {
                    html += '<p><strong>Valores Novos:</strong></p>';
                    html += '<pre class="bg-light p-2 rounded">' + JSON.stringify(log.new_values, null, 2) + '</pre>';
                }
            }

            if (log.user_agent) {
                html += '<hr><p><strong>User Agent:</strong></p>';
                html += '<p class="small text-muted">' + log.user_agent + '</p>';
            }

            $('#detailsContent').html(html);
        } else {
            $('#detailsContent').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> ${result.message}
                </div>
            `);
        }
    } catch (error) {
        $('#detailsContent').html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> Erro ao carregar detalhes: ${error.message}
            </div>
        `);
    }
}
</script>

<style>
.border-left-primary {
    border-left: 4px solid #4e73df !important;
}

.border-left-success {
    border-left: 4px solid #1cc88a !important;
}

.border-left-info {
    border-left: 4px solid #36b9cc !important;
}

.border-left-danger {
    border-left: 4px solid #e74a3b !important;
}

.text-xs {
    font-size: 0.7rem;
}

pre {
    white-space: pre-wrap;
    word-wrap: break-word;
}
</style>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">

<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>

<?= $this->endSection() ?>
