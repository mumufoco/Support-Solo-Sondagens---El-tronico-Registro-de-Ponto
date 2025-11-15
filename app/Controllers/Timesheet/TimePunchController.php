<?php

namespace App\Controllers\Timesheet;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;
use App\Models\TimePunchModel;
use App\Models\GeofenceModel;
use App\Models\AuditLogModel;
use App\Models\SettingModel;

class TimePunchController extends BaseController
{
    protected $employeeModel;
    protected $timePunchModel;
    protected $geofenceModel;
    protected $auditModel;
    protected $settingModel;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->timePunchModel = new TimePunchModel();
        $this->geofenceModel = new GeofenceModel();
        $this->auditModel = new AuditLogModel();
        $this->settingModel = new SettingModel();
    }

    /**
     * Display punch interface
     */
    public function index()
    {
        // Can be accessed without auth for code/QR punch
        $data = [
            'enabledMethods' => $this->getEnabledPunchMethods(),
        ];

        return view('timesheet/punch', $data);
    }

    /**
     * Display my punches (authenticated)
     */
    public function myPunches()
    {
        $this->requireAuth();

        $month = $this->request->getGet('month') ?: date('Y-m');

        $punches = $this->timePunchModel
            ->where('employee_id', $this->currentUser->id)
            ->where('DATE(punch_time) LIKE', $month . '%')
            ->orderBy('punch_time', 'DESC')
            ->paginate(50);

        $data = [
            'punches' => $punches,
            'pager' => $this->timePunchModel->pager,
            'currentMonth' => $month,
        ];

        return view('timesheet/my_punches', $data);
    }

    /**
     * Punch by unique code
     */
    public function punchByCode()
    {
        // Validate input
        $rules = [
            'unique_code' => 'required|min_length[4]|max_length[20]',
            'punch_type'  => 'required|in_list[entrada,saida,intervalo_inicio,intervalo_fim]',
        ];

        if (!$this->validate($rules)) {
            return $this->respondError('Dados inválidos.', $this->validator->getErrors(), 400);
        }

        $uniqueCode = $this->request->getPost('unique_code');
        $punchType = $this->request->getPost('punch_type');

        // Find employee by code
        $employee = $this->employeeModel->findByCode($uniqueCode);

        if (!$employee) {
            $this->auditModel->log(
                null,
                'PUNCH_FAILED',
                'time_punches',
                null,
                null,
                null,
                "Tentativa de registro com código inválido: {$uniqueCode}",
                'warning'
            );

            return $this->respondError('Código inválido.', null, 404);
        }

        // Check if employee is active
        if (!$employee->active) {
            return $this->respondError('Funcionário inativo.', null, 403);
        }

        // Process punch
        return $this->processPunch($employee->id, $punchType, 'codigo');
    }

    /**
     * Punch by QR Code
     */
    public function punchByQRCode()
    {
        // Validate input
        $rules = [
            'qr_data'    => 'required',
            'punch_type' => 'required|in_list[entrada,saida,intervalo_inicio,intervalo_fim]',
        ];

        if (!$this->validate($rules)) {
            return $this->respondError('Dados inválidos.', $this->validator->getErrors(), 400);
        }

        $qrData = $this->request->getPost('qr_data');
        $punchType = $this->request->getPost('punch_type');

        // Decode QR data (format: "EMP-{employee_id}-{timestamp}-{signature}")
        $qrParts = explode('-', $qrData);

        if (count($qrParts) < 4 || $qrParts[0] !== 'EMP') {
            return $this->respondError('QR Code inválido.', null, 400);
        }

        $employeeId = (int) $qrParts[1];
        $timestamp = (int) $qrParts[2];
        $signature = $qrParts[3];

        // Verify QR code hasn't expired (valid for 5 minutes)
        if (time() - $timestamp > 300) {
            return $this->respondError('QR Code expirado. Gere um novo código.', null, 400);
        }

        // Verify signature
        $expectedSignature = hash('sha256', $employeeId . $timestamp . env('app.encryption.key'));

        if (!hash_equals($expectedSignature, $signature)) {
            $this->auditModel->log(
                null,
                'PUNCH_FAILED',
                'time_punches',
                null,
                null,
                null,
                "Tentativa de registro com QR Code inválido: {$qrData}",
                'warning'
            );

            return $this->respondError('QR Code inválido.', null, 400);
        }

        // Get employee
        $employee = $this->employeeModel->find($employeeId);

        if (!$employee || !$employee->active) {
            return $this->respondError('Funcionário não encontrado ou inativo.', null, 404);
        }

        // Process punch
        return $this->processPunch($employeeId, $punchType, 'qrcode');
    }

    /**
     * Punch by facial recognition
     */
    public function punchByFace()
    {
        // Rate limiting: max 5 facial recognition attempts per minute per IP
        $throttler = \Config\Services::throttler();
        $key = 'facial_punch_' . $this->request->getIPAddress();

        if ($throttler->check($key, 5, MINUTE) === false) {
            return $this->respondError(
                'Muitas tentativas de reconhecimento facial. Aguarde 1 minuto antes de tentar novamente.',
                null,
                429
            );
        }

        // Validate input
        $rules = [
            'photo'      => 'required',
            'punch_type' => 'required|in_list[entrada,saida,intervalo_inicio,intervalo_fim]',
        ];

        if (!$this->validate($rules)) {
            return $this->respondError('Dados inválidos.', $this->validator->getErrors(), 400);
        }

        $photoBase64 = $this->request->getPost('photo');
        $punchType = $this->request->getPost('punch_type');

        // Call DeepFace API for recognition
        $deepfaceUrl = $this->settingModel->get('deepface_api_url', 'http://localhost:5000');
        $threshold = $this->settingModel->get('deepface_threshold', 0.40);

        try {
            $client = \Config\Services::curlrequest();

            $response = $client->post($deepfaceUrl . '/recognize', [
                'json' => [
                    'photo' => $photoBase64,
                    'threshold' => $threshold,
                ],
                'timeout' => 10,
            ]);

            $result = json_decode($response->getBody(), true);

            if (!$result['success']) {
                return $this->respondError('Erro ao processar reconhecimento facial.', null, 500);
            }

            if (!$result['recognized']) {
                $this->auditModel->log(
                    null,
                    'PUNCH_FAILED',
                    'time_punches',
                    null,
                    null,
                    null,
                    'Tentativa de registro facial sem reconhecimento',
                    'warning'
                );

                return $this->respondError('Rosto não reconhecido. Tente novamente.', null, 404);
            }

            $employeeId = (int) $result['employee_id'];
            $similarity = $result['similarity'];

            // Get employee
            $employee = $this->employeeModel->find($employeeId);

            if (!$employee || !$employee->active) {
                return $this->respondError('Funcionário não encontrado ou inativo.', null, 404);
            }

            // Process punch with facial data
            return $this->processPunch(
                $employeeId,
                $punchType,
                'facial',
                ['face_similarity' => $similarity]
            );

        } catch (\Exception $e) {
            log_message('error', 'DeepFace API error: ' . $e->getMessage());

            return $this->respondError('Erro ao conectar com serviço de reconhecimento facial.', null, 500);
        }
    }

    /**
     * Punch by fingerprint
     */
    public function punchByFingerprint()
    {
        // Validate input
        $rules = [
            'template'   => 'required',
            'punch_type' => 'required|in_list[entrada,saida,intervalo_inicio,intervalo_fim]',
        ];

        if (!$this->validate($rules)) {
            return $this->respondError('Dados inválidos.', $this->validator->getErrors(), 400);
        }

        $template = $this->request->getPost('template');
        $punchType = $this->request->getPost('punch_type');

        // Match fingerprint template
        $biometricModel = new \App\Models\BiometricTemplateModel();

        $match = $biometricModel
            ->where('biometric_type', 'fingerprint')
            ->where('active', true)
            ->findAll();

        // Simple template matching (in production, use specialized library)
        $matchedEmployeeId = null;
        $matchScore = 0;

        foreach ($match as $record) {
            $score = $this->compareTemplates($template, $record->template_data);

            if ($score > 0.85 && $score > $matchScore) {
                $matchScore = $score;
                $matchedEmployeeId = $record->employee_id;
            }
        }

        if (!$matchedEmployeeId) {
            $this->auditModel->log(
                null,
                'PUNCH_FAILED',
                'time_punches',
                null,
                null,
                null,
                'Tentativa de registro por biometria digital sem reconhecimento',
                'warning'
            );

            return $this->respondError('Biometria não reconhecida. Tente novamente.', null, 404);
        }

        // Get employee
        $employee = $this->employeeModel->find($matchedEmployeeId);

        if (!$employee || !$employee->active) {
            return $this->respondError('Funcionário não encontrado ou inativo.', null, 404);
        }

        // Process punch
        return $this->processPunch($matchedEmployeeId, $punchType, 'biometria');
    }

    /**
     * Process punch (common logic for all methods)
     */
    protected function processPunch(
        int $employeeId,
        string $punchType,
        string $method,
        array $additionalData = []
    ) {
        // Get geolocation if provided
        $latitude = $this->request->getPost('latitude');
        $longitude = $this->request->getPost('longitude');

        // Validate geolocation if required
        $requireGeolocation = $this->settingModel->get('require_geolocation', false);

        if ($requireGeolocation && (!$latitude || !$longitude)) {
            return $this->respondError('Geolocalização é obrigatória.', null, 400);
        }

        // Validate geofence if geolocation provided
        if ($latitude && $longitude) {
            $geofenceValid = $this->validateGeofence($latitude, $longitude);

            if (!$geofenceValid) {
                $this->auditModel->log(
                    $employeeId,
                    'PUNCH_FAILED',
                    'time_punches',
                    null,
                    null,
                    null,
                    "Tentativa de registro fora da cerca virtual: {$latitude}, {$longitude}",
                    'warning'
                );

                return $this->respondError('Você está fora da área permitida para registro de ponto.', null, 403);
            }
        }

        // Check for duplicate punch (within 1 minute)
        $recentPunch = $this->timePunchModel
            ->where('employee_id', $employeeId)
            ->where('punch_time >=', date('Y-m-d H:i:s', strtotime('-1 minute')))
            ->first();

        if ($recentPunch) {
            return $this->respondError('Você já registrou ponto recentemente. Aguarde 1 minuto.', null, 429);
        }

        // Prepare punch data
        $punchData = [
            'employee_id'   => $employeeId,
            'punch_time'    => date('Y-m-d H:i:s'),
            'punch_type'    => $punchType,
            'method'        => $method,
            'latitude'      => $latitude,
            'longitude'     => $longitude,
            'ip_address'    => $this->getClientIp(),
            'user_agent'    => $this->getUserAgent(),
        ];

        // Add additional data (e.g., face_similarity)
        $punchData = array_merge($punchData, $additionalData);

        // Insert punch (NSR and hash are generated by model)
        $punchId = $this->timePunchModel->insert($punchData);

        if (!$punchId) {
            log_message('error', 'Failed to insert punch: ' . json_encode($this->timePunchModel->errors()));
            return $this->respondError('Erro ao registrar ponto.', null, 500);
        }

        // Get the created punch
        $punch = $this->timePunchModel->find($punchId);

        // Log success
        $this->auditModel->log(
            $employeeId,
            'PUNCH_REGISTERED',
            'time_punches',
            $punchId,
            null,
            [
                'punch_type' => $punchType,
                'method' => $method,
                'nsr' => $punch->nsr,
            ],
            "Ponto registrado: {$punchType} via {$method} (NSR: {$punch->nsr})",
            'info'
        );

        return $this->respondSuccess([
            'punch' => $punch,
            'nsr' => $punch->nsr,
            'hash' => $punch->hash,
        ], 'Ponto registrado com sucesso!', 201);
    }

    /**
     * Validate geofence
     */
    protected function validateGeofence(float $latitude, float $longitude): bool
    {
        // Check if geofence is required
        $requireGeofence = $this->settingModel->get('require_geofence', false);

        if (!$requireGeofence) {
            return true; // Geofence not required
        }

        // Get active geofences
        $geofences = $this->geofenceModel->where('active', true)->findAll();

        if (empty($geofences)) {
            return true; // No geofences configured
        }

        // Check if point is within any geofence
        foreach ($geofences as $geofence) {
            if ($this->geofenceModel->isWithinGeofence($latitude, $longitude, $geofence)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compare fingerprint templates (placeholder)
     */
    protected function compareTemplates(string $template1, string $template2): float
    {
        // This is a placeholder. In production, use a specialized fingerprint matching library
        // such as SourceAFIS or similar

        // Simple similarity score based on string comparison (not secure for production)
        similar_text($template1, $template2, $percent);

        return $percent / 100;
    }

    /**
     * Get enabled punch methods
     */
    protected function getEnabledPunchMethods(): array
    {
        return [
            'codigo' => $this->settingModel->get('punch_method_code_enabled', true),
            'qrcode' => $this->settingModel->get('punch_method_qr_enabled', true),
            'facial' => $this->settingModel->get('punch_method_face_enabled', true),
            'biometria' => $this->settingModel->get('punch_method_fingerprint_enabled', false),
        ];
    }

    /**
     * Generate QR Code for employee (authenticated)
     */
    public function generateQRCode()
    {
        $this->requireAuth();

        $employeeId = $this->currentUser->id;
        $timestamp = time();

        // Generate signature
        $signature = hash('sha256', $employeeId . $timestamp . env('app.encryption.key'));

        // Create QR data
        $qrData = "EMP-{$employeeId}-{$timestamp}-{$signature}";

        return $this->respondSuccess([
            'qr_data' => $qrData,
            'expires_at' => date('Y-m-d H:i:s', $timestamp + 300), // 5 minutes
        ], 'QR Code gerado com sucesso.');
    }

    /**
     * Generate PDF receipt for punch (Portaria MTE 671/2021)
     *
     * @param int $punchId
     * @return \CodeIgniter\HTTP\Response
     */
    public function generateReceipt(int $punchId)
    {
        // Load required libraries
        if (!class_exists('\TCPDF')) {
            return $this->respondError('TCPDF library not installed.', null, 500);
        }

        // Get punch data
        $punch = $this->timePunchModel
            ->select('time_punches.*, employees.name, employees.cpf, employees.unique_code')
            ->join('employees', 'employees.id = time_punches.employee_id')
            ->find($punchId);

        if (!$punch) {
            return $this->respondError('Registro não encontrado.', null, 404);
        }

        // Get company settings
        $companyName = $this->settingModel->get('company_name', 'Empresa XYZ Ltda');
        $companyCNPJ = $this->settingModel->get('company_cnpj', '00.000.000/0001-00');
        $companyAddress = $this->settingModel->get('company_address', 'Rua Exemplo, 123 - São Paulo/SP');
        $inpiRegistry = $this->settingModel->get('inpi_registry', 'BR512024000000');

        // Create PDF
        $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8');

        // Set document information
        $pdf->SetCreator('Sistema de Ponto Eletrônico');
        $pdf->SetAuthor($companyName);
        $pdf->SetTitle('Comprovante de Registro de Ponto - NSR ' . str_pad($punch->nsr, 10, '0', STR_PAD_LEFT));
        $pdf->SetSubject('Comprovante conforme Portaria MTE 671/2021');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        // Add page
        $pdf->AddPage();

        // --- HEADER ---
        // Company logo (if exists)
        $logoPath = WRITEPATH . 'uploads/company_logo.png';
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 15, 15, 30, 0, 'PNG');
            $pdf->SetY(20);
        } else {
            $pdf->SetY(15);
        }

        // Company name
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, $companyName, 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'CNPJ: ' . $companyCNPJ, 0, 1, 'C');
        $pdf->Cell(0, 5, $companyAddress, 0, 1, 'C');

        $pdf->Ln(5);

        // --- TITLE ---
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetFillColor(0, 102, 204);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(0, 10, 'COMPROVANTE DE REGISTRO DE PONTO ELETRÔNICO', 0, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);

        $pdf->Ln(5);

        // --- BODY ---
        $pdf->SetFont('helvetica', '', 11);

        // Employee data
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 7, 'DADOS DO FUNCIONÁRIO', 0, 1);
        $pdf->SetFont('helvetica', '', 10);

        $pdf->Cell(50, 6, 'Nome:', 0, 0);
        $pdf->Cell(0, 6, $punch->name, 0, 1);

        $pdf->Cell(50, 6, 'CPF:', 0, 0);
        $pdf->Cell(0, 6, $punch->cpf, 0, 1);

        $pdf->Cell(50, 6, 'Matrícula:', 0, 0);
        $pdf->Cell(0, 6, $punch->unique_code, 0, 1);

        $pdf->Ln(3);

        // Punch data
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 7, 'DADOS DO REGISTRO', 0, 1);
        $pdf->SetFont('helvetica', '', 10);

        $pdf->Cell(50, 6, 'Data/Hora:', 0, 0);
        $pdf->Cell(0, 6, date('d/m/Y H:i:s', strtotime($punch->punch_time)), 0, 1);

        $pdf->Cell(50, 6, 'Tipo de Marcação:', 0, 0);
        $punchTypeLabel = $this->getPunchTypeLabel($punch->punch_type);
        $pdf->Cell(0, 6, strtoupper($punchTypeLabel), 0, 1);

        $pdf->Cell(50, 6, 'Método:', 0, 0);
        $methodLabel = $this->getMethodLabel($punch->method);
        $pdf->Cell(0, 6, $methodLabel, 0, 1);

        $pdf->Cell(50, 6, 'NSR:', 0, 0);
        $pdf->SetFont('courier', 'B', 10);
        $pdf->Cell(0, 6, str_pad($punch->nsr, 10, '0', STR_PAD_LEFT), 0, 1);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(50, 6, 'Hash SHA-256:', 0, 0);
        $pdf->SetFont('courier', '', 8);
        $pdf->MultiCell(0, 6, $punch->hash, 0, 'L');

        $pdf->Ln(3);

        // Geolocation (if available)
        if (!empty($punch->latitude) && !empty($punch->longitude)) {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(50, 6, 'Localização:', 0, 0);
            $pdf->Cell(0, 6, sprintf('%.6f, %.6f', $punch->latitude, $punch->longitude), 0, 1);
        }

        $pdf->Ln(5);

        // --- QR CODE ---
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 7, 'QR CODE PARA VALIDAÇÃO', 0, 1, 'C');

        // Generate QR Code data
        $qrData = json_encode([
            'nsr' => $punch->nsr,
            'employee_id' => $punch->employee_id,
            'punch_time' => $punch->punch_time,
            'hash' => $punch->hash,
            'validation_url' => base_url('validate-punch/' . $punch->nsr),
        ]);

        // Add QR Code to PDF
        $pdf->write2DBarcode($qrData, 'QRCODE,L', 70, $pdf->GetY(), 60, 60, null, 'N');

        $pdf->Ln(65);

        // Validation text
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->MultiCell(0, 5, 'Escaneie o QR Code acima para validar a autenticidade deste comprovante online.', 0, 'C');

        $pdf->Ln(5);

        // --- FOOTER ---
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(100, 100, 100);

        $pdf->MultiCell(0, 4, 'Este documento é válido sem assinatura conforme Portaria MTE nº 671/2021.', 0, 'C');
        $pdf->MultiCell(0, 4, 'Registro INPI: ' . $inpiRegistry, 0, 'C');
        $pdf->MultiCell(0, 4, 'Validação online: ' . base_url('validate-punch/' . $punch->nsr), 0, 'C');

        $pdf->Ln(2);

        $pdf->SetFont('helvetica', 'I', 7);
        $pdf->MultiCell(0, 3, 'Sistema de Ponto Eletrônico - Emitido em ' . date('d/m/Y H:i:s'), 0, 'C');

        // Save PDF to storage
        $year = date('Y', strtotime($punch->punch_time));
        $month = date('m', strtotime($punch->punch_time));

        $receiptDir = WRITEPATH . "receipts/{$year}/{$month}";
        if (!is_dir($receiptDir)) {
            mkdir($receiptDir, 0755, true);
        }

        $filename = "employee_{$punch->employee_id}_nsr_{$punch->nsr}.pdf";
        $filepath = $receiptDir . '/' . $filename;

        $pdf->Output($filepath, 'F');

        // Log generation
        $this->auditModel->log(
            $punch->employee_id,
            'RECEIPT_GENERATED',
            'time_punches',
            $punchId,
            null,
            null,
            "Comprovante PDF gerado: NSR {$punch->nsr}",
            'info'
        );

        // Return download link
        return $this->respondSuccess([
            'punch_id' => $punchId,
            'nsr' => $punch->nsr,
            'filename' => $filename,
            'download_url' => base_url("download-receipt/{$year}/{$month}/{$filename}"),
        ], 'Comprovante gerado com sucesso.');
    }

    /**
     * Download receipt PDF
     */
    public function downloadReceipt(string $year, string $month, string $filename)
    {
        $filepath = WRITEPATH . "receipts/{$year}/{$month}/{$filename}";

        if (!file_exists($filepath)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Comprovante não encontrado.');
        }

        return $this->response->download($filepath, null)->setFileName($filename);
    }

    /**
     * Helper: Get punch type label
     */
    private function getPunchTypeLabel(string $type): string
    {
        $labels = [
            'entrada' => 'ENTRADA',
            'saida' => 'SAÍDA',
            'intervalo_inicio' => 'INTERVALO - INÍCIO',
            'intervalo_fim' => 'INTERVALO - FIM',
        ];

        return $labels[$type] ?? strtoupper($type);
    }

    /**
     * Helper: Get method label
     */
    private function getMethodLabel(string $method): string
    {
        $labels = [
            'code' => 'Código Único',
            'qr_code' => 'QR Code',
            'facial' => 'Reconhecimento Facial',
            'fingerprint' => 'Biometria (Digital)',
        ];

        return $labels[$method] ?? $method;
    }

    /**
     * Verify punch hash (for auditing)
     */
    public function verifyHash(int $punchId)
    {
        $this->requireManager();

        $punch = $this->timePunchModel->find($punchId);

        if (!$punch) {
            return $this->respondError('Registro não encontrado.', null, 404);
        }

        $isValid = $this->timePunchModel->verifyHash($punch);

        return $this->respondSuccess([
            'punch_id' => $punchId,
            'nsr' => $punch->nsr,
            'hash' => $punch->hash,
            'is_valid' => $isValid,
        ], $isValid ? 'Hash válido.' : 'Hash inválido!');
    }
}
