<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SystemSettingModel;

/**
 * System Settings Controller
 *
 * Manages system-wide settings: company info, timezone, language, integrations
 */
class SystemController extends BaseController
{
    protected $settingModel;

    public function __construct()
    {
        $this->settingModel = new SystemSettingModel();
    }

    /**
     * System settings page
     */
    public function index()
    {
        $settings = $this->settingModel->getByGroup('system');

        // Get list of timezones
        $timezones = timezone_identifiers_list();

        // Get list of languages
        $languages = [
            'pt-BR' => 'Português (Brasil)',
            'en-US' => 'English (US)',
            'es-ES' => 'Español',
            'fr-FR' => 'Français',
        ];

        $data = [
            'title' => 'Configurações do Sistema',
            'breadcrumbs' => [
                ['label' => 'Configurações', 'url' => 'admin/settings'],
                ['label' => 'Sistema', 'url' => '']
            ],
            'settings' => $settings,
            'timezones' => $timezones,
            'languages' => $languages
        ];

        return view('admin/settings/system', $data);
    }

    /**
     * Update system settings
     */
    public function update()
    {
        if (!$this->request->is('post')) {
            return redirect()->back()->with('error', 'Método inválido');
        }

        $validation = \Config\Services::validation();

        $rules = [
            'company_cnpj' => 'permit_empty|exact_length[18]',
            'company_address' => 'permit_empty|max_length[255]',
            'company_phone' => 'permit_empty|max_length[20]',
            'company_email' => 'permit_empty|valid_email',
            'timezone' => 'required|max_length[50]',
            'language' => 'required|in_list[pt-BR,en-US,es-ES,fr-FR]',
            'date_format' => 'required|in_list[d/m/Y,m/d/Y,Y-m-d]',
            'time_format' => 'required|in_list[H:i,h:i A]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $validation->getErrors());
        }

        try {
            $data = $this->request->getPost();

            // Update settings
            $this->settingModel->setMultiple($data, 'system');

            // Update timezone in PHP
            if (isset($data['timezone'])) {
                date_default_timezone_set($data['timezone']);
            }

            // Clear cache
            cache()->delete('system_settings');

            return redirect()->back()->with('success', 'Configurações do sistema atualizadas com sucesso');

        } catch (\Exception $e) {
            log_message('error', 'Error updating system settings: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao atualizar configurações: ' . $e->getMessage());
        }
    }

    /**
     * Test timezone configuration
     */
    public function testTimezone()
    {
        if (!$this->request->is('post')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Método inválido'
            ]);
        }

        $timezone = $this->request->getPost('timezone');

        try {
            $oldTimezone = date_default_timezone_get();
            date_default_timezone_set($timezone);

            $now = new \DateTime();

            $info = [
                'timezone' => $timezone,
                'current_time' => $now->format('Y-m-d H:i:s'),
                'offset' => $now->format('P'),
                'is_dst' => $now->format('I') === '1',
            ];

            // Restore old timezone
            date_default_timezone_set($oldTimezone);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Fuso horário válido',
                'info' => $info
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Fuso horário inválido: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Reset system settings to defaults
     */
    public function reset()
    {
        if (!$this->request->is('post')) {
            return redirect()->back()->with('error', 'Método inválido');
        }

        try {
            // Delete system settings
            $this->settingModel->where('setting_group', 'system')->delete();

            // Re-insert defaults
            $now = date('Y-m-d H:i:s');
            $defaultSettings = [
                [
                    'setting_key' => 'company_cnpj',
                    'setting_value' => '',
                    'setting_type' => 'string',
                    'setting_group' => 'system',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'setting_key' => 'timezone',
                    'setting_value' => 'America/Sao_Paulo',
                    'setting_type' => 'string',
                    'setting_group' => 'system',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'setting_key' => 'language',
                    'setting_value' => 'pt-BR',
                    'setting_type' => 'string',
                    'setting_group' => 'system',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ];

            $db = \Config\Database::connect();
            $db->table('system_settings')->insertBatch($defaultSettings);

            cache()->delete('system_settings');

            return redirect()->back()->with('success', 'Configurações do sistema resetadas para o padrão');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao resetar: ' . $e->getMessage());
        }
    }
}
