<?php
$title = 'Configurações de Segurança';
$breadcrumbs = [
    ['label' => 'Configurações', 'url' => 'admin/settings'],
    ['label' => 'Segurança', 'url' => '']
];
?>

<?= $this->extend('layouts/modern') ?>

<?= $this->section('content') ?>

<form action="<?= base_url('admin/settings/security/update') ?>" method="POST">
    <?= csrf_field() ?>

    <!-- Password Policy -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-key"></i> Política de Senhas
            </h3>
        </div>
        <div class="card-body">

            <!-- Password Min Length -->
            <div class="form-group">
                <label for="password_min_length" class="form-label">
                    Tamanho Mínimo <span class="label-required">*</span>
                </label>
                <input type="number" class="form-control" id="password_min_length" name="password_min_length"
                       value="<?= esc($settings['password_min_length'] ?? 8) ?>"
                       min="6" max="128" required style="max-width: 200px;">
                <small class="form-help">Mínimo de caracteres necessários (recomendado: 8+)</small>
            </div>

            <!-- Password Requirements -->
            <div class="form-group">
                <label class="form-label">Requisitos Obrigatórios</label>

                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="password_require_uppercase" name="password_require_uppercase"
                           value="1" <?= ($settings['password_require_uppercase'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="password_require_uppercase">
                        Pelo menos uma letra maiúscula (A-Z)
                    </label>
                </div>

                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="password_require_lowercase" name="password_require_lowercase"
                           value="1" <?= ($settings['password_require_lowercase'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="password_require_lowercase">
                        Pelo menos uma letra minúscula (a-z)
                    </label>
                </div>

                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="password_require_numbers" name="password_require_numbers"
                           value="1" <?= ($settings['password_require_numbers'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="password_require_numbers">
                        Pelo menos um número (0-9)
                    </label>
                </div>

                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="password_require_special" name="password_require_special"
                           value="1" <?= ($settings['password_require_special'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="password_require_special">
                        Pelo menos um caractere especial (!@#$%^&*)
                    </label>
                </div>
            </div>

            <!-- Password Expiry -->
            <div class="form-group">
                <label for="password_expiry_days" class="form-label">Expiração de Senha</label>
                <div style="display: flex; gap: var(--spacing-sm); align-items: center;">
                    <input type="number" class="form-control" id="password_expiry_days" name="password_expiry_days"
                           value="<?= esc($settings['password_expiry_days'] ?? 0) ?>"
                           min="0" max="365" style="max-width: 200px;">
                    <span>dias</span>
                </div>
                <small class="form-help">Forçar troca de senha após X dias (0 = nunca expira)</small>
            </div>

            <!-- Test Password -->
            <div class="form-group">
                <label for="test_password" class="form-label">Testar Política de Senha</label>
                <div style="display: flex; gap: var(--spacing-sm);">
                    <input type="password" class="form-control" id="test_password" placeholder="Digite uma senha para testar" style="max-width: 300px;">
                    <button type="button" class="btn btn-outline-primary" onclick="testPassword()">
                        <i class="fas fa-vial"></i> Testar
                    </button>
                </div>
                <div id="passwordTestResult" style="margin-top: var(--spacing-sm);"></div>
            </div>

        </div>
    </div>

    <!-- Audit Logs -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-list-alt"></i> Logs de Auditoria
            </h3>
        </div>
        <div class="card-body">

            <!-- Enable Audit Log -->
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="enable_audit_log" name="enable_audit_log"
                           value="1" <?= ($settings['enable_audit_log'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="enable_audit_log">
                        <strong>Habilitar logs de auditoria</strong>
                    </label>
                </div>
                <small class="form-help" style="display: block; margin-top: 8px;">
                    Registra todas as ações importantes dos usuários (login, alterações, exclusões, etc.)
                </small>
            </div>

            <!-- Audit Log Retention -->
            <div class="form-group">
                <label for="audit_log_retention_days" class="form-label">
                    Retenção de Logs <span class="label-required">*</span>
                </label>
                <div style="display: flex; gap: var(--spacing-sm); align-items: center;">
                    <input type="number" class="form-control" id="audit_log_retention_days" name="audit_log_retention_days"
                           value="<?= esc($settings['audit_log_retention_days'] ?? 90) ?>"
                           min="1" max="3650" required style="max-width: 200px;">
                    <span>dias</span>
                </div>
                <small class="form-help">Tempo que os logs são mantidos antes de serem automaticamente removidos</small>
            </div>

            <!-- Log Actions -->
            <div class="form-group">
                <label class="form-label">Ações a Registrar</label>

                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="log_logins" name="log_logins"
                           value="1" <?= ($settings['log_logins'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="log_logins">
                        Logins e logouts
                    </label>
                </div>

                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="log_data_changes" name="log_data_changes"
                           value="1" <?= ($settings['log_data_changes'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="log_data_changes">
                        Alterações de dados
                    </label>
                </div>

                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="log_deletions" name="log_deletions"
                           value="1" <?= ($settings['log_deletions'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="log_deletions">
                        Exclusões de registros
                    </label>
                </div>

                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="log_settings_changes" name="log_settings_changes"
                           value="1" <?= ($settings['log_settings_changes'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="log_settings_changes">
                        Alterações de configurações
                    </label>
                </div>
            </div>

            <!-- View Logs -->
            <div class="form-group">
                <button type="button" class="btn btn-outline-primary" onclick="viewAuditLogs()">
                    <i class="fas fa-eye"></i> Visualizar Logs Recentes
                </button>
            </div>

            <div id="auditLogsContainer" style="display: none; margin-top: var(--spacing-lg);"></div>

        </div>
    </div>

    <!-- Backup Settings -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-database"></i> Backup Automático
            </h3>
        </div>
        <div class="card-body">

            <!-- Enable Auto Backup -->
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="enable_auto_backup" name="enable_auto_backup"
                           value="1" <?= ($settings['enable_auto_backup'] ?? 0) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="enable_auto_backup">
                        <strong>Habilitar backup automático</strong>
                    </label>
                </div>
                <small class="form-help" style="display: block; margin-top: 8px;">
                    Cria backups automáticos do banco de dados
                </small>
            </div>

            <!-- Backup Frequency -->
            <div class="form-group" id="backup_options" style="<?= ($settings['enable_auto_backup'] ?? 0) ? '' : 'display: none;' ?>">
                <label for="backup_frequency" class="form-label">Frequência</label>
                <select class="form-control" id="backup_frequency" name="backup_frequency" style="max-width: 250px;">
                    <option value="daily" <?= ($settings['backup_frequency'] ?? 'daily') === 'daily' ? 'selected' : '' ?>>
                        Diariamente
                    </option>
                    <option value="weekly" <?= ($settings['backup_frequency'] ?? '') === 'weekly' ? 'selected' : '' ?>>
                        Semanalmente
                    </option>
                    <option value="monthly" <?= ($settings['backup_frequency'] ?? '') === 'monthly' ? 'selected' : '' ?>>
                        Mensalmente
                    </option>
                </select>
            </div>

            <!-- Backup Retention -->
            <div class="form-group" id="backup_retention_group" style="<?= ($settings['enable_auto_backup'] ?? 0) ? '' : 'display: none;' ?>">
                <label for="backup_retention_days" class="form-label">Retenção de Backups</label>
                <div style="display: flex; gap: var(--spacing-sm); align-items: center;">
                    <input type="number" class="form-control" id="backup_retention_days" name="backup_retention_days"
                           value="<?= esc($settings['backup_retention_days'] ?? 30) ?>"
                           min="1" max="365" style="max-width: 200px;">
                    <span>dias</span>
                </div>
                <small class="form-help">Tempo que os backups são mantidos (recomendado: 30 dias)</small>
            </div>

            <!-- Create Backup Now -->
            <div class="form-group">
                <button type="button" class="btn btn-outline-success" onclick="createBackup()">
                    <i class="fas fa-save"></i> Criar Backup Agora
                </button>
                <small class="form-help" style="display: block; margin-top: 8px;">
                    Cria um backup manual do banco de dados imediatamente
                </small>
            </div>

        </div>
    </div>

    <!-- Session Security -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-user-shield"></i> Segurança Adicional
            </h3>
        </div>
        <div class="card-body">

            <!-- Force HTTPS -->
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="force_https" name="force_https"
                           value="1" <?= ($settings['force_https'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="force_https">
                        Forçar conexão HTTPS
                    </label>
                </div>
                <small class="form-help" style="display: block; margin-top: 8px;">
                    Redireciona automaticamente conexões HTTP para HTTPS
                </small>
            </div>

            <!-- Session Regenerate -->
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="regenerate_session_id" name="regenerate_session_id"
                           value="1" <?= ($settings['regenerate_session_id'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="regenerate_session_id">
                        Regenerar ID de sessão após login
                    </label>
                </div>
                <small class="form-help" style="display: block; margin-top: 8px;">
                    Previne ataques de fixação de sessão
                </small>
            </div>

            <!-- CSRF Protection -->
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="enable_csrf" name="enable_csrf"
                           value="1" <?= ($settings['enable_csrf'] ?? 1) ? 'checked' : '' ?> disabled>
                    <label class="form-check-label" for="enable_csrf">
                        Proteção CSRF (Cross-Site Request Forgery)
                    </label>
                </div>
                <small class="form-help" style="display: block; margin-top: 8px;">
                    <strong>Obrigatório:</strong> Esta proteção não pode ser desativada
                </small>
            </div>

            <!-- XSS Protection -->
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="enable_xss_filter" name="enable_xss_filter"
                           value="1" <?= ($settings['enable_xss_filter'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="enable_xss_filter">
                        Filtro XSS (Cross-Site Scripting)
                    </label>
                </div>
                <small class="form-help" style="display: block; margin-top: 8px;">
                    Protege contra ataques de injeção de scripts
                </small>
            </div>

        </div>
    </div>

    <!-- Data Privacy -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-user-lock"></i> Privacidade de Dados (LGPD)
            </h3>
        </div>
        <div class="card-body">

            <!-- Data Anonymization -->
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="enable_data_anonymization" name="enable_data_anonymization"
                           value="1" <?= ($settings['enable_data_anonymization'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="enable_data_anonymization">
                        Anonimizar dados de funcionários inativos
                    </label>
                </div>
                <small class="form-help" style="display: block; margin-top: 8px;">
                    Remove dados pessoais de funcionários inativos após período definido
                </small>
            </div>

            <!-- Anonymization Period -->
            <div class="form-group">
                <label for="anonymization_period_days" class="form-label">Período de Anonimização</label>
                <div style="display: flex; gap: var(--spacing-sm); align-items: center;">
                    <input type="number" class="form-control" id="anonymization_period_days" name="anonymization_period_days"
                           value="<?= esc($settings['anonymization_period_days'] ?? 365) ?>"
                           min="30" max="3650" style="max-width: 200px;">
                    <span>dias após inativação</span>
                </div>
            </div>

            <!-- Data Export -->
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="allow_data_export" name="allow_data_export"
                           value="1" <?= ($settings['allow_data_export'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="allow_data_export">
                        Permitir que funcionários exportem seus dados
                    </label>
                </div>
                <small class="form-help" style="display: block; margin-top: 8px;">
                    Direito de portabilidade dos dados (Art. 18 LGPD)
                </small>
            </div>

        </div>
    </div>

    <!-- Action Buttons -->
    <div style="display: flex; gap: var(--spacing-md); justify-content: space-between;">
        <button type="button" class="btn btn-outline-danger" onclick="resetToDefaults()">
            <i class="fas fa-undo"></i> Restaurar Padrão
        </button>
        <div style="display: flex; gap: var(--spacing-sm);">
            <a href="<?= base_url('admin/settings') ?>" class="btn btn-outline-primary">
                <i class="fas fa-times"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Salvar Alterações
            </button>
        </div>
    </div>

</form>

<?= $this->endSection() ?>

<?= $this->section('js') ?>
<script>
// Toggle backup options
document.getElementById('enable_auto_backup').addEventListener('change', function() {
    const display = this.checked ? 'block' : 'none';
    document.getElementById('backup_options').style.display = display;
    document.getElementById('backup_retention_group').style.display = display;
});

// Test password
function testPassword() {
    const password = document.getElementById('test_password').value;
    const resultDiv = document.getElementById('passwordTestResult');

    if (!password) {
        showNotification('Digite uma senha para testar', 'warning');
        return;
    }

    resultDiv.innerHTML = '<div class="spinner spinner-sm"></div>';

    fetch('<?= base_url('admin/settings/security/test-password') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'password=' + encodeURIComponent(password)
    })
    .then(response => response.json())
    .then(data => {
        const strength = data.strength;
        const progressBar = `
            <div style="background: var(--bg-page); border-radius: var(--radius-full); height: 8px; overflow: hidden; margin-bottom: 8px;">
                <div style="width: ${strength.score}%; height: 100%; background: var(--color-${strength.color}); transition: width 0.3s;"></div>
            </div>
        `;

        if (data.success) {
            resultDiv.innerHTML = `
                <div style="padding: var(--spacing-sm); background: var(--color-success-light); border-radius: var(--radius-md); border-left: 4px solid var(--color-success);">
                    <strong>✓ Senha válida!</strong><br>
                    ${progressBar}
                    <small>Força: <strong>${strength.level}</strong> (${strength.score}%)</small>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div style="padding: var(--spacing-sm); background: var(--color-danger-light); border-radius: var(--radius-md); border-left: 4px solid var(--color-danger);">
                    <strong>✗ Senha não atende os requisitos:</strong><br>
                    <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                        ${data.errors.map(err => '<li>' + err + '</li>').join('')}
                    </ul>
                    ${progressBar}
                    <small>Força: <strong>${strength.level}</strong> (${strength.score}%)</small>
                </div>
            `;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `
            <div style="padding: var(--spacing-sm); background: var(--color-danger-light); border-radius: var(--radius-md); border-left: 4px solid var(--color-danger);">
                <strong>Erro ao testar senha</strong>
            </div>
        `;
        console.error(error);
    });
}

// View audit logs
function viewAuditLogs() {
    const container = document.getElementById('auditLogsContainer');
    container.style.display = 'block';
    container.innerHTML = '<div style="text-align: center; padding: var(--spacing-lg);"><div class="spinner"></div></div>';

    fetch('<?= base_url('admin/settings/security/audit-logs') ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.logs.length > 0) {
                container.innerHTML = `
                    <div class="table-responsive">
                        <table class="table table-modern">
                            <thead>
                                <tr>
                                    <th>Usuário</th>
                                    <th>Ação</th>
                                    <th>IP</th>
                                    <th>Data/Hora</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.logs.map(log => `
                                    <tr>
                                        <td>${log.user}</td>
                                        <td>${log.action}</td>
                                        <td><code>${log.ip}</code></td>
                                        <td>${log.timestamp}</td>
                                        <td>
                                            <span class="badge badge-${log.status === 'success' ? 'success' : 'danger'}">
                                                ${log.status}
                                            </span>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    <p style="text-align: center; margin-top: var(--spacing-md); color: var(--text-muted);">
                        Mostrando ${data.total} logs mais recentes
                    </p>
                `;
            } else {
                container.innerHTML = `
                    <div style="text-align: center; padding: var(--spacing-lg); color: var(--text-muted);">
                        <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: var(--spacing-md);"></i>
                        <p>Nenhum log de auditoria encontrado</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            container.innerHTML = `
                <div style="padding: var(--spacing-md); background: var(--color-danger-light); border-radius: var(--radius-md);">
                    <strong>Erro ao carregar logs</strong>
                </div>
            `;
            console.error(error);
        });
}

// Create backup
function createBackup() {
    if (!confirm('Deseja criar um backup do banco de dados agora?')) return;

    showLoading();

    fetch('<?= base_url('admin/settings/security/backup') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification('Backup criado: ' + data.file + ' (' + data.size + ')', 'success', 8000);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showNotification('Erro ao criar backup', 'error');
        console.error(error);
    });
}

// Reset to defaults
function resetToDefaults() {
    if (!confirm('Deseja restaurar todas as configurações de segurança para o padrão? Esta ação não pode ser desfeita.')) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= base_url('admin/settings/security/reset') ?>';
    form.innerHTML = '<?= csrf_field() ?>';
    document.body.appendChild(form);
    form.submit();
}
</script>
<?= $this->endSection() ?>
