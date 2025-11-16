<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Editar Funcionário<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-user-edit me-2"></i>Editar Funcionário
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="<?= base_url('employees') ?>">Funcionários</a></li>
                            <li class="breadcrumb-item active"><?= esc($employee->name) ?></li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="<?= base_url('employees') ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Voltar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Form -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Dados do Funcionário</h5>
                </div>
                <div class="card-body">
                    <form action="<?= base_url('employees/update/' . $employee->id) ?>" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="_method" value="PUT">

                        <!-- Personal Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-muted border-bottom pb-2 mb-3">
                                    <i class="fas fa-user me-2"></i>Informações Pessoais
                                </h6>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Nome Completo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= session('errors.name') ? 'is-invalid' : '' ?>"
                                       id="name" name="name"
                                       value="<?= old('name', $employee->name) ?>"
                                       required>
                                <?php if (session('errors.name')): ?>
                                    <div class="invalid-feedback"><?= session('errors.name') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">E-mail <span class="text-danger">*</span></label>
                                <input type="email" class="form-control <?= session('errors.email') ? 'is-invalid' : '' ?>"
                                       id="email" name="email"
                                       value="<?= old('email', $employee->email) ?>"
                                       required>
                                <?php if (session('errors.email')): ?>
                                    <div class="invalid-feedback"><?= session('errors.email') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="cpf" class="form-label">CPF <span class="text-danger">*</span></label>
                                <input type="text" class="form-control cpf-mask <?= session('errors.cpf') ? 'is-invalid' : '' ?>"
                                       id="cpf" name="cpf"
                                       value="<?= old('cpf', $employee->cpf) ?>"
                                       placeholder="000.000.000-00"
                                       required>
                                <?php if (session('errors.cpf')): ?>
                                    <div class="invalid-feedback"><?= session('errors.cpf') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Telefone</label>
                                <input type="text" class="form-control phone-mask"
                                       id="phone" name="phone"
                                       value="<?= old('phone', $employee->phone ?? '') ?>"
                                       placeholder="(00) 00000-0000">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="unique_code" class="form-label">Código Único</label>
                                <input type="text" class="form-control" id="unique_code"
                                       value="<?= esc($employee->unique_code) ?>" readonly>
                                <small class="text-muted">Código gerado automaticamente</small>
                            </div>
                        </div>

                        <!-- Password Change (Optional) -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-muted border-bottom pb-2 mb-3">
                                    <i class="fas fa-key me-2"></i>Alterar Senha (Opcional)
                                </h6>
                                <p class="text-muted small">Deixe em branco se não deseja alterar a senha</p>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Nova Senha</label>
                                <input type="password" class="form-control <?= session('errors.password') ? 'is-invalid' : '' ?>"
                                       id="password" name="password"
                                       placeholder="Mínimo 8 caracteres">
                                <?php if (session('errors.password')): ?>
                                    <div class="invalid-feedback"><?= session('errors.password') ?></div>
                                <?php endif; ?>
                                <small class="text-muted">Mínimo 8 caracteres com letras e números</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="password_confirm" class="form-label">Confirmar Nova Senha</label>
                                <input type="password" class="form-control"
                                       id="password_confirm" name="password_confirm">
                            </div>
                        </div>

                        <!-- Work Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-muted border-bottom pb-2 mb-3">
                                    <i class="fas fa-briefcase me-2"></i>Informações de Trabalho
                                </h6>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="department" class="form-label">Departamento <span class="text-danger">*</span></label>
                                <select class="form-select <?= session('errors.department') ? 'is-invalid' : '' ?>"
                                        id="department" name="department" required>
                                    <option value="">Selecione...</option>
                                    <option value="TI" <?= old('department', $employee->department) === 'TI' ? 'selected' : '' ?>>TI</option>
                                    <option value="RH" <?= old('department', $employee->department) === 'RH' ? 'selected' : '' ?>>RH</option>
                                    <option value="Vendas" <?= old('department', $employee->department) === 'Vendas' ? 'selected' : '' ?>>Vendas</option>
                                    <option value="Financeiro" <?= old('department', $employee->department) === 'Financeiro' ? 'selected' : '' ?>>Financeiro</option>
                                    <option value="Operações" <?= old('department', $employee->department) === 'Operações' ? 'selected' : '' ?>>Operações</option>
                                </select>
                                <?php if (session('errors.department')): ?>
                                    <div class="invalid-feedback"><?= session('errors.department') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="position" class="form-label">Cargo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= session('errors.position') ? 'is-invalid' : '' ?>"
                                       id="position" name="position"
                                       value="<?= old('position', $employee->position) ?>"
                                       required>
                                <?php if (session('errors.position')): ?>
                                    <div class="invalid-feedback"><?= session('errors.position') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="role" class="form-label">Nível de Acesso <span class="text-danger">*</span></label>
                                <select class="form-select <?= session('errors.role') ? 'is-invalid' : '' ?>"
                                        id="role" name="role" required>
                                    <option value="">Selecione...</option>
                                    <option value="funcionario" <?= old('role', $employee->role) === 'funcionario' ? 'selected' : '' ?>>Funcionário</option>
                                    <option value="gestor" <?= old('role', $employee->role) === 'gestor' ? 'selected' : '' ?>>Gestor</option>
                                    <option value="admin" <?= old('role', $employee->role) === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                </select>
                                <?php if (session('errors.role')): ?>
                                    <div class="invalid-feedback"><?= session('errors.role') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="expected_hours_daily" class="form-label">Horas Diárias Esperadas</label>
                                <input type="number" class="form-control"
                                       id="expected_hours_daily" name="expected_hours_daily"
                                       value="<?= old('expected_hours_daily', $employee->expected_hours_daily ?? 8) ?>"
                                       min="1" max="12" step="0.5">
                                <small class="text-muted">Padrão: 8 horas</small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="work_schedule_start" class="form-label">Horário de Entrada</label>
                                <input type="time" class="form-control"
                                       id="work_schedule_start" name="work_schedule_start"
                                       value="<?= old('work_schedule_start', $employee->work_schedule_start ?? '08:00') ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="work_schedule_end" class="form-label">Horário de Saída</label>
                                <input type="time" class="form-control"
                                       id="work_schedule_end" name="work_schedule_end"
                                       value="<?= old('work_schedule_end', $employee->work_schedule_end ?? '17:00') ?>">
                            </div>
                        </div>

                        <!-- Additional Settings -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-muted border-bottom pb-2 mb-3">
                                    <i class="fas fa-cog me-2"></i>Configurações Adicionais
                                </h6>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                           id="active" name="active"
                                           <?= old('active', $employee->active) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="active">
                                        Funcionário Ativo
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                           id="allow_remote_punch" name="allow_remote_punch"
                                           <?= old('allow_remote_punch', $employee->allow_remote_punch ?? false) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="allow_remote_punch">
                                        Permitir Registro de Ponto Remoto
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Biometric Status -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-muted border-bottom pb-2 mb-3">
                                    <i class="fas fa-fingerprint me-2"></i>Status de Biometria
                                </h6>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="alert <?= $employee->has_face_biometric ? 'alert-success' : 'alert-warning' ?>">
                                    <i class="fas fa-face-smile me-2"></i>
                                    <strong>Biometria Facial:</strong>
                                    <?= $employee->has_face_biometric ? 'Cadastrada' : 'Não cadastrada' ?>
                                    <?php if ($employee->has_face_biometric): ?>
                                        <a href="<?= base_url('biometric/face/' . $employee->id) ?>" class="ms-2">Gerenciar</a>
                                    <?php else: ?>
                                        <a href="<?= base_url('biometric/face/enroll/' . $employee->id) ?>" class="ms-2">Cadastrar</a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="alert <?= $employee->has_fingerprint_biometric ? 'alert-success' : 'alert-warning' ?>">
                                    <i class="fas fa-fingerprint me-2"></i>
                                    <strong>Biometria Digital:</strong>
                                    <?= $employee->has_fingerprint_biometric ? 'Cadastrada' : 'Não cadastrada' ?>
                                    <?php if ($employee->has_fingerprint_biometric): ?>
                                        <a href="<?= base_url('biometric/fingerprint/' . $employee->id) ?>" class="ms-2">Gerenciar</a>
                                    <?php else: ?>
                                        <a href="<?= base_url('fingerprint/enroll/' . $employee->id) ?>" class="ms-2">Cadastrar</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row">
                            <div class="col-12">
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <a href="<?= base_url('employees') ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Salvar Alterações
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Info Panel -->
        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informações</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Código Único:</strong> <code><?= esc($employee->unique_code) ?></code></p>
                    <p class="mb-2"><strong>Cadastrado em:</strong> <?= date('d/m/Y', strtotime($employee->created_at)) ?></p>
                    <p class="mb-2"><strong>Última atualização:</strong> <?= date('d/m/Y H:i', strtotime($employee->updated_at)) ?></p>
                    <?php if (isset($employee->extra_hours_balance)): ?>
                        <p class="mb-2">
                            <strong>Saldo de Horas:</strong>
                            <span class="<?= $employee->extra_hours_balance >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= $employee->extra_hours_balance >= 0 ? '+' : '' ?><?= number_format($employee->extra_hours_balance, 2) ?>h
                            </span>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Atenção</h6>
                </div>
                <div class="card-body">
                    <ul class="small mb-0">
                        <li>Alterações no e-mail afetam o login</li>
                        <li>Alterações no CPF devem ser cuidadosas (validação LGPD)</li>
                        <li>Deixe a senha em branco se não quiser alterá-la</li>
                        <li>Desativar um funcionário bloqueia o acesso ao sistema</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script>
$(document).ready(function() {
    // CPF Mask
    $('.cpf-mask').mask('000.000.000-00');

    // Phone Mask
    $('.phone-mask').mask('(00) 00000-0000');

    // Form validation
    $('form').on('submit', function(e) {
        const password = $('#password').val();
        const passwordConfirm = $('#password_confirm').val();

        // Only validate if password fields are filled
        if (password || passwordConfirm) {
            if (password !== passwordConfirm) {
                e.preventDefault();
                alert('As senhas não coincidem!');
                $('#password_confirm').addClass('is-invalid');
                return false;
            }

            if (password.length < 8) {
                e.preventDefault();
                alert('A senha deve ter no mínimo 8 caracteres!');
                $('#password').addClass('is-invalid');
                return false;
            }
        }
    });
});
</script>
<?= $this->endSection() ?>
