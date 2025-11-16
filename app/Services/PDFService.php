<?php

namespace App\Services;

use TCPDF;

/**
 * PDF Service
 *
 * Generates PDF reports using TCPDF
 * Supports multiple report types with professional formatting
 */
class PDFService
{
    protected $companyName;
    protected $companyLogo;

    public function __construct()
    {
        $settingModel = new \App\Models\SettingModel();
        $this->companyName = $settingModel->get('company_name', 'Sistema de Ponto Eletrônico');
        $this->companyLogo = $settingModel->get('company_logo', null);
    }

    /**
     * Generate report PDF based on type
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
                    return $this->generateTimesheetPDF($data, $filters);
                case 'horas-extras':
                    return $this->generateOvertimePDF($data, $filters);
                case 'faltas-atrasos':
                    return $this->generateAbsencePDF($data, $filters);
                case 'banco-horas':
                    return $this->generateBankHoursPDF($data, $filters);
                case 'consolidado-mensal':
                    return $this->generateConsolidatedPDF($data, $filters);
                case 'justificativas':
                    return $this->generateJustificationsPDF($data, $filters);
                case 'advertencias':
                    return $this->generateWarningsPDF($data, $filters);
                case 'personalizado':
                    return $this->generateCustomPDF($data, $filters);
                default:
                    return [
                        'success' => false,
                        'error' => 'Tipo de relatório inválido'
                    ];
            }
        } catch (\Exception $e) {
            log_message('error', 'PDF generation error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erro ao gerar PDF',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate timesheet PDF
     */
    protected function generateTimesheetPDF(array $data, array $filters): array
    {
        $pdf = $this->createPDF('Relatório de Folha de Ponto');

        // Header info
        $html = $this->renderFilters($filters);

        // Table
        $html .= '<table border="1" cellpadding="4">';
        $html .= '<thead><tr style="background-color: #e0e0e0; font-weight: bold;">
            <th width="8%">Data</th>
            <th width="15%">Funcionário</th>
            <th width="12%">Departamento</th>
            <th width="8%">Entrada</th>
            <th width="8%">Saída</th>
            <th width="10%">Trabalhado</th>
            <th width="10%">Esperado</th>
            <th width="10%">Saldo</th>
            <th width="19%">Observações</th>
        </tr></thead><tbody>';

        foreach ($data as $record) {
            $balanceColor = $record['balance'] > 0 ? '#008000' : ($record['balance'] < 0 ? '#ff0000' : '#000000');

            $html .= '<tr>';
            $html .= '<td>' . date('d/m/Y', strtotime($record['date'])) . '</td>';
            $html .= '<td>' . esc($record['employee_name']) . '</td>';
            $html .= '<td>' . esc($record['department']) . '</td>';
            $html .= '<td>' . ($record['first_punch'] ?? '-') . '</td>';
            $html .= '<td>' . ($record['last_punch'] ?? '-') . '</td>';
            $html .= '<td>' . number_format($record['total_worked'], 2) . 'h</td>';
            $html .= '<td>' . number_format($record['expected'], 2) . 'h</td>';
            $html .= '<td style="color: ' . $balanceColor . ';">' .
                     ($record['balance'] > 0 ? '+' : '') . number_format($record['balance'], 2) . 'h</td>';
            $html .= '<td>' . esc($record['notes'] ?? '-') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        // Summary
        $totalWorked = array_sum(array_column($data, 'total_worked'));
        $totalExpected = array_sum(array_column($data, 'expected'));
        $totalBalance = $totalWorked - $totalExpected;

        $html .= '<br><table><tr>
            <td width="70%"><strong>TOTAIS:</strong></td>
            <td width="10%"><strong>' . number_format($totalWorked, 2) . 'h</strong></td>
            <td width="10%"><strong>' . number_format($totalExpected, 2) . 'h</strong></td>
            <td width="10%"><strong>' . ($totalBalance > 0 ? '+' : '') . number_format($totalBalance, 2) . 'h</strong></td>
        </tr></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        return $this->savePDF($pdf, 'relatorio_folha_ponto_' . date('Y-m-d_His') . '.pdf');
    }

    /**
     * Generate overtime PDF
     */
    protected function generateOvertimePDF(array $data, array $filters): array
    {
        $pdf = $this->createPDF('Relatório de Horas Extras');

        $html = $this->renderFilters($filters);

        $html .= '<table border="1" cellpadding="4">';
        $html .= '<thead><tr style="background-color: #e0e0e0; font-weight: bold;">
            <th width="10%">Data</th>
            <th width="20%">Funcionário</th>
            <th width="15%">Departamento</th>
            <th width="10%">Trabalhado</th>
            <th width="10%">Esperado</th>
            <th width="10%">Extras</th>
            <th width="10%">Extra 50%</th>
            <th width="15%">Tipo</th>
        </tr></thead><tbody>';

        foreach ($data as $record) {
            $html .= '<tr>';
            $html .= '<td>' . date('d/m/Y', strtotime($record['date'])) . '</td>';
            $html .= '<td>' . esc($record['employee_name']) . '</td>';
            $html .= '<td>' . esc($record['department']) . '</td>';
            $html .= '<td>' . number_format($record['total_worked'], 2) . 'h</td>';
            $html .= '<td>' . number_format($record['expected'], 2) . 'h</td>';
            $html .= '<td style="color: #008000;"><strong>' . number_format($record['extra'], 2) . 'h</strong></td>';
            $html .= '<td>' . number_format($record['extra'] * 1.5, 2) . 'h</td>';
            $html .= '<td>' . ($record['is_weekend'] ? 'Fim de semana' : 'Dia útil') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        // Summary
        $totalExtra = array_sum(array_column($data, 'extra'));
        $totalExtra50 = $totalExtra * 1.5;

        $html .= '<br><table><tr>
            <td width="60%"><strong>TOTAL HORAS EXTRAS:</strong></td>
            <td width="20%"><strong style="color: #008000;">' . number_format($totalExtra, 2) . 'h</strong></td>
            <td width="20%"><strong>Com adicional 50%: ' . number_format($totalExtra50, 2) . 'h</strong></td>
        </tr></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        return $this->savePDF($pdf, 'relatorio_horas_extras_' . date('Y-m-d_His') . '.pdf');
    }

    /**
     * Generate absence/late arrivals PDF
     */
    protected function generateAbsencePDF(array $data, array $filters): array
    {
        $pdf = $this->createPDF('Relatório de Faltas e Atrasos');

        $html = $this->renderFilters($filters);

        $html .= '<table border="1" cellpadding="4">';
        $html .= '<thead><tr style="background-color: #e0e0e0; font-weight: bold;">
            <th width="10%">Data</th>
            <th width="20%">Funcionário</th>
            <th width="15%">Departamento</th>
            <th width="10%">Tipo</th>
            <th width="10%">Horário</th>
            <th width="10%">Esperado</th>
            <th width="10%">Atraso</th>
            <th width="15%">Status</th>
        </tr></thead><tbody>';

        foreach ($data as $record) {
            $typeColor = $record['type'] === 'falta' ? '#dc3545' : '#ffc107';

            $html .= '<tr>';
            $html .= '<td>' . date('d/m/Y', strtotime($record['date'])) . '</td>';
            $html .= '<td>' . esc($record['employee_name']) . '</td>';
            $html .= '<td>' . esc($record['department']) . '</td>';
            $html .= '<td style="color: ' . $typeColor . ';"><strong>' . ucfirst($record['type']) . '</strong></td>';
            $html .= '<td>' . ($record['punch_time'] ?? '-') . '</td>';
            $html .= '<td>' . ($record['expected_time'] ?? '-') . '</td>';
            $html .= '<td>' . ($record['delay_minutes'] ?? '-') . '</td>';
            $html .= '<td>' . ($record['justified'] ? 'Justificado' : 'Pendente') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        // Statistics
        $faltas = count(array_filter($data, fn($r) => $r['type'] === 'falta'));
        $atrasos = count(array_filter($data, fn($r) => $r['type'] === 'atraso'));
        $justificados = count(array_filter($data, fn($r) => $r['justified']));

        $html .= '<br><table><tr>
            <td width="25%"><strong>Total Faltas:</strong> ' . $faltas . '</td>
            <td width="25%"><strong>Total Atrasos:</strong> ' . $atrasos . '</td>
            <td width="25%"><strong>Justificados:</strong> ' . $justificados . '</td>
            <td width="25%"><strong>Não Justificados:</strong> ' . (count($data) - $justificados) . '</td>
        </tr></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        return $this->savePDF($pdf, 'relatorio_faltas_atrasos_' . date('Y-m-d_His') . '.pdf');
    }

    /**
     * Generate bank hours PDF
     */
    protected function generateBankHoursPDF(array $data, array $filters): array
    {
        $pdf = $this->createPDF('Relatório de Banco de Horas');

        $html = $this->renderFilters($filters);

        $html .= '<table border="1" cellpadding="4">';
        $html .= '<thead><tr style="background-color: #e0e0e0; font-weight: bold;">
            <th width="20%">Funcionário</th>
            <th width="15%">Departamento</th>
            <th width="15%">Extras Acumuladas</th>
            <th width="15%">Devidas Acumuladas</th>
            <th width="15%">Saldo Total</th>
            <th width="20%">Status</th>
        </tr></thead><tbody>';

        foreach ($data as $record) {
            $balance = $record['extra_hours_balance'] - $record['owed_hours_balance'];
            $balanceColor = $balance > 0 ? '#008000' : ($balance < 0 ? '#dc3545' : '#6c757d');
            $status = $balance > 0 ? 'Credor' : ($balance < 0 ? 'Devedor' : 'Neutro');

            $html .= '<tr>';
            $html .= '<td>' . esc($record['employee_name']) . '</td>';
            $html .= '<td>' . esc($record['department']) . '</td>';
            $html .= '<td style="color: #008000;">+' . number_format($record['extra_hours_balance'], 2) . 'h</td>';
            $html .= '<td style="color: #dc3545;">-' . number_format($record['owed_hours_balance'], 2) . 'h</td>';
            $html .= '<td style="color: ' . $balanceColor . ';"><strong>' .
                     ($balance > 0 ? '+' : '') . number_format($balance, 2) . 'h</strong></td>';
            $html .= '<td>' . $status . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        // Summary
        $totalExtra = array_sum(array_column($data, 'extra_hours_balance'));
        $totalOwed = array_sum(array_column($data, 'owed_hours_balance'));
        $totalBalance = $totalExtra - $totalOwed;

        $html .= '<br><table><tr>
            <td width="33%"><strong>Total Extras:</strong> <span style="color: #008000;">+' . number_format($totalExtra, 2) . 'h</span></td>
            <td width="33%"><strong>Total Devidas:</strong> <span style="color: #dc3545;">-' . number_format($totalOwed, 2) . 'h</span></td>
            <td width="34%"><strong>Saldo Geral:</strong> <span style="color: ' . ($totalBalance > 0 ? '#008000' : '#dc3545') . ';">' .
                 ($totalBalance > 0 ? '+' : '') . number_format($totalBalance, 2) . 'h</span></td>
        </tr></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        return $this->savePDF($pdf, 'relatorio_banco_horas_' . date('Y-m-d_His') . '.pdf');
    }

    /**
     * Generate consolidated monthly PDF
     */
    protected function generateConsolidatedPDF(array $data, array $filters): array
    {
        $pdf = $this->createPDF('Relatório Consolidado Mensal');

        $html = $this->renderFilters($filters);

        $html .= '<table border="1" cellpadding="4">';
        $html .= '<thead><tr style="background-color: #e0e0e0; font-weight: bold;">
            <th width="15%">Funcionário</th>
            <th width="12%">Depto</th>
            <th width="8%">Dias</th>
            <th width="10%">Trabalhado</th>
            <th width="10%">Esperado</th>
            <th width="9%">Extra</th>
            <th width="9%">Devidas</th>
            <th width="9%">Saldo</th>
            <th width="9%">Atrasos</th>
            <th width="9%">Faltas</th>
        </tr></thead><tbody>';

        foreach ($data as $record) {
            $balance = $record['extra'] - $record['owed'];
            $balanceColor = $balance > 0 ? '#008000' : ($balance < 0 ? '#dc3545' : '#000');

            $html .= '<tr>';
            $html .= '<td>' . esc($record['employee_name']) . '</td>';
            $html .= '<td>' . esc($record['department']) . '</td>';
            $html .= '<td>' . $record['days_worked'] . '</td>';
            $html .= '<td>' . number_format($record['total_worked'], 2) . 'h</td>';
            $html .= '<td>' . number_format($record['total_expected'], 2) . 'h</td>';
            $html .= '<td style="color: #008000;">+' . number_format($record['extra'], 2) . 'h</td>';
            $html .= '<td style="color: #dc3545;">-' . number_format($record['owed'], 2) . 'h</td>';
            $html .= '<td style="color: ' . $balanceColor . ';">' .
                     ($balance > 0 ? '+' : '') . number_format($balance, 2) . 'h</td>';
            $html .= '<td>' . ($record['late_count'] ?? 0) . '</td>';
            $html .= '<td>' . ($record['absence_count'] ?? 0) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        return $this->savePDF($pdf, 'relatorio_consolidado_' . date('Y-m-d_His') . '.pdf');
    }

    /**
     * Generate justifications PDF
     */
    protected function generateJustificationsPDF(array $data, array $filters): array
    {
        $pdf = $this->createPDF('Relatório de Justificativas');

        $html = $this->renderFilters($filters);

        $html .= '<table border="1" cellpadding="4">';
        $html .= '<thead><tr style="background-color: #e0e0e0; font-weight: bold;">
            <th width="8%">Data</th>
            <th width="18%">Funcionário</th>
            <th width="12%">Tipo</th>
            <th width="12%">Categoria</th>
            <th width="30%">Motivo</th>
            <th width="10%">Status</th>
            <th width="10%">Anexos</th>
        </tr></thead><tbody>';

        foreach ($data as $record) {
            $statusColor = $record['status'] === 'aprovado' ? '#198754' :
                          ($record['status'] === 'rejeitado' ? '#dc3545' : '#ffc107');

            $html .= '<tr>';
            $html .= '<td>' . date('d/m/Y', strtotime($record['justification_date'])) . '</td>';
            $html .= '<td>' . esc($record['employee_name']) . '</td>';
            $html .= '<td>' . ucfirst(str_replace('-', ' ', $record['justification_type'])) . '</td>';
            $html .= '<td>' . ucfirst(str_replace('-', ' ', $record['category'])) . '</td>';
            $html .= '<td>' . esc(mb_substr($record['reason'], 0, 80)) . '...</td>';
            $html .= '<td style="color: ' . $statusColor . ';"><strong>' . ucfirst($record['status']) . '</strong></td>';
            $html .= '<td>' . ($record['has_attachments'] ? 'Sim' : 'Não') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        // Statistics
        $pendente = count(array_filter($data, fn($r) => $r['status'] === 'pendente'));
        $aprovado = count(array_filter($data, fn($r) => $r['status'] === 'aprovado'));
        $rejeitado = count(array_filter($data, fn($r) => $r['status'] === 'rejeitado'));

        $html .= '<br><table><tr>
            <td width="25%"><strong>Total:</strong> ' . count($data) . '</td>
            <td width="25%"><strong style="color: #ffc107;">Pendentes:</strong> ' . $pendente . '</td>
            <td width="25%"><strong style="color: #198754;">Aprovadas:</strong> ' . $aprovado . '</td>
            <td width="25%"><strong style="color: #dc3545;">Rejeitadas:</strong> ' . $rejeitado . '</td>
        </tr></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        return $this->savePDF($pdf, 'relatorio_justificativas_' . date('Y-m-d_His') . '.pdf');
    }

    /**
     * Generate warnings PDF
     */
    protected function generateWarningsPDF(array $data, array $filters): array
    {
        $pdf = $this->createPDF('Relatório de Advertências');

        $html = $this->renderFilters($filters);

        $html .= '<table border="1" cellpadding="4">';
        $html .= '<thead><tr style="background-color: #e0e0e0; font-weight: bold;">
            <th width="10%">Data</th>
            <th width="20%">Funcionário</th>
            <th width="12%">Departamento</th>
            <th width="12%">Tipo</th>
            <th width="30%">Motivo</th>
            <th width="16%">Status</th>
        </tr></thead><tbody>';

        foreach ($data as $record) {
            $typeColor = $record['warning_type'] === 'suspensao' ? '#dc3545' :
                        ($record['warning_type'] === 'escrita' ? '#ffc107' : '#17a2b8');

            $html .= '<tr>';
            $html .= '<td>' . date('d/m/Y', strtotime($record['date'])) . '</td>';
            $html .= '<td>' . esc($record['employee_name']) . '</td>';
            $html .= '<td>' . esc($record['department']) . '</td>';
            $html .= '<td style="color: ' . $typeColor . ';"><strong>' . ucfirst($record['warning_type']) . '</strong></td>';
            $html .= '<td>' . esc(mb_substr($record['reason'], 0, 60)) . '...</td>';
            $html .= '<td>' . ucfirst($record['status']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        // Statistics by type
        $verbal = count(array_filter($data, fn($r) => $r['warning_type'] === 'verbal'));
        $escrita = count(array_filter($data, fn($r) => $r['warning_type'] === 'escrita'));
        $suspensao = count(array_filter($data, fn($r) => $r['warning_type'] === 'suspensao'));

        $html .= '<br><table><tr>
            <td width="25%"><strong>Total:</strong> ' . count($data) . '</td>
            <td width="25%"><strong>Verbal:</strong> ' . $verbal . '</td>
            <td width="25%"><strong>Escrita:</strong> ' . $escrita . '</td>
            <td width="25%"><strong>Suspensão:</strong> ' . $suspensao . '</td>
        </tr></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        return $this->savePDF($pdf, 'relatorio_advertencias_' . date('Y-m-d_His') . '.pdf');
    }

    /**
     * Generate custom PDF
     */
    protected function generateCustomPDF(array $data, array $filters): array
    {
        $pdf = $this->createPDF('Relatório Personalizado');

        $html = $this->renderFilters($filters);

        // Dynamic table based on data structure
        if (!empty($data)) {
            $columns = array_keys((array)$data[0]);
            $colWidth = floor(100 / count($columns));

            $html .= '<table border="1" cellpadding="4">';
            $html .= '<thead><tr style="background-color: #e0e0e0; font-weight: bold;">';

            foreach ($columns as $col) {
                $html .= '<th width="' . $colWidth . '%">' . ucfirst(str_replace('_', ' ', $col)) . '</th>';
            }

            $html .= '</tr></thead><tbody>';

            foreach ($data as $record) {
                $html .= '<tr>';
                foreach ($columns as $col) {
                    $value = is_array($record) ? $record[$col] : $record->$col;
                    $html .= '<td>' . esc($value) . '</td>';
                }
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        } else {
            $html .= '<p>Nenhum dado encontrado para os filtros aplicados.</p>';
        }

        $pdf->writeHTML($html, true, false, true, false, '');

        return $this->savePDF($pdf, 'relatorio_personalizado_' . date('Y-m-d_His') . '.pdf');
    }

    /**
     * Create PDF instance with standard formatting
     */
    protected function createPDF(string $title): TCPDF
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator($this->companyName);
        $pdf->SetAuthor($this->companyName);
        $pdf->SetTitle($title);
        $pdf->SetSubject($title);

        // Set header and footer
        $pdf->SetHeaderData('', 0, $this->companyName, $title . "\n" . date('d/m/Y H:i:s'));
        $pdf->setFooterData([0,0,0], [0,0,0]);

        // Set header and footer fonts
        $pdf->setHeaderFont(['helvetica', '', 10]);
        $pdf->setFooterFont(['helvetica', '', 8]);

        // Set margins
        $pdf->SetMargins(15, 30, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);

        // Set auto page breaks
        $pdf->SetAutoPageBreak(true, 15);

        // Set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // Set font
        $pdf->SetFont('helvetica', '', 9);

        // Add page
        $pdf->AddPage();

        return $pdf;
    }

    /**
     * Render filters information
     */
    protected function renderFilters(array $filters): string
    {
        if (empty($filters)) {
            return '';
        }

        $html = '<div style="background-color: #f8f9fa; padding: 10px; margin-bottom: 15px; border: 1px solid #dee2e6;">';
        $html .= '<strong>Filtros Aplicados:</strong><br>';

        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $label = ucfirst(str_replace('_', ' ', $key));
            $html .= '<span style="font-size: 8pt;">' . $label . ': ' . esc($value) . ' | </span>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Save PDF to file
     */
    protected function savePDF(TCPDF $pdf, string $filename): array
    {
        $year = date('Y');
        $month = date('m');
        $dir = WRITEPATH . "uploads/reports/{$year}/{$month}/";

        // Create directory if not exists
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filepath = $dir . $filename;

        // Output to file
        $pdf->Output($filepath, 'F');

        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => base_url("uploads/reports/{$year}/{$month}/{$filename}"),
            'size' => filesize($filepath)
        ];
    }

    /**
     * Sign PDF with ICP-Brasil certificate (if configured)
     *
     * @param string $filepath Path to PDF file
     * @return bool Success status
     */
    public function signPDF(string $filepath): bool
    {
        $certPath = env('ICP_CERTIFICATE_PATH');
        $keyPath = env('ICP_KEY_PATH');
        $password = env('ICP_KEY_PASSWORD');

        if (!$certPath || !$keyPath || !file_exists($certPath) || !file_exists($keyPath)) {
            log_message('warning', 'ICP certificate not configured for PDF signing');
            return false;
        }

        try {
            $cert = file_get_contents($certPath);
            $key = file_get_contents($keyPath);

            $signedFile = $filepath . '.signed';

            $result = openssl_pkcs7_sign(
                $filepath,
                $signedFile,
                $cert,
                [$key, $password],
                [],
                PKCS7_BINARY | PKCS7_DETACHED
            );

            if ($result) {
                // Replace original with signed
                rename($signedFile, $filepath);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            log_message('error', 'PDF signing error: ' . $e->getMessage());
            return false;
        }
    }
}
