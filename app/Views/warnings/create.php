<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Nova Advertência</h4>
                </div>
                <div class="card-body">
                    <form action="/warnings" method="post" enctype="multipart/form-data">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="employee_id" class="form-label">Funcionário *</label>
                            <select name="employee_id" id="employee_id" class="form-select" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= $emp->id ?>"><?= esc($emp->name) ?> - <?= esc($emp->department) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="warning_type" class="form-label">Tipo de Advertência *</label>
                                <select name="warning_type" id="warning_type" class="form-select" required>
                                    <option value="">Selecione...</option>
                                    <option value="verbal">Verbal</option>
                                    <option value="escrita">Escrita</option>
                                    <option value="suspensao">Suspensão</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="occurrence_date" class="form-label">Data da Ocorrência *</label>
                                <input type="date" name="occurrence_date" id="occurrence_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="reason" class="form-label">Motivo Detalhado * (mínimo 50 caracteres)</label>
                            <textarea name="reason" id="reason" class="form-control" rows="6" required minlength="50" maxlength="5000"></textarea>
                            <div class="form-text">
                                <span id="charCount">0</span>/5000 caracteres (mínimo 50)
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="evidence_files" class="form-label">Evidências (Opcional - Máx. 5 arquivos, 10MB cada)</label>
                            <input type="file" name="evidence_files[]" id="evidence_files" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                            <div class="form-text">Formatos aceitos: PDF, JPG, PNG, DOC, DOCX</div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i>
                            <strong>Importante:</strong>
                            <ul class="mb-0">
                                <li>O funcionário receberá notificação por e-mail para assinar a advertência</li>
                                <li>O PDF será gerado automaticamente e assinado digitalmente</li>
                                <li>Após 48h sem assinatura, será possível adicionar testemunha</li>
                            </ul>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="/warnings" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Voltar
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-save"></i> Emitir Advertência
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('reason').addEventListener('input', function() {
    document.getElementById('charCount').textContent = this.value.length;
});
</script>

<?= $this->endSection() ?>
