<?= $this->extend('layouts/auth') ?>

<?= $this->section('title') ?>Login<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="auth-card">
    <div class="auth-header">
        <i class="fas fa-clock fa-3x mb-3"></i>
        <h1>Registro de Ponto</h1>
        <p>Faça login para acessar o sistema</p>
    </div>

    <div class="auth-body">
        <?php if (session()->has('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (session()->has('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form action="<?= base_url('login') ?>" method="POST" id="loginForm">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="email" class="form-label">E-mail ou CPF</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-user"></i>
                    </span>
                    <input type="text" class="form-control" id="email" name="email"
                           placeholder="seu@email.com ou 000.000.000-00"
                           value="<?= old('email') ?>" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Senha</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="••••••••" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label" for="remember">
                    Lembrar-me
                </label>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-sign-in-alt me-2"></i>Entrar
                </button>
            </div>

            <div class="text-center">
                <a href="<?= base_url('forgot-password') ?>" class="text-decoration-none">
                    Esqueceu sua senha?
                </a>
            </div>
        </form>

        <div class="divider">
            <span>ou acesse com</span>
        </div>

        <!-- Alternative Login Methods -->
        <div class="alternative-methods">
            <button type="button" class="auth-method-btn" onclick="openQRCodeLogin()">
                <i class="fas fa-qrcode text-primary"></i>
                <span class="fw-semibold">QR Code</span>
                <small class="d-block text-muted">Escaneie o código com seu celular</small>
            </button>

            <button type="button" class="auth-method-btn" onclick="openFacialLogin()">
                <i class="fas fa-face-smile text-success"></i>
                <span class="fw-semibold">Reconhecimento Facial</span>
                <small class="d-block text-muted">Use a câmera para fazer login</small>
            </button>

            <button type="button" class="auth-method-btn" onclick="openCodeLogin()">
                <i class="fas fa-hashtag text-warning"></i>
                <span class="fw-semibold">Código Único</span>
                <small class="d-block text-muted">Digite seu código pessoal</small>
            </button>
        </div>
    </div>
</div>

<!-- QR Code Login Modal -->
<div class="modal fade" id="qrcodeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-qrcode me-2"></i>Login com QR Code
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p>Aponte a câmera do seu celular para o QR Code abaixo:</p>
                <div id="qrcodeDisplay" class="mb-3">
                    <!-- QR Code will be generated here -->
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Gerando QR Code...</span>
                    </div>
                </div>
                <p class="text-muted small">
                    O QR Code expira em <span id="qrcodeTimer">5:00</span>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Facial Recognition Modal -->
<div class="modal fade" id="facialModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-face-smile me-2"></i>Login com Reconhecimento Facial
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <p>Posicione seu rosto dentro da moldura</p>
                </div>
                <div id="cameraContainer" class="position-relative">
                    <video id="cameraStream" autoplay playsinline class="w-100 rounded"></video>
                    <canvas id="cameraCanvas" class="d-none"></canvas>
                </div>
                <div class="text-center mt-3">
                    <button type="button" class="btn btn-primary" id="captureBtn" onclick="captureFace()">
                        <i class="fas fa-camera me-2"></i>Capturar
                    </button>
                </div>
                <div id="facialResult" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<!-- Unique Code Modal -->
<div class="modal fade" id="codeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-hashtag me-2"></i>Login com Código Único
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('login/code') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="unique_code" class="form-label">Digite seu código único</label>
                        <input type="text" class="form-control form-control-lg text-center"
                               id="unique_code" name="unique_code"
                               placeholder="ABC123"
                               maxlength="10"
                               style="letter-spacing: 3px; font-size: 1.5rem;"
                               required autofocus>
                        <div class="form-text">
                            O código único foi fornecido pelo RH
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Entrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function() {
        const password = document.getElementById('password');
        const icon = this.querySelector('i');

        if (password.type === 'password') {
            password.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            password.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });

    // QR Code Login
    function openQRCodeLogin() {
        const modal = new bootstrap.Modal(document.getElementById('qrcodeModal'));
        modal.show();

        // Generate QR Code (placeholder - implement with actual QR library)
        setTimeout(() => {
            document.getElementById('qrcodeDisplay').innerHTML =
                '<img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' +
                encodeURIComponent(window.location.origin + '/qr-login?token=' + Date.now()) +
                '" alt="QR Code" class="img-fluid">';

            // Start countdown timer
            startQRTimer(300); // 5 minutes
        }, 500);
    }

    function startQRTimer(seconds) {
        const timerElement = document.getElementById('qrcodeTimer');
        let remaining = seconds;

        const interval = setInterval(() => {
            const minutes = Math.floor(remaining / 60);
            const secs = remaining % 60;
            timerElement.textContent = `${minutes}:${secs.toString().padStart(2, '0')}`;

            remaining--;

            if (remaining < 0) {
                clearInterval(interval);
                timerElement.textContent = 'Expirado';
            }
        }, 1000);
    }

    // Facial Recognition
    let cameraStream = null;

    function openFacialLogin() {
        const modal = new bootstrap.Modal(document.getElementById('facialModal'));
        modal.show();

        // Request camera access
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(function(stream) {
                cameraStream = stream;
                document.getElementById('cameraStream').srcObject = stream;
            })
            .catch(function(error) {
                document.getElementById('facialResult').innerHTML =
                    '<div class="alert alert-danger">Erro ao acessar câmera: ' + error.message + '</div>';
            });

        // Stop camera when modal is closed
        document.getElementById('facialModal').addEventListener('hidden.bs.modal', function() {
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
                cameraStream = null;
            }
        });
    }

    function captureFace() {
        const video = document.getElementById('cameraStream');
        const canvas = document.getElementById('cameraCanvas');
        const context = canvas.getContext('2d');

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        context.drawImage(video, 0, 0);

        // Convert to base64
        const imageData = canvas.toDataURL('image/jpeg');

        // Send to server
        document.getElementById('facialResult').innerHTML =
            '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Processando...</div>';

        fetch('<?= base_url('login/facial') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '<?= csrf_hash() ?>'
            },
            body: JSON.stringify({ photo: imageData })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('facialResult').innerHTML =
                    '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Reconhecido! Redirecionando...</div>';
                setTimeout(() => {
                    window.location.href = '<?= base_url('dashboard') ?>';
                }, 1500);
            } else {
                document.getElementById('facialResult').innerHTML =
                    '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>' + data.message + '</div>';
            }
        })
        .catch(error => {
            document.getElementById('facialResult').innerHTML =
                '<div class="alert alert-danger">Erro: ' + error.message + '</div>';
        });
    }

    // Unique Code Login
    function openCodeLogin() {
        const modal = new bootstrap.Modal(document.getElementById('codeModal'));
        modal.show();
    }

    // Auto-uppercase unique code
    document.addEventListener('DOMContentLoaded', function() {
        const codeInput = document.getElementById('unique_code');
        if (codeInput) {
            codeInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        }
    });
</script>
<?= $this->endSection() ?>
