<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Registrar Ponto<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<style>
    .method-card {
        transition: all 0.3s;
        cursor: pointer;
        border: 2px solid #e0e0e0;
    }

    .method-card:hover {
        border-color: #667eea;
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.25);
    }

    .method-card.active {
        border-color: #667eea;
        background-color: #f8f9ff;
    }

    .method-icon {
        font-size: 4rem;
        margin-bottom: 1rem;
    }

    #videoContainer {
        position: relative;
        max-width: 640px;
        margin: 0 auto;
    }

    #video {
        width: 100%;
        border-radius: 10px;
        background-color: #000;
    }

    .face-overlay {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 300px;
        height: 400px;
        border: 3px solid #4caf50;
        border-radius: 50%;
        pointer-events: none;
    }

    .qrcode-scanner {
        max-width: 400px;
        margin: 0 auto;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1">
                <i class="fas fa-fingerprint me-2"></i>Registrar Ponto
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Registrar Ponto</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Current Time Card -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-center p-4">
                    <h3 class="mb-2">Hora Atual</h3>
                    <h1 class="display-2 mb-0" id="currentTime"><?= date('H:i:s') ?></h1>
                    <p class="mb-0 mt-2 opacity-75"><?= strftime('%A, %d de %B de %Y') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Method Selection -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">Escolha o Método de Registro</h4>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card method-card h-100" onclick="selectMethod('code')">
                <div class="card-body text-center p-4">
                    <i class="fas fa-hashtag method-icon text-primary"></i>
                    <h5>Código Único</h5>
                    <p class="text-muted small mb-0">Digite seu código pessoal</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card method-card h-100" onclick="selectMethod('qrcode')">
                <div class="card-body text-center p-4">
                    <i class="fas fa-qrcode method-icon text-info"></i>
                    <h5>QR Code</h5>
                    <p class="text-muted small mb-0">Escaneie seu QR Code</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card method-card h-100" onclick="selectMethod('facial')">
                <div class="card-body text-center p-4">
                    <i class="fas fa-face-smile method-icon text-success"></i>
                    <h5>Reconhecimento Facial</h5>
                    <p class="text-muted small mb-0">Use a câmera</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card method-card h-100" onclick="selectMethod('biometric')">
                <div class="card-body text-center p-4">
                    <i class="fas fa-fingerprint method-icon text-warning"></i>
                    <h5>Biometria Digital</h5>
                    <p class="text-muted small mb-0">Leitor de impressão digital</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Punch Forms -->
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <!-- Unique Code Method -->
            <div id="codeMethod" class="method-content d-none">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-hashtag me-2"></i>Registro por Código Único
                        </h5>
                    </div>
                    <div class="card-body">
                        <form action="<?= base_url('punch/register') ?>" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="method" value="codigo">

                            <div class="mb-4">
                                <label for="punch_type" class="form-label">Tipo de Registro</label>
                                <select class="form-select form-select-lg" id="punch_type" name="punch_type" required>
                                    <option value="">Selecione...</option>
                                    <option value="entrada">Entrada</option>
                                    <option value="inicio_intervalo">Início Intervalo</option>
                                    <option value="fim_intervalo">Fim Intervalo</option>
                                    <option value="saida">Saída</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="unique_code" class="form-label">Código Único</label>
                                <input type="text" class="form-control form-control-lg text-center"
                                       id="unique_code" name="unique_code"
                                       placeholder="Digite seu código"
                                       maxlength="10"
                                       style="letter-spacing: 3px; font-size: 2rem; font-weight: bold;"
                                       required>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-check me-2"></i>Registrar Ponto
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- QR Code Method -->
            <div id="qrcodeMethod" class="method-content d-none">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-qrcode me-2"></i>Registro por QR Code
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label for="qr_punch_type" class="form-label">Tipo de Registro</label>
                            <select class="form-select form-select-lg" id="qr_punch_type" required>
                                <option value="">Selecione...</option>
                                <option value="entrada">Entrada</option>
                                <option value="inicio_intervalo">Início Intervalo</option>
                                <option value="fim_intervalo">Fim Intervalo</option>
                                <option value="saida">Saída</option>
                            </select>
                        </div>

                        <div class="text-center mb-3">
                            <p class="mb-3">Aponte a câmera para o QR Code</p>
                            <div id="qrScanner" class="qrcode-scanner">
                                <video id="qrVideo" class="w-100 rounded"></video>
                            </div>
                            <button type="button" class="btn btn-primary mt-3" id="startQRScanner">
                                <i class="fas fa-camera me-2"></i>Iniciar Scanner
                            </button>
                        </div>

                        <div id="qrResult"></div>
                    </div>
                </div>
            </div>

            <!-- Facial Recognition Method -->
            <div id="facialMethod" class="method-content d-none">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-face-smile me-2"></i>Registro por Reconhecimento Facial
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label for="facial_punch_type" class="form-label">Tipo de Registro</label>
                            <select class="form-select form-select-lg" id="facial_punch_type" required>
                                <option value="">Selecione...</option>
                                <option value="entrada">Entrada</option>
                                <option value="inicio_intervalo">Início Intervalo</option>
                                <option value="fim_intervalo">Fim Intervalo</option>
                                <option value="saida">Saída</option>
                            </select>
                        </div>

                        <div class="text-center mb-3">
                            <p class="mb-3">Posicione seu rosto dentro da moldura</p>
                            <div id="videoContainer">
                                <video id="video" autoplay playsinline></video>
                                <div class="face-overlay"></div>
                                <canvas id="canvas" class="d-none"></canvas>
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-primary me-2" id="startCamera">
                                    <i class="fas fa-video me-2"></i>Iniciar Câmera
                                </button>
                                <button type="button" class="btn btn-success" id="capture" disabled>
                                    <i class="fas fa-camera me-2"></i>Capturar e Registrar
                                </button>
                            </div>
                        </div>

                        <div id="facialResult"></div>
                    </div>
                </div>
            </div>

            <!-- Biometric Method -->
            <div id="biometricMethod" class="method-content d-none">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-fingerprint me-2"></i>Registro por Biometria Digital
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label for="bio_punch_type" class="form-label">Tipo de Registro</label>
                            <select class="form-select form-select-lg" id="bio_punch_type" required>
                                <option value="">Selecione...</option>
                                <option value="entrada">Entrada</option>
                                <option value="inicio_intervalo">Início Intervalo</option>
                                <option value="fim_intervalo">Fim Intervalo</option>
                                <option value="saida">Saída</option>
                            </select>
                        </div>

                        <div class="text-center py-5">
                            <i class="fas fa-fingerprint fa-5x text-warning mb-4"></i>
                            <h4>Coloque seu dedo no leitor</h4>
                            <p class="text-muted">Aguardando leitura da impressão digital...</p>
                            <div class="spinner-border text-warning mt-3" role="status">
                                <span class="visually-hidden">Aguardando...</span>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Atenção:</strong> Certifique-se de que o leitor biométrico está conectado.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Location Info (if geolocation enabled) -->
            <div class="card mt-3" id="locationCard" style="display: none;">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-map-marker-alt text-success fa-2x me-3"></i>
                        <div>
                            <h6 class="mb-1">Localização Capturada</h6>
                            <small class="text-muted" id="locationText">Obtendo localização...</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Update current time
    function updateTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('currentTime').textContent = `${hours}:${minutes}:${seconds}`;
    }
    setInterval(updateTime, 1000);

    // Method selection
    function selectMethod(method) {
        // Hide all methods
        document.querySelectorAll('.method-content').forEach(el => el.classList.add('d-none'));
        document.querySelectorAll('.method-card').forEach(el => el.classList.remove('active'));

        // Show selected method
        document.getElementById(method + 'Method').classList.remove('d-none');
        event.currentTarget.classList.add('active');

        // Get geolocation if supported
        if (navigator.geolocation) {
            document.getElementById('locationCard').style.display = 'block';
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude.toFixed(6);
                    const lon = position.coords.longitude.toFixed(6);
                    document.getElementById('locationText').textContent = `${lat}, ${lon}`;

                    // Store coordinates for form submission
                    window.userLocation = { latitude: lat, longitude: lon };
                },
                function(error) {
                    document.getElementById('locationText').textContent = 'Localização não disponível';
                }
            );
        }
    }

    // Auto-uppercase unique code
    document.getElementById('unique_code')?.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });

    // Facial Recognition
    let stream = null;
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');

    document.getElementById('startCamera')?.addEventListener('click', async function() {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;
            document.getElementById('capture').disabled = false;
            this.disabled = true;
        } catch (err) {
            alert('Erro ao acessar câmera: ' + err.message);
        }
    });

    document.getElementById('capture')?.addEventListener('click', function() {
        const punchType = document.getElementById('facial_punch_type').value;

        if (!punchType) {
            alert('Selecione o tipo de registro primeiro!');
            return;
        }

        const context = canvas.getContext('2d');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        context.drawImage(video, 0, 0);

        const imageData = canvas.toDataURL('image/jpeg');

        document.getElementById('facialResult').innerHTML =
            '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Processando reconhecimento facial...</div>';

        fetch('<?= base_url('punch/facial') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '<?= csrf_hash() ?>'
            },
            body: JSON.stringify({
                punch_type: punchType,
                photo: imageData,
                latitude: window.userLocation?.latitude,
                longitude: window.userLocation?.longitude
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('facialResult').innerHTML =
                    '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>' +
                    data.message + '<br>NSR: ' + data.nsr + '</div>';

                // Stop camera
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }

                setTimeout(() => window.location.href = '<?= base_url('dashboard') ?>', 2000);
            } else {
                document.getElementById('facialResult').innerHTML =
                    '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>' +
                    data.message + '</div>';
            }
        })
        .catch(error => {
            document.getElementById('facialResult').innerHTML =
                '<div class="alert alert-danger">Erro: ' + error.message + '</div>';
        });
    });

    // QR Code Scanner (using jsQR library - would need to be included)
    document.getElementById('startQRScanner')?.addEventListener('click', function() {
        alert('Funcionalidade de scanner QR Code será implementada com biblioteca jsQR');
    });
</script>
<?= $this->endSection() ?>
