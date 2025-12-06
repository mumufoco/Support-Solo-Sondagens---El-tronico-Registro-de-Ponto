<?= $this->extend('layouts/modern') ?>

<?= $this->section('title') ?>Calendário de Escalas<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
use App\Libraries\UI\ComponentBuilder;
use App\Libraries\UI\UIHelper;

$monthNames = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

$monthName = $monthNames[(int)$month];
$prevMonth = $month == 1 ? 12 : $month - 1;
$nextMonth = $month == 12 ? 1 : $month + 1;
$prevYear = $month == 1 ? $year - 1 : $year;
$nextYear = $month == 12 ? $year + 1 : $year;
?>

<style>
.calendar-day {
    min-height: 120px;
    border: 1px solid var(--border-color, #dee2e6);
    padding: 8px;
    background: var(--bg-surface, #fff);
    position: relative;
    transition: all 0.2s;
}

.calendar-day:hover {
    background: var(--bg-hover, #f8f9fa);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.calendar-day-header {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 8px;
    color: var(--text-primary, #212529);
}

.calendar-day.today {
    background: var(--color-primary-light, #e7f1ff);
    border-color: var(--color-primary, #0d6efd);
}

.calendar-day.other-month {
    background: var(--bg-muted, #f8f9fa);
    opacity: 0.5;
}

.calendar-day.weekend {
    background: var(--bg-weekend, #fffaf0);
}

.shift-badge {
    display: block;
    padding: 4px 8px;
    margin-bottom: 4px;
    border-radius: 4px;
    font-size: 11px;
    color: white;
    text-decoration: none;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    transition: transform 0.2s;
}

.shift-badge:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    color: white;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 0;
}

.legend-color {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    flex-shrink: 0;
}
</style>

<!-- Page Header -->
<div style="margin-bottom: var(--spacing-xl);">
    <?= ComponentBuilder::card([
        'content' => UIHelper::flex([
            '<div>
                <h2 style="margin: 0 0 8px 0; font-size: var(--font-size-2xl); color: var(--text-primary);">
                    <i class="fas fa-calendar-alt me-2"></i>Calendário de Escalas
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="' . base_url('dashboard') . '">Dashboard</a></li>
                        <li class="breadcrumb-item active">Escalas</li>
                    </ol>
                </nav>
            </div>',
            '<div style="display: flex; gap: var(--spacing-sm);">
                ' . ComponentBuilder::button([
                    'text' => 'Atribuição em Massa',
                    'icon' => 'fa-users-cog',
                    'url' => base_url('schedules/bulk-assign'),
                    'style' => 'outline-primary',
                ]) . '
                ' . ComponentBuilder::button([
                    'text' => 'Nova Escala',
                    'icon' => 'fa-plus',
                    'url' => base_url('schedules/create'),
                    'style' => 'primary',
                ]) . '
            </div>'
        ], 'between', 'center')
    ]) ?>
</div>

<div style="display: grid; grid-template-columns: 3fr 1fr; gap: var(--spacing-lg);">

    <!-- Left Column: Calendar -->
    <div>
        <!-- Calendar Navigation -->
        <?= ComponentBuilder::card([
            'class' => 'mb-3',
            'content' => '
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <a href="' . base_url("schedules?year={$prevYear}&month={$prevMonth}") . '" class="btn btn-outline-secondary">
                        <i class="fas fa-chevron-left"></i> ' . $monthNames[$prevMonth] . '
                    </a>

                    <h3 style="margin: 0; font-size: var(--font-size-xl);">
                        ' . $monthName . ' ' . $year . '
                    </h3>

                    <a href="' . base_url("schedules?year={$nextYear}&month={$nextMonth}") . '" class="btn btn-outline-secondary">
                        ' . $monthNames[$nextMonth] . ' <i class="fas fa-chevron-right"></i>
                    </a>
                </div>

                <div class="mt-3 text-center">
                    <a href="' . base_url('schedules?year=' . date('Y') . '&month=' . date('m')) . '" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-calendar-day"></i> Hoje
                    </a>
                    <a href="' . base_url('schedules/export?start=' . $startDate . '&end=' . $endDate) . '" class="btn btn-sm btn-outline-success ms-2">
                        <i class="fas fa-download"></i> Exportar CSV
                    </a>
                </div>
            '
        ]) ?>

        <!-- Calendar Grid -->
        <?= ComponentBuilder::card([
            'content' => '
                <!-- Days of week header -->
                <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; margin-bottom: 2px;">
                    <div style="text-align: center; font-weight: 600; padding: 8px; background: var(--bg-surface); color: var(--text-muted);">DOM</div>
                    <div style="text-align: center; font-weight: 600; padding: 8px; background: var(--bg-surface); color: var(--text-muted);">SEG</div>
                    <div style="text-align: center; font-weight: 600; padding: 8px; background: var(--bg-surface); color: var(--text-muted);">TER</div>
                    <div style="text-align: center; font-weight: 600; padding: 8px; background: var(--bg-surface); color: var(--text-muted);">QUA</div>
                    <div style="text-align: center; font-weight: 600; padding: 8px; background: var(--bg-surface); color: var(--text-muted);">QUI</div>
                    <div style="text-align: center; font-weight: 600; padding: 8px; background: var(--bg-surface); color: var(--text-muted);">SEX</div>
                    <div style="text-align: center; font-weight: 600; padding: 8px; background: var(--bg-surface); color: var(--text-muted);">SÁB</div>
                </div>

                <!-- Calendar days -->
                <div style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px;">
                    ' . renderCalendar($year, $month, $calendarData) . '
                </div>
            '
        ]) ?>
    </div>

    <!-- Right Column: Legend & Filters -->
    <div>
        <!-- Shift Legend -->
        <?= ComponentBuilder::card([
            'title' => 'Legenda de Turnos',
            'icon' => 'fa-palette',
            'class' => 'mb-3',
            'content' => '
                <div style="display: flex; flex-direction: column; gap: var(--spacing-xs);">
                    ' . implode('', array_map(function($shift) {
                        return '
                            <div class="legend-item">
                                <div class="legend-color" style="background: ' . ($shift->color ?? '#6C757D') . ';"></div>
                                <div style="flex: 1;">
                                    <strong>' . esc($shift->name) . '</strong><br>
                                    <small class="text-muted">' . substr($shift->start_time, 0, 5) . ' - ' . substr($shift->end_time, 0, 5) . '</small>
                                </div>
                            </div>
                        ';
                    }, $shifts)) . '
                </div>
            '
        ]) ?>

        <!-- Quick Stats -->
        <?= ComponentBuilder::card([
            'title' => 'Estatísticas do Mês',
            'icon' => 'fa-chart-pie',
            'content' => '
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Total de Escalas</span>
                        <strong>' . count($schedules) . '</strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Funcionários Diferentes</span>
                        <strong>' . count(array_unique(array_column($schedules, 'employee_id'))) . '</strong>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span class="text-muted">Turnos Utilizados</span>
                        <strong>' . count(array_unique(array_column($schedules, 'shift_id'))) . '</strong>
                    </div>
                </div>
            '
        ]) ?>
    </div>

</div>

<?php
function renderCalendar($year, $month, $calendarData) {
    $output = '';
    $firstDay = mktime(0, 0, 0, $month, 1, $year);
    $daysInMonth = date('t', $firstDay);
    $dayOfWeek = date('w', $firstDay);
    $today = date('Y-m-d');

    // Previous month days
    $prevMonth = $month == 1 ? 12 : $month - 1;
    $prevYear = $month == 1 ? $year - 1 : $year;
    $daysInPrevMonth = date('t', mktime(0, 0, 0, $prevMonth, 1, $prevYear));

    for ($i = $dayOfWeek - 1; $i >= 0; $i--) {
        $day = $daysInPrevMonth - $i;
        $output .= '<div class="calendar-day other-month"><div class="calendar-day-header">' . $day . '</div></div>';
    }

    // Current month days
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
        $isToday = $date === $today;
        $isWeekend = date('w', strtotime($date)) == 0 || date('w', strtotime($date)) == 6;

        $classes = ['calendar-day'];
        if ($isToday) $classes[] = 'today';
        if ($isWeekend) $classes[] = 'weekend';

        $output .= '<div class="' . implode(' ', $classes) . '">';
        $output .= '<div class="calendar-day-header">' . $day . '</div>';

        // Add schedules for this day
        if (isset($calendarData[$date])) {
            foreach ($calendarData[$date] as $schedule) {
                $shiftName = esc($schedule->shift_name ?? 'Turno');
                $employeeName = esc($schedule->employee_name ?? 'Funcionário');
                $color = $schedule->shift_color ?? '#6C757D';

                $output .= '<a href="' . base_url('schedules/' . $schedule->id . '/edit') . '"
                    class="shift-badge"
                    style="background: ' . $color . ';"
                    title="' . $employeeName . ' - ' . $shiftName . '">';
                $output .= substr($employeeName, 0, 15) . (strlen($employeeName) > 15 ? '...' : '');
                $output .= '</a>';
            }
        }

        // Add button
        $output .= '<a href="' . base_url('schedules/create?date=' . $date) . '"
            class="btn btn-sm btn-outline-primary w-100 mt-1"
            style="font-size: 10px; padding: 2px;"
            title="Adicionar escala">
            <i class="fas fa-plus"></i>
        </a>';

        $output .= '</div>';
    }

    // Next month days
    $remainingDays = 42 - ($daysInMonth + $dayOfWeek);
    for ($day = 1; $day <= $remainingDays; $day++) {
        $output .= '<div class="calendar-day other-month"><div class="calendar-day-header">' . $day . '</div></div>';
    }

    return $output;
}
?>

<?= $this->endSection() ?>
