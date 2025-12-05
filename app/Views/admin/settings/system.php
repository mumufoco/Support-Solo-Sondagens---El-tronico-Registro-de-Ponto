<?php
$title = 'Configurações do Sistema';
$breadcrumbs = [
    ['label' => 'Configurações', 'url' => 'admin/settings'],
    ['label' => 'Sistema', 'url' => '']
];
?>

<?= $this->extend('layouts/modern') ?>

<?= $this->section('content') ?>

<form action="<?= base_url('admin/settings/system/update') ?>" method="POST">
    <?= csrf_field() ?>

    <!-- Company Information -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-building"></i> Informações da Empresa
            </h3>
        </div>
        <div class="card-body">

            <!-- Company CNPJ -->
            <div class="form-group">
                <label for="company_cnpj" class="form-label">CNPJ</label>
                <input type="text" class="form-control" id="company_cnpj" name="company_cnpj"
                       value="<?= esc($settings['company_cnpj'] ?? '') ?>"
                       placeholder="00.000.000/0000-00" maxlength="18">
                <small class="form-help">Formato: 00.000.000/0000-00</small>
            </div>

            <!-- Company Address -->
            <div class="form-group">
                <label for="company_address" class="form-label">Endereço Completo</label>
                <textarea class="form-control" id="company_address" name="company_address"
                          rows="3" placeholder="Rua, número, complemento, bairro, cidade - UF, CEP"><?= esc($settings['company_address'] ?? '') ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-md);">

                <!-- Company Phone -->
                <div class="form-group">
                    <label for="company_phone" class="form-label">Telefone</label>
                    <input type="text" class="form-control" id="company_phone" name="company_phone"
                           value="<?= esc($settings['company_phone'] ?? '') ?>"
                           placeholder="(00) 0000-0000">
                </div>

                <!-- Company Email -->
                <div class="form-group">
                    <label for="company_email" class="form-label">Email Corporativo</label>
                    <input type="email" class="form-control" id="company_email" name="company_email"
                           value="<?= esc($settings['company_email'] ?? '') ?>"
                           placeholder="contato@empresa.com.br">
                </div>

            </div>

        </div>
    </div>

    <!-- Regional Settings -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-globe"></i> Configurações Regionais
            </h3>
        </div>
        <div class="card-body">

            <!-- Timezone -->
            <div class="form-group">
                <label for="timezone" class="form-label">
                    Fuso Horário <span class="label-required">*</span>
                </label>
                <select class="form-control" id="timezone" name="timezone" required>
                    <optgroup label="Brasil">
                        <?php
                        $brazilTimezones = [
                            'America/Sao_Paulo' => 'São Paulo (GMT-3)',
                            'America/Rio_Branco' => 'Acre (GMT-5)',
                            'America/Manaus' => 'Amazonas (GMT-4)',
                            'America/Belem' => 'Belém (GMT-3)',
                            'America/Fortaleza' => 'Fortaleza (GMT-3)',
                            'America/Recife' => 'Recife (GMT-3)',
                            'America/Bahia' => 'Bahia (GMT-3)',
                            'America/Cuiaba' => 'Cuiabá (GMT-4)',
                            'America/Campo_Grande' => 'Campo Grande (GMT-4)',
                            'America/Noronha' => 'Fernando de Noronha (GMT-2)',
                        ];
                        foreach ($brazilTimezones as $tz => $label):
                        ?>
                            <option value="<?= $tz ?>" <?= ($settings['timezone'] ?? 'America/Sao_Paulo') === $tz ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Outros">
                        <?php foreach ($timezones as $tz): ?>
                            <?php if (!isset($brazilTimezones[$tz])): ?>
                                <option value="<?= $tz ?>" <?= ($settings['timezone'] ?? '') === $tz ? 'selected' : '' ?>>
                                    <?= $tz ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
                <small class="form-help">Fuso horário utilizado para registros de ponto e relatórios</small>
            </div>

            <!-- Test Timezone -->
            <div class="form-group">
                <button type="button" class="btn btn-outline-primary" onclick="testTimezone()">
                    <i class="fas fa-vial"></i> Testar Fuso Horário
                </button>
                <div id="timezoneResult" style="margin-top: var(--spacing-sm);"></div>
            </div>

            <!-- Language -->
            <div class="form-group">
                <label for="language" class="form-label">
                    Idioma <span class="label-required">*</span>
                </label>
                <select class="form-control" id="language" name="language" required>
                    <?php foreach ($languages as $code => $name): ?>
                        <option value="<?= $code ?>" <?= ($settings['language'] ?? 'pt-BR') === $code ? 'selected' : '' ?>>
                            <?= $name ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="form-help">Idioma da interface do sistema</small>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-md);">

                <!-- Date Format -->
                <div class="form-group">
                    <label for="date_format" class="form-label">
                        Formato de Data <span class="label-required">*</span>
                    </label>
                    <select class="form-control" id="date_format" name="date_format" required>
                        <option value="d/m/Y" <?= ($settings['date_format'] ?? 'd/m/Y') === 'd/m/Y' ? 'selected' : '' ?>>
                            DD/MM/AAAA (<?= date('d/m/Y') ?>)
                        </option>
                        <option value="m/d/Y" <?= ($settings['date_format'] ?? '') === 'm/d/Y' ? 'selected' : '' ?>>
                            MM/DD/AAAA (<?= date('m/d/Y') ?>)
                        </option>
                        <option value="Y-m-d" <?= ($settings['date_format'] ?? '') === 'Y-m-d' ? 'selected' : '' ?>>
                            AAAA-MM-DD (<?= date('Y-m-d') ?>)
                        </option>
                    </select>
                </div>

                <!-- Time Format -->
                <div class="form-group">
                    <label for="time_format" class="form-label">
                        Formato de Hora <span class="label-required">*</span>
                    </label>
                    <select class="form-control" id="time_format" name="time_format" required>
                        <option value="H:i" <?= ($settings['time_format'] ?? 'H:i') === 'H:i' ? 'selected' : '' ?>>
                            24 horas (<?= date('H:i') ?>)
                        </option>
                        <option value="h:i A" <?= ($settings['time_format'] ?? '') === 'h:i A' ? 'selected' : '' ?>>
                            12 horas (<?= date('h:i A') ?>)
                        </option>
                    </select>
                </div>

            </div>

        </div>
    </div>

    <!-- Work Schedule -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-calendar-alt"></i> Jornada de Trabalho Padrão
            </h3>
        </div>
        <div class="card-body">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-md);">

                <!-- Work Hours Per Day -->
                <div class="form-group">
                    <label for="work_hours_per_day" class="form-label">Horas por Dia</label>
                    <input type="number" class="form-control" id="work_hours_per_day" name="work_hours_per_day"
                           value="<?= esc($settings['work_hours_per_day'] ?? 8) ?>"
                           min="1" max="24" step="0.5">
                    <small class="form-help">Horas de trabalho por dia</small>
                </div>

                <!-- Work Days Per Week -->
                <div class="form-group">
                    <label for="work_days_per_week" class="form-label">Dias por Semana</label>
                    <input type="number" class="form-control" id="work_days_per_week" name="work_days_per_week"
                           value="<?= esc($settings['work_days_per_week'] ?? 5) ?>"
                           min="1" max="7">
                    <small class="form-help">Dias de trabalho por semana</small>
                </div>

                <!-- Lunch Break Duration -->
                <div class="form-group">
                    <label for="lunch_break_duration" class="form-label">Intervalo de Almoço</label>
                    <input type="number" class="form-control" id="lunch_break_duration" name="lunch_break_duration"
                           value="<?= esc($settings['lunch_break_duration'] ?? 60) ?>"
                           min="0" max="180">
                    <small class="form-help">Minutos</small>
                </div>

            </div>

            <!-- Week Start Day -->
            <div class="form-group">
                <label for="week_start_day" class="form-label">Início da Semana</label>
                <select class="form-control" id="week_start_day" name="week_start_day" style="max-width: 250px;">
                    <option value="0" <?= ($settings['week_start_day'] ?? 0) == 0 ? 'selected' : '' ?>>Domingo</option>
                    <option value="1" <?= ($settings['week_start_day'] ?? 0) == 1 ? 'selected' : '' ?>>Segunda-feira</option>
                    <option value="6" <?= ($settings['week_start_day'] ?? 0) == 6 ? 'selected' : '' ?>>Sábado</option>
                </select>
            </div>

        </div>
    </div>

    <!-- External Integrations -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-plug"></i> Integrações Externas
            </h3>
        </div>
        <div class="card-body">

            <!-- Enable API -->
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="enable_api" name="enable_api"
                           value="1" <?= ($settings['enable_api'] ?? 0) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="enable_api">
                        <strong>Habilitar API REST</strong>
                    </label>
                </div>
                <small class="form-help" style="display: block; margin-top: 8px;">
                    Permite acesso externo via API para integrações
                </small>
            </div>

            <!-- API Key -->
            <div class="form-group" id="api_key_group" style="<?= ($settings['enable_api'] ?? 0) ? '' : 'display: none;' ?>">
                <label for="api_key" class="form-label">Chave da API</label>
                <div style="display: flex; gap: var(--spacing-sm);">
                    <input type="text" class="form-control" id="api_key" name="api_key"
                           value="<?= esc($settings['api_key'] ?? '') ?>"
                           readonly style="flex: 1;">
                    <button type="button" class="btn btn-outline-primary" onclick="generateApiKey()">
                        <i class="fas fa-sync"></i> Gerar Nova
                    </button>
                </div>
                <small class="form-help">Mantenha esta chave segura. Use-a no header: Authorization: Bearer {key}</small>
            </div>

            <!-- Webhook URL -->
            <div class="form-group">
                <label for="webhook_url" class="form-label">URL do Webhook</label>
                <input type="url" class="form-control" id="webhook_url" name="webhook_url"
                       value="<?= esc($settings['webhook_url'] ?? '') ?>"
                       placeholder="https://api.exemplo.com/webhook">
                <small class="form-help">URL para receber notificações de eventos do sistema</small>
            </div>

        </div>
    </div>

    <!-- Maintenance Mode -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-wrench"></i> Modo de Manutenção
            </h3>
        </div>
        <div class="card-body">

            <!-- Enable Maintenance -->
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="maintenance_mode" name="maintenance_mode"
                           value="1" <?= ($settings['maintenance_mode'] ?? 0) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="maintenance_mode">
                        <strong>Ativar modo de manutenção</strong>
                    </label>
                </div>
                <small class="form-help" style="display: block; margin-top: 8px;">
                    Bloqueia acesso ao sistema para todos exceto administradores
                </small>
            </div>

            <!-- Maintenance Message -->
            <div class="form-group" id="maintenance_message_group" style="<?= ($settings['maintenance_mode'] ?? 0) ? '' : 'display: none;' ?>">
                <label for="maintenance_message" class="form-label">Mensagem de Manutenção</label>
                <textarea class="form-control" id="maintenance_message" name="maintenance_message"
                          rows="3"><?= esc($settings['maintenance_message'] ?? 'Sistema em manutenção. Voltaremos em breve.') ?></textarea>
            </div>

            <?php if ($settings['maintenance_mode'] ?? 0): ?>
            <div style="padding: var(--spacing-md); background: var(--color-warning-light); border-radius: var(--radius-md); border-left: 4px solid var(--color-warning);">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Atenção:</strong> O modo de manutenção está ATIVO. Usuários não conseguem acessar o sistema.
            </div>
            <?php endif; ?>

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
// CNPJ mask
document.getElementById('company_cnpj').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 14) {
        value = value.replace(/^(\d{2})(\d)/, '$1.$2');
        value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
        value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
        value = value.replace(/(\d{4})(\d)/, '$1-$2');
    }
    e.target.value = value;
});

// Phone mask
document.getElementById('company_phone').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length <= 11) {
        value = value.replace(/^(\d{2})(\d)/, '($1) $2');
        value = value.replace(/(\d{4,5})(\d{4})$/, '$1-$2');
    }
    e.target.value = value;
});

// Toggle API key field
document.getElementById('enable_api').addEventListener('change', function() {
    document.getElementById('api_key_group').style.display = this.checked ? 'block' : 'none';
});

// Toggle maintenance message
document.getElementById('maintenance_mode').addEventListener('change', function() {
    document.getElementById('maintenance_message_group').style.display = this.checked ? 'block' : 'none';
});

// Test timezone
function testTimezone() {
    const timezone = document.getElementById('timezone').value;
    const resultDiv = document.getElementById('timezoneResult');

    resultDiv.innerHTML = '<div class="spinner spinner-sm"></div>';

    fetch('<?= base_url('admin/settings/system/test-timezone') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'timezone=' + encodeURIComponent(timezone)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = `
                <div style="padding: var(--spacing-sm); background: var(--color-success-light); border-radius: var(--radius-md); border-left: 4px solid var(--color-success);">
                    <strong>Fuso válido!</strong><br>
                    <small>
                        Hora atual: ${data.info.current_time}<br>
                        Offset: GMT${data.info.offset}<br>
                        Horário de verão: ${data.info.is_dst ? 'Sim' : 'Não'}
                    </small>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div style="padding: var(--spacing-sm); background: var(--color-danger-light); border-radius: var(--radius-md); border-left: 4px solid var(--color-danger);">
                    <strong>Erro:</strong> ${data.message}
                </div>
            `;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `
            <div style="padding: var(--spacing-sm); background: var(--color-danger-light); border-radius: var(--radius-md); border-left: 4px solid var(--color-danger);">
                <strong>Erro ao testar fuso horário</strong>
            </div>
        `;
        console.error(error);
    });
}

// Generate API key
function generateApiKey() {
    const key = 'sk_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
    document.getElementById('api_key').value = key;
    showNotification('Nova chave API gerada. Lembre-se de salvar!', 'info');
}

// Reset to defaults
function resetToDefaults() {
    if (!confirm('Deseja restaurar todas as configurações do sistema para o padrão? Esta ação não pode ser desfeita.')) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= base_url('admin/settings/system/reset') ?>';
    form.innerHTML = '<?= csrf_field() ?>';
    document.body.appendChild(form);
    form.submit();
}
</script>
<?= $this->endSection() ?>
