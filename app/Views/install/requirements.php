<?= $this->extend('install/layout') ?>

<?= $this->section('content') ?>

<div class="step-indicator">
    <div class="step active">
        <div class="step-number">1</div>
        <div class="step-label">Requisitos</div>
    </div>
    <div class="step">
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

<h2 style="font-size: 22px; margin-bottom: 25px; color: #333;">🔍 Verificação de Requisitos</h2>

<!-- PHP Version -->
<div class="card">
    <div class="card-title">Versão do PHP</div>
    <div class="requirement-item">
        <div>
            <strong><?= $requirements['php_version']['name'] ?></strong>
            <div class="help-text">Requerido: <?= $requirements['php_version']['required'] ?> ou superior</div>
        </div>
        <div>
            <?php if ($requirements['php_version']['status']): ?>
                <span class="status-badge status-ok">✓ <?= $requirements['php_version']['current'] ?></span>
            <?php else: ?>
                <span class="status-badge status-error">✗ <?= $requirements['php_version']['current'] ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- PHP Extensions -->
<div class="card">
    <div class="card-title">Extensões do PHP</div>
    <?php foreach ($requirements['extensions'] as $ext => $info): ?>
        <div class="requirement-item">
            <div>
                <strong><?= $info['name'] ?></strong>
                <div class="help-text">Extensão: <?= $ext ?></div>
            </div>
            <div>
                <?php if ($info['status']): ?>
                    <span class="status-badge status-ok">✓ Instalada</span>
                <?php else: ?>
                    <span class="status-badge status-error">✗ Não Instalada</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- File Permissions -->
<div class="card">
    <div class="card-title">Permissões de Arquivos</div>
    <?php foreach ($requirements['writable'] as $key => $info): ?>
        <div class="requirement-item">
            <div>
                <strong><?= $info['name'] ?></strong>
            </div>
            <div>
                <?php if ($info['status']): ?>
                    <span class="status-badge status-ok">✓ Gravável</span>
                <?php else: ?>
                    <span class="status-badge status-error">✗ Sem Permissão</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if (!$canProceed): ?>
    <div class="alert alert-error">
        <strong>❌ Não é possível continuar</strong><br>
        Corrija os requisitos marcados com ✗ antes de prosseguir.<br><br>
        <strong>Como corrigir:</strong><br>
        • Extensões PHP: sudo apt-get install php-[extensão]<br>
        • Permissões: sudo chmod -R 755 writable/ && sudo chmod 777 writable/
    </div>
<?php else: ?>
    <div class="alert alert-success">
        <strong>✅ Todos os requisitos foram atendidos!</strong><br>
        Você pode prosseguir para a próxima etapa.
    </div>
<?php endif; ?>

<div class="button-group">
    <a href="/install" class="btn btn-secondary">← Voltar</a>
    <?php if ($canProceed): ?>
        <a href="/install/database" class="btn btn-primary">Próximo: Configurar Banco →</a>
    <?php else: ?>
        <button class="btn btn-primary" disabled>Próximo: Configurar Banco →</button>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
