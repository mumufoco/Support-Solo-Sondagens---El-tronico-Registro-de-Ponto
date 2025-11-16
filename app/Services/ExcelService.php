<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;

/**
 * Excel Service
 *
 * Generates Excel reports using PhpSpreadsheet
 * Supports multiple report types with advanced formatting
 */
class ExcelService
{
    protected $companyName;

    public function __construct()
    {
        $settingModel = new \App\Models\SettingModel();
        $this->companyName = $settingModel->get('company_name', 'Sistema de Ponto Eletrônico');
    }

    /**
     * Generate report Excel based on type
     *
     * @param string $type Report type
     * @param array $data Report data
     * @param array $filters Applied filters
     * @return array Result with file path or error
     */
    public function generateReport(string $type, array $data, array $filters = []): array
    {
        try {
            switch ($type) {
                case 'folha-ponto':
                    return $this->generateTimesheetExcel($data, $filters);
                case 'horas-extras':
                    return $this->generateOvertimeExcel($data, $filters);
                case 'faltas-atrasos':
                    return $this->generateAbsenceExcel($data, $filters);
                case 'banco-horas':
                    return $this->generateBankHoursExcel($data, $filters);
                case 'consolidado-mensal':
                    return $this->generateConsolidatedExcel($data, $filters);
                case 'justificativas':
                    return $this->generateJustificationsExcel($data, $filters);
                case 'advertencias':
                    return $this->generateWarningsExcel($data, $filters);
                case 'personalizado':
                    return $this->generateCustomExcel($data, $filters);
                default:
                    return [
                        'success' => false,
                        'error' => 'Tipo de relatório inválido'
                    ];
            }
        } catch (\Exception $e) {
            log_message('error', 'Excel generation error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erro ao gerar Excel',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate timesheet Excel
     */
    protected function generateTimesheetExcel(array $data, array $filters): array
    {
        $spreadsheet = new Spreadsheet();

        // Summary sheet
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Resumo');

        $this->createHeader($summarySheet, 'Relatório de Folha de Ponto');
        $this->renderFilters($summarySheet, $filters, 4);

        // Summary statistics
        $row = 7;
        $totalWorked = array_sum(array_column($data, 'total_worked'));
        $totalExpected = array_sum(array_column($data, 'expected'));
        $totalBalance = $totalWorked - $totalExpected;

        $summarySheet->setCellValue("A{$row}", 'Total Trabalhado:');
        $summarySheet->setCellValue("B{$row}", number_format($totalWorked, 2) . 'h');
        $summarySheet->getStyle("A{$row}")->getFont()->setBold(true);

        $row++;
        $summarySheet->setCellValue("A{$row}", 'Total Esperado:');
        $summarySheet->setCellValue("B{$row}", number_format($totalExpected, 2) . 'h');
        $summarySheet->getStyle("A{$row}")->getFont()->setBold(true);

        $row++;
        $summarySheet->setCellValue("A{$row}", 'Saldo Total:');
        $summarySheet->setCellValue("B{$row}", number_format($totalBalance, 2) . 'h');
        $summarySheet->getStyle("A{$row}:B{$row}")->getFont()->setBold(true);
        $summarySheet->getStyle("B{$row}")->getFont()->getColor()
            ->setARGB($totalBalance > 0 ? 'FF008000' : ($totalBalance < 0 ? 'FFFF0000' : 'FF000000'));

        // Details sheet
        $detailsSheet = $spreadsheet->createSheet();
        $detailsSheet->setTitle('Detalhes');

        $headers = ['Data', 'Funcionário', 'Departamento', 'Entrada', 'Saída', 'Trabalhado', 'Esperado', 'Saldo', 'Observações'];
        $this->createTableHeader($detailsSheet, $headers, 1);

        $row = 2;
        foreach ($data as $record) {
            $detailsSheet->setCellValue("A{$row}", date('d/m/Y', strtotime($record['date'])));
            $detailsSheet->setCellValue("B{$row}", $record['employee_name']);
            $detailsSheet->setCellValue("C{$row}", $record['department']);
            $detailsSheet->setCellValue("D{$row}", $record['first_punch'] ?? '-');
            $detailsSheet->setCellValue("E{$row}", $record['last_punch'] ?? '-');
            $detailsSheet->setCellValue("F{$row}", number_format($record['total_worked'], 2));
            $detailsSheet->setCellValue("G{$row}", number_format($record['expected'], 2));
            $detailsSheet->setCellValue("H{$row}", number_format($record['balance'], 2));
            $detailsSheet->setCellValue("I{$row}", $record['notes'] ?? '');

            // Conditional formatting for balance
            if ($record['balance'] > 0) {
                $detailsSheet->getStyle("H{$row}")->getFont()->getColor()->setARGB('FF008000');
            } elseif ($record['balance'] < 0) {
                $detailsSheet->getStyle("H{$row}")->getFont()->getColor()->setARGB('FFFF0000');
            }

            $row++;
        }

        // Auto-filter
        $detailsSheet->setAutoFilter("A1:I" . ($row - 1));

        // Column widths
        $this->autoSizeColumns($detailsSheet, 'I');

        return $this->saveExcel($spreadsheet, 'relatorio_folha_ponto_' . date('Y-m-d_His') . '.xlsx');
    }

    /**
     * Generate overtime Excel
     */
    protected function generateOvertimeExcel(array $data, array $filters): array
    {
        $spreadsheet = new Spreadsheet();

        // Summary sheet
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Resumo');

        $this->createHeader($summarySheet, 'Relatório de Horas Extras');
        $this->renderFilters($summarySheet, $filters, 4);

        $row = 7;
        $totalExtra = array_sum(array_column($data, 'extra'));
        $totalExtra50 = $totalExtra * 1.5;

        $summarySheet->setCellValue("A{$row}", 'Total Horas Extras:');
        $summarySheet->setCellValue("B{$row}", number_format($totalExtra, 2) . 'h');
        $summarySheet->getStyle("A{$row}:B{$row}")->getFont()->setBold(true);
        $summarySheet->getStyle("B{$row}")->getFont()->getColor()->setARGB('FF008000');

        $row++;
        $summarySheet->setCellValue("A{$row}", 'Com Adicional 50%:');
        $summarySheet->setCellValue("B{$row}", number_format($totalExtra50, 2) . 'h');
        $summarySheet->getStyle("A{$row}:B{$row}")->getFont()->setBold(true);

        // Details sheet
        $detailsSheet = $spreadsheet->createSheet();
        $detailsSheet->setTitle('Detalhes');

        $headers = ['Data', 'Funcionário', 'Departamento', 'Trabalhado', 'Esperado', 'Extras', 'Extra 50%', 'Tipo'];
        $this->createTableHeader($detailsSheet, $headers, 1);

        $row = 2;
        foreach ($data as $record) {
            $detailsSheet->setCellValue("A{$row}", date('d/m/Y', strtotime($record['date'])));
            $detailsSheet->setCellValue("B{$row}", $record['employee_name']);
            $detailsSheet->setCellValue("C{$row}", $record['department']);
            $detailsSheet->setCellValue("D{$row}", number_format($record['total_worked'], 2));
            $detailsSheet->setCellValue("E{$row}", number_format($record['expected'], 2));
            $detailsSheet->setCellValue("F{$row}", number_format($record['extra'], 2));
            $detailsSheet->setCellValue("G{$row}", "=F{$row}*1.5"); // Formula
            $detailsSheet->setCellValue("H{$row}", $record['is_weekend'] ? 'Fim de semana' : 'Dia útil');

            $detailsSheet->getStyle("F{$row}")->getFont()->getColor()->setARGB('FF008000');

            $row++;
        }

        // Total row with formulas
        $detailsSheet->setCellValue("E{$row}", 'TOTAL:');
        $detailsSheet->setCellValue("F{$row}", "=SUM(F2:F" . ($row - 1) . ")");
        $detailsSheet->setCellValue("G{$row}", "=SUM(G2:G" . ($row - 1) . ")");
        $detailsSheet->getStyle("E{$row}:G{$row}")->getFont()->setBold(true);

        $detailsSheet->setAutoFilter("A1:H" . ($row - 1));
        $this->autoSizeColumns($detailsSheet, 'H');

        return $this->saveExcel($spreadsheet, 'relatorio_horas_extras_' . date('Y-m-d_His') . '.xlsx');
    }

    /**
     * Generate absence Excel
     */
    protected function generateAbsenceExcel(array $data, array $filters): array
    {
        $spreadsheet = new Spreadsheet();

        // Summary sheet
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Resumo');

        $this->createHeader($summarySheet, 'Relatório de Faltas e Atrasos');
        $this->renderFilters($summarySheet, $filters, 4);

        $row = 7;
        $faltas = count(array_filter($data, fn($r) => $r['type'] === 'falta'));
        $atrasos = count(array_filter($data, fn($r) => $r['type'] === 'atraso'));
        $justificados = count(array_filter($data, fn($r) => $r['justified']));

        $summarySheet->setCellValue("A{$row}", 'Total Faltas:');
        $summarySheet->setCellValue("B{$row}", $faltas);
        $row++;
        $summarySheet->setCellValue("A{$row}", 'Total Atrasos:');
        $summarySheet->setCellValue("B{$row}", $atrasos);
        $row++;
        $summarySheet->setCellValue("A{$row}", 'Justificados:');
        $summarySheet->setCellValue("B{$row}", $justificados);
        $row++;
        $summarySheet->setCellValue("A{$row}", 'Não Justificados:');
        $summarySheet->setCellValue("B{$row}", count($data) - $justificados);

        // Details sheet
        $detailsSheet = $spreadsheet->createSheet();
        $detailsSheet->setTitle('Detalhes');

        $headers = ['Data', 'Funcionário', 'Departamento', 'Tipo', 'Horário', 'Esperado', 'Atraso (min)', 'Status'];
        $this->createTableHeader($detailsSheet, $headers, 1);

        $row = 2;
        foreach ($data as $record) {
            $detailsSheet->setCellValue("A{$row}", date('d/m/Y', strtotime($record['date'])));
            $detailsSheet->setCellValue("B{$row}", $record['employee_name']);
            $detailsSheet->setCellValue("C{$row}", $record['department']);
            $detailsSheet->setCellValue("D{$row}", ucfirst($record['type']));
            $detailsSheet->setCellValue("E{$row}", $record['punch_time'] ?? '-');
            $detailsSheet->setCellValue("F{$row}", $record['expected_time'] ?? '-');
            $detailsSheet->setCellValue("G{$row}", $record['delay_minutes'] ?? 0);
            $detailsSheet->setCellValue("H{$row}", $record['justified'] ? 'Justificado' : 'Pendente');

            // Color code type
            $color = $record['type'] === 'falta' ? 'FFDC3545' : 'FFFFC107';
            $detailsSheet->getStyle("D{$row}")->getFont()->getColor()->setARGB($color);

            $row++;
        }

        $detailsSheet->setAutoFilter("A1:H" . ($row - 1));
        $this->autoSizeColumns($detailsSheet, 'H');

        return $this->saveExcel($spreadsheet, 'relatorio_faltas_atrasos_' . date('Y-m-d_His') . '.xlsx');
    }

    /**
     * Generate bank hours Excel
     */
    protected function generateBankHoursExcel(array $data, array $filters): array
    {
        $spreadsheet = new Spreadsheet();

        // Summary sheet
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Resumo');

        $this->createHeader($summarySheet, 'Relatório de Banco de Horas');
        $this->renderFilters($summarySheet, $filters, 4);

        $row = 7;
        $totalExtra = array_sum(array_column($data, 'extra_hours_balance'));
        $totalOwed = array_sum(array_column($data, 'owed_hours_balance'));
        $totalBalance = $totalExtra - $totalOwed;

        $summarySheet->setCellValue("A{$row}", 'Total Extras:');
        $summarySheet->setCellValue("B{$row}", '+' . number_format($totalExtra, 2) . 'h');
        $summarySheet->getStyle("B{$row}")->getFont()->getColor()->setARGB('FF008000');
        $row++;

        $summarySheet->setCellValue("A{$row}", 'Total Devidas:');
        $summarySheet->setCellValue("B{$row}", '-' . number_format($totalOwed, 2) . 'h');
        $summarySheet->getStyle("B{$row}")->getFont()->getColor()->setARGB('FFDC3545');
        $row++;

        $summarySheet->setCellValue("A{$row}", 'Saldo Geral:');
        $summarySheet->setCellValue("B{$row}", ($totalBalance > 0 ? '+' : '') . number_format($totalBalance, 2) . 'h');
        $summarySheet->getStyle("A{$row}:B{$row}")->getFont()->setBold(true);
        $summarySheet->getStyle("B{$row}")->getFont()->getColor()
            ->setARGB($totalBalance > 0 ? 'FF008000' : 'FFDC3545');

        // Details sheet
        $detailsSheet = $spreadsheet->createSheet();
        $detailsSheet->setTitle('Detalhes');

        $headers = ['Funcionário', 'Departamento', 'Extras Acumuladas', 'Devidas Acumuladas', 'Saldo Total', 'Status'];
        $this->createTableHeader($detailsSheet, $headers, 1);

        $row = 2;
        foreach ($data as $record) {
            $balance = $record['extra_hours_balance'] - $record['owed_hours_balance'];
            $status = $balance > 0 ? 'Credor' : ($balance < 0 ? 'Devedor' : 'Neutro');

            $detailsSheet->setCellValue("A{$row}", $record['employee_name']);
            $detailsSheet->setCellValue("B{$row}", $record['department']);
            $detailsSheet->setCellValue("C{$row}", number_format($record['extra_hours_balance'], 2));
            $detailsSheet->setCellValue("D{$row}", number_format($record['owed_hours_balance'], 2));
            $detailsSheet->setCellValue("E{$row}", number_format($balance, 2));
            $detailsSheet->setCellValue("F{$row}", $status);

            // Color coding
            $detailsSheet->getStyle("C{$row}")->getFont()->getColor()->setARGB('FF008000');
            $detailsSheet->getStyle("D{$row}")->getFont()->getColor()->setARGB('FFDC3545');
            $detailsSheet->getStyle("E{$row}")->getFont()->getColor()
                ->setARGB($balance > 0 ? 'FF008000' : ($balance < 0 ? 'FFDC3545' : 'FF6C757D'));

            $row++;
        }

        $detailsSheet->setAutoFilter("A1:F" . ($row - 1));
        $this->autoSizeColumns($detailsSheet, 'F');

        return $this->saveExcel($spreadsheet, 'relatorio_banco_horas_' . date('Y-m-d_His') . '.xlsx');
    }

    /**
     * Generate consolidated monthly Excel
     */
    protected function generateConsolidatedExcel(array $data, array $filters): array
    {
        $spreadsheet = new Spreadsheet();

        // Summary sheet
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Resumo');

        $this->createHeader($summarySheet, 'Relatório Consolidado Mensal');
        $this->renderFilters($summarySheet, $filters, 4);

        // Details sheet
        $detailsSheet = $spreadsheet->createSheet();
        $detailsSheet->setTitle('Detalhes');

        $headers = ['Funcionário', 'Depto', 'Dias', 'Trabalhado', 'Esperado', 'Extra', 'Devidas', 'Saldo', 'Atrasos', 'Faltas'];
        $this->createTableHeader($detailsSheet, $headers, 1);

        $row = 2;
        foreach ($data as $record) {
            $balance = $record['extra'] - $record['owed'];

            $detailsSheet->setCellValue("A{$row}", $record['employee_name']);
            $detailsSheet->setCellValue("B{$row}", $record['department']);
            $detailsSheet->setCellValue("C{$row}", $record['days_worked']);
            $detailsSheet->setCellValue("D{$row}", number_format($record['total_worked'], 2));
            $detailsSheet->setCellValue("E{$row}", number_format($record['total_expected'], 2));
            $detailsSheet->setCellValue("F{$row}", number_format($record['extra'], 2));
            $detailsSheet->setCellValue("G{$row}", number_format($record['owed'], 2));
            $detailsSheet->setCellValue("H{$row}", number_format($balance, 2));
            $detailsSheet->setCellValue("I{$row}", $record['late_count'] ?? 0);
            $detailsSheet->setCellValue("J{$row}", $record['absence_count'] ?? 0);

            // Color coding
            $detailsSheet->getStyle("F{$row}")->getFont()->getColor()->setARGB('FF008000');
            $detailsSheet->getStyle("G{$row}")->getFont()->getColor()->setARGB('FFDC3545');
            $detailsSheet->getStyle("H{$row}")->getFont()->getColor()
                ->setARGB($balance > 0 ? 'FF008000' : ($balance < 0 ? 'FFDC3545' : 'FF000000'));

            $row++;
        }

        $detailsSheet->setAutoFilter("A1:J" . ($row - 1));
        $this->autoSizeColumns($detailsSheet, 'J');

        return $this->saveExcel($spreadsheet, 'relatorio_consolidado_' . date('Y-m-d_His') . '.xlsx');
    }

    /**
     * Generate justifications Excel
     */
    protected function generateJustificationsExcel(array $data, array $filters): array
    {
        $spreadsheet = new Spreadsheet();

        // Summary sheet
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Resumo');

        $this->createHeader($summarySheet, 'Relatório de Justificativas');
        $this->renderFilters($summarySheet, $filters, 4);

        $row = 7;
        $pendente = count(array_filter($data, fn($r) => $r['status'] === 'pendente'));
        $aprovado = count(array_filter($data, fn($r) => $r['status'] === 'aprovado'));
        $rejeitado = count(array_filter($data, fn($r) => $r['status'] === 'rejeitado'));

        $summarySheet->setCellValue("A{$row}", 'Total:');
        $summarySheet->setCellValue("B{$row}", count($data));
        $row++;
        $summarySheet->setCellValue("A{$row}", 'Pendentes:');
        $summarySheet->setCellValue("B{$row}", $pendente);
        $row++;
        $summarySheet->setCellValue("A{$row}", 'Aprovadas:');
        $summarySheet->setCellValue("B{$row}", $aprovado);
        $row++;
        $summarySheet->setCellValue("A{$row}", 'Rejeitadas:');
        $summarySheet->setCellValue("B{$row}", $rejeitado);

        // Details sheet
        $detailsSheet = $spreadsheet->createSheet();
        $detailsSheet->setTitle('Detalhes');

        $headers = ['Data', 'Funcionário', 'Tipo', 'Categoria', 'Motivo', 'Status', 'Anexos', 'Criado em'];
        $this->createTableHeader($detailsSheet, $headers, 1);

        $row = 2;
        foreach ($data as $record) {
            $detailsSheet->setCellValue("A{$row}", date('d/m/Y', strtotime($record['justification_date'])));
            $detailsSheet->setCellValue("B{$row}", $record['employee_name']);
            $detailsSheet->setCellValue("C{$row}", ucfirst(str_replace('-', ' ', $record['justification_type'])));
            $detailsSheet->setCellValue("D{$row}", ucfirst(str_replace('-', ' ', $record['category'])));
            $detailsSheet->setCellValue("E{$row}", mb_substr($record['reason'], 0, 100) . '...');
            $detailsSheet->setCellValue("F{$row}", ucfirst($record['status']));
            $detailsSheet->setCellValue("G{$row}", $record['has_attachments'] ? 'Sim' : 'Não');
            $detailsSheet->setCellValue("H{$row}", date('d/m/Y H:i', strtotime($record['created_at'])));

            // Status color
            $statusColor = $record['status'] === 'aprovado' ? 'FF198754' :
                          ($record['status'] === 'rejeitado' ? 'FFDC3545' : 'FFFFC107');
            $detailsSheet->getStyle("F{$row}")->getFont()->getColor()->setARGB($statusColor);

            $row++;
        }

        $detailsSheet->setAutoFilter("A1:H" . ($row - 1));
        $this->autoSizeColumns($detailsSheet, 'H');

        return $this->saveExcel($spreadsheet, 'relatorio_justificativas_' . date('Y-m-d_His') . '.xlsx');
    }

    /**
     * Generate warnings Excel
     */
    protected function generateWarningsExcel(array $data, array $filters): array
    {
        $spreadsheet = new Spreadsheet();

        // Summary sheet
        $summarySheet = $spreadsheet->getActiveSheet();
        $summarySheet->setTitle('Resumo');

        $this->createHeader($summarySheet, 'Relatório de Advertências');
        $this->renderFilters($summarySheet, $filters, 4);

        $row = 7;
        $verbal = count(array_filter($data, fn($r) => $r['warning_type'] === 'verbal'));
        $escrita = count(array_filter($data, fn($r) => $r['warning_type'] === 'escrita'));
        $suspensao = count(array_filter($data, fn($r) => $r['warning_type'] === 'suspensao'));

        $summarySheet->setCellValue("A{$row}", 'Total:');
        $summarySheet->setCellValue("B{$row}", count($data));
        $row++;
        $summarySheet->setCellValue("A{$row}", 'Verbal:');
        $summarySheet->setCellValue("B{$row}", $verbal);
        $row++;
        $summarySheet->setCellValue("A{$row}", 'Escrita:');
        $summarySheet->setCellValue("B{$row}", $escrita);
        $row++;
        $summarySheet->setCellValue("A{$row}", 'Suspensão:');
        $summarySheet->setCellValue("B{$row}", $suspensao);

        // Details sheet
        $detailsSheet = $spreadsheet->createSheet();
        $detailsSheet->setTitle('Detalhes');

        $headers = ['Data', 'Funcionário', 'Departamento', 'Tipo', 'Motivo', 'Status', 'Emitido por'];
        $this->createTableHeader($detailsSheet, $headers, 1);

        $row = 2;
        foreach ($data as $record) {
            $detailsSheet->setCellValue("A{$row}", date('d/m/Y', strtotime($record['date'])));
            $detailsSheet->setCellValue("B{$row}", $record['employee_name']);
            $detailsSheet->setCellValue("C{$row}", $record['department']);
            $detailsSheet->setCellValue("D{$row}", ucfirst($record['warning_type']));
            $detailsSheet->setCellValue("E{$row}", mb_substr($record['reason'], 0, 80) . '...');
            $detailsSheet->setCellValue("F{$row}", ucfirst($record['status']));
            $detailsSheet->setCellValue("G{$row}", $record['issued_by_name'] ?? '-');

            // Type color
            $typeColor = $record['warning_type'] === 'suspensao' ? 'FFDC3545' :
                        ($record['warning_type'] === 'escrita' ? 'FFFFC107' : 'FF17A2B8');
            $detailsSheet->getStyle("D{$row}")->getFont()->getColor()->setARGB($typeColor);

            $row++;
        }

        $detailsSheet->setAutoFilter("A1:G" . ($row - 1));
        $this->autoSizeColumns($detailsSheet, 'G');

        return $this->saveExcel($spreadsheet, 'relatorio_advertencias_' . date('Y-m-d_His') . '.xlsx');
    }

    /**
     * Generate custom Excel
     */
    protected function generateCustomExcel(array $data, array $filters): array
    {
        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Dados');

        $this->createHeader($sheet, 'Relatório Personalizado');
        $this->renderFilters($sheet, $filters, 4);

        if (!empty($data)) {
            $columns = array_keys((array)$data[0]);

            // Headers
            $col = 'A';
            $row = 6;
            foreach ($columns as $column) {
                $sheet->setCellValue("{$col}{$row}", ucfirst(str_replace('_', ' ', $column)));
                $col++;
            }

            $this->styleHeaderRow($sheet, $row, count($columns));

            // Data
            $row++;
            foreach ($data as $record) {
                $col = 'A';
                foreach ($columns as $column) {
                    $value = is_array($record) ? $record[$column] : $record->$column;
                    $sheet->setCellValue("{$col}{$row}", $value);
                    $col++;
                }
                $row++;
            }

            $lastCol = chr(65 + count($columns) - 1);
            $sheet->setAutoFilter("A6:{$lastCol}" . ($row - 1));
            $this->autoSizeColumns($sheet, $lastCol);
        }

        return $this->saveExcel($spreadsheet, 'relatorio_personalizado_' . date('Y-m-d_His') . '.xlsx');
    }

    /**
     * Create header for sheet
     */
    protected function createHeader($sheet, string $title): void
    {
        $sheet->setCellValue('A1', $this->companyName);
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', $title);
        $sheet->mergeCells('A2:F2');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A3', 'Gerado em: ' . date('d/m/Y H:i:s'));
        $sheet->mergeCells('A3:F3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }

    /**
     * Render filters
     */
    protected function renderFilters($sheet, array $filters, int $startRow): void
    {
        if (empty($filters)) {
            return;
        }

        $row = $startRow;
        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $label = ucfirst(str_replace('_', ' ', $key));
            $sheet->setCellValue("A{$row}", $label . ':');
            $sheet->setCellValue("B{$row}", $value);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;
        }
    }

    /**
     * Create table header
     */
    protected function createTableHeader($sheet, array $headers, int $row): void
    {
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue("{$col}{$row}", $header);
            $col++;
        }

        $this->styleHeaderRow($sheet, $row, count($headers));
    }

    /**
     * Style header row
     */
    protected function styleHeaderRow($sheet, int $row, int $colCount): void
    {
        $lastCol = chr(65 + $colCount - 1);
        $range = "A{$row}:{$lastCol}{$row}";

        $sheet->getStyle($range)->getFont()->setBold(true);
        $sheet->getStyle($range)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');
        $sheet->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);
    }

    /**
     * Auto-size columns
     */
    protected function autoSizeColumns($sheet, string $lastCol): void
    {
        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Save Excel to file
     */
    protected function saveExcel(Spreadsheet $spreadsheet, string $filename): array
    {
        $year = date('Y');
        $month = date('m');
        $dir = WRITEPATH . "uploads/reports/{$year}/{$month}/";

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filepath = $dir . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => base_url("uploads/reports/{$year}/{$month}/{$filename}"),
            'size' => filesize($filepath)
        ];
    }
}
