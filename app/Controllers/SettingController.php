<?php

namespace App\Controllers;

use App\Models\SettingModel;
use App\Models\GeofenceModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * SettingController
 *
 * Controller for system settings management
 * Implements Comando 14.1 - Administrative settings panel with 9 tabs
 */
class SettingController extends BaseController
{
    protected SettingModel $settingModel;
    protected GeofenceModel $geofenceModel;

    public function __construct()
    {
        $this->settingModel = new SettingModel();
        $this->geofenceModel = new GeofenceModel();
    }

    /**
     * Settings index page with tabs
     * GET /settings
     */
    public function index(): string
    {
        // Check admin permission
        if (!$this->hasPermission('manage_settings')) {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado');
        }

        // Get all settings grouped
        $settings = [
            'general' => $this->settingModel->getByGroup('general'),
            'workday' => $this->settingModel->getByGroup('workday'),
            'geolocation' => $this->settingModel->getByGroup('geolocation'),
            'notifications' => $this->settingModel->getByGroup('notifications'),
            'biometry' => $this->settingModel->getByGroup('biometry'),
            'apis' => $this->settingModel->getByGroup('apis'),
            'icp_brasil' => $this->settingModel->getByGroup('icp_brasil'),
            'lgpd' => $this->settingModel->getByGroup('lgpd'),
            'backup' => $this->settingModel->getByGroup('backup'),
        ];

        // Get geofences for geolocation tab
        $geofences = $this->geofenceModel->findAll();

        return view('settings/index', [
            'settings' => $settings,
            'geofences' => $geofences,
            'title' => 'Configurações do Sistema',
        ]);
    }

    /**
     * Save general settings
     * POST /settings/save-general
     */
    public function saveGeneral(): ResponseInterface
    {
        if (!$this->hasPermission('manage_settings')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso negado',
            ])->setStatusCode(403);
        }

        $validation = \Config\Services::validation();

        $validation->setRules([
            'company_name' => 'required|min_length[3]|max_length[200]',
            'company_cnpj' => 'required|exact_length[18]', // 00.000.000/0000-00
            'primary_color' => 'required|regex_match[/^#[0-9A-Fa-f]{6}$/]',
            'secondary_color' => 'required|regex_match[/^#[0-9A-Fa-f]{6}$/]',
            'timezone' => 'required',
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validação falhou',
                'errors' => $validation->getErrors(),
            ])->setStatusCode(400);
        }

        try {
            $settings = [
                'company_name' => $this->request->getPost('company_name'),
                'company_cnpj' => $this->request->getPost('company_cnpj'),
                'primary_color' => $this->request->getPost('primary_color'),
                'secondary_color' => $this->request->getPost('secondary_color'),
                'timezone' => $this->request->getPost('timezone'),
            ];

            // Handle logo upload
            $logo = $this->request->getFile('company_logo');
            if ($logo && $logo->isValid()) {
                $newName = $logo->getRandomName();
                $logo->move(WRITEPATH . 'uploads/logos/', $newName);
                $settings['company_logo'] = $newName;
            }

            // Save each setting
            foreach ($settings as $key => $value) {
                $this->settingModel->set($key, $value);
                $this->settingModel->update($this->settingModel->where('key', $key)->first()->id ?? null, [
                    'group' => 'general',
                ]);
            }

            // Clear cache
            cache()->delete('settings_general');

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Configurações gerais salvas com sucesso',
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error saving general settings: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao salvar configurações: ' . $e->getMessage(),
            ])->setStatusCode(500);
        }
    }

    /**
     * Save workday settings
     * POST /settings/save-workday
     */
    public function saveWorkday(): ResponseInterface
    {
        if (!$this->hasPermission('manage_settings')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso negado',
            ])->setStatusCode(403);
        }

        $validation = \Config\Services::validation();

        $validation->setRules([
            'workday_start' => 'required',
            'workday_end' => 'required',
            'mandatory_break_hours' => 'required|decimal',
            'late_tolerance_minutes' => 'required|integer',
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validação falhou',
                'errors' => $validation->getErrors(),
            ])->setStatusCode(400);
        }

        try {
            $settings = [
                'workday_start' => $this->request->getPost('workday_start'),
                'workday_end' => $this->request->getPost('workday_end'),
                'mandatory_break_hours' => $this->request->getPost('mandatory_break_hours'),
                'late_tolerance_minutes' => $this->request->getPost('late_tolerance_minutes'),
                'business_days' => json_encode($this->request->getPost('business_days') ?? []),
            ];

            foreach ($settings as $key => $value) {
                $this->settingModel->set($key, $value);
                $existing = $this->settingModel->where('key', $key)->first();
                if ($existing) {
                    $this->settingModel->update($existing->id, ['group' => 'workday']);
                }
            }

            cache()->delete('settings_workday');

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Configurações de jornada salvas com sucesso',
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error saving workday settings: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao salvar configurações: ' . $e->getMessage(),
            ])->setStatusCode(500);
        }
    }

    /**
     * Save geolocation settings
     * POST /settings/save-geolocation
     */
    public function saveGeolocation(): ResponseInterface
    {
        if (!$this->hasPermission('manage_settings')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso negado',
            ])->setStatusCode(403);
        }

        try {
            $settings = [
                'geolocation_enabled' => $this->request->getPost('geolocation_enabled') ? 'true' : 'false',
                'geolocation_required' => $this->request->getPost('geolocation_required') ? 'true' : 'false',
            ];

            foreach ($settings as $key => $value) {
                $this->settingModel->set($key, $value);
                $existing = $this->settingModel->where('key', $key)->first();
                if ($existing) {
                    $this->settingModel->update($existing->id, ['group' => 'geolocation']);
                }
            }

            cache()->delete('settings_geolocation');

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Configurações de geolocalização salvas com sucesso',
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao salvar configurações: ' . $e->getMessage(),
            ])->setStatusCode(500);
        }
    }

    /**
     * Save notification settings
     * POST /settings/save-notifications
     */
    public function saveNotifications(): ResponseInterface
    {
        if (!$this->hasPermission('manage_settings')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso negado',
            ])->setStatusCode(403);
        }

        try {
            $settings = [
                'notifications_email_enabled' => $this->request->getPost('notifications_email_enabled') ? 'true' : 'false',
                'notifications_push_enabled' => $this->request->getPost('notifications_push_enabled') ? 'true' : 'false',
                'notifications_sms_enabled' => $this->request->getPost('notifications_sms_enabled') ? 'true' : 'false',
                'punch_reminder_minutes' => $this->request->getPost('punch_reminder_minutes'),
                'email_template_welcome' => $this->request->getPost('email_template_welcome'),
                'email_template_punch_reminder' => $this->request->getPost('email_template_punch_reminder'),
                'email_template_justification' => $this->request->getPost('email_template_justification'),
            ];

            foreach ($settings as $key => $value) {
                $this->settingModel->set($key, $value);
                $existing = $this->settingModel->where('key', $key)->first();
                if ($existing) {
                    $this->settingModel->update($existing->id, ['group' => 'notifications']);
                }
            }

            cache()->delete('settings_notifications');

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Configurações de notificações salvas com sucesso',
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao salvar configurações: ' . $e->getMessage(),
            ])->setStatusCode(500);
        }
    }

    /**
     * Save biometry settings
     * POST /settings/save-biometry
     */
    public function saveBiometry(): ResponseInterface
    {
        if (!$this->hasPermission('manage_settings')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso negado',
            ])->setStatusCode(403);
        }

        $validation = \Config\Services::validation();

        $validation->setRules([
            'deepface_api_url' => 'required|valid_url',
            'deepface_threshold' => 'required|decimal|greater_than_equal_to[0.30]|less_than_equal_to[0.70]',
            'deepface_model' => 'required|in_list[VGG-Face,Facenet,Facenet512,OpenFace,DeepFace,DeepID,ArcFace,Dlib,SFace]',
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validação falhou',
                'errors' => $validation->getErrors(),
            ])->setStatusCode(400);
        }

        try {
            $settings = [
                'deepface_api_url' => $this->request->getPost('deepface_api_url'),
                'deepface_threshold' => $this->request->getPost('deepface_threshold'),
                'deepface_model' => $this->request->getPost('deepface_model'),
                'deepface_anti_spoofing' => $this->request->getPost('deepface_anti_spoofing') ? 'true' : 'false',
            ];

            foreach ($settings as $key => $value) {
                $this->settingModel->set($key, $value);
                $existing = $this->settingModel->where('key', $key)->first();
                if ($existing) {
                    $this->settingModel->update($existing->id, ['group' => 'biometry']);
                }
            }

            cache()->delete('settings_biometry');

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Configurações de biometria salvas com sucesso',
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao salvar configurações: ' . $e->getMessage(),
            ])->setStatusCode(500);
        }
    }

    /**
     * Save APIs settings
     * POST /settings/save-apis
     */
    public function saveAPIs(): ResponseInterface
    {
        if (!$this->hasPermission('manage_settings')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso negado',
            ])->setStatusCode(403);
        }

        try {
            $settings = [
                'nominatim_endpoint' => $this->request->getPost('nominatim_endpoint'),
                'api_rate_limit' => $this->request->getPost('api_rate_limit'),
                'api_cache_ttl' => $this->request->getPost('api_cache_ttl'),
            ];

            foreach ($settings as $key => $value) {
                $this->settingModel->set($key, $value);
                $existing = $this->settingModel->where('key', $key)->first();
                if ($existing) {
                    $this->settingModel->update($existing->id, ['group' => 'apis']);
                }
            }

            cache()->delete('settings_apis');

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Configurações de APIs salvas com sucesso',
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao salvar configurações: ' . $e->getMessage(),
            ])->setStatusCode(500);
        }
    }

    /**
     * Save ICP-Brasil settings
     * POST /settings/save-icp-brasil
     */
    public function saveICPBrasil(): ResponseInterface
    {
        if (!$this->hasPermission('manage_settings')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso negado',
            ])->setStatusCode(403);
        }

        try {
            $settings = [];

            // Handle certificate upload
            $certificate = $this->request->getFile('icp_certificate');
            if ($certificate && $certificate->isValid()) {
                $newName = 'certificate_' . time() . '.pfx';
                $certificate->move(WRITEPATH . 'certificates/', $newName);
                $settings['icp_certificate_path'] = $newName;

                // Get certificate info
                $certPath = WRITEPATH . 'certificates/' . $newName;
                $certPassword = $this->request->getPost('icp_certificate_password');

                // Try to read certificate
                $certs = [];
                if (openssl_pkcs12_read(file_get_contents($certPath), $certs, $certPassword)) {
                    $certData = openssl_x509_parse($certs['cert']);
                    $settings['icp_certificate_valid_until'] = date('Y-m-d H:i:s', $certData['validTo_time_t']);
                }
            }

            // Encrypt password
            if ($this->request->getPost('icp_certificate_password')) {
                $encrypter = \Config\Services::encrypter();
                $settings['icp_certificate_password'] = base64_encode($encrypter->encrypt($this->request->getPost('icp_certificate_password')));
            }

            foreach ($settings as $key => $value) {
                $this->settingModel->set($key, $value, $key === 'icp_certificate_password' ? 'encrypted' : 'string');
                $existing = $this->settingModel->where('key', $key)->first();
                if ($existing) {
                    $this->settingModel->update($existing->id, ['group' => 'icp_brasil']);
                }
            }

            cache()->delete('settings_icp_brasil');

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Configurações de ICP-Brasil salvas com sucesso',
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error saving ICP-Brasil settings: ' . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao salvar configurações: ' . $e->getMessage(),
            ])->setStatusCode(500);
        }
    }

    /**
     * Test ICP-Brasil certificate
     * POST /settings/test-icp-certificate
     */
    public function testICPCertificate(): ResponseInterface
    {
        if (!$this->hasPermission('manage_settings')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso negado',
            ])->setStatusCode(403);
        }

        try {
            $certPath = $this->settingModel->get('icp_certificate_path');
            $certPassword = $this->settingModel->get('icp_certificate_password');

            if (!$certPath) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Nenhum certificado configurado',
                ]);
            }

            $fullPath = WRITEPATH . 'certificates/' . $certPath;

            if (!file_exists($fullPath)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Arquivo de certificado não encontrado',
                ]);
            }

            // Decrypt password
            $encrypter = \Config\Services::encrypter();
            $decryptedPassword = $encrypter->decrypt(base64_decode($certPassword));

            // Try to read certificate
            $certs = [];
            if (!openssl_pkcs12_read(file_get_contents($fullPath), $certs, $decryptedPassword)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Falha ao ler certificado. Senha incorreta?',
                ]);
            }

            $certData = openssl_x509_parse($certs['cert']);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Certificado válido',
                'data' => [
                    'subject' => $certData['subject']['CN'] ?? 'N/A',
                    'issuer' => $certData['issuer']['CN'] ?? 'N/A',
                    'valid_from' => date('d/m/Y', $certData['validFrom_time_t']),
                    'valid_to' => date('d/m/Y', $certData['validTo_time_t']),
                    'days_remaining' => max(0, floor(($certData['validTo_time_t'] - time()) / 86400)),
                ],
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao testar certificado: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Save LGPD settings
     * POST /settings/save-lgpd
     */
    public function saveLGPD(): ResponseInterface
    {
        if (!$this->hasPermission('manage_settings')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso negado',
            ])->setStatusCode(403);
        }

        $validation = \Config\Services::validation();

        $validation->setRules([
            'lgpd_dpo_name' => 'required|min_length[3]',
            'lgpd_dpo_email' => 'required|valid_email',
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validação falhou',
                'errors' => $validation->getErrors(),
            ])->setStatusCode(400);
        }

        try {
            $settings = [
                'lgpd_dpo_name' => $this->request->getPost('lgpd_dpo_name'),
                'lgpd_dpo_email' => $this->request->getPost('lgpd_dpo_email'),
                'lgpd_retention_attendance' => $this->request->getPost('lgpd_retention_attendance'),
                'lgpd_retention_biometric' => $this->request->getPost('lgpd_retention_biometric'),
                'lgpd_retention_audit' => $this->request->getPost('lgpd_retention_audit'),
                'lgpd_retention_consents' => $this->request->getPost('lgpd_retention_consents'),
            ];

            foreach ($settings as $key => $value) {
                $this->settingModel->set($key, $value);
                $existing = $this->settingModel->where('key', $key)->first();
                if ($existing) {
                    $this->settingModel->update($existing->id, ['group' => 'lgpd']);
                }
            }

            cache()->delete('settings_lgpd');

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Configurações de LGPD salvas com sucesso',
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao salvar configurações: ' . $e->getMessage(),
            ])->setStatusCode(500);
        }
    }

    /**
     * Save backup settings
     * POST /settings/save-backup
     */
    public function saveBackup(): ResponseInterface
    {
        if (!$this->hasPermission('manage_settings')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso negado',
            ])->setStatusCode(403);
        }

        try {
            $encrypter = \Config\Services::encrypter();

            $settings = [
                'backup_type' => $this->request->getPost('backup_type'), // s3 or ftp
                'backup_schedule' => $this->request->getPost('backup_schedule'), // daily or weekly
                'backup_retention_days' => $this->request->getPost('backup_retention_days'),
            ];

            // S3 settings
            if ($this->request->getPost('backup_type') === 's3') {
                $settings['backup_s3_access_key'] = base64_encode($encrypter->encrypt($this->request->getPost('backup_s3_access_key')));
                $settings['backup_s3_secret_key'] = base64_encode($encrypter->encrypt($this->request->getPost('backup_s3_secret_key')));
                $settings['backup_s3_bucket'] = $this->request->getPost('backup_s3_bucket');
                $settings['backup_s3_region'] = $this->request->getPost('backup_s3_region');
            }

            // FTP settings
            if ($this->request->getPost('backup_type') === 'ftp') {
                $settings['backup_ftp_host'] = $this->request->getPost('backup_ftp_host');
                $settings['backup_ftp_user'] = $this->request->getPost('backup_ftp_user');
                $settings['backup_ftp_password'] = base64_encode($encrypter->encrypt($this->request->getPost('backup_ftp_password')));
                $settings['backup_ftp_path'] = $this->request->getPost('backup_ftp_path');
            }

            foreach ($settings as $key => $value) {
                $type = (strpos($key, 'password') !== false || strpos($key, 'secret') !== false || strpos($key, 'access_key') !== false) ? 'encrypted' : 'string';
                $this->settingModel->set($key, $value, $type);
                $existing = $this->settingModel->where('key', $key)->first();
                if ($existing) {
                    $this->settingModel->update($existing->id, ['group' => 'backup']);
                }
            }

            cache()->delete('settings_backup');

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Configurações de backup salvas com sucesso',
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Erro ao salvar configurações: ' . $e->getMessage(),
            ])->setStatusCode(500);
        }
    }

    /**
     * Check user permission
     */
    protected function hasPermission(string $permission): bool
    {
        $session = session();
        $role = $session->get('role');

        $permissions = [
            'admin' => ['manage_settings'],
            'manager' => [],
        ];

        return isset($permissions[$role]) && in_array($permission, $permissions[$role]);
    }
}
