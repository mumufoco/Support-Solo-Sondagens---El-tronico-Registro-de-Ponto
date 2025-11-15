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
