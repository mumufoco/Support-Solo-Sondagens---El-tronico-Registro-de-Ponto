#!/usr/bin/env php
<?php

/**
 * Daily Timesheet Calculation Worker
 *
 * Executes daily at 00:30 via cron
 * Processes previous day's punches for all active employees
 *
 * Cron entry:
 * 30 0 * * * /usr/bin/php /path/to/scripts/cron_calculate.php >> /var/log/ponto/cron_calculate.log 2>&1
 */

use CodeIgniter\Boot;
use Config\Paths;

// Load CodeIgniter
define('FCPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
chdir(dirname(__DIR__));

require_once __DIR__ . '/../vendor/autoload.php';
require FCPATH . '../app/Config/Paths.php';
$paths = new Paths();

// Define path constants
define('APPPATH', realpath(FCPATH . '../app') . DIRECTORY_SEPARATOR);
define('ROOTPATH', realpath(APPPATH . '../') . DIRECTORY_SEPARATOR);
define('SYSTEMPATH', realpath($paths->systemDirectory) . DIRECTORY_SEPARATOR);
define('WRITEPATH', realpath($paths->writableDirectory) . DIRECTORY_SEPARATOR);

// Load .env file
require_once SYSTEMPATH . 'Config/DotEnv.php';
$dotenv = new \CodeIgniter\Config\DotEnv(ROOTPATH);
$dotenv->load();

// Define environment
if (!defined('ENVIRONMENT')) {
    $env = $_ENV['CI_ENVIRONMENT'] ?? $_SERVER['CI_ENVIRONMENT'] ?? getenv('CI_ENVIRONMENT') ?: 'production';
    define('ENVIRONMENT', $env);
}

// Bootstrap CodeIgniter for console
require $paths->systemDirectory . '/Boot.php';
Boot::bootConsole($paths);

// Load database
$db = \Config\Database::connect();

// Load models
$employeeModel = new \App\Models\EmployeeModel();
$timePunchModel = new \App\Models\TimePunchModel();
$consolidatedModel = new \App\Models\TimesheetConsolidatedModel();
$justificationModel = new \App\Models\JustificationModel();
$auditModel = new \App\Models\AuditLogModel();

// Date to process (yesterday)
$processDate = date('Y-m-d', strtotime('-1 day'));

echo "===========================================\n";
echo "Daily Timesheet Calculation Worker\n";
echo "Processing date: {$processDate}\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n";
echo "===========================================\n\n";

// Get all active employees
$employees = $employeeModel->where('active', true)->findAll();
$totalEmployees = count($employees);
$successCount = 0;
$errorCount = 0;
$incompleteCount = 0;

echo "Found {$totalEmployees} active employees to process.\n\n";

foreach ($employees as $employee) {
    echo "Processing Employee ID {$employee->id} - {$employee->name}...\n";

    // Check if already processed
    if ($consolidatedModel->isProcessed($employee->id, $processDate)) {
        echo "  ⚠ Already processed. Skipping.\n\n";
        continue;
    }

    // Start transaction
    $db->transStart();

    try {
        // Get all punches for the date
        $punches = $timePunchModel
            ->where('employee_id', $employee->id)
            ->where('punch_date', $processDate)
            ->orderBy('punch_time', 'ASC')
            ->findAll();

        $punchCount = count($punches);
        echo "  Found {$punchCount} punch(es)\n";

        // Initialize variables
        $totalWorked = 0;
        $totalInterval = 0;
        $isIncomplete = false;
        $notes = [];
        $firstPunch = null;
        $lastPunch = null;

        // Validate pairing and calculate hours
        if ($punchCount === 0) {
            // No punches - mark as incomplete
            $isIncomplete = true;
            $notes[] = "Nenhuma marcação de ponto registrada.";
            echo "  ❌ Incomplete: No punches\n";
            $incompleteCount++;
        } elseif ($punchCount % 2 !== 0) {
            // Odd number of punches - incomplete
            $isIncomplete = true;
            $notes[] = "Número ímpar de marcações ({$punchCount}). Falta entrada ou saída.";
            echo "  ❌ Incomplete: Odd number of punches\n";
            $incompleteCount++;
        } else {
            // Even number - validate pairs
            $firstPunch = $punches[0]->punch_time;
            $lastPunch = $punches[$punchCount - 1]->punch_time;

            // Group punches into pairs (entrada/saída)
            for ($i = 0; $i < $punchCount; $i += 2) {
                $punchIn = $punches[$i];
                $punchOut = $punches[$i + 1] ?? null;

                if (!$punchOut) {
                    $isIncomplete = true;
                    $notes[] = "Marcação de saída faltando para entrada às {$punchIn->punch_time}.";
                    break;
                }

                // Calculate duration
                $start = strtotime("{$processDate} {$punchIn->punch_time}");
                $end = strtotime("{$processDate} {$punchOut->punch_time}");
                $duration = ($end - $start) / 3600; // Convert to hours

                // First pair is work, subsequent pairs are intervals
                if ($i === 0 || $i === $punchCount - 2) {
                    // Work period
                    $totalWorked += $duration;
                } else {
                    // Interval period
                    $totalInterval += $duration;
                }
            }

            // If not incomplete, calculate net worked hours
            if (!$isIncomplete && $punchCount >= 4) {
                // Total worked = (last_punch - first_punch) - total_interval
                $start = strtotime("{$processDate} {$firstPunch}");
                $end = strtotime("{$processDate} {$lastPunch}");
                $totalWorked = (($end - $start) / 3600) - $totalInterval;
            } elseif (!$isIncomplete && $punchCount === 2) {
                // Simple entrada/saída
                // Already calculated above
            }

            echo "  ✓ Total worked: " . number_format($totalWorked, 2) . "h\n";
            echo "  ✓ Total interval: " . number_format($totalInterval, 2) . "h\n";
        }

        // Get expected hours (from employee or default 8h)
        $expectedHours = $employee->daily_hours ?? 8.00;

        // Calculate extra/owed hours
        $extraHours = 0;
        $owedHours = 0;
        $justified = false;
        $justificationId = null;
        $intervalViolation = 0;

        if (!$isIncomplete) {
            $difference = $totalWorked - $expectedHours;

            if ($difference > 0) {
                // Extra hours
                $extraHours = $difference;
                echo "  ✅ Extra hours: +" . number_format($extraHours, 2) . "h\n";
            } elseif ($difference < 0) {
                // Owed hours - check for justification
                $hasJustification = $justificationModel->hasApprovedJustification(
                    $employee->id,
                    $processDate
                );

                if ($hasJustification) {
                    $justified = true;
                    $notes[] = "Justificativa aprovada. Horas não descontadas.";
                    echo "  ✅ Justified: Has approved justification\n";

                    // Get justification ID
                    $justification = $justificationModel
                        ->where('employee_id', $employee->id)
                        ->where('justification_date', $processDate)
                        ->where('status', 'aprovado')
                        ->first();

                    if ($justification) {
                        $justificationId = $justification->id;
                    }
                } else {
                    $owedHours = abs($difference);
                    echo "  ⚠ Owed hours: -" . number_format($owedHours, 2) . "h\n";
                }
            }

            // Validate mandatory intervals (CLT rules)
            if ($totalWorked > 6) {
                // Jornada > 6h: must have >= 1h interval
                if ($totalInterval < 1) {
                    $violation = 1 - $totalInterval;
                    $intervalViolation = $violation * 1.5; // 50% premium
                    $notes[] = "Violação de intervalo: jornada >6h sem intervalo mínimo de 1h. Pagamento adicional: " . number_format($intervalViolation, 2) . "h.";
                    echo "  ⚠ Interval violation: " . number_format($intervalViolation, 2) . "h\n";
                }
            } elseif ($totalWorked >= 4 && $totalWorked <= 6) {
                // Jornada 4-6h: must have >= 15min interval
                if ($totalInterval < 0.25) {
                    $violation = 0.25 - $totalInterval;
                    $intervalViolation = $violation * 1.5;
                    $notes[] = "Violação de intervalo: jornada 4-6h sem intervalo mínimo de 15min. Pagamento adicional: " . number_format($intervalViolation, 2) . "h.";
                    echo "  ⚠ Interval violation: " . number_format($intervalViolation, 2) . "h\n";
                }
            }
        }

        // Save to timesheet_consolidated
        $consolidatedData = [
            'employee_id' => $employee->id,
            'date' => $processDate,
            'total_worked' => round($totalWorked, 2),
            'expected' => $expectedHours,
            'extra' => round($extraHours, 2),
            'owed' => round($owedHours, 2),
            'interval_violation' => round($intervalViolation, 2),
            'justified' => $justified,
            'incomplete' => $isIncomplete,
            'justification_id' => $justificationId,
            'punches_count' => $punchCount,
            'first_punch' => $firstPunch,
            'last_punch' => $lastPunch,
            'total_interval' => round($totalInterval, 2),
            'notes' => implode(' | ', $notes),
            'processed_at' => date('Y-m-d H:i:s'),
        ];

        $consolidatedModel->insert($consolidatedData);

        // Update employee balance
        if (!$isIncomplete) {
            $currentExtra = (float) $employee->extra_hours_balance;
            $currentOwed = (float) $employee->owed_hours_balance;

            $newExtra = $currentExtra + $extraHours;
            $newOwed = $currentOwed + $owedHours;

            $employeeModel->update($employee->id, [
                'extra_hours_balance' => round($newExtra, 2),
                'owed_hours_balance' => round($newOwed, 2),
            ]);

            echo "  ✓ Updated balance: Extra=" . number_format($newExtra, 2) . "h, Owed=" . number_format($newOwed, 2) . "h\n";
        }

        // Commit transaction
        $db->transComplete();

        if ($db->transStatus() === false) {
            echo "  ❌ Transaction failed\n\n";
            $errorCount++;
            continue;
        }

        // Send email notification
        try {
            sendDailyEmail($employee, $consolidatedData);
            echo "  ✉ Email sent\n";
        } catch (\Exception $e) {
            echo "  ⚠ Email failed: " . $e->getMessage() . "\n";
        }

        // Notify if incomplete
        if ($isIncomplete) {
            notifyIncomplete($employee, $processDate, $notes);
            echo "  📬 Incomplete notification sent\n";
        }

        // Audit log
        $auditModel->log(
            $employee->id,
            'TIMESHEET_CALCULATED',
            'timesheet_consolidated',
            $consolidatedModel->getInsertID(),
            null,
            $consolidatedData,
            "Cálculo diário processado para {$processDate}",
            'info'
        );

        echo "  ✅ Success\n\n";
        $successCount++;

    } catch (\Exception $e) {
        $db->transRollback();
        echo "  ❌ Error: " . $e->getMessage() . "\n\n";
        $errorCount++;

        // Log error
        log_message('error', "Cron calculate failed for employee {$employee->id}: " . $e->getMessage());
    }
}

echo "\n===========================================\n";
echo "Processing Complete\n";
echo "Total: {$totalEmployees} | Success: {$successCount} | Errors: {$errorCount} | Incomplete: {$incompleteCount}\n";
echo "Finished at: " . date('Y-m-d H:i:s') . "\n";
echo "===========================================\n";

// Helper functions

/**
 * Send daily email summary to employee
 */
function sendDailyEmail($employee, $data)
{
    $email = \Config\Services::email();

    $email->setFrom(env('email.fromEmail', 'noreply@empresa.com'), env('email.fromName', 'Sistema de Ponto'));
    $email->setTo($employee->email);
    $email->setSubject("Resumo do Ponto - " . date('d/m/Y', strtotime($data['date'])));

    $balance = $data['extra'] - $data['owed'];
    $balanceText = $balance >= 0 ? "+{$balance}h" : "{$balance}h";

    $message = "
        <h2>Resumo do Ponto - " . date('d/m/Y', strtotime($data['date'])) . "</h2>
        <p>Olá, {$employee->name}!</p>

        <h3>Resumo do Dia:</h3>
        <ul>
            <li><strong>Horas Trabalhadas:</strong> " . number_format($data['total_worked'], 2) . "h</li>
            <li><strong>Horas Esperadas:</strong> " . number_format($data['expected'], 2) . "h</li>
            <li><strong>Horas Extras:</strong> +" . number_format($data['extra'], 2) . "h</li>
            <li><strong>Horas Devidas:</strong> -" . number_format($data['owed'], 2) . "h</li>
            <li><strong>Saldo do Dia:</strong> {$balanceText}</li>
        </ul>

        <p><a href='" . base_url('/timesheet/balance') . "'>Ver Saldo Completo</a></p>
    ";

    $email->setMessage($message);
    return $email->send();
}

/**
 * Notify employee and manager about incomplete punches
 */
function notifyIncomplete($employee, $date, $notes)
{
    $notificationModel = new \App\Models\NotificationModel();

    // Notify employee
    $notificationModel->insert([
        'employee_id' => $employee->id,
        'title' => '⚠️ Marcações Incompletas',
        'message' => "Suas marcações de ponto do dia " . date('d/m/Y', strtotime($date)) . " estão incompletas. " . implode(' ', $notes),
        'type' => 'warning',
        'link' => '/timesheet',
        'read' => false,
    ]);

    // Notify manager
    $employeeModel = new \App\Models\EmployeeModel();
    $managers = $employeeModel
        ->whereIn('role', ['admin', 'gestor'])
        ->where('active', true)
        ->findAll();

    foreach ($managers as $manager) {
        if ($manager->role === 'gestor' && $manager->department !== $employee->department) {
            continue;
        }

        $notificationModel->insert([
            'employee_id' => $manager->id,
            'title' => '⚠️ Marcações Incompletas',
            'message' => "{$employee->name} possui marcações incompletas em " . date('d/m/Y', strtotime($date)),
            'type' => 'warning',
            'link' => '/employees/' . $employee->id . '/timesheet',
            'read' => false,
        ]);
    }
}
