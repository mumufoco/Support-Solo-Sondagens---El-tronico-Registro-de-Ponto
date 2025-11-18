<?php

namespace App\Models;

use CodeIgniter\Model;

class BiometricTemplateModel extends Model
{
    protected $table            = 'biometric_templates';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'employee_id',
        'biometric_type',
        'template_data',
        'template_hash',
        'file_path',
        'enrollment_quality',
        'model_used',
        'active',
        'enrolled_by',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'employee_id'    => 'required|integer',
        'biometric_type' => 'required|in_list[face,fingerprint]',
    ];

    protected $validationMessages = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // SECURITY FIX: Callbacks for biometric data encryption
    // Encrypts sensitive biometric data before storing in database
    // This protects biometric templates from unauthorized access or data breaches
    protected $beforeInsert = ['encryptBiometricData'];
    protected $beforeUpdate = ['encryptBiometricData'];
    protected $afterFind    = ['decryptBiometricData'];

    /**
     * Encrypt biometric data before saving
     *
     * SECURITY: Biometric data is highly sensitive and must be encrypted at rest
     * Uses CodeIgniter's Encryption service with the configured encryption key
     *
     * @param array $data
     * @return array
     */
    protected function encryptBiometricData(array $data): array
    {
        // Only encrypt if template_data is present in the data array
        if (!isset($data['data']['template_data']) || empty($data['data']['template_data'])) {
            return $data;
        }

        try {
            $encrypter = \Config\Services::encrypter();

            // Check if data is already encrypted (starts with 'enc:')
            if (is_string($data['data']['template_data']) &&
                strpos($data['data']['template_data'], 'enc:') === 0) {
                // Already encrypted, don't encrypt again
                return $data;
            }

            // Encrypt the template data
            $encrypted = $encrypter->encrypt($data['data']['template_data']);

            // Add prefix to identify encrypted data
            $data['data']['template_data'] = 'enc:' . base64_encode($encrypted);

            log_message('info', 'Biometric template data encrypted for employee: ' . ($data['data']['employee_id'] ?? 'unknown'));
        } catch (\Exception $e) {
            log_message('error', 'Failed to encrypt biometric data: ' . $e->getMessage());
            throw new \RuntimeException('Falha ao criptografar dados biométricos. Contate o administrador.');
        }

        return $data;
    }

    /**
     * Decrypt biometric data after retrieval
     *
     * SECURITY: Decrypts biometric data when loading from database
     * Only decrypts data that has the 'enc:' prefix
     *
     * @param array $data
     * @return array
     */
    protected function decryptBiometricData(array $data): array
    {
        // Handle single result
        if (isset($data['data'])) {
            $data['data'] = $this->decryptSingleRecord($data['data']);
            return $data;
        }

        // Handle multiple results (findAll)
        if (isset($data['id'])) {
            // This is a single record without 'data' wrapper
            return $this->decryptSingleRecord($data);
        }

        // Handle array of records
        if (is_array($data)) {
            foreach ($data as $key => $record) {
                if (is_object($record) || is_array($record)) {
                    $data[$key] = $this->decryptSingleRecord($record);
                }
            }
        }

        return $data;
    }

    /**
     * Decrypt a single biometric template record
     *
     * @param object|array $record
     * @return object|array
     */
    protected function decryptSingleRecord($record)
    {
        if (empty($record)) {
            return $record;
        }

        $isObject = is_object($record);
        $templateData = $isObject ? $record->template_data ?? null : ($record['template_data'] ?? null);

        // Check if template_data exists and is encrypted
        if (!empty($templateData) && is_string($templateData) && strpos($templateData, 'enc:') === 0) {
            try {
                $encrypter = \Config\Services::encrypter();

                // Remove prefix and decode
                $encrypted = base64_decode(substr($templateData, 4));

                // Decrypt
                $decrypted = $encrypter->decrypt($encrypted);

                // Update the record
                if ($isObject) {
                    $record->template_data = $decrypted;
                } else {
                    $record['template_data'] = $decrypted;
                }
            } catch (\Exception $e) {
                log_message('error', 'Failed to decrypt biometric data: ' . $e->getMessage());
                // Return encrypted data as-is rather than failing
            }
        }

        return $record;
    }

    /**
     * Get active biometric template for employee
     */
    public function getActive(int $employeeId, string $type): ?object
    {
        return $this->where('employee_id', $employeeId)
            ->where('biometric_type', $type)
            ->where('active', true)
            ->first();
    }

    /**
     * Get all templates for employee
     */
    public function getByEmployee(int $employeeId): array
    {
        return $this->where('employee_id', $employeeId)
            ->findAll();
    }

    /**
     * Deactivate all templates of a type for an employee
     */
    public function deactivateType(int $employeeId, string $type): bool
    {
        return $this->where('employee_id', $employeeId)
            ->where('biometric_type', $type)
            ->set(['active' => false])
            ->update();
    }

    /**
     * Check if employee has biometric enrolled
     */
    public function hasEnrolled(int $employeeId, string $type): bool
    {
        return $this->where('employee_id', $employeeId)
            ->where('biometric_type', $type)
            ->where('active', true)
            ->countAllResults() > 0;
    }

    /**
     * Get total enrolled by type
     */
    public function getTotalEnrolled(string $type): int
    {
        return $this->where('biometric_type', $type)
            ->where('active', true)
            ->countAllResults();
    }
}
