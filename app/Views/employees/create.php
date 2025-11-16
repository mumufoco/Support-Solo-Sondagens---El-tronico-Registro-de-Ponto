<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Novo Funcionário<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-user-plus me-2"></i>Novo Funcionário
                    </h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="<?= base_url('employees') ?>">Funcionários</a></li>
                            <li class="breadcrumb-item active">Novo</li>
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
                    <form action="<?= base_url('employees/store') ?>" method="POST">
                        <?= csrf_field() ?>

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
                                       value="<?= old('name') ?>"
                                       required>
                                <?php if (session('errors.name')): ?>
                                    <div class="invalid-feedback"><?= session('errors.name') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">E-mail <span class="text-danger">*</span></label>
                                <input type="email" class="form-control <?= session('errors.email') ? 'is-invalid' : '' ?>"
                                       id="email" name="email"
                                       value="<?= old('email') ?>"
                                       required>
                                <?php if (session('errors.email')): ?>
                                    <div class="invalid-feedback"><?= session('errors.email') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="cpf" class="form-label">CPF <span class="text-danger">*</span></label>
                                <input type="text" class="form-control cpf-mask <?= session('errors.cpf') ? 'is-invalid' : '' ?>"
                                       id="cpf" name="cpf"
                                       value="<?= old('cpf') ?>"
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
                                       value="<?= old('phone') ?>"
                                       placeholder="(00) 00000-0000">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Senha <span class="text-danger">*</span></label>
                                <input type="password" class="form-control <?= session('errors.password') ? 'is-invalid' : '' ?>"
                                       id="password" name="password"
                                       placeholder="Mínimo 8 caracteres"
                                       required>
                                <?php if (session('errors.password')): ?>
                                    <div class="invalid-feedback"><?= session('errors.password') ?></div>
                                <?php endif; ?>
                                <small class="text-muted">Mínimo 8 caracteres com letras e números</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="password_confirm" class="form-label">Confirmar Senha <span class="text-danger">*</span></label>
                                <input type="password" class="form-control"
                                       id="password_confirm" name="password_confirm"
                                       required>
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
                                    <option value="TI" <?= old('department') === 'TI' ? 'selected' : '' ?>>TI</option>
                                    <option value="RH" <?= old('department') === 'RH' ? 'selected' : '' ?>>RH</option>
                                    <option value="Vendas" <?= old('department') === 'Vendas' ? 'selected' : '' ?>>Vendas</option>
                                    <option value="Financeiro" <?= old('department') === 'Financeiro' ? 'selected' : '' ?>>Financeiro</option>
                                    <option value="Operações" <?= old('department') === 'Operações' ? 'selected' : '' ?>>Operações</option>
                                </select>
                                <?php if (session('errors.department')): ?>
                                    <div class="invalid-feedback"><?= session('errors.department') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="position" class="form-label">Cargo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?= session('errors.position') ? 'is-invalid' : '' ?>"
                                       id="position" name="position"
                                       value="<?= old('position') ?>"
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
                                    <option value="funcionario" <?= old('role') === 'funcionario' ? 'selected' : '' ?>>Funcionário</option>
                                    <option value="gestor" <?= old('role') === 'gestor' ? 'selected' : '' ?>>Gestor</option>
                                    <option value="admin" <?= old('role') === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                </select>
                                <?php if (session('errors.role')): ?>
                                    <div class="invalid-feedback"><?= session('errors.role') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="expected_hours_daily" class="form-label">Horas Diárias Esperadas</label>
                                <input type="number" class="form-control"
                                       id="expected_hours_daily" name="expected_hours_daily"
                                       value="<?= old('expected_hours_daily', 8) ?>"
                                       min="1" max="12" step="0.5">
                                <small class="text-muted">Padrão: 8 horas</small>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="work_schedule_start" class="form-label">Horário de Entrada</label>
                                <input type="time" class="form-control"
                                       id="work_schedule_start" name="work_schedule_start"
                                       value="<?= old('work_schedule_start', '08:00') ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="work_schedule_end" class="form-label">Horário de Saída</label>
                                <input type="time" class="form-control"
                                       id="work_schedule_end" name="work_schedule_end"
                                       value="<?= old('work_schedule_end', '17:00') ?>">
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
                                           <?= old('active', true) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="active">
                                        Funcionário Ativo
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox"
                                           id="allow_remote_punch" name="allow_remote_punch"
                                           <?= old('allow_remote_punch', false) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="allow_remote_punch">
                                        Permitir Registro de Ponto Remoto
                                    </label>
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
                                        <i class="fas fa-save me-2"></i>Salvar Funcionário
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Help Panel -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Ajuda</h6>
                </div>
                <div class="card-body">
                    <h6>Informações Importantes</h6>
                    <ul class="small">
                        <li>Campos marcados com <span class="text-danger">*</span> são obrigatórios</li>
                        <li>O CPF deve ser único no sistema</li>
                        <li>O e-mail será usado para login</li>
                        <li>A senha deve ter no mínimo 8 caracteres</li>
                        <li>Um código único será gerado automaticamente</li>
                    </ul>

                    <hr>

                    <h6>Níveis de Acesso</h6>
                    <ul class="small">
                        <li><strong>Funcionário:</strong> Registrar ponto e visualizar própria jornada</li>
                        <li><strong>Gestor:</strong> Gerenciar equipe e aprovar justificativas</li>
                        <li><strong>Administrador:</strong> Acesso total ao sistema</li>
                    </ul>

                    <hr>

                    <h6>Próximos Passos</h6>
                    <ol class="small">
                        <li>Criar funcionário</li>
                        <li>Cadastrar biometria (facial ou digital)</li>
                        <li>Gerar QR Code para registro de ponto</li>
                        <li>Configurar notificações</li>
                    </ol>
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
    });
});
</script>
<?= $this->endSection() ?>
