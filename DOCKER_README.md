# 🐳 Docker - Sistema de Ponto Eletrônico Brasileiro

Guia completo para executar o sistema usando Docker e Docker Compose.

---

## 📋 Pré-requisitos

- **Docker Engine** 20.10+ ([Instalar Docker](https://docs.docker.com/engine/install/))
- **Docker Compose V2** (plugin) ([Migrar para V2](./DOCKER_SETUP_FIX.md))
- **Mínimo**: 4GB RAM, 10GB disco
- **Recomendado**: 8GB RAM, 20GB disco

### Verificar Instalação

```bash
docker --version
# Docker version 20.10.x ou superior

docker compose version
# Docker Compose version v2.x.x
```

---

## 🚀 Início Rápido (Quick Start)

### 1. Clonar o Repositório

```bash
git clone https://github.com/seu-usuario/ponto-eletronico.git
cd ponto-eletronico
```

### 2. Configurar Variáveis de Ambiente

Copie o arquivo `.env.example` para `.env` e configure:

```bash
cp .env.example .env
```

**IMPORTANTE**: Edite o `.env` e altere os seguintes valores:

```env
# Senhas (MUDE ESTAS!)
DB_PASSWORD=SuaSenhaMySQLForte123!
REDIS_PASSWORD=SuaSenhaRedisForte456!
DEEPFACE_API_KEY=SuaChaveAPISecreta789!

# Encryption Key (gere uma chave de 32 caracteres)
ENCRYPTION_KEY=base64:SEU-32-CARACTERES-AQUI==

# Banco de Dados
DB_DATABASE=ponto_eletronico
DB_USERNAME=ponto_user

# Ambiente
CI_ENVIRONMENT=production  # ou 'development' para testes
```

### 3. Iniciar Todos os Serviços

```bash
# Produção (apenas serviços essenciais)
docker compose up -d

# Desenvolvimento (inclui PHPMyAdmin, Mailhog, Redis Commander)
docker compose --profile development up -d
```

### 4. Aguardar Inicialização

```bash
# Verificar status dos containers
docker compose ps

# Ver logs em tempo real
docker compose logs -f
```

### 5. Acessar a Aplicação

- **Web App**: http://localhost
- **DeepFace API Health**: http://localhost:5000/health (interno)

**Ferramentas de Desenvolvimento** (se `--profile development`):
- **PHPMyAdmin**: http://localhost:8080
- **Mailhog**: http://localhost:8025
- **Redis Commander**: http://localhost:8081

---

## 📦 Serviços Incluídos

| Serviço | Container | Portas | Descrição |
|---------|-----------|--------|-----------|
| **app** | `ponto_app` | 80, 443 | Aplicação PHP + Nginx + PHP-FPM |
| **mysql** | `ponto_mysql` | 3306 | Banco de dados MySQL 8.0 |
| **redis** | `ponto_redis` | 6379 | Cache e sessões |
| **deepface** | `ponto_deepface` | 5000 (interno) | Reconhecimento facial |
| **phpmyadmin** | `ponto_phpmyadmin` | 8080 | Interface MySQL (dev) |
| **mailhog** | `ponto_mailhog` | 1025, 8025 | Captura de emails (dev) |
| **redis-commander** | `ponto_redis_commander` | 8081 | Interface Redis (dev) |

---

## 🛠️ Comandos Úteis

### Gerenciamento de Containers

```bash
# Iniciar todos os serviços
docker compose up -d

# Parar todos os serviços
docker compose stop

# Parar e remover containers
docker compose down

# Parar e remover containers + volumes (CUIDADO: apaga dados!)
docker compose down -v

# Reconstruir imagens
docker compose build

# Reconstruir e iniciar
docker compose up -d --build

# Ver logs
docker compose logs [serviço]
docker compose logs -f app  # logs em tempo real do app

# Verificar status
docker compose ps

# Ver uso de recursos
docker stats
```

### Executar Comandos na Aplicação

```bash
# Abrir shell no container
docker compose exec app bash

# Executar comandos PHP Spark (CodeIgniter CLI)
docker compose exec app php spark list
docker compose exec app php spark migrate
docker compose exec app php spark db:seed AdminSeeder
docker compose exec app php spark cache:clear

# Executar Composer
docker compose exec app composer install
docker compose exec app composer update

# Executar testes
docker compose exec app php spark test
```

### Banco de Dados

```bash
# Conectar ao MySQL
docker compose exec mysql mysql -u ponto_user -p ponto_eletronico

# Backup do banco
docker compose exec mysql mysqldump -u root -p ponto_eletronico > backup.sql

# Restaurar backup
docker compose exec -T mysql mysql -u root -p ponto_eletronico < backup.sql

# Ver logs do MySQL
docker compose logs -f mysql
```

### DeepFace API

```bash
# Verificar saúde da API
curl http://localhost:5000/health

# Ver logs
docker compose logs -f deepface

# Reiniciar apenas DeepFace
docker compose restart deepface
```

---

## 🔧 Configuração Avançada

### Variáveis de Ambiente Adicionais

Edite o `.env` para personalizar:

```env
# Portas customizadas
APP_PORT=8000           # Porta HTTP do app
APP_PORT_SSL=8443       # Porta HTTPS do app
DB_PORT=33060           # Porta MySQL
REDIS_PORT=63790        # Porta Redis
PHPMYADMIN_PORT=8090    # Porta PHPMyAdmin
MAILHOG_WEB_PORT=8026   # Porta Web Mailhog

# DeepFace - Reconhecimento Facial
DEEPFACE_THRESHOLD=0.40       # Threshold de similaridade
DEEPFACE_MODEL=VGG-Face       # Modelo: VGG-Face, Facenet, OpenFace, etc.
DEEPFACE_DETECTOR=opencv      # Detector: opencv, ssd, mtcnn

# Recursos (Docker Compose)
APP_CPU_LIMIT=1.5             # Limite de CPU para app
APP_MEMORY_LIMIT=1G           # Limite de memória para app
```

### Executar Migrations Automaticamente

Por padrão, migrations NÃO rodam automaticamente em produção. Para forçar:

```env
RUN_MIGRATIONS=true
```

Ou execute manualmente:

```bash
docker compose exec app php spark migrate --all
```

### SSL/HTTPS (Produção)

1. Coloque certificados em `docker/nginx/ssl/`:
   ```
   docker/nginx/ssl/
   ├── certificate.crt
   └── private.key
   ```

2. Descomente no `docker/nginx/default.conf`:
   ```nginx
   return 301 https://$server_name$request_uri;
   ```

3. Reconstrua:
   ```bash
   docker compose up -d --build app
   ```

---

## 🧪 Ambiente de Desenvolvimento

### Iniciar com Ferramentas de Dev

```bash
docker compose --profile development up -d
```

Isso adiciona:
- **PHPMyAdmin**: http://localhost:8080
- **Mailhog**: http://localhost:8025 (captura emails)
- **Redis Commander**: http://localhost:8081

### Hot Reload (Desenvolvimento)

Para editar código e ver mudanças instantâneas, monte volumes:

```yaml
# docker-compose.override.yml (criar este arquivo)
version: '3.8'
services:
  app:
    volumes:
      - ./:/var/www/html  # Monta código local
```

```bash
docker compose up -d
```

---

## 🐛 Troubleshooting

### Problema: "Error: Not supported URL scheme http+docker"

**Solução**: Migre para Docker Compose V2. Veja: [DOCKER_SETUP_FIX.md](./DOCKER_SETUP_FIX.md)

### Problema: Container `app` não inicia

```bash
# Ver logs detalhados
docker compose logs app

# Possíveis causas:
# 1. MySQL não está pronto
docker compose logs mysql

# 2. Erro no .env
docker compose exec app cat .env

# 3. Permissões
docker compose exec app ls -la writable/
```

### Problema: "Connection refused" ao MySQL

```bash
# Verificar se MySQL está rodando
docker compose ps mysql

# Verificar health check
docker compose exec mysql mysqladmin ping -h localhost

# Reiniciar MySQL
docker compose restart mysql
```

### Problema: DeepFace API lenta ou erro 500

```bash
# Ver logs
docker compose logs -f deepface

# Aumentar recursos
# Edite docker-compose.yml:
deploy:
  resources:
    limits:
      cpus: '4.0'
      memory: 4G
```

### Problema: Erro "disk space"

```bash
# Limpar containers e imagens não usadas
docker system prune -a

# Limpar volumes não usados (CUIDADO!)
docker volume prune
```

### Resetar Tudo (Fresh Start)

```bash
# Parar e remover tudo
docker compose down -v

# Remover imagens
docker compose down --rmi all

# Reconstruir do zero
docker compose build --no-cache
docker compose up -d
```

---

## 📊 Monitoramento e Logs

### Logs Centralizados

Todos os logs estão em:
```
writable/logs/
├── php-error.log
├── php-fpm-error.log
├── php-fpm-stdout.log
├── php-fpm-stderr.log
├── nginx-stdout.log
└── nginx-stderr.log
```

Ver logs:
```bash
docker compose exec app tail -f writable/logs/php-error.log
```

### Health Checks

```bash
# Verificar saúde de todos os serviços
docker compose ps

# Testar endpoints
curl http://localhost/          # App
curl http://localhost/health    # Nginx health
curl http://localhost:5000/health  # DeepFace (interno)
```

### Performance

```bash
# Ver uso de recursos em tempo real
docker stats

# Ver uso específico do app
docker stats ponto_app
```

---

## 🚀 Deploy em Produção

### 1. Configurar Servidor

```bash
# Instalar Docker e Docker Compose V2
curl -fsSL https://get.docker.com | sh
```

### 2. Clonar e Configurar

```bash
git clone https://github.com/seu-usuario/ponto-eletronico.git
cd ponto-eletronico
cp .env.example .env
nano .env  # Editar com senhas fortes
```

### 3. Iniciar em Produção

```bash
# Build otimizado para produção
docker compose build --no-cache

# Iniciar
docker compose up -d

# Ver logs de inicialização
docker compose logs -f
```

### 4. Configurar Backup Automático

```bash
# Adicionar ao crontab
crontab -e

# Backup diário às 2h da manhã
0 2 * * * cd /caminho/ponto-eletronico && docker compose exec -T mysql mysqldump -u root -pSUASENHA ponto_eletronico > /backup/ponto_$(date +\%Y\%m\%d).sql
```

---

## 📚 Documentação Adicional

- [DOCKER_SETUP_FIX.md](./DOCKER_SETUP_FIX.md) - Solução para problemas do Docker
- [README.md](./README.md) - Documentação principal do projeto
- [CodeIgniter 4 Docs](https://codeigniter.com/user_guide/)
- [Docker Docs](https://docs.docker.com/)

---

## 🆘 Suporte

Se encontrar problemas:

1. **Verifique os logs**: `docker compose logs -f`
2. **Consulte troubleshooting** acima
3. **Abra uma issue**: [GitHub Issues](https://github.com/seu-usuario/ponto-eletronico/issues)

---

## 📝 Licença

Este projeto está sob a licença especificada no arquivo LICENSE.

---

**Desenvolvido por Support Solo Sondagens** 🇧🇷
