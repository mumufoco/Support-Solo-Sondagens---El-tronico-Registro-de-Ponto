<?php

/**
 * Design System Configuration
 *
 * Centraliza todas as configurações de tema, cores, tipografia e estilos
 * Permite customização completa via painel de configurações
 */

namespace App\Libraries;

class DesignSystem
{
    private $config = [];

    public function __construct()
    {
        // Carregar configurações salvas ou usar padrões
        $this->loadConfig();
    }

    /**
     * Carrega configurações do banco ou arquivo
     */
    private function loadConfig()
    {
        $db = \Config\Database::connect();

        // Tentar carregar do banco
        try {
            if ($db->tableExists('system_settings')) {
                $query = $db->table('system_settings')
                    ->where('setting_key', 'design_system')
                    ->get();

                if ($row = $query->getRow()) {
                    $this->config = json_decode($row->setting_value, true);
                    return;
                }
            }
        } catch (\Exception $e) {
            log_message('warning', 'Could not load design config from database: ' . $e->getMessage());
        }

        // Usar configurações padrão
        $this->config = $this->getDefaultConfig();
    }

    /**
     * Configurações padrão do sistema
     */
    private function getDefaultConfig(): array
    {
        return [
            // Cores principais
            'colors' => [
                'primary' => '#3B82F6',      // Azul moderno
                'secondary' => '#8B5CF6',    // Roxo
                'success' => '#10B981',      // Verde
                'danger' => '#EF4444',       // Vermelho
                'warning' => '#F59E0B',      // Laranja
                'info' => '#06B6D4',         // Ciano
                'dark' => '#1F2937',         // Cinza escuro
                'light' => '#F3F4F6',        // Cinza claro
            ],

            // Cores do tema claro
            'light_theme' => [
                'background' => '#FFFFFF',
                'surface' => '#F9FAFB',
                'text_primary' => '#111827',
                'text_secondary' => '#6B7280',
                'border' => '#E5E7EB',
            ],

            // Cores do tema escuro
            'dark_theme' => [
                'background' => '#111827',
                'surface' => '#1F2937',
                'text_primary' => '#F9FAFB',
                'text_secondary' => '#9CA3AF',
                'border' => '#374151',
            ],

            // Tipografia
            'typography' => [
                'font_family' => "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
                'font_size_base' => '16px',
                'font_size_sm' => '14px',
                'font_size_lg' => '18px',
                'font_size_xl' => '20px',
                'line_height' => '1.5',
            ],

            // Espaçamento
            'spacing' => [
                'xs' => '4px',
                'sm' => '8px',
                'md' => '16px',
                'lg' => '24px',
                'xl' => '32px',
                'xxl' => '48px',
            ],

            // Bordas
            'borders' => [
                'radius_sm' => '4px',
                'radius_md' => '8px',
                'radius_lg' => '12px',
                'radius_xl' => '16px',
                'radius_full' => '9999px',
            ],

            // Sombras
            'shadows' => [
                'sm' => '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                'md' => '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
                'lg' => '0 10px 15px -3px rgba(0, 0, 0, 0.1)',
                'xl' => '0 20px 25px -5px rgba(0, 0, 0, 0.1)',
            ],

            // Layout
            'layout' => [
                'sidebar_width' => '280px',
                'sidebar_collapsed_width' => '80px',
                'header_height' => '64px',
                'content_max_width' => '1400px',
            ],

            // Customizações
            'custom' => [
                'logo' => null,
                'favicon' => null,
                'login_background' => null,
                'company_name' => 'Sistema de Ponto Eletrônico',
                'theme_mode' => 'light', // 'light', 'dark', 'auto'
            ],
        ];
    }

    /**
     * Retorna todas as configurações
     */
    public function getAll(): array
    {
        return $this->config;
    }

    /**
     * Retorna uma configuração específica
     */
    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Atualiza configurações
     */
    public function update(array $newConfig): bool
    {
        $this->config = array_merge($this->config, $newConfig);
        return $this->save();
    }

    /**
     * Salva configurações no banco
     */
    private function save(): bool
    {
        try {
            $db = \Config\Database::connect();

            // Criar tabela se não existir
            if (!$db->tableExists('system_settings')) {
                $this->createSettingsTable();
            }

            // Salvar ou atualizar
            $data = [
                'setting_key' => 'design_system',
                'setting_value' => json_encode($this->config),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $exists = $db->table('system_settings')
                ->where('setting_key', 'design_system')
                ->countAllResults() > 0;

            if ($exists) {
                $db->table('system_settings')
                    ->where('setting_key', 'design_system')
                    ->update($data);
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $db->table('system_settings')->insert($data);
            }

            return true;
        } catch (\Exception $e) {
            log_message('error', 'Could not save design config: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cria tabela de configurações do sistema
     */
    private function createSettingsTable()
    {
        $db = \Config\Database::connect();
        $forge = \Config\Database::forge();

        $fields = [
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'setting_key' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'unique' => true,
            ],
            'setting_value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ];

        $forge->addField($fields);
        $forge->addKey('id', true);
        $forge->addKey('setting_key');
        $forge->createTable('system_settings', true);

        log_message('info', 'Created system_settings table');
    }

    /**
     * Gera CSS customizado baseado nas configurações
     */
    public function generateCSS(): string
    {
        $colors = $this->get('colors', []);
        $typography = $this->get('typography', []);
        $spacing = $this->get('spacing', []);
        $borders = $this->get('borders', []);
        $shadows = $this->get('shadows', []);
        $layout = $this->get('layout', []);
        $lightTheme = $this->get('light_theme', []);
        $darkTheme = $this->get('dark_theme', []);

        $css = ":root {\n";

        // Cores
        foreach ($colors as $key => $value) {
            $css .= "  --color-{$key}: {$value};\n";
        }

        // Tema claro
        foreach ($lightTheme as $key => $value) {
            $css .= "  --light-{$key}: {$value};\n";
        }

        // Tema escuro
        foreach ($darkTheme as $key => $value) {
            $css .= "  --dark-{$key}: {$value};\n";
        }

        // Tipografia
        foreach ($typography as $key => $value) {
            $css .= "  --{$key}: {$value};\n";
        }

        // Espaçamento
        foreach ($spacing as $key => $value) {
            $css .= "  --spacing-{$key}: {$value};\n";
        }

        // Bordas
        foreach ($borders as $key => $value) {
            $css .= "  --{$key}: {$value};\n";
        }

        // Sombras
        foreach ($shadows as $key => $value) {
            $css .= "  --shadow-{$key}: {$value};\n";
        }

        // Layout
        foreach ($layout as $key => $value) {
            $css .= "  --{$key}: {$value};\n";
        }

        $css .= "}\n\n";

        // Tema claro (padrão)
        $css .= "[data-theme='light'] {\n";
        $css .= "  --background: var(--light-background);\n";
        $css .= "  --surface: var(--light-surface);\n";
        $css .= "  --text-primary: var(--light-text_primary);\n";
        $css .= "  --text-secondary: var(--light-text_secondary);\n";
        $css .= "  --border: var(--light-border);\n";
        $css .= "}\n\n";

        // Tema escuro
        $css .= "[data-theme='dark'] {\n";
        $css .= "  --background: var(--dark-background);\n";
        $css .= "  --surface: var(--dark-surface);\n";
        $css .= "  --text-primary: var(--dark-text_primary);\n";
        $css .= "  --text-secondary: var(--dark-text_secondary);\n";
        $css .= "  --border: var(--dark-border);\n";
        $css .= "}\n";

        return $css;
    }
}
