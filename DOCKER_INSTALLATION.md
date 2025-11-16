# 🐳 Guia de Instalação Docker - Ponto Eletrônico

**Versão:** 1.0
**Data:** 2025-11-16
**Método:** Instalação via Docker e Docker Compose

---

## 📋 Índice

1. [Por que usar Docker?](#por-que-usar-docker)
2. [Pré-requisitos](#pré-requisitos)
3. [Instalação do Docker](#instalação-do-docker)
4. [Instalação do Sistema](#instalação-do-sistema)
5. [Serviços Disponíveis](#serviços-disponíveis)
6. [Comandos Úteis](#comandos-úteis)
7. [Troubleshooting](#troubleshooting)

---

## 🎯 Por que usar Docker?

**Vantagens da instalação Docker:**

✅ **Ambiente isolado** - Não interfere com seu sistema operacional
✅ **Fácil de instalar** - Tudo configurado automaticamente
✅ **Portável** - Funciona em Windows, Linux e macOS
✅ **Desenvolvimento e Produção** - Ambiente consistente
✅ **Serviços integrados** - MySQL, Redis, DeepFace, Nginx já configurados
✅ **Fácil de remover** - Um comando remove tudo sem deixar rastros

---

## 🖥️ Pré-requisitos

### Requisitos Mínimos de Hardware

- **RAM:** 4 GB mínimo, 8 GB recomendado
- **Espaço em Disco:** 10 GB livre
- **Processador:** x86_64 (64 bits)

### Sistemas Operacionais Suportados

- **Linux:** Ubuntu 20.04+, Debian 10+, CentOS 8+, Fedora 34+
- **macOS:** 10.15+ (Catalina ou superior)
- **Windows:** Windows 10/11 Pro, Enterprise ou Education (com WSL 2)

---

## 🔧 Instalação do Docker

### Linux (Ubuntu/Debian)

```bash
# 1. Atualizar pacotes
sudo apt-get update

# 2. Instalar dependências
sudo apt-get install -y \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg \
    lsb-release

# 3. Adicionar chave GPG oficial do Docker
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

# 4. Adicionar repositório Docker
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu \
  $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

# 5. Instalar Docker Engine
sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# 6. Verificar instalação
sudo docker --version
sudo docker compose version

# 7. Adicionar usuário ao grupo docker (para não precisar de sudo)
sudo usermod -aG docker $USER
newgrp docker

# 8. Testar Docker
docker run hello-world
```

### Linux (CentOS/RHEL/Fedora)

```bash
# 1. Instalar dependências
sudo yum install -y yum-utils

# 2. Adicionar repositório Docker
sudo yum-config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo

# 3. Instalar Docker
sudo yum install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# 4. Iniciar Docker
sudo systemctl start docker
sudo systemctl enable docker

# 5. Verificar instalação
sudo docker --version
sudo docker compose version
```

### macOS

```bash
# Opção 1: Docker Desktop (Recomendado)
# Baixe e instale: https://www.docker.com/products/docker-desktop

# Opção 2: Homebrew
brew install --cask docker

# Verificar instalação
docker --version
docker compose version
```

### Windows

**1. Habilitar WSL 2:**
```powershell
# Execute PowerShell como Administrador
wsl --install
wsl --set-default-version 2
```

**2. Instalar Docker Desktop:**
- Baixe: https://www.docker.com/products/docker-desktop
- Execute o instalador
- Reinicie o computador
- Abra Docker Desktop e aguarde iniciar

**3. Verificar instalação:**
```powershell
docker --version
docker compose version
```

---

## 🚀 Instalação do Sistema

### Passo 1: Clonar o Repositório

```bash
# Clone o repositório
git clone https://github.com/mumufoco/Support-Solo-Sondagens---El-tronico-Registro-de-Ponto.git

# Entre no diretório
cd Support-Solo-Sondagens---El-tronico-Registro-de-Ponto
```

### Passo 2: Executar Script de Instalação

**Linux/macOS:**
```bash
# Tornar script executável
chmod +x scripts/install.sh

# Executar instalação
./scripts/install.sh
```

**Windows (PowerShell/Git Bash):**
```bash
bash scripts/install.sh
```

### Passo 3: Seguir o Assistente de Instalação

O script executará automaticamente:

#### 📌 **Etapa 1: Verificação de Requisitos**
```
✓ Docker encontrado: Docker version 24.0.7
✓ Docker Compose encontrado: Docker Compose version v2.23.0
✓ Git encontrado: git version 2.34.1
```

#### 📌 **Etapa 2: Configuração do Ambiente**

Se `.env` não existir, será criado a partir de `.env.example`:

```bash
→ Configurando ambiente...
✓ Arquivo .env criado a partir de .env.example

IMPORTANTE: Edite o arquivo .env e configure:
  - Senhas do banco de dados
  - Chave de criptografia
  - Credenciais de email
  - URL base da aplicação

Pressione Enter após configurar o .env...
```

**Edite o arquivo .env:**
```bash
nano .env
```

**Configurações essenciais:**
```ini
#--------------------------------------------------------------------
# APP
#--------------------------------------------------------------------
app.baseURL = 'http://localhost'  # ou seu domínio
CI_ENVIRONMENT = production

#--------------------------------------------------------------------
# DATABASE
#--------------------------------------------------------------------
database.default.hostname = mysql
database.default.database = ponto_eletronico
database.default.username = ponto_user
database.default.password = SuaSenhaSuperForte123!
database.default.port = 3306

#--------------------------------------------------------------------
# REDIS
#--------------------------------------------------------------------
cache.redis.host = redis
cache.redis.password = OutraSenhaForte456!
cache.redis.port = 6379

#--------------------------------------------------------------------
# DEEPFACE API
#--------------------------------------------------------------------
deepface.api.url = http://deepface:5000

#--------------------------------------------------------------------
# ENCRYPTION (será gerado automaticamente)
#--------------------------------------------------------------------
encryption.key = base64:SUA_CHAVE_SERA_GERADA_AQUI

#--------------------------------------------------------------------
# COMPANY
#--------------------------------------------------------------------
company.name = 'Sua Empresa LTDA'
company.cnpj = '00.000.000/0000-00'
company.address = 'Rua Exemplo, 123'
company.city = 'São Paulo'
company.state = 'SP'
company.phone = '(11) 1234-5678'
```

Pressione **Enter** para continuar após salvar.

#### 📌 **Etapa 3: Geração de Chave de Criptografia**
```
→ Gerando chave de criptografia...
✓ Chave de criptografia gerada
```

#### 📌 **Etapa 4: Criação de Diretórios**
```
→ Criando diretórios necessários...
✓ Diretórios criados com permissões corretas
```

#### 📌 **Etapa 5: Instalação de Dependências**
```
→ Instalando dependências do Composer...
[Container temporário instalando pacotes...]
✓ Dependências do Composer instaladas
```

#### 📌 **Etapa 6: Build de Imagens Docker**
```
→ Construindo imagens Docker...
[Building php-app...]
[Building deepface-api...]
[Building nginx-server...]
✓ Imagens Docker construídas
```

Tempo estimado: **5-10 minutos** (primeira vez)

#### 📌 **Etapa 7: Iniciar Serviços**
```
→ Iniciando serviços...
Creating network "ponto_network" with driver "bridge"
Creating volume "mysql_data" with local driver
Creating volume "redis_data" with local driver
Creating ponto_mysql ... done
Creating ponto_redis ... done
Creating ponto_deepface ... done
Creating ponto_php ... done
Creating ponto_nginx ... done
✓ Serviços iniciados

→ Aguardando MySQL inicializar...
[Aguarda 10 segundos...]

NAME              IMAGE                    STATUS         PORTS
ponto_mysql       mysql:8.0                Up 15 seconds  0.0.0.0:3306->3306/tcp
ponto_redis       redis:7-alpine           Up 15 seconds  0.0.0.0:6379->6379/tcp
ponto_deepface    ponto_deepface:latest    Up 10 seconds  0.0.0.0:5000->5000/tcp
ponto_php         ponto_php:latest         Up 10 seconds
ponto_nginx       ponto_nginx:latest       Up 8 seconds   0.0.0.0:80->80/tcp, 0.0.0.0:443->443/tcp
```

#### 📌 **Etapa 8: Executar Migrations**
```
→ Executando migrações do banco de dados...
CodeIgniter v4.6.3 Command Line Tool - Server Time: 2025-11-16 12:00:00 UTC+00:00

Running all new migrations...

Migration: 2024-01-01-000001_CreateEmployeesTable
  Migrated: 2024-01-01-000001_CreateEmployeesTable

Migration: 2024-01-01-000002_CreateTimePunchesTable
  Migrated: 2024-01-01-000002_CreateTimePunchesTable

[... todas as migrations ...]

✓ Migrações executadas
```

#### 📌 **Etapa 9: Executar Seeders (Opcional)**
```
→ Executando seeders...
Deseja executar os seeders (admin, settings, geofences)? (s/N): s

AdminSeeder ................................................... ✓
SettingsSeeder ................................................ ✓
GeofenceSeeder ................................................ ✓

✓ Seeders executados

→ Credenciais padrão:
  Email: admin@pontoeletronico.com.br
  Senha: Admin@123
  IMPORTANTE: Altere a senha após o primeiro login!
```

#### 📌 **Etapa 10: Instalação Concluída!**
```
======================================================================
  ✓ Instalação Concluída com Sucesso!
======================================================================

Próximos passos:
  1. Acesse: http://localhost
  2. Faça login com as credenciais do administrador
  3. Altere a senha padrão
  4. Configure os geofences da empresa
  5. Cadastre os funcionários

Serviços disponíveis:
  - Aplicação Web: http://localhost
  - DeepFace API: http://localhost:5000
  - PHPMyAdmin: http://localhost:8080 (profile: development)
  - Mailhog: http://localhost:8025 (profile: development)

Comandos úteis:
  - Ver logs: docker-compose logs -f
  - Parar: docker-compose stop
  - Reiniciar: docker-compose restart
  - Remover: docker-compose down
```

---

## 🌐 Serviços Disponíveis

Após a instalação, os seguintes serviços estarão rodando:

| Serviço | URL | Descrição | Porta |
|---------|-----|-----------|-------|
| **Aplicação Web** | http://localhost | Sistema principal | 80, 443 |
| **DeepFace API** | http://localhost:5000 | Reconhecimento facial | 5000 |
| **MySQL** | localhost:3306 | Banco de dados | 3306 |
| **Redis** | localhost:6379 | Cache | 6379 |
| **PHPMyAdmin** | http://localhost:8080 | Admin MySQL (dev) | 8080 |
| **Mailhog** | http://localhost:8025 | Teste emails (dev) | 8025, 1025 |

### Serviços de Desenvolvimento (Opcional)

Para habilitar PHPMyAdmin e Mailhog:

```bash
# Parar serviços atuais
docker-compose stop

# Iniciar com perfil development
docker-compose --profile development up -d

# Verificar
docker-compose ps
```

---

## 🔧 Comandos Úteis

### Gerenciamento de Containers

```bash
# Ver status dos containers
docker-compose ps

# Ver logs de todos os serviços
docker-compose logs -f

# Ver logs de um serviço específico
docker-compose logs -f php
docker-compose logs -f nginx
docker-compose logs -f mysql

# Parar serviços (não remove containers)
docker-compose stop

# Iniciar serviços parados
docker-compose start

# Reiniciar serviços
docker-compose restart

# Parar e remover containers (mantém volumes)
docker-compose down

# Parar e remover tudo (incluindo volumes - CUIDADO!)
docker-compose down -v
```

### Executar Comandos dentro dos Containers

```bash
# Acessar shell do PHP
docker-compose exec php bash

# Executar migrations
docker-compose exec php php spark migrate

# Executar seeders
docker-compose exec php php spark db:seed AdminSeeder

# Limpar cache
docker-compose exec php php spark cache:clear

# Verificar status das migrations
docker-compose exec php php spark migrate:status

# Criar novo controller
docker-compose exec php php spark make:controller NomeController

# Criar nova migration
docker-compose exec php php spark make:migration NomeMigration
```

### Backup e Restore do Banco de Dados

**Backup:**
```bash
# Backup do banco MySQL
docker-compose exec mysql mysqldump -u ponto_user -p ponto_eletronico > backup_$(date +%Y%m%d_%H%M%S).sql

# Ou usando docker diretamente
docker exec ponto_mysql mysqldump -u ponto_user -pponto_pass ponto_eletronico > backup.sql
```

**Restore:**
```bash
# Restore do banco
docker-compose exec -T mysql mysql -u ponto_user -p ponto_eletronico < backup.sql

# Ou usando docker diretamente
docker exec -i ponto_mysql mysql -u ponto_user -pponto_pass ponto_eletronico < backup.sql
```

### Monitoramento

```bash
# Ver uso de recursos (CPU, RAM)
docker stats

# Ver apenas containers do ponto eletrônico
docker stats ponto_php ponto_nginx ponto_mysql ponto_redis ponto_deepface

# Inspecionar container
docker inspect ponto_php

# Ver redes
docker network ls
docker network inspect ponto_network

# Ver volumes
docker volume ls
docker volume inspect mysql_data
```

---

## 🔍 Troubleshooting

### Problema 1: Porta 80 já está em uso

**Erro:**
```
ERROR: for nginx  Cannot start service nginx: driver failed programming external connectivity on endpoint ponto_nginx: Bind for 0.0.0.0:80 failed: port is already allocated
```

**Solução:**

```bash
# Opção 1: Parar serviço que está usando a porta 80
sudo systemctl stop apache2  # ou nginx
sudo systemctl disable apache2

# Opção 2: Alterar porta no docker-compose.yml
# Edite docker-compose.yml:
nginx:
  ports:
    - "8000:80"  # Altere de 80:80 para 8000:80
    - "443:443"

# Reinicie
docker-compose down
docker-compose up -d

# Acesse em: http://localhost:8000
```

### Problema 2: MySQL não inicia (port 3306 em uso)

**Solução:**

```bash
# Parar MySQL local
sudo systemctl stop mysql
sudo systemctl disable mysql

# Ou alterar porta no docker-compose.yml
mysql:
  ports:
    - "3307:3306"  # MySQL Docker na porta 3307
```

### Problema 3: Containers reiniciando constantemente

**Verificar logs:**
```bash
docker-compose logs mysql
docker-compose logs php
```

**Causas comuns:**
- **MySQL:** Senha incorreta, volume corrompido
- **PHP:** Erro no código, extensão faltando
- **DeepFace:** Falta de memória RAM

**Solução:**
```bash
# Remover volumes e recriar
docker-compose down -v
docker-compose up -d

# Se persistir, rebuild
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

### Problema 4: DeepFace API não responde

**Verificar:**
```bash
# Ver logs
docker-compose logs deepface

# Testar health check
curl http://localhost:5000/health
```

**Solução:**
```bash
# Reiniciar container
docker-compose restart deepface

# Se não funcionar, rebuild
docker-compose stop deepface
docker-compose rm -f deepface
docker-compose build --no-cache deepface
docker-compose up -d deepface
```

### Problema 5: Erro "Cannot connect to MySQL"

**Solução:**
```bash
# Aguardar MySQL estar pronto (pode levar 30-60s)
docker-compose logs mysql | grep "ready for connections"

# Verificar credenciais no .env
cat .env | grep database

# Verificar se MySQL está rodando
docker-compose ps mysql

# Testar conexão manual
docker-compose exec mysql mysql -u ponto_user -p
```

### Problema 6: Erro de permissão em arquivos

**Solução:**
```bash
# Dentro do container PHP
docker-compose exec php chown -R www-data:www-data /var/www/html/writable
docker-compose exec php chmod -R 755 /var/www/html/writable
docker-compose exec php chmod -R 777 /var/www/html/writable/cache
docker-compose exec php chmod -R 777 /var/www/html/writable/logs

# Ou no host (se volumes estiverem mapeados)
sudo chown -R $USER:$USER writable/
chmod -R 755 writable/
chmod -R 777 writable/cache writable/logs
```

### Problema 7: Containers ficam sem memória

**Verificar uso:**
```bash
docker stats
```

**Aumentar limite de memória:**

Edite `docker-compose.yml`:
```yaml
php:
  deploy:
    resources:
      limits:
        memory: 2G  # Aumentar de 1G para 2G
```

Reinicie:
```bash
docker-compose down
docker-compose up -d
```

---

## 🔒 Segurança em Produção

### SSL/HTTPS

**Usando Let's Encrypt:**

```bash
# 1. Certifique-se que domínio aponta para seu servidor
# 2. Instale certbot no HOST (não no container)
sudo apt-get install certbot

# 3. Gere certificado
sudo certbot certonly --standalone -d seudominio.com.br

# 4. Copie certificados para docker/nginx/ssl/
sudo cp /etc/letsencrypt/live/seudominio.com.br/fullchain.pem docker/nginx/ssl/
sudo cp /etc/letsencrypt/live/seudominio.com.br/privkey.pem docker/nginx/ssl/

# 5. Configure nginx.conf para usar SSL (já está configurado)
# 6. Reinicie nginx
docker-compose restart nginx
```

### Firewall

```bash
# Permitir apenas portas necessárias
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS
sudo ufw deny 3306/tcp   # MySQL (não expor)
sudo ufw deny 6379/tcp   # Redis (não expor)
sudo ufw enable
```

### Senhas Fortes

Altere as senhas padrão no `.env`:
```ini
database.default.password = SenhaSuper@Forte123!
cache.redis.password = OutraSenha@MuitoForte456!
```

---

## 📚 Próximos Passos

Após a instalação:

1. **Configurações Iniciais** (`/settings`)
   - Informações da empresa
   - Jornada de trabalho
   - Geofences
   - Biometria (DeepFace)
   - Email/SMTP
   - LGPD

2. **Cadastrar Funcionários** (`/employees/create`)
   - Dados pessoais
   - Biometria facial
   - QR Code

3. **Configurar Backup Automático**
   ```bash
   # Adicionar ao crontab do HOST
   crontab -e

   # Backup diário às 3h
   0 3 * * * cd /caminho/para/projeto && docker-compose exec -T mysql mysqldump -u ponto_user -pponto_pass ponto_eletronico > /backups/ponto_$(date +\%Y\%m\%d).sql
   ```

4. **Monitoramento**
   - Configure alertas de disco cheio
   - Configure alertas de serviços down
   - Monitore logs: `docker-compose logs -f --tail=100`

---

## 📞 Suporte

**Documentação:** [INSTALLATION.md](INSTALLATION.md)
**Issues:** [GitHub Issues](https://github.com/mumufoco/Support-Solo-Sondagens---El-tronico-Registro-de-Ponto/issues)
**Email:** suporte@pontoeletronico.com.br

---

## ⚖️ Conformidade Legal

✅ Portaria MTE nº 671/2021
✅ CLT (Consolidação das Leis do Trabalho)
✅ LGPD Lei 13.709/2018
✅ ICP-Brasil (Certificação Digital)

---

**Última Atualização:** 2025-11-16
**Versão do Documento:** 1.0
**Compatível com:** Docker 20.10+, Docker Compose 2.0+
