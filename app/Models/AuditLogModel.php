<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table            = 'audit_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'description',
        'level',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = false; // Audit logs are immutable

    // Callbacks
    protected $allowCallbacks = false; // DISABLED: Causing type errors in PHP 8.4+

    /**
     * Encode values arrays to JSON
     */
    protected function encodeValues(array $data): array
    {
        if (isset($data['data']['old_values']) && is_array($data['data']['old_values'])) {
            $data['data']['old_values'] = json_encode($data['data']['old_values']);
        }

        if (isset($data['data']['new_values']) && is_array($data['data']['new_values'])) {
            $data['data']['new_values'] = json_encode($data['data']['new_values']);
        }

        return $data;
    }

    /**
     * Decode values JSON to arrays
     */
    protected function decodeValues(array $data): array
    {
        if (isset($data['data'])) {
            if (is_array($data['data'])) {
                foreach ($data['data'] as &$row) {
                    if (isset($row->old_values) && is_string($row->old_values)) {
                        $row->old_values = json_decode($row->old_values, true);
                    }
                    if (isset($row->new_values) && is_string($row->new_values)) {
                        $row->new_values = json_decode($row->new_values, true);
                    }
                }
            } elseif (is_object($data['data'])) {
                if (isset($data['data']->old_values) && is_string($data['data']->old_values)) {
                    $data['data']->old_values = json_decode($data['data']->old_values, true);
                }
                if (isset($data['data']->new_values) && is_string($data['data']->new_values)) {
                    $data['data']->new_values = json_decode($data['data']->new_values, true);
                }
            }
        }

        return $data;
    }

    /**
     * Log an action
     */
    public function log(
        ?int $userId,
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        string $level = 'info'
    ): bool {
        $data = [
            'user_id'     => $userId,
            'action'      => strtoupper($action),
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'old_values'  => $oldValues ? json_encode($oldValues) : null,
            'new_values'  => $newValues ? json_encode($newValues) : null,
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'url'         => $_SERVER['REQUEST_URI'] ?? null,
            'method'      => $_SERVER['REQUEST_METHOD'] ?? null,
            'description' => $description,
            'level'       => $level,
        ];

        return $this->insert($data) !== false;
    }

    /**
     * Get logs by user
     */
    public function getByUser(int $userId, ?int $limit = 50): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get logs by entity
     */
    public function getByEntity(string $entityType, int $entityId): array
    {
        return $this->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Get logs by action
     */
    public function getByAction(string $action): array
    {
        return $this->where('action', strtoupper($action))
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Get critical logs
     */
    public function getCritical(?int $limit = 100): array
    {
        return $this->whereIn('level', ['error', 'critical'])
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Search logs
     */
    public function search(array $filters): array
    {
        $builder = $this;

        if (isset($filters['user_id'])) {
            $builder = $builder->where('user_id', $filters['user_id']);
        }

        if (isset($filters['action'])) {
            $builder = $builder->where('action', strtoupper($filters['action']));
        }

        if (isset($filters['entity_type'])) {
            $builder = $builder->where('entity_type', $filters['entity_type']);
        }

        if (isset($filters['level'])) {
            $builder = $builder->where('level', $filters['level']);
        }

        if (isset($filters['start_date'])) {
            $builder = $builder->where('created_at >=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $builder = $builder->where('created_at <=', $filters['end_date']);
        }

        return $builder->orderBy('created_at', 'DESC')
            ->findAll($filters['limit'] ?? 100);
    }

    /**
     * Delete old logs (retention policy)
     */
    public function deleteOldLogs(int $days = 3650): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $this->where('created_at <', $cutoffDate)->delete();
    }
}
