<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1">
                <i class="fas fa-home me-2"></i>Dashboard
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Welcome Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="mb-2">
                                <?php
                                $hour = date('H');
                                if ($hour < 12) echo 'Bom dia';
                                elseif ($hour < 18) echo 'Boa tarde';
                                else echo 'Boa noite';
                                ?>, <?= esc($employee['name']) ?>!
                            </h3>
                            <p class="mb-0 opacity-75">
                                <i class="fas fa-calendar me-2"></i>
                                <?= strftime('%A, %d de %B de %Y', strtotime(date('Y-m-d'))) ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <div class="h2 mb-0">
                                <i class="fas fa-clock me-2"></i>
                                <span id="currentTime"><?= date('H:i:s') ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="row mb-4">
        <!-- Hours Balance -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title mb-0 text-muted">Saldo de Horas</h6>
                        <div class="rounded-circle p-2" style="background-color: #e3f2fd;">
                            <i class="fas fa-hourglass-half text-primary"></i>
                        </div>
                    </div>
                    <h3 class="mb-0 <?= $balance >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= $balance >= 0 ? '+' : '-' ?><?= abs($balance) ?> h
                    </h3>
                    <small class="text-muted">Este mês</small>
                </div>
            </div>
        </div>

        <!-- Today's Punches -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title mb-0 text-muted">Registros Hoje</h6>
                        <div class="rounded-circle p-2" style="background-color: #e8f5e9;">
                            <i class="fas fa-fingerprint text-success"></i>
                        </div>
                    </div>
                    <h3 class="mb-0"><?= $todayPunches ?? 0 ?></h3>
                    <small class="text-muted">
                        <?php
                        $expected = 4; // entrada, saída almoço, retorno almoço, saída
                        $remaining = max(0, $expected - ($todayPunches ?? 0));
                        ?>
                        <?= $remaining > 0 ? "Faltam {$remaining} registros" : 'Completo' ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- This Month -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title mb-0 text-muted">Dias Trabalhados</h6>
                        <div class="rounded-circle p-2" style="background-color: #fff3e0;">
                            <i class="fas fa-calendar-check text-warning"></i>
                        </div>
                    </div>
                    <h3 class="mb-0"><?= $daysWorked ?? 0 ?></h3>
                    <small class="text-muted">De <?= $expectedDays ?? 22 ?> dias úteis</small>
                </div>
            </div>
        </div>

        <!-- Notifications -->
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title mb-0 text-muted">Notificações</h6>
                        <div class="rounded-circle p-2" style="background-color: #fce4ec;">
                            <i class="fas fa-bell text-danger"></i>
                        </div>
                    </div>
                    <h3 class="mb-0"><?= $unreadNotifications ?? 0 ?></h3>
                    <small class="text-muted">Não lidas</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8 mb-4">
            <!-- Quick Punch Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-fingerprint me-2"></i>Registro Rápido de Ponto
                    </h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-4">
                        <div class="mb-4">
                            <i class="fas fa-clock fa-4x text-primary"></i>
                        </div>
                        <h4 class="mb-3">Registre seu ponto agora</h4>
                        <p class="text-muted mb-4">Escolha o método de sua preferência</p>

                        <div class="row g-3">
                            <div class="col-md-3 col-6">
                                <a href="<?= base_url('punch/code') ?>" class="btn btn-outline-primary w-100 p-3">
                                    <i class="fas fa-hashtag fa-2x mb-2"></i>
                                    <div>Código</div>
                                </a>
                            </div>
                            <div class="col-md-3 col-6">
                                <a href="<?= base_url('punch/qrcode') ?>" class="btn btn-outline-info w-100 p-3">
                                    <i class="fas fa-qrcode fa-2x mb-2"></i>
                                    <div>QR Code</div>
                                </a>
                            </div>
                            <div class="col-md-3 col-6">
                                <a href="<?= base_url('punch/facial') ?>" class="btn btn-outline-success w-100 p-3">
                                    <i class="fas fa-face-smile fa-2x mb-2"></i>
                                    <div>Facial</div>
                                </a>
                            </div>
                            <div class="col-md-3 col-6">
                                <a href="<?= base_url('punch/biometric') ?>" class="btn btn-outline-warning w-100 p-3">
                                    <i class="fas fa-fingerprint fa-2x mb-2"></i>
                                    <div>Digital</div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today's Punches -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Registros de Hoje
                    </h5>
                    <a href="<?= base_url('timesheet') ?>" class="btn btn-sm btn-outline-primary">
                        Ver Todos
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($todayPunchesList)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                            <p>Nenhum registro de ponto hoje</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($todayPunchesList as $punch): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-circle fa-xs me-2 <?=
                                            $punch['type'] === 'entrada' ? 'text-success' :
                                            ($punch['type'] === 'saida' ? 'text-danger' :
                                            ($punch['type'] === 'inicio_intervalo' ? 'text-warning' : 'text-info'))
                                        ?>"></i>
                                        <strong><?= ucfirst($punch['type']) ?></strong>
                                        <small class="text-muted ms-2">
                                            via <?= ucfirst($punch['method']) ?>
                                        </small>
                                    </div>
                                    <div>
                                        <span class="badge bg-light text-dark fs-6">
                                            <?= $punch['time'] ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (in_array($employee['role'], ['admin', 'gestor'])): ?>
                <!-- Manager/Admin Section -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>Visão Geral da Equipe
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <h3 class="text-primary"><?= $teamStats['total'] ?? 0 ?></h3>
                                <small class="text-muted">Total de Funcionários</small>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <h3 class="text-success"><?= $teamStats['present'] ?? 0 ?></h3>
                                <small class="text-muted">Presentes Hoje</small>
                            </div>
                            <div class="col-md-4">
                                <h3 class="text-warning"><?= $teamStats['pending'] ?? 0 ?></h3>
                                <small class="text-muted">Justificativas Pendentes</small>
                            </div>
                        </div>
                        <div class="text-center mt-4">
                            <a href="<?= base_url('reports') ?>" class="btn btn-primary">
                                <i class="fas fa-chart-bar me-2"></i>Ver Relatórios
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4 mb-4">
            <!-- Recent Notifications -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-bell me-2"></i>Notificações
                    </h5>
                    <a href="<?= base_url('notifications') ?>" class="btn btn-sm btn-outline-primary">
                        Ver Todas
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentNotifications)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-bell-slash fa-2x mb-2 opacity-50"></i>
                            <p class="mb-0">Sem notificações</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentNotifications as $notification): ?>
                                <a href="<?= base_url("notifications/{$notification['id']}") ?>"
                                   class="list-group-item list-group-item-action <?= !$notification['read'] ? 'list-group-item-light' : '' ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                        <h6 class="mb-1 <?= !$notification['read'] ? 'fw-bold' : '' ?>">
                                            <?= esc($notification['title']) ?>
                                        </h6>
                                        <?php if (!$notification['read']): ?>
                                            <span class="badge bg-primary">Nova</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mb-1 small"><?= esc($notification['message']) ?></p>
                                    <small class="text-muted"><?= $notification['time_ago'] ?></small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Ações Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= base_url('timesheet') ?>" class="btn btn-outline-primary">
                            <i class="fas fa-calendar-alt me-2"></i>Espelho de Ponto
                        </a>
                        <a href="<?= base_url('justifications/create') ?>" class="btn btn-outline-warning">
                            <i class="fas fa-file-alt me-2"></i>Enviar Justificativa
                        </a>
                        <a href="<?= base_url('profile') ?>" class="btn btn-outline-info">
                            <i class="fas fa-user me-2"></i>Meu Perfil
                        </a>
                        <?php if (in_array($employee['role'], ['admin', 'gestor'])): ?>
                            <a href="<?= base_url('employees') ?>" class="btn btn-outline-success">
                                <i class="fas fa-users me-2"></i>Gerenciar Equipe
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Monthly Summary -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>Resumo Mensal
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Horas Trabalhadas</span>
                                <strong><?= $monthlyStats['worked'] ?? '0:00' ?> h</strong>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: 75%"></div>
                            </div>
                        </li>
                        <li class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Horas Esperadas</span>
                                <strong><?= $monthlyStats['expected'] ?? '176:00' ?> h</strong>
                            </div>
                        </li>
                        <li class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Atrasos</span>
                                <span class="badge bg-warning"><?= $monthlyStats['lateArrivals'] ?? 0 ?></span>
                            </div>
                        </li>
                        <li>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Ausências</span>
                                <span class="badge bg-danger"><?= $monthlyStats['absences'] ?? 0 ?></span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Update current time every second
    function updateTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('currentTime').textContent = `${hours}:${minutes}:${seconds}`;
    }

    setInterval(updateTime, 1000);
    updateTime(); // Initial call
</script>
<?= $this->endSection() ?>
