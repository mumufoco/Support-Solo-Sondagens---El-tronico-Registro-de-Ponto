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
     * List audit logs - Dashboard view
     * GET /audit
     */
    public function index()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || !in_array($employee['role'], ['admin', 'dpo', 'manager'])) {
            return redirect()->to('/dashboard')
                ->with('error', 'Acesso negado. Apenas administradores podem acessar logs de auditoria.');
        }

        // Get filter options
        $actions = $this->getDistinctActions();
        $entities = $this->getDistinctEntities();
        $levels = ['info', 'warning', 'error', 'critical'];

        // Get statistics
        $stats = $this->getStatistics();

        // Get users for filter
        $users = $this->employeeModel
            ->orderBy('name', 'ASC')
            ->findAll();

        return view('audit/index', [
            'employee' => $employee,
            'actions' => $actions,
            'entities' => $entities,
            'levels' => $levels,
            'stats' => $stats,
            'users' => $users,
            'title' => 'Auditoria e Logs',
        ]);
    }

    /**
     * Get audit logs data (DataTables server-side)
     * POST /audit/data
     */
    public function getData()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || !in_array($employee['role'], ['admin', 'dpo', 'manager'])) {
            return $this->response->setJSON([
                'error' => 'Acesso negado',
            ])->setStatusCode(403);
        }

        $request = $this->request;

        // DataTables parameters
        $draw = intval($request->getPost('draw'));
        $start = intval($request->getPost('start'));
        $length = intval($request->getPost('length'));
        $searchValue = $request->getPost('search')['value'] ?? '';
        $orderColumnIndex = intval($request->getPost('order')[0]['column'] ?? 0);
        $orderDir = $request->getPost('order')[0]['dir'] ?? 'desc';

        // Custom filters
        $filterUserId = $request->getPost('filter_user_id');
        $filterAction = $request->getPost('filter_action');
        $filterEntity = $request->getPost('filter_entity');
        $filterLevel = $request->getPost('filter_level');
        $filterStartDate = $request->getPost('filter_start_date');
        $filterEndDate = $request->getPost('filter_end_date');

        // Column mapping
        $columns = ['id', 'user_id', 'action', 'entity_type', 'description', 'level', 'created_at'];
        $orderColumn = $columns[$orderColumnIndex] ?? 'created_at';

        // Build query
        $db = \Config\Database::connect();
        $builder = $db->table('audit_logs');

        // Apply filters
        if ($filterUserId) {
            $builder->where('user_id', $filterUserId);
        }

        if ($filterAction) {
            $builder->where('action', $filterAction);
        }

        if ($filterEntity) {
            $builder->where('entity_type', $filterEntity);
        }

        if ($filterLevel) {
            $builder->where('level', $filterLevel);
        }

        if ($filterStartDate) {
            $builder->where('created_at >=', $filterStartDate . ' 00:00:00');
        }

        if ($filterEndDate) {
            $builder->where('created_at <=', $filterEndDate . ' 23:59:59');
        }

        // Global search
        if ($searchValue) {
            $builder->groupStart()
                ->like('action', $searchValue)
                ->orLike('entity_type', $searchValue)
                ->orLike('description', $searchValue)
                ->orLike('ip_address', $searchValue)
                ->groupEnd();
        }

        // Get total records (before pagination)
        $totalRecords = $builder->countAllResults(false);

        // Apply ordering and pagination
        $builder->orderBy($orderColumn, $orderDir);
        $builder->limit($length, $start);

        $logs = $builder->get()->getResult();

        // Get employee names
        $employeeIds = array_unique(array_filter(array_column($logs, 'user_id')));
        $employees = [];

        if (!empty($employeeIds)) {
            $employeeData = $this->employeeModel->whereIn('id', $employeeIds)->findAll();
            foreach ($employeeData as $emp) {
                $employees[$emp->id] = $emp->name;
            }
        }

        // Format data for DataTables
        $data = [];
        foreach ($logs as $log) {
            $userName = $employees[$log->user_id] ?? ($log->user_id ? "ID: {$log->user_id}" : 'Sistema');

            $data[] = [
                'id' => $log->id,
                'user' => $userName,
                'action' => $this->formatAction($log->action),
                'entity' => $this->formatEntity($log->entity_type, $log->entity_id),
                'description' => $log->description ?? '-',
                'level' => $this->formatLevel($log->level),
                'ip_address' => $log->ip_address ?? '-',
                'created_at' => date('d/m/Y H:i:s', strtotime($log->created_at)),
                'details' => $log->id, // For modal button
            ];
        }

        // Get total records without filters
        $totalRecordsNoFilter = $db->table('audit_logs')->countAll();

        return $this->response->setJSON([
            'draw' => $draw,
            'recordsTotal' => $totalRecordsNoFilter,
            'recordsFiltered' => $totalRecords,
            'data' => $data,
        ]);
    }

    /**
     * Get statistics for dashboard
     */
    protected function getStatistics(): array
    {
        $db = \Config\Database::connect();

        // Total logs
        $total = $db->table('audit_logs')->countAll();

        // Logs today
        $today = $db->table('audit_logs')
            ->where('created_at >=', date('Y-m-d') . ' 00:00:00')
            ->countAllResults();

        // Logs this week
        $thisWeek = $db->table('audit_logs')
            ->where('created_at >=', date('Y-m-d', strtotime('-7 days')) . ' 00:00:00')
            ->countAllResults();

        // Critical logs (last 30 days)
        $critical = $db->table('audit_logs')
            ->whereIn('level', ['error', 'critical'])
            ->where('created_at >=', date('Y-m-d', strtotime('-30 days')) . ' 00:00:00')
            ->countAllResults();

        // Most active users (last 30 days)
        $activeUsers = $db->table('audit_logs')
            ->select('user_id, COUNT(*) as count')
            ->where('created_at >=', date('Y-m-d', strtotime('-30 days')) . ' 00:00:00')
            ->where('user_id IS NOT NULL')
            ->groupBy('user_id')
            ->orderBy('count', 'DESC')
            ->limit(5)
            ->get()
            ->getResult();

        // Get employee names for active users
        $userStats = [];
        foreach ($activeUsers as $user) {
            $employee = $this->employeeModel->find($user->user_id);
            $userStats[] = [
                'name' => $employee->name ?? "ID: {$user->user_id}",
                'count' => $user->count,
            ];
        }

        // Most common actions (last 30 days)
        $commonActions = $db->table('audit_logs')
            ->select('action, COUNT(*) as count')
            ->where('created_at >=', date('Y-m-d', strtotime('-30 days')) . ' 00:00:00')
            ->groupBy('action')
            ->orderBy('count', 'DESC')
            ->limit(5)
            ->get()
            ->getResult();

        return [
            'total' => $total,
            'today' => $today,
            'this_week' => $thisWeek,
            'critical' => $critical,
            'active_users' => $userStats,
            'common_actions' => $commonActions,
        ];
    }

    /**
     * Get distinct actions
     */
    protected function getDistinctActions(): array
    {
        $db = \Config\Database::connect();

        return $db->table('audit_logs')
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->get()
            ->getResultArray();
    }

    /**
     * Get distinct entities
     */
    protected function getDistinctEntities(): array
    {
        $db = \Config\Database::connect();

        return $db->table('audit_logs')
            ->select('entity_type')
            ->distinct()
            ->orderBy('entity_type')
            ->get()
            ->getResultArray();
    }

    /**
     * Format action for display
     */
    protected function formatAction(string $action): string
    {
        $badges = [
            'CREATE' => 'success',
            'UPDATE' => 'info',
            'DELETE' => 'warning',
            'PURGE' => 'danger',
            'VIEW' => 'secondary',
            'EXPORT' => 'primary',
            'LOGIN' => 'success',
            'LOGOUT' => 'secondary',
            'GRANT_CONSENT' => 'success',
            'REVOKE_CONSENT' => 'warning',
        ];

        $class = $badges[$action] ?? 'secondary';

        return "<span class=\"badge badge-{$class}\">{$action}</span>";
    }

    /**
     * Format entity for display
     */
    protected function formatEntity(string $entityType, ?int $entityId): string
    {
        $formatted = str_replace('_', ' ', ucfirst($entityType));

        if ($entityId) {
            return "{$formatted} #{$entityId}";
        }

        return $formatted;
    }

    /**
     * Format level for display
     */
    protected function formatLevel(string $level): string
    {
        $badges = [
            'info' => 'info',
            'warning' => 'warning',
            'error' => 'danger',
            'critical' => 'danger',
        ];

        $icons = [
            'info' => 'info-circle',
            'warning' => 'exclamation-triangle',
            'error' => 'times-circle',
            'critical' => 'skull-crossbones',
        ];

        $class = $badges[$level] ?? 'secondary';
        $icon = $icons[$level] ?? 'circle';

        return "<span class=\"badge badge-{$class}\"><i class=\"fas fa-{$icon}\"></i> " . strtoupper($level) . "</span>";
    }

    /**
     * Show audit log details
     * GET /audit/{id}
     */
    public function show($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || !in_array($employee['role'], ['admin', 'dpo', 'manager'])) {
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
        $oldData = $log->old_values ? (is_string($log->old_values) ? json_decode($log->old_values, true) : $log->old_values) : null;
        $newData = $log->new_values ? (is_string($log->new_values) ? json_decode($log->new_values, true) : $log->new_values) : null;

        return view('audit/show', [
            'employee' => $employee,
            'log' => $log,
            'user' => $user,
            'oldData' => $oldData,
            'newData' => $newData,
        ]);
    }

    /**
     * Get audit log details via AJAX (for modal)
     * GET /audit/details/{id}
     */
    public function details(int $id)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || !in_array($employee['role'], ['admin', 'dpo', 'manager'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso negado',
            ])->setStatusCode(403);
        }

        $log = $this->auditModel->find($id);

        if (!$log) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Log não encontrado',
            ])->setStatusCode(404);
        }

        // Get employee name
        $employee = null;
        if ($log->user_id) {
            $employee = $this->employeeModel->find($log->user_id);
        }

        // Decode JSON values
        $oldValues = $log->old_values ? (is_string($log->old_values) ? json_decode($log->old_values, true) : $log->old_values) : null;
        $newValues = $log->new_values ? (is_string($log->new_values) ? json_decode($log->new_values, true) : $log->new_values) : null;

        return $this->response->setJSON([
            'success' => true,
            'log' => [
                'id' => $log->id,
                'action' => $log->action,
                'entity_type' => $log->entity_type,
                'entity_id' => $log->entity_id,
                'description' => $log->description,
                'level' => $log->level,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'url' => $log->url,
                'method' => $log->method,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'created_at' => $log->created_at,
            ],
            'employee' => $employee ? [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
            ] : null,
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
        if (!session()->has('user_id')) {
            return null;
        }

        $employeeId = session()->get('user_id');
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
