<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SystemSettingModel;
use App\Libraries\DesignSystem;

/**
 * Appearance Settings Controller
 *
 * Manages visual customization: colors, logos, fonts, themes
 */
class AppearanceController extends BaseController
{
    protected $settingModel;
    protected $designSystem;

    public function __construct()
    {
        $this->settingModel = new SystemSettingModel();
        $this->designSystem = new DesignSystem();
    }

    /**
     * Appearance settings page
     */
    public function index()
    {
        $settings = $this->settingModel->getByGroup('appearance');

        $data = [
            'title' => 'Configurações de Aparência',
            'breadcrumbs' => [
                ['label' => 'Configurações', 'url' => 'admin/settings'],
                ['label' => 'Aparência', 'url' => '']
            ],
            'settings' => $settings,
            'currentConfig' => $this->designSystem->getConfig()
        ];

        return view('admin/settings/appearance', $data);
    }

    /**
     * Update appearance settings
     */
    public function update()
    {
        if (!$this->request->is('post')) {
            return redirect()->back()->with('error', 'Método inválido');
        }

        $validation = \Config\Services::validation();

        $rules = [
            'company_name' => 'required|max_length[100]',
            'primary_color' => 'permit_empty|regex_match[/^#[0-9A-Fa-f]{6}$/]',
            'secondary_color' => 'permit_empty|regex_match[/^#[0-9A-Fa-f]{6}$/]',
            'font_family' => 'permit_empty|max_length[100]',
            'theme_mode' => 'required|in_list[light,dark,auto]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $validation->getErrors());
        }

        try {
            // Get all form data
            $data = $this->request->getPost();

            // Handle file uploads
            $this->handleFileUploads($data);

            // Update settings
            $this->settingModel->setMultiple($data, 'appearance');

            // Update Design System
            $this->designSystem->updateColors([
                'primary' => $data['primary_color'] ?? null,
                'secondary' => $data['secondary_color'] ?? null,
                'success' => $data['success_color'] ?? null,
                'warning' => $data['warning_color'] ?? null,
                'danger' => $data['danger_color'] ?? null,
                'info' => $data['info_color'] ?? null,
            ]);

            if (isset($data['font_family'])) {
                $this->designSystem->updateTypography(['font_family' => $data['font_family']]);
            }

            $this->designSystem->updateCustom([
                'company_name' => $data['company_name'] ?? null,
                'theme_mode' => $data['theme_mode'] ?? 'light',
            ]);

            // Clear cache
            cache()->delete('design_system_css');

            return redirect()->back()->with('success', 'Configurações de aparência atualizadas com sucesso');

        } catch (\Exception $e) {
            log_message('error', 'Error updating appearance settings: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao atualizar configurações: ' . $e->getMessage());
        }
    }

    /**
     * Upload logo
     */
    public function uploadLogo()
    {
        if (!$this->request->is('post')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Método inválido'
            ]);
        }

        $file = $this->request->getFile('logo');

        if (!$file || !$file->isValid()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Arquivo inválido'
            ]);
        }

        // Validate file type
        $validTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/svg+xml'];
        if (!in_array($file->getMimeType(), $validTypes)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tipo de arquivo não permitido. Use PNG, JPG ou SVG.'
            ]);
        }

        // Validate file size (max 2MB)
        if ($file->getSize() > 2097152) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Arquivo muito grande. Máximo 2MB.'
            ]);
        }

        try {
            // Generate unique filename
            $newName = 'logo_' . time() . '.' . $file->getExtension();

            // Move file
            $file->move(WRITEPATH . '../public/assets/uploads/logos', $newName);

            // Save to settings
            $logoPath = 'assets/uploads/logos/' . $newName;
            $this->settingModel->set('logo_path', $logoPath, 'file', 'appearance');

            // Update design system
            $this->designSystem->updateCustom(['logo' => base_url($logoPath)]);

            // Try to extract colors from logo
            $colors = $this->extractColorsFromImage(WRITEPATH . '../public/' . $logoPath);
            if ($colors) {
                $this->designSystem->updateColors($colors);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Logo enviado com sucesso',
                'url' => base_url($logoPath),
                'colors' => $colors
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao enviar logo: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Upload favicon
     */
    public function uploadFavicon()
    {
        if (!$this->request->is('post')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Método inválido'
            ]);
        }

        $file = $this->request->getFile('favicon');

        if (!$file || !$file->isValid()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Arquivo inválido'
            ]);
        }

        try {
            $newName = 'favicon_' . time() . '.' . $file->getExtension();
            $file->move(WRITEPATH . '../public/assets/uploads/favicons', $newName);

            $faviconPath = 'assets/uploads/favicons/' . $newName;
            $this->settingModel->set('favicon_path', $faviconPath, 'file', 'appearance');

            $this->designSystem->updateCustom(['favicon' => base_url($faviconPath)]);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Favicon enviado com sucesso',
                'url' => base_url($faviconPath)
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao enviar favicon: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Upload login background
     */
    public function uploadLoginBackground()
    {
        if (!$this->request->is('post')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Método inválido'
            ]);
        }

        $file = $this->request->getFile('login_background');

        if (!$file || !$file->isValid()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Arquivo inválido'
            ]);
        }

        try {
            $newName = 'login_bg_' . time() . '.' . $file->getExtension();
            $file->move(WRITEPATH . '../public/assets/uploads/backgrounds', $newName);

            $bgPath = 'assets/uploads/backgrounds/' . $newName;
            $this->settingModel->set('login_background_path', $bgPath, 'file', 'appearance');

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Fundo de login enviado com sucesso',
                'url' => base_url($bgPath)
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao enviar fundo: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Reset appearance to defaults
     */
    public function reset()
    {
        if (!$this->request->is('post')) {
            return redirect()->back()->with('error', 'Método inválido');
        }

        try {
            // Delete appearance settings
            $this->settingModel->where('setting_group', 'appearance')->delete();

            // Reset design system
            $this->designSystem->resetToDefaults();

            // Clear cache
            cache()->delete('design_system_css');

            return redirect()->back()->with('success', 'Aparência resetada para o padrão');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erro ao resetar: ' . $e->getMessage());
        }
    }

    /**
     * Preview theme
     */
    public function preview()
    {
        $colors = $this->request->getGet();

        // Generate temporary CSS with preview colors
        $tempDesign = new DesignSystem();
        if (!empty($colors)) {
            $tempDesign->updateColors($colors);
        }

        return $this->response->setJSON([
            'success' => true,
            'css' => $tempDesign->generateCSS()
        ]);
    }

    /**
     * Handle file uploads
     */
    protected function handleFileUploads(array &$data): void
    {
        $fileFields = ['logo', 'favicon', 'login_background'];

        foreach ($fileFields as $field) {
            $file = $this->request->getFile($field);

            if ($file && $file->isValid() && !$file->hasMoved()) {
                $newName = $field . '_' . time() . '.' . $file->getExtension();
                $uploadPath = WRITEPATH . '../public/assets/uploads/' . $field . 's';

                // Create directory if it doesn't exist
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                $file->move($uploadPath, $newName);
                $data[$field . '_path'] = 'assets/uploads/' . $field . 's/' . $newName;
            }
        }
    }

    /**
     * Extract dominant colors from image
     */
    protected function extractColorsFromImage(string $imagePath): ?array
    {
        try {
            // This is a simplified version - you could use a library like ColorThief
            // For now, just return null and let user manually set colors
            return null;

            // TODO: Implement actual color extraction
            // Could use: https://github.com/ksubileau/color-thief-php

        } catch (\Exception $e) {
            log_message('error', 'Error extracting colors: ' . $e->getMessage());
            return null;
        }
    }
}
