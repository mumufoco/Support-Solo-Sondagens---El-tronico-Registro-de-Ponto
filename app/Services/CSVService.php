<?php

namespace App\Services;

/**
 * CSV Service
 *
 * Generates CSV reports with Excel Brasil compatibility
 * - Delimiter: semicolon (;)
 * - Encoding: UTF-8 with BOM
 * - Proper escaping of quotes and line breaks
 */
class CSVService
{
    protected $delimiter = ';';
    protected $enclosure = '"';
    protected $escapeChar = '\\';

    /**
     * Generate report CSV based on type
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
                    return $this->generateTimesheetCSV($data, $filters);
                case 'horas-extras':
                    return $this->generateOvertimeCSV($data, $filters);
                case 'faltas-atrasos':
                    return $this->generateAbsenceCSV($data, $filters);
                case 'banco-horas':
                    return $this->generateBankHoursCSV($data, $filters);
                case 'consolidado-mensal':
                    return $this->generateConsolidatedCSV($data, $filters);
                case 'justificativas':
                    return $this->generateJustificationsCSV($data, $filters);
                case 'advertencias':
                    return $this->generateWarningsCSV($data, $filters);
                case 'personalizado':
                    return $this->generateCustomCSV($data, $filters);
                default:
                    return [
                        'success' => false,
                        'error' => 'Tipo de relatório inválido'
                    ];
            }
        } catch (\Exception $e) {
            log_message('error', 'CSV generation error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erro ao gerar CSV',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate timesheet CSV
     */
    protected function generateTimesheetCSV(array $data, array $filters): array
    {
        $headers = [
            'Data',
            'Funcionário',
            'Departamento',
            'Entrada',
            'Saída',
            'Trabalhado (h)',
            'Esperado (h)',
            'Saldo (h)',
            'Observações'
        ];

        $rows = [];
        foreach ($data as $record) {
            $rows[] = [
                date('d/m/Y', strtotime($record['date'])),
                $record['employee_name'],
                $record['department'],
                $record['first_punch'] ?? '-',
                $record['last_punch'] ?? '-',
                number_format($record['total_worked'], 2, ',', '.'),
                number_format($record['expected'], 2, ',', '.'),
                ($record['balance'] > 0 ? '+' : '') . number_format($record['balance'], 2, ',', '.'),
                $record['notes'] ?? ''
            ];
        }

        return $this->writeCSV('relatorio_folha_ponto_' . date('Y-m-d_His') . '.csv', $headers, $rows);
    }

    /**
     * Generate overtime CSV
     */
    protected function generateOvertimeCSV(array $data, array $filters): array
    {
        $headers = [
            'Data',
            'Funcionário',
            'Departamento',
            'Trabalhado (h)',
            'Esperado (h)',
            'Extras (h)',
            'Extra 50% (h)',
            'Tipo'
        ];

        $rows = [];
        foreach ($data as $record) {
            $extra50 = $record['extra'] * 1.5;
            $rows[] = [
                date('d/m/Y', strtotime($record['date'])),
                $record['employee_name'],
                $record['department'],
                number_format($record['total_worked'], 2, ',', '.'),
                number_format($record['expected'], 2, ',', '.'),
                number_format($record['extra'], 2, ',', '.'),
                number_format($extra50, 2, ',', '.'),
                $record['is_weekend'] ? 'Fim de semana' : 'Dia útil'
            ];
        }

        return $this->writeCSV('relatorio_horas_extras_' . date('Y-m-d_His') . '.csv', $headers, $rows);
    }

    /**
     * Generate absence CSV
     */
    protected function generateAbsenceCSV(array $data, array $filters): array
    {
        $headers = [
            'Data',
            'Funcionário',
            'Departamento',
            'Tipo',
            'Horário',
            'Esperado',
            'Atraso (min)',
            'Status'
        ];

        $rows = [];
        foreach ($data as $record) {
            $rows[] = [
                date('d/m/Y', strtotime($record['date'])),
                $record['employee_name'],
                $record['department'],
                ucfirst($record['type']),
                $record['punch_time'] ?? '-',
                $record['expected_time'] ?? '-',
                $record['delay_minutes'] ?? '0',
                $record['justified'] ? 'Justificado' : 'Pendente'
            ];
        }

        return $this->writeCSV('relatorio_faltas_atrasos_' . date('Y-m-d_His') . '.csv', $headers, $rows);
    }

    /**
     * Generate bank hours CSV
     */
    protected function generateBankHoursCSV(array $data, array $filters): array
    {
        $headers = [
            'Funcionário',
            'Departamento',
            'Extras Acumuladas (h)',
            'Devidas Acumuladas (h)',
            'Saldo Total (h)',
            'Status'
        ];

        $rows = [];
        foreach ($data as $record) {
            $balance = $record['extra_hours_balance'] - $record['owed_hours_balance'];
            $status = $balance > 0 ? 'Credor' : ($balance < 0 ? 'Devedor' : 'Neutro');

            $rows[] = [
                $record['employee_name'],
                $record['department'],
                number_format($record['extra_hours_balance'], 2, ',', '.'),
                number_format($record['owed_hours_balance'], 2, ',', '.'),
                ($balance > 0 ? '+' : '') . number_format($balance, 2, ',', '.'),
                $status
            ];
        }

        return $this->writeCSV('relatorio_banco_horas_' . date('Y-m-d_His') . '.csv', $headers, $rows);
    }

    /**
     * Generate consolidated monthly CSV
     */
    protected function generateConsolidatedCSV(array $data, array $filters): array
    {
        $headers = [
            'Funcionário',
            'Departamento',
            'Dias Trabalhados',
            'Horas Trabalhadas',
            'Horas Esperadas',
            'Horas Extras',
            'Horas Devidas',
            'Saldo',
            'Atrasos',
            'Faltas'
        ];

        $rows = [];
        foreach ($data as $record) {
            $balance = $record['extra'] - $record['owed'];

            $rows[] = [
                $record['employee_name'],
                $record['department'],
                $record['days_worked'],
                number_format($record['total_worked'], 2, ',', '.'),
                number_format($record['total_expected'], 2, ',', '.'),
                number_format($record['extra'], 2, ',', '.'),
                number_format($record['owed'], 2, ',', '.'),
                ($balance > 0 ? '+' : '') . number_format($balance, 2, ',', '.'),
                $record['late_count'] ?? '0',
                $record['absence_count'] ?? '0'
            ];
        }

        return $this->writeCSV('relatorio_consolidado_' . date('Y-m-d_His') . '.csv', $headers, $rows);
    }

    /**
     * Generate justifications CSV
     */
    protected function generateJustificationsCSV(array $data, array $filters): array
    {
        $headers = [
            'Data',
            'Funcionário',
            'Tipo',
            'Categoria',
            'Motivo',
            'Status',
            'Possui Anexos',
            'Criado em'
        ];

        $rows = [];
        foreach ($data as $record) {
            $rows[] = [
                date('d/m/Y', strtotime($record['justification_date'])),
                $record['employee_name'],
                ucfirst(str_replace('-', ' ', $record['justification_type'])),
                ucfirst(str_replace('-', ' ', $record['category'])),
                $this->truncate($record['reason'], 200),
                ucfirst($record['status']),
                $record['has_attachments'] ? 'Sim' : 'Não',
                date('d/m/Y H:i', strtotime($record['created_at']))
            ];
        }

        return $this->writeCSV('relatorio_justificativas_' . date('Y-m-d_His') . '.csv', $headers, $rows);
    }

    /**
     * Generate warnings CSV
     */
    protected function generateWarningsCSV(array $data, array $filters): array
    {
        $headers = [
            'Data',
            'Funcionário',
            'Departamento',
            'Tipo',
            'Motivo',
            'Status',
            'Emitido por'
        ];

        $rows = [];
        foreach ($data as $record) {
            $rows[] = [
                date('d/m/Y', strtotime($record['date'])),
                $record['employee_name'],
                $record['department'],
                ucfirst($record['warning_type']),
                $this->truncate($record['reason'], 200),
                ucfirst($record['status']),
                $record['issued_by_name'] ?? '-'
            ];
        }

        return $this->writeCSV('relatorio_advertencias_' . date('Y-m-d_His') . '.csv', $headers, $rows);
    }

    /**
     * Generate custom CSV
     */
    protected function generateCustomCSV(array $data, array $filters): array
    {
        if (empty($data)) {
            return [
                'success' => false,
                'error' => 'Nenhum dado para exportar'
            ];
        }

        // Extract headers from first record
        $firstRecord = (array) $data[0];
        $headers = array_map(function($key) {
            return ucfirst(str_replace('_', ' ', $key));
        }, array_keys($firstRecord));

        $rows = [];
        foreach ($data as $record) {
            $recordArray = (array) $record;
            $rows[] = array_values($recordArray);
        }

        return $this->writeCSV('relatorio_personalizado_' . date('Y-m-d_His') . '.csv', $headers, $rows);
    }

    /**
     * Write CSV file
     *
     * @param string $filename Filename
     * @param array $headers Column headers
     * @param array $rows Data rows
     * @return array Result
     */
    protected function writeCSV(string $filename, array $headers, array $rows): array
    {
        $year = date('Y');
        $month = date('m');
        $dir = WRITEPATH . "uploads/reports/{$year}/{$month}/";

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filepath = $dir . $filename;

        // Open file for writing
        $file = fopen($filepath, 'w');

        if (!$file) {
            return [
                'success' => false,
                'error' => 'Não foi possível criar o arquivo CSV'
            ];
        }

        // Write UTF-8 BOM for Excel compatibility
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        // Write headers
        $this->writeRow($file, $headers);

        // Write data rows
        foreach ($rows as $row) {
            $this->writeRow($file, $row);
        }

        fclose($file);

        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => base_url("uploads/reports/{$year}/{$month}/{$filename}"),
            'size' => filesize($filepath)
        ];
    }

    /**
     * Write a single row to CSV
     *
     * @param resource $file File handle
     * @param array $fields Field values
     */
    protected function writeRow($file, array $fields): void
    {
        $row = [];

        foreach ($fields as $field) {
            // Convert to string
            $field = (string) $field;

            // Escape quotes
            $field = str_replace($this->enclosure, $this->enclosure . $this->enclosure, $field);

            // Enclose if contains delimiter, enclosure, or newline
            if (
                strpos($field, $this->delimiter) !== false ||
                strpos($field, $this->enclosure) !== false ||
                strpos($field, "\n") !== false ||
                strpos($field, "\r") !== false
            ) {
                $field = $this->enclosure . $field . $this->enclosure;
            }

            $row[] = $field;
        }

        fwrite($file, implode($this->delimiter, $row) . "\n");
    }

    /**
     * Truncate text with ellipsis
     *
     * @param string $text Text to truncate
     * @param int $length Max length
     * @return string Truncated text
     */
    protected function truncate(string $text, int $length): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length - 3) . '...';
    }

    /**
     * Escape special characters for CSV
     *
     * @param string $value Value to escape
     * @return string Escaped value
     */
    protected function escape(string $value): string
    {
        // Remove any existing carriage returns
        $value = str_replace("\r", '', $value);

        // Replace newlines with space
        $value = str_replace("\n", ' ', $value);

        // Escape quotes
        $value = str_replace('"', '""', $value);

        return $value;
    }
}
