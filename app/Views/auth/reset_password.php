<?= $this->extend('layouts/auth') ?>

<?= $this->section('title') ?>Redefinir Senha<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="auth-card">
    <div class="auth-header">
        <i class="fas fa-lock fa-3x mb-3"></i>
        <h1>Redefinir Senha</h1>
        <p>Crie uma nova senha segura</p>
    </div>

    <div class="auth-body">
        <?php if (session()->has('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('reset-password') ?>" method="POST" id="resetForm">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= esc($token ?? '') ?>">

            <div class="mb-3">
                <label for="password" class="form-label">Nova Senha</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="••••••••" required autofocus>
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="form-text">
                    Mínimo 8 caracteres, com letras maiúsculas, minúsculas, números e símbolos
                </div>
            </div>

            <div class="mb-3">
                <label for="password_confirm" class="form-label">Confirmar Nova Senha</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="fas fa-lock"></i>
                    </span>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                           placeholder="••••••••" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirm">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <!-- Password Strength Indicator -->
            <div class="mb-3">
                <label class="form-label small">Força da Senha:</label>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                </div>
                <div id="passwordFeedback" class="form-text"></div>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-check me-2"></i>Redefinir Senha
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
            <i class="fas fa-info-circle me-1"></i>
            Após redefinir, você será redirecionado para o login
        </p>
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

    document.getElementById('togglePasswordConfirm').addEventListener('click', function() {
        const password = document.getElementById('password_confirm');
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

    // Password strength checker
    document.getElementById('password').addEventListener('input', function() {
        const password = this.value;
        const strengthBar = document.getElementById('passwordStrength');
        const feedback = document.getElementById('passwordFeedback');

        let strength = 0;
        let messages = [];

        // Length
        if (password.length >= 8) {
            strength += 20;
        } else {
            messages.push('Mínimo 8 caracteres');
        }

        // Lowercase
        if (/[a-z]/.test(password)) {
            strength += 20;
        } else {
            messages.push('Letra minúscula');
        }

        // Uppercase
        if (/[A-Z]/.test(password)) {
            strength += 20;
        } else {
            messages.push('Letra maiúscula');
        }

        // Numbers
        if (/[0-9]/.test(password)) {
            strength += 20;
        } else {
            messages.push('Número');
        }

        // Special characters
        if (/[^A-Za-z0-9]/.test(password)) {
            strength += 20;
        } else {
            messages.push('Caractere especial');
        }

        // Update progress bar
        strengthBar.style.width = strength + '%';

        // Color coding
        if (strength < 40) {
            strengthBar.className = 'progress-bar bg-danger';
            feedback.innerHTML = '<span class="text-danger">Fraca - Necessário: ' + messages.join(', ') + '</span>';
        } else if (strength < 60) {
            strengthBar.className = 'progress-bar bg-warning';
            feedback.innerHTML = '<span class="text-warning">Regular - Adicione: ' + messages.join(', ') + '</span>';
        } else if (strength < 80) {
            strengthBar.className = 'progress-bar bg-info';
            feedback.innerHTML = '<span class="text-info">Boa</span>';
        } else {
            strengthBar.className = 'progress-bar bg-success';
            feedback.innerHTML = '<span class="text-success">Excelente!</span>';
        }
    });

    // Validate password match on submit
    document.getElementById('resetForm').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('password_confirm').value;

        if (password !== confirmPassword) {
            e.preventDefault();
            alert('As senhas não coincidem!');
            return false;
        }

        // Check minimum strength
        const strengthBar = document.getElementById('passwordStrength');
        const strength = parseInt(strengthBar.style.width);

        if (strength < 60) {
            e.preventDefault();
            alert('A senha não atende aos requisitos mínimos de segurança. Por favor, use uma senha mais forte.');
            return false;
        }
    });
</script>
<?= $this->endSection() ?>
