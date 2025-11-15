<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;
use App\Models\GeofenceModel;
use App\Models\AuditLogModel;
use App\Services\GeolocationService;

/**
 * Geofence Controller
 *
 * Handles geofence location management (Admin only)
 */
class GeofenceController extends BaseController
{
    protected $employeeModel;
    protected $geofenceModel;
    protected $auditModel;
    protected $geolocationService;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->geofenceModel = new GeofenceModel();
        $this->auditModel = new AuditLogModel();
        $this->geolocationService = new GeolocationService();
        helper(['form']);
    }

    /**
     * List all geofences
     * GET /geofences
     */
    public function index()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')
                ->with('error', 'Acesso negado. Apenas administradores podem gerenciar geofences.');
        }

        $geofences = $this->geofenceModel
            ->orderBy('created_at', 'DESC')
            ->findAll();

        return view('geofences/index', [
            'employee' => $employee,
            'geofences' => $geofences,
        ]);
    }

    /**
     * Show create form
     * GET /geofences/create
     */
    public function create()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        return view('geofences/create', [
            'employee' => $employee,
        ]);
    }

    /**
     * Store new geofence
     * POST /geofences
     */
    public function store()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        // Validation rules
        $rules = [
            'name' => 'required|min_length[3]|max_length[255]',
            'description' => 'permit_empty|max_length[500]',
            'latitude' => 'required|valid_latitude',
            'longitude' => 'required|valid_longitude',
            'radius_meters' => 'required|numeric|greater_than[0]',
            'active' => 'permit_empty',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Create geofence
        $data = [
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'latitude' => $this->request->getPost('latitude'),
            'longitude' => $this->request->getPost('longitude'),
            'radius_meters' => $this->request->getPost('radius_meters'),
            'active' => $this->request->getPost('active') ? true : false,
        ];

        $geofenceId = $this->geofenceModel->insert($data);

        if (!$geofenceId) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao criar geofence.');
        }

        // Log creation
        $this->auditModel->log(
            $employee['id'],
            'GEOFENCE_CREATED',
            'geofences',
            $geofenceId,
            null,
            $data,
            "Geofence '{$data['name']}' criado",
            'info'
        );

        return redirect()->to('/geofences')
            ->with('success', 'Geofence criado com sucesso!');
    }

    /**
     * Show geofence details
     * GET /geofences/{id}
     */
    public function show($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        $geofence = $this->geofenceModel->find($id);

        if (!$geofence) {
            return redirect()->to('/geofences')
                ->with('error', 'Geofence não encontrado.');
        }

        return view('geofences/show', [
            'employee' => $employee,
            'geofence' => $geofence,
        ]);
    }

    /**
     * Show edit form
     * GET /geofences/{id}/edit
     */
    public function edit($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        $geofence = $this->geofenceModel->find($id);

        if (!$geofence) {
            return redirect()->to('/geofences')
                ->with('error', 'Geofence não encontrado.');
        }

        return view('geofences/edit', [
            'employee' => $employee,
            'geofence' => $geofence,
        ]);
    }

    /**
     * Update geofence
     * PUT /geofences/{id}
     */
    public function update($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        $geofence = $this->geofenceModel->find($id);

        if (!$geofence) {
            return redirect()->to('/geofences')
                ->with('error', 'Geofence não encontrado.');
        }

        // Validation rules
        $rules = [
            'name' => 'required|min_length[3]|max_length[255]',
            'description' => 'permit_empty|max_length[500]',
            'latitude' => 'required|valid_latitude',
            'longitude' => 'required|valid_longitude',
            'radius_meters' => 'required|numeric|greater_than[0]',
            'active' => 'permit_empty',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $oldData = (array) $geofence;

        // Update geofence
        $newData = [
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'latitude' => $this->request->getPost('latitude'),
            'longitude' => $this->request->getPost('longitude'),
            'radius_meters' => $this->request->getPost('radius_meters'),
            'active' => $this->request->getPost('active') ? true : false,
        ];

        $this->geofenceModel->update($id, $newData);

        // Log update
        $this->auditModel->log(
            $employee['id'],
            'GEOFENCE_UPDATED',
            'geofences',
            $id,
            $oldData,
            $newData,
            "Geofence '{$newData['name']}' atualizado",
            'info'
        );

        return redirect()->to('/geofences')
            ->with('success', 'Geofence atualizado com sucesso!');
    }

    /**
     * Delete geofence
     * DELETE /geofences/{id}
     */
    public function delete($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        $geofence = $this->geofenceModel->find($id);

        if (!$geofence) {
            return redirect()->to('/geofences')
                ->with('error', 'Geofence não encontrado.');
        }

        // Log deletion
        $this->auditModel->log(
            $employee['id'],
            'GEOFENCE_DELETED',
            'geofences',
            $id,
            (array) $geofence,
            null,
            "Geofence '{$geofence->name}' excluído",
            'warning'
        );

        $this->geofenceModel->delete($id);

        return redirect()->to('/geofences')
            ->with('success', 'Geofence excluído com sucesso.');
    }

    /**
     * Toggle geofence active status
     * POST /geofences/{id}/toggle
     */
    public function toggle($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        $geofence = $this->geofenceModel->find($id);

        if (!$geofence) {
            return redirect()->to('/geofences')
                ->with('error', 'Geofence não encontrado.');
        }

        $newStatus = !$geofence->active;

        $this->geofenceModel->update($id, ['active' => $newStatus]);

        // Log change
        $this->auditModel->log(
            $employee['id'],
            'GEOFENCE_TOGGLED',
            'geofences',
            $id,
            ['active' => $geofence->active],
            ['active' => $newStatus],
            "Geofence '{$geofence->name}' " . ($newStatus ? 'ativado' : 'desativado'),
            'info'
        );

        $message = $newStatus ? 'Geofence ativado com sucesso.' : 'Geofence desativado com sucesso.';

        return redirect()->to('/geofences')->with('success', $message);
    }

    /**
     * Test geofence validation
     * POST /geofences/test
     */
    public function test()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso negado.',
            ])->setStatusCode(403);
        }

        $latitude = $this->request->getPost('latitude');
        $longitude = $this->request->getPost('longitude');

        if (!$latitude || !$longitude) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Latitude e longitude são obrigatórios.',
            ])->setStatusCode(400);
        }

        // Validate against all active geofences
        $result = $this->geolocationService->validateGeofence($latitude, $longitude);

        return $this->response->setJSON($result);
    }

    /**
     * Get geofences as JSON (for map display)
     * GET /geofences/json
     */
    public function json()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Não autenticado.',
            ])->setStatusCode(401);
        }

        $geofences = $this->geofenceModel
            ->where('active', true)
            ->findAll();

        $data = array_map(function($geofence) {
            return [
                'id' => $geofence->id,
                'name' => $geofence->name,
                'description' => $geofence->description,
                'latitude' => (float) $geofence->latitude,
                'longitude' => (float) $geofence->longitude,
                'radius' => (int) $geofence->radius_meters,
            ];
        }, $geofences);

        return $this->response->setJSON([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get authenticated employee from session
     */
    protected function getAuthenticatedEmployee(): ?array
    {
        if (!session()->has('employee_id')) {
            return null;
        }

        $employeeId = session()->get('employee_id');
        $employee = $this->employeeModel->find($employeeId);

        if (!$employee) {
            return null;
        }

        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'email' => $employee->email,
            'role' => $employee->role,
            'department' => $employee->department,
        ];
    }
}
