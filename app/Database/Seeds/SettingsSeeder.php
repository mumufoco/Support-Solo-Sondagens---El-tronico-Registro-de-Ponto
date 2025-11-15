<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('settings');

        // Clear existing settings (optional - comment out if you want to preserve)
        // $builder->truncate();

        $settings = [
            // GENERAL SETTINGS
            [
                'key'         => 'company_name',
                'value'       => 'Empresa Exemplo LTDA',
                'type'        => 'string',
                'group'       => 'general',
                'description' => 'Nome da empresa',
                'editable'    => true,
            ],
            [
                'key'         => 'company_cnpj',
                'value'       => '00.000.000/0001-00',
                'type'        => 'string',
                'group'       => 'general',
                'description' => 'CNPJ da empresa',
                'editable'    => true,
            ],
            [
                'key'         => 'company_address',
                'value'       => 'Rua Exemplo, 123 - Centro - São Paulo/SP - CEP 01000-000',
                'type'        => 'string',
                'group'       => 'general',
                'description' => 'Endereço completo da empresa',
                'editable'    => true,
            ],
            [
                'key'         => 'company_phone',
                'value'       => '(11) 1234-5678',
                'type'        => 'string',
                'group'       => 'general',
                'description' => 'Telefone de contato',
                'editable'    => true,
            ],
            [
                'key'         => 'company_email',
                'value'       => 'contato@empresa.com.br',
                'type'        => 'string',
                'group'       => 'general',
                'description' => 'E-mail de contato',
                'editable'    => true,
            ],
            [
                'key'         => 'timezone',
                'value'       => 'America/Sao_Paulo',
                'type'        => 'string',
                'group'       => 'general',
                'description' => 'Fuso horário do sistema',
                'editable'    => true,
            ],

            // JORNADA SETTINGS
            [
                'key'         => 'work_schedule_start',
                'value'       => '08:00',
                'type'        => 'string',
                'group'       => 'jornada',
                'description' => 'Horário padrão de início do expediente',
                'editable'    => true,
            ],
            [
                'key'         => 'work_schedule_end',
                'value'       => '18:00',
                'type'        => 'string',
                'group'       => 'jornada',
                'description' => 'Horário padrão de fim do expediente',
                'editable'    => true,
            ],
            [
                'key'         => 'expected_hours_daily',
                'value'       => '8.00',
                'type'        => 'string',
                'group'       => 'jornada',
                'description' => 'Jornada diária padrão em horas',
                'editable'    => true,
            ],
            [
                'key'         => 'interval_required_minutes',
                'value'       => '60',
                'type'        => 'integer',
                'group'       => 'jornada',
                'description' => 'Intervalo obrigatório em minutos (para jornada > 6h)',
                'editable'    => true,
            ],
            [
                'key'         => 'tolerance_minutes',
                'value'       => '15',
                'type'        => 'integer',
                'group'       => 'jornada',
                'description' => 'Tolerância em minutos para atrasos/saídas antecipadas',
                'editable'    => true,
            ],
            [
                'key'         => 'work_days',
                'value'       => json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']),
                'type'        => 'json',
                'group'       => 'jornada',
                'description' => 'Dias úteis da semana',
                'editable'    => true,
            ],

            // GEOLOCATION SETTINGS
            [
                'key'         => 'geolocation_required',
                'value'       => '1',
                'type'        => 'boolean',
                'group'       => 'geolocation',
                'description' => 'Geolocalização obrigatória para registro de ponto',
                'editable'    => true,
            ],
            [
                'key'         => 'geofence_default_radius',
                'value'       => '100',
                'type'        => 'integer',
                'group'       => 'geolocation',
                'description' => 'Raio padrão da cerca virtual em metros',
                'editable'    => true,
            ],
            [
                'key'         => 'company_latitude',
                'value'       => '-23.5505',
                'type'        => 'string',
                'group'       => 'geolocation',
                'description' => 'Latitude da sede da empresa (São Paulo exemplo)',
                'editable'    => true,
            ],
            [
                'key'         => 'company_longitude',
                'value'       => '-46.6333',
                'type'        => 'string',
                'group'       => 'geolocation',
                'description' => 'Longitude da sede da empresa (São Paulo exemplo)',
                'editable'    => true,
            ],

            // NOTIFICATIONS SETTINGS
            [
                'key'         => 'notifications_email_enabled',
                'value'       => '1',
                'type'        => 'boolean',
                'group'       => 'notifications',
                'description' => 'Notificações por e-mail habilitadas',
                'editable'    => true,
            ],
            [
                'key'         => 'notifications_push_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'notifications',
                'description' => 'Notificações push habilitadas',
                'editable'    => true,
            ],
            [
                'key'         => 'notifications_sms_enabled',
                'value'       => '0',
                'type'        => 'boolean',
                'group'       => 'notifications',
                'description' => 'Notificações SMS habilitadas',
                'editable'    => true,
            ],
            [
                'key'         => 'punch_reminder_minutes',
                'value'       => '30',
                'type'        => 'integer',
                'group'       => 'notifications',
                'description' => 'Lembrete de ponto X minutos antes do horário',
                'editable'    => true,
            ],

            // BIOMETRIA SETTINGS
            [
                'key'         => 'deepface_api_url',
                'value'       => 'http://localhost:5000',
                'type'        => 'string',
                'group'       => 'biometria',
                'description' => 'URL da API DeepFace',
                'editable'    => true,
            ],
            [
                'key'         => 'deepface_threshold',
                'value'       => '0.40',
                'type'        => 'string',
                'group'       => 'biometria',
                'description' => 'Threshold de similaridade facial (0.30-0.70)',
                'editable'    => true,
            ],
            [
                'key'         => 'deepface_model',
                'value'       => 'VGG-Face',
                'type'        => 'string',
                'group'       => 'biometria',
                'description' => 'Modelo DeepFace (VGG-Face, Facenet, ArcFace, etc)',
                'editable'    => true,
            ],
            [
                'key'         => 'deepface_detector',
                'value'       => 'opencv',
                'type'        => 'string',
                'group'       => 'biometria',
                'description' => 'Detector de rosto (opencv, retinaface, mtcnn, etc)',
                'editable'    => true,
            ],
            [
                'key'         => 'anti_spoofing_enabled',
                'value'       => '1',
                'type'        => 'boolean',
                'group'       => 'biometria',
                'description' => 'Anti-spoofing habilitado (detectar fotos falsas)',
                'editable'    => true,
            ],

            // FILE UPLOAD SETTINGS
            [
                'key'         => 'upload_max_size',
                'value'       => '5242880',
                'type'        => 'integer',
                'group'       => 'upload',
                'description' => 'Tamanho máximo de upload em bytes (5MB)',
                'editable'    => true,
            ],
            [
                'key'         => 'upload_allowed_types',
                'value'       => 'jpg,jpeg,png,pdf',
                'type'        => 'string',
                'group'       => 'upload',
                'description' => 'Tipos de arquivo permitidos para upload',
                'editable'    => true,
            ],

            // LGPD SETTINGS
            [
                'key'         => 'lgpd_dpo_name',
                'value'       => 'Nome do DPO',
                'type'        => 'string',
                'group'       => 'lgpd',
                'description' => 'Nome do Encarregado de Dados (DPO)',
                'editable'    => true,
            ],
            [
                'key'         => 'lgpd_dpo_email',
                'value'       => 'dpo@empresa.com.br',
                'type'        => 'string',
                'group'       => 'lgpd',
                'description' => 'E-mail do DPO',
                'editable'    => true,
            ],
            [
                'key'         => 'data_retention_days',
                'value'       => '3650',
                'type'        => 'integer',
                'group'       => 'lgpd',
                'description' => 'Retenção de dados em dias (10 anos = 3650)',
                'editable'    => true,
            ],
            [
                'key'         => 'audit_retention_days',
                'value'       => '3650',
                'type'        => 'integer',
                'group'       => 'lgpd',
                'description' => 'Retenção de logs de auditoria em dias',
                'editable'    => false,
            ],

            // CACHE SETTINGS
            [
                'key'         => 'cache_enabled',
                'value'       => '1',
                'type'        => 'boolean',
                'group'       => 'cache',
                'description' => 'Sistema de cache habilitado',
                'editable'    => true,
            ],
            [
                'key'         => 'cache_ttl',
                'value'       => '3600',
                'type'        => 'integer',
                'group'       => 'cache',
                'description' => 'Time-to-live do cache em segundos (1 hora)',
                'editable'    => true,
            ],

            // SECURITY SETTINGS
            [
                'key'         => 'session_timeout',
                'value'       => '7200',
                'type'        => 'integer',
                'group'       => 'security',
                'description' => 'Timeout de sessão em segundos (2 horas)',
                'editable'    => true,
            ],
            [
                'key'         => 'brute_force_attempts',
                'value'       => '5',
                'type'        => 'integer',
                'group'       => 'security',
                'description' => 'Tentativas de login antes de bloqueio',
                'editable'    => true,
            ],
            [
                'key'         => 'brute_force_lockout_minutes',
                'value'       => '15',
                'type'        => 'integer',
                'group'       => 'security',
                'description' => 'Tempo de bloqueio em minutos',
                'editable'    => true,
            ],
            [
                'key'         => 'rate_limit_facial_per_minute',
                'value'       => '5',
                'type'        => 'integer',
                'group'       => 'security',
                'description' => 'Limite de tentativas de reconhecimento facial por minuto',
                'editable'    => true,
            ],
        ];

        // Insert settings
        $insertedCount = 0;
        foreach ($settings as $setting) {
            // Check if setting already exists
            $existing = $builder->where('key', $setting['key'])->get()->getRow();

            if (!$existing) {
                $setting['created_at'] = date('Y-m-d H:i:s');
                $setting['updated_at'] = date('Y-m-d H:i:s');
                $builder->insert($setting);
                $insertedCount++;
            }
        }

        echo "✅ Settings seeded successfully!\n";
        echo "   Total settings: " . count($settings) . "\n";
        echo "   New settings inserted: {$insertedCount}\n";
        echo "   Skipped (already exists): " . (count($settings) - $insertedCount) . "\n";
    }
}
