<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0"><i class="fas fa-pen"></i> Assinar Advertência</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Atenção:</strong> Você recebeu uma advertência <?= $warning->warning_type ?>.
                        Por favor, leia atentamente antes de assinar.
                    </div>

                    <!-- Warning Details -->
                    <div class="card mb-4">
                        <div class="card-header">Detalhes da Advertência</div>
                        <div class="card-body">
                            <p><strong>Tipo:</strong> <?= ucfirst($warning->warning_type) ?></p>
                            <p><strong>Data da Ocorrência:</strong> <?= date('d/m/Y', strtotime($warning->occurrence_date)) ?></p>
                            <p><strong>Emitida por:</strong> <?= esc($issuer->name) ?></p>
                            <hr>
                            <p><strong>Motivo:</strong></p>
                            <div class="alert alert-light"><?= nl2br(esc($warning->reason)) ?></div>
                        </div>
                    </div>

                    <!-- PDF Preview -->
                    <?php if ($warning->pdf_path): ?>
                        <div class="mb-4 text-center">
                            <a href="/warnings/<?= $warning->id ?>/download" target="_blank" class="btn btn-primary">
                                <i class="fas fa-file-pdf"></i> Visualizar PDF Completo
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Signature Form -->
                    <form id="signatureForm">
                        <!-- Terms Acceptance -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="termsAccepted" required>
                                <label class="form-check-label" for="termsAccepted">
                                    <strong>Li e estou ciente do conteúdo desta advertência</strong>
                                </label>
                            </div>
                        </div>

                        <!-- Signature Method Selection -->
                        <div class="mb-4">
                            <label class="form-label"><strong>Método de Assinatura:</strong></label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="signature_method" id="methodSMS" value="sms" checked>
                                <label class="btn btn-outline-primary" for="methodSMS">
                                    <i class="fas fa-mobile-alt"></i> Código SMS
                                </label>

                                <input type="radio" class="btn-check" name="signature_method" id="methodICP" value="icp">
                                <label class="btn btn-outline-primary" for="methodICP">
                                    <i class="fas fa-certificate"></i> Certificado ICP-Brasil
                                </label>
                            </div>
                        </div>

                        <!-- SMS Method -->
                        <div id="smsMethod" class="signature-method">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Você receberá um código de 6 dígitos via SMS para confirmar sua assinatura.
                            </div>

                            <button type="button" class="btn btn-primary mb-3" onclick="sendSMS()">
                                <i class="fas fa-paper-plane"></i> Enviar Código SMS
                            </button>

                            <div id="smsCodeInput" style="display: none;">
                                <label class="form-label">Digite o código recebido:</label>
                                <input type="text" class="form-control" id="smsCode" maxlength="6" placeholder="000000">
                            </div>
                        </div>

                        <!-- ICP Method -->
                        <div id="icpMethod" class="signature-method" style="display: none;">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Faça upload do seu certificado digital ICP-Brasil (.pfx ou .p12)
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Certificado ICP-Brasil:</label>
                                <input type="file" class="form-control" id="certificateFile" accept=".pfx,.p12">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Senha do Certificado:</label>
                                <input type="password" class="form-control" id="certificatePassword">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="/warnings/<?= $warning->id ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Voltar
                            </a>
                            <button type="submit" class="btn btn-success" id="signButton" disabled>
                                <i class="fas fa-check"></i> Assinar Advertência
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const warningId = <?= $warning->id ?>;

// Enable/disable sign button based on terms
document.getElementById('termsAccepted').addEventListener('change', function() {
    document.getElementById('signButton').disabled = !this.checked;
});

// Toggle signature methods
document.querySelectorAll('[name="signature_method"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('smsMethod').style.display = this.value === 'sms' ? 'block' : 'none';
        document.getElementById('icpMethod').style.display = this.value === 'icp' ? 'block' : 'none';
    });
});

// Send SMS code
async function sendSMS() {
    try {
        const response = await fetch(`/warnings/${warningId}/send-sms`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'}
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            document.getElementById('smsCodeInput').style.display = 'block';
        } else {
            alert('Erro: ' + data.message);
        }
    } catch (error) {
        alert('Erro ao enviar SMS: ' + error.message);
    }
}

// Handle form submission
document.getElementById('signatureForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const method = document.querySelector('[name="signature_method"]:checked').value;
    const formData = new FormData();
    formData.append('terms_accepted', '1');
    formData.append('signature_method', method);

    if (method === 'sms') {
        const code = document.getElementById('smsCode').value;
        if (!code || code.length !== 6) {
            alert('Digite o código de 6 dígitos recebido via SMS');
            return;
        }
        formData.append('sms_code', code);
    } else {
        const certFile = document.getElementById('certificateFile').files[0];
        const certPass = document.getElementById('certificatePassword').value;

        if (!certFile || !certPass) {
            alert('Selecione o certificado e digite a senha');
            return;
        }

        formData.append('certificate', certFile);
        formData.append('certificate_password', certPass);
    }

    try {
        const response = await fetch(`/warnings/${warningId}/sign`, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            alert('Advertência assinada com sucesso!');
            window.location.href = `/warnings/${warningId}`;
        } else {
            alert('Erro: ' + data.message);
        }
    } catch (error) {
        alert('Erro ao assinar: ' + error.message);
    }
});
</script>

<?= $this->endSection() ?>
