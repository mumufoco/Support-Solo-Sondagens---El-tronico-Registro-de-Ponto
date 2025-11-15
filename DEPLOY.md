# Deployment Guide - Sistema de Registro de Ponto Eletrônico

Guia completo de implantação do Sistema de Registro de Ponto Eletrônico com conformidade LGPD e Portaria MTE 671/2021.

## Índice

1. [Requisitos do Sistema](#requisitos-do-sistema)
2. [Instalação Rápida (Docker)](#instalação-rápida-docker)
3. [Instalação Manual](#instalação-manual)
4. [Configuração](#configuração)
5. [Backup e Restauração](#backup-e-restauração)
6. [Atualização](#atualização)
7. [Monitoramento](#monitoramento)
8. [Resolução de Problemas](#resolução-de-problemas)

---

## Requisitos do Sistema

### Mínimos (Para até 50 funcionários)
- **CPU**: 2 cores
- **RAM**: 4 GB
- **Disco**: 20 GB SSD
- **SO**: Linux Ubuntu 20.04+ ou CentOS 8+

### Recomendados (Para até 200 funcionários)
- **CPU**: 4 cores
- **RAM**: 8 GB
- **Disco**: 50 GB SSD
- **SO**: Linux Ubuntu 22.04 LTS

### Software Necessário
- Docker 20.10+
- Docker Compose 2.0+
- Git 2.30+
- OpenSSL 1.1+

---

## Instalação Rápida (Docker)

### 1. Clone o Repositório

```bash
git clone https://github.com/seu-usuario/ponto-eletronico.git
cd ponto-eletronico
```

### 2. Execute o Script de Instalação

```bash
./scripts/install.sh
```

O script irá:
- ✅ Verificar requisitos
- ✅ Configurar ambiente (.env)
- ✅ Gerar chave de criptografia
- ✅ Instalar dependências
- ✅ Construir imagens Docker
- ✅ Iniciar serviços
- ✅ Executar migrações
- ✅ Criar usuário administrador

### 3. Acesse o Sistema

Aguarde alguns segundos e acesse:
- **Aplicação Web**: http://localhost
- **DeepFace API**: http://localhost:5000
- **PHPMyAdmin**: http://localhost:8080 (dev)

**Credenciais padrão:**
- Email: `admin@pontoeletronico.com.br`
- Senha: `Admin@123`

⚠️ **IMPORTANTE**: Altere a senha imediatamente após o primeiro login!

---

## Instalação Manual

### 1. Preparar o Ambiente

```bash
# Atualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar Docker
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER

# Instalar Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Reiniciar sessão
newgrp docker
```

### 2. Clonar e Configurar

```bash
# Clone o repositório
git clone https://github.com/seu-usuario/ponto-eletronico.git
cd ponto-eletronico

# Copiar arquivo de ambiente
cp .env.production .env

# Editar configurações
nano .env
```

### 3. Configurar .env

Edite os seguintes campos no arquivo `.env`:

```ini
# Banco de Dados
DB_PASSWORD=SENHA_SEGURA_AQUI
REDIS_PASSWORD=SENHA_REDIS_AQUI

# Aplicação
app.baseURL = 'https://seu-dominio.com.br/'
encryption.key = 'GERE_COM_php_spark_key:generate'

# Email
email.SMTPHost = 'smtp.seu-provedor.com'
email.SMTPUser = 'seu-email@dominio.com'
email.SMTPPass = 'sua-senha-app'

# DeepFace API
DEEPFACE_API_KEY = 'CHAVE_SEGURA_AQUI'

# Empresa (MTE 671/2021)
COMPANY_NAME = 'Nome da Sua Empresa'
COMPANY_CNPJ = '00.000.000/0001-00'
COMPANY_ADDRESS = 'Endereço Completo'
```

### 4. Gerar Chave de Criptografia

```bash
# Opção 1: Com PHP instalado
php spark key:generate

# Opção 2: Com OpenSSL
openssl rand -base64 32
# Copie o resultado para encryption.key no .env
```

### 5. Criar Diretórios

```bash
mkdir -p writable/{cache,logs,session,uploads,backups}
mkdir -p public/uploads
chmod -R 777 writable/
chmod -R 755 public/
```

### 6. Instalar Dependências

```bash
docker run --rm -v $(pwd):/app composer:latest install --no-dev --optimize-autoloader
```

### 7. Construir e Iniciar

```bash
# Construir imagens
docker-compose build

# Iniciar serviços
docker-compose up -d

# Verificar status
docker-compose ps
```

### 8. Migrar Banco de Dados

```bash
# Executar migrações
docker-compose exec php php spark migrate

# Executar seeders
docker-compose exec php php spark db:seed AdminSeeder
docker-compose exec php php spark db:seed SettingsSeeder
docker-compose exec php php spark db:seed GeofenceSeeder
```

---

## Configuração

### SSL/HTTPS (Produção)

#### Com Let's Encrypt (Certbot)

```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-nginx

# Obter certificado
sudo certbot --nginx -d seu-dominio.com.br -d www.seu-dominio.com.br

# Renovação automática (já configurado)
sudo systemctl status certbot.timer
```

#### Configurar Nginx

Edite `docker/nginx/default.conf` e descomente a seção HTTPS:

```nginx
server {
    listen 443 ssl http2;
    server_name seu-dominio.com.br;

    ssl_certificate /etc/nginx/ssl/fullchain.pem;
    ssl_certificate_key /etc/nginx/ssl/privkey.pem;

    # ... resto da configuração
}
```

Copie os certificados:

```bash
sudo mkdir -p docker/nginx/ssl
sudo cp /etc/letsencrypt/live/seu-dominio.com.br/fullchain.pem docker/nginx/ssl/
sudo cp /etc/letsencrypt/live/seu-dominio.com.br/privkey.pem docker/nginx/ssl/
```

Reinicie:

```bash
docker-compose restart nginx
```

### Configurar Firewall

```bash
# UFW (Ubuntu)
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 22/tcp
sudo ufw enable

# Ou iptables
sudo iptables -A INPUT -p tcp --dport 80 -j ACCEPT
sudo iptables -A INPUT -p tcp --dport 443 -j ACCEPT
sudo iptables -save
```

### Configurar Email

Para Gmail:

1. Ative a verificação em 2 etapas
2. Gere uma senha de app: https://myaccount.google.com/apppasswords
3. Use a senha de app no `.env`

```ini
email.SMTPHost = 'smtp.gmail.com'
email.SMTPUser = 'seu-email@gmail.com'
email.SMTPPass = 'senha-app-gerada'
email.SMTPPort = 587
email.SMTPCrypto = 'tls'
```

### Configurar Geofences

1. Acesse: **Admin > Configurações > Geofences**
2. Clique em **Adicionar Geofence**
3. Informe:
   - Nome: "Escritório Principal"
   - Latitude/Longitude (use Google Maps)
   - Raio em metros (ex: 100)
4. Salve

---

## Backup e Restauração

### Backup Manual

```bash
# Criar backup completo
./scripts/backup.sh
```

O backup inclui:
- ✅ Dump do banco de dados MySQL
- ✅ Arquivos enviados (uploads)
- ✅ Biometrias faciais (DeepFace)
- ✅ Configurações (.env)
- ✅ Manifesto com checksums

Backups são salvos em: `writable/backups/ponto_backup_YYYYMMDD_HHMMSS.tar.gz`

### Backup Automático (Cron)

```bash
# Editar crontab
crontab -e

# Adicionar linha (backup diário às 2h)
0 2 * * * cd /path/to/ponto-eletronico && ./scripts/backup.sh >> /var/log/ponto-backup.log 2>&1
```

### Restauração

```bash
# Listar backups disponíveis
ls -lh writable/backups/

# Restaurar backup específico
./scripts/restore.sh writable/backups/ponto_backup_20240115_020000.tar.gz
```

### Backup Remoto (S3/FTP)

Adicione ao `.env`:

```ini
BACKUP_REMOTE_PATH=user@servidor:/backups/ponto
# ou
BACKUP_S3_BUCKET=s3://meu-bucket/backups/
```

---

## Atualização

### Atualização Automática

```bash
./scripts/update.sh
```

O script irá:
1. ✅ Ativar modo de manutenção
2. ✅ Criar backup pré-atualização
3. ✅ Baixar atualizações do Git
4. ✅ Atualizar dependências
5. ✅ Reconstruir imagens Docker
6. ✅ Executar migrações
7. ✅ Limpar cache
8. ✅ Reiniciar serviços
9. ✅ Verificar atualização
10. ✅ Desativar modo de manutenção

### Atualização Manual

```bash
# 1. Ativar manutenção
touch writable/maintenance.lock

# 2. Fazer backup
./scripts/backup.sh

# 3. Atualizar código
git pull origin main

# 4. Atualizar dependências
docker-compose exec php composer update --no-dev

# 5. Migrar banco
docker-compose exec php php spark migrate

# 6. Limpar cache
docker-compose exec php php spark cache:clear

# 7. Reiniciar
docker-compose restart

# 8. Desativar manutenção
rm writable/maintenance.lock
```

---

## Monitoramento

### Logs

```bash
# Ver todos os logs
docker-compose logs -f

# Logs específicos
docker-compose logs -f nginx
docker-compose logs -f php
docker-compose logs -f mysql
docker-compose logs -f deepface

# Logs da aplicação
tail -f writable/logs/log-$(date +%Y-%m-%d).log
```

### Métricas de Performance

```bash
# Status dos containers
docker stats

# Uso de disco
df -h
du -sh writable/*

# Conexões MySQL
docker-compose exec mysql mysqladmin -u root -p processlist

# Cache Redis
docker-compose exec redis redis-cli -a senha INFO stats
```

### Health Checks

```bash
# Verificar serviços
curl http://localhost/health
curl http://localhost:5000/health

# Verificar banco de dados
docker-compose exec php php spark db:check
```

### Monitoramento Externo (Opcional)

#### Uptime Robot
- Adicione: https://seu-dominio.com.br/health
- Intervalo: 5 minutos

#### Sentry (Erros)
1. Crie conta: https://sentry.io
2. Adicione ao `.env`:

```ini
SENTRY_DSN=https://xxx@sentry.io/xxx
SENTRY_ENVIRONMENT=production
```

---

## Resolução de Problemas

### Erro: "Cannot connect to MySQL"

```bash
# Verificar se MySQL está rodando
docker-compose ps mysql

# Ver logs do MySQL
docker-compose logs mysql

# Reiniciar MySQL
docker-compose restart mysql

# Aguardar 10 segundos
sleep 10

# Testar conexão
docker-compose exec mysql mysql -u root -p
```

### Erro: "Permission denied" em writable/

```bash
# Corrigir permissões
sudo chmod -R 777 writable/
sudo chown -R www-data:www-data writable/
```

### DeepFace não responde

```bash
# Verificar logs
docker-compose logs deepface

# Reiniciar serviço
docker-compose restart deepface

# Aguardar inicialização
sleep 15

# Testar
curl http://localhost:5000/health
```

### Página em branco (500 Error)

```bash
# Ver logs do PHP
docker-compose logs php
tail -f writable/logs/log-$(date +%Y-%m-%d).log

# Verificar permissões
ls -la writable/

# Limpar cache
docker-compose exec php php spark cache:clear
rm -rf writable/cache/*
```

### Desempenho lento

```bash
# Verificar recursos
docker stats

# Otimizar banco de dados
docker-compose exec mysql mysqlcheck -u root -p --optimize --all-databases

# Limpar logs antigos
find writable/logs/ -name "*.log" -mtime +30 -delete

# Aumentar OPcache (docker/php/opcache.ini)
opcache.memory_consumption = 256
```

### Restaurar de Backup

```bash
# Em caso de falha crítica
docker-compose down
./scripts/restore.sh writable/backups/ultimo_backup.tar.gz
docker-compose up -d
```

---

## Comandos Úteis

```bash
# Parar todos os serviços
docker-compose stop

# Iniciar serviços
docker-compose start

# Reiniciar serviços
docker-compose restart

# Ver logs em tempo real
docker-compose logs -f

# Executar comando no container PHP
docker-compose exec php php spark [comando]

# Acessar shell do container
docker-compose exec php bash

# Remover tudo (CUIDADO!)
docker-compose down -v

# Limpar imagens não utilizadas
docker system prune -a
```

---

## Segurança

### Checklist de Segurança Pré-Produção

- [ ] Senhas fortes configuradas (DB, Redis, Admin)
- [ ] HTTPS/SSL configurado
- [ ] Firewall configurado (portas 80, 443, 22)
- [ ] Backup automático configurado
- [ ] Logs configurados e monitorados
- [ ] Email funcional (testes enviados)
- [ ] Geofences configuradas
- [ ] LGPD: DPO configurado
- [ ] Usuário admin senha alterada
- [ ] Arquivos sensíveis protegidos (.env, composer.json)

### Hardening

```bash
# Desabilitar informações do PHP
# Em docker/php/php.ini:
expose_php = Off

# Desabilitar funções perigosas do PHP
disable_functions = exec,passthru,shell_exec,system,proc_open,popen

# Desabilitar listagem de diretórios Nginx
autoindex off;

# Adicionar headers de segurança
# Em docker/nginx/default.conf já incluídos:
# X-Frame-Options, X-Content-Type-Options, X-XSS-Protection
```

---

## Suporte

Para suporte, entre em contato:
- **Email**: suporte@pontoeletronico.com.br
- **Telefone**: +55 (11) 9999-9999
- **Documentação**: https://docs.pontoeletronico.com.br

---

**Desenvolvido por Support Solo Sondagens**
**Versão**: 1.0.0
**Data**: Novembro 2024
