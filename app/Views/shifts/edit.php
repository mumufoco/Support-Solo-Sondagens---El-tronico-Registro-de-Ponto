<?= $this->extend('layouts/modern') ?>

<?= $this->section('title') ?>Editar Turno<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
use App\Libraries\UI\ComponentBuilder;
use App\Libraries\UI\UIHelper;
?>

<!-- Page Header -->
<div style="margin-bottom: var(--spacing-xl);">
    <?= ComponentBuilder::card([
        'content' => UIHelper::flex([
            '<div>
                <h2 style="margin: 0 0 8px 0; font-size: var(--font-size-2xl); color: var(--text-primary);">
                    <i class="fas fa-edit me-2"></i>Editar Turno: ' . esc($shift->name) . '
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="' . base_url('dashboard') . '">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="' . base_url('shifts') . '">Turnos</a></li>
                        <li class="breadcrumb-item"><a href="' . base_url('shifts/' . $shift->id) . '">' . esc($shift->name) . '</a></li>
                        <li class="breadcrumb-item active">Editar</li>
                    </ol>
                </nav>
            </div>',
            ComponentBuilder::button([
                'text' => 'Voltar',
                'icon' => 'fa-arrow-left',
                'url' => base_url('shifts/' . $shift->id),
                'style' => 'outline-secondary',
            ])
        ], 'between', 'center')
    ]) ?>
</div>

<!-- Form Card -->
<?= ComponentBuilder::card([
    'title' => 'Informações do Turno',
    'icon' => 'fa-info-circle',
    'content' => '
        <form method="POST" action="' . base_url('shifts/' . $shift->id . '/update') . '" id="shiftForm">

            <div class="row">
                <!-- Nome -->
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">
                        Nome do Turno <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="name" name="name"
                        value="' . old('name', $shift->name) . '"
                        placeholder="Ex: Manhã Comercial, Plantão Noturno..."
                        required>
                    ' . (isset($errors['name']) ? '<div class="invalid-feedback d-block">' . $errors['name'] . '</div>' : '') . '
                </div>

                <!-- Tipo -->
                <div class="col-md-6 mb-3">
                    <label for="type" class="form-label">
                        Tipo de Turno <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="type" name="type" required>
                        <option value="">Selecione...</option>
                        ' . implode('', array_map(function($key, $label) use ($shift) {
                            $selected = old('type', $shift->type) === $key ? 'selected' : '';
                            return '<option value="' . $key . '" ' . $selected . '>' . $label . '</option>';
                        }, array_keys($shiftTypes), array_values($shiftTypes))) . '
                    </select>
                    ' . (isset($errors['type']) ? '<div class="invalid-feedback d-block">' . $errors['type'] . '</div>' : '') . '
                </div>
            </div>

            <div class="row">
                <!-- Horário Início -->
                <div class="col-md-4 mb-3">
                    <label for="start_time" class="form-label">
                        Horário de Início <span class="text-danger">*</span>
                    </label>
                    <input type="time" class="form-control" id="start_time" name="start_time"
                        value="' . old('start_time', substr($shift->start_time, 0, 5)) . '"
                        required>
                    ' . (isset($errors['start_time']) ? '<div class="invalid-feedback d-block">' . $errors['start_time'] . '</div>' : '') . '
                </div>

                <!-- Horário Fim -->
                <div class="col-md-4 mb-3">
                    <label for="end_time" class="form-label">
                        Horário de Término <span class="text-danger">*</span>
                    </label>
                    <input type="time" class="form-control" id="end_time" name="end_time"
                        value="' . old('end_time', substr($shift->end_time, 0, 5)) . '"
                        required>
                    ' . (isset($errors['end_time']) ? '<div class="invalid-feedback d-block">' . $errors['end_time'] . '</div>' : '') . '
                    <small class="text-muted">Se o turno termina no dia seguinte, será calculado automaticamente</small>
                </div>

                <!-- Intervalo -->
                <div class="col-md-4 mb-3">
                    <label for="break_duration" class="form-label">
                        Duração do Intervalo (minutos)
                    </label>
                    <input type="number" class="form-control" id="break_duration" name="break_duration"
                        value="' . old('break_duration', $shift->break_duration) . '"
                        min="0" max="480" step="15"
                        placeholder="0">
                    ' . (isset($errors['break_duration']) ? '<div class="invalid-feedback d-block">' . $errors['break_duration'] . '</div>' : '') . '
                    <small class="text-muted">0 = sem intervalo</small>
                </div>
            </div>

            <div class="row">
                <!-- Cor -->
                <div class="col-md-4 mb-3">
                    <label for="color" class="form-label">
                        Cor para Calendário
                    </label>
                    <div class="input-group">
                        <input type="color" class="form-control form-control-color" id="color" name="color"
                            value="' . old('color', $shift->color ?? '#6C757D') . '"
                            title="Escolha uma cor">
                        <input type="text" class="form-control" id="color_hex" readonly
                            value="' . old('color', $shift->color ?? '#6C757D') . '">
                    </div>
                    ' . (isset($errors['color']) ? '<div class="invalid-feedback d-block">' . $errors['color'] . '</div>' : '') . '
                    <small class="text-muted">Cor usada para identificar este turno no calendário</small>
                </div>

                <!-- Status -->
                <div class="col-md-4 mb-3">
                    <label for="active" class="form-label">Status</label>
                    <div class="form-check form-switch" style="padding-top: 8px;">
                        <input class="form-check-input" type="checkbox" id="active" name="active" value="1"
                            ' . (old('active', $shift->active) ? 'checked' : '') . '>
                        <label class="form-check-label" for="active">
                            <strong>Turno Ativo</strong>
                        </label>
                    </div>
                    <small class="text-muted">Apenas turnos ativos podem ser atribuídos a funcionários</small>
                </div>

                <!-- Duração Calculada -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Duração Total Estimada</label>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-clock me-2"></i>
                        <span id="duration_display">Calculando...</span>
                    </div>
                </div>
            </div>

            <!-- Descrição -->
            <div class="mb-3">
                <label for="description" class="form-label">Descrição</label>
                <textarea class="form-control" id="description" name="description" rows="3"
                    placeholder="Descrição opcional do turno...">' . old('description', $shift->description) . '</textarea>
                ' . (isset($errors['description']) ? '<div class="invalid-feedback d-block">' . $errors['description'] . '</div>' : '') . '
            </div>

            <!-- Actions -->
            <div class="mt-4 pt-3 border-top" style="display: flex; gap: var(--spacing-sm); justify-content: flex-end;">
                <a href="' . base_url('shifts/' . $shift->id) . '" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-2"></i>Cancelar
                </a>
                ' . ComponentBuilder::button([
                    'text' => 'Salvar Alterações',
                    'icon' => 'fa-save',
                    'style' => 'primary',
                    'type' => 'submit'
                ]) . '
            </div>

        </form>

        <script>
        // Update hex display when color changes
        document.getElementById("color").addEventListener("input", function() {
            document.getElementById("color_hex").value = this.value.toUpperCase();
        });

        // Calculate duration when times change
        document.getElementById("start_time").addEventListener("change", calculateDuration);
        document.getElementById("end_time").addEventListener("change", calculateDuration);
        document.getElementById("break_duration").addEventListener("input", calculateDuration);

        function calculateDuration() {
            const start = document.getElementById("start_time").value;
            const end = document.getElementById("end_time").value;
            const breakMinutes = parseInt(document.getElementById("break_duration").value) || 0;

            if (!start || !end) {
                document.getElementById("duration_display").textContent = "Preencha os horários";
                return;
            }

            // Convert to minutes
            const [startH, startM] = start.split(":").map(Number);
            const [endH, endM] = end.split(":").map(Number);

            let startMinutes = startH * 60 + startM;
            let endMinutes = endH * 60 + endM;

            // Handle overnight shifts
            if (endMinutes < startMinutes) {
                endMinutes += 24 * 60; // Add 24 hours
            }

            let totalMinutes = endMinutes - startMinutes - breakMinutes;
            const hours = Math.floor(totalMinutes / 60);
            const minutes = totalMinutes % 60;

            const display = hours + "h" + (minutes > 0 ? " " + minutes + "min" : "");
            document.getElementById("duration_display").innerHTML =
                \'<strong>\' + display + \'</strong> \' +
                (endMinutes >= 24 * 60 ? \'<span class="badge bg-warning ms-2">Turno noturno</span>\' : \'\');
        }

        // Calculate on page load
        window.addEventListener("DOMContentLoaded", calculateDuration);
        </script>
    '
]) ?>

<?= $this->endSection() ?>
