<?php

namespace App\Services;

use App\Models\TimePunchModel;
use App\Models\EmployeeModel;
use App\Models\JustificationModel;
use App\Models\SettingModel;

/**
 * Timesheet Service
 *
 * Handles timesheet calculations, validations, and report generation
 */
class TimesheetService
{
    protected $timePunchModel;
    protected $employeeModel;
    protected $justificationModel;
    protected $settingModel;

    public function __construct()
    {
        $this->timePunchModel = new TimePunchModel();
        $this->employeeModel = new EmployeeModel();
        $this->justificationModel = new JustificationModel();
        $this->settingModel = new SettingModel();
    }

    /**
     * Calculate total hours worked for an employee in a period
     *
     * @param int $employeeId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function calculateHoursWorked(int $employeeId, string $startDate, string $endDate): array
    {
        // Get punches for period
        $punches = $this->timePunchModel
            ->where('employee_id', $employeeId)
            ->where('DATE(punch_time) >=', $startDate)
            ->where('DATE(punch_time) <=', $endDate)
            ->orderBy('punch_time', 'ASC')
            ->findAll();

        // Group punches by date
        $punchesByDate = [];
        foreach ($punches as $punch) {
            $date = date('Y-m-d', strtotime($punch->punch_time));
            $punchesByDate[$date][] = $punch;
        }

        // Calculate hours for each day
        $dailyHours = [];
        $totalHours = 0;
        $totalDays = 0;

        foreach ($punchesByDate as $date => $dayPunches) {
            $hours = $this->calculateDailyHours($dayPunches);
            $dailyHours[$date] = $hours;
            $totalHours += $hours['total_hours'];

            if ($hours['total_hours'] > 0) {
                $totalDays++;
            }
        }

        // Get employee expected hours
        $employee = $this->employeeModel->find($employeeId);
        $expectedDailyHours = $employee->daily_hours ?? 8.00;
        $expectedTotalHours = $expectedDailyHours * $totalDays;

        return [
            'employee_id' => $employeeId,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'total_hours' => round($totalHours, 2),
            'total_days' => $totalDays,
            'average_hours_per_day' => $totalDays > 0 ? round($totalHours / $totalDays, 2) : 0,
            'expected_hours' => round($expectedTotalHours, 2),
            'balance' => round($totalHours - $expectedTotalHours, 2),
            'daily_breakdown' => $dailyHours,
        ];
    }

    /**
     * Calculate hours for a single day
     *
     * @param array $punches
     * @return array
     */
    public function calculateDailyHours(array $punches): array
    {
        if (empty($punches)) {
            return [
                'total_hours' => 0,
                'work_hours' => 0,
                'break_hours' => 0,
                'punches' => [],
                'pairs' => [],
            ];
        }

        // Sort punches by time
        usort($punches, function ($a, $b) {
            return strtotime($a->punch_time) <=> strtotime($b->punch_time);
        });

        // Pair entrada/saida punches
        $pairs = [];
        $workHours = 0;
        $breakHours = 0;

        $currentEntrada = null;
        $currentIntervaloInicio = null;

        foreach ($punches as $punch) {
            switch ($punch->punch_type) {
                case 'entrada':
                    $currentEntrada = $punch;
                    break;

                case 'saida':
                    if ($currentEntrada) {
                        $hours = $this->calculateHoursBetween(
                            $currentEntrada->punch_time,
                            $punch->punch_time
                        );

                        $pairs[] = [
                            'type' => 'work',
                            'entrada' => $currentEntrada,
                            'saida' => $punch,
                            'hours' => $hours,
                        ];

                        $workHours += $hours;
                        $currentEntrada = null;
                    }
                    break;

                case 'intervalo_inicio':
                    $currentIntervaloInicio = $punch;
                    break;

                case 'intervalo_fim':
                    if ($currentIntervaloInicio) {
                        $hours = $this->calculateHoursBetween(
                            $currentIntervaloInicio->punch_time,
                            $punch->punch_time
                        );

                        $pairs[] = [
                            'type' => 'break',
                            'inicio' => $currentIntervaloInicio,
                            'fim' => $punch,
                            'hours' => $hours,
                        ];

                        $breakHours += $hours;
                        $currentIntervaloInicio = null;
                    }
                    break;
            }
        }

        return [
            'total_hours' => round($workHours, 2),
            'work_hours' => round($workHours, 2),
            'break_hours' => round($breakHours, 2),
            'punches' => array_map(function ($p) {
                return [
                    'time' => $p->punch_time,
                    'type' => $p->punch_type,
                    'method' => $p->method,
                ];
            }, $punches),
            'pairs' => $pairs,
        ];
    }

    /**
     * Calculate hours between two timestamps
     *
     * @param string $start
     * @param string $end
     * @return float
     */
    protected function calculateHoursBetween(string $start, string $end): float
    {
        $startTime = strtotime($start);
        $endTime = strtotime($end);

        $seconds = $endTime - $startTime;
        $hours = $seconds / 3600;

        return max(0, $hours); // Never negative
    }

    /**
     * Validate punch pairs (entrada must have saida, etc.)
     *
     * @param array $punches
     * @return array
     */
    public function validatePunchPairs(array $punches): array
    {
        $errors = [];
        $warnings = [];

        // Sort punches by time
        usort($punches, function ($a, $b) {
            return strtotime($a->punch_time) <=> strtotime($b->punch_time);
        });

        $expectedNext = 'entrada';

        foreach ($punches as $index => $punch) {
            // Check sequence
            if ($punch->punch_type === 'entrada') {
                if ($expectedNext !== 'entrada') {
                    $warnings[] = "Registro #{$punch->nsr}: Entrada registrada sem saída anterior.";
                }
                $expectedNext = 'saida';

            } elseif ($punch->punch_type === 'saida') {
                if ($expectedNext !== 'saida') {
                    $errors[] = "Registro #{$punch->nsr}: Saída sem entrada correspondente.";
                }
                $expectedNext = 'entrada';

            } elseif ($punch->punch_type === 'intervalo_inicio') {
                $expectedNext = 'intervalo_fim';

            } elseif ($punch->punch_type === 'intervalo_fim') {
                if ($expectedNext !== 'intervalo_fim') {
                    $errors[] = "Registro #{$punch->nsr}: Fim de intervalo sem início correspondente.";
                }
                $expectedNext = 'saida';
            }
        }

        // Check if last punch is saida
        if (!empty($punches)) {
            $lastPunch = end($punches);
            if ($lastPunch->punch_type === 'entrada') {
                $warnings[] = "Jornada não finalizada. Falta registro de saída.";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Generate monthly timesheet (espelho de ponto)
     *
     * @param int $employeeId
     * @param string $month Format: Y-m
     * @return array
     */
    public function generateMonthlyTimesheet(int $employeeId, string $month): array
    {
        $employee = $this->employeeModel->find($employeeId);

        if (!$employee) {
            return [
                'success' => false,
                'error' => 'Funcionário não encontrado.',
            ];
        }

        // Get date range for month
        $startDate = $month . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));

        // Get all punches for month
        $punches = $this->timePunchModel
            ->where('employee_id', $employeeId)
            ->where('DATE(punch_time) >=', $startDate)
            ->where('DATE(punch_time) <=', $endDate)
            ->orderBy('punch_time', 'ASC')
            ->findAll();

        // Group by date
        $punchesByDate = [];
        foreach ($punches as $punch) {
            $date = date('Y-m-d', strtotime($punch->punch_time));
            $punchesByDate[$date][] = $punch;
        }

        // Get justifications for month
        $justifications = $this->justificationModel
            ->where('employee_id', $employeeId)
            ->where('DATE(date) >=', $startDate)
            ->where('DATE(date) <=', $endDate)
            ->findAll();

        $justificationsByDate = [];
        foreach ($justifications as $justification) {
            $justificationsByDate[$justification->date][] = $justification;
        }

        // Generate daily records
        $dailyRecords = [];
        $currentDate = $startDate;

        while ($currentDate <= $endDate) {
            $dayPunches = $punchesByDate[$currentDate] ?? [];
            $dayJustifications = $justificationsByDate[$currentDate] ?? [];

            $dailyHours = $this->calculateDailyHours($dayPunches);
            $validation = $this->validatePunchPairs($dayPunches);

            $dailyRecords[] = [
                'date' => $currentDate,
                'day_of_week' => date('l', strtotime($currentDate)),
                'punches' => $dailyHours['punches'],
                'hours_worked' => $dailyHours['total_hours'],
                'expected_hours' => $employee->daily_hours,
                'balance' => round($dailyHours['total_hours'] - $employee->daily_hours, 2),
                'justifications' => $dayJustifications,
                'validation' => $validation,
            ];

            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }

        // Calculate totals
        $hoursCalculation = $this->calculateHoursWorked($employeeId, $startDate, $endDate);

        // Generate NSR range
        $nsrRange = $this->getNSRRange($punches);

        return [
            'success' => true,
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'cpf' => $employee->cpf,
                'position' => $employee->position,
                'department' => $employee->department,
            ],
            'period' => [
                'month' => $month,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'summary' => [
                'total_hours' => $hoursCalculation['total_hours'],
                'expected_hours' => $hoursCalculation['expected_hours'],
                'balance' => $hoursCalculation['balance'],
                'days_worked' => $hoursCalculation['total_days'],
                'total_punches' => count($punches),
                'nsr_range' => $nsrRange,
            ],
            'daily_records' => $dailyRecords,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get NSR range from punches
     *
     * @param array $punches
     * @return array
     */
    protected function getNSRRange(array $punches): array
    {
        if (empty($punches)) {
            return ['first' => null, 'last' => null];
        }

        $nsrs = array_map(function ($p) {
            return $p->nsr;
        }, $punches);

        return [
            'first' => min($nsrs),
            'last' => max($nsrs),
        ];
    }

    /**
     * Check for missing punches (days without punches)
     *
     * @param int $employeeId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function findMissingPunches(int $employeeId, string $startDate, string $endDate): array
    {
        // Get punches
        $punches = $this->timePunchModel
            ->select('DISTINCT DATE(punch_time) as date')
            ->where('employee_id', $employeeId)
            ->where('DATE(punch_time) >=', $startDate)
            ->where('DATE(punch_time) <=', $endDate)
            ->findAll();

        $punchDates = array_map(function ($p) {
            return $p->date;
        }, $punches);

        // Get all business days in period
        $missingDates = [];
        $currentDate = $startDate;

        while ($currentDate <= $endDate) {
            $dayOfWeek = date('N', strtotime($currentDate)); // 1=Monday, 7=Sunday

            // Skip weekends (assuming Monday-Friday work week)
            if ($dayOfWeek <= 5) {
                if (!in_array($currentDate, $punchDates)) {
                    // Check if there's a justification
                    $justification = $this->justificationModel
                        ->where('employee_id', $employeeId)
                        ->where('date', $currentDate)
                        ->first();

                    $missingDates[] = [
                        'date' => $currentDate,
                        'day_of_week' => date('l', strtotime($currentDate)),
                        'has_justification' => $justification !== null,
                        'justification' => $justification,
                    ];
                }
            }

            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }

        return $missingDates;
    }

    /**
     * Check for late arrivals
     *
     * @param int $employeeId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function findLateArrivals(int $employeeId, string $startDate, string $endDate): array
    {
        $employee = $this->employeeModel->find($employeeId);

        if (!$employee || !$employee->work_start_time) {
            return [];
        }

        // Get all entrance punches
        $punches = $this->timePunchModel
            ->where('employee_id', $employeeId)
            ->where('punch_type', 'entrada')
            ->where('DATE(punch_time) >=', $startDate)
            ->where('DATE(punch_time) <=', $endDate)
            ->orderBy('punch_time', 'ASC')
            ->findAll();

        $lateArrivals = [];
        $toleranceMinutes = $this->settingModel->get('late_tolerance_minutes', 10);

        foreach ($punches as $punch) {
            $punchTime = date('H:i:s', strtotime($punch->punch_time));
            $expectedTime = $employee->work_start_time;

            // Add tolerance
            $expectedWithTolerance = date('H:i:s', strtotime($expectedTime) + ($toleranceMinutes * 60));

            if ($punchTime > $expectedWithTolerance) {
                $minutesLate = (strtotime($punchTime) - strtotime($expectedTime)) / 60;

                $lateArrivals[] = [
                    'date' => date('Y-m-d', strtotime($punch->punch_time)),
                    'punch_time' => $punchTime,
                    'expected_time' => $expectedTime,
                    'minutes_late' => round($minutesLate, 0),
                    'punch' => $punch,
                ];
            }
        }

        return $lateArrivals;
    }

    /**
     * Calculate overtime hours
     *
     * @param int $employeeId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function calculateOvertime(int $employeeId, string $startDate, string $endDate): array
    {
        $employee = $this->employeeModel->find($employeeId);

        if (!$employee) {
            return [];
        }

        $calculation = $this->calculateHoursWorked($employeeId, $startDate, $endDate);

        $dailyOvertime = [];
        $totalOvertime = 0;

        foreach ($calculation['daily_breakdown'] as $date => $hours) {
            $overtime = $hours['total_hours'] - $employee->daily_hours;

            if ($overtime > 0) {
                $dailyOvertime[] = [
                    'date' => $date,
                    'hours_worked' => $hours['total_hours'],
                    'expected_hours' => $employee->daily_hours,
                    'overtime_hours' => round($overtime, 2),
                ];

                $totalOvertime += $overtime;
            }
        }

        return [
            'total_overtime' => round($totalOvertime, 2),
            'daily_overtime' => $dailyOvertime,
        ];
    }

    /**
     * Get timesheet statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $today = date('Y-m-d');
        $thisMonth = date('Y-m');

        return [
            'punches_today' => $this->timePunchModel
                ->where('DATE(punch_time)', $today)
                ->countAllResults(),
            'punches_this_month' => $this->timePunchModel
                ->where('DATE(punch_time) LIKE', $thisMonth . '%')
                ->countAllResults(),
            'total_punches' => $this->timePunchModel->countAllResults(),
        ];
    }
}
