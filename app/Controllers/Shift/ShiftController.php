<?php

namespace App\Controllers\Shift;

use App\Controllers\BaseController;
use App\Models\WorkShiftModel;
use App\Models\ScheduleModel;
use App\Models\EmployeeModel;

/**
 * ShiftController
 *
 * Manages work shifts (morning, afternoon, night, custom)
 * Handles CRUD operations, cloning, and shift statistics
 *
 * @package App\Controllers\Shift
 */
class ShiftController extends BaseController
{
    protected WorkShiftModel $shiftModel;
    protected ScheduleModel $scheduleModel;
    protected EmployeeModel $employeeModel;

    public function __construct()
    {
        $this->shiftModel = new WorkShiftModel();
        $this->scheduleModel = new ScheduleModel();
        $this->employeeModel = new EmployeeModel();
    }

    /**
     * List all work shifts
     *
     * @return string
     */
    public function index()
    {
        $this->requireManager();

        // Get filter parameters
        $type = $this->request->getGet('type');
        $status = $this->request->getGet('status');
        $search = $this->request->getGet('search');

        // Build query
        $query = $this->shiftModel;

        // Type filter
        if ($type && in_array($type, ['morning', 'afternoon', 'night', 'custom'])) {
            $query->where('type', $type);
        }

        // Status filter
        if ($status === 'active') {
            $query->where('active', true);
        } elseif ($status === 'inactive') {
            $query->where('active', false);
        }

        // Search
        if ($search) {
            $query->groupStart()
                ->like('name', $search)
                ->orLike('description', $search)
                ->groupEnd();
        }

        // Get shifts with employee count
        $shifts = $query->orderBy('type', 'ASC')
            ->orderBy('start_time', 'ASC')
            ->findAll();

        // Enrich with additional data
        foreach ($shifts as &$shift) {
            $shift->employee_count = $this->scheduleModel
                ->where('shift_id', $shift->id)
                ->countAllResults();

            $shift->duration = $this->shiftModel->calculateDuration($shift);
        }

        $data = [
            'shifts' => $shifts,
            'filters' => [
                'type' => $type,
                'status' => $status,
                'search' => $search,
            ],
            'statistics' => $this->getOverallStatistics(),
        ];

        return view('shifts/index', $data);
    }

    /**
     * Show shift details
     *
     * @param int $id Shift ID
     * @return mixed
     */
    public function show(int $id)
    {
        $this->requireManager();

        $shift = $this->shiftModel->find($id);

        if (!$shift) {
            $this->setError('Turno não encontrado.');
            return redirect()->to('/shifts');
        }

        // Get assigned employees
        $assignedEmployees = $this->scheduleModel->getEmployeesByShift($id);

        // Get shift statistics
        $statistics = $this->shiftModel->getShiftStatistics($id);

        $data = [
            'shift' => $shift,
            'duration' => $this->shiftModel->calculateDuration($shift),
            'assignedEmployees' => $assignedEmployees,
            'statistics' => $statistics,
        ];

        return view('shifts/show', $data);
    }

    /**
     * Show create shift form
     *
     * @return string
     */
    public function create()
    {
        $this->requireManager();

        $data = [
            'shiftTypes' => [
                'morning' => 'Manhã',
                'afternoon' => 'Tarde',
                'night' => 'Noite',
                'custom' => 'Personalizado',
            ],
        ];

        return view('shifts/create', $data);
    }

    /**
     * Store new shift
     *
     * @return mixed
     */
    public function store()
    {
        $this->requireManager();

        // Validation rules
        $rules = [
            'name' => 'required|min_length[3]|max_length[100]|is_unique[work_shifts.name]',
            'start_time' => 'required|valid_time',
            'end_time' => 'required|valid_time',
            'type' => 'required|in_list[morning,afternoon,night,custom]',
            'break_duration' => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[480]',
            'color' => 'permit_empty|regex_match[/^#[0-9A-Fa-f]{6}$/]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Check for time overlaps with existing shifts
        $startTime = $this->request->getPost('start_time');
        $endTime = $this->request->getPost('end_time');

        $overlappingShifts = $this->shiftModel->findOverlappingShifts($startTime, $endTime);

        if (!empty($overlappingShifts)) {
            $this->setWarning('Atenção: Este turno se sobrepõe a outros turnos existentes.');
        }

        // Prepare data
        $data = [
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'type' => $this->request->getPost('type'),
            'break_duration' => $this->request->getPost('break_duration') ?: 0,
            'color' => $this->request->getPost('color') ?: $this->getDefaultColor($this->request->getPost('type')),
            'active' => true,
            'created_by' => $this->currentUser->id,
        ];

        // Insert shift
        $shiftId = $this->shiftModel->insert($data);

        if (!$shiftId) {
            $this->setError('Erro ao criar turno.');
            return redirect()->back()->withInput();
        }

        // Log creation
        $this->logAudit(
            'SHIFT_CREATED',
            'work_shifts',
            $shiftId,
            null,
            $data,
            "Turno criado: {$data['name']} ({$data['start_time']} - {$data['end_time']})"
        );

        $this->setSuccess('Turno criado com sucesso!');
        return redirect()->to('/shifts/' . $shiftId);
    }

    /**
     * Show edit shift form
     *
     * @param int $id Shift ID
     * @return mixed
     */
    public function edit(int $id)
    {
        $this->requireManager();

        $shift = $this->shiftModel->find($id);

        if (!$shift) {
            $this->setError('Turno não encontrado.');
            return redirect()->to('/shifts');
        }

        $data = [
            'shift' => $shift,
            'shiftTypes' => [
                'morning' => 'Manhã',
                'afternoon' => 'Tarde',
                'night' => 'Noite',
                'custom' => 'Personalizado',
            ],
        ];

        return view('shifts/edit', $data);
    }

    /**
     * Update shift
     *
     * @param int $id Shift ID
     * @return mixed
     */
    public function update(int $id)
    {
        $this->requireManager();

        $shift = $this->shiftModel->find($id);

        if (!$shift) {
            $this->setError('Turno não encontrado.');
            return redirect()->to('/shifts');
        }

        // Validation rules
        $rules = [
            'name' => "required|min_length[3]|max_length[100]|is_unique[work_shifts.name,id,{$id}]",
            'start_time' => 'required|valid_time',
            'end_time' => 'required|valid_time',
            'type' => 'required|in_list[morning,afternoon,night,custom]',
            'break_duration' => 'permit_empty|integer|greater_than_equal_to[0]|less_than_equal_to[480]',
            'color' => 'permit_empty|regex_match[/^#[0-9A-Fa-f]{6}$/]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Check for time overlaps (excluding current shift)
        $startTime = $this->request->getPost('start_time');
        $endTime = $this->request->getPost('end_time');

        $overlappingShifts = $this->shiftModel->findOverlappingShifts($startTime, $endTime, $id);

        if (!empty($overlappingShifts)) {
            $this->setWarning('Atenção: Este turno se sobrepõe a outros turnos existentes.');
        }

        // Prepare old values for audit
        $oldValues = [
            'name' => $shift->name,
            'start_time' => $shift->start_time,
            'end_time' => $shift->end_time,
            'type' => $shift->type,
            'break_duration' => $shift->break_duration,
            'active' => $shift->active,
        ];

        // Prepare new data
        $data = [
            'name' => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'type' => $this->request->getPost('type'),
            'break_duration' => $this->request->getPost('break_duration') ?: 0,
            'color' => $this->request->getPost('color') ?: $shift->color,
            'active' => $this->request->getPost('active') ? true : false,
        ];

        // Update shift
        if (!$this->shiftModel->update($id, $data)) {
            $this->setError('Erro ao atualizar turno.');
            return redirect()->back()->withInput();
        }

        // Log update
        $this->logAudit(
            'SHIFT_UPDATED',
            'work_shifts',
            $id,
            $oldValues,
            $data,
            "Turno atualizado: {$data['name']}"
        );

        $this->setSuccess('Turno atualizado com sucesso!');
        return redirect()->to('/shifts/' . $id);
    }

    /**
     * Delete shift (soft delete)
     *
     * @param int $id Shift ID
     * @return mixed
     */
    public function delete(int $id)
    {
        $this->requireManager();

        $shift = $this->shiftModel->find($id);

        if (!$shift) {
            $this->setError('Turno não encontrado.');
            return redirect()->to('/shifts');
        }

        // Check if shift has active schedules
        $activeSchedules = $this->scheduleModel
            ->where('shift_id', $id)
            ->where('date >=', date('Y-m-d'))
            ->countAllResults();

        if ($activeSchedules > 0) {
            $this->setError("Não é possível excluir este turno pois existem {$activeSchedules} escalas futuras associadas a ele.");
            return redirect()->back();
        }

        // Soft delete
        if (!$this->shiftModel->delete($id)) {
            $this->setError('Erro ao excluir turno.');
            return redirect()->back();
        }

        // Log deletion
        $this->logAudit(
            'SHIFT_DELETED',
            'work_shifts',
            $id,
            ['name' => $shift->name, 'active' => $shift->active],
            null,
            "Turno excluído: {$shift->name}"
        );

        $this->setSuccess('Turno excluído com sucesso!');
        return redirect()->to('/shifts');
    }

    /**
     * Clone shift
     *
     * @param int $id Shift ID to clone
     * @return mixed
     */
    public function clone(int $id)
    {
        $this->requireManager();

        $shift = $this->shiftModel->find($id);

        if (!$shift) {
            $this->setError('Turno não encontrado.');
            return redirect()->to('/shifts');
        }

        // Clone shift
        $newShiftId = $this->shiftModel->cloneShift($id, "Cópia de {$shift->name}");

        if (!$newShiftId) {
            $this->setError('Erro ao clonar turno.');
            return redirect()->back();
        }

        // Log cloning
        $this->logAudit(
            'SHIFT_CLONED',
            'work_shifts',
            $newShiftId,
            null,
            ['source_id' => $id, 'name' => "Cópia de {$shift->name}"],
            "Turno clonado: {$shift->name} → Cópia de {$shift->name}"
        );

        $this->setSuccess('Turno clonado com sucesso!');
        return redirect()->to('/shifts/' . $newShiftId . '/edit');
    }

    /**
     * Toggle shift active status
     *
     * @param int $id Shift ID
     * @return mixed
     */
    public function toggleActive(int $id)
    {
        $this->requireManager();

        $shift = $this->shiftModel->find($id);

        if (!$shift) {
            return $this->respondError('Turno não encontrado.', null, 404);
        }

        $newStatus = !$shift->active;

        if (!$this->shiftModel->update($id, ['active' => $newStatus])) {
            return $this->respondError('Erro ao atualizar status do turno.');
        }

        // Log status change
        $this->logAudit(
            'SHIFT_STATUS_CHANGED',
            'work_shifts',
            $id,
            ['active' => $shift->active],
            ['active' => $newStatus],
            "Status do turno {$shift->name} alterado para " . ($newStatus ? 'ativo' : 'inativo')
        );

        return $this->respondSuccess(
            ['active' => $newStatus],
            $newStatus ? 'Turno ativado com sucesso!' : 'Turno desativado com sucesso!'
        );
    }

    /**
     * Show shift statistics
     *
     * @return string
     */
    public function statistics()
    {
        $this->requireManager();

        $data = [
            'overallStats' => $this->getOverallStatistics(),
            'shiftBreakdown' => $this->getShiftBreakdown(),
            'coverageReport' => $this->getCoverageReport(),
        ];

        return view('shifts/statistics', $data);
    }

    /**
     * Get overall shift statistics
     *
     * @return array
     */
    private function getOverallStatistics(): array
    {
        $totalShifts = $this->shiftModel->countAllResults();
        $activeShifts = $this->shiftModel->where('active', true)->countAllResults();

        $totalEmployeesScheduled = $this->scheduleModel
            ->select('employee_id')
            ->where('date >=', date('Y-m-d'))
            ->groupBy('employee_id')
            ->countAllResults();

        $upcomingSchedules = $this->scheduleModel
            ->where('date >=', date('Y-m-d'))
            ->where('date <=', date('Y-m-d', strtotime('+30 days')))
            ->countAllResults();

        return [
            'total_shifts' => $totalShifts,
            'active_shifts' => $activeShifts,
            'inactive_shifts' => $totalShifts - $activeShifts,
            'employees_scheduled' => $totalEmployeesScheduled,
            'upcoming_schedules' => $upcomingSchedules,
        ];
    }

    /**
     * Get shift breakdown by type
     *
     * @return array
     */
    private function getShiftBreakdown(): array
    {
        $breakdown = [];
        $types = ['morning', 'afternoon', 'night', 'custom'];

        foreach ($types as $type) {
            $count = $this->shiftModel->where('type', $type)->where('active', true)->countAllResults();
            $breakdown[$type] = $count;
        }

        return $breakdown;
    }

    /**
     * Get shift coverage report for next 7 days
     *
     * @return array
     */
    private function getCoverageReport(): array
    {
        $coverage = [];
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime('+7 days'));

        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime("+{$i} days"));

            $schedules = $this->scheduleModel
                ->where('date', $date)
                ->countAllResults();

            $coverage[] = [
                'date' => $date,
                'day_name' => date('l', strtotime($date)),
                'scheduled_count' => $schedules,
            ];
        }

        return $coverage;
    }

    /**
     * Get default color for shift type
     *
     * @param string $type Shift type
     * @return string Hex color code
     */
    private function getDefaultColor(string $type): string
    {
        $colors = [
            'morning' => '#FFA500',   // Orange
            'afternoon' => '#4169E1', // Royal Blue
            'night' => '#2F4F4F',     // Dark Slate Gray
            'custom' => '#228B22',    // Forest Green
        ];

        return $colors[$type] ?? '#6C757D'; // Default gray
    }
}
