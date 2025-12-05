<?php
$title = 'Configurações de Aparência';
$breadcrumbs = [
    ['label' => 'Configurações', 'url' => 'admin/settings'],
    ['label' => 'Aparência', 'url' => '']
];
?>

<?= $this->extend('layouts/modern') ?>

<?= $this->section('content') ?>

<form action="<?= base_url('admin/settings/appearance/update') ?>" method="POST" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <!-- Identity Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-building"></i> Identidade Visual
            </h3>
        </div>
        <div class="card-body">

            <!-- Company Name -->
            <div class="form-group">
                <label for="company_name" class="form-label">
                    Nome da Empresa <span class="label-required">*</span>
                </label>
                <input type="text" class="form-control" id="company_name" name="company_name"
                       value="<?= esc($currentConfig['custom']['company_name'] ?? 'Sistema de Ponto Eletrônico') ?>"
                       required>
                <small class="form-help">Nome que aparecerá no sistema e relatórios</small>
            </div>

            <!-- Logo Upload -->
            <div class="form-group">
                <label for="logo" class="form-label">Logo da Empresa</label>
                <div style="display: flex; gap: var(--spacing-md); align-items: start;">
                    <div style="flex: 1;">
                        <input type="file" class="form-control" id="logo" name="logo" accept="image/png,image/jpeg,image/svg+xml">
                        <small class="form-help">PNG, JPG ou SVG. Tamanho recomendado: 180x40px. Máximo 2MB.</small>
                    </div>
                    <div id="logoPreview" style="padding: var(--spacing-md); background: var(--bg-page); border: 1px solid var(--border-color); border-radius: var(--radius-md); min-width: 200px; text-align: center;">
                        <?php if (isset($currentConfig['custom']['logo']) && $currentConfig['custom']['logo']): ?>
                            <img src="<?= esc($currentConfig['custom']['logo']) ?>" alt="Logo" style="max-width: 180px; max-height: 60px;">
                        <?php else: ?>
                            <span style="color: var(--text-muted);">Nenhum logo</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Favicon Upload -->
            <div class="form-group">
                <label for="favicon" class="form-label">Favicon</label>
                <div style="display: flex; gap: var(--spacing-md); align-items: start;">
                    <div style="flex: 1;">
                        <input type="file" class="form-control" id="favicon" name="favicon" accept="image/x-icon,image/png">
                        <small class="form-help">ICO ou PNG. Tamanho recomendado: 32x32px.</small>
                    </div>
                    <div id="faviconPreview" style="padding: var(--spacing-md); background: var(--bg-page); border: 1px solid var(--border-color); border-radius: var(--radius-md); min-width: 80px; text-align: center;">
                        <?php if (isset($currentConfig['custom']['favicon']) && $currentConfig['custom']['favicon']): ?>
                            <img src="<?= esc($currentConfig['custom']['favicon']) ?>" alt="Favicon" style="width: 32px; height: 32px;">
                        <?php else: ?>
                            <span style="color: var(--text-muted); font-size: var(--font-size-sm);">-</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Colors Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-palette"></i> Cores do Sistema
            </h3>
        </div>
        <div class="card-body">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-lg);">

                <!-- Primary Color -->
                <div class="form-group">
                    <label for="primary_color" class="form-label">Cor Primária</label>
                    <div style="display: flex; gap: var(--spacing-sm); align-items: center;">
                        <input type="color" class="form-control" id="primary_color" name="primary_color"
                               value="<?= esc($currentConfig['colors']['primary'] ?? '#3B82F6') ?>"
                               style="width: 60px; height: 40px; padding: 2px;">
                        <input type="text" class="form-control" id="primary_color_text"
                               value="<?= esc($currentConfig['colors']['primary'] ?? '#3B82F6') ?>"
                               pattern="^#[0-9A-Fa-f]{6}$"
                               style="flex: 1;">
                    </div>
                </div>

                <!-- Secondary Color -->
                <div class="form-group">
                    <label for="secondary_color" class="form-label">Cor Secundária</label>
                    <div style="display: flex; gap: var(--spacing-sm); align-items: center;">
                        <input type="color" class="form-control" id="secondary_color" name="secondary_color"
                               value="<?= esc($currentConfig['colors']['secondary'] ?? '#8B5CF6') ?>"
                               style="width: 60px; height: 40px; padding: 2px;">
                        <input type="text" class="form-control" id="secondary_color_text"
                               value="<?= esc($currentConfig['colors']['secondary'] ?? '#8B5CF6') ?>"
                               style="flex: 1;">
                    </div>
                </div>

                <!-- Success Color -->
                <div class="form-group">
                    <label for="success_color" class="form-label">Cor de Sucesso</label>
                    <div style="display: flex; gap: var(--spacing-sm); align-items: center;">
                        <input type="color" class="form-control" id="success_color" name="success_color"
                               value="<?= esc($currentConfig['colors']['success'] ?? '#10B981') ?>"
                               style="width: 60px; height: 40px; padding: 2px;">
                        <input type="text" class="form-control" id="success_color_text"
                               value="<?= esc($currentConfig['colors']['success'] ?? '#10B981') ?>"
                               style="flex: 1;">
                    </div>
                </div>

                <!-- Warning Color -->
                <div class="form-group">
                    <label for="warning_color" class="form-label">Cor de Aviso</label>
                    <div style="display: flex; gap: var(--spacing-sm); align-items: center;">
                        <input type="color" class="form-control" id="warning_color" name="warning_color"
                               value="<?= esc($currentConfig['colors']['warning'] ?? '#F59E0B') ?>"
                               style="width: 60px; height: 40px; padding: 2px;">
                        <input type="text" class="form-control" id="warning_color_text"
                               value="<?= esc($currentConfig['colors']['warning'] ?? '#F59E0B') ?>"
                               style="flex: 1;">
                    </div>
                </div>

                <!-- Danger Color -->
                <div class="form-group">
                    <label for="danger_color" class="form-label">Cor de Perigo</label>
                    <div style="display: flex; gap: var(--spacing-sm); align-items: center;">
                        <input type="color" class="form-control" id="danger_color" name="danger_color"
                               value="<?= esc($currentConfig['colors']['danger'] ?? '#EF4444') ?>"
                               style="width: 60px; height: 40px; padding: 2px;">
                        <input type="text" class="form-control" id="danger_color_text"
                               value="<?= esc($currentConfig['colors']['danger'] ?? '#EF4444') ?>"
                               style="flex: 1;">
                    </div>
                </div>

                <!-- Info Color -->
                <div class="form-group">
                    <label for="info_color" class="form-label">Cor de Informação</label>
                    <div style="display: flex; gap: var(--spacing-sm); align-items: center;">
                        <input type="color" class="form-control" id="info_color" name="info_color"
                               value="<?= esc($currentConfig['colors']['info'] ?? '#06B6D4') ?>"
                               style="width: 60px; height: 40px; padding: 2px;">
                        <input type="text" class="form-control" id="info_color_text"
                               value="<?= esc($currentConfig['colors']['info'] ?? '#06B6D4') ?>"
                               style="flex: 1;">
                    </div>
                </div>

            </div>

            <div style="margin-top: var(--spacing-lg); padding: var(--spacing-md); background: var(--color-info-light); border-radius: var(--radius-md); border-left: 4px solid var(--color-info);">
                <i class="fas fa-lightbulb"></i>
                <strong>Dica:</strong> As cores serão aplicadas em todo o sistema. Use o botão "Pré-visualizar" para ver como ficará antes de salvar.
            </div>

        </div>
    </div>

    <!-- Typography Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-font"></i> Tipografia
            </h3>
        </div>
        <div class="card-body">

            <!-- Font Family -->
            <div class="form-group">
                <label for="font_family" class="form-label">Família de Fonte</label>
                <select class="form-control" id="font_family" name="font_family">
                    <option value="'Inter', sans-serif" <?= ($currentConfig['typography']['font_family'] ?? '') === "'Inter', sans-serif" ? 'selected' : '' ?>>Inter (Padrão)</option>
                    <option value="'Roboto', sans-serif" <?= ($currentConfig['typography']['font_family'] ?? '') === "'Roboto', sans-serif" ? 'selected' : '' ?>>Roboto</option>
                    <option value="'Open Sans', sans-serif" <?= ($currentConfig['typography']['font_family'] ?? '') === "'Open Sans', sans-serif" ? 'selected' : '' ?>>Open Sans</option>
                    <option value="'Lato', sans-serif" <?= ($currentConfig['typography']['font_family'] ?? '') === "'Lato', sans-serif" ? 'selected' : '' ?>>Lato</option>
                    <option value="'Montserrat', sans-serif" <?= ($currentConfig['typography']['font_family'] ?? '') === "'Montserrat', sans-serif" ? 'selected' : '' ?>>Montserrat</option>
                    <option value="'Poppins', sans-serif" <?= ($currentConfig['typography']['font_family'] ?? '') === "'Poppins', sans-serif" ? 'selected' : '' ?>>Poppins</option>
                </select>
                <small class="form-help">Fonte utilizada em todo o sistema</small>
            </div>

            <!-- Font Preview -->
            <div style="padding: var(--spacing-lg); background: var(--bg-page); border: 1px solid var(--border-color); border-radius: var(--radius-md);">
                <p id="fontPreview" style="font-size: var(--font-size-xl); margin: 0;">
                    The quick brown fox jumps over the lazy dog
                </p>
                <p id="fontPreviewPt" style="font-size: var(--font-size-base); margin: 8px 0 0 0; color: var(--text-muted);">
                    Às vezes, o êxito é fruto de ação, não de sorte.
                </p>
            </div>

        </div>
    </div>

    <!-- Theme Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-adjust"></i> Tema
            </h3>
        </div>
        <div class="card-body">

            <!-- Theme Mode -->
            <div class="form-group">
                <label class="form-label">Modo do Tema</label>
                <div style="display: flex; gap: var(--spacing-md);">
                    <div class="form-check">
                        <input type="radio" class="form-check-input" id="theme_light" name="theme_mode" value="light"
                               <?= ($currentConfig['custom']['theme_mode'] ?? 'light') === 'light' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="theme_light">
                            <i class="fas fa-sun"></i> Claro
                        </label>
                    </div>
                    <div class="form-check">
                        <input type="radio" class="form-check-input" id="theme_dark" name="theme_mode" value="dark"
                               <?= ($currentConfig['custom']['theme_mode'] ?? 'light') === 'dark' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="theme_dark">
                            <i class="fas fa-moon"></i> Escuro
                        </label>
                    </div>
                    <div class="form-check">
                        <input type="radio" class="form-check-input" id="theme_auto" name="theme_mode" value="auto"
                               <?= ($currentConfig['custom']['theme_mode'] ?? 'light') === 'auto' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="theme_auto">
                            <i class="fas fa-circle-half-stroke"></i> Automático
                        </label>
                    </div>
                </div>
                <small class="form-help">Modo automático detecta a preferência do sistema operacional do usuário</small>
            </div>

        </div>
    </div>

    <!-- Login Customization -->
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-image"></i> Tela de Login
            </h3>
        </div>
        <div class="card-body">

            <!-- Login Background -->
            <div class="form-group">
                <label for="login_background" class="form-label">Imagem de Fundo</label>
                <input type="file" class="form-control" id="login_background" name="login_background" accept="image/*">
                <small class="form-help">Imagem de fundo da tela de login. Recomendado: 1920x1080px</small>
            </div>

        </div>
    </div>

    <!-- Action Buttons -->
    <div style="display: flex; gap: var(--spacing-md); justify-content: space-between;">
        <div>
            <button type="button" class="btn btn-outline-primary" onclick="previewChanges()">
                <i class="fas fa-eye"></i> Pré-visualizar
            </button>
            <button type="button" class="btn btn-outline-danger" onclick="resetToDefaults()">
                <i class="fas fa-undo"></i> Restaurar Padrão
            </button>
        </div>
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
// Sync color inputs
document.querySelectorAll('input[type="color"]').forEach(colorInput => {
    const textInput = document.getElementById(colorInput.id + '_text');

    colorInput.addEventListener('input', function() {
        if (textInput) {
            textInput.value = this.value.toUpperCase();
        }
    });

    if (textInput) {
        textInput.addEventListener('input', function() {
            if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                colorInput.value = this.value;
            }
        });
    }
});

// Font preview
document.getElementById('font_family').addEventListener('change', function() {
    const preview = document.getElementById('fontPreview');
    const previewPt = document.getElementById('fontPreviewPt');
    preview.style.fontFamily = this.value;
    previewPt.style.fontFamily = this.value;
});

// Logo preview
document.getElementById('logo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('logoPreview').innerHTML =
                `<img src="${e.target.result}" alt="Logo" style="max-width: 180px; max-height: 60px;">`;
        };
        reader.readAsDataURL(file);
    }
});

// Favicon preview
document.getElementById('favicon').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('faviconPreview').innerHTML =
                `<img src="${e.target.result}" alt="Favicon" style="width: 32px; height: 32px;">`;
        };
        reader.readAsDataURL(file);
    }
});

// Preview changes
function previewChanges() {
    const colors = {
        primary_color: document.getElementById('primary_color').value,
        secondary_color: document.getElementById('secondary_color').value,
        success_color: document.getElementById('success_color').value,
        warning_color: document.getElementById('warning_color').value,
        danger_color: document.getElementById('danger_color').value,
        info_color: document.getElementById('info_color').value
    };

    const queryString = new URLSearchParams(colors).toString();

    fetch(`<?= base_url('admin/settings/appearance/preview') ?>?${queryString}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Apply CSS temporarily
                let styleTag = document.getElementById('preview-styles');
                if (!styleTag) {
                    styleTag = document.createElement('style');
                    styleTag.id = 'preview-styles';
                    document.head.appendChild(styleTag);
                }
                styleTag.textContent = data.css;

                showNotification('Pré-visualização aplicada! Recarregue a página para voltar ao normal.', 'info', 8000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Erro ao gerar pré-visualização', 'error');
        });
}

// Reset to defaults
function resetToDefaults() {
    if (!confirm('Deseja restaurar todas as configurações de aparência para o padrão? Esta ação não pode ser desfeita.')) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?= base_url('admin/settings/appearance/reset') ?>';
    form.innerHTML = '<?= csrf_field() ?>';
    document.body.appendChild(form);
    form.submit();
}
</script>
<?= $this->endSection() ?>
