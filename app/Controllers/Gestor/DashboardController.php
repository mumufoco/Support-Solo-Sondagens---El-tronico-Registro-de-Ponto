<?php

namespace App\Controllers\Gestor;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;
use App\Models\JustificationModel;
use App\Models\TimePunchModel;
use App\Models\AuditLogModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $employeeModel = new EmployeeModel();
        $justificationModel = new JustificationModel();
        $timePunchModel = new TimePunchModel();

        // Pegar funcionários da equipe do gestor logado
        $currentEmployeeId = session()->get('employee_id');

        if (!$currentEmployeeId) {
            return redirect()->to('/auth/login')->with('error', 'Sessão expirada.');
        }

        // Get all subordinates (direct + indirect) using hierarchy
        $teamEmployees = $employeeModel->getAllSubordinates($currentEmployeeId, true);

        $data = [
            'team_count' => count($teamEmployees),
            'team_employees' => $teamEmployees,

            // Justificativas pendentes
            'pending_justifications' => $justificationModel
                ->select('justifications.*, employees.name as employee_name')
                ->join('employees', 'employees.id = justifications.employee_id')
                ->where('justifications.status', 'pending')
                ->orderBy('justifications.created_at', 'DESC')
                ->findAll(10),

            // Resumo de presenças do dia
            'attendance_today' => $this->getAttendanceToday(),

            // Estatísticas da equipe
            'team_stats' => $this->getTeamStats(),
        ];

        return view('gestor/dashboard', $data);
    }

    /**
     * Aprovar justificativa
     */
    public function approveJustification($id)
    {
        $justificationModel = new JustificationModel();
        $justification = $justificationModel->find($id);

        if (!$justification) {
            return redirect()->back()
                ->with('error', 'Justificativa não encontrada.');
        }

        $justificationModel->update($id, [
            'status' => 'approved',
            'approved_by' => session()->get('employee_id'),
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        // Log de auditoria
        $this->logAudit('approve_justification', 'justification', $id, null, null, "Approved justification #{$id}");

        return redirect()->back()
            ->with('message', 'Justificativa aprovada com sucesso.');
    }

    /**
     * Rejeitar justificativa
     */
    public function rejectJustification($id)
    {
        $justificationModel = new JustificationModel();
        $justification = $justificationModel->find($id);

        if (!$justification) {
            return redirect()->back()
                ->with('error', 'Justificativa não encontrada.');
        }

        $justificationModel->update($id, [
            'status' => 'rejected',
            'approved_by' => session()->get('employee_id'),
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        // Log de auditoria
        $this->logAudit('reject_justification', 'justification', $id, null, null, "Rejected justification #{$id}");

        return redirect()->back()
            ->with('message', 'Justificativa rejeitada.');
    }

    /**
     * Retorna presenças de hoje
     */
    private function getAttendanceToday(): array
    {
        $timePunchModel = new TimePunchModel();

        $punches = $timePunchModel
            ->select('time_punches.*, employees.name as employee_name')
            ->join('employees', 'employees.id = time_punches.employee_id')
            ->where('DATE(time_punches.punch_time)', date('Y-m-d'))
            ->orderBy('time_punches.punch_time', 'DESC')
            ->findAll();

        // Agrupar por funcionário
        $attendance = [];

        foreach ($punches as $punch) {
            $employeeId = $punch->employee_id;

            if (!isset($attendance[$employeeId])) {
                $attendance[$employeeId] = [
                    'employee_name' => $punch->employee_name,
                    'punches' => [],
                ];
            }

            $attendance[$employeeId]['punches'][] = $punch;
        }

        return array_values($attendance);
    }

    /**
     * Estatísticas da equipe
     */
    private function getTeamStats(): array
    {
        $employeeModel = new EmployeeModel();
        $timePunchModel = new TimePunchModel();

        $totalEmployees = $employeeModel->where('active', true)->countAllResults();

        // Funcionários que bateram ponto hoje
        $punchedToday = $timePunchModel
            ->select('DISTINCT employee_id')
            ->where('DATE(punch_time)', date('Y-m-d'))
            ->countAllResults(false);

        $attendanceRate = $totalEmployees > 0 ? round(($punchedToday / $totalEmployees) * 100, 1) : 0;

        return [
            'total_employees' => $totalEmployees,
            'punched_today' => $punchedToday,
            'absent_today' => $totalEmployees - $punchedToday,
            'attendance_rate' => $attendanceRate,
        ];
    }

}
