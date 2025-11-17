# 🚀 Guia Rápido de Inicialização

## Sistema de Ponto Eletrônico - Versão Corrigida

Este guia contém os passos necessários para inicializar o sistema após as correções aplicadas.

---

## ⚡ Início Rápido (3 Passos)

### 1. Instale as Dependências

```bash
# Instalar PHP 8.1+ (se necessário)
# Ubuntu/Debian:
sudo apt update
sudo apt install php8.1 php8.1-cli php8.1-pgsql php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd php8.1-intl composer

# Verificar instalação
php --version
composer --version
```

### 2. Configure a Senha do Banco

Edite o arquivo `.env` e adicione a senha do PostgreSQL do Supabase:

```bash
# Linha 30 do arquivo .env
database.default.password = SUA_SENHA_SUPABASE_AQUI
```

**Como obter a senha:**
1. Acesse https://supabase.com/dashboard
2. Selecione seu projeto
3. Vá em Settings → Database
4. Copie a senha em "Database Password"

### 3. Execute o Script de Inicialização

```bash
./init-project.sh
```

---

## 📋 Setup Completo Passo-a-Passo

### Passo 1: Instalar Dependências do Composer

```bash
composer install
```

Isso irá instalar:
- CodeIgniter 4.4+
- PHPSpreadsheet (Excel)
- TCPDF (PDF)
- QRCode Generator
- JWT
- Web Push
- Guzzle
- Workerman (WebSocket)

### Passo 2: Criar Estrutura do Banco de Dados

```bash
# Executar migrations (criar tabelas)
php spark migrate

# Verificar status
php spark migrate:status
```

**Tabelas criadas:**
- employees (funcionários)
- time_punches (registros de ponto)
- biometric_templates (biometria)
- justifications (justificativas)
- geofences (cercas virtuais)
- warnings (advertências)
- audit_logs (auditoria)
- notifications (notificações)
- settings (configurações)
- chat_* (sistema de chat)

### Passo 3: Popular Dados Iniciais

```bash
# Criar usuário administrador padrão
php spark db:seed AdminUserSeeder

# Criar configurações padrão
php spark db:seed SettingsSeeder
```

**Usuário Admin Criado:**
- Email: `admin@ponto.com.br`
- Senha: `Admin@123`
- Role: `admin`

⚠️ **IMPORTANTE:** Altere esta senha no primeiro login!

### Passo 4: Iniciar o Servidor

```bash
php spark serve --port=8080
```

### Passo 5: Acessar o Sistema

Abra seu navegador em:
```
http://localhost:8080
```

---

## 🔑 Credenciais Padrão

### Login Administrativo
```
Email: admin@ponto.com.br
Senha: Admin@123
```

### Configurações do Sistema

Todas as configurações estão no arquivo `.env`:

| Configuração | Valor Atual | Descrição |
|--------------|-------------|-----------|
| CI_ENVIRONMENT | development | Ambiente de execução |
| app.baseURL | http://localhost:8080/ | URL base |
| app.appTimezone | America/Sao_Paulo | Fuso horário |
| database.default.DBDriver | Postgre | Driver PostgreSQL |
| database.default.hostname | aws-0-us-west-1.pooler.supabase.com | Host Supabase |
| database.default.port | 6543 | Porta Supabase |

---

## 🛠️ Comandos Úteis

### Migrations

```bash
# Executar todas as migrations pendentes
php spark migrate

# Reverter última migration
php spark migrate:rollback

# Resetar banco (CUIDADO: apaga tudo!)
php spark migrate:refresh

# Ver status das migrations
php spark migrate:status
```

### Seeds

```bash
# Executar um seeder específico
php spark db:seed NomeDoSeeder

# Executar todos os seeders
php spark db:seed DatabaseSeeder
```

### Cache

```bash
# Limpar cache
php spark cache:clear

# Limpar cache e configurações
php spark cache:clear && php spark config:clear
```

### Rotas

```bash
# Listar todas as rotas
php spark routes
```

---

## 📁 Estrutura de Diretórios

```
project/
├── app/
│   ├── Controllers/      # Controladores
│   ├── Models/          # Modelos de dados
│   ├── Views/           # Views (HTML)
│   ├── Config/          # Configurações
│   ├── Database/        # Migrations e Seeds
│   ├── Filters/         # Filtros (Auth, CORS, etc)
│   ├── Services/        # Serviços de negócio
│   └── Helpers/         # Funções auxiliares
├── public/              # Arquivos públicos (CSS, JS, imagens)
├── writable/            # Logs, cache, sessões, uploads
├── storage/             # Backups, relatórios, QR codes
├── tests/               # Testes automatizados
├── .env                 # Configurações (NÃO COMMITAR)
└── composer.json        # Dependências PHP
```

---

## 🔍 Verificação de Saúde do Sistema

Execute estes comandos para verificar se tudo está funcionando:

```bash
# 1. Verificar PHP
php --version
# Esperado: PHP 8.1.x ou superior

# 2. Verificar extensões PHP necessárias
php -m | grep -E "pdo_pgsql|pgsql|mbstring|intl|curl|gd|zip"
# Esperado: todas listadas

# 3. Verificar composer
composer --version
# Esperado: Composer version 2.x

# 4. Verificar dependências instaladas
ls vendor/codeigniter4/framework
# Esperado: diretório existe

# 5. Testar conexão com banco
php spark db:table employees
# Esperado: estrutura da tabela

# 6. Verificar permissões
ls -la writable/
# Esperado: drwxrwxrwx (777)
```

---

## 🐛 Troubleshooting

### Erro: "Class not found"
```bash
composer dump-autoload
php spark cache:clear
```

### Erro: "Unable to connect to database"
1. Verifique a senha no `.env` (linha 30)
2. Teste conectividade: `ping aws-0-us-west-1.pooler.supabase.com`
3. Verifique firewall/proxy

### Erro: "Permission denied" em writable/
```bash
chmod -R 777 writable/
chmod -R 777 storage/
```

### Erro: "CSRF token mismatch"
```bash
php spark cache:clear
# Limpe cookies do navegador
```

### Página em branco
1. Verifique `writable/logs/log-*.log`
2. Ative debug no `.env`: `CI_ENVIRONMENT = development`
3. Verifique `public/index.php` está acessível

---

## 🎯 Próximas Funcionalidades

Após o sistema inicializar, você terá acesso a:

### ✅ Funcionalidades Prontas
- Login/Logout seguro
- Dashboard com estatísticas
- Registro de ponto (código único, QR Code)
- Gestão de funcionários
- Relatórios (PDF, Excel, CSV)
- Justificativas de ausências
- Sistema de advertências
- Auditoria completa
- Chat em tempo real (WebSocket)
- Notificações push
- Conformidade LGPD

### 🔧 Requer Configuração Adicional
- **Reconhecimento Facial**: Configure DeepFace API (Python)
- **Biometria Digital**: Configure SourceAFIS (opcional)
- **E-mail**: Configure SMTP no `.env`
- **Geofencing**: Configure cercas virtuais no admin

---

## 📚 Documentação Adicional

- [README.md](README.md) - Documentação completa
- [CORRECCOES_APLICADAS.md](CORRECCOES_APLICADAS.md) - Detalhes das correções
- [docs/](docs/) - Documentação técnica detalhada

---

## 💡 Dicas de Desenvolvimento

1. **Use o Debug Toolbar** (ativado automaticamente em development)
2. **Verifique logs regularmente**: `tail -f writable/logs/log-*.log`
3. **Mantenha .env seguro**: NUNCA commite este arquivo
4. **Teste em produção**: Use `.env.production.example` como base
5. **Backup regular**: Use `php spark backup:database`

---

## 🆘 Suporte

Se encontrar problemas:

1. ✅ Consulte este guia primeiro
2. ✅ Verifique `writable/logs/`
3. ✅ Revise [CORRECCOES_APLICADAS.md](CORRECCOES_APLICADAS.md)
4. ✅ Consulte documentação do CodeIgniter 4

---

**Sistema de Ponto Eletrônico Brasileiro**
**Conformidade: MTE 671/2021 | CLT Art. 74 | LGPD**

🎯 Desenvolvido com CodeIgniter 4 + PostgreSQL (Supabase)
