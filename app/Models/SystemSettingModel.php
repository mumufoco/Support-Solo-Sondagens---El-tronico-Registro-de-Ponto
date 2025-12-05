<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * System Setting Model
 *
 * Manages system-wide configuration settings stored in database
 */
class SystemSettingModel extends Model
{
    protected $table = 'system_settings';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'object';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'setting_key',
        'setting_value',
        'setting_type',
        'setting_group',
        'is_encrypted',
        'description'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    // Validation
    protected $validationRules = [
        'setting_key' => 'required|max_length[100]|is_unique[system_settings.setting_key,id,{id}]',
        'setting_value' => 'permit_empty',
        'setting_type' => 'required|in_list[string,integer,boolean,json,file]',
        'setting_group' => 'required|in_list[appearance,authentication,certificate,system,security]'
    ];

    protected $validationMessages = [
        'setting_key' => [
            'required' => 'A chave da configuração é obrigatória',
            'is_unique' => 'Esta chave de configuração já existe'
        ]
    ];

    /**
     * Get a setting value by key
     *
     * @param string $key Setting key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $setting = $this->where('setting_key', $key)->first();

        if (!$setting) {
            return $default;
        }

        // Decrypt if encrypted
        if ($setting->is_encrypted) {
            $setting->setting_value = $this->decrypt($setting->setting_value);
        }

        // Type casting based on setting_type
        return $this->castValue($setting->setting_value, $setting->setting_type);
    }

    /**
     * Set a setting value
     *
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @param string $type Value type
     * @param string $group Setting group
     * @param bool $encrypt Whether to encrypt the value
     * @return bool
     */
    public function set(string $key, $value, string $type = 'string', string $group = 'system', bool $encrypt = false): bool
    {
        // Convert value to string for storage
        $storedValue = $this->prepareValue($value, $type);

        // Encrypt if needed
        if ($encrypt) {
            $storedValue = $this->encrypt($storedValue);
        }

        $data = [
            'setting_key' => $key,
            'setting_value' => $storedValue,
            'setting_type' => $type,
            'setting_group' => $group,
            'is_encrypted' => $encrypt
        ];

        // Check if exists
        $existing = $this->where('setting_key', $key)->first();

        if ($existing) {
            return $this->update($existing->id, $data);
        }

        return (bool) $this->insert($data);
    }

    /**
     * Get all settings by group
     *
     * @param string $group Setting group
     * @return array
     */
    public function getByGroup(string $group): array
    {
        $settings = $this->where('setting_group', $group)->findAll();
        $result = [];

        foreach ($settings as $setting) {
            $value = $setting->setting_value;

            // Decrypt if needed
            if ($setting->is_encrypted) {
                $value = $this->decrypt($value);
            }

            // Cast to proper type
            $result[$setting->setting_key] = $this->castValue($value, $setting->setting_type);
        }

        return $result;
    }

    /**
     * Set multiple settings at once
     *
     * @param array $settings Array of [key => value]
     * @param string $group Setting group
     * @return bool
     */
    public function setMultiple(array $settings, string $group = 'system'): bool
    {
        $this->db->transStart();

        foreach ($settings as $key => $value) {
            // Determine type
            $type = $this->determineType($value);
            $encrypt = str_contains($key, 'password') || str_contains($key, 'secret');

            $this->set($key, $value, $type, $group, $encrypt);
        }

        $this->db->transComplete();

        return $this->db->transStatus();
    }

    /**
     * Delete a setting
     *
     * @param string $key Setting key
     * @return bool
     */
    public function deleteSetting(string $key): bool
    {
        return $this->where('setting_key', $key)->delete();
    }

    /**
     * Get all settings as key-value array
     *
     * @return array
     */
    public function getAllSettings(): array
    {
        $settings = $this->findAll();
        $result = [];

        foreach ($settings as $setting) {
            $value = $setting->setting_value;

            if ($setting->is_encrypted) {
                $value = $this->decrypt($value);
            }

            $result[$setting->setting_key] = $this->castValue($value, $setting->setting_type);
        }

        return $result;
    }

    /**
     * Cast value to proper type
     *
     * @param string $value Stored value
     * @param string $type Type to cast to
     * @return mixed
     */
    protected function castValue(string $value, string $type)
    {
        switch ($type) {
            case 'integer':
                return (int) $value;
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($value, true);
            case 'file':
            case 'string':
            default:
                return $value;
        }
    }

    /**
     * Prepare value for storage
     *
     * @param mixed $value Value to prepare
     * @param string $type Value type
     * @return string
     */
    protected function prepareValue($value, string $type): string
    {
        switch ($type) {
            case 'json':
                return json_encode($value);
            case 'boolean':
                return $value ? '1' : '0';
            default:
                return (string) $value;
        }
    }

    /**
     * Determine type from value
     *
     * @param mixed $value
     * @return string
     */
    protected function determineType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'integer';
        }
        if (is_array($value)) {
            return 'json';
        }
        return 'string';
    }

    /**
     * Encrypt a value
     *
     * @param string $value
     * @return string
     */
    protected function encrypt(string $value): string
    {
        $encrypter = \Config\Services::encrypter();
        return base64_encode($encrypter->encrypt($value));
    }

    /**
     * Decrypt a value
     *
     * @param string $value
     * @return string
     */
    protected function decrypt(string $value): string
    {
        try {
            $encrypter = \Config\Services::encrypter();
            return $encrypter->decrypt(base64_decode($value));
        } catch (\Exception $e) {
            log_message('error', 'Failed to decrypt setting: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Create settings table migration
     * This can be called from a migration or setup script
     *
     * @return string SQL for creating the table
     */
    public static function getCreateTableSQL(): string
    {
        return "
            CREATE TABLE IF NOT EXISTS `system_settings` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `setting_key` VARCHAR(100) NOT NULL UNIQUE,
                `setting_value` TEXT NULL,
                `setting_type` ENUM('string', 'integer', 'boolean', 'json', 'file') DEFAULT 'string',
                `setting_group` ENUM('appearance', 'authentication', 'certificate', 'system', 'security') DEFAULT 'system',
                `is_encrypted` TINYINT(1) DEFAULT 0,
                `description` VARCHAR(255) NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                INDEX `idx_setting_key` (`setting_key`),
                INDEX `idx_setting_group` (`setting_group`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
    }
}
