<?php

namespace App\Models;

use CodeIgniter\Model;

class ChatMessageModel extends Model
{
    protected $table            = 'chat_messages';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'sender_id',
        'recipient_id',
        'message',
        'attachment_path',
        'attachment_type',
        'attachment_size',
        'sent_at',
        'delivered_at',
        'read_at',
        'deleted_by_sender',
        'deleted_by_recipient',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = false; // Messages are immutable

    // Validation
    protected $validationRules = [
        'sender_id'    => 'required|integer',
        'recipient_id' => 'required|integer',
        'message'      => 'required|max_length[5000]',
    ];

    protected $validationMessages = [
        'message' => [
            'max_length' => 'A mensagem deve ter no máximo 5000 caracteres.',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Get conversation between two users
     */
    public function getConversation(int $user1Id, int $user2Id, ?int $limit = 50): array
    {
        return $this->groupStart()
            ->where('sender_id', $user1Id)
            ->where('recipient_id', $user2Id)
            ->groupEnd()
            ->orGroupStart()
            ->where('sender_id', $user2Id)
            ->where('recipient_id', $user1Id)
            ->groupEnd()
            ->orderBy('sent_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get unread messages for user
     */
    public function getUnread(int $userId): array
    {
        return $this->where('recipient_id', $userId)
            ->where('read_at', null)
            ->where('deleted_by_recipient', false)
            ->orderBy('sent_at', 'DESC')
            ->findAll();
    }

    /**
     * Get unread count for user
     */
    public function getUnreadCount(int $userId): int
    {
        return $this->where('recipient_id', $userId)
            ->where('read_at', null)
            ->where('deleted_by_recipient', false)
            ->countAllResults();
    }

    /**
     * Mark message as read
     */
    public function markAsRead(int $messageId): bool
    {
        return $this->update($messageId, [
            'read_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Mark all messages from a user as read
     */
    public function markAllAsRead(int $senderId, int $recipientId): bool
    {
        return $this->where('sender_id', $senderId)
            ->where('recipient_id', $recipientId)
            ->where('read_at', null)
            ->set(['read_at' => date('Y-m-d H:i:s')])
            ->update();
    }

    /**
     * Mark message as delivered
     */
    public function markAsDelivered(int $messageId): bool
    {
        return $this->update($messageId, [
            'delivered_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Delete message for sender
     */
    public function deleteForSender(int $messageId): bool
    {
        return $this->update($messageId, ['deleted_by_sender' => true]);
    }

    /**
     * Delete message for recipient
     */
    public function deleteForRecipient(int $messageId): bool
    {
        return $this->update($messageId, ['deleted_by_recipient' => true]);
    }

    /**
     * Get recent contacts for user
     */
    public function getRecentContacts(int $userId, ?int $limit = 10): array
    {
        $db = \Config\Database::connect();

        $sql = "
            SELECT DISTINCT
                CASE
                    WHEN sender_id = ? THEN recipient_id
                    ELSE sender_id
                END as contact_id,
                MAX(sent_at) as last_message_at
            FROM chat_messages
            WHERE sender_id = ? OR recipient_id = ?
            GROUP BY contact_id
            ORDER BY last_message_at DESC
            LIMIT ?
        ";

        $query = $db->query($sql, [$userId, $userId, $userId, $limit]);

        return $query->getResult();
    }

    /**
     * Send message
     */
    public function send(
        int $senderId,
        int $recipientId,
        string $message,
        ?string $attachmentPath = null,
        ?string $attachmentType = null,
        ?int $attachmentSize = null
    ): int|false {
        $data = [
            'sender_id'    => $senderId,
            'recipient_id' => $recipientId,
            'message'      => $message,
            'sent_at'      => date('Y-m-d H:i:s'),
        ];

        if ($attachmentPath) {
            $data['attachment_path'] = $attachmentPath;
            $data['attachment_type'] = $attachmentType;
            $data['attachment_size'] = $attachmentSize;
        }

        return $this->insert($data);
    }
}
