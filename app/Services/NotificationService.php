<?php

namespace App\Services;

use App\Models\NotificationModel;
use App\Models\EmployeeModel;
use App\Models\SettingModel;

/**
 * Notification Service
 *
 * Handles notification creation, delivery, and management
 */
class NotificationService
{
    protected $notificationModel;
    protected $employeeModel;
    protected $settingModel;

    public function __construct()
    {
        $this->notificationModel = new NotificationModel();
        $this->employeeModel = new EmployeeModel();
        $this->settingModel = new SettingModel();
    }

    /**
     * Create a notification for a specific employee
     *
     * @param int $employeeId
     * @param string $title
     * @param string $message
     * @param string $type
     * @param string|null $link
     * @return int|false Notification ID or false on failure
     */
    public function notify(
        int $employeeId,
        string $title,
        string $message,
        string $type = 'info',
        ?string $link = null
    ) {
        $data = [
            'employee_id' => $employeeId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link,
            'read' => false,
        ];

        return $this->notificationModel->insert($data);
    }

    /**
     * Create notifications for multiple employees
     *
     * @param array $employeeIds
     * @param string $title
     * @param string $message
     * @param string $type
     * @param string|null $link
     * @return int Count of created notifications
     */
    public function notifyMultiple(
        array $employeeIds,
        string $title,
        string $message,
        string $type = 'info',
        ?string $link = null
    ): int {
        $count = 0;

        foreach ($employeeIds as $employeeId) {
            if ($this->notify($employeeId, $title, $message, $type, $link)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Notify all admins
     *
     * @param string $title
     * @param string $message
     * @param string $type
     * @param string|null $link
     * @return int Count of created notifications
     */
    public function notifyAdmins(
        string $title,
        string $message,
        string $type = 'info',
        ?string $link = null
    ): int {
        $admins = $this->employeeModel
            ->where('role', 'admin')
            ->where('active', true)
            ->findAll();

        $adminIds = array_map(function ($admin) {
            return $admin->id;
        }, $admins);

        return $this->notifyMultiple($adminIds, $title, $message, $type, $link);
    }

    /**
     * Notify all managers
     *
     * @param string $title
     * @param string $message
     * @param string $type
     * @param string|null $link
     * @return int Count of created notifications
     */
    public function notifyManagers(
        string $title,
        string $message,
        string $type = 'info',
        ?string $link = null
    ): int {
        $managers = $this->employeeModel
            ->whereIn('role', ['admin', 'gestor'])
            ->where('active', true)
            ->findAll();

        $managerIds = array_map(function ($manager) {
            return $manager->id;
        }, $managers);

        return $this->notifyMultiple($managerIds, $title, $message, $type, $link);
    }

    /**
     * Notify department employees
     *
     * @param string $department
     * @param string $title
     * @param string $message
     * @param string $type
     * @param string|null $link
     * @return int Count of created notifications
     */
    public function notifyDepartment(
        string $department,
        string $title,
        string $message,
        string $type = 'info',
        ?string $link = null
    ): int {
        $employees = $this->employeeModel
            ->where('department', $department)
            ->where('active', true)
            ->findAll();

        $employeeIds = array_map(function ($employee) {
            return $employee->id;
        }, $employees);

        return $this->notifyMultiple($employeeIds, $title, $message, $type, $link);
    }

    /**
     * Mark notification as read
     *
     * @param int $notificationId
     * @param int $employeeId
     * @return bool
     */
    public function markAsRead(int $notificationId, int $employeeId): bool
    {
        // Verify ownership
        $notification = $this->notificationModel->find($notificationId);

        if (!$notification || $notification->employee_id !== $employeeId) {
            return false;
        }

        return $this->notificationModel->update($notificationId, [
            'read' => true,
            'read_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Mark all notifications as read for an employee
     *
     * @param int $employeeId
     * @return int Count of updated notifications
     */
    public function markAllAsRead(int $employeeId): int
    {
        return $this->notificationModel
            ->where('employee_id', $employeeId)
            ->where('read', false)
            ->set([
                'read' => true,
                'read_at' => date('Y-m-d H:i:s'),
            ])
            ->update();
    }

    /**
     * Get unread notifications for an employee
     *
     * @param int $employeeId
     * @param int $limit
     * @return array
     */
    public function getUnread(int $employeeId, int $limit = 10): array
    {
        return $this->notificationModel
            ->where('employee_id', $employeeId)
            ->where('read', false)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get all notifications for an employee
     *
     * @param int $employeeId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAll(int $employeeId, int $limit = 20, int $offset = 0): array
    {
        return $this->notificationModel
            ->where('employee_id', $employeeId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit, $offset)
            ->findAll();
    }

    /**
     * Count unread notifications for an employee
     *
     * @param int $employeeId
     * @return int
     */
    public function countUnread(int $employeeId): int
    {
        return $this->notificationModel
            ->where('employee_id', $employeeId)
            ->where('read', false)
            ->countAllResults();
    }

    /**
     * Delete notification
     *
     * @param int $notificationId
     * @param int $employeeId
     * @return bool
     */
    public function delete(int $notificationId, int $employeeId): bool
    {
        // Verify ownership
        $notification = $this->notificationModel->find($notificationId);

        if (!$notification || $notification->employee_id !== $employeeId) {
            return false;
        }

        return $this->notificationModel->delete($notificationId);
    }

    /**
     * Delete all read notifications for an employee
     *
     * @param int $employeeId
     * @return int Count of deleted notifications
     */
    public function deleteAllRead(int $employeeId): int
    {
        return $this->notificationModel
            ->where('employee_id', $employeeId)
            ->where('read', true)
            ->delete();
    }

    /**
     * Delete old notifications (cleanup)
     *
     * @param int $daysOld
     * @return int Count of deleted notifications
     */
    public function deleteOld(int $daysOld = 30): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));

        return $this->notificationModel
            ->where('created_at <', $cutoffDate)
            ->where('read', true)
            ->delete();
    }

    /**
     * Specialized notification: New employee registration
     *
     * @param int $employeeId
     * @return int
     */
    public function notifyNewEmployeeRegistration(int $employeeId): int
    {
        $employee = $this->employeeModel->find($employeeId);

        if (!$employee) {
            return 0;
        }

        return $this->notifyAdmins(
            'Novo cadastro pendente',
            "O funcionário {$employee->name} ({$employee->email}) solicitou cadastro no sistema.",
            'employee_registration',
            '/employees/pending'
        );
    }

    /**
     * Specialized notification: Justification submitted
     *
     * @param int $justificationId
     * @param int $employeeId
     * @return int
     */
    public function notifyJustificationSubmitted(int $justificationId, int $employeeId): int
    {
        $employee = $this->employeeModel->find($employeeId);

        if (!$employee) {
            return 0;
        }

        // Notify managers
        return $this->notifyManagers(
            'Nova justificativa pendente',
            "O funcionário {$employee->name} enviou uma justificativa para aprovação.",
            'justification',
            "/justifications/{$justificationId}"
        );
    }

    /**
     * Specialized notification: Justification approved/rejected
     *
     * @param int $employeeId
     * @param bool $approved
     * @param string|null $reason
     * @return int|false
     */
    public function notifyJustificationStatus(
        int $employeeId,
        bool $approved,
        ?string $reason = null
    ) {
        $status = $approved ? 'aprovada' : 'rejeitada';
        $type = $approved ? 'success' : 'warning';

        $message = "Sua justificativa foi {$status}.";
        if ($reason) {
            $message .= " Motivo: {$reason}";
        }

        return $this->notify(
            $employeeId,
            'Justificativa ' . ucfirst($status),
            $message,
            $type,
            '/justifications'
        );
    }

    /**
     * Specialized notification: Missing punch
     *
     * @param int $employeeId
     * @param string $date
     * @return int|false
     */
    public function notifyMissingPunch(int $employeeId, string $date) {
        $formattedDate = date('d/m/Y', strtotime($date));

        return $this->notify(
            $employeeId,
            'Registro de ponto ausente',
            "Você não registrou ponto no dia {$formattedDate}. Por favor, justifique a ausência.",
            'warning',
            '/justifications/create'
        );
    }

    /**
     * Specialized notification: Late arrival
     *
     * @param int $employeeId
     * @param string $date
     * @param int $minutesLate
     * @return int|false
     */
    public function notifyLateArrival(int $employeeId, string $date, int $minutesLate) {
        $formattedDate = date('d/m/Y', strtotime($date));

        return $this->notify(
            $employeeId,
            'Atraso registrado',
            "Você chegou {$minutesLate} minutos atrasado no dia {$formattedDate}.",
            'warning',
            '/timesheet/my-punches'
        );
    }

    /**
     * Specialized notification: Monthly timesheet ready
     *
     * @param int $employeeId
     * @param string $month
     * @return int|false
     */
    public function notifyTimesheetReady(int $employeeId, string $month) {
        $formattedMonth = date('m/Y', strtotime($month . '-01'));

        return $this->notify(
            $employeeId,
            'Espelho de ponto disponível',
            "O espelho de ponto do mês {$formattedMonth} está disponível para visualização.",
            'info',
            "/reports/timesheet/{$month}"
        );
    }

    /**
     * Specialized notification: Warning issued
     *
     * @param int $employeeId
     * @param string $warningType
     * @return int|false
     */
    public function notifyWarning(int $employeeId, string $warningType) {
        return $this->notify(
            $employeeId,
            'Advertência recebida',
            "Você recebeu uma advertência: {$warningType}. Confira os detalhes.",
            'warning',
            '/warnings'
        );
    }

    /**
     * Get notification statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total_notifications' => $this->notificationModel->countAllResults(false),
            'unread_notifications' => $this->notificationModel
                ->where('read', false)
                ->countAllResults(),
            'notifications_today' => $this->notificationModel
                ->where('DATE(created_at)', date('Y-m-d'))
                ->countAllResults(),
        ];
    }
}
