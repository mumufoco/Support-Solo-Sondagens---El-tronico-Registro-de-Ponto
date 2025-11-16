<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Meu Perfil<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1">
                <i class="fas fa-user me-2"></i>Meu Perfil
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Perfil</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <!-- Profile Card -->
            <div class="card text-center">
                <div class="card-body">
                    <div class="mb-3">
                        <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center"
                             style="width: 120px; height: 120px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <span class="text-white display-4 fw-bold">
                                <?= strtoupper(substr($employee['name'], 0, 1)) ?>
                            </span>
                        </div>
                    </div>
                    <h4 class="mb-1"><?= esc($employee['name']) ?></h4>
                    <p class="text-muted mb-3"><?= esc($employee['position'] ?? 'N/A') ?></p>

                    <div class="mb-3">
                        <?php
                        $role_badges = [
                            'admin' => ['label' => 'Administrador', 'class' => 'danger'],
                            'gestor' => ['label' => 'Gestor', 'class' => 'warning'],
                            'funcionario' => ['label' => 'Funcionário', 'class' => 'primary'],
                        ];
                        $badge = $role_badges[$employee['role']] ?? ['label' => 'Funcionário', 'class' => 'secondary'];
                        ?>
                        <span class="badge bg-<?= $badge['class'] ?>"><?= $badge['label'] ?></span>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="<?= base_url('profile/edit') ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Editar Perfil
                        </a>
                        <a href="<?= base_url('profile/password') ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-key me-2"></i>Alterar Senha
                        </a>
                        <a href="<?= base_url('profile/biometric') ?>" class="btn btn-outline-success">
                            <i class="fas fa-fingerprint me-2"></i>Biometria
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-line me-2"></i>Estatísticas Rápidas
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Dias Trabalhados (mês):</span>
                                <strong><?= $stats['days_worked'] ?? 0 ?></strong>
                            </div>
                        </li>
                        <li class="mb-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Atrasos (mês):</span>
                                <strong class="text-warning"><?= $stats['late_arrivals'] ?? 0 ?></strong>
                            </div>
                        </li>
                        <li>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Saldo de Horas:</span>
                                <strong class="<?= ($employee['hours_balance'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= ($employee['hours_balance'] ?? 0) >= 0 ? '+' : '' ?><?= $employee['hours_balance_formatted'] ?? '0:00' ?>
                                </strong>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <!-- Personal Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-id-card me-2"></i>Informações Pessoais
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">Nome Completo</label>
                            <p class="mb-0 fw-semibold"><?= esc($employee['name']) ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">CPF</label>
                            <p class="mb-0 fw-semibold"><?= format_cpf($employee['cpf']) ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">E-mail</label>
                            <p class="mb-0 fw-semibold"><?= esc($employee['email']) ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">Telefone</label>
                            <p class="mb-0 fw-semibold"><?= $employee['phone'] ? format_phone_br($employee['phone']) : 'Não informado' ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">Código Único</label>
                            <p class="mb-0 fw-semibold font-monospace"><?= esc($employee['unique_code']) ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">Data de Admissão</label>
                            <p class="mb-0 fw-semibold"><?= $employee['admission_date'] ? format_date_br($employee['admission_date']) : 'N/A' ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Work Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-briefcase me-2"></i>Informações de Trabalho
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">Departamento</label>
                            <p class="mb-0 fw-semibold"><?= esc($employee['department'] ?? 'N/A') ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">Cargo</label>
                            <p class="mb-0 fw-semibold"><?= esc($employee['position'] ?? 'N/A') ?></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">Carga Horária Diária</label>
                            <p class="mb-0 fw-semibold"><?= $employee['daily_hours'] ?? 8 ?> horas</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small mb-1">Carga Horária Semanal</label>
                            <p class="mb-0 fw-semibold"><?= $employee['weekly_hours'] ?? 44 ?> horas</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Work Schedule -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>Horário de Trabalho
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="p-3 bg-light rounded">
                                <i class="fas fa-sun text-warning mb-2"></i>
                                <h6 class="mb-1 text-muted small">Entrada</h6>
                                <h5 class="mb-0 font-monospace"><?= format_time($employee['work_start_time']) ?></h5>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="p-3 bg-light rounded">
                                <i class="fas fa-coffee text-info mb-2"></i>
                                <h6 class="mb-1 text-muted small">Início Intervalo</h6>
                                <h5 class="mb-0 font-monospace"><?= format_time($employee['lunch_start_time']) ?></h5>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="p-3 bg-light rounded">
                                <i class="fas fa-utensils text-success mb-2"></i>
                                <h6 class="mb-1 text-muted small">Fim Intervalo</h6>
                                <h5 class="mb-0 font-monospace"><?= format_time($employee['lunch_end_time']) ?></h5>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="p-3 bg-light rounded">
                                <i class="fas fa-moon text-primary mb-2"></i>
                                <h6 class="mb-1 text-muted small">Saída</h6>
                                <h5 class="mb-0 font-monospace"><?= format_time($employee['work_end_time']) ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Biometric Status -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-fingerprint me-2"></i>Status Biométrico
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-face-smile fa-2x me-3 <?= $employee['has_face_biometric'] ? 'text-success' : 'text-muted' ?>"></i>
                                <div>
                                    <h6 class="mb-1">Reconhecimento Facial</h6>
                                    <?php if ($employee['has_face_biometric']): ?>
                                        <span class="badge bg-success">Cadastrado</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Não Cadastrado</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-fingerprint fa-2x me-3 <?= $employee['has_fingerprint_biometric'] ? 'text-success' : 'text-muted' ?>"></i>
                                <div>
                                    <h6 class="mb-1">Impressão Digital</h6>
                                    <?php if ($employee['has_fingerprint_biometric']): ?>
                                        <span class="badge bg-success">Cadastrado</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Não Cadastrado</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if (!$employee['has_face_biometric'] || !$employee['has_fingerprint_biometric']): ?>
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Cadastre sua biometria para registrar ponto de forma mais rápida e segura.
                            <a href="<?= base_url('profile/biometric') ?>" class="alert-link">Cadastrar agora</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
