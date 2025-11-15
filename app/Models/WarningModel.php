<?php

namespace App\Models;

use CodeIgniter\Model;

class WarningModel extends Model
{
    protected $table            = 'warnings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'employee_id',
        'warning_type',
        'occurrence_date',
        'reason',
        'evidence_files',
        'issued_by',
        'pdf_path',
        'employee_signature',
        'employee_signed_at',
        'witness_name',
        'witness_cpf',
        'witness_signature',
        'status',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'employee_id'     => 'required|integer',
        'warning_type'    => 'required|in_list[verbal,escrita,suspensao]',
        'occurrence_date' => 'required|valid_date',
        'reason'          => 'required|min_length[50]',
        'issued_by'       => 'required|integer',
    ];

    protected $validationMessages = [
        'reason' => [
            'min_length' => 'O motivo deve ter no mínimo 50 caracteres.',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['encodeEvidence'];
    protected $beforeUpdate   = ['encodeEvidence'];
    protected $afterFind      = ['decodeEvidence'];

    /**
     * Encode evidence files array to JSON
     */
    protected function encodeEvidence(array $data): array
    {
        if (isset($data['data']['evidence_files']) && is_array($data['data']['evidence_files'])) {
            $data['data']['evidence_files'] = json_encode($data['data']['evidence_files']);
        }

        return $data;
    }

    /**
     * Decode evidence files JSON to array
     */
    protected function decodeEvidence(array $data): array
    {
        if (isset($data['data'])) {
            if (is_array($data['data'])) {
                foreach ($data['data'] as &$row) {
                    if (isset($row->evidence_files) && is_string($row->evidence_files)) {
                        $row->evidence_files = json_decode($row->evidence_files, true);
                    }
                }
            } elseif (isset($data['data']->evidence_files) && is_string($data['data']->evidence_files)) {
                $data['data']->evidence_files = json_decode($data['data']->evidence_files, true);
            }
        }

        return $data;
    }

    /**
     * Get warnings by employee
     */
    public function getByEmployee(int $employeeId): array
    {
        return $this->where('employee_id', $employeeId)
            ->orderBy('occurrence_date', 'DESC')
            ->findAll();
    }

    /**
     * Get pending signature warnings
     */
    public function getPendingSignature(?int $employeeId = null): array
    {
        $builder = $this->where('status', 'pendente-assinatura');

        if ($employeeId) {
            $builder->where('employee_id', $employeeId);
        }

        return $builder->orderBy('occurrence_date', 'DESC')->findAll();
    }

    /**
     * Sign warning (employee)
     */
    public function sign(int $warningId, string $signature): bool
    {
        return $this->update($warningId, [
            'employee_signature' => $signature,
            'employee_signed_at' => date('Y-m-d H:i:s'),
            'status'             => 'assinado',
        ]);
    }

    /**
     * Refuse signature with witness
     */
    public function refuseSignature(
        int $warningId,
        string $witnessName,
        string $witnessCpf,
        string $witnessSignature
    ): bool {
        return $this->update($warningId, [
            'witness_name'      => $witnessName,
            'witness_cpf'       => $witnessCpf,
            'witness_signature' => $witnessSignature,
            'status'            => 'recusado',
        ]);
    }

    /**
     * Get warning count by type for employee
     */
    public function getCountByType(int $employeeId, string $type): int
    {
        return $this->where('employee_id', $employeeId)
            ->where('warning_type', $type)
            ->countAllResults();
    }

    /**
     * Get total warnings for employee
     */
    public function getTotalWarnings(int $employeeId): int
    {
        return $this->where('employee_id', $employeeId)->countAllResults();
    }

    /**
     * Check if employee is at warning limit (3 warnings)
     */
    public function isAtLimit(int $employeeId): bool
    {
        return $this->getTotalWarnings($employeeId) >= 3;
    }

    /**
     * Get warnings by date range
     */
    public function getByDateRange(string $startDate, string $endDate): array
    {
        return $this->where('occurrence_date >=', $startDate)
            ->where('occurrence_date <=', $endDate)
            ->orderBy('occurrence_date', 'DESC')
            ->findAll();
    }

    /**
     * Get warnings timeline for employee
     */
    public function getTimeline(int $employeeId): array
    {
        $warnings = $this->getByEmployee($employeeId);
        $timeline = [];

        foreach ($warnings as $warning) {
            $timeline[] = [
                'id'              => $warning->id,
                'type'            => $warning->warning_type,
                'date'            => $warning->occurrence_date,
                'status'          => $warning->status,
                'signed_at'       => $warning->employee_signed_at,
                'reason_preview'  => substr($warning->reason, 0, 100) . '...',
            ];
        }

        return $timeline;
    }
}
