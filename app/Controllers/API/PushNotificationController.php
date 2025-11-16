<?php

namespace App\Controllers\API;

use CodeIgniter\RESTful\ResourceController;
use App\Services\Notification\PushNotificationService;

/**
 * Push Notification Controller
 *
 * Manages push notification device tokens and sends notifications
 *
 * Endpoints:
 * - POST /api/notifications/register - Register device token
 * - DELETE /api/notifications/unregister - Unregister device token
 * - POST /api/notifications/test - Send test notification
 *
 * @package App\Controllers\API
 */
class PushNotificationController extends ResourceController
{
    protected $format = 'json';

    protected PushNotificationService $notificationService;

    public function __construct()
    {
        $this->notificationService = new PushNotificationService();
    }

    /**
     * Register device token
     *
     * POST /api/notifications/register
     *
     * Request body:
     * {
     *   "device_token": "fcm_token_here",
     *   "platform": "android|ios|web",
     *   "device_name": "Samsung Galaxy S21" (optional)
     * }
     *
     * Response:
     * {
     *   "success": true,
     *   "message": "Device token registered successfully"
     * }
     *
     * @return ResponseInterface
     */
    public function register()
    {
        // Get employee ID from authenticated token
        $employeeId = $this->request->employeeId ?? null;

        if (!$employeeId) {
            return $this->failUnauthorized('Authentication required');
        }

        // Get device token from request
        $deviceToken = $this->request->getJsonVar('device_token') ?? $this->request->getPost('device_token');
        $platform = $this->request->getJsonVar('platform') ?? $this->request->getPost('platform') ?? 'android';
        $deviceName = $this->request->getJsonVar('device_name') ?? $this->request->getPost('device_name') ?? 'Unknown Device';

        // Validate input
        if (!$deviceToken) {
            return $this->fail('Missing device_token', 400);
        }

        if (!in_array($platform, ['android', 'ios', 'web'])) {
            return $this->fail('Invalid platform. Must be: android, ios, or web', 400);
        }

        // Register token
        $success = $this->notificationService->registerDeviceToken(
            $employeeId,
            $deviceToken,
            $platform,
            $deviceName
        );

        if ($success) {
            log_message('info', "Device token registered for employee ID: {$employeeId}, platform: {$platform}");

            return $this->respond([
                'success' => true,
                'message' => 'Device token registered successfully',
            ]);
        } else {
            return $this->fail('Failed to register device token', 500);
        }
    }

    /**
     * Unregister device token
     *
     * DELETE /api/notifications/unregister
     *
     * Request body:
     * {
     *   "device_token": "fcm_token_here"
     * }
     *
     * Response:
     * {
     *   "success": true,
     *   "message": "Device token unregistered successfully"
     * }
     *
     * @return ResponseInterface
     */
    public function unregister()
    {
        // Get employee ID from authenticated token
        $employeeId = $this->request->employeeId ?? null;

        if (!$employeeId) {
            return $this->failUnauthorized('Authentication required');
        }

        // Get device token from request
        $deviceToken = $this->request->getJsonVar('device_token') ?? $this->request->getPost('device_token');

        if (!$deviceToken) {
            return $this->fail('Missing device_token', 400);
        }

        // Unregister token
        $success = $this->notificationService->unregisterDeviceToken($deviceToken);

        if ($success) {
            log_message('info', "Device token unregistered for employee ID: {$employeeId}");

            return $this->respond([
                'success' => true,
                'message' => 'Device token unregistered successfully',
            ]);
        } else {
            return $this->failNotFound('Device token not found');
        }
    }

    /**
     * Send test notification
     *
     * POST /api/notifications/test
     *
     * Request body:
     * {
     *   "title": "Test Notification" (optional),
     *   "body": "This is a test" (optional)
     * }
     *
     * Response:
     * {
     *   "success": true,
     *   "message": "Test notification sent",
     *   "response": {...}
     * }
     *
     * @return ResponseInterface
     */
    public function test()
    {
        // Get employee ID from authenticated token
        $employeeId = $this->request->employeeId ?? null;

        if (!$employeeId) {
            return $this->failUnauthorized('Authentication required');
        }

        // Get custom title and body (optional)
        $title = $this->request->getJsonVar('title') ?? $this->request->getPost('title') ?? 'Notificação de Teste';
        $body = $this->request->getJsonVar('body') ?? $this->request->getPost('body') ?? 'Esta é uma notificação de teste do sistema de ponto eletrônico';

        // Send test notification
        $result = $this->notificationService->sendToEmployee(
            $employeeId,
            $title,
            $body,
            [
                'type' => 'test',
                'timestamp' => date('Y-m-d H:i:s'),
            ],
            [
                'icon' => 'ic_test',
                'sound' => 'default',
            ]
        );

        if ($result['success']) {
            return $this->respond([
                'success' => true,
                'message' => 'Test notification sent successfully',
                'response' => $result,
            ]);
        } else {
            return $this->fail($result['error'] ?? 'Failed to send test notification', 500);
        }
    }

    /**
     * Get available notification templates
     *
     * GET /api/notifications/templates
     *
     * Response:
     * {
     *   "templates": {
     *     "punch_in": {...},
     *     "punch_out": {...},
     *     ...
     *   }
     * }
     *
     * @return ResponseInterface
     */
    public function templates()
    {
        $templates = $this->notificationService->getTemplates();

        return $this->respond([
            'templates' => $templates,
        ]);
    }
}
