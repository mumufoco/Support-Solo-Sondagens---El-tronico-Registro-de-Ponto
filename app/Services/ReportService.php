<?php

namespace App\Services;

use App\Models\TimePunchModel;
use App\Models\EmployeeModel;
use App\Models\SettingModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Report Service
 *
 * Handles generation of reports in various formats (PDF, Excel, CSV)
 */
class ReportService
{
    protected $timePunchModel;
    protected $employeeModel;
    protected $settingModel;
    protected $timesheetService;

    public function __construct()
    {
        $this->timePunchModel = new TimePunchModel();
        $this->employeeModel = new EmployeeModel();
        $this->settingModel = new SettingModel();
        $this->timesheetService = new TimesheetService();
    }

    /**
     * Generate monthly timesheet report in Excel format
     *
     * @param int $employeeId
     * @param string $month Format: Y-m
     * @return array
     */
    public function generateMonthlyTimesheetExcel(int $employeeId, string $month): array
    {
        try {
            // Get timesheet data
            $timesheet = $this->timesheetService->generateMonthlyTimesheet($employeeId, $month);

            if (!$timesheet['success']) {
                return $timesheet;
            }

            // Create spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set document properties
            $companyName = $this->settingModel->get('company_name', 'Sistema de Ponto Eletrônico');
            $spreadsheet->getProperties()
                ->setCreator($companyName)
                ->setTitle('Espelho de Ponto - ' . $timesheet['employee']['name'])
                ->setSubject('Relatório de Ponto Eletrônico')
                ->setDescription('Espelho de ponto mensal - ' . $month);

            // Header
            $sheet->setCellValue('A1', $companyName);
            $sheet->mergeCells('A1:H1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('A2', 'ESPELHO DE PONTO ELETRÔNICO');
            $sheet->mergeCells('A2:H2');
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Employee info
            $row = 4;
            $sheet->setCellValue("A{$row}", 'Funcionário:');
            $sheet->setCellValue("B{$row}", $timesheet['employee']['name']);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);

            $row++;
            $sheet->setCellValue("A{$row}", 'CPF:');
            $sheet->setCellValue("B{$row}", $this->formatCPF($timesheet['employee']['cpf']));
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);

            $row++;
            $sheet->setCellValue("A{$row}", 'Cargo:');
            $sheet->setCellValue("B{$row}", $timesheet['employee']['position']);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);

            $row++;
            $sheet->setCellValue("A{$row}", 'Departamento:');
            $sheet->setCellValue("B{$row}", $timesheet['employee']['department']);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);

            $row++;
            $sheet->setCellValue("A{$row}", 'Período:');
            $sheet->setCellValue("B{$row}", date('m/Y', strtotime($month . '-01')));
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);

            // Table header
            $row += 2;
            $headerRow = $row;
            $headers = ['Data', 'Dia', 'Entrada', 'Saída', 'Int. Início', 'Int. Fim', 'Horas', 'Saldo'];
            $col = 'A';

            foreach ($headers as $header) {
                $sheet->setCellValue("{$col}{$row}", $header);
                $sheet->getStyle("{$col}{$row}")->getFont()->setBold(true);
                $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("{$col}{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E0E0E0');
                $col++;
            }

            // Data rows
            $row++;

            foreach ($timesheet['daily_records'] as $record) {
                $punches = $record['punches'];

                // Extract punch times
                $entrada = '';
                $saida = '';
                $intervaloInicio = '';
                $intervaloFim = '';

                foreach ($punches as $punch) {
                    $time = date('H:i', strtotime($punch['time']));

                    switch ($punch['type']) {
                        case 'entrada':
                            $entrada = $time;
                            break;
                        case 'saida':
                            $saida = $time;
                            break;
                        case 'intervalo_inicio':
                            $intervaloInicio = $time;
                            break;
                        case 'intervalo_fim':
                            $intervaloFim = $time;
                            break;
                    }
                }

                $sheet->setCellValue("A{$row}", date('d/m', strtotime($record['date'])));
                $sheet->setCellValue("B{$row}", $this->getDayOfWeekPT($record['day_of_week']));
                $sheet->setCellValue("C{$row}", $entrada);
                $sheet->setCellValue("D{$row}", $saida);
                $sheet->setCellValue("E{$row}", $intervaloInicio);
                $sheet->setCellValue("F{$row}", $intervaloFim);
                $sheet->setCellValue("G{$row}", $this->formatHours($record['hours_worked']));
                $sheet->setCellValue("H{$row}", $this->formatBalance($record['balance']));

                // Center align
                foreach (range('A', 'H') as $col) {
                    $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // Color balance
                if ($record['balance'] < 0) {
                    $sheet->getStyle("H{$row}")->getFont()->getColor()->setRGB('FF0000');
                } elseif ($record['balance'] > 0) {
                    $sheet->getStyle("H{$row}")->getFont()->getColor()->setRGB('008000');
                }

                $row++;
            }

            // Summary
            $row++;
            $sheet->setCellValue("F{$row}", 'Total de Horas:');
            $sheet->setCellValue("G{$row}", $this->formatHours($timesheet['summary']['total_hours']));
            $sheet->getStyle("F{$row}:G{$row}")->getFont()->setBold(true);

            $row++;
            $sheet->setCellValue("F{$row}", 'Horas Previstas:');
            $sheet->setCellValue("G{$row}", $this->formatHours($timesheet['summary']['expected_hours']));
            $sheet->getStyle("F{$row}:G{$row}")->getFont()->setBold(true);

            $row++;
            $sheet->setCellValue("F{$row}", 'Saldo Total:');
            $sheet->setCellValue("G{$row}", $this->formatBalance($timesheet['summary']['balance']));
            $sheet->getStyle("F{$row}:G{$row}")->getFont()->setBold(true);

            if ($timesheet['summary']['balance'] < 0) {
                $sheet->getStyle("G{$row}")->getFont()->getColor()->setRGB('FF0000');
            } elseif ($timesheet['summary']['balance'] > 0) {
                $sheet->getStyle("G{$row}")->getFont()->getColor()->setRGB('008000');
            }

            // NSR info
            $row += 2;
            $sheet->setCellValue("A{$row}", 'NSR Inicial: ' . $timesheet['summary']['nsr_range']['first']);
            $sheet->setCellValue("D{$row}", 'NSR Final: ' . $timesheet['summary']['nsr_range']['last']);

            // Borders
            $lastDataRow = $row - 3;
            $sheet->getStyle("A{$headerRow}:H{$lastDataRow}")->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);

            // Column widths
            $sheet->getColumnDimension('A')->setWidth(10);
            $sheet->getColumnDimension('B')->setWidth(12);
            $sheet->getColumnDimension('C')->setWidth(10);
            $sheet->getColumnDimension('D')->setWidth(10);
            $sheet->getColumnDimension('E')->setWidth(12);
            $sheet->getColumnDimension('F')->setWidth(12);
            $sheet->getColumnDimension('G')->setWidth(10);
            $sheet->getColumnDimension('H')->setWidth(10);

            // Save to file
            $filename = "espelho_ponto_{$timesheet['employee']['name']}_{$month}.xlsx";
            $filepath = WRITEPATH . 'uploads/reports/' . $filename;

            // Ensure directory exists
            if (!is_dir(dirname($filepath))) {
                mkdir(dirname($filepath), 0755, true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);

            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath),
            ];

        } catch (\Exception $e) {
            log_message('error', 'Report generation error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Erro ao gerar relatório.',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate department summary report
     *
     * @param string $department
     * @param string $month
     * @return array
     */
    public function generateDepartmentSummaryExcel(string $department, string $month): array
    {
        try {
            // Get employees in department
            $employees = $this->employeeModel
                ->where('department', $department)
                ->where('active', true)
                ->orderBy('name', 'ASC')
                ->findAll();

            if (empty($employees)) {
                return [
                    'success' => false,
                    'error' => 'Nenhum funcionário encontrado no departamento.',
                ];
            }

            // Create spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Header
            $companyName = $this->settingModel->get('company_name', 'Sistema de Ponto Eletrônico');
            $sheet->setCellValue('A1', $companyName);
            $sheet->mergeCells('A1:F1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('A2', "RELATÓRIO DE HORAS - {$department}");
            $sheet->mergeCells('A2:F2');
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('A3', 'Período: ' . date('m/Y', strtotime($month . '-01')));
            $sheet->mergeCells('A3:F3');
            $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Table header
            $row = 5;
            $headers = ['Funcionário', 'Horas Trabalhadas', 'Horas Previstas', 'Saldo', 'Dias Trabalhados', 'Atrasos'];
            $col = 'A';

            foreach ($headers as $header) {
                $sheet->setCellValue("{$col}{$row}", $header);
                $sheet->getStyle("{$col}{$row}")->getFont()->setBold(true);
                $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("{$col}{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E0E0E0');
                $col++;
            }

            // Data rows
            $row++;
            $startDate = $month . '-01';
            $endDate = date('Y-m-t', strtotime($startDate));

            foreach ($employees as $employee) {
                $calculation = $this->timesheetService->calculateHoursWorked(
                    $employee->id,
                    $startDate,
                    $endDate
                );

                $lateArrivals = $this->timesheetService->findLateArrivals(
                    $employee->id,
                    $startDate,
                    $endDate
                );

                $sheet->setCellValue("A{$row}", $employee->name);
                $sheet->setCellValue("B{$row}", $this->formatHours($calculation['total_hours']));
                $sheet->setCellValue("C{$row}", $this->formatHours($calculation['expected_hours']));
                $sheet->setCellValue("D{$row}", $this->formatBalance($calculation['balance']));
                $sheet->setCellValue("E{$row}", $calculation['total_days']);
                $sheet->setCellValue("F{$row}", count($lateArrivals));

                // Center align numeric columns
                foreach (['B', 'C', 'D', 'E', 'F'] as $col) {
                    $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                // Color balance
                if ($calculation['balance'] < 0) {
                    $sheet->getStyle("D{$row}")->getFont()->getColor()->setRGB('FF0000');
                } elseif ($calculation['balance'] > 0) {
                    $sheet->getStyle("D{$row}")->getFont()->getColor()->setRGB('008000');
                }

                $row++;
            }

            // Borders
            $sheet->getStyle("A5:F" . ($row - 1))->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);

            // Column widths
            $sheet->getColumnDimension('A')->setWidth(30);
            $sheet->getColumnDimension('B')->setWidth(18);
            $sheet->getColumnDimension('C')->setWidth(18);
            $sheet->getColumnDimension('D')->setWidth(12);
            $sheet->getColumnDimension('E')->setWidth(18);
            $sheet->getColumnDimension('F')->setWidth(12);

            // Save to file
            $filename = "relatorio_departamento_{$department}_{$month}.xlsx";
            $filepath = WRITEPATH . 'uploads/reports/' . $filename;

            // Ensure directory exists
            if (!is_dir(dirname($filepath))) {
                mkdir(dirname($filepath), 0755, true);
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($filepath);

            return [
                'success' => true,
                'filename' => $filename,
                'filepath' => $filepath,
                'size' => filesize($filepath),
            ];

        } catch (\Exception $e) {
            log_message('error', 'Department report generation error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Erro ao gerar relatório.',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format CPF
     *
     * @param string $cpf
     * @return string
     */
    protected function formatCPF(string $cpf): string
    {
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }

    /**
     * Format hours (decimal to HH:MM)
     *
     * @param float $hours
     * @return string
     */
    protected function formatHours(float $hours): string
    {
        $h = floor($hours);
        $m = round(($hours - $h) * 60);

        return sprintf('%02d:%02d', $h, $m);
    }

    /**
     * Format balance with sign
     *
     * @param float $balance
     * @return string
     */
    protected function formatBalance(float $balance): string
    {
        $sign = $balance >= 0 ? '+' : '';
        return $sign . $this->formatHours(abs($balance));
    }

    /**
     * Get day of week in Portuguese
     *
     * @param string $dayOfWeek
     * @return string
     */
    protected function getDayOfWeekPT(string $dayOfWeek): string
    {
        $days = [
            'Monday' => 'Segunda',
            'Tuesday' => 'Terça',
            'Wednesday' => 'Quarta',
            'Thursday' => 'Quinta',
            'Friday' => 'Sexta',
            'Saturday' => 'Sábado',
            'Sunday' => 'Domingo',
        ];

        return $days[$dayOfWeek] ?? $dayOfWeek;
    }

    /**
     * Delete old reports
     *
     * @param int $daysOld
     * @return int Count of deleted files
     */
    public function deleteOldReports(int $daysOld = 30): int
    {
        $reportsDir = WRITEPATH . 'uploads/reports/';

        if (!is_dir($reportsDir)) {
            return 0;
        }

        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);
        $deleted = 0;

        $files = scandir($reportsDir);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filepath = $reportsDir . $file;

            if (is_file($filepath) && filemtime($filepath) < $cutoffTime) {
                if (unlink($filepath)) {
                    $deleted++;
                }
            }
        }

        return $deleted;
    }
}
