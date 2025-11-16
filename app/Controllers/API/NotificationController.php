<?php

namespace App\Controllers\API;

use CodeIgniter\RESTful\ResourceController;
use App\Models\NotificationModel;
use App\Services\NotificationService;

/**
 * API Notification Controller
 *
 * Handles notifications via API
 */
class NotificationController extends ResourceController
{
    protected $modelName = 'App\Models\NotificationModel';
    protected $format = 'json';

    protected $notificationModel;
    protected $notificationService;

    public function __construct()
    {
        $this->notificationModel = new NotificationModel();
        $this->notificationService = new NotificationService();
        helper(['datetime']);
    }

    /**
     * Get all notifications
     * GET /api/notifications?page=1&per_page=20
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function index()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        $page = (int) ($this->request->getGet('page') ?: 1);
        $perPage = (int) ($this->request->getGet('per_page') ?: 20);
        $perPage = min($perPage, 100); // Max 100 per page

        $notifications = $this->notificationModel
            ->where('employee_id', $employee->id)
            ->orderBy('created_at', 'DESC')
            ->paginate($perPage, 'default', $page);

        $pager = $this->notificationModel->pager;

        return $this->respond([
            'success' => true,
            'data' => array_map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'link' => $notification->link,
                    'read' => $notification->read,
                    'read_at' => $notification->read_at ? format_datetime_br($notification->read_at) : null,
                    'created_at' => format_datetime_br($notification->created_at),
                    'time_ago' => time_ago_br($notification->created_at),
                ];
            }, $notifications),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $pager->getTotal(),
                'last_page' => $pager->getPageCount(),
            ],
        ], 200);
    }

    /**
     * Get unread notifications
     * GET /api/notifications/unread?limit=10
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function unread()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        $limit = (int) ($this->request->getGet('limit') ?: 10);
        $limit = min($limit, 50); // Max 50

        $notifications = $this->notificationService->getUnread($employee->id, $limit);

        return $this->respond([
            'success' => true,
            'data' => array_map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'type' => $notification->type,
                    'link' => $notification->link,
                    'created_at' => format_datetime_br($notification->created_at),
                    'time_ago' => time_ago_br($notification->created_at),
                ];
            }, $notifications),
            'count' => count($notifications),
        ], 200);
    }

    /**
     * Count unread notifications
     * GET /api/notifications/unread/count
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function unreadCount()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        $count = $this->notificationService->countUnread($employee->id);

        return $this->respond([
            'success' => true,
            'data' => [
                'unread_count' => $count,
            ],
        ], 200);
    }

    /**
     * Mark notification as read
     * PUT /api/notifications/{id}/read
     *
     * @param int $id
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function markAsRead($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        $success = $this->notificationService->markAsRead($id, $employee->id);

        if (!$success) {
            return $this->fail('Notificação não encontrada.', 404);
        }

        return $this->respond([
            'success' => true,
            'message' => 'Notificação marcada como lida.',
        ], 200);
    }

    /**
     * Mark all notifications as read
     * PUT /api/notifications/read-all
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function markAllAsRead()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        $count = $this->notificationService->markAllAsRead($employee->id);

        return $this->respond([
            'success' => true,
            'message' => "{$count} notificação(ões) marcada(s) como lida(s).",
            'count' => $count,
        ], 200);
    }

    /**
     * Delete notification
     * DELETE /api/notifications/{id}
     *
     * @param int $id
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function delete($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        $success = $this->notificationService->delete($id, $employee->id);

        if (!$success) {
            return $this->fail('Notificação não encontrada.', 404);
        }

        return $this->respondDeleted([
            'success' => true,
            'message' => 'Notificação excluída.',
        ]);
    }

    /**
     * Delete all read notifications
     * DELETE /api/notifications/read-all
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function deleteAllRead()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        $count = $this->notificationService->deleteAllRead($employee->id);

        return $this->respond([
            'success' => true,
            'message' => "{$count} notificação(ões) excluída(s).",
            'count' => $count,
        ], 200);
    }

    /**
     * Get notification by ID
     * GET /api/notifications/{id}
     *
     * @param int $id
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function show($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->fail('Não autenticado.', 401);
        }

        $notification = $this->notificationModel->find($id);

        if (!$notification || $notification->employee_id !== $employee->id) {
            return $this->fail('Notificação não encontrada.', 404);
        }

        // Mark as read automatically when viewing
        if (!$notification->read) {
            $this->notificationService->markAsRead($id, $employee->id);
            $notification->read = true;
            $notification->read_at = date('Y-m-d H:i:s');
        }

        return $this->respond([
            'success' => true,
            'data' => [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'type' => $notification->type,
                'link' => $notification->link,
                'read' => $notification->read,
                'read_at' => $notification->read_at ? format_datetime_br($notification->read_at) : null,
                'created_at' => format_datetime_br($notification->created_at),
                'time_ago' => time_ago_br($notification->created_at),
            ],
        ], 200);
    }

    /**
     * Get authenticated employee from AuthController
     *
     * @return object|null
     */
    protected function getAuthenticatedEmployee(): ?object
    {
        $authController = new \App\Controllers\API\AuthController();
        return $authController->getAuthenticatedEmployee();
    }
}
