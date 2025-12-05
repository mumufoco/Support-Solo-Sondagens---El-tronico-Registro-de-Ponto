<?php
$title = 'Configurações de Autenticação';
$breadcrumbs = [
    ['label' => 'Configurações', 'url' => 'admin/settings'],
    ['label' => 'Autenticação', 'url' => '']
];
?>

<?= $this->extend('layouts/modern') ?>

<?= $this->section('content') ?>

<form action="<?= base_url('admin/settings/authentication/update') ?>" method="POST">
    <?= csrf_field() ?>

    <!-- Session Settings -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-clock"></i> Configurações de Sessão
            </h3>
        </div>
        <div class="card-body">

            <!-- Session Timeout -->
            <div class="form-group">
                <label for="session_timeout" class="form-label">
                    Tempo de Sessão <span class="label-required">*</span>
                </label>
                <div style="display: flex; gap: var(--spacing-sm); align-items: center;">
                    <input type="number" class="form-control" id="session_timeout" name="session_timeout"
                           value="<?= esc($settings['session_timeout'] ?? 3600) ?>"
                           min="300" max="86400" required style="max-width: 200px;">
                    <span>segundos</span>
                    <span style="color: var(--text-muted); font-size: var(--font-size-sm);">
                        (<?= gmdate('H:i:s', $settings['session_timeout'] ?? 3600) ?>)
                    </span>
                </div>
                <small class="form-help">Tempo em segundos antes da sessão expirar por inatividade (mín: 5min, máx: 24h)</small>
            </div>

            <!-- Remember Me -->
            <div class="form-group">
                <label class="form-label">Lembrar-me</label>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="enable_remember_me" name="enable_remember_me"
                           value="1" <?= ($settings['enable_remember_me'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="enable_remember_me">
                        Permitir que usuários marquem "Lembrar-me" no login
                    </label>
                </div>
            </div>

            <!-- Remember Me Duration -->
            <div class="form-group" id="remember_me_duration_group">
                <label for="remember_me_duration" class="form-label">Duração do "Lembrar-me"</label>
                <div style="display: flex; gap: var(--spacing-sm); align-items: center;">
                    <input type="number" class="form-control" id="remember_me_duration" name="remember_me_duration"
                           value="<?= esc($settings['remember_me_duration'] ?? 2592000) ?>"
                           min="86400" max="31536000" style="max-width: 200px;">
                    <span>segundos</span>
                    <span style="color: var(--text-muted); font-size: var(--font-size-sm);">
                        (<?= round(($settings['remember_me_duration'] ?? 2592000) / 86400) ?> dias)
                    </span>
                </div>
                <small class="form-help">Tempo que o usuário permanece logado (padrão: 30 dias)</small>
            </div>

        </div>
    </div>

    <!-- Login Security -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-shield-alt"></i> Segurança de Login
            </h3>
        </div>
        <div class="card-body">

            <!-- Max Login Attempts -->
            <div class="form-group">
                <label for="max_login_attempts" class="form-label">
                    Máximo de Tentativas de Login <span class="label-required">*</span>
                </label>
                <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts"
                       value="<?= esc($settings['max_login_attempts'] ?? 5) ?>"
                       min="1" max="20" required style="max-width: 200px;">
                <small class="form-help">Número de tentativas de login falhadas antes de bloquear a conta</small>
            </div>

            <!-- Lockout Duration -->
            <div class="form-group">
                <label for="lockout_duration" class="form-label">
                    Duração do Bloqueio <span class="label-required">*</span>
                </label>
                <div style="display: flex; gap: var(--spacing-sm); align-items: center;">
                    <input type="number" class="form-control" id="lockout_duration" name="lockout_duration"
                           value="<?= esc($settings['lockout_duration'] ?? 900) ?>"
                           min="60" max="86400" required style="max-width: 200px;">
                    <span>segundos</span>
                    <span style="color: var(--text-muted); font-size: var(--font-size-sm);">
                        (<?= round(($settings['lockout_duration'] ?? 900) / 60) ?> minutos)
                    </span>
                </div>
                <small class="form-help">Tempo que a conta fica bloqueada após exceder tentativas (padrão: 15min)</small>
            </div>

            <!-- Clear Locked Accounts -->
            <div class="form-group">
                <button type="button" class="btn btn-outline-warning" onclick="clearLockedAccounts()">
                    <i class="fas fa-unlock"></i> Desbloquear Todas as Contas
                </button>
                <small class="form-help" style="display: block; margin-top: 8px;">
                    Remove o bloqueio de todas as contas temporariamente bloqueadas
                </small>
            </div>

        </div>
    </div>

    <!-- Two-Factor Authentication -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-mobile-alt"></i> Autenticação de Dois Fatores (2FA)
            </h3>
        </div>
        <div class="card-body">

            <!-- Enable 2FA -->
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="enable_2fa" name="enable_2fa"
                           value="1" <?= ($settings['enable_2fa'] ?? 0) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="enable_2fa">
                        <strong>Habilitar autenticação de dois fatores</strong>
                    </label>
                </div>
                <small class="form-help" style="display: block; margin-top: 8px;">
                    Exige que usuários configurem um segundo fator de autenticação (Google Authenticator, Authy, etc.)
                </small>
            </div>

            <!-- 2FA Options -->
            <div id="twofa_options" style="<?= ($settings['enable_2fa'] ?? 0) ? '' : 'display: none;' ?>">

                <!-- Force 2FA for Admins -->
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="force_2fa_admins" name="force_2fa_admins"
                               value="1" <?= ($settings['force_2fa_admins'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="force_2fa_admins">
                            Obrigatório para administradores
                        </label>
                    </div>
                </div>

                <!-- Force 2FA for All -->
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="force_2fa_all" name="force_2fa_all"
                               value="1" <?= ($settings['force_2fa_all'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="force_2fa_all">
                            Obrigatório para todos os usuários
                        </label>
                    </div>
                </div>

                <!-- Test 2FA -->
                <div class="form-group">
                    <button type="button" class="btn btn-outline-primary" onclick="test2FA()">
                        <i class="fas fa-vial"></i> Testar Configuração 2FA
                    </button>
                </div>

            </div>

        </div>
    </div>

    <!-- Email Notifications -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-envelope"></i> Notificações por Email
            </h3>
        </div>
        <div class="card-body">

            <!-- Email Verification -->
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="enable_email_verification" name="enable_email_verification"
                           value="1" <?= ($settings['enable_email_verification'] ?? 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="enable_email_verification">
                        Exigir verificação de email no cadastro
                    </label>
                </div>
            </div>

            <!-- Login Notifications -->
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="enable_login_notifications" name="enable_login_notifications"
                           value="1" <?= ($settings['enable_login_notifications'] ?? 0) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="enable_login_notifications">
                        Enviar email quando houver novo login
                    </label>
                </div>
                <small class="form-help" style="display: block; margin-top: 8px;">
                    Notifica o usuário por email sempre que houver um login em sua conta
                </small>
            </div>

            <!-- Test Email -->
            <div class="form-group">
                <label for="test_email" class="form-label">Testar Envio de Email</label>
                <div style="display: flex; gap: var(--spacing-sm);">
                    <input type="email" class="form-control" id="test_email" placeholder="seu@email.com" style="max-width: 300px;">
                    <button type="button" class="btn btn-outline-primary" onclick="testEmail()">
                        <i class="fas fa-paper-plane"></i> Enviar Email de Teste
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- Password Reset -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-key"></i> Redefinição de Senha
            </h3>
        </div>
        <div class="card-body">

            <!-- Password Reset Expiry -->
            <div class="form-group">
                <label for="password_reset_expiry" class="form-label">
                    Validade do Link de Redefinição <span class="label-required">*</span>
                </label>
                <div style="display: flex; gap: var(--spacing-sm); align-items: center;">
                    <input type="number" class="form-control" id="password_reset_expiry" name="password_reset_expiry"
                           value="<?= esc($settings['password_reset_expiry'] ?? 3600) ?>"
                           min="300" max="86400" required style="max-width: 200px;">
                    <span>segundos</span>
                    <span style="color: var(--text-muted); font-size: var(--font-size-sm);">
                        (<?= round(($settings['password_reset_expiry'] ?? 3600) / 60) ?> minutos)
                    </span>
                </div>
                <small class="form-help">Tempo que o link de redefinição de senha permanece válido</small>
            </div>

        </div>
    </div>

    <!-- IP Restrictions -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-network-wired"></i> Restrições de IP
            </h3>
        </div>
        <div class="card-body">

            <!-- Allowed IPs -->
            <div class="form-group">
                <label for="allowed_ip_addresses" class="form-label">Endereços IP Permitidos</label>
                <textarea class="form-control" id="allowed_ip_addresses" name="allowed_ip_addresses"
                          rows="4" placeholder="192.168.1.1&#10;10.0.0.0/24&#10;Deixe vazio para permitir todos"><?= esc($settings['allowed_ip_addresses'] ?? '') ?></textarea>
                <small class="form-help">
                    Um IP por linha. Suporta CIDR (ex: 192.168.1.0/24). Deixe vazio para permitir qualquer IP.
                </small>
            </div>

            <div style="padding: var(--spacing-md); background: var(--color-warning-light); border-radius: var(--radius-md); border-left: 4px solid var(--color-warning);">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Atenção:</strong> Ao restringir IPs, certifique-se de incluir seu IP atual para não perder acesso!
            </div>

        </div>
    </div>

    <!-- Login Statistics -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-chart-line"></i> Estatísticas de Login
            </h3>
        </div>
        <div class="card-body">
            <div id="loginStatsContainer">
                <div style="text-align: center; padding: var(--spacing-xl);">
                    <div class="spinner"></div>
                    <p style="margin-top: var(--spacing-md); color: var(--text-muted);">Carregando estatísticas...</p>
                </div>
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
// Toggle 2FA options
document.getElementById('enable_2fa').addEventListener('change', function() {
    document.getElementById('twofa_options').style.display = this.checked ? 'block' : 'none';
});

// Toggle Remember Me duration
document.getElementById('enable_remember_me').addEventListener('change', function() {
    document.getElementById('remember_me_duration_group').style.display = this.checked ? 'block' : 'none';
});

// Load login statistics
loadLoginStats();

function loadLoginStats() {
    fetch('<?= base_url('admin/settings/authentication/login-stats') ?>')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.stats;
                document.getElementById('loginStatsContainer').innerHTML = `
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: var(--spacing-lg);">
                        <div style="text-align: center;">
                            <div style="font-size: var(--font-size-3xl); font-weight: 700; color: var(--color-primary);">
                                ${stats.total_logins_today}
                            </div>
                            <div style="color: var(--text-muted); font-size: var(--font-size-sm);">Logins Hoje</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: var(--font-size-3xl); font-weight: 700; color: var(--color-danger);">
                                ${stats.failed_attempts_today}
                            </div>
                            <div style="color: var(--text-muted); font-size: var(--font-size-sm);">Falhas Hoje</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: var(--font-size-3xl); font-weight: 700; color: var(--color-warning);">
                                ${stats.locked_accounts}
                            </div>
                            <div style="color: var(--text-muted); font-size: var(--font-size-sm);">Contas Bloqueadas</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: var(--font-size-3xl); font-weight: 700; color: var(--color-success);">
                                ${stats.active_sessions}
                            </div>
                            <div style="color: var(--text-muted); font-size: var(--font-size-sm);">Sessões Ativas</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: var(--font-size-xl); font-weight: 700; color: var(--color-info);">
                                ${stats.average_session_duration}
                            </div>
                            <div style="color: var(--text-muted); font-size: var(--font-size-sm);">Duração Média</div>
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading stats:', error);
        });
}

function test2FA() {
    showLoading();

    fetch('<?= base_url('admin/settings/authentication/test-2fa') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showNotification('Erro ao testar 2FA', 'error');
        console.error(error);
    });
}

function clearLockedAccounts() {
    if (!confirm('Deseja desbloquear todas as contas bloqueadas?')) return;

    fetch('<?= base_url('admin/settings/authentication/clear-locked') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message + ' (' + data.unlocked_count + ' contas)', 'success');
            loadLoginStats(); // Refresh stats
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Erro ao desbloquear contas', 'error');
        console.error(error);
    });
}

function testEmail() {
    const email = document.getElementById('test_email').value;

    if (!email) {
        showNotification('Digite um email', 'warning');
        return;
    }

    showLoading();

    fetch('<?= base_url('admin/settings/authentication/test-email') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'email=' + encodeURIComponent(email)
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        hideLoading();
        showNotification('Erro ao enviar email', 'error');
        console.error(error);
    });
}

function resetToDefaults() {
    if (!confirm('Deseja restaurar todas as configurações de autenticação para o padrão? Esta ação não pode ser desfeita.')) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= base_url('admin/settings/authentication/reset') ?>';
    form.innerHTML = '<?= csrf_field() ?>';
    document.body.appendChild(form);
    form.submit();
}
</script>
<?= $this->endSection() ?>
