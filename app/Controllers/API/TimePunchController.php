<?php

namespace App\Controllers\API;

use CodeIgniter\RESTful\ResourceController;
use App\Models\TimePunchModel;
use App\Models\EmployeeModel;
use App\Models\GeofenceModel;
use App\Models\AuditLogModel;
use App\Services\GeolocationService;
use App\Services\DeepFaceService;
use App\Services\TimesheetService;

/**
 * API Time Punch Controller
 *
 * Handles time punch operations via API
 */
class TimePunchController extends ResourceController
{
    protected $modelName = 'App\Models\TimePunchModel';
    protected $format = 'json';

    protected $timePunchModel;
    protected $employeeModel;
    protected $geofenceModel;
    protected $auditModel;
    protected $geolocationService;
    protected $deepfaceService;
    protected $timesheetService;

    public function __construct()
    {
        $this->timePunchModel = new TimePunchModel();
        $this->employeeModel = new EmployeeModel();
        $this->geofenceModel = new GeofenceModel();
        $this->auditModel = new AuditLogModel();
        $this->geolocationService = new GeolocationService();
        $this->deepfaceService = new DeepFaceService();
        $this->timesheetService = new TimesheetService();
        helper(['security', 'format', 'datetime']);
    }

    /**
     * Register punch
     * POST /api/punch
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function create()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        // Validate input
        $rules = [
            'punch_type' => 'required|valid_punch_type',
            'method' => 'required|in_list[codigo,qrcode,facial,biometria]',
            'latitude' => 'permit_empty|valid_latitude',
            'longitude' => 'permit_empty|valid_longitude',
            'photo' => 'permit_empty|valid_base64_image|max_file_size[5242880]',
        ];

        if (!$this->validate($rules)) {
            return $this->fail($this->validator->getErrors(), 400);
        }

        $punchType = $this->request->getPost('punch_type');
        $method = $this->request->getPost('method');
        $latitude = $this->request->getPost('latitude');
        $longitude = $this->request->getPost('longitude');
        $photo = $this->request->getPost('photo');

        // Validate geolocation if provided
        if ($latitude && $longitude) {
            $geofenceValidation = $this->geolocationService->validateGeofence($latitude, $longitude);

            if (!$geofenceValidation['valid']) {
                return $this->fail($geofenceValidation['error'], 403, [
                    'geofence_required' => true,
                    'nearest_geofence' => $geofenceValidation['nearest_geofence'] ?? null,
                ]);
            }
        }

        // Validate facial recognition if method is facial
        if ($method === 'facial' && $photo) {
            $recognition = $this->deepfaceService->recognizeFace($photo);

            if (!$recognition['success'] || !$recognition['recognized']) {
                return $this->fail('Rosto não reconhecido.', 400);
            }

            if ($recognition['employee_id'] !== $employee->id) {
                return $this->fail('A foto não corresponde ao funcionário autenticado.', 403);
            }

            $faceSimilarity = $recognition['similarity'];
        }

        // Check for duplicate punch (within 1 minute)
        $recentPunch = $this->timePunchModel
            ->where('employee_id', $employee->id)
            ->where('punch_time >=', date('Y-m-d H:i:s', strtotime('-1 minute')))
            ->first();

        if ($recentPunch) {
            return $this->fail('Você já registrou ponto recentemente. Aguarde 1 minuto.', 429);
        }

        // Prepare punch data
        $punchData = [
            'employee_id' => $employee->id,
            'punch_time' => date('Y-m-d H:i:s'),
            'punch_type' => $punchType,
            'method' => $method,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'ip_address' => get_client_ip(),
            'user_agent' => get_user_agent(),
        ];

        if (isset($faceSimilarity)) {
            $punchData['face_similarity'] = $faceSimilarity;
        }

        // Insert punch
        $punchId = $this->timePunchModel->insert($punchData);

        if (!$punchId) {
            return $this->fail('Erro ao registrar ponto.', 500);
        }

        // Get the created punch
        $punch = $this->timePunchModel->find($punchId);

        // Log success
        $this->auditModel->log(
            $employee->id,
            'PUNCH_REGISTERED_API',
            'time_punches',
            $punchId,
            null,
            [
                'punch_type' => $punchType,
                'method' => $method,
                'nsr' => $punch->nsr,
            ],
            "Ponto registrado via API: {$punchType} via {$method}",
            'info'
        );

        return $this->respondCreated([
            'success' => true,
            'message' => 'Ponto registrado com sucesso!',
            'data' => [
                'id' => $punch->id,
                'nsr' => $punch->nsr,
                'punch_time' => format_datetime_br($punch->punch_time),
                'punch_type' => $punch->punch_type,
                'method' => $punch->method,
                'hash' => $punch->hash,
            ],
        ]);
    }

    /**
     * Get today's punches
     * GET /api/punch/today
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function today()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        $today = date('Y-m-d');

        $punches = $this->timePunchModel
            ->where('employee_id', $employee->id)
            ->where('DATE(punch_time)', $today)
            ->orderBy('punch_time', 'ASC')
            ->findAll();

        // Calculate hours
        $calculation = $this->timesheetService->calculateDailyHours($punches);

        return $this->respond([
            'success' => true,
            'data' => [
                'date' => format_date_br($today),
                'punches' => array_map(function ($punch) {
                    return [
                        'id' => $punch->id,
                        'nsr' => $punch->nsr,
                        'time' => format_time($punch->punch_time),
                        'punch_type' => $punch->punch_type,
                        'method' => $punch->method,
                        'latitude' => $punch->latitude,
                        'longitude' => $punch->longitude,
                    ];
                }, $punches),
                'summary' => [
                    'total_hours' => $calculation['total_hours'],
                    'work_hours' => $calculation['work_hours'],
                    'break_hours' => $calculation['break_hours'],
                    'total_punches' => count($punches),
                ],
            ],
        ], 200);
    }

    /**
     * Get punch history
     * GET /api/punch/history?month=2024-01
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function history()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        $month = $this->request->getGet('month') ?: date('Y-m');
        $page = (int) ($this->request->getGet('page') ?: 1);
        $perPage = 50;

        $punches = $this->timePunchModel
            ->where('employee_id', $employee->id)
            ->where('DATE(punch_time) LIKE', $month . '%')
            ->orderBy('punch_time', 'DESC')
            ->paginate($perPage, 'default', $page);

        $pager = $this->timePunchModel->pager;

        return $this->respond([
            'success' => true,
            'data' => array_map(function ($punch) {
                return [
                    'id' => $punch->id,
                    'nsr' => $punch->nsr,
                    'date' => format_date_br($punch->punch_time),
                    'time' => format_time($punch->punch_time),
                    'punch_type' => $punch->punch_type,
                    'method' => $punch->method,
                    'latitude' => $punch->latitude,
                    'longitude' => $punch->longitude,
                ];
            }, $punches),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $pager->getTotal(),
                'last_page' => $pager->getPageCount(),
            ],
        ], 200);
    }

    /**
     * Get monthly summary
     * GET /api/punch/summary?month=2024-01
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function summary()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        $month = $this->request->getGet('month') ?: date('Y-m');

        $timesheet = $this->timesheetService->generateMonthlyTimesheet($employee->id, $month);

        if (!$timesheet['success']) {
            return $this->fail($timesheet['error'], 400);
        }

        return $this->respond([
            'success' => true,
            'data' => [
                'month' => format_month_year_br($month),
                'summary' => [
                    'total_hours' => $timesheet['summary']['total_hours'],
                    'expected_hours' => $timesheet['summary']['expected_hours'],
                    'balance' => $timesheet['summary']['balance'],
                    'days_worked' => $timesheet['summary']['days_worked'],
                    'total_punches' => $timesheet['summary']['total_punches'],
                ],
                'daily_records' => array_map(function ($record) {
                    return [
                        'date' => format_date_br($record['date']),
                        'day_of_week' => get_day_of_week_br($record['date'], true),
                        'hours_worked' => $record['hours_worked'],
                        'expected_hours' => $record['expected_hours'],
                        'balance' => $record['balance'],
                        'punch_count' => count($record['punches']),
                    ];
                }, $timesheet['daily_records']),
            ],
        ], 200);
    }

    /**
     * Verify punch hash
     * GET /api/punch/{id}/verify
     *
     * @param int $id
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function verify($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        $punch = $this->timePunchModel->find($id);

        if (!$punch) {
            return $this->fail('Registro não encontrado.', 404);
        }

        // SECURITY FIX: Check access permissions with department validation for gestores
        // This prevents IDOR (Insecure Direct Object Reference) attacks
        $hasAccess = false;

        if ($punch->employee_id === $employee->id) {
            // Employee can access their own punches
            $hasAccess = true;
        } elseif ($employee->role === 'admin') {
            // Admins can access all punches
            $hasAccess = true;
        } elseif ($employee->role === 'gestor') {
            // Gestores can only access punches from employees in their department
            $punchEmployee = $this->employeeModel->find($punch->employee_id);

            if ($punchEmployee && $punchEmployee->department === $employee->department) {
                $hasAccess = true;
            } else {
                log_message('security', "IDOR attempt: Gestor {$employee->id} tried to access punch from different department: employee {$punch->employee_id}");
            }
        }

        if (!$hasAccess) {
            return $this->fail('Acesso negado.', 403);
        }

        $isValid = $this->timePunchModel->verifyHash($punch);

        return $this->respond([
            'success' => true,
            'data' => [
                'punch_id' => $punch->id,
                'nsr' => $punch->nsr,
                'hash' => $punch->hash,
                'is_valid' => $isValid,
                'punch_time' => format_datetime_br($punch->punch_time),
            ],
        ], 200);
    }

    /**
     * Get available geofences
     * GET /api/punch/geofences
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function geofences()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        $latitude = $this->request->getGet('latitude');
        $longitude = $this->request->getGet('longitude');

        $geofences = $this->geofenceModel->where('active', true)->findAll();

        $geofencesData = array_map(function ($geofence) use ($latitude, $longitude) {
            $data = [
                'id' => $geofence->id,
                'name' => $geofence->name,
                'description' => $geofence->description,
                'latitude' => $geofence->latitude,
                'longitude' => $geofence->longitude,
                'radius_meters' => $geofence->radius_meters,
            ];

            if ($latitude && $longitude) {
                $distance = $this->geolocationService->calculateDistance(
                    $latitude,
                    $longitude,
                    $geofence->latitude,
                    $geofence->longitude
                );

                $data['distance_meters'] = round($distance, 2);
                $data['distance_readable'] = format_distance($distance);
                $data['within'] = $distance <= $geofence->radius_meters;
            }

            return $data;
        }, $geofences);

        // Sort by distance if coordinates provided
        if ($latitude && $longitude) {
            usort($geofencesData, function ($a, $b) {
                return $a['distance_meters'] <=> $b['distance_meters'];
            });
        }

        return $this->respond([
            'success' => true,
            'data' => $geofencesData,
        ], 200);
    }

    /**
     * Get authenticated employee from AuthController
     *
     * @return object|null
     */
    protected function getAuthenticatedEmployee(): ?object
    {
        // Reuse logic from AuthController
        $authController = new \App\Controllers\API\AuthController();
        return $authController->getAuthenticatedEmployee();
    }
}
