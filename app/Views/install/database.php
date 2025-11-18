<?= $this->extend('install/layout') ?>

<?= $this->section('content') ?>

<div class="step-indicator">
    <div class="step completed">
        <div class="step-number">✓</div>
        <div class="step-label">Requisitos</div>
    </div>
    <div class="step active">
        <div class="step-number">2</div>
        <div class="step-label">Banco de Dados</div>
    </div>
    <div class="step">
        <div class="step-number">3</div>
        <div class="step-label">Migrations</div>
    </div>
    <div class="step">
        <div class="step-number">4</div>
        <div class="step-label">Dados Iniciais</div>
    </div>
    <div class="step">
        <div class="step-number">5</div>
        <div class="step-label">Concluir</div>
    </div>
</div>

<h2 style="font-size: 22px; margin-bottom: 25px; color: #333;">🗄️ Configuração do Banco de Dados MySQL</h2>

<?php if (session('error')): ?>
    <div class="alert alert-error">
        <?= session('error') ?>
    </div>
<?php endif; ?>

<?php if (session('success')): ?>
    <div class="alert alert-success">
        <?= session('success') ?>
    </div>
<?php endif; ?>

<div class="alert alert-info">
    <strong>ℹ️ Importante:</strong><br>
    As credenciais do banco de dados serão testadas antes de prosseguir.<br>
    Se o banco de dados não existir, tentaremos criá-lo automaticamente.
</div>

<form id="database-form">
    <div class="card">
        <div class="card-title">Configurações de Conexão</div>

        <div class="form-group">
            <label for="db_host">Host do MySQL *</label>
            <input type="text" id="db_host" name="db_host" value="localhost" required>
            <div class="help-text">Geralmente "localhost" ou "127.0.0.1"</div>
        </div>

        <div class="form-group">
            <label for="db_port">Porta</label>
            <input type="number" id="db_port" name="db_port" value="3306">
            <div class="help-text">Padrão: 3306</div>
        </div>

        <div class="form-group">
            <label for="db_database">Nome do Banco de Dados *</label>
            <input type="text" id="db_database" name="db_database" value="ponto_eletronico" required>
            <div class="help-text">Nome do banco de dados (será criado se não existir)</div>
        </div>

        <div class="form-group">
            <label for="db_username">Usuário do MySQL *</label>
            <input type="text" id="db_username" name="db_username" value="root" required>
            <div class="help-text">Usuário com permissões CREATE DATABASE</div>
        </div>

        <div class="form-group">
            <label for="db_password">Senha do MySQL</label>
            <input type="password" id="db_password" name="db_password" value="">
            <div class="help-text">Deixe em branco se não houver senha</div>
        </div>
    </div>

    <div class="card" style="background: #f8f9fa; border: 2px dashed #667eea;">
        <div style="text-align: center;">
            <h3 style="font-size: 16px; margin-bottom: 15px; color: #667eea;">
                🔍 Teste a Conexão Antes de Prosseguir
            </h3>
            <button type="button" id="test-connection-btn" class="test-connection-btn">
                Testar Conexão com MySQL
            </button>
        </div>
    </div>

    <!-- Console de Output do Teste -->
    <div id="test-output" style="display: none;">
        <div class="console-output" id="console-output"></div>
    </div>

    <!-- Loading -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
        <p>Testando conexão com o banco de dados...</p>
    </div>

    <div class="button-group">
        <a href="/install/requirements" class="btn btn-secondary">← Voltar</a>
        <button type="submit" id="continue-btn" class="btn btn-primary" disabled>
            Próximo: Executar Migrations →
        </button>
    </div>
</form>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('database-form');
    const testBtn = document.getElementById('test-connection-btn');
    const continueBtn = document.getElementById('continue-btn');
    const testOutput = document.getElementById('test-output');
    const consoleOutput = document.getElementById('console-output');
    const loading = document.getElementById('loading');

    let connectionTested = false;

    // Testar conexão
    testBtn.addEventListener('click', function() {
        // Limpar console
        consoleOutput.innerHTML = '';
        testOutput.style.display = 'block';
        loading.classList.add('active');
        testBtn.disabled = true;
        continueBtn.disabled = true;
        connectionTested = false;

        // Coletar dados do formulário
        const formData = new FormData();
        formData.append('db_host', document.getElementById('db_host').value);
        formData.append('db_port', document.getElementById('db_port').value);
        formData.append('db_database', document.getElementById('db_database').value);
        formData.append('db_username', document.getElementById('db_username').value);
        formData.append('db_password', document.getElementById('db_password').value);

        // Fazer requisição AJAX
        fetch('/install/test-database-connection', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            loading.classList.remove('active');
            testBtn.disabled = false;

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
                connectionTested = true;
                continueBtn.disabled = false;

                // Mudar texto do botão de teste
                testBtn.textContent = '✓ Conexão Testada com Sucesso';
                testBtn.style.background = '#10b981';
            } else {
                finalMsg.style.color = '#ef4444';
                connectionTested = false;
                continueBtn.disabled = true;

                testBtn.textContent = 'Testar Conexão com MySQL';
                testBtn.style.background = '#10b981';
            }

            consoleOutput.appendChild(finalMsg);
            consoleOutput.scrollTop = consoleOutput.scrollHeight;
        })
        .catch(error => {
            loading.classList.remove('active');
            testBtn.disabled = false;
            continueBtn.disabled = true;

            const div = document.createElement('div');
            div.style.color = '#ef4444';
            div.style.fontWeight = 'bold';
            div.textContent = '❌ Erro na requisição: ' + error.message;
            consoleOutput.appendChild(div);
        });
    });

    // Submeter formulário
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!connectionTested) {
            alert('Por favor, teste a conexão com o banco de dados antes de prosseguir.');
            return;
        }

        // Redirecionar para salvar configuração
        window.location.href = '/install/save-configuration';
    });

    // Resetar teste quando campos mudarem
    ['db_host', 'db_port', 'db_database', 'db_username', 'db_password'].forEach(field => {
        document.getElementById(field).addEventListener('input', function() {
            if (connectionTested) {
                connectionTested = false;
                continueBtn.disabled = true;
                testBtn.textContent = 'Testar Conexão com MySQL';
                testBtn.style.background = '#10b981';

                const warning = document.createElement('div');
                warning.className = 'alert alert-warning';
                warning.style.marginTop = '15px';
                warning.innerHTML = '<strong>⚠️ Configuração alterada</strong><br>Teste a conexão novamente antes de prosseguir.';

                // Remover avisos anteriores
                const oldWarnings = document.querySelectorAll('.alert-warning');
                oldWarnings.forEach(w => w.remove());

                form.insertBefore(warning, document.querySelector('.button-group'));
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
