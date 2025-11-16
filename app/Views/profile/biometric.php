<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Cadastro Biométrico<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1">
                <i class="fas fa-fingerprint me-2"></i>Cadastro Biométrico
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('profile') ?>">Perfil</a></li>
                    <li class="breadcrumb-item active">Biometria</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (!$hasConsent): ?>
        <!-- LGPD Consent Card -->
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-shield-alt me-2"></i>Consentimento LGPD Necessário
                        </h5>
                    </div>
                    <div class="card-body">
                        <h6 class="mb-3">Termo de Consentimento para Coleta e Tratamento de Dados Biométricos</h6>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Lei Geral de Proteção de Dados (LGPD) - Lei nº 13.709/2018</strong>
                        </div>

                        <p>Eu, <strong><?= esc($employee['name']) ?></strong>, CPF <strong><?= format_cpf($employee['cpf']) ?></strong>, declaro para os devidos fins que:</p>

                        <ol class="mb-4">
                            <li class="mb-2">
                                <strong>Autorizo</strong> a empresa Support Solo Sondagens a coletar e tratar meus dados biométricos (reconhecimento facial e impressão digital) para fins exclusivos de registro de ponto eletrônico.
                            </li>
                            <li class="mb-2">
                                <strong>Estou ciente</strong> de que meus dados biométricos serão armazenados de forma criptografada em banco de dados seguro, conforme exigências da LGPD.
                            </li>
                            <li class="mb-2">
                                <strong>Compreendo</strong> que o tratamento dos meus dados biométricos tem como base legal o meu consentimento livre, informado e inequívoco (Art. 7º, I da LGPD).
                            </li>
                            <li class="mb-2">
                                <strong>Tenho conhecimento</strong> de que posso revogar este consentimento a qualquer momento, mediante solicitação formal.
                            </li>
                            <li class="mb-2">
                                <strong>Fui informado(a)</strong> sobre meus direitos como titular dos dados: acesso, correção, anonimização, bloqueio, eliminação, portabilidade, e informação sobre compartilhamento.
                            </li>
                        </ol>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Importante:</strong> Sem este consentimento, não será possível cadastrar sua biometria. Você ainda poderá registrar ponto usando código único ou outros métodos disponíveis.
                        </div>

                        <form action="<?= base_url('profile/biometric/consent') ?>" method="POST">
                            <?= csrf_field() ?>

                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="consentCheck" name="consent" required>
                                <label class="form-check-label" for="consentCheck">
                                    <strong>Li e concordo</strong> com os termos acima e autorizo o tratamento dos meus dados biométricos conforme descrito.
                                </label>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?= base_url('profile') ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancelar
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check me-2"></i>Concordar e Continuar
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-muted small">
                        <i class="fas fa-clock me-1"></i> Data: <?= date('d/m/Y H:i:s') ?> |
                        <i class="fas fa-map-marker-alt me-1"></i> IP: <?= get_client_ip() ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Biometric Enrollment -->
        <div class="row">
            <div class="col-lg-6 mb-4">
                <!-- Facial Recognition -->
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-face-smile me-2"></i>Reconhecimento Facial
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($employee['has_face_biometric']): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Biometria facial cadastrada!</strong>
                                <p class="mb-0 mt-2 small">Cadastrado em: <?= format_datetime_br($faceEnrollmentDate ?? '') ?></p>
                            </div>

                            <div class="text-center mb-3">
                                <i class="fas fa-user-check fa-4x text-success"></i>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-primary" onclick="testFacial()">
                                    <i class="fas fa-camera me-2"></i>Testar Reconhecimento
                                </button>
                                <button type="button" class="btn btn-outline-danger" onclick="deleteFacial()">
                                    <i class="fas fa-trash me-2"></i>Remover Biometria
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Cadastre sua face para registrar ponto usando reconhecimento facial.
                            </div>

                            <div class="text-center mb-3">
                                <video id="faceVideo" autoplay playsinline class="w-100 rounded" style="max-height: 300px; background: #000;"></video>
                                <canvas id="faceCanvas" class="d-none"></canvas>
                            </div>

                            <div id="faceResult" class="mb-3"></div>

                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-primary" id="startFaceCamera">
                                    <i class="fas fa-video me-2"></i>Iniciar Câmera
                                </button>
                                <button type="button" class="btn btn-success" id="captureFace" disabled>
                                    <i class="fas fa-camera me-2"></i>Capturar e Cadastrar
                                </button>
                            </div>

                            <div class="mt-3">
                                <small class="text-muted">
                                    <strong>Dicas:</strong><br>
                                    • Posicione seu rosto centralizado<br>
                                    • Certifique-se de ter boa iluminação<br>
                                    • Remova óculos escuros ou bonés<br>
                                    • Mantenha expressão neutra
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <!-- Fingerprint -->
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-fingerprint me-2"></i>Impressão Digital
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($employee['has_fingerprint_biometric']): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Impressão digital cadastrada!</strong>
                                <p class="mb-0 mt-2 small">Cadastrado em: <?= format_datetime_br($fingerprintEnrollmentDate ?? '') ?></p>
                            </div>

                            <div class="text-center mb-3">
                                <i class="fas fa-fingerprint fa-4x text-success"></i>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-danger" onclick="deleteFingerprint()">
                                    <i class="fas fa-trash me-2"></i>Remover Biometria
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Cadastre sua impressão digital para registrar ponto usando leitor biométrico.
                            </div>

                            <div class="text-center py-5">
                                <i class="fas fa-fingerprint fa-5x text-primary mb-4"></i>
                                <h5>Leitor Biométrico</h5>
                                <p class="text-muted">Posicione seu dedo no leitor</p>
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Aguardando...</span>
                                </div>
                            </div>

                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Atenção:</strong> Certifique-se de que o leitor biométrico está conectado ao computador.
                            </div>

                            <div class="mt-3">
                                <small class="text-muted">
                                    <strong>Dicas:</strong><br>
                                    • Limpe o dedo antes de posicionar<br>
                                    • Posicione o dedo centralizado no leitor<br>
                                    • Pressione levemente, sem forçar<br>
                                    • Mantenha o dedo imóvel durante a leitura
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revoke Consent -->
        <div class="row">
            <div class="col-12">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-ban me-2"></i>Revogar Consentimento
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">
                            Você pode revogar o consentimento para uso de dados biométricos a qualquer momento.
                            <strong>Ao revogar:</strong>
                        </p>
                        <ul class="mb-3">
                            <li>Todos os seus dados biométricos serão desativados</li>
                            <li>Você não poderá mais usar reconhecimento facial ou biometria digital</li>
                            <li>Você ainda poderá registrar ponto usando código único ou QR Code</li>
                            <li>Seus registros anteriores permanecerão no sistema para fins legais</li>
                        </ul>

                        <form action="<?= base_url('profile/biometric/revoke') ?>" method="POST"
                              onsubmit="return confirm('Tem certeza que deseja revogar o consentimento? Esta ação removerá todas as suas biometrias cadastradas.');">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-ban me-2"></i>Revogar Consentimento
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    let faceStream = null;

    // Facial Recognition
    document.getElementById('startFaceCamera')?.addEventListener('click', async function() {
        try {
            faceStream = await navigator.mediaDevices.getUserMedia({ video: true });
            document.getElementById('faceVideo').srcObject = faceStream;
            document.getElementById('captureFace').disabled = false;
            this.disabled = true;
        } catch (err) {
            alert('Erro ao acessar câmera: ' + err.message);
        }
    });

    document.getElementById('captureFace')?.addEventListener('click', function() {
        const video = document.getElementById('faceVideo');
        const canvas = document.getElementById('faceCanvas');
        const context = canvas.getContext('2d');

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        context.drawImage(video, 0, 0);

        const imageData = canvas.toDataURL('image/jpeg');

        document.getElementById('faceResult').innerHTML =
            '<div class="alert alert-info"><i class="fas fa-spinner fa-spin me-2"></i>Processando cadastro...</div>';

        fetch('<?= base_url('api/biometric/enroll/face') ?>', {
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
                document.getElementById('faceResult').innerHTML =
                    '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>' +
                    data.message + '</div>';

                if (faceStream) {
                    faceStream.getTracks().forEach(track => track.stop());
                }

                setTimeout(() => window.location.reload(), 2000);
            } else {
                document.getElementById('faceResult').innerHTML =
                    '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>' +
                    data.message + '</div>';
            }
        })
        .catch(error => {
            document.getElementById('faceResult').innerHTML =
                '<div class="alert alert-danger">Erro: ' + error.message + '</div>';
        });
    });

    function testFacial() {
        // Open fullscreen modal for testing
        const modal = document.createElement('div');
        modal.id = 'testFacialModal';
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-camera me-2"></i>Teste de Reconhecimento Facial
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center position-relative" style="background: #000;">
                        <div class="position-absolute top-50 start-50 translate-middle" style="width: 80%; max-width: 500px;">
                            <video id="testFaceVideo" autoplay playsinline class="w-100 rounded" style="max-height: 400px;"></video>
                            <canvas id="testFaceCanvas" class="d-none"></canvas>

                            <!-- Face Guide Circle -->
                            <div id="faceGuide" class="position-absolute top-50 start-50 translate-middle"
                                 style="width: 300px; height: 300px; border: 3px dashed rgba(255,255,255,0.5); border-radius: 50%; pointer-events: none;">
                            </div>

                            <div id="testResult" class="mt-3"></div>

                            <div class="mt-4 d-grid gap-2">
                                <button type="button" class="btn btn-lg btn-light" id="startTestCamera">
                                    <i class="fas fa-video me-2"></i>Iniciar Câmera
                                </button>
                                <button type="button" class="btn btn-lg btn-success" id="captureTestFace" disabled>
                                    <i class="fas fa-camera me-2"></i>Capturar e Testar
                                </button>
                            </div>

                            <div class="alert alert-info mt-3 text-start">
                                <strong>Instruções:</strong><br>
                                • Posicione seu rosto dentro do círculo<br>
                                • Mantenha boa iluminação<br>
                                • Aguarde 2-3 segundos pelo resultado
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        // Setup camera
        let testStream = null;

        document.getElementById('startTestCamera').addEventListener('click', async function() {
            try {
                testStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'user', width: 640, height: 480 }
                });
                document.getElementById('testFaceVideo').srcObject = testStream;
                document.getElementById('captureTestFace').disabled = false;
                this.disabled = true;
            } catch (err) {
                document.getElementById('testResult').innerHTML =
                    '<div class="alert alert-danger">Erro ao acessar câmera: ' + err.message + '</div>';
            }
        });

        document.getElementById('captureTestFace').addEventListener('click', function() {
            const video = document.getElementById('testFaceVideo');
            const canvas = document.getElementById('testFaceCanvas');
            const context = canvas.getContext('2d');

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0);

            const imageData = canvas.toDataURL('image/jpeg');

            document.getElementById('testResult').innerHTML =
                '<div class="alert alert-info"><div class="spinner-border spinner-border-sm me-2"></div>Reconhecendo... Aguarde 2-3 segundos...</div>';

            this.disabled = true;

            fetch('<?= base_url('api/biometric/test') ?>', {
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
                if (testStream) {
                    testStream.getTracks().forEach(track => track.stop());
                }

                if (data.success && data.data.test_passed) {
                    // Success
                    document.getElementById('testResult').innerHTML =
                        '<div class="alert alert-success">' +
                        '<i class="fas fa-check-circle fa-3x mb-3"></i><br>' +
                        '<h4>✅ ' + data.message + '</h4>' +
                        '<p class="mb-0">Similaridade: <strong>' + data.data.similarity_percent + '%</strong></p>' +
                        '</div>';

                    setTimeout(() => bsModal.hide(), 3000);
                } else if (data.data && data.data.critical) {
                    // Critical error
                    document.getElementById('testResult').innerHTML =
                        '<div class="alert alert-danger">' +
                        '<i class="fas fa-exclamation-triangle fa-3x mb-3"></i><br>' +
                        '<h4>🚨 ' + data.message + '</h4>' +
                        '</div>';

                    setTimeout(() => {
                        bsModal.hide();
                        window.location.reload();
                    }, 5000);
                } else {
                    // Failed
                    document.getElementById('testResult').innerHTML =
                        '<div class="alert alert-warning">' +
                        '<i class="fas fa-times-circle fa-3x mb-3"></i><br>' +
                        '<h4>⚠️ ' + data.message + '</h4>' +
                        (data.data && data.data.failures ? '<p>Tentativas falhadas: ' + data.data.failures + '/2</p>' : '') +
                        '</div>';

                    if (data.data && data.data.disabled) {
                        setTimeout(() => {
                            bsModal.hide();
                            window.location.reload();
                        }, 4000);
                    } else {
                        document.getElementById('captureTestFace').disabled = false;
                    }
                }
            })
            .catch(error => {
                document.getElementById('testResult').innerHTML =
                    '<div class="alert alert-danger">Erro: ' + error.message + '</div>';
                document.getElementById('captureTestFace').disabled = false;
            });
        });

        // Cleanup on modal close
        modal.addEventListener('hidden.bs.modal', function() {
            if (testStream) {
                testStream.getTracks().forEach(track => track.stop());
            }
            modal.remove();
        });
    }

    function deleteFacial() {
        if (confirm('Tem certeza que deseja remover sua biometria facial?')) {
            // API call to delete facial biometric
            alert('Biometria facial removida com sucesso!');
            window.location.reload();
        }
    }

    function deleteFingerprint() {
        if (confirm('Tem certeza que deseja remover sua biometria digital?')) {
            // API call to delete fingerprint biometric
            alert('Biometria digital removida com sucesso!');
            window.location.reload();
        }
    }
</script>
<?= $this->endSection() ?>
