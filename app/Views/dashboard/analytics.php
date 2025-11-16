<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($page_title ?? 'Analytics') ?> - Ponto Eletrônico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .kpi-card { border-left: 4px solid; transition: transform 0.2s; }
        .kpi-card:hover { transform: translateY(-5px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .kpi-value { font-size: 2rem; font-weight: bold; }
        .kpi-label { color: #6c757d; font-size: 0.875rem; text-transform: uppercase; }
        .chart-container { position: relative; height: 300px; }
        .activity-item { border-left: 3px solid #007bff; padding-left: 1rem; margin-bottom: 1rem; }
        .filter-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard"><i class="fas fa-chart-line"></i> Dashboard Analytics</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text text-white me-3"><i class="fas fa-user"></i> <?= esc($employee->name ?? 'Usuário') ?></span>
                <a href="/auth/logout" class="btn btn-outline-light btn-sm"><i class="fas fa-sign-out-alt"></i> Sair</a>
            </div>
        </div>
    </nav>
    <div class="container-fluid mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card filter-card">
                    <div class="card-body">
                        <h5 class="card-title mb-3"><i class="fas fa-filter"></i> Filtros</h5>
                        <form method="GET" action="/dashboard/analytics" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Data Inicial</label>
                                <input type="date" name="start_date" class="form-control" value="<?= esc($filters['startDate']) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Data Final</label>
                                <input type="date" name="end_date" class="form-control" value="<?= esc($filters['endDate']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Departamento</label>
                                <select name="department_id" class="form-select">
                                    <option value="">Todos os Departamentos</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= $dept['id'] ?>" <?= $filters['departmentId'] == $dept['id'] ? 'selected' : '' ?>><?= esc($dept['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-light w-100"><i class="fas fa-search"></i> Filtrar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card kpi-card" style="border-left-color: #007bff;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="kpi-label">Total Funcionários</div>
                                <div class="kpi-value text-primary"><?= number_format($kpis['total_employees']) ?></div>
                            </div>
                            <i class="fas fa-users fa-3x text-primary opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card kpi-card" style="border-left-color: #28a745;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="kpi-label">Funcionários Ativos</div>
                                <div class="kpi-value text-success"><?= number_format($kpis['active_employees']) ?></div>
                            </div>
                            <i class="fas fa-user-check fa-3x text-success opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card kpi-card" style="border-left-color: #ffc107;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="kpi-label">Batidas no Período</div>
                                <div class="kpi-value text-warning"><?= number_format($kpis['punches_today']) ?></div>
                            </div>
                            <i class="fas fa-fingerprint fa-3x text-warning opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card kpi-card" style="border-left-color: #17a2b8;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="kpi-label">Total de Horas</div>
                                <div class="kpi-value text-info"><?= number_format($kpis['total_hours'], 1) ?>h</div>
                            </div>
                            <i class="fas fa-clock fa-3x text-info opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card kpi-card" style="border-left-color: #dc3545;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="kpi-label">Aprovações Pendentes</div>
                                <div class="kpi-value text-danger"><?= number_format($kpis['pending_approvals']) ?></div>
                            </div>
                            <i class="fas fa-hourglass-half fa-2x text-danger opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card kpi-card" style="border-left-color: #6f42c1;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="kpi-label">Média Horas/Funcionário</div>
                                <div class="kpi-value text-purple"><?= number_format($kpis['avg_hours_per_employee'], 1) ?>h</div>
                            </div>
                            <i class="fas fa-chart-bar fa-2x text-purple opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card kpi-card" style="border-left-color: #20c997;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="kpi-label">Taxa de Presença</div>
                                <div class="kpi-value text-teal"><?= number_format($attendance_rate, 1) ?>%</div>
                            </div>
                            <i class="fas fa-percentage fa-2x text-teal opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white"><i class="fas fa-chart-line"></i> Batidas por Hora</div>
                    <div class="card-body"><div class="chart-container"><canvas id="punchesByHourChart"></canvas></div></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-success text-white"><i class="fas fa-chart-pie"></i> Status dos Funcionários</div>
                    <div class="card-body"><div class="chart-container"><canvas id="employeeStatusChart"></canvas></div></div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-info text-white"><i class="fas fa-chart-bar"></i> Horas por Departamento</div>
                    <div class="card-body"><div class="chart-container"><canvas id="hoursByDepartmentChart"></canvas></div></div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-warning"><i class="fas fa-trophy"></i> Top 10 Funcionários</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-sm">
                                <thead><tr><th>#</th><th>Nome</th><th>Departamento</th><th class="text-end">Horas</th></tr></thead>
                                <tbody>
                                    <?php foreach ($top_employees as $index => $emp): ?>
                                        <tr><td><?= $index + 1 ?></td><td><?= esc($emp['name']) ?></td><td><?= esc($emp['department']) ?></td><td class="text-end"><strong><?= number_format($emp['total_hours'], 1) ?>h</strong></td></tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-secondary text-white"><i class="fas fa-history"></i> Atividade Recente</div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <?php foreach ($recent_activity as $activity): ?>
                            <div class="activity-item">
                                <strong><?= esc($activity['employee_name']) ?></strong> <small class="text-muted">(<?= esc($activity['department']) ?>)</small><br>
                                <small><i class="fas fa-clock"></i> <?= esc($activity['formatted_time']) ?>
                                <?php if ($activity['punch_out_time']): ?>
                                    <span class="badge bg-success">Completo</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Em andamento</span>
                                <?php endif; ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-4">
            <div class="col-md-12 text-center">
                <a href="/dashboard/export?<?= http_build_query($filters) ?>" class="btn btn-success btn-lg"><i class="fas fa-file-csv"></i> Exportar Relatório (CSV)</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    new Chart(document.getElementById('punchesByHourChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: <?= json_encode($charts['punches_by_hour']['labels']) ?>,
            datasets: [{ label: 'Batidas', data: <?= json_encode($charts['punches_by_hour']['data']) ?>, borderColor: 'rgb(75, 192, 192)', backgroundColor: 'rgba(75, 192, 192, 0.2)', tension: 0.4, fill: true }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true, position: 'top' }}, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }}}}
    });
    new Chart(document.getElementById('employeeStatusChart').getContext('2d'), {
        type: 'pie',
        data: { labels: <?= json_encode($charts['employee_status']['labels']) ?>, datasets: [{ data: <?= json_encode($charts['employee_status']['data']) ?>, backgroundColor: <?= json_encode($charts['employee_status']['colors']) ?> }]},
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' }}}
    });
    new Chart(document.getElementById('hoursByDepartmentChart').getContext('2d'), {
        type: 'bar',
        data: { labels: <?= json_encode($charts['hours_by_department']['labels']) ?>, datasets: [{ label: 'Horas Trabalhadas', data: <?= json_encode($charts['hours_by_department']['data']) ?>, backgroundColor: 'rgba(54, 162, 235, 0.5)', borderColor: 'rgba(54, 162, 235, 1)', borderWidth: 1 }]},
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: true, position: 'top' }}, scales: { y: { beginAtZero: true, title: { display: true, text: 'Horas' }}}}
    });
    </script>
</body>
</html>
