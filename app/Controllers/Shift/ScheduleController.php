<?php

namespace App\Controllers\Shift;

use App\Controllers\BaseController;
use App\Models\ScheduleModel;
use App\Models\WorkShiftModel;
use App\Models\EmployeeModel;

/**
 * ScheduleController
 *
 * Manages employee shift assignments and schedules
 * Handles recurring schedules, bulk assignments, and calendar views
 *
 * @package App\Controllers\Shift
 */
class ScheduleController extends BaseController
{
    protected ScheduleModel $scheduleModel;
    protected WorkShiftModel $shiftModel;
    protected EmployeeModel $employeeModel;

    public function __construct()
    {
        $this->scheduleModel = new ScheduleModel();
        $this->shiftModel = new WorkShiftModel();
        $this->employeeModel = new EmployeeModel();
    }

    /**
     * Show calendar view with schedules
     *
     * @return string
     */
    public function index()
    {
        $this->requireManager();

        // Get date parameters (default to current month)
        $year = $this->request->getGet('year') ?: date('Y');
        $month = $this->request->getGet('month') ?: date('m');
        $view = $this->request->getGet('view') ?: 'month'; // month, week, day

        // Calculate date range
        $startDate = date('Y-m-d', strtotime("{$year}-{$month}-01"));
        $endDate = date('Y-m-t', strtotime($startDate));

        // Get schedules for the period
        $schedules = $this->scheduleModel->getScheduleByDateRange($startDate, $endDate);

        // Get all active shifts for the legend
        $shifts = $this->shiftModel->where('active', true)->findAll();

        // Get all active employees
        $employees = $this->employeeModel->where('active', true)->findAll();

        $data = [
            'year' => $year,
            'month' => $month,
            'view' => $view,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'schedules' => $schedules,
            'shifts' => $shifts,
            'employees' => $employees,
            'calendarData' => $this->buildCalendarData($schedules, $startDate, $endDate),
        ];

        return view('schedules/index', $data);
    }

    /**
     * Show create schedule form
     *
     * @return string
     */
    public function create()
    {
        $this->requireManager();

        $data = [
            'shifts' => $this->shiftModel->where('active', true)->findAll(),
            'employees' => $this->employeeModel->where('active', true)->findAll(),
        ];

        return view('schedules/create', $data);
    }

    /**
     * Store new schedule
     *
     * @return mixed
     */
    public function store()
    {
        $this->requireManager();

        // Validation rules
        $rules = [
            'employee_id' => 'required|integer|is_not_unique[employees.id]',
            'shift_id' => 'required|integer|is_not_unique[work_shifts.id]',
            'date' => 'required|valid_date[Y-m-d]',
            'is_recurring' => 'permit_empty|in_list[0,1]',
        ];

        // Add rules for recurring schedules
        if ($this->request->getPost('is_recurring') == '1') {
            $rules['recurrence_end_date'] = 'required|valid_date[Y-m-d]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $employeeId = (int)$this->request->getPost('employee_id');
        $shiftId = (int)$this->request->getPost('shift_id');
        $date = $this->request->getPost('date');
        $isRecurring = $this->request->getPost('is_recurring') == '1';

        // Check if employee is already scheduled for this date
        if ($this->scheduleModel->isEmployeeScheduled($employeeId, $date)) {
            $this->setError('Este funcionário já possui um turno agendado para esta data.');
            return redirect()->back()->withInput();
        }

        // Prepare data
        $data = [
            'employee_id' => $employeeId,
            'shift_id' => $shiftId,
            'date' => $date,
            'week_day' => (int)date('w', strtotime($date)),
            'is_recurring' => $isRecurring ? 1 : 0,
            'recurrence_end_date' => $isRecurring ? $this->request->getPost('recurrence_end_date') : null,
            'status' => 'scheduled',
            'notes' => $this->request->getPost('notes'),
            'created_by' => $this->currentUser->id,
        ];

        // Create schedule (or recurring schedules)
        if ($isRecurring) {
            $success = $this->scheduleModel->createRecurringSchedule($data);
            $message = 'Escala recorrente criada com sucesso!';
        } else {
            $scheduleId = $this->scheduleModel->insert($data);
            $success = (bool)$scheduleId;
            $message = 'Escala criada com sucesso!';

            if ($success) {
                // Log creation
                $this->logAudit(
                    'SCHEDULE_CREATED',
                    'schedules',
                    $scheduleId,
                    null,
                    $data,
                    "Escala criada para funcionário ID {$employeeId} em {$date}"
                );
            }
        }

        if (!$success) {
            $this->setError('Erro ao criar escala.');
            return redirect()->back()->withInput();
        }

        $this->setSuccess($message);
        return redirect()->to('/schedules');
    }

    /**
     * Show edit schedule form
     *
     * @param int $id Schedule ID
     * @return mixed
     */
    public function edit(int $id)
    {
        $this->requireManager();

        $schedule = $this->scheduleModel->find($id);

        if (!$schedule) {
            $this->setError('Escala não encontrada.');
            return redirect()->to('/schedules');
        }

        $data = [
            'schedule' => $schedule,
            'shifts' => $this->shiftModel->where('active', true)->findAll(),
            'employees' => $this->employeeModel->where('active', true)->findAll(),
        ];

        return view('schedules/edit', $data);
    }

    /**
     * Update schedule
     *
     * @param int $id Schedule ID
     * @return mixed
     */
    public function update(int $id)
    {
        $this->requireManager();

        $schedule = $this->scheduleModel->find($id);

        if (!$schedule) {
            $this->setError('Escala não encontrada.');
            return redirect()->to('/schedules');
        }

        // Validation rules
        $rules = [
            'employee_id' => 'required|integer|is_not_unique[employees.id]',
            'shift_id' => 'required|integer|is_not_unique[work_shifts.id]',
            'date' => 'required|valid_date[Y-m-d]',
            'status' => 'required|in_list[scheduled,completed,cancelled,absent]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $employeeId = (int)$this->request->getPost('employee_id');
        $date = $this->request->getPost('date');

        // Check if employee is already scheduled (excluding current schedule)
        if ($this->scheduleModel->isEmployeeScheduled($employeeId, $date, $id)) {
            $this->setError('Este funcionário já possui outro turno agendado para esta data.');
            return redirect()->back()->withInput();
        }

        // Prepare old values for audit
        $oldValues = [
            'employee_id' => $schedule->employee_id,
            'shift_id' => $schedule->shift_id,
            'date' => $schedule->date,
            'status' => $schedule->status,
        ];

        // Prepare new data
        $data = [
            'employee_id' => $employeeId,
            'shift_id' => (int)$this->request->getPost('shift_id'),
            'date' => $date,
            'week_day' => (int)date('w', strtotime($date)),
            'status' => $this->request->getPost('status'),
            'notes' => $this->request->getPost('notes'),
        ];

        // Update schedule
        if (!$this->scheduleModel->update($id, $data)) {
            $this->setError('Erro ao atualizar escala.');
            return redirect()->back()->withInput();
        }

        // Log update
        $this->logAudit(
            'SCHEDULE_UPDATED',
            'schedules',
            $id,
            $oldValues,
            $data,
            "Escala atualizada para funcionário ID {$employeeId}"
        );

        $this->setSuccess('Escala atualizada com sucesso!');
        return redirect()->to('/schedules');
    }

    /**
     * Delete schedule
     *
     * @param int $id Schedule ID
     * @return mixed
     */
    public function delete(int $id)
    {
        $this->requireManager();

        $schedule = $this->scheduleModel->find($id);

        if (!$schedule) {
            $this->setError('Escala não encontrada.');
            return redirect()->to('/schedules');
        }

        // Check if schedule is in the past
        if ($schedule->date < date('Y-m-d')) {
            $this->setWarning('Não é recomendado excluir escalas passadas.');
        }

        if (!$this->scheduleModel->delete($id)) {
            $this->setError('Erro ao excluir escala.');
            return redirect()->back();
        }

        // Log deletion
        $this->logAudit(
            'SCHEDULE_DELETED',
            'schedules',
            $id,
            [
                'employee_id' => $schedule->employee_id,
                'date' => $schedule->date,
                'shift_id' => $schedule->shift_id,
            ],
            null,
            "Escala excluída para funcionário ID {$schedule->employee_id} em {$schedule->date}"
        );

        $this->setSuccess('Escala excluída com sucesso!');
        return redirect()->to('/schedules');
    }

    /**
     * Bulk assign shifts to multiple employees
     *
     * @return mixed
     */
    public function bulkAssign()
    {
        $this->requireManager();

        // Validation rules
        $rules = [
            'employee_ids' => 'required',
            'shift_id' => 'required|integer|is_not_unique[work_shifts.id]',
            'start_date' => 'required|valid_date[Y-m-d]',
            'end_date' => 'required|valid_date[Y-m-d]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $employeeIds = $this->request->getPost('employee_ids');
        if (is_string($employeeIds)) {
            $employeeIds = explode(',', $employeeIds);
        }
        $employeeIds = array_map('intval', $employeeIds);

        $shiftId = (int)$this->request->getPost('shift_id');
        $startDate = $this->request->getPost('start_date');
        $endDate = $this->request->getPost('end_date');
        $weekDays = $this->request->getPost('week_days') ?: [1, 2, 3, 4, 5]; // Default Mon-Fri

        // Bulk assign
        $assigned = $this->scheduleModel->bulkAssign($employeeIds, $shiftId, $startDate, $endDate, $weekDays);

        if ($assigned === false) {
            $this->setError('Erro ao atribuir turnos em massa.');
            return redirect()->back()->withInput();
        }

        // Log bulk assignment
        $this->logAudit(
            'SCHEDULE_BULK_ASSIGNED',
            'schedules',
            null,
            null,
            [
                'employee_count' => count($employeeIds),
                'shift_id' => $shiftId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'assigned_count' => $assigned,
            ],
            "Atribuição em massa: {$assigned} escalas criadas para " . count($employeeIds) . " funcionários"
        );

        $this->setSuccess("{$assigned} escalas criadas com sucesso!");
        return redirect()->to('/schedules');
    }

    /**
     * Show bulk assignment form
     *
     * @return string
     */
    public function bulkAssignForm()
    {
        $this->requireManager();

        $data = [
            'shifts' => $this->shiftModel->where('active', true)->findAll(),
            'employees' => $this->employeeModel->where('active', true)->findAll(),
        ];

        return view('schedules/bulk_assign', $data);
    }

    /**
     * Get employee schedules (for employee view)
     *
     * @return string
     */
    public function mySchedules()
    {
        $this->requireAuth();

        $employeeId = $this->currentUser->id;

        // Get date range
        $startDate = $this->request->getGet('start') ?: date('Y-m-01');
        $endDate = $this->request->getGet('end') ?: date('Y-m-t');

        // Get schedules for employee
        $schedules = $this->scheduleModel
            ->select('schedules.*, work_shifts.name as shift_name, work_shifts.start_time, work_shifts.end_time, work_shifts.color')
            ->join('work_shifts', 'work_shifts.id = schedules.shift_id')
            ->where('schedules.employee_id', $employeeId)
            ->where('schedules.date >=', $startDate)
            ->where('schedules.date <=', $endDate)
            ->orderBy('schedules.date', 'ASC')
            ->findAll();

        $data = [
            'schedules' => $schedules,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];

        return view('schedules/my_schedules', $data);
    }

    /**
     * Build calendar data structure
     *
     * @param array $schedules Schedules data
     * @param string $startDate Start date
     * @param string $endDate End date
     * @return array Calendar data
     */
    private function buildCalendarData(array $schedules, string $startDate, string $endDate): array
    {
        $calendarData = [];

        // Group schedules by date
        foreach ($schedules as $schedule) {
            $date = $schedule->date;

            if (!isset($calendarData[$date])) {
                $calendarData[$date] = [];
            }

            $calendarData[$date][] = $schedule;
        }

        return $calendarData;
    }

    /**
     * Export schedules to CSV
     *
     * @return mixed
     */
    public function export()
    {
        $this->requireManager();

        $startDate = $this->request->getGet('start') ?: date('Y-m-01');
        $endDate = $this->request->getGet('end') ?: date('Y-m-t');

        $schedules = $this->scheduleModel
            ->select('schedules.*, employees.name as employee_name, work_shifts.name as shift_name, work_shifts.start_time, work_shifts.end_time')
            ->join('employees', 'employees.id = schedules.employee_id')
            ->join('work_shifts', 'work_shifts.id = schedules.shift_id')
            ->where('schedules.date >=', $startDate)
            ->where('schedules.date <=', $endDate)
            ->orderBy('schedules.date', 'ASC')
            ->orderBy('employees.name', 'ASC')
            ->findAll();

        // Generate CSV
        $filename = "escalas_{$startDate}_to_{$endDate}.csv";

        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename={$filename}");

        $output = fopen('php://output', 'w');

        // UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Headers
        fputcsv($output, ['Data', 'Funcionário', 'Turno', 'Horário Início', 'Horário Fim', 'Status', 'Observações']);

        // Data
        foreach ($schedules as $schedule) {
            fputcsv($output, [
                date('d/m/Y', strtotime($schedule->date)),
                $schedule->employee_name,
                $schedule->shift_name,
                substr($schedule->start_time, 0, 5),
                substr($schedule->end_time, 0, 5),
                $this->translateStatus($schedule->status),
                $schedule->notes ?? '',
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Translate schedule status to Portuguese
     *
     * @param string $status Status code
     * @return string Translated status
     */
    private function translateStatus(string $status): string
    {
        $statuses = [
            'scheduled' => 'Agendado',
            'completed' => 'Concluído',
            'cancelled' => 'Cancelado',
            'absent' => 'Ausente',
        ];

        return $statuses[$status] ?? $status;
    }
}
