# Guia de Instalação - Sistema de Registro de Ponto Eletrônico

Este guia descreve o processo completo de instalação do sistema em ambiente de produção.

## 📋 Índice

1. [Requisitos do Sistema](#requisitos-do-sistema)
2. [Preparação do Servidor](#preparação-do-servidor)
3. [Instalação do Sistema](#instalação-do-sistema)
4. [Configuração do Servidor Web](#configuração-do-servidor-web)
5. [Configuração SSL/HTTPS](#configuração-sslhttps)
6. [Verificação Final](#verificação-final)
7. [Backup e Manutenção](#backup-e-manutenção)
8. [Solução de Problemas](#solução-de-problemas)

---

## 🔧 Requisitos do Sistema

### Requisitos Mínimos do Servidor

- **Sistema Operacional**: Linux (Ubuntu 20.04+ / CentOS 7+ / Debian 10+)
- **PHP**: Versão 8.1 ou superior
- **MySQL**: Versão 5.7+ ou MariaDB 10.3+
- **Memória RAM**: Mínimo 2GB (recomendado 4GB+)
- **Espaço em Disco**: Mínimo 1GB livre
- **Servidor Web**: Apache 2.4+ ou Nginx 1.18+

### Extensões PHP Necessárias

O instalador verificará automaticamente todas estas extensões:

- ✅ `pdo_mysql` - Conexão com banco de dados MySQL
- ✅ `openssl` - Criptografia e segurança
- ✅ `mbstring` - Manipulação de strings multi-byte
- ✅ `json` - Processamento de dados JSON
- ✅ `curl` - Requisições HTTP
- ✅ `gd` - Processamento de imagens
- ✅ `intl` - Internacionalização
- ✅ `xml` - Processamento de XML
- ✅ `zip` - Compressão de arquivos

### Instalar Extensões PHP (Ubuntu/Debian)

```bash
sudo apt update
sudo apt install php8.1 php8.1-cli php8.1-mysql php8.1-mbstring \
  php8.1-curl php8.1-gd php8.1-intl php8.1-xml php8.1-zip \
  php8.1-openssl php8.1-json
```

### Instalar Extensões PHP (CentOS/RHEL)

```bash
sudo yum install epel-release
sudo yum install php81 php81-php-mysqlnd php81-php-mbstring \
  php81-php-curl php81-php-gd php81-php-intl php81-php-xml \
  php81-php-zip php81-php-openssl php81-php-json
```

---

## 🚀 Preparação do Servidor

### 1. Criar Banco de Dados MySQL

Conecte-se ao MySQL como root:

```bash
mysql -u root -p
```

Execute os comandos SQL:

```sql
-- Criar banco de dados
CREATE DATABASE registro_ponto CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Criar usuário dedicado
CREATE USER 'ponto_user'@'localhost' IDENTIFIED BY 'senha_segura_aqui';

-- Conceder permissões
GRANT ALL PRIVILEGES ON registro_ponto.* TO 'ponto_user'@'localhost';

-- Aplicar mudanças
FLUSH PRIVILEGES;

-- Verificar
SHOW DATABASES;
EXIT;
```

**⚠️ IMPORTANTE**: Substitua `'senha_segura_aqui'` por uma senha forte e única!

### 2. Fazer Upload dos Arquivos

Envie os arquivos do sistema para o servidor (via FTP, SFTP ou Git):

```bash
# Exemplo usando Git
cd /var/www/html
sudo git clone https://github.com/seu-usuario/seu-repositorio.git registro-ponto
cd registro-ponto
```

### 3. Instalar Dependências do Composer

```bash
cd /var/www/html/registro-ponto

# Instalar dependências em modo produção (sem dev)
# Use --ignore-platform-reqs se tiver problemas de versão do PHP
composer install --no-dev --optimize-autoloader --ignore-platform-reqs
```

**⚠️ Problemas com Composer?**

Se encontrar erro `"Your Composer dependencies require a PHP version ">= 8.3.0""`:

```bash
# Opção 1: Execute o script de pré-instalação
php pre-install.php

# Opção 2: Remova o arquivo problemático manualmente
rm vendor/composer/platform_check.php

# Opção 3: Reinstale com ignore-platform-reqs
composer install --no-dev --optimize-autoloader --ignore-platform-reqs
```

### 4. Aplicar Configurações PHP (Hospedagem Compartilhada)

Se estiver em hospedagem compartilhada, o arquivo `.user.ini` já está incluído e será carregado automaticamente pelo PHP para corrigir configurações comuns.

**Configurações aplicadas automaticamente:**
- ✅ `session.gc_divisor` corrigido (evita warning de sessão)
- ✅ Limites de memória e upload aumentados
- ✅ Timezone configurado (America/Sao_Paulo)
- ✅ Segurança de sessão habilitada

**⚠️ Nota**: Se o arquivo `.user.ini` não for reconhecido pelo servidor, as correções já estão implementadas nos scripts PHP via `ini_set()`.

### 5. Configurar Permissões de Diretórios

```bash
# Definir proprietário correto (ajuste www-data conforme seu servidor)
sudo chown -R www-data:www-data /var/www/html/registro-ponto

# Permissões para diretórios writable
sudo chmod -R 755 /var/www/html/registro-ponto/writable
sudo chmod -R 755 /var/www/html/registro-ponto/writable/cache
sudo chmod -R 755 /var/www/html/registro-ponto/writable/logs
sudo chmod -R 755 /var/www/html/registro-ponto/writable/session
sudo chmod -R 755 /var/www/html/registro-ponto/writable/uploads

# Permissões mais restritivas para o restante
sudo chmod -R 755 /var/www/html/registro-ponto
```

---

## ⚙️ Instalação do Sistema

### Executar o Instalador Interativo

O sistema possui um instalador interativo que guiará você por 4 fases:

```bash
cd /var/www/html/registro-ponto
php install.php
```

### Fase 1: Checagem Inicial

O instalador verificará automaticamente:

- ✅ Versão do PHP (8.1+)
- ✅ Extensões PHP necessárias
- ✅ Permissões de diretórios
- ✅ Dependências do Composer

**Se houver falhas**: O instalador exibirá instruções detalhadas para corrigir cada problema.

### Fase 2: Criação do Administrador

Você será solicitado a fornecer:

1. **Nome completo** do administrador
2. **E-mail** (será usado para login)
3. **Senha** (requisitos mínimos):
   - Mínimo 12 caracteres
   - Pelo menos 1 letra maiúscula
   - Pelo menos 1 letra minúscula
   - Pelo menos 1 número

**Exemplo**:
```
Nome do administrador: João da Silva
E-mail do administrador: admin@empresa.com.br
Senha: Admin@2024Segura
```

### Fase 3: Configuração do Banco de Dados

O instalador solicitará as seguintes informações:

1. **URL da aplicação** (ex: `https://ponto.suaempresa.com.br`)
2. **Host do MySQL** (geralmente `localhost`)
3. **Nome do banco de dados** (ex: `registro_ponto`)
4. **Usuário do MySQL** (ex: `ponto_user`)
5. **Senha do MySQL**
6. **Porta do MySQL** (padrão: `3306`)

**Exemplo de preenchimento**:
```
URL base da aplicação: https://ponto.empresa.com.br
Host do banco MySQL: localhost
Nome do banco: registro_ponto
Usuário do MySQL: ponto_user
Senha do MySQL: [sua senha segura]
Porta do MySQL: 3306
```

#### Tabelas Existentes

Se o banco já tiver tabelas, o instalador perguntará:

```
⚠️ O banco de dados contém tabelas. Deseja apagar todas? (s/n)
```

- Digite `s` e confirme com `CONFIRMO` para limpar completamente
- Digite `n` para cancelar (recomendado se há dados importantes)

**⚠️ ATENÇÃO**: Apagar tabelas é **IRREVERSÍVEL**! Faça backup antes!

### Fase 4: Checagem Final

O instalador validará automaticamente:

1. ✅ Conectividade com banco de dados
2. ✅ Existência das tabelas necessárias
3. ✅ Criação do usuário administrador
4. ✅ Arquivo `.env` gerado corretamente
5. ✅ Permissões dos diretórios writable

### Resultado da Instalação

Se tudo correr bem, você verá:

```
✅ Instalação concluída com sucesso!

Sistema pronto para uso em produção.

📝 Credenciais do administrador:
   E-mail: admin@empresa.com.br
   Senha: [a senha que você definiu]

🌐 Acesse o sistema em:
   https://ponto.suaempresa.com.br

⚠️  IMPORTANTE:
   - Guarde suas credenciais em local seguro
   - Faça backup regular do banco de dados
   - O arquivo .env contém informações sensíveis (não compartilhe!)
```

---

## 🌐 Configuração do Servidor Web

### Apache 2.4

Crie um arquivo de configuração do VirtualHost:

```bash
sudo nano /etc/apache2/sites-available/registro-ponto.conf
```

Adicione a seguinte configuração:

```apache
<VirtualHost *:80>
    ServerName ponto.suaempresa.com.br
    ServerAlias www.ponto.suaempresa.com.br

    DocumentRoot /var/www/html/registro-ponto/public

    <Directory /var/www/html/registro-ponto/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Logs
    ErrorLog ${APACHE_LOG_DIR}/registro-ponto-error.log
    CustomLog ${APACHE_LOG_DIR}/registro-ponto-access.log combined

    # Redirecionar HTTP para HTTPS (após configurar SSL)
    # RewriteEngine On
    # RewriteCond %{HTTPS} off
    # RewriteRule ^(.*)$ https://%{HTTP_HOST}$1 [R=301,L]
</VirtualHost>

<VirtualHost *:443>
    ServerName ponto.suaempresa.com.br
    ServerAlias www.ponto.suaempresa.com.br

    DocumentRoot /var/www/html/registro-ponto/public

    <Directory /var/www/html/registro-ponto/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # SSL Configuration (configurar após obter certificado)
    # SSLEngine on
    # SSLCertificateFile /path/to/certificate.crt
    # SSLCertificateKeyFile /path/to/private.key
    # SSLCertificateChainFile /path/to/chain.crt

    ErrorLog ${APACHE_LOG_DIR}/registro-ponto-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/registro-ponto-ssl-access.log combined
</VirtualHost>
```

Ativar módulos e site:

```bash
# Habilitar módulos necessários
sudo a2enmod rewrite
sudo a2enmod ssl
sudo a2enmod headers

# Ativar o site
sudo a2ensite registro-ponto.conf

# Desativar site padrão (opcional)
sudo a2dissite 000-default.conf

# Testar configuração
sudo apache2ctl configtest

# Recarregar Apache
sudo systemctl reload apache2
```

### Nginx

Crie um arquivo de configuração:

```bash
sudo nano /etc/nginx/sites-available/registro-ponto
```

Adicione:

```nginx
server {
    listen 80;
    listen [::]:80;

    server_name ponto.suaempresa.com.br www.ponto.suaempresa.com.br;

    # Redirecionar para HTTPS (após configurar SSL)
    # return 301 https://$server_name$request_uri;

    root /var/www/html/registro-ponto/public;
    index index.php index.html;

    # Logs
    access_log /var/log/nginx/registro-ponto-access.log;
    error_log /var/log/nginx/registro-ponto-error.log;

    # Desabilitar listagem de diretórios
    autoindex off;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Negar acesso a arquivos sensíveis
    location ~ /\. {
        deny all;
    }

    location ~ ^/(\.env|composer\.(json|lock)|package\.json|\.git) {
        deny all;
        return 404;
    }
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;

    server_name ponto.suaempresa.com.br www.ponto.suaempresa.com.br;

    root /var/www/html/registro-ponto/public;
    index index.php index.html;

    # SSL Configuration (configurar após obter certificado)
    # ssl_certificate /path/to/certificate.crt;
    # ssl_certificate_key /path/to/private.key;
    # ssl_trusted_certificate /path/to/chain.crt;

    # SSL Settings modernas
    # ssl_protocols TLSv1.2 TLSv1.3;
    # ssl_ciphers 'ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256';
    # ssl_prefer_server_ciphers on;

    access_log /var/log/nginx/registro-ponto-ssl-access.log;
    error_log /var/log/nginx/registro-ponto-ssl-error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }

    location ~ ^/(\.env|composer\.(json|lock)|package\.json|\.git) {
        deny all;
        return 404;
    }
}
```

Ativar o site:

```bash
# Criar link simbólico
sudo ln -s /etc/nginx/sites-available/registro-ponto /etc/nginx/sites-enabled/

# Remover site padrão (opcional)
sudo rm /etc/nginx/sites-enabled/default

# Testar configuração
sudo nginx -t

# Recarregar Nginx
sudo systemctl reload nginx
```

---

## 🔒 Configuração SSL/HTTPS

### Opção 1: Let's Encrypt (Gratuito e Recomendado)

#### Para Apache:

```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-apache

# Obter e instalar certificado automaticamente
sudo certbot --apache -d ponto.suaempresa.com.br -d www.ponto.suaempresa.com.br

# Configurar renovação automática
sudo certbot renew --dry-run
```

#### Para Nginx:

```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-nginx

# Obter e instalar certificado automaticamente
sudo certbot --nginx -d ponto.suaempresa.com.br -d www.ponto.suaempresa.com.br

# Configurar renovação automática
sudo certbot renew --dry-run
```

### Opção 2: Certificado Comercial

Se você comprou um certificado SSL:

1. Faça upload dos arquivos do certificado para `/etc/ssl/certs/`
2. Faça upload da chave privada para `/etc/ssl/private/`
3. Configure as diretivas SSL no VirtualHost/server block
4. Reinicie o servidor web

### Verificar SSL

Após configurar, teste em:
- https://www.ssllabs.com/ssltest/
- https://ponto.suaempresa.com.br

---

## ✅ Verificação Final

### 1. Testar Acesso ao Sistema

Abra um navegador e acesse:
```
https://ponto.suaempresa.com.br
```

Você deve ver a tela de login do sistema.

### 2. Login com Administrador

Use as credenciais criadas durante a instalação:
- E-mail: `admin@empresa.com.br`
- Senha: [a senha que você definiu]

### 3. Verificar Funcionalidades

Teste as principais funcionalidades:

- ✅ Login/Logout
- ✅ Dashboard carrega corretamente
- ✅ Cadastro de funcionários
- ✅ Registro de ponto
- ✅ Relatórios
- ✅ Upload de documentos

### 4. Verificar Logs

```bash
# Logs do sistema
tail -f /var/www/html/registro-ponto/writable/logs/*.log

# Logs do Apache
tail -f /var/log/apache2/registro-ponto-error.log

# Logs do Nginx
tail -f /var/log/nginx/registro-ponto-error.log
```

### 5. Verificar Configuração do PHP

Crie um arquivo temporário para verificar:

```bash
echo "<?php phpinfo();" > /var/www/html/registro-ponto/public/phpinfo.php
```

Acesse: `https://ponto.suaempresa.com.br/phpinfo.php`

**⚠️ IMPORTANTE**: Delete este arquivo após verificação!

```bash
rm /var/www/html/registro-ponto/public/phpinfo.php
```

---

## 💾 Backup e Manutenção

### Backup do Banco de Dados

Crie um script de backup automático:

```bash
sudo nano /usr/local/bin/backup-ponto.sh
```

Conteúdo:

```bash
#!/bin/bash

# Configurações
DB_NAME="registro_ponto"
DB_USER="ponto_user"
DB_PASS="sua_senha_mysql"
BACKUP_DIR="/var/backups/registro-ponto"
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/backup_$DATE.sql.gz"

# Criar diretório se não existir
mkdir -p $BACKUP_DIR

# Fazer backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_FILE

# Manter apenas últimos 30 dias
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +30 -delete

echo "Backup concluído: $BACKUP_FILE"
```

Tornar executável e agendar:

```bash
sudo chmod +x /usr/local/bin/backup-ponto.sh

# Agendar backup diário às 2h da manhã
sudo crontab -e

# Adicionar linha:
0 2 * * * /usr/local/bin/backup-ponto.sh >> /var/log/backup-ponto.log 2>&1
```

### Backup dos Arquivos

```bash
# Backup completo do sistema
tar -czf /var/backups/registro-ponto-files-$(date +%Y%m%d).tar.gz \
  --exclude='writable/cache/*' \
  --exclude='writable/logs/*' \
  --exclude='writable/session/*' \
  /var/www/html/registro-ponto
```

### Restauração de Backup

```bash
# Restaurar banco de dados
gunzip < /var/backups/registro-ponto/backup_20240101_020000.sql.gz | \
  mysql -u ponto_user -p registro_ponto

# Restaurar arquivos
tar -xzf /var/backups/registro-ponto-files-20240101.tar.gz -C /
```

### Manutenção Regular

#### Limpar Cache

```bash
cd /var/www/html/registro-ponto
php spark cache:clear
```

#### Atualizar Sistema

```bash
cd /var/www/html/registro-ponto

# Fazer backup antes!
/usr/local/bin/backup-ponto.sh

# Atualizar código (se usando Git)
git pull origin main

# Atualizar dependências
composer install --no-dev --optimize-autoloader

# Executar novas migrations (se houver)
php spark migrate --all

# Limpar cache
php spark cache:clear

# Recarregar servidor web
sudo systemctl reload apache2  # ou nginx
```

---

## 🔧 Solução de Problemas

### Problema: Erro 500 - Internal Server Error

**Possíveis causas e soluções**:

1. **Permissões incorretas**:
```bash
sudo chown -R www-data:www-data /var/www/html/registro-ponto
sudo chmod -R 755 /var/www/html/registro-ponto/writable
```

2. **Arquivo .env ausente ou inválido**:
```bash
# Verificar se existe
ls -la /var/www/html/registro-ponto/.env

# Verificar permissões
chmod 600 /var/www/html/registro-ponto/.env
```

3. **Verificar logs**:
```bash
tail -50 /var/www/html/registro-ponto/writable/logs/log-*.log
```

### Problema: Página em branco (sem erro)

1. **Ativar modo debug temporariamente**:
```bash
nano /var/www/html/registro-ponto/.env

# Alterar:
CI_ENVIRONMENT = development

# Acessar o sistema novamente para ver erros detalhados
# IMPORTANTE: Retornar para 'production' após identificar o problema!
```

2. **Verificar logs do PHP**:
```bash
# Apache
tail -f /var/log/apache2/error.log

# Nginx
tail -f /var/log/nginx/error.log
```

### Problema: Erro de conexão com banco de dados

1. **Verificar credenciais no .env**:
```bash
cat /var/www/html/registro-ponto/.env | grep database
```

2. **Testar conexão manual**:
```bash
mysql -h localhost -u ponto_user -p registro_ponto
```

3. **Verificar se MySQL está rodando**:
```bash
sudo systemctl status mysql
```

4. **Verificar privilégios do usuário**:
```sql
SHOW GRANTS FOR 'ponto_user'@'localhost';
```

### Problema: CSS/JS não carregam

1. **Verificar permissões**:
```bash
ls -la /var/www/html/registro-ponto/public
```

2. **Limpar cache do navegador**: Ctrl+Shift+R (Chrome) ou Ctrl+F5 (Firefox)

3. **Verificar URL base no .env**:
```bash
grep baseURL /var/www/html/registro-ponto/.env
# Deve ter a URL completa com https://
```

### Problema: Upload de arquivos não funciona

1. **Verificar permissões do diretório**:
```bash
sudo chmod -R 755 /var/www/html/registro-ponto/writable/uploads
sudo chown -R www-data:www-data /var/www/html/registro-ponto/writable/uploads
```

2. **Verificar limites do PHP**:
```bash
# Editar php.ini
sudo nano /etc/php/8.1/apache2/php.ini  # ou /etc/php/8.1/fpm/php.ini para Nginx

# Ajustar:
upload_max_filesize = 20M
post_max_size = 20M
memory_limit = 256M

# Reiniciar servidor
sudo systemctl restart apache2  # ou php8.1-fpm para Nginx
```

### Problema: Sessions não persistem / Logout automático

1. **Verificar diretório de sessões**:
```bash
sudo chmod -R 755 /var/www/html/registro-ponto/writable/session
sudo chown -R www-data:www-data /var/www/html/registro-ponto/writable/session
```

2. **Verificar configuração de cookies** no `.env`:
```ini
cookie.secure = true
cookie.samesite = Lax
```

### Problema: Erro "CSRF token mismatch"

1. **Limpar sessões antigas**:
```bash
rm -rf /var/www/html/registro-ponto/writable/session/*
```

2. **Limpar cache do navegador** e tentar novamente

3. **Verificar se HTTPS está ativo** (obrigatório em produção)

---

## 📞 Suporte Adicional

### Logs Importantes

```bash
# Logs da aplicação CodeIgniter
/var/www/html/registro-ponto/writable/logs/log-YYYY-MM-DD.log

# Logs do Apache
/var/log/apache2/registro-ponto-error.log
/var/log/apache2/registro-ponto-access.log

# Logs do Nginx
/var/log/nginx/registro-ponto-error.log
/var/log/nginx/registro-ponto-access.log

# Logs do MySQL
/var/log/mysql/error.log
```

### Comandos Úteis do CodeIgniter 4

```bash
cd /var/www/html/registro-ponto

# Ver lista de comandos disponíveis
php spark list

# Executar migrations
php spark migrate --all

# Reverter última migration
php spark migrate:rollback

# Ver status das migrations
php spark migrate:status

# Limpar cache
php spark cache:clear

# Ver rotas disponíveis
php spark routes
```

### Verificação de Segurança

Use estas ferramentas para auditar a segurança:

1. **SSL/TLS**: https://www.ssllabs.com/ssltest/
2. **Headers de Segurança**: https://securityheaders.com/
3. **Scan de vulnerabilidades**: https://observatory.mozilla.org/

### Checklist de Produção

Antes de colocar em produção, verifique:

- [ ] Todas as extensões PHP necessárias instaladas
- [ ] Certificado SSL configurado e válido
- [ ] HTTPS funcionando (forceGlobalSecureRequests = true)
- [ ] Ambiente definido como 'production' no .env
- [ ] Debug mode desativado (CI_ENVIRONMENT = production)
- [ ] Backup automático configurado
- [ ] Permissões de arquivos corretas (755/644)
- [ ] .env com permissão 600 e não acessível via web
- [ ] Senhas fortes definidas (BD, admin)
- [ ] Firewall configurado (permitir apenas 80/443)
- [ ] Logs sendo gerados corretamente
- [ ] Sistema de monitoramento ativo
- [ ] Testes de funcionalidades principais realizados

---

## 📄 Notas Finais

### Arquivos Sensíveis

**NUNCA** compartilhe ou versione:
- `.env` - Contém credenciais e chaves
- `writable/` - Contém sessões e cache
- Backups do banco de dados

### Atualizações

Mantenha o sistema atualizado:
- PHP (patches de segurança)
- CodeIgniter 4 (novas versões)
- Dependências do Composer
- Sistema operacional
- MySQL/MariaDB

### Monitoramento

Configure monitoramento para:
- Espaço em disco
- Uso de memória
- Carga do servidor
- Disponibilidade do serviço
- Logs de erro

---

**Sistema de Registro de Ponto Eletrônico v1.0**

*Desenvolvido para gestão eficiente de jornada de trabalho*

Para mais informações, consulte a documentação técnica do CodeIgniter 4: https://codeigniter.com/user_guide/
