<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Sistema de Ponto Eletrônico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .register-container {
            width: 100%;
            max-width: 500px;
            padding: 20px;
        }
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .register-header i {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .register-header h3 {
            margin: 0;
            font-weight: 600;
        }
        .register-body {
            padding: 30px;
        }
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        .input-group:focus-within .input-group-text {
            border-color: #667eea;
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .btn-register:active {
            transform: translateY(0);
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .password-requirements {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
        }
        .password-requirements i {
            font-size: 0.75rem;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <i class="fas fa-user-plus"></i>
                <h3>Criar Conta</h3>
                <p class="mb-0 mt-2">Sistema de Ponto Eletrônico</p>
            </div>
            <div class="register-body">
                <?php if (session()->has('error')): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= session('error') ?>
                    </div>
                <?php endif; ?>

                <?php if (session()->has('errors')): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Erros de validação:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach (session('errors') as $error): ?>
                                <li><?= esc($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (session()->has('message')): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= session('message') ?>
                    </div>
                <?php endif; ?>

                <form action="<?= base_url('auth/register') ?>" method="POST">
                    <?= csrf_field() ?>

                    <!-- Nome Completo -->
                    <div class="mb-3">
                        <label for="name" class="form-label">
                            <i class="fas fa-user me-1"></i> Nome Completo
                        </label>
                        <input type="text" class="form-control" id="name" name="name"
                               value="<?= old('name') ?>" required autofocus>
                    </div>

                    <!-- E-mail -->
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope me-1"></i> E-mail
                        </label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= old('email') ?>" required>
                    </div>

                    <!-- CPF -->
                    <div class="mb-3">
                        <label for="cpf" class="form-label">
                            <i class="fas fa-id-card me-1"></i> CPF
                        </label>
                        <input type="text" class="form-control" id="cpf" name="cpf"
                               value="<?= old('cpf') ?>" placeholder="000.000.000-00"
                               maxlength="14" required>
                        <small class="text-muted">Digite apenas números (11 dígitos)</small>
                    </div>

                    <!-- Senha -->
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-1"></i> Senha
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-key"></i>
                            </span>
                            <input type="password" class="form-control" id="password"
                                   name="password" required>
                            <button class="btn btn-outline-secondary" type="button"
                                    id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-requirements mt-2">
                            <i class="fas fa-info-circle"></i> A senha deve conter:
                            <ul class="mb-0 mt-1">
                                <li>Mínimo de 8 caracteres</li>
                                <li>Letra maiúscula e minúscula</li>
                                <li>Número e caractere especial (@, #, $, etc.)</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Confirmar Senha -->
                    <div class="mb-4">
                        <label for="password_confirm" class="form-label">
                            <i class="fas fa-lock me-1"></i> Confirmar Senha
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-key"></i>
                            </span>
                            <input type="password" class="form-control" id="password_confirm"
                                   name="password_confirm" required>
                        </div>
                    </div>

                    <!-- Botão Cadastrar -->
                    <button type="submit" class="btn btn-register">
                        <i class="fas fa-user-plus me-2"></i>
                        Criar Conta
                    </button>
                </form>

                <div class="login-link">
                    Já possui uma conta?
                    <a href="<?= base_url('auth/login') ?>">
                        <i class="fas fa-sign-in-alt me-1"></i>Fazer Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // CPF Mask
        document.getElementById('cpf').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');

            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                e.target.value = value;
            }
        });

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function(e) {
            const password = e.target.value;
            const requirements = {
                length: password.length >= 8,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };

            // You could add visual feedback here
        });

        // Confirm password validation
        document.getElementById('password_confirm').addEventListener('input', function(e) {
            const password = document.getElementById('password').value;
            const confirm = e.target.value;

            if (password !== confirm) {
                e.target.setCustomValidity('As senhas não coincidem');
            } else {
                e.target.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
