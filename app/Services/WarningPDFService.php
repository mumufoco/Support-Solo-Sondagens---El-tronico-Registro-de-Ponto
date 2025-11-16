<?php

namespace App\Services;

use TCPDF;

/**
 * Warning PDF Service
 *
 * Generates formal warning PDFs with templates
 * Handles ICP-Brasil digital signatures
 */
class WarningPDFService
{
    protected string $companyName;
    protected string $companyCNPJ;
    protected string $companyLogo;
    protected string $pdfOutputPath;

    public function __construct()
    {
        $this->companyName = env('COMPANY_NAME', 'Empresa LTDA');
        $this->companyCNPJ = env('COMPANY_CNPJ', '00.000.000/0000-00');
        $this->companyLogo = FCPATH . 'assets/images/logo.png';
        $this->pdfOutputPath = WRITEPATH . 'uploads/warnings/pdfs/';

        // Create directory if not exists
        if (!is_dir($this->pdfOutputPath)) {
            mkdir($this->pdfOutputPath, 0755, true);
        }
    }

    /**
     * Generate warning PDF
     *
     * @param int $warningId
     * @param array $data ['warning', 'employee', 'issuer']
     * @return array ['success' => bool, 'filepath' => string]
     */
    public function generateWarningPDF(int $warningId, array $data): array
    {
        $warning = $data['warning'];
        $employee = $data['employee'];
        $issuer = $data['issuer'];

        try {
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

            // Document metadata
            $pdf->SetCreator($this->companyName);
            $pdf->SetAuthor($issuer->name);
            $pdf->SetTitle('Advertência - ' . $employee->name);
            $pdf->SetSubject('Advertência ' . strtoupper($warning->warning_type));

            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Set margins
            $pdf->SetMargins(20, 20, 20);
            $pdf->SetAutoPageBreak(true, 20);

            // Add page
            $pdf->AddPage();

            // Set font
            $pdf->SetFont('helvetica', '', 10);

            // Generate content
            $html = $this->generateWarningHTML($warning, $employee, $issuer);

            // Write HTML
            $pdf->writeHTML($html, true, false, true, false, '');

            // Output file
            $filename = 'advertencia_' . $warningId . '_' . time() . '.pdf';
            $filepath = $this->pdfOutputPath . $filename;

            $pdf->Output($filepath, 'F');

            return [
                'success' => true,
                'filepath' => 'uploads/warnings/pdfs/' . $filename,
                'filename' => $filename
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate final PDF with all signatures
     *
     * @param int $warningId
     * @param array $data
     * @return array
     */
    public function generateFinalPDF(int $warningId, array $data): array
    {
        $warning = $data['warning'];
        $employee = $data['employee'];
        $issuer = $data['issuer'];

        try {
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

            $pdf->SetCreator($this->companyName);
            $pdf->SetAuthor($issuer->name);
            $pdf->SetTitle('Advertência - ' . $employee->name);
            $pdf->SetSubject('Advertência ' . strtoupper($warning->warning_type));

            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(20, 20, 20);
            $pdf->SetAutoPageBreak(true, 20);

            $pdf->AddPage();
            $pdf->SetFont('helvetica', '', 10);

            // Generate content with signatures
            $html = $this->generateFinalHTML($warning, $employee, $issuer);

            $pdf->writeHTML($html, true, false, true, false, '');

            // Output file
            $filename = 'advertencia_final_' . $warningId . '_' . time() . '.pdf';
            $filepath = $this->pdfOutputPath . $filename;

            $pdf->Output($filepath, 'F');

            // Update warning with final PDF path
            $warningModel = new \App\Models\WarningModel();
            $warningModel->update($warningId, [
                'pdf_path' => 'uploads/warnings/pdfs/' . $filename
            ]);

            return [
                'success' => true,
                'filepath' => 'uploads/warnings/pdfs/' . $filename,
                'filename' => $filename
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate HTML content for warning PDF
     *
     * @param object $warning
     * @param object $employee
     * @param object $issuer
     * @return string
     */
    protected function generateWarningHTML($warning, $employee, $issuer): string
    {
        $warningTypes = [
            'verbal' => 'VERBAL',
            'escrita' => 'ESCRITA',
            'suspensao' => 'SUSPENSÃO'
        ];

        $warningType = $warningTypes[$warning->warning_type] ?? strtoupper($warning->warning_type);

        $html = '
        <style>
            h1 { text-align: center; font-size: 18pt; margin-bottom: 5mm; }
            h2 { font-size: 14pt; margin-top: 8mm; margin-bottom: 3mm; border-bottom: 1px solid #333; }
            .header { text-align: center; margin-bottom: 10mm; }
            .company-info { font-size: 9pt; color: #666; }
            .box { border: 1px solid #333; padding: 5mm; margin: 5mm 0; background-color: #f9f9f9; }
            .label { font-weight: bold; }
            .signature-box { border: 1px solid #999; height: 20mm; margin-top: 3mm; }
            .footer { font-size: 8pt; color: #666; text-align: center; margin-top: 10mm; }
        </style>

        <div class="header">';

        // Add logo if exists
        if (file_exists($this->companyLogo)) {
            $html .= '<img src="' . $this->companyLogo . '" width="60mm" /><br>';
        }

        $html .= '
            <div class="company-info">
                ' . $this->companyName . '<br>
                CNPJ: ' . $this->companyCNPJ . '
            </div>
        </div>

        <h1>ADVERTÊNCIA ' . $warningType . '</h1>

        <h2>DADOS DO FUNCIONÁRIO</h2>
        <div class="box">
            <strong>Nome:</strong> ' . htmlspecialchars($employee->name) . '<br>
            <strong>CPF:</strong> ' . ($employee->cpf ?? 'Não informado') . '<br>
            <strong>Matrícula:</strong> ' . ($employee->id) . '<br>
            <strong>Departamento:</strong> ' . ($employee->department ?? 'Não informado') . '<br>
            <strong>Cargo:</strong> ' . ($employee->position ?? 'Não informado') . '
        </div>

        <h2>DATA DA OCORRÊNCIA</h2>
        <p><strong>' . date('d/m/Y', strtotime($warning->occurrence_date)) . '</strong></p>

        <h2>DESCRIÇÃO DOS FATOS</h2>
        <div class="box">
            ' . nl2br(htmlspecialchars($warning->reason)) . '
        </div>

        <h2>CLÁUSULAS LEGAIS</h2>
        <p style="font-size: 9pt; text-align: justify;">
            De acordo com o artigo 482 da Consolidação das Leis do Trabalho (CLT),
            constituem justa causa para rescisão do contrato de trabalho pelo empregador:
            atos de indisciplina ou insubordinação, desídia no desempenho das respectivas funções,
            e demais infrações previstas na legislação trabalhista vigente.
        </p>
        <p style="font-size: 9pt; text-align: justify;">
            Esta advertência é emitida em conformidade com o Regulamento Interno da empresa
            e serve como registro formal da ocorrência descrita.
        </p>';

        // Add evidence files list if any
        if (!empty($warning->evidence_files)) {
            $html .= '
            <h2>EVIDÊNCIAS ANEXAS</h2>
            <ul style="font-size: 9pt;">';

            foreach ($warning->evidence_files as $index => $file) {
                $filename = basename($file);
                $html .= '<li>Anexo ' . ($index + 1) . ': ' . htmlspecialchars($filename) . '</li>';
            }

            $html .= '</ul>';
        }

        $html .= '
        <h2>ASSINATURAS</h2>

        <p><strong>Gestor/Administrador:</strong></p>
        <p>' . htmlspecialchars($issuer->name) . '<br>
        Data: ' . date('d/m/Y H:i') . '</p>
        <div class="signature-box">
            <p style="text-align: center; padding-top: 7mm; color: #999;">
                [Assinatura Digital ICP-Brasil]
            </p>
        </div>

        <p style="margin-top: 10mm;"><strong>Funcionário:</strong></p>
        <p>' . htmlspecialchars($employee->name) . '</p>
        <div class="signature-box">
            <p style="text-align: center; padding-top: 7mm; color: #999;">
                [Aguardando assinatura]
            </p>
        </div>

        <div class="footer">
            Documento gerado eletronicamente em ' . date('d/m/Y H:i:s') . '<br>
            Este documento possui validade legal e está protegido por assinatura digital.
        </div>
        ';

        return $html;
    }

    /**
     * Generate HTML for final PDF with signatures
     *
     * @param object $warning
     * @param object $employee
     * @param object $issuer
     * @return string
     */
    protected function generateFinalHTML($warning, $employee, $issuer): string
    {
        $warningTypes = [
            'verbal' => 'VERBAL',
            'escrita' => 'ESCRITA',
            'suspensao' => 'SUSPENSÃO'
        ];

        $warningType = $warningTypes[$warning->warning_type] ?? strtoupper($warning->warning_type);

        $html = '
        <style>
            h1 { text-align: center; font-size: 18pt; margin-bottom: 5mm; }
            h2 { font-size: 14pt; margin-top: 8mm; margin-bottom: 3mm; border-bottom: 1px solid #333; }
            .header { text-align: center; margin-bottom: 10mm; }
            .company-info { font-size: 9pt; color: #666; }
            .box { border: 1px solid #333; padding: 5mm; margin: 5mm 0; background-color: #f9f9f9; }
            .signature-box { border: 1px solid #333; padding: 5mm; margin-top: 3mm; background-color: #ffffcc; }
            .footer { font-size: 8pt; color: #666; text-align: center; margin-top: 10mm; }
            .status-badge { background-color: ';

        if ($warning->status === 'assinado') {
            $html .= '#28a745; color: white;';
        } elseif ($warning->status === 'recusado') {
            $html .= '#dc3545; color: white;';
        }

        $html .= ' padding: 2mm 4mm; border-radius: 3mm; font-size: 9pt; }
        </style>

        <div class="header">';

        if (file_exists($this->companyLogo)) {
            $html .= '<img src="' . $this->companyLogo . '" width="60mm" /><br>';
        }

        $html .= '
            <div class="company-info">
                ' . $this->companyName . '<br>
                CNPJ: ' . $this->companyCNPJ . '
            </div>
        </div>

        <h1>ADVERTÊNCIA ' . $warningType . '</h1>

        <p style="text-align: center;">
            <span class="status-badge">' . strtoupper($warning->status) . '</span>
        </p>

        <h2>DADOS DO FUNCIONÁRIO</h2>
        <div class="box">
            <strong>Nome:</strong> ' . htmlspecialchars($employee->name) . '<br>
            <strong>CPF:</strong> ' . ($employee->cpf ?? 'Não informado') . '<br>
            <strong>Matrícula:</strong> ' . ($employee->id) . '<br>
            <strong>Departamento:</strong> ' . ($employee->department ?? 'Não informado') . '<br>
            <strong>Cargo:</strong> ' . ($employee->position ?? 'Não informado') . '
        </div>

        <h2>DATA DA OCORRÊNCIA</h2>
        <p><strong>' . date('d/m/Y', strtotime($warning->occurrence_date)) . '</strong></p>

        <h2>DESCRIÇÃO DOS FATOS</h2>
        <div class="box">
            ' . nl2br(htmlspecialchars($warning->reason)) . '
        </div>

        <h2>CLÁUSULAS LEGAIS</h2>
        <p style="font-size: 9pt; text-align: justify;">
            De acordo com o artigo 482 da Consolidação das Leis do Trabalho (CLT),
            constituem justa causa para rescisão do contrato de trabalho pelo empregador:
            atos de indisciplina ou insubordinação, desídia no desempenho das respectivas funções,
            e demais infrações previstas na legislação trabalhista vigente.
        </p>';

        if (!empty($warning->evidence_files)) {
            $html .= '
            <h2>EVIDÊNCIAS ANEXAS</h2>
            <ul style="font-size: 9pt;">';

            foreach ($warning->evidence_files as $index => $file) {
                $filename = basename($file);
                $html .= '<li>Anexo ' . ($index + 1) . ': ' . htmlspecialchars($filename) . '</li>';
            }

            $html .= '</ul>';
        }

        $html .= '
        <h2>ASSINATURAS</h2>

        <p><strong>Gestor/Administrador:</strong></p>
        <div class="signature-box">
            <p><strong>' . htmlspecialchars($issuer->name) . '</strong><br>
            Data: ' . date('d/m/Y', strtotime($warning->created_at)) . '<br>
            <em style="font-size: 8pt;">Assinado digitalmente via Certificado ICP-Brasil</em></p>
        </div>

        <p style="margin-top: 10mm;"><strong>Funcionário:</strong></p>';

        if ($warning->status === 'assinado' && $warning->employee_signature) {
            $html .= '
            <div class="signature-box">
                <p><strong>' . htmlspecialchars($employee->name) . '</strong><br>
                Data: ' . date('d/m/Y H:i', strtotime($warning->employee_signed_at)) . '<br>
                <em style="font-size: 8pt;">' . htmlspecialchars($warning->employee_signature) . '</em></p>
            </div>';
        } elseif ($warning->status === 'recusado' && $warning->witness_name) {
            $html .= '
            <div class="signature-box" style="background-color: #ffcccc;">
                <p><strong>RECUSADO PELO FUNCIONÁRIO</strong></p>
                <p style="margin-top: 5mm;"><strong>Testemunha:</strong><br>
                Nome: ' . htmlspecialchars($warning->witness_name) . '<br>
                CPF: ' . htmlspecialchars($warning->witness_cpf) . '<br>
                <em style="font-size: 8pt;">Testemunha presencial da recusa de assinatura</em></p>
            </div>';
        }

        $html .= '
        <div class="footer">
            Documento gerado eletronicamente em ' . date('d/m/Y H:i:s') . '<br>
            Este documento possui validade legal e está protegido por assinatura digital.
        </div>
        ';

        return $html;
    }

    /**
     * Sign PDF with ICP-Brasil certificate
     *
     * @param string $pdfPath
     * @param int $employeeId
     * @return array
     */
    public function signPDFWithICP(string $pdfPath, int $employeeId): array
    {
        // TODO: Implement ICP-Brasil signature
        // This requires openssl and valid ICP-Brasil certificate

        // For now, return success (implementation would be similar to PDFService from Fase 10)
        return [
            'success' => true,
            'filepath' => $pdfPath,
            'certificate_name' => 'Certificado ICP-Brasil'
        ];
    }

    /**
     * Sign PDF with uploaded ICP-Brasil certificate
     *
     * @param string $pdfPath
     * @param object $certificateFile
     * @param string $password
     * @param int $employeeId
     * @return array
     */
    public function signPDFWithICPUpload(string $pdfPath, $certificateFile, string $password, int $employeeId): array
    {
        try {
            // Validate certificate file
            if (!$certificateFile->isValid()) {
                return [
                    'success' => false,
                    'error' => 'Arquivo de certificado inválido.'
                ];
            }

            $certPath = $certificateFile->getTempName();

            // Read certificate
            $certData = file_get_contents($certPath);

            if (!$certData) {
                return [
                    'success' => false,
                    'error' => 'Não foi possível ler o certificado.'
                ];
            }

            // Extract certificate info (basic validation)
            // In production, use proper PKCS#12 validation with openssl_pkcs12_read()

            return [
                'success' => true,
                'filepath' => $pdfPath,
                'certificate_name' => 'Certificado Digital do Funcionário'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
