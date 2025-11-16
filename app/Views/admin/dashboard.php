<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Admin Dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0"><i class="fas fa-tachometer-alt me-2"></i>Dashboard Administrativo</h1>
        <div>
            <a href="/admin/settings" class="btn btn-outline-primary">
                <i class="fas fa-cog me-1"></i>Configurações
            </a>
            <a href="/admin/reports" class="btn btn-outline-success">
                <i class="fas fa-chart-line me-1"></i>Relatórios
            </a>
        </div>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Funcionários Ativos</h6>
                            <h2 class="mb-0"><?= $total_employees ?? 0 ?></h2>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Marcações Hoje</h6>
                            <h2 class="mb-0"><?= $punches_today ?? 0 ?></h2>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Pendências</h6>
                            <h2 class="mb-0"><?= $pending_justifications ?? 0 ?></h2>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Cadastros Faciais</h6>
                            <h2 class="mb-0"><?= $enrolled_faces ?? 0 ?></h2>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-user-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Gráfico de Marcações -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Marcações Últimos 7 Dias</h5>
                </div>
                <div class="card-body">
                    <canvas id="punchesChart" height="80"></canvas>
                </div>
            </div>
        </div>

        <!-- Alertas -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Alertas</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($alerts)): ?>
                        <?php foreach ($alerts as $alert): ?>
                            <div class="alert alert-<?= $alert['type'] ?> d-flex align-items-center" role="alert">
                                <i class="fas <?= $alert['icon'] ?> me-2"></i>
                                <div class="flex-grow-1">
                                    <?= $alert['message'] ?>
                                </div>
                                <?php if (isset($alert['link'])): ?>
                                    <a href="<?= $alert['link'] ?>" class="btn btn-sm btn-outline-<?= $alert['type'] ?>">
                                        Ver
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0">
                            <i class="fas fa-check-circle me-1"></i>
                            Nenhum alerta no momento
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Consentimentos LGPD -->
            <?php if ($pending_consents > 0): ?>
            <div class="card mb-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="fas fa-shield-alt me-2"></i>LGPD</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong><?= $pending_consents ?></strong> funcionário(s) sem consentimento LGPD.
                    </p>
                    <a href="/admin/consents" class="btn btn-danger btn-sm w-100">
                        <i class="fas fa-file-signature me-1"></i>Gerenciar Consentimentos
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Atalhos Rápidos -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Atalhos Rápidos</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="/admin/employees" class="btn btn-outline-primary w-100">
                                <i class="fas fa-users fa-2x d-block mb-2"></i>
                                Gerenciar Funcionários
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="/admin/punches" class="btn btn-outline-success w-100">
                                <i class="fas fa-clock fa-2x d-block mb-2"></i>
                                Ver Marcações
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="/admin/reports" class="btn btn-outline-info w-100">
                                <i class="fas fa-file-alt fa-2x d-block mb-2"></i>
                                Relatórios
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="/admin/settings" class="btn btn-outline-warning w-100">
                                <i class="fas fa-cog fa-2x d-block mb-2"></i>
                                Configurações
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Dados do gráfico de marcações
const punchesData = <?= json_encode($punches_last_7_days ?? []) ?>;

// Extrair labels e valores
const labels = punchesData.map(d => d.date);
const data = punchesData.map(d => d.count);

// Criar gráfico
const ctx = document.getElementById('punchesChart').getContext('2d');
const punchesChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Marcações',
            data: data,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
            tooltip: {
                mode: 'index',
                intersect: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 10
                }
            }
        }
    }
});
</script>
<?= $this->endSection() ?>
