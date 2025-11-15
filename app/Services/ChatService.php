<?php

namespace App\Services;

use App\Models\ChatRoomModel;
use App\Models\ChatRoomMemberModel;
use App\Models\ChatMessageModel;
use App\Models\ChatMessageReactionModel;
use App\Models\ChatOnlineUserModel;
use App\Models\EmployeeModel;

/**
 * Chat Service
 *
 * Business logic for chat functionality
 */
class ChatService
{
    protected ChatRoomModel $roomModel;
    protected ChatRoomMemberModel $memberModel;
    protected ChatMessageModel $messageModel;
    protected ChatMessageReactionModel $reactionModel;
    protected ChatOnlineUserModel $onlineUserModel;
    protected EmployeeModel $employeeModel;

    public function __construct()
    {
        $this->roomModel = new ChatRoomModel();
        $this->memberModel = new ChatRoomMemberModel();
        $this->messageModel = new ChatMessageModel();
        $this->reactionModel = new ChatMessageReactionModel();
        $this->onlineUserModel = new ChatOnlineUserModel();
        $this->employeeModel = new EmployeeModel();
    }

    /**
     * Get or create private chat room between two employees
     *
     * @param int $employee1Id
     * @param int $employee2Id
     * @return array
     */
    public function getOrCreatePrivateRoom(int $employee1Id, int $employee2Id): array
    {
        $room = $this->roomModel->getOrCreatePrivateRoom($employee1Id, $employee2Id);

        if (!$room) {
            return [
                'success' => false,
                'message' => 'Erro ao criar sala de chat.',
            ];
        }

        return [
            'success' => true,
            'room'    => $room,
        ];
    }

    /**
     * Create group chat room
     *
     * @param int    $creatorId
     * @param string $name
     * @param array  $memberIds
     * @return array
     */
    public function createGroupRoom(int $creatorId, string $name, array $memberIds): array
    {
        // Create room
        $roomId = $this->roomModel->insert([
            'name'       => $name,
            'type'       => 'group',
            'created_by' => $creatorId,
            'active'     => true,
        ]);

        if (!$roomId) {
            return [
                'success' => false,
                'message' => 'Erro ao criar sala de chat em grupo.',
            ];
        }

        // Add creator as admin
        $this->memberModel->insert([
            'room_id'     => $roomId,
            'employee_id' => $creatorId,
            'role'        => 'admin',
            'joined_at'   => date('Y-m-d H:i:s'),
        ]);

        // Add members
        foreach ($memberIds as $memberId) {
            if ($memberId !== $creatorId) {
                $this->memberModel->insert([
                    'room_id'     => $roomId,
                    'employee_id' => $memberId,
                    'role'        => 'member',
                    'joined_at'   => date('Y-m-d H:i:s'),
                ]);
            }
        }

        $room = $this->roomModel->find($roomId);

        return [
            'success' => true,
            'room'    => $room,
        ];
    }

    /**
     * Get rooms for employee
     *
     * @param int $employeeId
     * @return array
     */
    public function getEmployeeRooms(int $employeeId): array
    {
        $rooms = $this->roomModel->getRoomsForEmployee($employeeId);

        // Add unread count for each room
        foreach ($rooms as &$room) {
            $room->unread_count = $this->roomModel->getUnreadCount($room->id, $employeeId);
        }

        return $rooms;
    }

    /**
     * Get room messages
     *
     * @param int $roomId
     * @param int $employeeId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getRoomMessages(int $roomId, int $employeeId, int $limit = 50, int $offset = 0): array
    {
        // Check if employee is member
        if (!$this->memberModel->isMember($roomId, $employeeId)) {
            return [
                'success' => false,
                'message' => 'Você não é membro desta sala.',
            ];
        }

        $messages = $this->messageModel->getRoomMessages($roomId, $limit, $offset);

        // Add reactions to each message
        foreach ($messages as &$message) {
            $message->reactions = $this->reactionModel->getReactionSummary($message->id);
        }

        return [
            'success'  => true,
            'messages' => array_reverse($messages), // Oldest first
        ];
    }

    /**
     * Send message
     *
     * @param int    $roomId
     * @param int    $senderId
     * @param string $message
     * @param int    $replyTo
     * @return array
     */
    public function sendMessage(int $roomId, int $senderId, string $message, ?int $replyTo = null): array
    {
        // Check if sender is member
        if (!$this->memberModel->isMember($roomId, $senderId)) {
            return [
                'success' => false,
                'message' => 'Você não é membro desta sala.',
            ];
        }

        // Create message
        $messageId = $this->messageModel->insert([
            'room_id'   => $roomId,
            'sender_id' => $senderId,
            'message'   => $message,
            'type'      => 'text',
            'reply_to'  => $replyTo,
        ]);

        if (!$messageId) {
            return [
                'success' => false,
                'message' => 'Erro ao enviar mensagem.',
            ];
        }

        $messageData = $this->messageModel->find($messageId);

        return [
            'success' => true,
            'message' => $messageData,
        ];
    }

    /**
     * Mark room as read
     *
     * @param int $roomId
     * @param int $employeeId
     * @return bool
     */
    public function markAsRead(int $roomId, int $employeeId): bool
    {
        return $this->memberModel->markAsRead($roomId, $employeeId);
    }

    /**
     * Add reaction to message
     *
     * @param int    $messageId
     * @param int    $employeeId
     * @param string $emoji
     * @return bool
     */
    public function addReaction(int $messageId, int $employeeId, string $emoji): bool
    {
        return $this->reactionModel->toggleReaction($messageId, $employeeId, $emoji);
    }

    /**
     * Edit message
     *
     * @param int    $messageId
     * @param int    $employeeId
     * @param string $newMessage
     * @return array
     */
    public function editMessage(int $messageId, int $employeeId, string $newMessage): array
    {
        $message = $this->messageModel->find($messageId);

        if (!$message) {
            return [
                'success' => false,
                'message' => 'Mensagem não encontrada.',
            ];
        }

        // Only sender can edit
        if ($message->sender_id !== $employeeId) {
            return [
                'success' => false,
                'message' => 'Você não pode editar esta mensagem.',
            ];
        }

        // Can only edit within 15 minutes
        $createdTime = strtotime($message->created_at);
        $currentTime = time();

        if (($currentTime - $createdTime) > 900) {
            // 15 minutes
            return [
                'success' => false,
                'message' => 'Você só pode editar mensagens enviadas nos últimos 15 minutos.',
            ];
        }

        $this->messageModel->editMessage($messageId, $newMessage);

        return [
            'success' => true,
            'message' => 'Mensagem editada com sucesso.',
        ];
    }

    /**
     * Delete message
     *
     * @param int $messageId
     * @param int $employeeId
     * @return array
     */
    public function deleteMessage(int $messageId, int $employeeId): array
    {
        $message = $this->messageModel->find($messageId);

        if (!$message) {
            return [
                'success' => false,
                'message' => 'Mensagem não encontrada.',
            ];
        }

        // Check permissions
        $employee = $this->employeeModel->find($employeeId);

        if ($message->sender_id !== $employeeId && $employee->role !== 'admin') {
            return [
                'success' => false,
                'message' => 'Você não pode excluir esta mensagem.',
            ];
        }

        $this->messageModel->delete($messageId);

        return [
            'success' => true,
            'message' => 'Mensagem excluída com sucesso.',
        ];
    }

    /**
     * Search messages
     *
     * @param int    $roomId
     * @param int    $employeeId
     * @param string $query
     * @return array
     */
    public function searchMessages(int $roomId, int $employeeId, string $query): array
    {
        // Check if employee is member
        if (!$this->memberModel->isMember($roomId, $employeeId)) {
            return [
                'success' => false,
                'message' => 'Você não é membro desta sala.',
            ];
        }

        $messages = $this->messageModel->searchMessages($roomId, $query);

        return [
            'success'  => true,
            'messages' => $messages,
        ];
    }

    /**
     * Get online users
     *
     * @return array
     */
    public function getOnlineUsers(): array
    {
        return $this->onlineUserModel->getOnlineUsers();
    }

    /**
     * Get room members
     *
     * @param int $roomId
     * @param int $employeeId
     * @return array
     */
    public function getRoomMembers(int $roomId, int $employeeId): array
    {
        // Check if employee is member
        if (!$this->memberModel->isMember($roomId, $employeeId)) {
            return [
                'success' => false,
                'message' => 'Você não é membro desta sala.',
            ];
        }

        $members = $this->memberModel->getRoomMembers($roomId);

        // Add online status
        foreach ($members as &$member) {
            $member->is_online = $this->onlineUserModel->isOnline($member->employee_id);
        }

        return [
            'success' => true,
            'members' => $members,
        ];
    }

    /**
     * Add member to room
     *
     * @param int $roomId
     * @param int $employeeId
     * @param int $newMemberId
     * @return array
     */
    public function addMember(int $roomId, int $employeeId, int $newMemberId): array
    {
        $room = $this->roomModel->find($roomId);

        if (!$room) {
            return [
                'success' => false,
                'message' => 'Sala não encontrada.',
            ];
        }

        // Only admins or room creator can add members
        $member = $this->memberModel->where('room_id', $roomId)
            ->where('employee_id', $employeeId)
            ->first();

        if (!$member || ($member->role !== 'admin' && $room->created_by !== $employeeId)) {
            return [
                'success' => false,
                'message' => 'Você não tem permissão para adicionar membros.',
            ];
        }

        // Check if already member
        if ($this->memberModel->isMember($roomId, $newMemberId)) {
            return [
                'success' => false,
                'message' => 'Usuário já é membro desta sala.',
            ];
        }

        // Add member
        $this->memberModel->insert([
            'room_id'     => $roomId,
            'employee_id' => $newMemberId,
            'role'        => 'member',
            'joined_at'   => date('Y-m-d H:i:s'),
        ]);

        return [
            'success' => true,
            'message' => 'Membro adicionado com sucesso.',
        ];
    }

    /**
     * Remove member from room
     *
     * @param int $roomId
     * @param int $employeeId
     * @param int $memberToRemove
     * @return array
     */
    public function removeMember(int $roomId, int $employeeId, int $memberToRemove): array
    {
        $room = $this->roomModel->find($roomId);

        if (!$room) {
            return [
                'success' => false,
                'message' => 'Sala não encontrada.',
            ];
        }

        // Only admins or room creator can remove members
        $member = $this->memberModel->where('room_id', $roomId)
            ->where('employee_id', $employeeId)
            ->first();

        if (!$member || ($member->role !== 'admin' && $room->created_by !== $employeeId)) {
            return [
                'success' => false,
                'message' => 'Você não tem permissão para remover membros.',
            ];
        }

        // Remove member
        $this->memberModel->where('room_id', $roomId)
            ->where('employee_id', $memberToRemove)
            ->delete();

        return [
            'success' => true,
            'message' => 'Membro removido com sucesso.',
        ];
    }
}
