<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Registrar Ponto<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
    .punch-method-card {
        border: 2px solid #e0e0e0;
        transition: all 0.3s ease;
        cursor: pointer;
        min-height: 200px;
    }

    .punch-method-card:hover {
        border-color: #0066cc;
        box-shadow: 0 4px 15px rgba(0, 102, 204, 0.2);
        transform: translateY(-5px);
    }

    .punch-method-card.active {
        border-color: #0066cc;
        background: linear-gradient(135deg, #f8f9fa 0%, #e7f3ff 100%);
    }

    .punch-method-card.disabled {
        opacity: 0.5;
        cursor: not-allowed;
        background-color: #f5f5f5;
    }

    .punch-method-card.disabled:hover {
        transform: none;
        box-shadow: none;
        border-color: #e0e0e0;
    }

    .method-icon {
        font-size: 4rem;
        color: #0066cc;
    }

    .punch-type-btn {
        min-width: 150px;
        margin: 5px;
    }

    .camera-container {
        position: relative;
        max-width: 640px;
        margin: 0 auto;
    }

    #videoPreview {
        width: 100%;
        border-radius: 8px;
        border: 2px solid #0066cc;
    }

    .capture-overlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 80%;
        height: 80%;
        border: 3px dashed rgba(255, 255, 255, 0.8);
        border-radius: 50%;
        pointer-events: none;
    }

    .success-animation {
        animation: successPulse 0.5s ease;
    }

    @keyframes successPulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1">
                <i class="fas fa-clock me-2"></i>Registro de Ponto Eletrônico
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Registrar Ponto</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Current Time Display -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-primary text-white">
                <div class="card-body text-center py-4">
                    <h1 class="display-4 mb-0" id="currentTime"><?= date('H:i:s') ?></h1>
                    <p class="mb-0 mt-2" id="currentDate"><?= strftime('%A, %d de %B de %Y') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Messages -->
    <div id="alertContainer"></div>

    <!-- Punch Type Selection -->
    <div class="row mb-4" id="punchTypeSelection">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-hand-pointer me-2"></i>Selecione o Tipo de Marcação
                    </h5>
                </div>
                <div class="card-body text-center">
                    <button type="button" class="btn btn-lg btn-success punch-type-btn" data-type="entrada">
                        <i class="fas fa-sign-in-alt me-2"></i>Entrada
                    </button>
                    <button type="button" class="btn btn-lg btn-warning punch-type-btn" data-type="saida">
                        <i class="fas fa-sign-out-alt me-2"></i>Saída
                    </button>
                    <button type="button" class="btn btn-lg btn-info punch-type-btn" data-type="intervalo_inicio">
                        <i class="fas fa-coffee me-2"></i>Início Intervalo
                    </button>
                    <button type="button" class="btn btn-lg btn-secondary punch-type-btn" data-type="intervalo_fim">
                        <i class="fas fa-play me-2"></i>Fim Intervalo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Punch Methods -->
    <div class="row" id="punchMethodsContainer" style="display: none;">
        <div class="col-12 mb-3">
            <button type="button" class="btn btn-outline-secondary" id="backToPunchType">
                <i class="fas fa-arrow-left me-2"></i>Voltar
            </button>
        </div>

        <!-- Method: Code -->
        <?php if ($enabledMethods['codigo'] ?? true): ?>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card punch-method-card" data-method="codigo">
                <div class="card-body text-center">
                    <i class="fas fa-keyboard method-icon mb-3"></i>
                    <h5 class="card-title">Código Único</h5>
                    <p class="text-muted">Digite seu código pessoal</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Method: QR Code -->
        <?php if ($enabledMethods['qrcode'] ?? true): ?>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card punch-method-card" data-method="qrcode">
                <div class="card-body text-center">
                    <i class="fas fa-qrcode method-icon mb-3"></i>
                    <h5 class="card-title">QR Code</h5>
                    <p class="text-muted">Escaneie seu QR Code</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Method: Facial -->
        <?php if ($enabledMethods['facial'] ?? true): ?>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card punch-method-card" data-method="facial">
                <div class="card-body text-center">
                    <i class="fas fa-user-circle method-icon mb-3"></i>
                    <h5 class="card-title">Reconhecimento Facial</h5>
                    <p class="text-muted">Use seu rosto</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Method: Fingerprint -->
        <?php if ($enabledMethods['biometria'] ?? false): ?>
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card punch-method-card" data-method="biometria">
                <div class="card-body text-center">
                    <i class="fas fa-fingerprint method-icon mb-3"></i>
                    <h5 class="card-title">Biometria</h5>
                    <p class="text-muted">Use sua digital</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Punch Forms -->

    <!-- Form: Code -->
    <div class="row punch-form" id="formCodigo" style="display: none;">
        <div class="col-md-6 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-keyboard me-2"></i>Registro por Código</h5>
                </div>
                <div class="card-body">
                    <form id="punchCodeForm">
                        <div class="mb-3">
                            <label for="uniqueCode" class="form-label">Código Único:</label>
                            <input type="text" class="form-control form-control-lg text-center"
                                   id="uniqueCode" name="unique_code"
                                   placeholder="Digite seu código"
                                   required autofocus>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-check me-2"></i>Registrar Ponto
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetPunch()">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Form: QR Code -->
    <div class="row punch-form" id="formQrcode" style="display: none;">
        <div class="col-md-6 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-qrcode me-2"></i>Registro por QR Code</h5>
                </div>
                <div class="card-body">
                    <div id="qrScannerContainer" class="text-center">
                        <video id="qrVideo" style="width: 100%; max-width: 500px; border-radius: 8px;"></video>
                        <p class="text-muted mt-3">Posicione o QR Code na frente da câmera</p>
                    </div>
                    <div class="d-grid gap-2 mt-3">
                        <button type="button" class="btn btn-outline-secondary" onclick="resetPunch()">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form: Facial -->
    <div class="row punch-form" id="formFacial" style="display: none;">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>Registro por Reconhecimento Facial</h5>
                </div>
                <div class="card-body">
                    <div class="camera-container">
                        <video id="videoPreview" autoplay playsinline></video>
                        <div class="capture-overlay"></div>
                    </div>
                    <div class="text-center mt-3">
                        <p class="text-muted">Posicione seu rosto dentro do círculo</p>
                        <div id="faceStatus" class="alert alert-info">
                            <i class="fas fa-camera me-2"></i>Aguardando câmera...
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary btn-lg" id="captureFaceBtn" disabled>
                            <i class="fas fa-camera me-2"></i>Capturar e Registrar
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetPunch()">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form: Fingerprint -->
    <div class="row punch-form" id="formBiometria" style="display: none;">
        <div class="col-md-6 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-fingerprint me-2"></i>Registro por Biometria</h5>
                </div>
                <div class="card-body text-center">
                    <i class="fas fa-fingerprint" style="font-size: 8rem; color: #0066cc; opacity: 0.3;"></i>
                    <p class="mt-4 text-muted">Posicione seu dedo no leitor biométrico</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Funcionalidade requer hardware específico
                    </div>
                    <button type="button" class="btn btn-outline-secondary" onclick="resetPunch()">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    let selectedPunchType = null;
    let selectedMethod = null;
    let videoStream = null;

    // Update current time
    setInterval(() => {
        const now = new Date();
        document.getElementById('currentTime').textContent = now.toLocaleTimeString('pt-BR');
    }, 1000);

    // Punch type selection
    document.querySelectorAll('.punch-type-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            selectedPunchType = this.dataset.type;
            document.getElementById('punchTypeSelection').style.display = 'none';
            document.getElementById('punchMethodsContainer').style.display = 'flex';
        });
    });

    // Back to punch type selection
    document.getElementById('backToPunchType').addEventListener('click', function() {
        resetPunch();
    });

    // Method selection
    document.querySelectorAll('.punch-method-card').forEach(card => {
        if (!card.classList.contains('disabled')) {
            card.addEventListener('click', function() {
                selectedMethod = this.dataset.method;
                showPunchForm(selectedMethod);
            });
        }
    });

    // Show punch form
    function showPunchForm(method) {
        document.getElementById('punchMethodsContainer').style.display = 'none';
        document.querySelectorAll('.punch-form').forEach(form => form.style.display = 'none');

        const formId = 'form' + method.charAt(0).toUpperCase() + method.slice(1);
        document.getElementById(formId).style.display = 'flex';

        if (method === 'facial') {
            startCamera();
        } else if (method === 'qrcode') {
            // QR scanner would be initialized here
            showAlert('info', 'Scanner de QR Code não implementado nesta demo');
        }
    }

    // Reset to initial state
    function resetPunch() {
        selectedPunchType = null;
        selectedMethod = null;

        if (videoStream) {
            videoStream.getTracks().forEach(track => track.stop());
            videoStream = null;
        }

        document.querySelectorAll('.punch-form').forEach(form => form.style.display = 'none');
        document.getElementById('punchMethodsContainer').style.display = 'none';
        document.getElementById('punchTypeSelection').style.display = 'block';
    }

    // Code punch form submission
    document.getElementById('punchCodeForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const uniqueCode = document.getElementById('uniqueCode').value;

        try {
            const response = await fetch('<?= base_url('timesheet/punch/code') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    unique_code: uniqueCode,
                    punch_type: selectedPunchType,
                    location_lat: null,
                    location_lng: null,
                })
            });

            const result = await response.json();

            if (response.ok) {
                showAlert('success', result.message || 'Ponto registrado com sucesso!');
                setTimeout(() => resetPunch(), 2000);
            } else {
                showAlert('danger', result.message || 'Erro ao registrar ponto.');
            }
        } catch (error) {
            showAlert('danger', 'Erro de conexão. Tente novamente.');
        }
    });

    // Start camera for facial recognition
    async function startCamera() {
        try {
            videoStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user' }
            });

            const video = document.getElementById('videoPreview');
            video.srcObject = videoStream;

            document.getElementById('faceStatus').innerHTML = '<i class="fas fa-check-circle me-2"></i>Câmera pronta';
            document.getElementById('faceStatus').className = 'alert alert-success';
            document.getElementById('captureFaceBtn').disabled = false;
        } catch (error) {
            document.getElementById('faceStatus').innerHTML = '<i class="fas fa-times-circle me-2"></i>Erro ao acessar câmera';
            document.getElementById('faceStatus').className = 'alert alert-danger';
        }
    }

    // Capture face and submit
    document.getElementById('captureFaceBtn').addEventListener('click', async function() {
        const video = document.getElementById('videoPreview');
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);

        const photoBase64 = canvas.toDataURL('image/jpeg').split(',')[1];

        try {
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processando...';

            const response = await fetch('<?= base_url('timesheet/punch/face') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    photo: photoBase64,
                    punch_type: selectedPunchType,
                    location_lat: null,
                    location_lng: null,
                })
            });

            const result = await response.json();

            if (response.ok) {
                showAlert('success', result.message || 'Ponto registrado com sucesso!');
                setTimeout(() => resetPunch(), 2000);
            } else {
                showAlert('danger', result.message || 'Erro ao registrar ponto.');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-camera me-2"></i>Capturar e Registrar';
            }
        } catch (error) {
            showAlert('danger', 'Erro de conexão. Tente novamente.');
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-camera me-2"></i>Capturar e Registrar';
        }
    });

    // Show alert
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        document.getElementById('alertContainer').innerHTML = alertHtml;

        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            const alert = document.querySelector('#alertContainer .alert');
            if (alert) {
                alert.remove();
            }
        }, 5000);
    }
</script>
<?= $this->endSection() ?>
