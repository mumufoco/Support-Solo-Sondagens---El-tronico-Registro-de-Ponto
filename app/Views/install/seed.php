<?= $this->extend('install/layout') ?>

<?= $this->section('content') ?>

<div class="step-indicator">
    <div class="step completed">
        <div class="step-number">✓</div>
        <div class="step-label">Requisitos</div>
    </div>
    <div class="step completed">
        <div class="step-number">✓</div>
        <div class="step-label">Banco de Dados</div>
    </div>
    <div class="step completed">
        <div class="step-number">✓</div>
        <div class="step-label">Migrations</div>
    </div>
    <div class="step active">
        <div class="step-number">4</div>
        <div class="step-label">Dados Iniciais</div>
    </div>
    <div class="step">
        <div class="step-number">5</div>
        <div class="step-label">Concluir</div>
    </div>
</div>

<h2 style="font-size: 22px; margin-bottom: 25px; color: #333;">👤 Configuração Inicial</h2>

<div class="alert alert-info">
    <strong>ℹ️ Criar Usuário Administrador:</strong><br>
    Você precisa criar um usuário administrador para acessar o sistema.
</div>

<form id="seed-form">
    <div class="card">
        <div class="card-title">Dados do Administrador</div>

        <div class="form-group">
            <label for="admin_name">Nome Completo *</label>
            <input type="text" id="admin_name" name="admin_name" value="Administrador" required>
        </div>

        <div class="form-group">
            <label for="admin_email">E-mail *</label>
            <input type="email" id="admin_email" name="admin_email" value="admin@exemplo.com" required>
            <div class="help-text">Será usado para fazer login no sistema</div>
        </div>

        <div class="form-group">
            <label for="admin_password">Senha *</label>
            <input type="password" id="admin_password" name="admin_password" required minlength="8">
            <div class="help-text">Mínimo 8 caracteres. Recomendado: letras, números e símbolos</div>
        </div>

        <div class="form-group">
            <label for="admin_password_confirm">Confirmar Senha *</label>
            <input type="password" id="admin_password_confirm" name="admin_password_confirm" required minlength="8">
        </div>
    </div>

    <div class="card">
        <div class="card-title">Dados de Exemplo (Opcional)</div>

        <div class="checkbox-group">
            <input type="checkbox" id="include_sample_data" name="include_sample_data" value="yes">
            <label for="include_sample_data" style="margin-bottom: 0;">
                Incluir dados de exemplo (gestor e funcionário de teste)
            </label>
        </div>
        <div class="help-text" style="margin-left: 30px;">
            Útil para testar o sistema. Você pode excluir depois.
        </div>
    </div>

    <div id="validation-error" class="alert alert-error" style="display: none;"></div>

    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 15px;">
        ▶ Criar Usuários e Finalizar Instalação
    </button>
</form>

<!-- Console de Output -->
<div id="seed-output" style="display: none;">
    <div class="console-output" id="console-output"></div>
</div>

<!-- Loading -->
<div class="loading" id="loading">
    <div class="spinner"></div>
    <p>Criando usuários...</p>
</div>

<div class="button-group">
    <a href="/install/migrations" class="btn btn-secondary">← Voltar</a>
    <a href="/install/finish" id="continue-btn" class="btn btn-success" style="display: none;">
        Finalizar Instalação →
    </a>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('seed-form');
    const continueBtn = document.getElementById('continue-btn');
    const seedOutput = document.getElementById('seed-output');
    const consoleOutput = document.getElementById('console-output');
    const loading = document.getElementById('loading');
    const validationError = document.getElementById('validation-error');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Validar senhas
        const password = document.getElementById('admin_password').value;
        const passwordConfirm = document.getElementById('admin_password_confirm').value;

        validationError.style.display = 'none';

        if (password !== passwordConfirm) {
            validationError.textContent = '❌ As senhas não coincidem.';
            validationError.style.display = 'block';
            return;
        }

        if (password.length < 8) {
            validationError.textContent = '❌ A senha deve ter no mínimo 8 caracteres.';
            validationError.style.display = 'block';
            return;
        }

        // Validar força da senha (recomendação)
        const hasUpperCase = /[A-Z]/.test(password);
        const hasLowerCase = /[a-z]/.test(password);
        const hasNumbers = /\d/.test(password);
        const hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password);

        if (!hasUpperCase || !hasLowerCase || !hasNumbers || !hasSpecialChar) {
            if (!confirm('A senha não atende aos critérios de segurança recomendados (maiúsculas, minúsculas, números e caracteres especiais). Deseja continuar mesmo assim?')) {
                return;
            }
        }

        // Enviar formulário
        consoleOutput.innerHTML = '';
        seedOutput.style.display = 'block';
        loading.classList.add('active');

        const formData = new FormData(form);

        fetch('/install/run-seeder', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            loading.classList.remove('active');

            // Adicionar detalhes ao console
            if (data.details && data.details.length > 0) {
                data.details.forEach(detail => {
                    const div = document.createElement('div');
                    div.textContent = detail;
                    consoleOutput.appendChild(div);
                });
            }

            // Adicionar mensagem final
            const finalMsg = document.createElement('div');
            finalMsg.style.marginTop = '15px';
            finalMsg.style.fontWeight = 'bold';
            finalMsg.style.fontSize = '16px';
            finalMsg.textContent = data.message;

            if (data.success) {
                finalMsg.style.color = '#10b981';

                // Ocultar formulário
                form.style.display = 'none';

                // Mostrar botão de continuar
                continueBtn.style.display = 'inline-block';

                // Adicionar informações de login
                const loginInfo = document.createElement('div');
                loginInfo.className = 'alert alert-success';
                loginInfo.style.marginTop = '20px';
                loginInfo.innerHTML = `
                    <strong>✅ Instalação Concluída!</strong><br><br>
                    <strong>Credenciais de Acesso:</strong><br>
                    E-mail: ${document.getElementById('admin_email').value}<br>
                    Senha: (a que você definiu)<br><br>
                    Guarde essas informações em local seguro!
                `;

                consoleOutput.appendChild(loginInfo);
            } else {
                finalMsg.style.color = '#ef4444';
            }

            consoleOutput.appendChild(finalMsg);
            consoleOutput.scrollTop = consoleOutput.scrollHeight;
        })
        .catch(error => {
            loading.classList.remove('active');

            const div = document.createElement('div');
            div.style.color = '#ef4444';
            div.style.fontWeight = 'bold';
            div.textContent = '❌ Erro na requisição: ' + error.message;
            consoleOutput.appendChild(div);
        });
    });
});
</script>
<?= $this->endSection() ?>
