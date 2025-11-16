<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Services\Security\EncryptionService;

class SettingModel extends Model
{
    protected $table            = 'settings';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'editable',
    ];

    /**
     * Encryption service instance
     * @var EncryptionService|null
     */
    protected ?EncryptionService $encryptionService = null;

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'key'   => 'required|is_unique[settings.key,id,{id}]',
        'type'  => 'required|in_list[string,integer,boolean,json,encrypted]',
        'group' => 'required',
    ];

    protected $validationMessages = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Get or initialize encryption service
     *
     * @return EncryptionService
     */
    protected function getEncryptionService(): EncryptionService
    {
        if ($this->encryptionService === null) {
            $this->encryptionService = new EncryptionService();
        }

        return $this->encryptionService;
    }

    /**
     * Get setting value by key
     */
    public function get(string $key, $default = null)
    {
        $setting = $this->where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return $this->castValue($setting->value, $setting->type);
    }

    /**
     * Set setting value
     */
    public function updateSetting(string $key, $value, ?string $type = null): bool
    {
        $existing = $this->where('key', $key)->first();

        $settingType = $type ?? ($existing->type ?? 'string');

        // Encrypt if type is 'encrypted'
        if ($settingType === 'encrypted') {
            try {
                $value = $this->getEncryptionService()->encrypt((string) $value);
            } catch (\Exception $e) {
                log_message('error', 'Failed to encrypt setting: ' . $e->getMessage());
                throw new \RuntimeException('Failed to encrypt setting value: ' . $e->getMessage());
            }
        } elseif (is_array($value)) {
            $value = json_encode($value);
        } else {
            $value = (string) $value;
        }

        $data = [
            'key'   => $key,
            'value' => $value,
            'type'  => $settingType,
        ];

        if ($existing) {
            return $this->update($existing->id, $data);
        }

        return $this->insert($data) !== false;
    }

    /**
     * Get all settings by group
     */
    public function getByGroup(string $group): array
    {
        $settings = $this->where('group', $group)->findAll();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->key] = $this->castValue($setting->value, $setting->type);
        }

        return $result;
    }

    /**
     * Get all settings as array
     */
    public function getAll(): array
    {
        $settings = $this->findAll();
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->key] = $this->castValue($setting->value, $setting->type);
        }

        return $result;
    }

    /**
     * Cast value to proper type
     */
    private function castValue($value, string $type)
    {
        switch ($type) {
            case 'integer':
                return (int) $value;

            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);

            case 'json':
                return json_decode($value, true);

            case 'encrypted':
                try {
                    return $this->getEncryptionService()->decrypt($value);
                } catch (\Exception $e) {
                    log_message('error', 'Failed to decrypt setting: ' . $e->getMessage());
                    // Return null on decryption failure instead of throwing
                    // This prevents breaking the application if key is rotated
                    return null;
                }

            default:
                return $value;
        }
    }

    /**
     * Get editable settings
     */
    public function getEditable(): array
    {
        return $this->where('editable', true)->findAll();
    }

    /**
     * Update multiple settings at once
     */
    public function updateMany(array $settings): bool
    {
        $db = \Config\Database::connect();
        $db->transStart();

        foreach ($settings as $key => $value) {
            $this->updateSetting($key, $value);
        }

        $db->transComplete();

        return $db->transStatus();
    }
}
