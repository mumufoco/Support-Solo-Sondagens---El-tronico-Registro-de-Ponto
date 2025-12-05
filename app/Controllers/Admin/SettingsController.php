<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SystemSettingModel;

/**
 * Settings Controller
 *
 * Main controller for system settings management
 */
class SettingsController extends BaseController
{
    protected $settingModel;

    public function __construct()
    {
        $this->settingModel = new SystemSettingModel();
    }

    /**
     * Settings dashboard/index
     */
    public function index()
    {
        // Get statistics about settings
        $stats = [
            'appearance' => count($this->settingModel->getByGroup('appearance')),
            'authentication' => count($this->settingModel->getByGroup('authentication')),
            'certificate' => count($this->settingModel->getByGroup('certificate')),
            'system' => count($this->settingModel->getByGroup('system')),
            'security' => count($this->settingModel->getByGroup('security')),
        ];

        $data = [
            'title' => 'Configurações do Sistema',
            'breadcrumbs' => [
                ['label' => 'Configurações', 'url' => '']
            ],
            'stats' => $stats
        ];

        return view('admin/settings/index', $data);
    }

    /**
     * Clear all cached settings
     */
    public function clearCache()
    {
        cache()->delete('system_settings');
        cache()->delete('design_system_css');

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Cache limpo com sucesso'
        ]);
    }

    /**
     * Export settings as JSON
     */
    public function export()
    {
        $settings = $this->settingModel->getAllSettings();

        // Remove sensitive data
        $filtered = array_filter($settings, function($key) {
            return !str_contains($key, 'password') &&
                   !str_contains($key, 'secret') &&
                   !str_contains($key, 'key');
        }, ARRAY_FILTER_USE_KEY);

        return $this->response->download(
            'settings-export-' . date('Y-m-d') . '.json',
            json_encode($filtered, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Import settings from JSON
     */
    public function import()
    {
        if (!$this->request->is('post')) {
            return redirect()->back()->with('error', 'Método inválido');
        }

        $file = $this->request->getFile('settings_file');

        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Arquivo inválido');
        }

        try {
            $content = file_get_contents($file->getTempName());
            $settings = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON inválido');
            }

            // Import settings
            foreach ($settings as $group => $groupSettings) {
                if (is_array($groupSettings)) {
                    $this->settingModel->setMultiple($groupSettings, $group);
                }
            }

            return redirect()->back()->with('success', 'Configurações importadas com sucesso');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao importar: ' . $e->getMessage());
        }
    }

    /**
     * Reset settings to default
     */
    public function reset()
    {
        if (!$this->request->is('post')) {
            return redirect()->back()->with('error', 'Método inválido');
        }

        $group = $this->request->getPost('group');

        if ($group && in_array($group, ['appearance', 'authentication', 'certificate', 'system', 'security'])) {
            // Delete settings for specific group
            $this->settingModel->where('setting_group', $group)->delete();
            $message = "Configurações de {$group} resetadas com sucesso";
        } else {
            // Reset all (dangerous - should require confirmation)
            $this->settingModel->truncate();
            $message = 'Todas as configurações foram resetadas';
        }

        // Clear cache
        cache()->delete('system_settings');

        return redirect()->back()->with('success', $message);
    }

    /**
     * Test database connection
     */
    public function testDatabase()
    {
        try {
            $db = \Config\Database::connect();
            $db->query('SELECT 1');

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Conexão com banco de dados OK',
                'database' => $db->database
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get system info
     */
    public function systemInfo()
    {
        $info = [
            'php_version' => PHP_VERSION,
            'codeigniter_version' => \CodeIgniter\CodeIgniter::CI_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'max_upload_size' => ini_get('upload_max_filesize'),
            'max_post_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'timezone' => date_default_timezone_get(),
            'environment' => ENVIRONMENT,
        ];

        return $this->response->setJSON([
            'success' => true,
            'info' => $info
        ]);
    }
}
