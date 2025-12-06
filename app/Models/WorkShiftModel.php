<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Work Shift Model
 *
 * Manages work shifts (morning, afternoon, night, custom)
 */
class WorkShiftModel extends Model
{
    protected $table = 'work_shifts';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = true;
    protected $protectFields = true;

    protected $allowedFields = [
        'name',
        'description',
        'start_time',
        'end_time',
        'color',
        'type',
        'break_duration',
        'active',
        'created_by'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'name' => 'required|max_length[100]',
        'start_time' => 'required|valid_time',
        'end_time' => 'required|valid_time',
        'type' => 'required|in_list[morning,afternoon,night,custom]',
        'color' => 'permit_empty|max_length[7]',
        'break_duration' => 'permit_empty|integer|greater_than_equal_to[0]'
    ];

    protected $validationMessages = [
        'name' => [
            'required' => 'O nome do turno é obrigatório',
            'max_length' => 'O nome não pode ter mais de 100 caracteres'
        ],
        'start_time' => [
            'required' => 'O horário de início é obrigatório',
            'valid_time' => 'Horário de início inválido'
        ],
        'end_time' => [
            'required' => 'O horário de término é obrigatório',
            'valid_time' => 'Horário de término inválido'
        ],
        'type' => [
            'required' => 'O tipo de turno é obrigatório',
            'in_list' => 'Tipo de turno inválido'
        ]
    ];

    // Callbacks
    protected $beforeInsert = ['setCreatedBy'];
    protected $beforeUpdate = [];

    /**
     * Set created_by field
     */
    protected function setCreatedBy(array $data): array
    {
        if (!isset($data['data']['created_by'])) {
            $session = session();
            $userId = $session->get('user_id');

            if ($userId) {
                $data['data']['created_by'] = $userId;
            }
        }

        return $data;
    }

    /**
     * Get all active shifts
     */
    public function getActiveShifts(): array
    {
        return $this->where('active', 1)->findAll();
    }

    /**
     * Get shifts by type
     */
    public function getShiftsByType(string $type): array
    {
        return $this->where('type', $type)
            ->where('active', 1)
            ->findAll();
    }

    /**
     * Calculate shift duration in hours
     */
    public function calculateDuration(object $shift): float
    {
        $start = strtotime($shift->start_time);
        $end = strtotime($shift->end_time);

        // Handle overnight shifts
        if ($end < $start) {
            $end += 86400; // Add 24 hours
        }

        $duration = ($end - $start) / 3600; // Convert to hours

        // Subtract break duration
        if (!empty($shift->break_duration)) {
            $duration -= ($shift->break_duration / 60);
        }

        return round($duration, 2);
    }

    /**
     * Check if time ranges overlap
     */
    public function hasTimeOverlap(string $start1, string $end1, string $start2, string $end2): bool
    {
        $s1 = strtotime($start1);
        $e1 = strtotime($end1);
        $s2 = strtotime($start2);
        $e2 = strtotime($end2);

        // Handle overnight shifts
        if ($e1 < $s1) $e1 += 86400;
        if ($e2 < $s2) $e2 += 86400;

        return ($s1 < $e2) && ($e1 > $s2);
    }

    /**
     * Get shift statistics
     */
    public function getShiftStatistics(): array
    {
        $stats = [
            'total' => $this->countAllResults(false),
            'active' => $this->where('active', 1)->countAllResults(false),
            'by_type' => []
        ];

        $types = ['morning', 'afternoon', 'night', 'custom'];
        foreach ($types as $type) {
            $stats['by_type'][$type] = $this->where('type', $type)
                ->where('active', 1)
                ->countAllResults(false);
        }

        return $stats;
    }

    /**
     * Get shifts with employee count
     */
    public function getShiftsWithEmployeeCount(): array
    {
        return $this->select('work_shifts.*, COUNT(DISTINCT schedules.employee_id) as employee_count')
            ->join('schedules', 'schedules.shift_id = work_shifts.id', 'left')
            ->where('work_shifts.active', 1)
            ->groupBy('work_shifts.id')
            ->findAll();
    }

    /**
     * Clone a shift
     */
    public function cloneShift(int $shiftId): ?int
    {
        $shift = $this->find($shiftId);

        if (!$shift) {
            return null;
        }

        $newShift = [
            'name' => $shift->name . ' (Cópia)',
            'description' => $shift->description,
            'start_time' => $shift->start_time,
            'end_time' => $shift->end_time,
            'color' => $shift->color,
            'type' => $shift->type,
            'break_duration' => $shift->break_duration,
            'active' => 0 // Clones start as inactive
        ];

        return $this->insert($newShift) ? $this->getInsertID() : null;
    }

    /**
     * Get default shifts for installation
     */
    public static function getDefaultShifts(): array
    {
        return [
            [
                'name' => 'Manhã',
                'description' => 'Turno da manhã padrão',
                'start_time' => '08:00:00',
                'end_time' => '12:00:00',
                'color' => '#FFA500',
                'type' => 'morning',
                'break_duration' => 0,
                'active' => 1
            ],
            [
                'name' => 'Tarde',
                'description' => 'Turno da tarde padrão',
                'start_time' => '13:00:00',
                'end_time' => '18:00:00',
                'color' => '#4169E1',
                'type' => 'afternoon',
                'break_duration' => 0,
                'active' => 1
            ],
            [
                'name' => 'Noite',
                'description' => 'Turno da noite padrão',
                'start_time' => '22:00:00',
                'end_time' => '06:00:00',
                'color' => '#2F4F4F',
                'type' => 'night',
                'break_duration' => 60,
                'active' => 1
            ],
            [
                'name' => 'Comercial',
                'description' => 'Horário comercial completo',
                'start_time' => '08:00:00',
                'end_time' => '18:00:00',
                'color' => '#228B22',
                'type' => 'custom',
                'break_duration' => 60,
                'active' => 1
            ]
        ];
    }
}
