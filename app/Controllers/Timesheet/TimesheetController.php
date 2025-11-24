<?php

namespace App\Controllers\Timesheet;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;
use App\Models\TimesheetConsolidatedModel;
use App\Models\TimePunchModel;
use App\Models\AuditLogModel;

class TimesheetController extends BaseController
{
    protected $employeeModel;
    protected $consolidatedModel;
    protected $timePunchModel;
    protected $auditModel;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->consolidatedModel = new TimesheetConsolidatedModel();
        $this->timePunchModel = new TimePunchModel();
        $this->auditModel = new AuditLogModel();
    }

    /**
     * Display timesheet history (espelho de ponto)
     */
    public function index()
    {
        // Get authenticated employee
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return redirect()->to('/login')->with('error', 'Você precisa estar autenticado.');
        }

        // Get month filter (format: YYYY-MM)
        $selectedMonth = $this->request->getGet('month') ?: date('Y-m');

        // Get target employee ID (for managers)
        $targetEmployeeId = $this->request->getGet('employee_id') ?? $employee['id'];

        // Validate access
        if ($targetEmployeeId != $employee['id'] && !in_array($employee['role'], ['admin', 'gestor'])) {
            $targetEmployeeId = $employee['id'];
        }

        // Get viewing employee
        $viewingEmployee = $this->employeeModel->find($targetEmployeeId);

        // Get consolidated records for the month
        $startDate = $selectedMonth . '-01';
        $endDate = date('Y-m-t', strtotime($startDate)); // Last day of month

        $records = $this->consolidatedModel
            ->where('employee_id', $targetEmployeeId)
            ->where('date >=', $startDate)
            ->where('date <=', $endDate)
            ->orderBy('date', 'ASC')
            ->findAll();

        // Calculate summary
        $totalHours = 0;
        $totalExpected = 0;
        $totalExtra = 0;
        $totalOwed = 0;
        $lateArrivals = 0;
        $daysWorked = 0;

        $dailyRecords = [];
        foreach ($records as $record) {
            $totalHours += $record->total_worked ?? 0;
            $totalExpected += $record->expected ?? 0;
            $totalExtra += $record->extra ?? 0;
            $totalOwed += $record->owed ?? 0;

            if ($record->total_worked > 0) {
                $daysWorked++;
            }

            // Get punches for this day
            $dayPunches = $this->timePunchModel
                ->where('employee_id', $targetEmployeeId)
                ->where('DATE(punch_time)', $record->date)
                ->orderBy('punch_time', 'ASC')
                ->findAll();

            // Organize punches by type
            $entrada = null;
            $inicio_intervalo = null;
            $fim_intervalo = null;
            $saida = null;

            foreach ($dayPunches as $punch) {
                $time = date('H:i', strtotime($punch->punch_time));

                if ($punch->punch_type === 'entrada' && !$entrada) {
                    $entrada = $time;
                } elseif ($punch->punch_type === 'saida' && !$inicio_intervalo) {
                    $inicio_intervalo = $time; // First saida is lunch start
                } elseif ($punch->punch_type === 'entrada' && $entrada) {
                    $fim_intervalo = $time; // Second entrada is lunch end
                } elseif ($punch->punch_type === 'saida') {
                    $saida = $time; // Last saida is day end
                }
            }

            // Check if late (assuming work starts at 08:00)
            $entradaLate = false;
            if ($entrada && strtotime($entrada) > strtotime('08:10')) {
                $entradaLate = true;
                $lateArrivals++;
            }

            // Prepare daily record for view
            $dailyRecords[] = [
                'date' => $record->date,
                'date_formatted' => date('d/m/Y', strtotime($record->date)),
                'day_of_week' => strftime('%a', strtotime($record->date)),
                'entrada' => $entrada,
                'entrada_late' => $entradaLate,
                'inicio_intervalo' => $inicio_intervalo,
                'fim_intervalo' => $fim_intervalo,
                'saida' => $saida,
                'total_hours' => number_format($record->total_worked, 2),
                'balance' => $record->extra - $record->owed,
                'balance_formatted' => number_format(abs($record->extra - $record->owed), 2),
                'missing_punches' => $record->incomplete ?? false,
                'is_weekend' => in_array(date('N', strtotime($record->date)), [6, 7]),
                'is_holiday' => false, // TODO: Check holidays table
                'holiday_name' => null,
            ];
        }

        // Calculate expected days (workdays in month)
        $expectedDays = 22; // Default estimate for business days

        // Summary data
        $summary = [
            'total_hours' => number_format($totalHours, 2),
            'expected_hours' => number_format($totalExpected, 2),
            'balance' => $totalExtra - $totalOwed,
            'balance_formatted' => number_format(abs($totalExtra - $totalOwed), 2),
            'days_worked' => $daysWorked,
            'expected_days' => $expectedDays,
            'late_arrivals' => $lateArrivals,
        ];

        return view('timesheet/index', [
            'employee' => $employee,
            'viewingEmployee' => $viewingEmployee,
            'selectedMonth' => $selectedMonth,
            'summary' => $summary,
            'dailyRecords' => $dailyRecords,
        ]);
    }

    /**
     * Dashboard: Balance and evolution chart
     */
    public function balance()
    {
        // Get authenticated employee
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return redirect()->to('/login')->with('error', 'Você precisa estar autenticado.');
        }

        // Get target employee ID (for managers viewing others)
        $targetEmployeeId = $this->request->getGet('employee_id');

        // If manager/admin and employee_id provided, use it; otherwise use own ID
        if (in_array($employee['role'], ['admin', 'gestor']) && $targetEmployeeId) {
            $viewingEmployeeId = (int) $targetEmployeeId;
            $viewingEmployee = $this->employeeModel->find($viewingEmployeeId);

            // Gestores can only view their department
            if ($employee['role'] === 'gestor' && $viewingEmployee && $viewingEmployee->department !== $employee['department']) {
                return redirect()->back()->with('error', 'Você não tem permissão para visualizar este funcionário.');
            }
        } else {
            $viewingEmployeeId = $employee['id'];
            $viewingEmployee = (object) $employee;
        }

        // Get period filter (default: last 30 days)
        $period = $this->request->getGet('period') ?? '30';
        $days = (int) $period;

        // Get filter for irregularities only
        $irregularitiesOnly = $this->request->getGet('irregularities') === '1';

        // Get current balance
        $balance = $this->consolidatedModel->getCurrentBalance($viewingEmployeeId);

        // Get balance evolution for chart
        $evolution = $this->consolidatedModel->getBalanceEvolution($viewingEmployeeId, $days);

        // Get consolidated records
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        $endDate = date('Y-m-d');

        $query = $this->consolidatedModel
            ->where('employee_id', $viewingEmployeeId)
            ->where('date >=', $startDate)
            ->where('date <=', $endDate);

        if ($irregularitiesOnly) {
            $query->groupStart()
                ->where('incomplete', true)
                ->orWhere('interval_violation >', 0)
                ->orWhere('owed >', 0)
                ->groupEnd();
        }

        $records = $query->orderBy('date', 'DESC')->findAll();

        // Get statistics
        $statistics = $this->consolidatedModel->getStatistics($viewingEmployeeId, $startDate, $endDate);

        // Get list of employees (for manager dropdown)
        $employees = [];
        if (in_array($employee['role'], ['admin', 'gestor'])) {
            $employeeQuery = $this->employeeModel->where('active', true);

            if ($employee['role'] === 'gestor') {
                $employeeQuery->where('department', $employee['department']);
            }

            $employees = $employeeQuery->orderBy('name', 'ASC')->findAll();
        }

        // Get incomplete days
        $incompleteDays = $this->consolidatedModel->getIncompleteDays($viewingEmployeeId, $startDate);

        return view('timesheet/balance', [
            'employee' => $employee,
            'viewingEmployee' => $viewingEmployee,
            'balance' => $balance,
            'evolution' => $evolution,
            'records' => $records,
            'statistics' => $statistics,
            'employees' => $employees,
            'incompleteDays' => $incompleteDays,
            'period' => $period,
            'irregularitiesOnly' => $irregularitiesOnly,
            'employee_id' => $targetEmployeeId ?? '', // For URL generation in view
        ]);
    }

    /**
     * Export balance to PDF or Excel
     */
    public function export()
    {
        // Get authenticated employee
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return redirect()->to('/login')->with('error', 'Você precisa estar autenticado.');
        }

        // Get parameters
        $format = $this->request->getGet('format') ?? 'pdf'; // pdf or excel
        $targetEmployeeId = $this->request->getGet('employee_id') ?? $employee['id'];
        $period = $this->request->getGet('period') ?? '30';
        $days = (int) $period;

        // Validate access
        if ($targetEmployeeId != $employee['id'] && !in_array($employee['role'], ['admin', 'gestor'])) {
            return redirect()->back()->with('error', 'Você não tem permissão para exportar dados de outros funcionários.');
        }

        // Get viewing employee
        $viewingEmployee = $this->employeeModel->find($targetEmployeeId);

        // Gestores can only export their department
        if ($employee['role'] === 'gestor' && $viewingEmployee->department !== $employee['department']) {
            return redirect()->back()->with('error', 'Você não tem permissão para exportar dados deste funcionário.');
        }

        // Get data
        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        $endDate = date('Y-m-d');

        $balance = $this->consolidatedModel->getCurrentBalance($targetEmployeeId);
        $records = $this->consolidatedModel->getByEmployeeAndRange($targetEmployeeId, $startDate, $endDate);
        $statistics = $this->consolidatedModel->getStatistics($targetEmployeeId, $startDate, $endDate);

        // Log audit
        $this->auditModel->log(
            $employee['id'],
            'TIMESHEET_EXPORTED',
            'timesheet_consolidated',
            null,
            null,
            [
                'format' => $format,
                'employee_id' => $targetEmployeeId,
                'period' => $period,
            ],
            "Exportação de folha de ponto ({$format}) - {$viewingEmployee->name}",
            'info'
        );

        if ($format === 'excel') {
            return $this->exportExcel($viewingEmployee, $balance, $records, $statistics, $startDate, $endDate);
        } else {
            return $this->exportPdf($viewingEmployee, $balance, $records, $statistics, $startDate, $endDate);
        }
    }

    /**
     * Export to PDF using TCPDF
     */
    private function exportPdf($employee, $balance, $records, $statistics, $startDate, $endDate)
    {
        // Load TCPDF
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator('Sistema de Ponto Eletrônico');
        $pdf->SetAuthor('Sistema de Ponto');
        $pdf->SetTitle('Folha de Ponto - ' . $employee->name);
        $pdf->SetSubject('Relatório de Horas');

        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);

        // Set auto page breaks
        $pdf->SetAutoPageBreak(true, 15);

        // Add page
        $pdf->AddPage();

        // Set font
        $pdf->SetFont('helvetica', '', 10);

        // Title
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'Folha de Ponto Eletrônico', 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Período: ' . date('d/m/Y', strtotime($startDate)) . ' a ' . date('d/m/Y', strtotime($endDate)), 0, 1, 'C');
        $pdf->Ln(5);

        // Employee info
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'Funcionário: ' . $employee->name, 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Cargo: ' . ($employee->position ?? 'N/A'), 0, 1);
        $pdf->Cell(0, 5, 'Departamento: ' . ($employee->department ?? 'N/A'), 0, 1);
        $pdf->Ln(5);

        // Balance summary
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'Resumo do Saldo de Horas', 0, 1);
        $pdf->SetFont('helvetica', '', 10);

        $balanceColor = $balance['balance'] > 0 ? [34, 139, 34] : ($balance['balance'] < 0 ? [220, 53, 69] : [108, 117, 125]);
        $balanceText = number_format(abs($balance['balance']), 2) . 'h';
        if ($balance['balance'] > 0) $balanceText = '+' . $balanceText;
        if ($balance['balance'] < 0) $balanceText = '-' . $balanceText;

        $pdf->Cell(60, 6, 'Horas Extras:', 0, 0);
        $pdf->SetTextColor(34, 139, 34);
        $pdf->Cell(40, 6, '+' . number_format($balance['extra'], 2) . 'h', 0, 1);
        $pdf->SetTextColor(0, 0, 0);

        $pdf->Cell(60, 6, 'Horas Devidas:', 0, 0);
        $pdf->SetTextColor(220, 53, 69);
        $pdf->Cell(40, 6, '-' . number_format($balance['owed'], 2) . 'h', 0, 1);
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(60, 6, 'Saldo Total:', 0, 0);
        $pdf->SetTextColor($balanceColor[0], $balanceColor[1], $balanceColor[2]);
        $pdf->Cell(40, 6, $balanceText, 0, 1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(5);

        // Statistics
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'Estatísticas do Período', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(60, 5, 'Dias trabalhados:', 0, 0);
        $pdf->Cell(40, 5, $statistics['total_days'], 0, 1);
        $pdf->Cell(60, 5, 'Dias incompletos:', 0, 0);
        $pdf->Cell(40, 5, $statistics['incomplete_days'], 0, 1);
        $pdf->Cell(60, 5, 'Média diária:', 0, 0);
        $pdf->Cell(40, 5, number_format($statistics['avg_worked'], 2) . 'h', 0, 1);
        $pdf->Ln(5);

        // Records table
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'Registros Detalhados', 0, 1);
        $pdf->SetFont('helvetica', '', 8);

        // Table header
        $pdf->SetFillColor(220, 220, 220);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(20, 6, 'Data', 1, 0, 'C', true);
        $pdf->Cell(18, 6, 'Entrada', 1, 0, 'C', true);
        $pdf->Cell(18, 6, 'Saída', 1, 0, 'C', true);
        $pdf->Cell(20, 6, 'Trabalho', 1, 0, 'C', true);
        $pdf->Cell(20, 6, 'Esperado', 1, 0, 'C', true);
        $pdf->Cell(18, 6, 'Extra', 1, 0, 'C', true);
        $pdf->Cell(18, 6, 'Devidas', 1, 0, 'C', true);
        $pdf->Cell(48, 6, 'Observações', 1, 1, 'C', true);

        // Table rows
        $pdf->SetFont('helvetica', '', 7);
        foreach ($records as $record) {
            $pdf->Cell(20, 5, date('d/m/Y', strtotime($record->date)), 1, 0, 'C');
            $pdf->Cell(18, 5, $record->first_punch ?? '-', 1, 0, 'C');
            $pdf->Cell(18, 5, $record->last_punch ?? '-', 1, 0, 'C');
            $pdf->Cell(20, 5, number_format($record->total_worked, 2) . 'h', 1, 0, 'C');
            $pdf->Cell(20, 5, number_format($record->expected, 2) . 'h', 1, 0, 'C');
            $pdf->Cell(18, 5, number_format($record->extra, 2) . 'h', 1, 0, 'C');
            $pdf->Cell(18, 5, number_format($record->owed, 2) . 'h', 1, 0, 'C');

            $obs = [];
            if ($record->incomplete) $obs[] = 'Incompleto';
            if ($record->justified) $obs[] = 'Justificado';
            if ($record->interval_violation > 0) $obs[] = 'Violação intervalo';

            $pdf->Cell(48, 5, implode(', ', $obs), 1, 1, 'L');
        }

        // Output
        $filename = 'folha_ponto_' . $employee->name . '_' . date('Y-m-d') . '.pdf';
        $pdf->Output($filename, 'D');
    }

    /**
     * Export to Excel using PhpSpreadsheet
     */
    private function exportExcel($employee, $balance, $records, $statistics, $startDate, $endDate)
    {
        // Load PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Title
        $sheet->setCellValue('A1', 'Folha de Ponto Eletrônico');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Período: ' . date('d/m/Y', strtotime($startDate)) . ' a ' . date('d/m/Y', strtotime($endDate)));
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Employee info
        $row = 4;
        $sheet->setCellValue("A{$row}", 'Funcionário:');
        $sheet->setCellValue("B{$row}", $employee->name);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);

        $row++;
        $sheet->setCellValue("A{$row}", 'Cargo:');
        $sheet->setCellValue("B{$row}", $employee->position ?? 'N/A');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);

        $row++;
        $sheet->setCellValue("A{$row}", 'Departamento:');
        $sheet->setCellValue("B{$row}", $employee->department ?? 'N/A');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);

        // Balance summary
        $row += 2;
        $sheet->setCellValue("A{$row}", 'Resumo do Saldo');
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);

        $row++;
        $sheet->setCellValue("A{$row}", 'Horas Extras:');
        $sheet->setCellValue("B{$row}", number_format($balance['extra'], 2) . 'h');
        $sheet->getStyle("B{$row}")->getFont()->getColor()->setARGB('FF228B22');

        $row++;
        $sheet->setCellValue("A{$row}", 'Horas Devidas:');
        $sheet->setCellValue("B{$row}", number_format($balance['owed'], 2) . 'h');
        $sheet->getStyle("B{$row}")->getFont()->getColor()->setARGB('FFDC3545');

        $row++;
        $sheet->setCellValue("A{$row}", 'Saldo Total:');
        $balanceText = ($balance['balance'] > 0 ? '+' : '') . number_format($balance['balance'], 2) . 'h';
        $sheet->setCellValue("B{$row}", $balanceText);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $sheet->getStyle("B{$row}")->getFont()->setBold(true);

        if ($balance['balance'] > 0) {
            $sheet->getStyle("B{$row}")->getFont()->getColor()->setARGB('FF228B22');
        } elseif ($balance['balance'] < 0) {
            $sheet->getStyle("B{$row}")->getFont()->getColor()->setARGB('FFDC3545');
        }

        // Statistics
        $row += 2;
        $sheet->setCellValue("A{$row}", 'Estatísticas');
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);

        $row++;
        $sheet->setCellValue("A{$row}", 'Dias trabalhados:');
        $sheet->setCellValue("B{$row}", $statistics['total_days']);

        $row++;
        $sheet->setCellValue("A{$row}", 'Dias incompletos:');
        $sheet->setCellValue("B{$row}", $statistics['incomplete_days']);

        $row++;
        $sheet->setCellValue("A{$row}", 'Média diária:');
        $sheet->setCellValue("B{$row}", number_format($statistics['avg_worked'], 2) . 'h');

        // Records table
        $row += 2;
        $sheet->setCellValue("A{$row}", 'Registros Detalhados');
        $sheet->mergeCells("A{$row}:H{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(12);

        $row++;
        $headerRow = $row;
        $sheet->setCellValue("A{$row}", 'Data');
        $sheet->setCellValue("B{$row}", 'Entrada');
        $sheet->setCellValue("C{$row}", 'Saída');
        $sheet->setCellValue("D{$row}", 'Trabalho');
        $sheet->setCellValue("E{$row}", 'Esperado');
        $sheet->setCellValue("F{$row}", 'Extra');
        $sheet->setCellValue("G{$row}", 'Devidas');
        $sheet->setCellValue("H{$row}", 'Observações');

        $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:H{$row}")->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFDCDCDC');

        // Data rows
        foreach ($records as $record) {
            $row++;
            $sheet->setCellValue("A{$row}", date('d/m/Y', strtotime($record->date)));
            $sheet->setCellValue("B{$row}", $record->first_punch ?? '-');
            $sheet->setCellValue("C{$row}", $record->last_punch ?? '-');
            $sheet->setCellValue("D{$row}", number_format($record->total_worked, 2) . 'h');
            $sheet->setCellValue("E{$row}", number_format($record->expected, 2) . 'h');
            $sheet->setCellValue("F{$row}", number_format($record->extra, 2) . 'h');
            $sheet->setCellValue("G{$row}", number_format($record->owed, 2) . 'h');

            $obs = [];
            if ($record->incomplete) $obs[] = 'Incompleto';
            if ($record->justified) $obs[] = 'Justificado';
            if ($record->interval_violation > 0) $obs[] = 'Violação intervalo';

            $sheet->setCellValue("H{$row}", implode(', ', $obs));
        }

        // Auto-size columns
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Output
        $filename = 'folha_ponto_' . $employee->name . '_' . date('Y-m-d') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Get authenticated employee from session
     */
    private function getAuthenticatedEmployee()
    {
        $session = session();
        return $session->get('employee');
    }
}
