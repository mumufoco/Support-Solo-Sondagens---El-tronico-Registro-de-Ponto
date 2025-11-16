<?php

namespace App\Models;

use CodeIgniter\Model;

class JustificationModel extends Model
{
    protected $table            = 'justifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'employee_id',
        'justification_date',
        'justification_type',
        'category',
        'reason',
        'attachments',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'submitted_by',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'employee_id'         => 'required|integer',
        'justification_date'  => 'required|valid_date',
        'justification_type'  => 'required|in_list[falta,atraso,saida-antecipada]',
        'category'            => 'required|in_list[doenca,compromisso-pessoal,emergencia-familiar,outro]',
        'reason'              => 'required|min_length[50]|max_length[5000]',
    ];

    protected $validationMessages = [
        'reason' => [
            'min_length' => 'O motivo deve ter no mínimo 50 caracteres.',
            'max_length' => 'O motivo deve ter no máximo 5000 caracteres.',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['encodeAttachments'];
    protected $beforeUpdate   = ['encodeAttachments'];
    protected $afterFind      = ['decodeAttachments'];

    /**
     * Encode attachments array to JSON
     */
    protected function encodeAttachments(array $data): array
    {
        if (isset($data['data']['attachments']) && is_array($data['data']['attachments'])) {
            $data['data']['attachments'] = json_encode($data['data']['attachments']);
        }

        return $data;
    }

    /**
     * Decode attachments JSON to array
     */
    protected function decodeAttachments(array $data): array
    {
        if (isset($data['data'])) {
            if (is_array($data['data'])) {
                foreach ($data['data'] as &$row) {
                    if (isset($row->attachments) && is_string($row->attachments)) {
                        $row->attachments = json_decode($row->attachments, true);
                    }
                }
            } elseif (isset($data['data']->attachments) && is_string($data['data']->attachments)) {
                $data['data']->attachments = json_decode($data['data']->attachments, true);
            }
        }

        return $data;
    }

    /**
     * Get pending justifications
     */
    public function getPending(?int $employeeId = null): array
    {
        $builder = $this->where('status', 'pendente');

        if ($employeeId) {
            $builder->where('employee_id', $employeeId);
        }

        return $builder->orderBy('justification_date', 'DESC')->findAll();
    }

    /**
     * Get justifications by employee and date range
     */
    public function getByDateRange(int $employeeId, string $startDate, string $endDate): array
    {
        return $this->where('employee_id', $employeeId)
            ->where('justification_date >=', $startDate)
            ->where('justification_date <=', $endDate)
            ->orderBy('justification_date', 'DESC')
            ->findAll();
    }

    /**
     * Approve justification
     */
    public function approve(int $justificationId, int $approvedBy): bool
    {
        return $this->update($justificationId, [
            'status'      => 'aprovado',
            'approved_by' => $approvedBy,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Reject justification
     */
    public function reject(int $justificationId, int $approvedBy, string $reason): bool
    {
        return $this->update($justificationId, [
            'status'           => 'rejeitado',
            'approved_by'      => $approvedBy,
            'approved_at'      => date('Y-m-d H:i:s'),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Check if date has approved justification
     */
    public function hasApprovedJustification(int $employeeId, string $date): bool
    {
        return $this->where('employee_id', $employeeId)
            ->where('justification_date', $date)
            ->where('status', 'aprovado')
            ->countAllResults() > 0;
    }

    /**
     * Get total pending count
     */
    public function getPendingCount(): int
    {
        return $this->where('status', 'pendente')->countAllResults();
    }
}
