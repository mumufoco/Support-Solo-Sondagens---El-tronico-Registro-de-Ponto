<?php

namespace App\Models;

use CodeIgniter\Model;

class TimePunchModel extends Model
{
    protected $table            = 'time_punches';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'employee_id',
        'punch_time',
        'punch_type',
        'method',
        'nsr',
        'hash',
        'location_lat',
        'location_lng',
        'location_accuracy',
        'within_geofence',
        'geofence_name',
        'face_similarity',
        'ip_address',
        'user_agent',
        'notes',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = false; // Time punches não são atualizados

    // Validation
    protected $validationRules = [
        'employee_id' => 'required|integer',
        'punch_time'  => 'required|valid_date',
        'punch_type'  => 'required|in_list[entrada,saida,intervalo-inicio,intervalo-fim]',
        'method'      => 'required|in_list[codigo,qrcode,facial,biometria]',
    ];

    protected $validationMessages = [
        'employee_id' => [
            'required' => 'O ID do funcionário é obrigatório.',
            'integer'  => 'O ID do funcionário deve ser um número.',
        ],
        'punch_type' => [
            'required' => 'O tipo de marcação é obrigatório.',
            'in_list'  => 'Tipo de marcação inválido.',
        ],
        'method' => [
            'required' => 'O método de registro é obrigatório.',
            'in_list'  => 'Método de registro inválido.',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = ['generateNSR', 'generateHash'];

    /**
     * Generate NSR (Número Sequencial de Registro)
     */
    protected function generateNSR(array $data): array
    {
        if (!isset($data['data']['nsr'])) {
            $db = \Config\Database::connect();
            $db->transStart();

            // SECURITY FIX: Lock table to prevent race condition in NSR generation
            // This ensures two simultaneous requests don't get the same NSR
            $db->query('LOCK TABLES ' . $this->table . ' WRITE');

            try {
                $builder = $db->table($this->table);
                $lastNSR = $builder->selectMax('nsr')->get()->getRow();

                $data['data']['nsr'] = ($lastNSR && $lastNSR->nsr) ? $lastNSR->nsr + 1 : 1;

                // Unlock tables before transaction complete
                $db->query('UNLOCK TABLES');
            } catch (\Exception $e) {
                // Ensure tables are unlocked even if error occurs
                $db->query('UNLOCK TABLES');
                throw $e;
            }

            $db->transComplete();
        }

        return $data;
    }

    /**
     * Generate SHA-256 hash for integrity
     */
    protected function generateHash(array $data): array
    {
        if (isset($data['data']['employee_id']) && isset($data['data']['punch_time']) && isset($data['data']['nsr'])) {
            $hashString = sprintf(
                '%d|%s|%s|%s',
                $data['data']['employee_id'],
                $data['data']['punch_type'] ?? '',
                $data['data']['punch_time'],
                $data['data']['nsr']
            );

            $data['data']['hash'] = hash('sha256', $hashString);
        }

        return $data;
    }

    /**
     * Get punches by employee and date
     */
    public function getPunchesByDate(int $employeeId, string $date): array
    {
        return $this->where('employee_id', $employeeId)
            ->where('DATE(punch_time)', $date)
            ->orderBy('punch_time', 'ASC')
            ->findAll();
    }

    /**
     * Get last punch for employee
     */
    public function getLastPunch(int $employeeId, ?string $date = null): ?object
    {
        $builder = $this->where('employee_id', $employeeId);

        if ($date) {
            $builder->where('DATE(punch_time)', $date);
        }

        return $builder->orderBy('punch_time', 'DESC')
            ->first();
    }

    /**
     * Get punches by employee and date range
     */
    public function getPunchesByDateRange(int $employeeId, string $startDate, string $endDate): array
    {
        return $this->where('employee_id', $employeeId)
            ->where('DATE(punch_time) >=', $startDate)
            ->where('DATE(punch_time) <=', $endDate)
            ->orderBy('punch_time', 'ASC')
            ->findAll();
    }

    /**
     * Check if employee can punch (prevent duplicate punches within 1 minute)
     */
    public function canPunch(int $employeeId): bool
    {
        $lastPunch = $this->getLastPunch($employeeId);

        if (!$lastPunch) {
            return true;
        }

        $lastPunchTime = strtotime($lastPunch->punch_time);
        $now = time();

        // Allow punch if last punch was more than 1 minute ago
        return ($now - $lastPunchTime) >= 60;
    }

    /**
     * Determine next punch type based on last punch
     */
    public function getNextPunchType(int $employeeId, ?string $date = null): string
    {
        $lastPunch = $this->getLastPunch($employeeId, $date);

        if (!$lastPunch) {
            return 'entrada';
        }

        $mapping = [
            'entrada'          => 'intervalo-inicio',
            'intervalo-inicio' => 'intervalo-fim',
            'intervalo-fim'    => 'saida',
            'saida'            => 'entrada', // New day
        ];

        return $mapping[$lastPunch->punch_type] ?? 'entrada';
    }

    /**
     * Validate punch pairs (entrada/saida, intervalo-inicio/intervalo-fim)
     */
    public function validatePairs(int $employeeId, string $date): array
    {
        $punches = $this->getPunchesByDate($employeeId, $date);

        $validation = [
            'complete'  => false,
            'pairs'     => [],
            'missing'   => [],
            'total'     => count($punches),
        ];

        if (empty($punches)) {
            $validation['missing'][] = 'Nenhuma marcação encontrada';
            return $validation;
        }

        $types = array_column($punches, 'punch_type');

        // Check entrada/saida
        if (in_array('entrada', $types) && !in_array('saida', $types)) {
            $validation['missing'][] = 'Falta marcação de saída';
        }

        // Check intervalo
        if (in_array('intervalo-inicio', $types) && !in_array('intervalo-fim', $types)) {
            $validation['missing'][] = 'Falta marcação de fim de intervalo';
        }

        $validation['complete'] = empty($validation['missing']);
        $validation['pairs'] = $this->groupPairs($punches);

        return $validation;
    }

    /**
     * Group punches into pairs
     */
    private function groupPairs(array $punches): array
    {
        $pairs = [];
        $temp = null;

        foreach ($punches as $punch) {
            if (in_array($punch->punch_type, ['entrada', 'intervalo-inicio'])) {
                $temp = $punch;
            } elseif ($temp && in_array($punch->punch_type, ['saida', 'intervalo-fim'])) {
                $pairs[] = [
                    'start' => $temp,
                    'end'   => $punch,
                    'type'  => str_contains($temp->punch_type, 'intervalo') ? 'interval' : 'work',
                ];
                $temp = null;
            }
        }

        return $pairs;
    }

    /**
     * Calculate total hours worked for a date
     */
    public function calculateHours(int $employeeId, string $date): array
    {
        $punches = $this->getPunchesByDate($employeeId, $date);
        $pairs = $this->groupPairs($punches);

        $totalWork = 0;
        $totalInterval = 0;

        foreach ($pairs as $pair) {
            $start = strtotime($pair['start']->punch_time);
            $end = strtotime($pair['end']->punch_time);
            $hours = ($end - $start) / 3600; // Convert to hours

            if ($pair['type'] === 'work') {
                $totalWork += $hours;
            } else {
                $totalInterval += $hours;
            }
        }

        return [
            'total_work'     => round($totalWork, 2),
            'total_interval' => round($totalInterval, 2),
            'net_work'       => round($totalWork - $totalInterval, 2),
            'pairs'          => count($pairs),
        ];
    }

    /**
     * Get punches outside geofence
     */
    public function getOutsideGeofence(int $employeeId, string $startDate, string $endDate): array
    {
        return $this->where('employee_id', $employeeId)
            ->where('DATE(punch_time) >=', $startDate)
            ->where('DATE(punch_time) <=', $endDate)
            ->where('within_geofence', false)
            ->findAll();
    }

    /**
     * Get punches by method
     */
    public function getByMethod(string $method, ?int $employeeId = null, ?string $date = null): array
    {
        $builder = $this->where('method', $method);

        if ($employeeId) {
            $builder->where('employee_id', $employeeId);
        }

        if ($date) {
            $builder->where('DATE(punch_time)', $date);
        }

        return $builder->findAll();
    }

    /**
     * Get today's punches count
     */
    public function getTodayPunchesCount(): int
    {
        return $this->where('DATE(punch_time)', date('Y-m-d'))
            ->countAllResults();
    }

    /**
     * Verify hash integrity
     */
    public function verifyHash(object $punch): bool
    {
        $hashString = sprintf(
            '%d|%s|%s|%s',
            $punch->employee_id,
            $punch->punch_type,
            $punch->punch_time,
            $punch->nsr
        );

        $expectedHash = hash('sha256', $hashString);

        return $expectedHash === $punch->hash;
    }
}
