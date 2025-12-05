<?php
$title = 'Configurações';
$breadcrumbs = [
    ['label' => 'Configurações', 'url' => '']
];
?>

<?= $this->extend('layouts/modern') ?>

<?= $this->section('content') ?>

<!-- Settings Overview -->
<div class="card mb-4">
    <div class="card-body">
        <h2 style="margin: 0 0 8px 0; font-size: var(--font-size-2xl);">
            <i class="fas fa-cog"></i> Configurações do Sistema
        </h2>
        <p style="margin: 0; color: var(--text-muted);">
            Gerencie todas as configurações do sistema de ponto eletrônico
        </p>
    </div>
</div>

<!-- Settings Categories -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--spacing-lg);">

    <!-- Appearance Settings -->
    <a href="<?= base_url('admin/settings/appearance') ?>" class="card" style="text-decoration: none; color: inherit; transition: all var(--transition-base);">
        <div class="card-body">
            <div style="display: flex; gap: var(--spacing-lg); align-items: start;">
                <div style="width: 60px; height: 60px; border-radius: var(--radius-lg); background-color: var(--color-primary-light); color: var(--color-primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                    <i class="fas fa-palette"></i>
                </div>
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 8px 0; font-size: var(--font-size-lg); font-weight: 600;">
                        Aparência
                    </h3>
                    <p style="margin: 0; color: var(--text-muted); font-size: var(--font-size-sm);">
                        Customize cores, logo, favicon, fontes e tema visual do sistema
                    </p>
                    <div style="margin-top: var(--spacing-md);">
                        <span class="badge badge-primary"><?= $stats['appearance'] ?? 0 ?> configurações</span>
                    </div>
                </div>
            </div>
        </div>
    </a>

    <!-- Authentication Settings -->
    <a href="<?= base_url('admin/settings/authentication') ?>" class="card" style="text-decoration: none; color: inherit;">
        <div class="card-body">
            <div style="display: flex; gap: var(--spacing-lg); align-items: start;">
                <div style="width: 60px; height: 60px; border-radius: var(--radius-lg); background-color: var(--color-success-light); color: var(--color-success); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                    <i class="fas fa-shield-halved"></i>
                </div>
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 8px 0; font-size: var(--font-size-lg); font-weight: 600;">
                        Autenticação
                    </h3>
                    <p style="margin: 0; color: var(--text-muted); font-size: var(--font-size-sm);">
                        Configure login, autenticação de dois fatores, sessão e redefinição de senha
                    </p>
                    <div style="margin-top: var(--spacing-md);">
                        <span class="badge badge-success"><?= $stats['authentication'] ?? 0 ?> configurações</span>
                    </div>
                </div>
            </div>
        </div>
    </a>

    <!-- Certificate Settings -->
    <a href="<?= base_url('admin/settings/certificate') ?>" class="card" style="text-decoration: none; color: inherit;">
        <div class="card-body">
            <div style="display: flex; gap: var(--spacing-lg); align-items: start;">
                <div style="width: 60px; height: 60px; border-radius: var(--radius-lg); background-color: var(--color-warning-light); color: var(--color-warning); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                    <i class="fas fa-certificate"></i>
                </div>
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 8px 0; font-size: var(--font-size-lg); font-weight: 600;">
                        Certificado Digital
                    </h3>
                    <p style="margin: 0; color: var(--text-muted); font-size: var(--font-size-sm);">
                        Gerencie certificados A1 e A3 para assinatura digital de registros
                    </p>
                    <div style="margin-top: var(--spacing-md);">
                        <span class="badge badge-warning"><?= $stats['certificate'] ?? 0 ?> configurações</span>
                    </div>
                </div>
            </div>
        </div>
    </a>

    <!-- System Settings -->
    <a href="<?= base_url('admin/settings/system') ?>" class="card" style="text-decoration: none; color: inherit;">
        <div class="card-body">
            <div style="display: flex; gap: var(--spacing-lg); align-items: start;">
                <div style="width: 60px; height: 60px; border-radius: var(--radius-lg); background-color: var(--color-info-light); color: var(--color-info); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                    <i class="fas fa-server"></i>
                </div>
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 8px 0; font-size: var(--font-size-lg); font-weight: 600;">
                        Sistema
                    </h3>
                    <p style="margin: 0; color: var(--text-muted); font-size: var(--font-size-sm);">
                        Informações da empresa, CNPJ, fuso horário, idioma e integrações
                    </p>
                    <div style="margin-top: var(--spacing-md);">
                        <span class="badge badge-info"><?= $stats['system'] ?? 0 ?> configurações</span>
                    </div>
                </div>
            </div>
        </div>
    </a>

    <!-- Security Settings -->
    <a href="<?= base_url('admin/settings/security') ?>" class="card" style="text-decoration: none; color: inherit;">
        <div class="card-body">
            <div style="display: flex; gap: var(--spacing-lg); align-items: start;">
                <div style="width: 60px; height: 60px; border-radius: var(--radius-lg); background-color: var(--color-danger-light); color: var(--color-danger); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0;">
                    <i class="fas fa-lock"></i>
                </div>
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 8px 0; font-size: var(--font-size-lg); font-weight: 600;">
                        Segurança
                    </h3>
                    <p style="margin: 0; color: var(--text-muted); font-size: var(--font-size-sm);">
                        Políticas de senha, logs de auditoria, backup e permissões
                    </p>
                    <div style="margin-top: var(--spacing-md);">
                        <span class="badge badge-danger"><?= $stats['security'] ?? 0 ?> configurações</span>
                    </div>
                </div>
            </div>
        </div>
    </a>

</div>

<!-- Quick Actions -->
<div class="card" style="margin-top: var(--spacing-xl);">
    <div class="card-header">
        <h3 class="card-title">Ações Rápidas</h3>
    </div>
    <div class="card-body">
        <div style="display: flex; flex-wrap: wrap; gap: var(--spacing-md);">

            <button type="button" class="btn btn-outline-primary" onclick="clearCache()">
                <i class="fas fa-sync"></i>
                Limpar Cache
            </button>

            <a href="<?= base_url('admin/settings/export') ?>" class="btn btn-outline-primary">
                <i class="fas fa-download"></i>
                Exportar Configurações
            </a>

            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('importFile').click()">
                <i class="fas fa-upload"></i>
                Importar Configurações
            </button>

            <button type="button" class="btn btn-outline-primary" onclick="testDatabase()">
                <i class="fas fa-database"></i>
                Testar Banco de Dados
            </button>

            <button type="button" class="btn btn-outline-primary" onclick="systemInfo()">
                <i class="fas fa-info-circle"></i>
                Informações do Sistema
            </button>

        </div>

        <form id="importForm" action="<?= base_url('admin/settings/import') ?>" method="POST" enctype="multipart/form-data" style="display: none;">
            <?= csrf_field() ?>
            <input type="file" id="importFile" name="settings_file" accept=".json" onchange="this.form.submit()">
        </form>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
function clearCache() {
    if (!confirm('Deseja limpar o cache do sistema?')) return;

    fetch('<?= base_url('admin/settings/clear-cache') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message || 'Erro ao limpar cache', 'error');
        }
    })
    .catch(error => {
        showNotification('Erro ao limpar cache', 'error');
        console.error(error);
    });
}

function testDatabase() {
    showLoading();

    fetch('<?= base_url('admin/settings/test-database') ?>', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification(data.message + ' - Banco: ' + data.database, 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showNotification('Erro ao testar conexão', 'error');
        console.error(error);
    });
}

function systemInfo() {
    fetch('<?= base_url('admin/settings/system-info') ?>', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let info = data.info;
            let message = `
PHP: ${info.php_version}
CodeIgniter: ${info.codeigniter_version}
Server: ${info.server_software}
Upload Max: ${info.max_upload_size}
Memory Limit: ${info.memory_limit}
Timezone: ${info.timezone}
Environment: ${info.environment}
            `;
            alert(message);
        } else {
            showNotification('Erro ao obter informações', 'error');
        }
    })
    .catch(error => {
        showNotification('Erro ao obter informações', 'error');
        console.error(error);
    });
}
</script>
<?= $this->endSection() ?>
