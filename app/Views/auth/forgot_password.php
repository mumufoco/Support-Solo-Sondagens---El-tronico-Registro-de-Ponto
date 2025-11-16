<?= $this->extend('layouts/auth') ?>

<?= $this->section('title') ?>Esqueci Minha Senha<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="auth-card">
    <div class="auth-header">
        <i class="fas fa-key fa-3x mb-3"></i>
        <h1>Esqueci Minha Senha</h1>
        <p>Recupere o acesso à sua conta</p>
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

        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Digite seu e-mail cadastrado. Enviaremos instruções para redefinir sua senha.
        </div>

        <form action="<?= base_url('forgot-password') ?>" method="POST">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <input type="email" class="form-control" id="email" name="email"
                           placeholder="seu@email.com"
                           value="<?= old('email') ?>" required autofocus>
                </div>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-paper-plane me-2"></i>Enviar Link de Recuperação
                </button>
            </div>

            <div class="text-center">
                <a href="<?= base_url('login') ?>" class="text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i>Voltar ao Login
                </a>
            </div>
        </form>
    </div>

    <div class="auth-footer">
        <p class="mb-0 text-muted small">
            <i class="fas fa-shield-alt me-1"></i>
            Seus dados estão protegidos conforme a LGPD
        </p>
    </div>
</div>
<?= $this->endSection() ?>
