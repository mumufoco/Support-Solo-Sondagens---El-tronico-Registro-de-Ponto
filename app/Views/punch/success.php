<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Ponto Registrado<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
    @keyframes successPulse {
        0% {
            transform: scale(0.8);
            opacity: 0;
        }
        50% {
            transform: scale(1.1);
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    .success-icon {
        animation: successPulse 0.6s ease-out;
    }

    .print-area {
        background: white;
        padding: 2rem;
        border: 2px dashed #ccc;
    }

    @media print {
        body * {
            visibility: hidden;
        }
        .print-area, .print-area * {
            visibility: visible;
        }
        .print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .no-print {
            display: none !important;
        }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Success Message -->
            <div class="card border-0 shadow-lg mb-4">
                <div class="card-body text-center p-5">
                    <div class="success-icon mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                    </div>
                    <h2 class="mb-3">Ponto Registrado com Sucesso!</h2>
                    <p class="text-muted mb-0">Seu registro foi salvo no sistema</p>
                </div>
            </div>

            <!-- Punch Details -->
            <div class="card mb-4 print-area">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-receipt me-2"></i>Comprovante de Registro
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Funcionário</h6>
                            <p class="mb-0"><strong><?= esc($employee['name']) ?></strong></p>
                            <small class="text-muted"><?= esc($employee['unique_code']) ?></small>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h6 class="text-muted mb-2">Departamento</h6>
                            <p class="mb-0"><strong><?= esc($employee['department'] ?? 'N/A') ?></strong></p>
                        </div>
                    </div>

                    <hr>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-calendar-day fa-2x text-primary me-3"></i>
                                <div>
                                    <h6 class="mb-0 text-muted">Data</h6>
                                    <strong><?= esc($punch['date']) ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-clock fa-2x text-success me-3"></i>
                                <div>
                                    <h6 class="mb-0 text-muted">Horário</h6>
                                    <strong><?= esc($punch['time']) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-tag fa-2x text-warning me-3"></i>
                                <div>
                                    <h6 class="mb-0 text-muted">Tipo</h6>
                                    <strong><?= ucfirst(str_replace('_', ' ', $punch['type'])) ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-fingerprint fa-2x text-info me-3"></i>
                                <div>
                                    <h6 class="mb-0 text-muted">Método</h6>
                                    <strong><?= ucfirst($punch['method']) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <small class="text-muted">NSR (Número Sequencial)</small>
                            <p class="mb-0 font-monospace"><strong><?= esc($punch['nsr']) ?></strong></p>
                        </div>
                        <div class="col-md-6 mb-2">
                            <small class="text-muted">Hash de Verificação</small>
                            <p class="mb-0 font-monospace small"><strong><?= substr($punch['hash'], 0, 32) ?>...</strong></p>
                        </div>
                    </div>

                    <?php if (isset($punch['latitude']) && isset($punch['longitude'])): ?>
                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <small class="text-muted">
                                    <i class="fas fa-map-marker-alt me-1"></i>Localização
                                </small>
                                <p class="mb-0 font-monospace small">
                                    <?= esc($punch['latitude']) ?>, <?= esc($punch['longitude']) ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="text-center mt-4 pt-3 border-top">
                        <small class="text-muted">
                            <i class="fas fa-shield-alt me-1"></i>
                            Registro protegido por criptografia conforme Portaria MTE 671/2021
                        </small>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="card no-print">
                <div class="card-body">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Imprimir Comprovante
                        </button>
                        <a href="<?= base_url('timesheet') ?>" class="btn btn-outline-info">
                            <i class="fas fa-calendar-alt me-2"></i>Ver Espelho de Ponto
                        </a>
                        <a href="<?= base_url('dashboard') ?>" class="btn btn-primary">
                            <i class="fas fa-home me-2"></i>Voltar ao Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <!-- Auto-redirect message -->
            <div class="text-center mt-3 no-print">
                <small class="text-muted">
                    Você será redirecionado para o dashboard em <span id="countdown">10</span> segundos...
                </small>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Auto-redirect countdown
    let seconds = 10;
    const countdownElement = document.getElementById('countdown');

    const interval = setInterval(() => {
        seconds--;
        countdownElement.textContent = seconds;

        if (seconds <= 0) {
            clearInterval(interval);
            window.location.href = '<?= base_url('dashboard') ?>';
        }
    }, 1000);
</script>
<?= $this->endSection() ?>
