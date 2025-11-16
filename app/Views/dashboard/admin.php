<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="mb-1"><i class="fas fa-tachometer-alt text-primary"></i> Dashboard Administrativo</h2>
            <p class="text-muted">Visão geral completa do sistema de ponto eletrônico</p>
        </div>
    </div>

    <!-- LINHA 1: Cards de Resumo -->
    <div class="row mb-4" id="summary-cards">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-left-primary h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Funcionários Ativos</div>
                            <div class="h3 mb-0 font-weight-bold" id="total-employees">-</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div>
                    </div>
                    <a href="/employees" class="btn btn-sm btn-primary mt-2 btn-block">Ver Todos</a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-left-success h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Marcações Hoje</div>
                            <div class="h3 mb-0 font-weight-bold" id="punches-today">-</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-clock fa-2x text-gray-300"></i></div>
                    </div>
                    <a href="/reports/daily" class="btn btn-sm btn-success mt-2 btn-block">Relatório do Dia</a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-left-warning h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pendências</div>
                            <div class="h3 mb-0 font-weight-bold" id="total-pending">-</div>
                            <small class="text-muted" id="pending-breakdown">-</small>
                        </div>
                        <div class="col-auto"><i class="fas fa-bell fa-2x text-gray-300"></i></div>
                    </div>
                    <a href="/justifications?status=pending" class="btn btn-sm btn-warning mt-2 btn-block">Ver Pendências</a>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card shadow-sm border-left-info h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Saldo Médio</div>
                            <div class="h3 mb-0 font-weight-bold" id="average-balance">-</div>
                        </div>
                        <div class="col-auto"><i class="fas fa-chart-bar fa-2x text-gray-300"></i></div>
                    </div>
                    <small class="text-muted">Este mês</small>
                </div>
            </div>
        </div>
    </div>

    <!-- LINHA 2: Gráficos -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="m-0 font-weight-bold text-primary">Evolução de Marcações (30 dias)</h6>
                </div>
                <div class="card-body"><canvas id="punchesChart" height="80"></canvas></div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="m-0 font-weight-bold text-success">Por Departamento</h6>
                </div>
                <div class="card-body"><canvas id="departmentChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- LINHA 3: Heatmap -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="m-0 font-weight-bold text-danger">Mapa de Calor - Horários de Movimento</h6>
                </div>
                <div class="card-body"><div id="heatmap" style="height:300px;"></div></div>
            </div>
        </div>
    </div>

    <!-- LINHA 4: Alertas + Atividade -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="m-0 font-weight-bold text-warning">Alertas</h6>
                </div>
                <div class="card-body" id="alerts"><div class="spinner-border"></div></div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="m-0 font-weight-bold text-info">Atividade Recente</h6>
                </div>
                <div class="card-body p-0" id="activity" style="max-height:400px;overflow-y:auto;">
                    <div class="spinner-border"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- LINHA 5: Atalhos -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light"><h6 class="m-0">Atalhos Rápidos</h6></div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <a href="/employees/create" class="btn btn-lg btn-primary btn-block">
                                <i class="fas fa-user-plus fa-2x mb-2"></i><br>Cadastrar Funcionário
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="/reports" class="btn btn-lg btn-success btn-block">
                                <i class="fas fa-file-excel fa-2x mb-2"></i><br>Gerar Relatório
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="/settings" class="btn btn-lg btn-warning btn-block">
                                <i class="fas fa-cog fa-2x mb-2"></i><br>Configurações
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="/audit" class="btn btn-lg btn-info btn-block">
                                <i class="fas fa-clipboard-list fa-2x mb-2"></i><br>Logs
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Serviços -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light"><h6 class="m-0">Status dos Serviços</h6></div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4"><h6>MySQL</h6><span class="badge badge-pill badge-lg" id="mysql">...</span></div>
                        <div class="col-md-4"><h6>DeepFace API</h6><span class="badge badge-pill badge-lg" id="deepface">...</span></div>
                        <div class="col-md-4"><h6>WebSocket</h6><span class="badge badge-pill badge-lg" id="websocket">...</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/heatmap.js@2.0.5/build/heatmap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script>
moment.locale('pt-br');
setInterval(loadData, 30000);
$(document).ready(function() { loadData(); checkServices(); });

function loadData() {
    $.get('/api/dashboard/summary', function(d) {
        $('#total-employees').text(d.total_employees||0);
        $('#punches-today').text(d.punches_today||0);
        $('#total-pending').text(d.total_pending||0);
        $('#pending-breakdown').text(d.pending_justifications + ' just, ' + d.pending_warnings + ' adv');
        let bal = parseFloat(d.average_balance||0);
        $('#average-balance').removeClass('text-success text-danger').addClass(bal >= 0 ? 'text-success':'text-danger').text((bal>=0?'+':'') + bal.toFixed(2)+'h');
    });
    
    $.get('/api/dashboard/punches-evolution', function(d) {
        new Chart($('#punchesChart'), {
            type: 'line',
            data: { labels: d.labels, datasets: [{ label: 'Marcações', data: d.values, borderColor: 'rgb(75,192,192)', fill: true }] },
            options: { responsive: true, maintainAspectRatio: false }
        });
    });
    
    $.get('/api/dashboard/department-distribution', function(d) {
        new Chart($('#departmentChart'), {
            type: 'pie',
            data: { labels: d.labels, datasets: [{ data: d.values, backgroundColor: ['#ff6384','#36a2eb','#ffce56','#4bc0c0','#9966ff','#ff9f40'] }] },
            options: { responsive: true }
        });
    });
    
    $.get('/api/dashboard/alerts', function(d) {
        let html = d.length ? '' : '<div class="alert alert-success">Nenhum alerta</div>';
        d.forEach(a => { html += '<div class="alert alert-'+a.type+'">'+a.message+'</div>'; });
        $('#alerts').html(html);
    });
    
    $.get('/api/dashboard/activity', function(d) {
        let html = '<div class="list-group list-group-flush">';
        d.forEach(a => { html += '<div class="list-group-item"><strong>'+a.user+'</strong> '+a.action+' <small>'+moment(a.created_at).fromNow()+'</small></div>'; });
        $('#activity').html(html+'</div>');
    });
}

function checkServices() {
    $.get('/api/services/mysql').done(() => $('#mysql').attr('class','badge badge-pill badge-lg badge-success').text('Online')).fail(() => $('#mysql').attr('class','badge badge-pill badge-lg badge-danger').text('Offline'));
    $.get('/api/services/deepface').done(() => $('#deepface').attr('class','badge badge-pill badge-lg badge-success').text('Online')).fail(() => $('#deepface').attr('class','badge badge-pill badge-lg badge-danger').text('Offline'));
    $.get('/api/services/websocket').done(() => $('#websocket').attr('class','badge badge-pill badge-lg badge-success').text('Online')).fail(() => $('#websocket').attr('class','badge badge-pill badge-lg badge-danger').text('Offline'));
}
</script>

<style>
.border-left-primary { border-left: 4px solid #4e73df !important; }
.border-left-success { border-left: 4px solid #1cc88a !important; }
.border-left-warning { border-left: 4px solid #f6c23e !important; }
.border-left-info { border-left: 4px solid #36b9cc !important; }
.text-xs { font-size: 0.7rem; }
.badge-lg { padding: 0.5rem 1rem; font-size: 1rem; }
</style>

<?= $this->endSection() ?>
