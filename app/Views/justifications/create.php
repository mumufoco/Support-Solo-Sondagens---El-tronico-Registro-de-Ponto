<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Nova Justificativa<?= $this->endSection() ?>

<?= $this->section('styles') ?>
<!-- Flatpickr CSS (Date Picker) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">

<style>
    .char-counter {
        font-size: 0.85rem;
        color: #6c757d;
    }

    .char-counter.text-warning {
        color: #ffc107 !important;
    }

    .char-counter.text-danger {
        color: #dc3545 !important;
    }

    .file-upload-area {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 2rem;
        text-align: center;
        transition: all 0.3s;
        cursor: pointer;
    }

    .file-upload-area:hover {
        border-color: #0d6efd;
        background-color: #f8f9fa;
    }

    .file-upload-area.drag-over {
        border-color: #0d6efd;
        background-color: #e7f1ff;
    }

    .file-preview {
        display: inline-block;
        position: relative;
        margin: 0.5rem;
        padding: 0.5rem;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        background: #f8f9fa;
    }

    .file-preview .remove-file {
        position: absolute;
        top: -8px;
        right: -8px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #dc3545;
        color: white;
        border: 2px solid white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }

    .file-preview .remove-file:hover {
        background: #bb2d3b;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1">
                <i class="fas fa-plus-circle me-2"></i>Nova Justificativa
            </h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?= base_url('justifications') ?>">Justificativas</a></li>
                    <li class="breadcrumb-item active">Nova</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Preencha os dados da justificativa
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Info Alert -->
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Importante:</strong> Preencha todos os campos obrigatórios. Justificativas são enviadas para aprovação do gestor.
                        <?php if (in_array($employee['role'], ['admin', 'gestor'])): ?>
                            <br><small>Como gestor/admin, suas justificativas serão aprovadas automaticamente.</small>
                        <?php endif; ?>
                    </div>

                    <form action="<?= base_url('justifications') ?>" method="POST" enctype="multipart/form-data" id="justificationForm">
                        <?= csrf_field() ?>

                        <!-- Date -->
                        <div class="mb-3">
                            <label for="justification_date" class="form-label">
                                Data <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control <?= session('errors.justification_date') ? 'is-invalid' : '' ?>"
                                   id="justification_date"
                                   name="justification_date"
                                   placeholder="Selecione a data"
                                   value="<?= old('justification_date', $date ?? '') ?>"
                                   required>
                            <?php if (session('errors.justification_date')): ?>
                                <div class="invalid-feedback"><?= session('errors.justification_date') ?></div>
                            <?php endif; ?>
                            <div class="form-text">
                                <i class="fas fa-calendar-alt me-1"></i>
                                Não é permitido justificar datas futuras
                            </div>
                        </div>

                        <!-- Type -->
                        <div class="mb-3">
                            <label for="justification_type" class="form-label">
                                Tipo de Justificativa <span class="text-danger">*</span>
                            </label>
                            <select class="form-select <?= session('errors.justification_type') ? 'is-invalid' : '' ?>"
                                    id="justification_type"
                                    name="justification_type"
                                    required>
                                <option value="">Selecione o tipo</option>
                                <option value="falta" <?= old('justification_type') === 'falta' ? 'selected' : '' ?>>
                                    Falta
                                </option>
                                <option value="atraso" <?= old('justification_type') === 'atraso' ? 'selected' : '' ?>>
                                    Atraso
                                </option>
                                <option value="saida-antecipada" <?= old('justification_type') === 'saida-antecipada' ? 'selected' : '' ?>>
                                    Saída Antecipada
                                </option>
                            </select>
                            <?php if (session('errors.justification_type')): ?>
                                <div class="invalid-feedback"><?= session('errors.justification_type') ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Category -->
                        <div class="mb-3">
                            <label for="category" class="form-label">
                                Categoria <span class="text-danger">*</span>
                            </label>
                            <select class="form-select <?= session('errors.category') ? 'is-invalid' : '' ?>"
                                    id="category"
                                    name="category"
                                    required>
                                <option value="">Selecione a categoria</option>
                                <option value="doenca" <?= old('category') === 'doenca' ? 'selected' : '' ?>>
                                    Doença
                                </option>
                                <option value="compromisso-pessoal" <?= old('category') === 'compromisso-pessoal' ? 'selected' : '' ?>>
                                    Compromisso Pessoal
                                </option>
                                <option value="emergencia-familiar" <?= old('category') === 'emergencia-familiar' ? 'selected' : '' ?>>
                                    Emergência Familiar
                                </option>
                                <option value="outro" <?= old('category') === 'outro' ? 'selected' : '' ?>>
                                    Outro
                                </option>
                            </select>
                            <?php if (session('errors.category')): ?>
                                <div class="invalid-feedback"><?= session('errors.category') ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Reason -->
                        <div class="mb-3">
                            <label for="reason" class="form-label">
                                Motivo Detalhado <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control <?= session('errors.reason') ? 'is-invalid' : '' ?>"
                                      id="reason"
                                      name="reason"
                                      rows="5"
                                      placeholder="Descreva o motivo da justificativa com detalhes (mínimo 50 caracteres)"
                                      required><?= old('reason') ?></textarea>
                            <div class="d-flex justify-content-between">
                                <div class="form-text">
                                    <i class="fas fa-pen me-1"></i>
                                    Mínimo 50 caracteres, máximo 500
                                </div>
                                <div class="char-counter" id="charCounter">
                                    <span id="charCount">0</span> / 500
                                </div>
                            </div>
                            <?php if (session('errors.reason')): ?>
                                <div class="invalid-feedback d-block"><?= session('errors.reason') ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Attachments -->
                        <div class="mb-4">
                            <label class="form-label">
                                Anexos (Opcional)
                            </label>
                            <div class="file-upload-area" id="fileUploadArea">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                                <p class="mb-2">
                                    <strong>Clique ou arraste arquivos aqui</strong>
                                </p>
                                <p class="text-muted small mb-0">
                                    Máximo 3 arquivos • PDF, JPG ou PNG • 5MB cada
                                </p>
                                <input type="file"
                                       id="attachments"
                                       name="attachments[]"
                                       multiple
                                       accept=".pdf,.jpg,.jpeg,.png"
                                       class="d-none">
                            </div>

                            <div id="filePreviewContainer" class="mt-3"></div>

                            <div class="form-text">
                                <i class="fas fa-info-circle me-1"></i>
                                Anexe documentos comprobatórios (ex: atestado médico, comprovante)
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                            <a href="<?= base_url('justifications') ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-paper-plane me-2"></i>Enviar Justificativa
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Flatpickr JS (Date Picker) -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>

<script>
    // Date picker initialization
    flatpickr('#justification_date', {
        locale: 'pt',
        dateFormat: 'Y-m-d',
        maxDate: 'today',
        defaultDate: '<?= $date ?? '' ?>',
        allowInput: true,
    });

    // Character counter
    const reasonTextarea = document.getElementById('reason');
    const charCountSpan = document.getElementById('charCount');
    const charCounter = document.getElementById('charCounter');

    reasonTextarea.addEventListener('input', function() {
        const count = this.value.length;
        charCountSpan.textContent = count;

        // Update color based on count
        charCounter.classList.remove('text-warning', 'text-danger', 'text-success');

        if (count < 50) {
            charCounter.classList.add('text-danger');
        } else if (count > 450) {
            charCounter.classList.add('text-warning');
        } else {
            charCounter.classList.add('text-success');
        }

        // Limit to 500 chars
        if (count > 500) {
            this.value = this.value.substring(0, 500);
            charCountSpan.textContent = 500;
        }
    });

    // Trigger initial count
    reasonTextarea.dispatchEvent(new Event('input'));

    // File upload handling
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('attachments');
    const filePreviewContainer = document.getElementById('filePreviewContainer');
    let selectedFiles = [];

    // Click to upload
    fileUploadArea.addEventListener('click', () => fileInput.click());

    // Drag and drop
    fileUploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        fileUploadArea.classList.add('drag-over');
    });

    fileUploadArea.addEventListener('dragleave', () => {
        fileUploadArea.classList.remove('drag-over');
    });

    fileUploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        fileUploadArea.classList.remove('drag-over');

        const files = e.dataTransfer.files;
        handleFiles(files);
    });

    // File input change
    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        // Validate max 3 files
        if (selectedFiles.length + files.length > 3) {
            alert('Máximo de 3 arquivos permitidos.');
            return;
        }

        Array.from(files).forEach(file => {
            // Validate file type
            const validTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'];
            if (!validTypes.includes(file.type)) {
                alert(`Tipo de arquivo não permitido: ${file.name}`);
                return;
            }

            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert(`Arquivo muito grande (máx 5MB): ${file.name}`);
                return;
            }

            // Add to selected files
            selectedFiles.push(file);
            renderFilePreview(file);
        });

        updateFileInput();
    }

    function renderFilePreview(file) {
        const preview = document.createElement('div');
        preview.className = 'file-preview';

        const fileIcon = file.type === 'application/pdf'
            ? '<i class="fas fa-file-pdf text-danger fa-2x"></i>'
            : '<i class="fas fa-file-image text-primary fa-2x"></i>';

        preview.innerHTML = `
            ${fileIcon}
            <div class="ms-2 d-inline-block">
                <div class="small"><strong>${file.name}</strong></div>
                <div class="text-muted" style="font-size: 0.75rem;">${(file.size / 1024).toFixed(1)} KB</div>
            </div>
            <button type="button" class="remove-file" onclick="removeFile('${file.name}')">
                <i class="fas fa-times"></i>
            </button>
        `;

        filePreviewContainer.appendChild(preview);
    }

    function removeFile(fileName) {
        selectedFiles = selectedFiles.filter(f => f.name !== fileName);
        renderAllPreviews();
        updateFileInput();
    }

    function renderAllPreviews() {
        filePreviewContainer.innerHTML = '';
        selectedFiles.forEach(file => renderFilePreview(file));
    }

    function updateFileInput() {
        // Create a new DataTransfer object
        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;
    }

    // Form validation
    document.getElementById('justificationForm').addEventListener('submit', function(e) {
        const reasonValue = reasonTextarea.value.trim();

        if (reasonValue.length < 50) {
            e.preventDefault();
            alert('O motivo deve ter no mínimo 50 caracteres.');
            reasonTextarea.focus();
            return false;
        }

        if (reasonValue.length > 500) {
            e.preventDefault();
            alert('O motivo deve ter no máximo 500 caracteres.');
            reasonTextarea.focus();
            return false;
        }

        // Disable submit button to prevent double submission
        document.getElementById('submitBtn').disabled = true;
        document.getElementById('submitBtn').innerHTML =
            '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';
    });
</script>
<?= $this->endSection() ?>
