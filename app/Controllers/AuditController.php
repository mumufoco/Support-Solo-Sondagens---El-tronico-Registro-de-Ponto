<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;
use App\Models\AuditLogModel;

/**
 * Audit Controller
 *
 * Handles audit log viewing and searching (Admin only)
 */
class AuditController extends BaseController
{
    protected $employeeModel;
    protected $auditModel;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->auditModel = new AuditLogModel();
        helper(['datetime', 'format']);
    }

    /**
     * List audit logs
     * GET /audit
     */
    public function index()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')
                ->with('error', 'Acesso negado. Apenas administradores podem acessar logs de auditoria.');
        }

        $perPage = 50;

        // Filters
        $action = $this->request->getGet('action');
        $userId = $this->request->getGet('user_id');
        $level = $this->request->getGet('level');
        $dateFrom = $this->request->getGet('date_from');
        $dateTo = $this->request->getGet('date_to');
        $search = $this->request->getGet('search');

        // Build query
        $query = $this->auditModel;

        if ($action) {
            $query->where('action', $action);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($level) {
            $query->where('level', $level);
        }

        if ($dateFrom) {
            $query->where('created_at >=', $dateFrom . ' 00:00:00');
        }

        if ($dateTo) {
            $query->where('created_at <=', $dateTo . ' 23:59:59');
        }

        if ($search) {
            $query->groupStart()
                ->like('action', $search)
                ->orLike('description', $search)
                ->orLike('table_name', $search)
                ->groupEnd();
        }

        $logs = $query->orderBy('created_at', 'DESC')
            ->paginate($perPage);

        // Get employee names
        foreach ($logs as &$log) {
            if ($log->user_id) {
                $user = $this->employeeModel->find($log->user_id);
                $log->user_name = $user ? $user->name : 'Desconhecido';
            } else {
                $log->user_name = 'Sistema';
            }
        }

        // Get unique actions for filter
        $actions = $this->auditModel
            ->distinct()
            ->select('action')
            ->orderBy('action', 'ASC')
            ->findColumn('action');

        // Get users for filter
        $users = $this->employeeModel
            ->where('active', true)
            ->orderBy('name', 'ASC')
            ->findAll();

        return view('audit/index', [
            'employee' => $employee,
            'logs' => $logs,
            'pager' => $this->auditModel->pager,
            'actions' => $actions,
            'users' => $users,
            'filters' => [
                'action' => $action,
                'user_id' => $userId,
                'level' => $level,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show audit log details
     * GET /audit/{id}
     */
    public function show($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        $log = $this->auditModel->find($id);

        if (!$log) {
            return redirect()->to('/audit')
                ->with('error', 'Log de auditoria não encontrado.');
        }

        // Get user data
        $user = null;
        if ($log->user_id) {
            $user = $this->employeeModel->find($log->user_id);
        }

        // Decode JSON data
        $oldData = $log->old_data ? json_decode($log->old_data, true) : null;
        $newData = $log->new_data ? json_decode($log->new_data, true) : null;

        return view('audit/show', [
            'employee' => $employee,
            'log' => $log,
            'user' => $user,
            'oldData' => $oldData,
            'newData' => $newData,
        ]);
    }

    /**
     * Export audit logs
     * GET /audit/export
     */
    public function export()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        $dateFrom = $this->request->getGet('date_from') ?: date('Y-m-01');
        $dateTo = $this->request->getGet('date_to') ?: date('Y-m-d');

        $logs = $this->auditModel
            ->where('created_at >=', $dateFrom . ' 00:00:00')
            ->where('created_at <=', $dateTo . ' 23:59:59')
            ->orderBy('created_at', 'DESC')
            ->findAll();

        // Generate CSV
        $filename = "audit_log_{$dateFrom}_to_{$dateTo}.csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // Header
        fputcsv($output, [
            'ID',
            'Data/Hora',
            'Usuário',
            'Ação',
            'Tabela',
            'Registro ID',
            'Descrição',
            'Nível',
            'IP',
        ]);

        // Data rows
        foreach ($logs as $log) {
            $user = $log->user_id ? $this->employeeModel->find($log->user_id) : null;
            $userName = $user ? $user->name : 'Sistema';

            fputcsv($output, [
                $log->id,
                format_datetime_br($log->created_at),
                $userName,
                $log->action,
                $log->table_name ?? '',
                $log->record_id ?? '',
                $log->description,
                $log->level,
                $log->ip_address ?? '',
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Clear old logs (Admin only)
     * POST /audit/clear
     */
    public function clear()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        $days = $this->request->getPost('days') ?: 90;

        if ($days < 30) {
            return redirect()->back()
                ->with('error', 'Não é possível excluir logs com menos de 30 dias.');
        }

        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $deletedCount = $this->auditModel
            ->where('created_at <', $cutoffDate)
            ->delete();

        // Log this action
        $this->auditModel->log(
            $employee['id'],
            'AUDIT_LOGS_CLEARED',
            'audit_logs',
            null,
            null,
            ['days' => $days, 'deleted_count' => $deletedCount],
            "Logs de auditoria mais antigos que {$days} dias foram excluídos ({$deletedCount} registros)",
            'warning'
        );

        return redirect()->to('/audit')
            ->with('success', "{$deletedCount} registro(s) de auditoria foram excluídos.");
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
