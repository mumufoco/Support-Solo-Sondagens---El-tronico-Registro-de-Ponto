<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-warning">
                <div class="card-header bg-warning">
                    <h4 class="mb-0"><i class="fas fa-user-plus"></i> Adicionar Testemunha</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Recusa de Assinatura</strong><br>
                        O funcionário <strong><?= esc($warningEmployee->name) ?></strong> não assinou a advertência dentro do prazo de 48 horas.
                        É necessário adicionar uma testemunha presencial.
                    </div>

                    <!-- Warning Summary -->
                    <div class="card mb-4">
                        <div class="card-header">Resumo da Advertência</div>
                        <div class="card-body">
                            <p><strong>Tipo:</strong> <?= ucfirst($warning->warning_type) ?></p>
                            <p><strong>Data da Ocorrência:</strong> <?= date('d/m/Y', strtotime($warning->occurrence_date)) ?></p>
                            <p><strong>Emitida em:</strong> <?= date('d/m/Y H:i', strtotime($warning->created_at)) ?></p>
                            <p class="mb-0"><strong>Tempo decorrido:</strong>
                                <?= round((time() - strtotime($warning->created_at)) / 3600) ?> horas (> 48h)
                            </p>
                        </div>
                    </div>

                    <!-- Witness Form -->
                    <form id="witnessForm">
                        <h5 class="mb-3">Dados da Testemunha</h5>

                        <div class="mb-3">
                            <label for="witness_name" class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control" id="witness_name" required minlength="3" maxlength="255">
                        </div>

                        <div class="mb-3">
                            <label for="witness_cpf" class="form-label">CPF *</label>
                            <input type="text" class="form-control" id="witness_cpf" required pattern="\d{3}\.\d{3}\.\d{3}-\d{2}" placeholder="000.000.000-00">
                            <div class="form-text">Formato: 000.000.000-00</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Assinatura da Testemunha *</label>
                            <div class="card">
                                <div class="card-body">
                                    <canvas id="signatureCanvas" width="600" height="200" style="border: 1px solid #ccc; cursor: crosshair; width: 100%;"></canvas>
                                </div>
                                <div class="card-footer">
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="clearSignature()">
                                        <i class="fas fa-eraser"></i> Limpar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Importante:</strong> A testemunha confirma que esteve presente quando o funcionário
                            recusou-se a assinar a advertência. Esta ação será registrada permanentemente.
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="/warnings/<?= $warning->id ?>" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save"></i> Confirmar Recusa com Testemunha
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const canvas = document.getElementById('signatureCanvas');
const ctx = canvas.getContext('2d');
let drawing = false;
let hasSignature = false;

// Setup canvas
canvas.width = 600;
canvas.height = 200;
ctx.strokeStyle = '#000';
ctx.lineWidth = 2;
ctx.lineCap = 'round';

// Drawing functions
canvas.addEventListener('mousedown', startDrawing);
canvas.addEventListener('mousemove', draw);
canvas.addEventListener('mouseup', stopDrawing);
canvas.addEventListener('mouseout', stopDrawing);

// Touch support
canvas.addEventListener('touchstart', (e) => {
    e.preventDefault();
    const touch = e.touches[0];
    const mouseEvent = new MouseEvent('mousedown', {
        clientX: touch.clientX,
        clientY: touch.clientY
    });
    canvas.dispatchEvent(mouseEvent);
});

canvas.addEventListener('touchmove', (e) => {
    e.preventDefault();
    const touch = e.touches[0];
    const mouseEvent = new MouseEvent('mousemove', {
        clientX: touch.clientX,
        clientY: touch.clientY
    });
    canvas.dispatchEvent(mouseEvent);
});

canvas.addEventListener('touchend', (e) => {
    e.preventDefault();
    const mouseEvent = new MouseEvent('mouseup', {});
    canvas.dispatchEvent(mouseEvent);
});

function startDrawing(e) {
    drawing = true;
    hasSignature = true;
    const rect = canvas.getBoundingClientRect();
    ctx.beginPath();
    ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
}

function draw(e) {
    if (!drawing) return;
    const rect = canvas.getBoundingClientRect();
    ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
    ctx.stroke();
}

function stopDrawing() {
    drawing = false;
}

function clearSignature() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    hasSignature = false;
}

// CPF mask
document.getElementById('witness_cpf').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 11) value = value.substr(0, 11);

    if (value.length > 9) {
        value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    } else if (value.length > 6) {
        value = value.replace(/(\d{3})(\d{3})(\d+)/, '$1.$2.$3');
    } else if (value.length > 3) {
        value = value.replace(/(\d{3})(\d+)/, '$1.$2');
    }

    e.target.value = value;
});

// Form submission
document.getElementById('witnessForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    if (!hasSignature) {
        alert('Por favor, adicione a assinatura da testemunha');
        return;
    }

    const name = document.getElementById('witness_name').value;
    const cpf = document.getElementById('witness_cpf').value;
    const signature = canvas.toDataURL();

    try {
        const response = await fetch('/warnings/<?= $warning->id ?>/refuse-signature', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                witness_name: name,
                witness_cpf: cpf,
                witness_signature: signature
            })
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            window.location.href = '/warnings/<?= $warning->id ?>';
        } else {
            alert('Erro: ' + data.message);
        }
    } catch (error) {
        alert('Erro ao adicionar testemunha: ' + error.message);
    }
});
</script>

<?= $this->endSection() ?>
