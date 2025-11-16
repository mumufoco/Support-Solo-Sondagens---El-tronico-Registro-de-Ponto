<?php

namespace App\Services\Notification;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Config\Services;

/**
 * Push Notification Service
 *
 * Sends push notifications via Firebase Cloud Messaging (FCM)
 *
 * Features:
 * - Send to individual devices
 * - Send to multiple devices (multicast)
 * - Topic-based notifications
 * - Rich notifications with data payload
 * - Badge count management
 * - Notification templates
 * - Device token management
 * - Automatic cleanup of invalid tokens
 *
 * @package App\Services\Notification
 */
class PushNotificationService
{
    /**
     * Database connection
     * @var ConnectionInterface
     */
    protected ConnectionInterface $db;

    /**
     * FCM Server Key
     * @var string
     */
    protected string $fcmServerKey;

    /**
     * FCM API endpoint
     * @var string
     */
    protected string $fcmEndpoint = 'https://fcm.googleapis.com/fcm/send';

    /**
     * Notification templates
     * @var array
     */
    protected array $templates = [
        'punch_in' => [
            'title' => 'Ponto Registrado',
            'body' => 'Entrada registrada às {time}',
            'icon' => 'ic_punch_in',
            'sound' => 'default',
            'badge' => 1,
        ],
        'punch_out' => [
            'title' => 'Ponto Registrado',
            'body' => 'Saída registrada às {time}',
            'icon' => 'ic_punch_out',
            'sound' => 'default',
            'badge' => 1,
        ],
        'timesheet_approved' => [
            'title' => 'Ponto Aprovado',
            'body' => 'Seu ponto de {date} foi aprovado',
            'icon' => 'ic_approved',
            'sound' => 'success',
            'badge' => 1,
        ],
        'timesheet_rejected' => [
            'title' => 'Ponto Rejeitado',
            'body' => 'Seu ponto de {date} foi rejeitado. Motivo: {reason}',
            'icon' => 'ic_rejected',
            'sound' => 'alert',
            'badge' => 1,
        ],
        'warning_issued' => [
            'title' => 'Advertência',
            'body' => 'Você recebeu uma advertência: {reason}',
            'icon' => 'ic_warning',
            'sound' => 'alert',
            'badge' => 1,
        ],
        'schedule_updated' => [
            'title' => 'Escala Atualizada',
            'body' => 'Sua escala de trabalho foi atualizada',
            'icon' => 'ic_schedule',
            'sound' => 'default',
            'badge' => 1,
        ],
        'announcement' => [
            'title' => 'Comunicado',
            'body' => '{message}',
            'icon' => 'ic_announcement',
            'sound' => 'default',
            'badge' => 1,
        ],
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = Services::database()->connect();

        // Load FCM server key from environment
        $this->fcmServerKey = getenv('FCM_SERVER_KEY') ?: '';

        if (empty($this->fcmServerKey)) {
            log_message('warning', 'FCM_SERVER_KEY not configured. Push notifications will not work.');
        }
    }

    /**
     * Send notification to a single device
     *
     * @param string $deviceToken FCM device token
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data payload
     * @param array $options Notification options (icon, sound, badge, etc.)
     * @return array Response with success status
     */
    public function sendToDevice(
        string $deviceToken,
        string $title,
        string $body,
        array $data = [],
        array $options = []
    ): array {
        return $this->sendToDevices([$deviceToken], $title, $body, $data, $options);
    }

    /**
     * Send notification to multiple devices (multicast)
     *
     * @param array $deviceTokens Array of FCM device tokens
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data payload
     * @param array $options Notification options
     * @return array Response with success status and results
     */
    public function sendToDevices(
        array $deviceTokens,
        string $title,
        string $body,
        array $data = [],
        array $options = []
    ): array {
        if (empty($this->fcmServerKey)) {
            return [
                'success' => false,
                'error' => 'FCM not configured',
            ];
        }

        if (empty($deviceTokens)) {
            return [
                'success' => false,
                'error' => 'No device tokens provided',
            ];
        }

        // Build notification payload
        $notification = [
            'title' => $title,
            'body' => $body,
            'icon' => $options['icon'] ?? 'ic_notification',
            'sound' => $options['sound'] ?? 'default',
            'badge' => $options['badge'] ?? 1,
            'click_action' => $options['click_action'] ?? 'FLUTTER_NOTIFICATION_CLICK',
        ];

        // Build FCM message
        $message = [
            'registration_ids' => $deviceTokens,
            'notification' => $notification,
            'data' => array_merge($data, [
                'title' => $title,
                'body' => $body,
                'click_action' => $notification['click_action'],
            ]),
            'priority' => $options['priority'] ?? 'high',
            'content_available' => true,
        ];

        // Send to FCM
        $response = $this->sendToFCM($message);

        // Process response and clean up invalid tokens
        if ($response['success'] && isset($response['results'])) {
            $this->processResults($deviceTokens, $response['results']);
        }

        return $response;
    }

    /**
     * Send notification using template
     *
     * @param string $templateName Template name
     * @param array $deviceTokens Device tokens
     * @param array $variables Template variables
     * @param array $data Additional data
     * @return array
     */
    public function sendUsingTemplate(
        string $templateName,
        array $deviceTokens,
        array $variables = [],
        array $data = []
    ): array {
        if (!isset($this->templates[$templateName])) {
            return [
                'success' => false,
                'error' => "Template '{$templateName}' not found",
            ];
        }

        $template = $this->templates[$templateName];

        // Replace variables in title and body
        $title = $this->replaceVariables($template['title'], $variables);
        $body = $this->replaceVariables($template['body'], $variables);

        // Merge template options
        $options = [
            'icon' => $template['icon'],
            'sound' => $template['sound'],
            'badge' => $template['badge'],
        ];

        // Add template name to data
        $data['template'] = $templateName;

        return $this->sendToDevices($deviceTokens, $title, $body, $data, $options);
    }

    /**
     * Send notification to employee
     *
     * @param int $employeeId Employee ID
     * @param string $title Title
     * @param string $body Body
     * @param array $data Data payload
     * @param array $options Options
     * @return array
     */
    public function sendToEmployee(
        int $employeeId,
        string $title,
        string $body,
        array $data = [],
        array $options = []
    ): array {
        $tokens = $this->getEmployeeDeviceTokens($employeeId);

        if (empty($tokens)) {
            return [
                'success' => false,
                'error' => 'No device tokens found for employee',
            ];
        }

        return $this->sendToDevices($tokens, $title, $body, $data, $options);
    }

    /**
     * Send notification to employee using template
     *
     * @param int $employeeId Employee ID
     * @param string $templateName Template name
     * @param array $variables Variables
     * @param array $data Data
     * @return array
     */
    public function sendToEmployeeUsingTemplate(
        int $employeeId,
        string $templateName,
        array $variables = [],
        array $data = []
    ): array {
        $tokens = $this->getEmployeeDeviceTokens($employeeId);

        if (empty($tokens)) {
            return [
                'success' => false,
                'error' => 'No device tokens found for employee',
            ];
        }

        return $this->sendUsingTemplate($templateName, $tokens, $variables, $data);
    }

    /**
     * Send notification to topic
     *
     * @param string $topic Topic name
     * @param string $title Title
     * @param string $body Body
     * @param array $data Data
     * @param array $options Options
     * @return array
     */
    public function sendToTopic(
        string $topic,
        string $title,
        string $body,
        array $data = [],
        array $options = []
    ): array {
        if (empty($this->fcmServerKey)) {
            return [
                'success' => false,
                'error' => 'FCM not configured',
            ];
        }

        $notification = [
            'title' => $title,
            'body' => $body,
            'icon' => $options['icon'] ?? 'ic_notification',
            'sound' => $options['sound'] ?? 'default',
        ];

        $message = [
            'to' => '/topics/' . $topic,
            'notification' => $notification,
            'data' => array_merge($data, [
                'title' => $title,
                'body' => $body,
            ]),
            'priority' => $options['priority'] ?? 'high',
        ];

        return $this->sendToFCM($message);
    }

    /**
     * Register device token
     *
     * @param int $employeeId Employee ID
     * @param string $deviceToken FCM device token
     * @param string $platform Platform (android, ios, web)
     * @param string $deviceName Device name
     * @return bool
     */
    public function registerDeviceToken(
        int $employeeId,
        string $deviceToken,
        string $platform = 'android',
        string $deviceName = 'Unknown Device'
    ): bool {
        // Check if token already exists
        $existing = $this->db->table('push_notification_tokens')
            ->where('device_token', $deviceToken)
            ->get()
            ->getRow();

        if ($existing) {
            // Update existing token
            return $this->db->table('push_notification_tokens')
                ->where('id', $existing->id)
                ->update([
                    'employee_id' => $employeeId,
                    'platform' => $platform,
                    'device_name' => $deviceName,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]) > 0;
        } else {
            // Insert new token
            return $this->db->table('push_notification_tokens')
                ->insert([
                    'employee_id' => $employeeId,
                    'device_token' => $deviceToken,
                    'platform' => $platform,
                    'device_name' => $deviceName,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
        }
    }

    /**
     * Unregister device token
     *
     * @param string $deviceToken Device token
     * @return bool
     */
    public function unregisterDeviceToken(string $deviceToken): bool
    {
        return $this->db->table('push_notification_tokens')
            ->where('device_token', $deviceToken)
            ->delete() > 0;
    }

    /**
     * Get employee device tokens
     *
     * @param int $employeeId Employee ID
     * @return array
     */
    protected function getEmployeeDeviceTokens(int $employeeId): array
    {
        $tokens = $this->db->table('push_notification_tokens')
            ->select('device_token')
            ->where('employee_id', $employeeId)
            ->where('is_valid', true)
            ->get()
            ->getResult();

        return array_column($tokens, 'device_token');
    }

    /**
     * Send message to FCM
     *
     * @param array $message FCM message
     * @return array
     */
    protected function sendToFCM(array $message): array
    {
        $headers = [
            'Authorization: key=' . $this->fcmServerKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->fcmEndpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            log_message('error', "FCM request failed: {$error}");
            return [
                'success' => false,
                'error' => $error,
            ];
        }

        $response = json_decode($result, true);

        if ($httpCode !== 200) {
            log_message('error', "FCM returned HTTP {$httpCode}: {$result}");
            return [
                'success' => false,
                'error' => "HTTP {$httpCode}",
                'response' => $response,
            ];
        }

        log_message('info', "FCM notification sent successfully");

        return array_merge([
            'success' => true,
            'http_code' => $httpCode,
        ], $response);
    }

    /**
     * Process FCM results and mark invalid tokens
     *
     * @param array $deviceTokens Device tokens
     * @param array $results FCM results
     * @return void
     */
    protected function processResults(array $deviceTokens, array $results): void
    {
        foreach ($results as $index => $result) {
            $token = $deviceTokens[$index] ?? null;

            if (!$token) {
                continue;
            }

            // Mark token as invalid if FCM returned error
            if (isset($result['error'])) {
                $error = $result['error'];

                if (in_array($error, ['InvalidRegistration', 'NotRegistered', 'MismatchSenderId'])) {
                    $this->markTokenAsInvalid($token);
                    log_message('info', "Marked FCM token as invalid: {$error}");
                }
            }
        }
    }

    /**
     * Mark token as invalid
     *
     * @param string $deviceToken Device token
     * @return void
     */
    protected function markTokenAsInvalid(string $deviceToken): void
    {
        $this->db->table('push_notification_tokens')
            ->where('device_token', $deviceToken)
            ->update(['is_valid' => false]);
    }

    /**
     * Replace variables in text
     *
     * @param string $text Text with placeholders
     * @param array $variables Variables
     * @return string
     */
    protected function replaceVariables(string $text, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }

        return $text;
    }

    /**
     * Get all templates
     *
     * @return array
     */
    public function getTemplates(): array
    {
        return $this->templates;
    }

    /**
     * Add custom template
     *
     * @param string $name Template name
     * @param array $template Template data
     * @return void
     */
    public function addTemplate(string $name, array $template): void
    {
        $this->templates[$name] = $template;
    }

    /**
     * Clean up invalid tokens
     *
     * @return int Number of tokens deleted
     */
    public function cleanupInvalidTokens(): int
    {
        $count = $this->db->table('push_notification_tokens')
            ->where('is_valid', false)
            ->delete();

        if ($count > 0) {
            log_message('info', "Cleaned up {$count} invalid push notification tokens");
        }

        return $count;
    }
}
