<?php

namespace App\Models;

use CodeIgniter\Model;

class TimesheetConsolidatedModel extends Model
{
    protected $table            = 'timesheet_consolidated';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'employee_id',
        'date',
        'total_worked',
        'expected',
        'extra',
        'owed',
        'interval_violation',
        'justified',
        'incomplete',
        'justification_id',
        'punches_count',
        'first_punch',
        'last_punch',
        'total_interval',
        'notes',
        'processed_at',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules      = [
        'employee_id'  => 'required|integer',
        'date'         => 'required|valid_date',
        'total_worked' => 'required|decimal',
        'expected'     => 'required|decimal',
    ];

    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    /**
     * Get consolidated data for employee in date range
     */
    public function getByEmployeeAndRange(int $employeeId, string $startDate, string $endDate): array
    {
        return $this->where('employee_id', $employeeId)
            ->where('date >=', $startDate)
            ->where('date <=', $endDate)
            ->orderBy('date', 'ASC')
            ->findAll();
    }

    /**
     * Get current balance for employee
     */
    public function getCurrentBalance(int $employeeId): array
    {
        $data = $this->selectSum('extra', 'total_extra')
            ->selectSum('owed', 'total_owed')
            ->where('employee_id', $employeeId)
            ->first();

        $totalExtra = $data->total_extra ?? 0;
        $totalOwed = $data->total_owed ?? 0;

        $balance = $totalExtra - $totalOwed;

        return [
            'extra' => (float) $totalExtra,
            'owed' => (float) $totalOwed,
            'balance' => (float) $balance,
        ];
    }

    /**
     * Get incomplete days for employee
     */
    public function getIncompleteDays(int $employeeId, ?string $startDate = null): array
    {
        $builder = $this->where('employee_id', $employeeId)
            ->where('incomplete', true);

        if ($startDate) {
            $builder->where('date >=', $startDate);
        }

        return $builder->orderBy('date', 'DESC')->findAll();
    }

    /**
     * Get balance evolution for chart (last N days)
     */
    public function getBalanceEvolution(int $employeeId, int $days = 30): array
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        $records = $this->select('date, extra, owed')
            ->where('employee_id', $employeeId)
            ->where('date >=', $startDate)
            ->orderBy('date', 'ASC')
            ->findAll();

        $evolution = [];
        $cumulativeExtra = 0;
        $cumulativeOwed = 0;

        foreach ($records as $record) {
            $cumulativeExtra += (float) $record->extra;
            $cumulativeOwed += (float) $record->owed;

            $evolution[] = [
                'date' => $record->date,
                'balance' => $cumulativeExtra - $cumulativeOwed,
                'extra' => $cumulativeExtra,
                'owed' => $cumulativeOwed,
            ];
        }

        return $evolution;
    }

    /**
     * Check if date already processed
     */
    public function isProcessed(int $employeeId, string $date): bool
    {
        return $this->where('employee_id', $employeeId)
            ->where('date', $date)
            ->countAllResults() > 0;
    }

    /**
     * Get statistics for employee
     */
    public function getStatistics(int $employeeId, string $startDate, string $endDate): array
    {
        $data = $this->select('
                COUNT(*) as total_days,
                SUM(CASE WHEN incomplete = 1 THEN 1 ELSE 0 END) as incomplete_days,
                SUM(CASE WHEN justified = 1 THEN 1 ELSE 0 END) as justified_days,
                SUM(total_worked) as total_worked,
                SUM(expected) as total_expected,
                SUM(extra) as total_extra,
                SUM(owed) as total_owed,
                SUM(interval_violation) as total_violations,
                AVG(total_worked) as avg_worked
            ')
            ->where('employee_id', $employeeId)
            ->where('date >=', $startDate)
            ->where('date <=', $endDate)
            ->first();

        return [
            'total_days' => (int) ($data->total_days ?? 0),
            'incomplete_days' => (int) ($data->incomplete_days ?? 0),
            'justified_days' => (int) ($data->justified_days ?? 0),
            'total_worked' => (float) ($data->total_worked ?? 0),
            'total_expected' => (float) ($data->total_expected ?? 0),
            'total_extra' => (float) ($data->total_extra ?? 0),
            'total_owed' => (float) ($data->total_owed ?? 0),
            'total_violations' => (float) ($data->total_violations ?? 0),
            'avg_worked' => (float) ($data->avg_worked ?? 0),
        ];
    }
}
