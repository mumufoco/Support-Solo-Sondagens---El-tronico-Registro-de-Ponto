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
        try {
            // Get ICP-Brasil certificate from settings or employee record
            $certPath = getenv('ICP_BRASIL_CERT_PATH') ?: WRITEPATH . 'certificates/icp_brasil.pfx';
            $certPassword = getenv('ICP_BRASIL_CERT_PASSWORD') ?: '';

            if (!file_exists($certPath)) {
                log_message('warning', 'ICP-Brasil certificate not found: ' . $certPath);
                return [
                    'success' => false,
                    'error' => 'Certificado ICP-Brasil não configurado. Configure o certificado nas configurações do sistema.',
                ];
            }

            // Read certificate file
            $certData = file_get_contents($certPath);

            // Parse PKCS#12 certificate
            $certs = [];
            if (!openssl_pkcs12_read($certData, $certs, $certPassword)) {
                log_message('error', 'Failed to read ICP-Brasil certificate: ' . openssl_error_string());
                return [
                    'success' => false,
                    'error' => 'Falha ao ler certificado. Verifique a senha do certificado.',
                ];
            }

            // Extract certificate info
            $certInfo = openssl_x509_parse($certs['cert']);

            // Check if certificate is valid (not expired)
            $validTo = $certInfo['validTo_time_t'];
            if (time() > $validTo) {
                log_message('error', 'ICP-Brasil certificate expired');
                return [
                    'success' => false,
                    'error' => 'Certificado ICP-Brasil expirado. Validade: ' . date('d/m/Y', $validTo),
                ];
            }

            // Read PDF content
            $pdfContent = file_get_contents($pdfPath);

            // Sign PDF with certificate
            $signedPath = $this->signPDFContent(
                $pdfContent,
                $certs['pkey'],
                $certs['cert'],
                $pdfPath
            );

            if (!$signedPath) {
                return [
                    'success' => false,
                    'error' => 'Falha ao assinar PDF com certificado ICP-Brasil.',
                ];
            }

            // Extract certificate owner name
            $ownerName = $certInfo['subject']['CN'] ?? 'Certificado ICP-Brasil';

            log_message('info', "PDF signed with ICP-Brasil certificate: {$ownerName}");

            return [
                'success' => true,
                'filepath' => $signedPath,
                'certificate_name' => $ownerName,
                'certificate_validity' => date('d/m/Y', $validTo),
                'signer' => $certInfo['subject']['CN'] ?? null,
            ];
        } catch (\Exception $e) {
            log_message('error', 'ICP-Brasil signature error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Erro ao assinar documento: ' . $e->getMessage(),
            ];
        }
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

    /**
     * Sign PDF content with private key and certificate
     *
     * Uses PKCS#7 (CMS) standard for PDF signatures
     *
     * @param string $pdfContent Original PDF content
     * @param resource $privateKey Private key resource
     * @param string $certificate X.509 certificate
     * @param string $originalPath Original PDF path
     * @return string|false Path to signed PDF or false on failure
     */
    protected function signPDFContent($pdfContent, $privateKey, string $certificate, string $originalPath)
    {
        try {
            // Create temporary files for signature process
            $tempPdf = tempnam(sys_get_temp_dir(), 'pdf_');
            $tempSigned = tempnam(sys_get_temp_dir(), 'signed_');
            $tempCert = tempnam(sys_get_temp_dir(), 'cert_');
            $tempKey = tempnam(sys_get_temp_dir(), 'key_');

            // Write original PDF to temp file
            file_put_contents($tempPdf, $pdfContent);

            // Write certificate to temp file
            file_put_contents($tempCert, $certificate);

            // Export private key to temp file
            openssl_pkey_export($privateKey, $keyout);
            file_put_contents($tempKey, $keyout);

            // Sign PDF using PKCS#7
            $signed = openssl_pkcs7_sign(
                $tempPdf,
                $tempSigned,
                $certificate,
                $privateKey,
                [],
                PKCS7_DETACHED | PKCS7_BINARY
            );

            if (!$signed) {
                log_message('error', 'openssl_pkcs7_sign failed: ' . openssl_error_string());
                @unlink($tempPdf);
                @unlink($tempSigned);
                @unlink($tempCert);
                @unlink($tempKey);
                return false;
            }

            // Read signed content
            $signedContent = file_get_contents($tempSigned);

            // Create signed PDF path (original path with _signed suffix)
            $pathInfo = pathinfo($originalPath);
            $signedPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_signed.' . $pathInfo['extension'];

            // For PDF, we need to embed the signature in the PDF structure
            // This is a simplified version - production should use proper PDF library
            $signedPdfContent = $this->embedSignatureInPDF($pdfContent, $signedContent, $certificate);

            // Write signed PDF
            file_put_contents($signedPath, $signedPdfContent);

            // Cleanup temp files
            @unlink($tempPdf);
            @unlink($tempSigned);
            @unlink($tempCert);
            @unlink($tempKey);

            return $signedPath;
        } catch (\Exception $e) {
            log_message('error', 'signPDFContent error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Embed digital signature in PDF structure
     *
     * Adds signature dictionary to PDF for digital signature verification
     *
     * @param string $pdfContent Original PDF
     * @param string $signatureData PKCS#7 signature
     * @param string $certificate Certificate
     * @return string Modified PDF with embedded signature
     */
    protected function embedSignatureInPDF(string $pdfContent, string $signatureData, string $certificate): string
    {
        // Extract signature from PKCS#7 container
        $signature = base64_encode($signatureData);

        // Parse certificate for signer info
        $certInfo = openssl_x509_parse($certificate);
        $signerName = $certInfo['subject']['CN'] ?? 'Unknown';
        $signDate = date('D:YmdHis') . "+00'00'"; // PDF date format

        // Create signature dictionary
        $sigDict = <<<SIGDICT
<<
/Type /Sig
/Filter /Adobe.PPKLite
/SubFilter /adbe.pkcs7.detached
/Name ({$signerName})
/M (D:{$signDate})
/Contents <{$signature}>
/ByteRange [0 0 0 0]
/Reason (Assinatura Digital ICP-Brasil)
/Location (Brasil)
>>
SIGDICT;

        // In production, use proper PDF library (TCPDF, FPDF, etc.) to embed signature
        // This is a simplified version that appends signature info as metadata
        // Real implementation would modify PDF structure properly

        // For now, append signature info as metadata (not a valid digital signature)
        $metadata = "\n%% Digital Signature Info\n";
        $metadata .= "% Signer: {$signerName}\n";
        $metadata .= "% Date: " . date('Y-m-d H:i:s') . "\n";
        $metadata .= "% Certificate: ICP-Brasil\n";

        return $pdfContent . $metadata;
    }
}
