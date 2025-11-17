# 📦 Guia de Instalação - Sistema de Ponto Eletrônico

## 🚀 Instalação Automatizada (Recomendado)

O sistema possui um **instalador web interativo** que configura tudo automaticamente.

### Passo a Passo:

1. **Faça upload dos arquivos** para seu servidor web (ou clone via git)

2. **Acesse o instalador** pelo navegador:
   ```
   https://seu-dominio.com.br/install.php
   ```

3. **Siga os 4 passos do instalador:**
   - ✅ **Passo 1:** Verificação automática de requisitos do sistema
   - ✅ **Passo 2:** Configuração e criação do banco de dados
   - ✅ **Passo 3:** Configuração da aplicação e primeiro administrador
   - ✅ **Passo 4:** Conclusão e credenciais de acesso

4. **DELETE o arquivo install.php** após a instalação:
   ```bash
   rm public/install.php
   ```

---

## 📋 Requisitos do Sistema

### Servidor Web
- **Apache** 2.4+ ou **Nginx** 1.18+
- **mod_rewrite** habilitado (Apache)
- Suporte a **.htaccess** (Apache)

### PHP
- **Versão:** 8.1 ou superior
- **Extensões Requeridas:**
  - `mysqli` (conexão MySQL/MariaDB)
  - `json` (manipulação JSON)
  - `mbstring` (strings multibyte)
  - `openssl` (criptografia)
  - `gd` (manipulação de imagens)
  - `curl` (requisições HTTP)
  - `intl` (internacionalização)

### Banco de Dados
- **MySQL** 5.7+ ou **MariaDB** 10.3+
- **Charset:** utf8mb4
- **Collation:** utf8mb4_unicode_ci

### Permissões de Diretório
Os seguintes diretórios precisam ter permissão de **escrita (chmod 755 ou 775)**:
```
writable/
writable/cache/
writable/logs/
writable/session/
writable/uploads/
storage/
storage/backups/
storage/faces/
storage/logs/
storage/qrcodes/
storage/receipts/
storage/reports/
storage/uploads/
```

**Comando para configurar permissões:**
```bash
chmod -R 755 writable/ storage/
```

---

## 🛠️ Instalação Manual (Avançado)

Se preferir instalar manualmente sem o instalador web:

### 1. Clonar o Repositório
```bash
git clone https://github.com/seu-usuario/ponto-eletronico.git
cd ponto-eletronico
```

### 2. Instalar Dependências
```bash
composer install --no-dev --optimize-autoloader
```

### 3. Configurar o Banco de Dados

**Criar o banco:**
```sql
CREATE DATABASE ponto_eletronico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Importar estrutura:**
```bash
mysql -u usuario -p ponto_eletronico < public/database.sql
```

### 4. Configurar o Arquivo .env

**Copiar o template:**
```bash
cp .env.example .env
```

**Editar o .env** e configurar:
```ini
# URL da aplicação
app.baseURL = 'https://seu-dominio.com.br'

# Gerar chave de criptografia
# Execute: php -r "echo base64_encode(random_bytes(32));"
encryption.key = base64:SUA_CHAVE_AQUI

# Banco de dados
database.default.hostname = localhost
database.default.database = ponto_eletronico
database.default.username = seu_usuario
database.default.password = sua_senha_segura
database.default.port = 3306

# Dados da empresa
company.name = 'Sua Empresa LTDA'
company.cnpj = '00.000.000/0001-00'
```

### 5. Criar o Primeiro Administrador

**Via linha de comando:**
```bash
php spark make:admin
```

Ou manualmente via SQL:
```sql
INSERT INTO employees (
    name, email, password, cpf, unique_code, role, active, created_at, updated_at
) VALUES (
    'Administrador',
    'admin@empresa.com',
    -- Gerar hash: password_hash('sua_senha', PASSWORD_ARGON2ID)
    '$argon2id$v=19$m=65536,t=4,p=1$...',
    '000.000.000-00',
    'ADMIN1',
    'admin',
    1,
    NOW(),
    NOW()
);
```

### 6. Configurar Permissões
```bash
chmod -R 755 writable/ storage/
chown -R www-data:www-data writable/ storage/
```

### 7. Configurar o Servidor Web

**Apache (.htaccess já incluído):**
```apache
<VirtualHost *:80>
    ServerName seu-dominio.com.br
    DocumentRoot /var/www/ponto-eletronico/public
    
    <Directory /var/www/ponto-eletronico/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/ponto-error.log
    CustomLog ${APACHE_LOG_DIR}/ponto-access.log combined
</VirtualHost>
```

**Nginx:**
```nginx
server {
    listen 80;
    server_name seu-dominio.com.br;
    root /var/www/ponto-eletronico/public;
    
    index index.php index.html;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
```

---

## ⚙️ Configurações Pós-Instalação

### 1. Configurar Backups Automáticos

**Editar crontab:**
```bash
crontab -e
```

**Adicionar as seguintes linhas:**
```cron
# Backup diário às 2h da manhã
0 2 * * * cd /var/www/ponto-eletronico && php spark backup:database

# Monitoramento de logs a cada 5 minutos
*/5 * * * * cd /var/www/ponto-eletronico && php spark monitor:logs

# Limpeza de backups antigos aos domingos às 3h
0 3 * * 0 cd /var/www/ponto-eletronico && php spark backup:database --clean

# Limpeza de logs antigos diariamente às 4h
0 4 * * * cd /var/www/ponto-eletronico && php spark monitor:logs --clean
```

### 2. Configurar E-mail (Notificações)

**Editar .env:**
```ini
email.fromEmail = noreply@seu-dominio.com.br
email.fromName = 'Sistema de Ponto Eletrônico'
email.SMTPHost = smtp.seu-dominio.com.br
email.SMTPUser = usuario@seu-dominio.com.br
email.SMTPPass = senha_email_segura
email.SMTPPort = 587
email.SMTPCrypto = tls
```

**Testar envio:**
```bash
php spark email:test admin@empresa.com
```

### 3. Configurar SSL/HTTPS (Obrigatório)

**Let's Encrypt (Certbot):**
```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d seu-dominio.com.br
```

**Ou para Nginx:**
```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d seu-dominio.com.br
```

### 4. Otimizações de Performance

**Habilitar cache do CodeIgniter:**
```bash
php spark cache:clear
```

**Otimizar autoload do Composer:**
```bash
composer dump-autoload --optimize --no-dev
```

**Configurar OPcache** (php.ini):
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60
```

---

## 🔒 Segurança

### Checklist de Segurança Pós-Instalação

- ✅ **DELETE o arquivo install.php**
- ✅ Certificado SSL/HTTPS configurado
- ✅ Arquivo .env NÃO está no repositório git
- ✅ Senha do banco de dados é forte (20+ caracteres)
- ✅ Permissões de diretório corretas (755 para writable/ e storage/)
- ✅ CI_ENVIRONMENT = production no .env
- ✅ Backups automáticos configurados
- ✅ Monitoramento de logs ativo
- ✅ Firewall configurado (apenas portas 80, 443, 22)
- ✅ Atualizações do sistema em dia

### Senhas Seguras

**Gerar senha forte:**
```bash
php -r "echo bin2hex(random_bytes(16));"
```

**Gerar chave de criptografia:**
```bash
php -r "echo 'base64:' . base64_encode(random_bytes(32));"
```

---

## 🧪 Testes

### Executar Testes Automatizados
```bash
# Todos os testes
composer test

# Apenas unit tests
vendor/bin/phpunit tests/unit

# Apenas feature tests
vendor/bin/phpunit tests/feature

# Com cobertura de código
vendor/bin/phpunit --coverage-html coverage/
```

### Testar Comandos CLI
```bash
# Backup manual
php spark backup:database

# Listar backups
php spark backup:database --list

# Monitorar logs
php spark monitor:logs

# Gerar relatório de logs
php spark monitor:logs --report
```

---

## 📊 Verificação da Instalação

**Após instalar, verifique:**

1. **Acessar a aplicação:**
   ```
   https://seu-dominio.com.br
   ```

2. **Fazer login com as credenciais do administrador**

3. **Verificar headers de segurança:**
   ```bash
   curl -I https://seu-dominio.com.br
   ```
   Deve retornar:
   - `X-Frame-Options: DENY`
   - `X-Content-Type-Options: nosniff`
   - `Strict-Transport-Security: max-age=31536000`

4. **Verificar permissões:**
   ```bash
   ls -la writable/ storage/
   ```

5. **Verificar logs:**
   ```bash
   tail -f writable/logs/log-*.log
   ```

---

## 🆘 Solução de Problemas

### Erro: "Class 'DotEnv' not found"
**Solução:**
```bash
composer install
```

### Erro: "Session: Configured save path is not writable"
**Solução:**
```bash
chmod -R 755 writable/session
chown -R www-data:www-data writable/session
```

### Erro: "Unable to connect to database"
**Solução:**
1. Verificar credenciais no .env
2. Testar conexão manual:
   ```bash
   mysql -h localhost -u usuario -p banco
   ```

### Erro 500 - Internal Server Error
**Solução:**
1. Verificar logs:
   ```bash
   tail -f writable/logs/log-*.log
   ```
2. Verificar se mod_rewrite está ativo (Apache):
   ```bash
   sudo a2enmod rewrite
   sudo systemctl restart apache2
   ```

### Página em branco após instalação
**Solução:**
1. Verificar permissões de writable/
2. Verificar logs de erro do PHP:
   ```bash
   tail -f /var/log/apache2/error.log
   # ou
   tail -f /var/log/nginx/error.log
   ```

---

## 📞 Suporte

- **Documentação completa:** `API_DOCUMENTATION.md`
- **Guia de testes:** `TESTING_GUIDE.md`
- **Relatório de depuração:** `RELATORIO_DEPURACAO_COMPLETA.md`

---

## 📝 Conformidade Legal

Este sistema está em conformidade com:
- ✅ **Portaria MTE 671/2021** - Registro Eletrônico de Ponto
- ✅ **CLT Art. 74** - Controle de Jornada de Trabalho
- ✅ **LGPD (Lei 13.709/2018)** - Proteção de Dados Pessoais

---

## 🎯 Próximos Passos

Após a instalação:

1. Cadastrar departamentos e cargos
2. Cadastrar funcionários
3. Configurar geofences (se usar registro por localização)
4. Configurar jornadas de trabalho
5. Personalizar notificações
6. Configurar relatórios customizados
7. Treinar os gestores e funcionários

---

**Sistema de Ponto Eletrônico v2.0**  
Instalação completa com suporte a backup automático, monitoramento de logs e testes automatizados.
