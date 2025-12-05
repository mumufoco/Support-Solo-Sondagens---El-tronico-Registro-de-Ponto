# Guia de Configuração do Sistema

## Instalação Inicial

### 1. Executar Migração do Banco de Dados

O sistema de configurações requer uma tabela no banco de dados. Execute a migration:

```bash
php spark migrate
```

Ou execute manualmente o SQL:

```bash
php spark migrate:latest
```

A migration criará a tabela `system_settings` com as configurações padrão.

### 2. Verificar Permissões

Certifique-se que os diretórios de upload têm permissões de escrita:

```bash
chmod -R 775 public/assets/uploads/
```

### 3. Configurar Chave de Criptografia

No arquivo `.env`, certifique-se que a chave de encryption está configurada:

```env
encryption.key = SUA_CHAVE_AQUI_32_CARACTERES
```

Para gerar uma nova chave:

```bash
php spark key:generate
```

## Usando o Sistema de Configurações

### Acessar Painel de Configurações

Como administrador, acesse:

```
https://seudominio.com/admin/settings
```

### Seções Disponíveis

#### 1. **Aparência** (`/admin/settings/appearance`)
- Nome da empresa
- Upload de logo (PNG/JPG/SVG, máx 2MB)
- Upload de favicon
- Cores do sistema (primária, secundária, sucesso, aviso, perigo, info)
- Seleção de fonte
- Modo de tema (claro/escuro/automático)
- Imagem de fundo da tela de login

#### 2. **Autenticação** (Em desenvolvimento)
- Timeout de sessão
- Autenticação de dois fatores (2FA)
- Máximo de tentativas de login
- Políticas de redefinição de senha

#### 3. **Certificado Digital** (Em desenvolvimento)
- Upload de certificado A1
- Configuração de certificado A3
- Validade e renovação

#### 4. **Sistema** (Em desenvolvimento)
- CNPJ da empresa
- Fuso horário
- Idioma
- Integrações externas

#### 5. **Segurança** (Em desenvolvimento)
- Políticas de senha
- Logs de auditoria
- Backup automático
- Permissões e roles

## API de Configurações (Para Desenvolvedores)

### Obter uma Configuração

```php
$settingModel = new \App\Models\SystemSettingModel();
$value = $settingModel->get('primary_color', '#3B82F6'); // com default
```

### Definir uma Configuração

```php
$settingModel = new \App\Models\SystemSettingModel();
$settingModel->set('company_name', 'Minha Empresa', 'string', 'appearance');
```

### Obter Todas as Configurações de um Grupo

```php
$settingModel = new \App\Models\SystemSettingModel();
$appearanceSettings = $settingModel->getByGroup('appearance');
```

### Definir Múltiplas Configurações

```php
$settings = [
    'primary_color' => '#FF5733',
    'secondary_color' => '#33FF57',
    'company_name' => 'Nova Empresa'
];

$settingModel->setMultiple($settings, 'appearance');
```

### Configurações Criptografadas

Configurações sensíveis (senhas, secrets) são automaticamente criptografadas:

```php
// Será criptografado automaticamente se o nome contiver 'password' ou 'secret'
$settingModel->set('api_password', 'senha123', 'string', 'system', true);

// Recuperar (descriptografa automaticamente)
$password = $settingModel->get('api_password');
```

## Design System

O Design System é atualizado automaticamente quando as configurações de aparência são modificadas.

### Usar Design System em Views

```php
<?php
$designSystem = new \App\Libraries\DesignSystem();
?>

<style>
    <?= $designSystem->generateCSS() ?>
</style>
```

O layout `layouts/modern.php` já inclui o Design System automaticamente.

### Atualizar Cores Programaticamente

```php
$designSystem = new \App\Libraries\DesignSystem();

$designSystem->updateColors([
    'primary' => '#FF5733',
    'secondary' => '#33FF57'
]);
```

### Limpar Cache

Após modificar configurações, limpe o cache:

```php
cache()->delete('system_settings');
cache()->delete('design_system_css');
```

Ou via interface em `/admin/settings` > "Limpar Cache"

## Backup e Restauração

### Exportar Configurações

1. Acesse `/admin/settings`
2. Clique em "Exportar Configurações"
3. Salve o arquivo JSON

Ou via código:

```php
$controller = new \App\Controllers\Admin\SettingsController();
return $controller->export(); // Retorna JSON
```

### Importar Configurações

1. Acesse `/admin/settings`
2. Clique em "Importar Configurações"
3. Selecione o arquivo JSON

O sistema importará todas as configurações não-sensíveis do arquivo.

## Resetar Configurações

### Resetar Um Grupo

```php
$settingModel = new \App\Models\SystemSettingModel();
$settingModel->where('setting_group', 'appearance')->delete();
```

### Resetar Todas (CUIDADO!)

```php
$settingModel = new \App\Models\SystemSettingModel();
$settingModel->truncate();

// Re-executar migration para restaurar defaults
php spark migrate:refresh
```

## Solução de Problemas

### Erro: "Table 'system_settings' doesn't exist"

Execute a migration:

```bash
php spark migrate
```

### Erro: "Permission denied" ao fazer upload

Verifique permissões dos diretórios:

```bash
chmod -R 775 public/assets/uploads/
```

### Erro: "Encryption key not found"

Configure a chave no `.env`:

```bash
php spark key:generate
```

### Cache não está limpando

Limpe manualmente:

```bash
rm -rf writable/cache/*
```

## Segurança

### Proteção de Upload

- Apenas PNG, JPG, SVG permitidos para logos
- Máximo 2MB por arquivo
- Validação de MIME type
- Arquivos salvos com nomes únicos (timestamp)

### Configurações Sensíveis

- Senhas e secrets são criptografados no banco
- Chave de encryption deve ser segura (32 caracteres)
- Não versionar `.env` no git

### Acesso Administrativo

- Apenas usuários com role 'admin' podem acessar configurações
- Filtros `auth` e `admin` protegem todas as rotas
- Log de auditoria para alterações (em desenvolvimento)

## Próximos Passos

- [ ] Implementar seções de Autenticação, Certificado, Sistema e Segurança
- [ ] Adicionar extração automática de cores do logo
- [ ] Implementar preview ao vivo de mudanças
- [ ] Adicionar histórico de alterações
- [ ] Implementar versionamento de configurações
- [ ] Adicionar API REST para configurações

---

**Última atualização:** 2025-12-05
**Versão:** 1.0
