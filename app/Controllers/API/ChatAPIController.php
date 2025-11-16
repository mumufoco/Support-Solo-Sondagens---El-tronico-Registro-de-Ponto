<?php

namespace App\Controllers\API;

use CodeIgniter\RESTful\ResourceController;
use App\Services\ChatService;
use App\Services\Auth\AuthService;
use App\Models\EmployeeModel;

/**
 * Chat API Controller
 *
 * RESTful API for chat operations
 */
class ChatAPIController extends ResourceController
{
    protected $modelName = 'App\Models\ChatRoomModel';
    protected $format    = 'json';

    protected ChatService $chatService;
    protected AuthService $authService;
    protected EmployeeModel $employeeModel;

    public function __construct()
    {
        $this->chatService = new ChatService();
        $this->authService = new AuthService();
        $this->employeeModel = new EmployeeModel();
    }

    /**
     * Get authenticated employee from Bearer token
     */
    protected function getAuthenticatedEmployee(): ?array
    {
        // Try session first
        $employeeId = session()->get('employee_id');

        // Try Bearer token
        if (!$employeeId) {
            $authHeader = $this->request->getHeader('Authorization');

            if ($authHeader) {
                $token = str_replace('Bearer ', '', $authHeader->getValue());

                // Validate token (implement your token validation logic)
                $employeeId = $this->validateToken($token);
            }
        }

        if (!$employeeId) {
            return null;
        }

        $employee = $this->employeeModel->find($employeeId);

        return $employee ? (array) $employee : null;
    }

    /**
     * Validate Bearer token (JWT)
     *
     * Decodes and validates JWT token using AuthService.
     * Returns employee_id if valid, null otherwise.
     *
     * @param string $token JWT token
     * @return int|null Employee ID if valid
     */
    protected function validateToken(string $token): ?int
    {
        try {
            // Validate JWT token
            $payload = $this->authService->validateJWT($token);

            if (!$payload) {
                log_message('warning', 'Invalid JWT token provided to Chat API');
                return null;
            }

            // Extract employee_id from payload
            $employeeId = $payload['sub'] ?? null;

            if (!$employeeId) {
                log_message('error', 'JWT token missing subject (employee_id)');
                return null;
            }

            // Verify employee exists and is active
            $employee = $this->employeeModel->find($employeeId);

            if (!$employee || !$employee->active) {
                log_message('warning', 'JWT token for inactive or non-existent employee: ' . $employeeId);
                return null;
            }

            return (int)$employeeId;
        } catch (\Exception $e) {
            log_message('error', 'JWT validation error in Chat API: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all rooms for authenticated user
     *
     * GET /api/chat/rooms
     */
    public function getRooms()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->failUnauthorized('Não autenticado.');
        }

        $rooms = $this->chatService->getEmployeeRooms($employee['id']);

        return $this->respond([
            'success' => true,
            'rooms'   => $rooms,
        ]);
    }

    /**
     * Get messages for a room
     *
     * GET /api/chat/rooms/{roomId}/messages
     */
    public function getMessages($roomId = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->failUnauthorized('Não autenticado.');
        }

        $limit = $this->request->getGet('limit') ?? 50;
        $offset = $this->request->getGet('offset') ?? 0;
        $beforeId = $this->request->getGet('before_id');

        $result = $this->chatService->getRoomMessages($roomId, $employee['id'], $limit, $offset);

        if (!$result['success']) {
            return $this->failForbidden($result['message']);
        }

        return $this->respond($result);
    }

    /**
     * Send message to room
     *
     * POST /api/chat/rooms/{roomId}/messages
     */
    public function sendMessage($roomId = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->failUnauthorized('Não autenticado.');
        }

        $rules = [
            'message' => 'required|max_length[5000]',
            'reply_to' => 'permit_empty|integer',
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors(json_encode($this->validator->getErrors()));
        }

        $message = $this->request->getPost('message');
        $replyTo = $this->request->getPost('reply_to');

        $result = $this->chatService->sendMessage($roomId, $employee['id'], $message, $replyTo);

        if (!$result['success']) {
            return $this->fail($result['message']);
        }

        return $this->respondCreated($result);
    }

    /**
     * Edit message
     *
     * PUT /api/chat/messages/{messageId}
     */
    public function editMessage($messageId = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->failUnauthorized('Não autenticado.');
        }

        $rules = [
            'message' => 'required|max_length[5000]',
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors(json_encode($this->validator->getErrors()));
        }

        $message = $this->request->getPost('message') ?? $this->request->getRawInput()['message'] ?? null;

        if (!$message) {
            return $this->fail('Mensagem é obrigatória.');
        }

        $result = $this->chatService->editMessage($messageId, $employee['id'], $message);

        if (!$result['success']) {
            return $this->fail($result['message']);
        }

        return $this->respond($result);
    }

    /**
     * Delete message
     *
     * DELETE /api/chat/messages/{messageId}
     */
    public function deleteMessage($messageId = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->failUnauthorized('Não autenticado.');
        }

        $result = $this->chatService->deleteMessage($messageId, $employee['id']);

        if (!$result['success']) {
            return $this->fail($result['message']);
        }

        return $this->respondDeleted($result);
    }

    /**
     * Add reaction to message
     *
     * POST /api/chat/messages/{messageId}/reactions
     */
    public function addReaction($messageId = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->failUnauthorized('Não autenticado.');
        }

        $emoji = $this->request->getPost('emoji');

        if (!$emoji) {
            return $this->fail('Emoji é obrigatório.');
        }

        $result = $this->chatService->addReaction($messageId, $employee['id'], $emoji);

        return $this->respond([
            'success' => $result,
            'message' => $result ? 'Reação adicionada/removida com sucesso.' : 'Erro ao adicionar reação.',
        ]);
    }

    /**
     * Mark room as read
     *
     * POST /api/chat/rooms/{roomId}/read
     */
    public function markAsRead($roomId = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->failUnauthorized('Não autenticado.');
        }

        $result = $this->chatService->markAsRead($roomId, $employee['id']);

        return $this->respond([
            'success' => $result,
            'message' => $result ? 'Marcado como lido.' : 'Erro ao marcar como lido.',
        ]);
    }

    /**
     * Get room members
     *
     * GET /api/chat/rooms/{roomId}/members
     */
    public function getMembers($roomId = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->failUnauthorized('Não autenticado.');
        }

        $result = $this->chatService->getRoomMembers($roomId, $employee['id']);

        if (!$result['success']) {
            return $this->failForbidden($result['message']);
        }

        return $this->respond($result);
    }

    /**
     * Add member to room
     *
     * POST /api/chat/rooms/{roomId}/members
     */
    public function addMember($roomId = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->failUnauthorized('Não autenticado.');
        }

        $newMemberId = $this->request->getPost('employee_id');

        if (!$newMemberId) {
            return $this->fail('ID do funcionário é obrigatório.');
        }

        $result = $this->chatService->addMember($roomId, $employee['id'], $newMemberId);

        if (!$result['success']) {
            return $this->fail($result['message']);
        }

        return $this->respondCreated($result);
    }

    /**
     * Remove member from room
     *
     * DELETE /api/chat/rooms/{roomId}/members/{memberId}
     */
    public function removeMember($roomId = null, $memberId = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->failUnauthorized('Não autenticado.');
        }

        $result = $this->chatService->removeMember($roomId, $employee['id'], $memberId);

        if (!$result['success']) {
            return $this->fail($result['message']);
        }

        return $this->respondDeleted($result);
    }

    /**
     * Search messages in room
     *
     * GET /api/chat/rooms/{roomId}/search
     */
    public function searchMessages($roomId = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->failUnauthorized('Não autenticado.');
        }

        $query = $this->request->getGet('q');

        if (!$query) {
            return $this->fail('Query de busca é obrigatória.');
        }

        $result = $this->chatService->searchMessages($roomId, $employee['id'], $query);

        if (!$result['success']) {
            return $this->fail($result['message']);
        }

        return $this->respond($result);
    }

    /**
     * Get online users
     *
     * GET /api/chat/online
     */
    public function getOnlineUsers()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->failUnauthorized('Não autenticado.');
        }

        $users = $this->chatService->getOnlineUsers();

        return $this->respond([
            'success' => true,
            'users'   => $users,
        ]);
    }

    /**
     * Create private room
     *
     * POST /api/chat/rooms/private
     */
    public function createPrivateRoom()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->failUnauthorized('Não autenticado.');
        }

        $otherEmployeeId = $this->request->getPost('employee_id');

        if (!$otherEmployeeId) {
            return $this->fail('ID do funcionário é obrigatório.');
        }

        $result = $this->chatService->getOrCreatePrivateRoom($employee['id'], $otherEmployeeId);

        if (!$result['success']) {
            return $this->fail($result['message']);
        }

        return $this->respondCreated($result);
    }

    /**
     * Create group room
     *
     * POST /api/chat/rooms/group
     */
    public function createGroupRoom()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->failUnauthorized('Não autenticado.');
        }

        $rules = [
            'name'    => 'required|min_length[3]|max_length[255]',
            'members' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->failValidationErrors(json_encode($this->validator->getErrors()));
        }

        $name = $this->request->getPost('name');
        $members = $this->request->getPost('members');

        if (!is_array($members)) {
            $members = explode(',', $members);
        }

        $result = $this->chatService->createGroupRoom($employee['id'], $name, $members);

        if (!$result['success']) {
            return $this->fail($result['message']);
        }

        return $this->respondCreated($result);
    }
}
