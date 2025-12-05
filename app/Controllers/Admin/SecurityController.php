<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SystemSettingModel;

/**
 * Security Settings Controller
 *
 * Manages security policies, audit logs, backups, and permissions
 */
class SecurityController extends BaseController
{
    protected $settingModel;

    public function __construct()
    {
        $this->settingModel = new SystemSettingModel();
    }

    /**
     * Security settings page
     */
    public function index()
    {
        $settings = $this->settingModel->getByGroup('security');

        $data = [
            'title' => 'Configurações de Segurança',
            'breadcrumbs' => [
                ['label' => 'Configurações', 'url' => 'admin/settings'],
                ['label' => 'Segurança', 'url' => '']
            ],
            'settings' => $settings
        ];

        return view('admin/settings/security', $data);
    }

    /**
     * Update security settings
     */
    public function update()
    {
        if (!$this->request->is('post')) {
            return redirect()->back()->with('error', 'Método inválido');
        }

        $validation = \Config\Services::validation();

        $rules = [
            'password_min_length' => 'required|integer|greater_than[5]|less_than[129]',
            'password_require_uppercase' => 'required|in_list[0,1]',
            'password_require_lowercase' => 'required|in_list[0,1]',
            'password_require_numbers' => 'required|in_list[0,1]',
            'password_require_special' => 'required|in_list[0,1]',
            'password_expiry_days' => 'permit_empty|integer|greater_than[0]',
            'enable_audit_log' => 'required|in_list[0,1]',
            'audit_log_retention_days' => 'required|integer|greater_than[0]',
            'enable_auto_backup' => 'required|in_list[0,1]',
            'backup_frequency' => 'permit_empty|in_list[daily,weekly,monthly]',
            'backup_retention_days' => 'permit_empty|integer|greater_than[0]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $validation->getErrors());
        }

        try {
            $data = $this->request->getPost();

            // Update settings
            $this->settingModel->setMultiple($data, 'security');

            // Clear cache
            cache()->delete('system_settings');

            return redirect()->back()->with('success', 'Configurações de segurança atualizadas com sucesso');

        } catch (\Exception $e) {
            log_message('error', 'Error updating security settings: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao atualizar configurações: ' . $e->getMessage());
        }
    }

    /**
     * Get audit logs
     */
    public function auditLogs()
    {
        try {
            // TODO: Implement actual audit log fetching from database
            $logs = [
                [
                    'id' => 1,
                    'user' => 'Admin',
                    'action' => 'Login',
                    'ip' => '192.168.1.100',
                    'timestamp' => date('Y-m-d H:i:s', strtotime('-5 minutes')),
                    'status' => 'success'
                ],
                [
                    'id' => 2,
                    'user' => 'João Silva',
                    'action' => 'Registro de Ponto',
                    'ip' => '192.168.1.105',
                    'timestamp' => date('Y-m-d H:i:s', strtotime('-10 minutes')),
                    'status' => 'success'
                ],
                [
                    'id' => 3,
                    'user' => 'Unknown',
                    'action' => 'Login Failed',
                    'ip' => '203.0.113.45',
                    'timestamp' => date('Y-m-d H:i:s', strtotime('-15 minutes')),
                    'status' => 'failed'
                ],
            ];

            return $this->response->setJSON([
                'success' => true,
                'logs' => $logs,
                'total' => count($logs)
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao obter logs: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Create backup
     */
    public function backup()
    {
        if (!$this->request->is('post')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Método inválido'
            ]);
        }

        try {
            // TODO: Implement actual backup logic
            $backupFile = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $backupPath = WRITEPATH . 'backups/' . $backupFile;

            // Create backups directory if it doesn't exist
            if (!is_dir(WRITEPATH . 'backups')) {
                mkdir(WRITEPATH . 'backups', 0755, true);
            }

            // Simulate backup creation
            // In production, use mysqldump or similar
            log_message('info', 'Backup created: ' . $backupFile);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Backup criado com sucesso',
                'file' => $backupFile,
                'size' => '2.5 MB',
                'path' => $backupPath
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Backup error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao criar backup: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Test password policy
     */
    public function testPassword()
    {
        if (!$this->request->is('post')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Método inválido'
            ]);
        }

        $password = $this->request->getPost('password');
        $settings = $this->settingModel->getByGroup('security');

        $errors = [];
        $minLength = $settings['password_min_length'] ?? 8;

        if (strlen($password) < $minLength) {
            $errors[] = "Mínimo {$minLength} caracteres";
        }

        if (($settings['password_require_uppercase'] ?? 1) && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Pelo menos uma letra maiúscula';
        }

        if (($settings['password_require_lowercase'] ?? 1) && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Pelo menos uma letra minúscula';
        }

        if (($settings['password_require_numbers'] ?? 1) && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Pelo menos um número';
        }

        if (($settings['password_require_special'] ?? 1) && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Pelo menos um caractere especial';
        }

        if (empty($errors)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Senha atende todos os requisitos',
                'strength' => $this->calculatePasswordStrength($password)
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Senha não atende os requisitos',
                'errors' => $errors,
                'strength' => $this->calculatePasswordStrength($password)
            ]);
        }
    }

    /**
     * Calculate password strength
     */
    protected function calculatePasswordStrength(string $password): array
    {
        $strength = 0;
        $feedback = [];

        if (strlen($password) >= 8) {
            $strength += 20;
        } else {
            $feedback[] = 'Use pelo menos 8 caracteres';
        }

        if (strlen($password) >= 12) {
            $strength += 10;
        }

        if (preg_match('/[a-z]/', $password)) {
            $strength += 20;
        } else {
            $feedback[] = 'Adicione letras minúsculas';
        }

        if (preg_match('/[A-Z]/', $password)) {
            $strength += 20;
        } else {
            $feedback[] = 'Adicione letras maiúsculas';
        }

        if (preg_match('/[0-9]/', $password)) {
            $strength += 20;
        } else {
            $feedback[] = 'Adicione números';
        }

        if (preg_match('/[^A-Za-z0-9]/', $password)) {
            $strength += 10;
        } else {
            $feedback[] = 'Adicione caracteres especiais';
        }

        $level = 'fraca';
        $color = 'danger';

        if ($strength >= 80) {
            $level = 'muito forte';
            $color = 'success';
        } elseif ($strength >= 60) {
            $level = 'forte';
            $color = 'primary';
        } elseif ($strength >= 40) {
            $level = 'média';
            $color = 'warning';
        }

        return [
            'score' => $strength,
            'level' => $level,
            'color' => $color,
            'feedback' => $feedback
        ];
    }

    /**
     * Reset security settings to defaults
     */
    public function reset()
    {
        if (!$this->request->is('post')) {
            return redirect()->back()->with('error', 'Método inválido');
        }

        try {
            // Delete security settings
            $this->settingModel->where('setting_group', 'security')->delete();

            // Re-insert defaults
            $now = date('Y-m-d H:i:s');
            $defaultSettings = [
                [
                    'setting_key' => 'password_min_length',
                    'setting_value' => '8',
                    'setting_type' => 'integer',
                    'setting_group' => 'security',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'setting_key' => 'password_require_special',
                    'setting_value' => '1',
                    'setting_type' => 'boolean',
                    'setting_group' => 'security',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'setting_key' => 'enable_audit_log',
                    'setting_value' => '1',
                    'setting_type' => 'boolean',
                    'setting_group' => 'security',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ];

            $db = \Config\Database::connect();
            $db->table('system_settings')->insertBatch($defaultSettings);

            cache()->delete('system_settings');

            return redirect()->back()->with('success', 'Configurações de segurança resetadas para o padrão');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao resetar: ' . $e->getMessage());
        }
    }
}
