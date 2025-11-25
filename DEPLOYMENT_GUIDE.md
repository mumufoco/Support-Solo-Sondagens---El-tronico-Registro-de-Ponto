# Guia de Deployment - Sistema de Ponto Eletrônico

## 🔴 CAUSA RAIZ DO ERRO 403

O script de diagnóstico (`diagnostico-403.php`) revelou que o servidor **NÃO TEM OS ARQUIVOS DA APLICAÇÃO**.

### Arquivos Críticos Ausentes no Servidor:

✅ **Presentes localmente no Git:**
- `public/index.php` (arquivo principal)
- `app/Config/App.php` e outros arquivos de configuração
- `vendor/` (dependências do Composer)
- `writable/` (diretórios de cache, logs, session)

❌ **FALTANDO no servidor de produção:**
- Todos os arquivos acima estão ausentes em `/home/supportson/public_html/ponto/`

### O Erro 403 Ocorre Porque:
Quando você acessa `https://ponto.supportsondagens.com.br`, o Apache não encontra o arquivo `index.php` para servir, resultando em "403 Forbidden" (acesso negado).

---

## 📋 SOLUÇÕES DISPONÍVEIS

### **Opção 1: Deploy via Git (RECOMENDADO)**

#### Passo 1: Conectar via SSH ao servidor
```bash
ssh -p 22 supportson@148.113.162.190
```

#### Passo 2: Navegar até o diretório da aplicação
```bash
cd /home/supportson/public_html/ponto
```

#### Passo 3: Clonar ou puxar o repositório
Se o diretório estiver vazio, clone o repositório:
```bash
# Remover diretório atual se existir
cd /home/supportson/public_html
rm -rf ponto

# Clonar o repositório
git clone https://github.com/mumufoco/Support-Solo-Sondagens---El-tronico-Registro-de-Ponto.git ponto
cd ponto
```

Se já existir um repositório, apenas puxe as alterações:
```bash
cd /home/supportson/public_html/ponto
git fetch origin
git checkout claude/fix-auth-log-errors-01YHVDcAhJNqGjYTrwKTaEe2
git pull origin claude/fix-auth-log-errors-01YHVDcAhJNqGjYTrwKTaEe2
```

#### Passo 4: Instalar dependências do Composer
```bash
# Se o composer estiver instalado globalmente
composer install --no-dev --optimize-autoloader

# Se precisar baixar o composer
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev --optimize-autoloader
```

#### Passo 5: Criar diretório .env
```bash
cp .env.example .env
nano .env
```

Configure as variáveis de ambiente:
```env
CI_ENVIRONMENT = production

app.baseURL = 'https://ponto.supportsondagens.com.br/'

database.default.hostname = localhost
database.default.database = supportso_ponto
database.default.username = supportso_admin
database.default.password = SUA_SENHA_AQUI
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = 3306
```

#### Passo 6: Corrigir permissões
```bash
# Permissões de arquivos (644)
find /home/supportson/public_html/ponto -type f -exec chmod 644 {} \;

# Permissões de diretórios (755)
find /home/supportson/public_html/ponto -type d -exec chmod 755 {} \;

# Writable deve ser gravável
chmod -R 775 /home/supportson/public_html/ponto/writable
chmod -R 775 /home/supportson/public_html/ponto/storage

# Cache e session devem ser graváveis
chmod -R 775 /home/supportson/public_html/ponto/writable/cache
chmod -R 775 /home/supportson/public_html/ponto/writable/session
chmod -R 775 /home/supportson/public_html/ponto/writable/logs
```

#### Passo 7: Configurar Document Root no cPanel

**CRÍTICO:** O Document Root do Apache deve apontar para a pasta `public/`:

1. Acesse cPanel → "Domínios" ou "Addon Domains"
2. Encontre o domínio `ponto.supportsondagens.com.br`
3. Edite o Document Root para:
   ```
   /home/supportson/public_html/ponto/public
   ```
   ⚠️ **Importante:** Deve terminar em `/public`!

4. Salve as alterações
5. Aguarde 1-2 minutos para propagar

#### Passo 8: Verificar .htaccess
Verifique se o arquivo `/home/supportson/public_html/ponto/public/.htaccess` existe:
```bash
cat /home/supportson/public_html/ponto/public/.htaccess
```

Se não existir, crie:
```bash
cat > /home/supportson/public_html/ponto/public/.htaccess << 'EOF'
# CodeIgniter 4 - Public Folder .htaccess

# Disable directory browsing
Options -Indexes

# Set default index file
DirectoryIndex index.php index.html

# Enable rewrite engine
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Redirect trailing slashes
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)/$ /$1 [L,R=301]

    # Rewrite requests to index.php
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php/$1 [L]
</IfModule>

# Deny access to sensitive files
<FilesMatch "^\.">
    Require all denied
</FilesMatch>

<FilesMatch "(^composer\.|^phpunit\.|\\.env$)">
    Require all denied
</FilesMatch>
EOF
```

#### Passo 9: Testar a aplicação
```bash
# Verificar se index.php existe
ls -la /home/supportson/public_html/ponto/public/index.php

# Testar via curl
curl -I https://ponto.supportsondagens.com.br
```

Você deve ver `HTTP/1.1 200 OK` ou `HTTP/1.1 302 Found`.

---

### **Opção 2: Deploy via cPanel File Manager**

Se você não tem acesso SSH ou preferir usar o cPanel:

#### Passo 1: Preparar arquivo ZIP localmente
No seu computador local:
```bash
cd /home/user/Support-Solo-Sondagens---El-tronico-Registro-de-Ponto
git checkout claude/fix-auth-log-errors-01YHVDcAhJNqGjYTrwKTaEe2
zip -r ponto-deployment.zip . -x "*.git*" ".env" "writable/cache/*" "writable/logs/*" "writable/session/*"
```

#### Passo 2: Fazer upload via cPanel
1. Acesse cPanel → File Manager
2. Navegue até `/home/supportson/public_html/`
3. Delete a pasta `ponto` se existir (faça backup antes!)
4. Faça upload do arquivo `ponto-deployment.zip`
5. Clique com botão direito no arquivo → "Extract"
6. Renomeie a pasta extraída para `ponto`

#### Passo 3: Criar .env via File Manager
1. Navegue até `/home/supportson/public_html/ponto/`
2. Clique em "+ File" e crie `.env`
3. Edite o arquivo e cole as configurações (veja Opção 1, Passo 5)

#### Passo 4: Ajustar permissões via cPanel
1. Selecione a pasta `writable`
2. Clique em "Permissions"
3. Defina para `775` (rwxrwxr-x)
4. Marque "Recurse into subdirectories"
5. Clique em "Change Permissions"

#### Passo 5: Configurar Document Root
Siga o Passo 7 da Opção 1.

---

### **Opção 3: Deploy via FTP/SFTP**

#### Usando FileZilla ou WinSCP:

1. **Conectar ao servidor:**
   - Host: `148.113.162.190` ou `ponto.supportsondagens.com.br`
   - Porta: `21` (FTP) ou `22` (SFTP)
   - Usuário: `supportson`
   - Senha: `Mumufoco@1990`

2. **Fazer upload dos arquivos:**
   - Navegue até `/home/supportson/public_html/ponto/`
   - Faça upload de TODA a pasta do projeto
   - **Importante:** Não envie `.git/`, `.env`, `writable/cache/`, `writable/logs/*`

3. **Criar .env manualmente** (veja Opção 1, Passo 5)

4. **Ajustar permissões:**
   - `writable/` → `775`
   - `storage/` → `775`
   - Todos os arquivos → `644`
   - Todos os diretórios → `755`

5. **Configurar Document Root** (veja Opção 1, Passo 7)

---

## 🔧 VERIFICAÇÕES PÓS-DEPLOYMENT

### 1. Verificar estrutura de arquivos
```bash
ls -la /home/supportson/public_html/ponto/
```

Deve mostrar:
```
app/
public/
vendor/
writable/
storage/
.env
composer.json
```

### 2. Verificar index.php
```bash
ls -la /home/supportson/public_html/ponto/public/index.php
```

Deve retornar: `-rw-r--r-- 1 supportson supportson 3854 ...`

### 3. Verificar permissões do writable
```bash
ls -ld /home/supportson/public_html/ponto/writable/
```

Deve retornar: `drwxrwxr-x 10 supportson supportson ...`

### 4. Testar acesso ao site
Acesse: `https://ponto.supportsondagens.com.br`

Você deve ver:
- ✅ Página de login ou instalação
- ❌ NÃO deve ver erro 403

### 5. Verificar logs de erro
```bash
tail -50 /home/supportson/public_html/ponto/writable/logs/log-*.php
```

Se houver erros, eles aparecerão aqui.

---

## 🚨 PROBLEMAS COMUNS E SOLUÇÕES

### Problema 1: Erro "composer: command not found"
**Solução:** Instale o Composer localmente:
```bash
cd /home/supportson/public_html/ponto
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev --optimize-autoloader
```

### Problema 2: Erro "Permission denied" ao criar diretórios
**Solução:** Execute como usuário correto:
```bash
# Se você estiver como root, mude para supportson
su - supportson
cd /home/supportson/public_html/ponto
```

### Problema 3: Erro 500 após deployment
**Causas possíveis:**
1. `.env` não configurado → Configure o banco de dados
2. Permissões incorretas → Verifique writable (775)
3. Vendor não instalado → Execute `composer install`
4. PHP < 8.1 → Verifique versão do PHP no cPanel

**Verificar versão do PHP:**
```bash
php -v
```

Deve ser PHP 8.1 ou superior. Se for inferior, configure no cPanel:
1. cPanel → "Select PHP Version"
2. Escolha PHP 8.1 ou 8.2
3. Ative extensões: `mysqli`, `intl`, `json`, `mbstring`, `curl`

### Problema 4: Erro "Database connection failed"
**Solução:** Verifique as credenciais no `.env`:
```bash
nano /home/supportson/public_html/ponto/.env
```

Teste a conexão:
```bash
mysql -h localhost -u supportso_admin -p supportso_ponto
```

### Problema 5: Erro "Headers already sent"
**Solução:** Já foi corrigido nos commits anteriores. Certifique-se de estar na branch correta:
```bash
git checkout claude/fix-auth-log-errors-01YHVDcAhJNqGjYTrwKTaEe2
git pull origin claude/fix-auth-log-errors-01YHVDcAhJNqGjYTrwKTaEe2
```

---

## 📝 CHECKLIST DE DEPLOYMENT

Use esta checklist para garantir que tudo foi feito corretamente:

- [ ] 1. Código clonado/copiado para `/home/supportson/public_html/ponto/`
- [ ] 2. Dependências instaladas: `composer install --no-dev --optimize-autoloader`
- [ ] 3. Arquivo `.env` criado e configurado
- [ ] 4. Permissões ajustadas: arquivos (644), diretórios (755), writable (775)
- [ ] 5. Document Root configurado para `/home/supportson/public_html/ponto/public`
- [ ] 6. Arquivo `.htaccess` presente em `public/.htaccess`
- [ ] 7. PHP versão 8.1+ configurada no cPanel
- [ ] 8. Extensões PHP necessárias ativadas (mysqli, intl, json, mbstring, curl)
- [ ] 9. Banco de dados criado e credenciais configuradas no `.env`
- [ ] 10. Site acessível em `https://ponto.supportsondagens.com.br` sem erro 403

---

## 📞 PRÓXIMOS PASSOS

Após seguir este guia:

1. **Teste o acesso:** `https://ponto.supportsondagens.com.br`
2. **Verifique logs:** `/home/supportson/public_html/ponto/writable/logs/`
3. **Execute instalação:** Se aparecer tela de instalação, siga os passos
4. **Reporte problemas:** Se houver erros, copie os logs e informe

---

## 🔑 INFORMAÇÕES DE ACESSO (CONFIDENCIAL)

**SSH/SFTP:**
- Host: `148.113.162.190`
- Porta: `22` (SSH/SFTP) ou `21` (FTP)
- Usuário: `supportson`
- Senha: `Mumufoco@1990`

**Caminhos:**
- Aplicação: `/home/supportson/public_html/ponto/`
- Document Root: `/home/supportson/public_html/ponto/public/`
- Logs: `/home/supportson/public_html/ponto/writable/logs/`

**Banco de Dados:**
- Host: `localhost`
- Database: `supportso_ponto`
- Usuário: `supportso_admin`
- Senha: (configurar no `.env`)

---

**Última atualização:** 2025-11-25
**Branch de correções:** `claude/fix-auth-log-errors-01YHVDcAhJNqGjYTrwKTaEe2`
