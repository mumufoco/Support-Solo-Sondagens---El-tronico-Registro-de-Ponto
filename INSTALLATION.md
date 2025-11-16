# 📦 Guia de Instalação - Sistema de Ponto Eletrônico

**Versão:** 1.0
**Data:** 2025-11-16
**Conformidade:** Portaria MTE 671/2021, CLT, LGPD

---

## 📋 Índice

1. [Requisitos do Sistema](#requisitos-do-sistema)
2. [Métodos de Instalação](#métodos-de-instalação)
3. [Instalação Web (Recomendado)](#instalação-web-recomendado)
4. [Instalação Manual](#instalação-manual)
5. [Instalação Docker](#instalação-docker)
6. [Pós-Instalação](#pós-instalação)
7. [Solução de Problemas](#solução-de-problemas)
8. [Próximos Passos](#próximos-passos)

---

## 🖥️ Requisitos do Sistema

### Requisitos Mínimos

- **PHP:** 8.1+ (8.2+ recomendado)
- **MySQL:** 8.0+ ou MariaDB 10.6+
- **Servidor Web:** Apache 2.4+ ou Nginx 1.18+
- **Composer:** 2.5+
- **Node.js:** 18+ (opcional, para build de assets)
- **Memória RAM:** 2GB mínimo, 4GB recomendado
- **Espaço em Disco:** 500MB mínimo, 2GB recomendado

### Extensões PHP Necessárias

```bash
intl
mbstring
json
mysqlnd
gd
curl
xml
zip
fileinfo
openssl
```

### Portas Utilizadas

- **80/443** - Aplicação Web (HTTP/HTTPS)
- **3306** - MySQL
- **2346** - WebSocket (Chat em tempo real)
- **5000** - DeepFace API (Reconhecimento facial)
- **8080** - PHPMyAdmin (opcional, dev)
- **8025** - Mailhog (opcional, dev)

---

## 🚀 Métodos de Instalação

Escolha o método mais adequado para seu ambiente:

| Método | Complexidade | Tempo | Recomendado Para |
|--------|--------------|-------|------------------|
| **Instalador Web** | Fácil | 5-10 min | Produção, iniciantes |
| **Instalação Manual** | Média | 15-20 min | Desenvolvedores, customização |
| **Docker** | Fácil | 10-15 min | Desenvolvimento, staging |

---

## 🌐 Instalação Web (Recomendado)

### Passo 1: Preparar o Servidor

```bash
# Clone o repositório
git clone https://github.com/seu-usuario/ponto-eletronico.git
cd ponto-eletronico

# Instalar dependências
./install-dependencies.sh
```

### Passo 2: Configurar Servidor Web

**Apache (.htaccess já incluído):**
```apache
<VirtualHost *:80>
    ServerName pontoeletronico.local
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
    server_name pontoeletronico.local;

    root /var/www/ponto-eletronico/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

### Passo 3: Acessar o Instalador Web

1. Acesse: `http://seudominio.com/install.php`
2. Siga o assistente de 5 passos:
   - **Passo 1:** Verificação de requisitos
   - **Passo 2:** Configuração do banco de dados
   - **Passo 3:** Criar usuário administrador
   - **Passo 4:** Executar instalação
   - **Passo 5:** Concluído!

3. **IMPORTANTE:** Delete o arquivo `public/install.php` após a instalação!

```bash
rm public/install.php
```

---

## 🔧 Instalação Manual

### Passo 1: Instalar Dependências

```bash
# Clone o repositório
git clone https://github.com/seu-usuario/ponto-eletronico.git
cd ponto-eletronico

# Executar script de dependências
chmod +x install-dependencies.sh
./install-dependencies.sh
```

### Passo 2: Configurar Ambiente

```bash
# Copiar arquivo de configuração
cp .env.example .env

# Editar configurações
nano .env
```

**Configurações essenciais no .env:**

```ini
#--------------------------------------------------------------------
# APP
#--------------------------------------------------------------------
app.baseURL = 'http://pontoeletronico.local'
CI_ENVIRONMENT = production

#--------------------------------------------------------------------
# DATABASE
#--------------------------------------------------------------------
database.default.hostname = localhost
database.default.database = ponto_eletronico
database.default.username = seu_usuario
database.default.password = sua_senha_segura
database.default.port = 3306

#--------------------------------------------------------------------
# ENCRYPTION
#--------------------------------------------------------------------
encryption.key = base64:SUA_CHAVE_GERADA_AQUI

#--------------------------------------------------------------------
# COMPANY
#--------------------------------------------------------------------
company.name = 'Sua Empresa LTDA'
company.cnpj = '00.000.000/0000-00'
```

### Passo 3: Gerar Chave de Criptografia

```bash
# Gerar chave aleatória segura
php -r "echo 'encryption.key = base64:' . base64_encode(random_bytes(32)) . PHP_EOL;"

# Copie a saída e cole no arquivo .env
```

### Passo 4: Criar Banco de Dados

```bash
# Conectar ao MySQL
mysql -u root -p

# Criar banco de dados
CREATE DATABASE ponto_eletronico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Criar usuário (opcional)
CREATE USER 'ponto_user'@'localhost' IDENTIFIED BY 'senha_forte_aqui';
GRANT ALL PRIVILEGES ON ponto_eletronico.* TO 'ponto_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Passo 5: Executar Migrations

```bash
# Executar todas as migrations
php spark migrate

# Verificar se foi executado corretamente
php spark migrate:status
```

### Passo 6: Criar Usuário Administrador

```bash
# Executar seeder do admin
php spark db:seed AdminSeeder

# Credenciais padrão:
# Email: admin@pontoeletronico.com.br
# Senha: Admin@123
# IMPORTANTE: Altere após o primeiro login!
```

### Passo 7: Configurar Permissões

```bash
# Permissões dos diretórios
chmod -R 755 writable/ storage/ public/uploads/
chmod -R 777 writable/cache writable/logs writable/session

# Verificar proprietário (Apache/Nginx)
sudo chown -R www-data:www-data .
```

---

## 🐳 Instalação Docker

### Passo 1: Verificar Requisitos

```bash
# Verificar Docker
docker --version  # 20.10+

# Verificar Docker Compose
docker-compose --version  # 2.0+
```

### Passo 2: Executar Script de Instalação

```bash
# Clone o repositório
git clone https://github.com/seu-usuario/ponto-eletronico.git
cd ponto-eletronico

# Executar instalador Docker
chmod +x scripts/install.sh
./scripts/install.sh
```

### Passo 3: Serviços Disponíveis

Após a instalação, os seguintes serviços estarão disponíveis:

| Serviço | URL | Descrição |
|---------|-----|-----------|
| **App Web** | http://localhost | Aplicação principal |
| **DeepFace API** | http://localhost:5000 | Reconhecimento facial |
| **PHPMyAdmin** | http://localhost:8080 | Gerenciamento MySQL |
| **Mailhog** | http://localhost:8025 | Captura de emails (dev) |

### Comandos Docker Úteis

```bash
# Ver logs
docker-compose logs -f

# Parar serviços
docker-compose stop

# Reiniciar serviços
docker-compose restart

# Remover tudo
docker-compose down -v

# Executar comandos dentro do container
docker-compose exec php php spark migrate
docker-compose exec php php spark db:seed AdminSeeder
```

---

## ✅ Pós-Instalação

### 1. Testar a Instalação

```bash
# Acessar a página inicial
curl http://localhost

# Verificar migrations
php spark migrate:status

# Verificar permissões
ls -la writable/
```

### 2. Configurações Iniciais

Após fazer login como administrador, configure:

1. **Informações da Empresa** (`/settings`)
   - Nome, CNPJ, endereço
   - Logo da empresa
   - Cores personalizadas

2. **Jornada de Trabalho** (`/settings`)
   - Horário padrão (ex: 08:00 - 17:00)
   - Horas diárias esperadas (ex: 8h)
   - Tolerância de atraso (ex: 10 minutos)

3. **Geofences** (`/geofence`)
   - Criar cercas virtuais para locais de trabalho
   - Definir raio permitido para registros remotos

4. **Biometria** (`/settings`)
   - Configurar URL da DeepFace API
   - Definir threshold de reconhecimento (padrão: 0.40)
   - Ativar/desativar anti-spoofing

5. **Email/Notificações** (`/settings`)
   - Configurar SMTP (Gmail, SendGrid, etc.)
   - Testar envio de emails
   - Configurar templates

6. **LGPD** (`/settings`)
   - Definir DPO (Data Protection Officer)
   - Configurar períodos de retenção de dados
   - Revisar políticas de privacidade

### 3. Cadastrar Funcionários

1. Acesse `/employees/create`
2. Preencha dados pessoais e de trabalho
3. Cadastre biometria (facial e/ou digital)
4. Gere QR Code personalizado

### 4. Configurar Servidor de Produção

**SSL/HTTPS (Let's Encrypt):**
```bash
sudo apt-get install certbot python3-certbot-apache
sudo certbot --apache -d pontoeletronico.seudominio.com
```

**WebSocket (Systemd):**
```bash
sudo cp config/systemd/websocket-chat.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable websocket-chat
sudo systemctl start websocket-chat
```

**Backup Automático (Cron):**
```bash
# Editar crontab
crontab -e

# Adicionar linha:
0 3 * * * cd /var/www/ponto-eletronico && php spark backup:database
0 4 * * * cd /var/www/ponto-eletronico && php spark cleanup:qrcodes
```

---

## 🔍 Solução de Problemas

### Problema: Erro 500 (Internal Server Error)

**Solução:**
```bash
# Verificar logs
tail -f writable/logs/log-$(date +%Y-%m-%d).log

# Verificar permissões
chmod -R 755 writable/
chmod -R 777 writable/cache writable/logs

# Limpar cache
php spark cache:clear
```

### Problema: Banco de dados não conecta

**Solução:**
```bash
# Testar conexão MySQL
mysql -h localhost -u ponto_user -p ponto_eletronico

# Verificar .env
cat .env | grep database

# Verificar firewall
sudo ufw allow 3306
```

### Problema: Composer dependencies not found

**Solução:**
```bash
# Reinstalar dependências
rm -rf vendor/
composer install --no-dev --optimize-autoloader

# Verificar autoload
composer dump-autoload -o
```

### Problema: DeepFace API não responde

**Solução:**
```bash
# Verificar se está rodando
curl http://localhost:5000/health

# Reiniciar container Docker
docker-compose restart deepface

# Verificar logs
docker-compose logs deepface
```

### Problema: WebSocket não conecta

**Solução:**
```bash
# Verificar se servidor WebSocket está rodando
ps aux | grep workerman

# Iniciar manualmente
php websocket-server.php start

# Verificar porta
netstat -tuln | grep 2346

# Verificar firewall
sudo ufw allow 2346
```

---

## 📚 Próximos Passos

### 1. Ler Documentação

- [README.md](README.md) - Visão geral do projeto
- [WEBSOCKET-CHAT.md](WEBSOCKET-CHAT.md) - Sistema de chat
- [CONSOLIDACAO_CONTROLLERS.md](CONSOLIDACAO_CONTROLLERS.md) - Arquitetura
- [ANALISE_COMPLETA_FASES_0_14.md](ANALISE_COMPLETA_FASES_0_14.md) - Análise técnica

### 2. Testes

```bash
# Executar testes unitários (Fase 15)
vendor/bin/phpunit

# Testes de integração (Fase 16)
vendor/bin/phpunit --testsuite=Integration

# Testes de aceitação (Fase 17)
vendor/bin/phpunit --testsuite=Acceptance
```

### 3. Deploy em Produção

Consulte: [DEPLOYMENT.md](DEPLOYMENT.md) para guia completo de deploy

### 4. Configurações Avançadas

- Configurar Redis para cache
- Configurar queue workers (background jobs)
- Configurar S3/FTP para backups remotos
- Configurar CDN para assets estáticos

---

## 📞 Suporte

**Documentação:** [GitHub Wiki](https://github.com/seu-usuario/ponto-eletronico/wiki)
**Issues:** [GitHub Issues](https://github.com/seu-usuario/ponto-eletronico/issues)
**Email:** suporte@pontoeletronico.com.br

---

## 📄 Licença

Este projeto está licenciado sob a [MIT License](LICENSE).

---

## ⚖️ Conformidade Legal

✅ Portaria MTE nº 671/2021
✅ CLT (Consolidação das Leis do Trabalho)
✅ LGPD Lei 13.709/2018
✅ ICP-Brasil (Certificação Digital)

---

**Última Atualização:** 2025-11-16
**Versão do Documento:** 1.0
