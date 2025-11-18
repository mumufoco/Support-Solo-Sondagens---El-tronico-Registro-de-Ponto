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
    <div class="step active">
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

<h2 style="font-size: 22px; margin-bottom: 25px; color: #333;">📦 Criação da Estrutura do Banco</h2>

<div class="alert alert-info">
    <strong>ℹ️ O que será feito:</strong><br>
    Serão criadas todas as tabelas necessárias para o funcionamento do sistema:<br>
    • Funcionários (employees)<br>
    • Registros de ponto (timesheets)<br>
    • Tokens de autenticação (remember_tokens)<br>
    • Logs de auditoria (audit_logs)<br>
    • Templates biométricos (biometric_templates)<br>
    • E outras tabelas do sistema
</div>

<div class="card">
    <div class="card-title">Executar Migrations</div>
    <p style="margin-bottom: 20px; color: #666;">
        Clique no botão abaixo para criar a estrutura de tabelas no banco de dados.
    </p>
    <button type="button" id="run-migrations-btn" class="btn btn-primary">
        ▶ Executar Migrations
    </button>
</div>

<!-- Console de Output -->
<div id="migrations-output" style="display: none;">
    <div class="console-output" id="console-output"></div>
</div>

<!-- Loading -->
<div class="loading" id="loading">
    <div class="spinner"></div>
    <p>Criando estrutura do banco de dados...</p>
</div>

<div class="button-group">
    <a href="/install/database" class="btn btn-secondary">← Voltar</a>
    <a href="/install/seed" id="continue-btn" class="btn btn-primary" style="display: none;">
        Próximo: Dados Iniciais →
    </a>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const runBtn = document.getElementById('run-migrations-btn');
    const continueBtn = document.getElementById('continue-btn');
    const migrationsOutput = document.getElementById('migrations-output');
    const consoleOutput = document.getElementById('console-output');
    const loading = document.getElementById('loading');

    runBtn.addEventListener('click', function() {
        consoleOutput.innerHTML = '';
        migrationsOutput.style.display = 'block';
        loading.classList.add('active');
        runBtn.disabled = true;

        // Fazer requisição AJAX
        fetch('/install/run-migrations', {
            method: 'POST'
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
                runBtn.textContent = '✓ Migrations Executadas com Sucesso';
                runBtn.style.background = '#10b981';
                continueBtn.style.display = 'inline-block';
            } else {
                finalMsg.style.color = '#ef4444';
                runBtn.disabled = false;
                runBtn.textContent = '↻ Tentar Novamente';
            }

            consoleOutput.appendChild(finalMsg);
            consoleOutput.scrollTop = consoleOutput.scrollHeight;
        })
        .catch(error => {
            loading.classList.remove('active');
            runBtn.disabled = false;

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
