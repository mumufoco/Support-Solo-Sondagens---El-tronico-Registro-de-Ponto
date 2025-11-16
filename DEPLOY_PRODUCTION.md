# 🚀 Guia de Deploy em Produção - Sistema de Ponto Eletrônico

**Data:** 16 de Novembro de 2025
**Branch:** `claude/fix-composer-setup-warning-01NedH4Ms8iQLTecqkxf9rPx`
**Status:** Pronto para deploy ✅

---

## 📋 Pré-requisitos do Servidor

### Requisitos Mínimos:
- **CPU:** 2 cores
- **RAM:** 4GB (Recomendado: 8GB)
- **Disco:** 20GB livres
- **SO:** Ubuntu 20.04+ / Debian 11+ / CentOS 8+
- **Portas:** 80, 443 (HTTP/HTTPS)

---

## 🔧 Passo 1: Preparar o Servidor

### 1.1. Conectar ao Servidor

```bash
ssh seu-usuario@seu-servidor.com
```

### 1.2. Atualizar Sistema

```bash
sudo apt-get update
sudo apt-get upgrade -y
```

### 1.3. Instalar Dependências Básicas

```bash
sudo apt-get install -y \
    curl \
    wget \
    git \
    ca-certificates \
    gnupg \
    lsb-release
```

---

## 🐳 Passo 2: Instalar Docker e Docker Compose V2

### 2.1. Adicionar Repositório Oficial do Docker

```bash
# Remover versões antigas (se existirem)
sudo apt-get remove docker docker-engine docker.io containerd runc

# Adicionar chave GPG oficial do Docker
sudo mkdir -p /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg

# Configurar repositório
echo \
  "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
  $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
```

### 2.2. Instalar Docker Engine e Docker Compose Plugin

```bash
# Atualizar índice de pacotes
sudo apt-get update

# Instalar Docker Engine e Plugin Compose V2
sudo apt-get install -y \
    docker-ce \
    docker-ce-cli \
    containerd.io \
    docker-buildx-plugin \
    docker-compose-plugin

# Verificar instalação
docker --version
# Esperado: Docker version 24.0.x ou superior

docker compose version
# Esperado: Docker Compose version v2.x.x
```

### 2.3. Configurar Permissões (Opcional, mas recomendado)

```bash
# Adicionar usuário ao grupo docker
sudo usermod -aG docker $USER

# Fazer logout e login novamente
exit
# Reconectar via SSH
```

### 2.4. Iniciar e Habilitar Docker

```bash
sudo systemctl start docker
sudo systemctl enable docker

# Verificar status
sudo systemctl status docker
```

---

## 📦 Passo 3: Clonar o Repositório

### 3.1. Navegar para Diretório de Aplicações

```bash
# Criar diretório para aplicações (se não existir)
sudo mkdir -p /var/www
cd /var/www
```

### 3.2. Clonar o Projeto

```bash
# Clone do repositório
sudo git clone http://127.0.0.1:21845/git/mumufoco/Support-Solo-Sondagens---El-tronico-Registro-de-Ponto.git ponto-eletronico

# Entrar no diretório
cd ponto-eletronico

# Checkout da branch com Docker
sudo git checkout claude/fix-composer-setup-warning-01NedH4Ms8iQLTecqkxf9rPx

# Verificar arquivos Docker
ls -lh Dockerfile docker-compose.yml
```

---

## ⚙️ Passo 4: Configurar Variáveis de Ambiente

### 4.1. Verificar se .env.example Existe

```bash
ls -lh .env.example
```

### 4.2. Criar Arquivo .env (CRÍTICO!)

```bash
# Copiar exemplo
sudo cp .env.example .env

# Editar com nano (ou vim)
sudo nano .env
```

### 4.3. Configurações Obrigatórias

**IMPORTANTE:** Altere os seguintes valores no `.env`:

```env
#---------------------------------------------------------
# AMBIENTE
#---------------------------------------------------------
CI_ENVIRONMENT = production

#---------------------------------------------------------
# APP
#---------------------------------------------------------
app.baseURL = 'https://seu-dominio.com.br/'
app.appTimezone = 'America/Sao_Paulo'

#---------------------------------------------------------
# ENCRYPTION (GERE UMA CHAVE NOVA!)
#---------------------------------------------------------
# Gerar chave: php spark key:generate
encryption.key = 'base64:GERE-UMA-CHAVE-DE-32-BYTES-AQUI=='

#---------------------------------------------------------
# DATABASE (ALTERE AS SENHAS!)
#---------------------------------------------------------
database.default.hostname = mysql
database.default.database = ponto_eletronico
database.default.username = ponto_user
database.default.password = SuaSenhaMySQLForte123!
database.default.DBDriver = MySQLi
database.default.port = 3306

#---------------------------------------------------------
# MYSQL ROOT PASSWORD (para Docker)
#---------------------------------------------------------
DB_ROOT_PASSWORD = SuaSenhaRootMySQLForte456!

#---------------------------------------------------------
# REDIS (ALTERE A SENHA!)
#---------------------------------------------------------
REDIS_HOST = redis
REDIS_PASSWORD = SuaSenhaRedisForte789!
REDIS_PORT = 6379

#---------------------------------------------------------
# DEEPFACE API (ALTERE A CHAVE!)
#---------------------------------------------------------
DEEPFACE_API_URL = http://deepface:5000
DEEPFACE_API_KEY = SuaChaveAPISecretaDeepFace999!
DEEPFACE_THRESHOLD = 0.40
DEEPFACE_MODEL = VGG-Face

#---------------------------------------------------------
# EMAIL (Configure SMTP se necessário)
#---------------------------------------------------------
email.SMTPHost = smtp.gmail.com
email.SMTPPort = 587
email.SMTPUser = seu-email@gmail.com
email.SMTPPass = sua-senha-app-gmail
email.SMTPCrypto = tls
email.fromEmail = noreply@seu-dominio.com.br
email.fromName = 'Sistema de Ponto Eletrônico'

#---------------------------------------------------------
# PORTAS (Customizar se necessário)
#---------------------------------------------------------
APP_PORT = 80
APP_PORT_SSL = 443
DB_PORT = 3306
REDIS_PORT = 6379
```

**Salvar e sair:** `Ctrl + X`, depois `Y`, depois `Enter`

### 4.4. Gerar Chave de Encriptação (MUITO IMPORTANTE!)

```bash
# Método 1: Se PHP estiver instalado no servidor
php -r "echo 'base64:' . base64_encode(random_bytes(32)) . PHP_EOL;"

# Método 2: Usando OpenSSL
echo "base64:$(openssl rand -base64 32)"

# Copie o resultado e cole em .env na linha:
# encryption.key = 'base64:RESULTADO-AQUI'
```

### 4.5. Ajustar Permissões

```bash
sudo chown -R $USER:$USER /var/www/ponto-eletronico
chmod 600 .env  # Proteger arquivo .env
```

---

## 🚀 Passo 5: Iniciar Aplicação com Docker

### 5.1. Build das Imagens (Primeira Vez)

```bash
# Navegar para diretório do projeto
cd /var/www/ponto-eletronico

# Build sem cache (primeira vez)
docker compose build --no-cache
```

**Tempo estimado:** 5-10 minutos (depende da internet)

### 5.2. Iniciar Todos os Serviços

```bash
# Modo PRODUÇÃO (apenas serviços essenciais)
docker compose up -d

# Verificar status
docker compose ps
```

**Serviços esperados:**
- ✅ `ponto_app` - Running (healthy)
- ✅ `ponto_mysql` - Running (healthy)
- ✅ `ponto_redis` - Running (healthy)
- ✅ `ponto_deepface` - Running (healthy)

### 5.3. Acompanhar Logs de Inicialização

```bash
# Ver todos os logs em tempo real
docker compose logs -f

# Ver apenas logs do app
docker compose logs -f app

# Para sair dos logs: Ctrl + C
```

---

## 🗃️ Passo 6: Configurar Banco de Dados

### 6.1. Verificar MySQL

```bash
# Conectar ao MySQL
docker compose exec mysql mysql -u ponto_user -p ponto_eletronico

# Senha: a que você definiu em DB_PASSWORD no .env
# Verificar banco: SHOW DATABASES;
# Sair: EXIT;
```

### 6.2. Executar Migrations

```bash
# Executar todas as migrations
docker compose exec app php spark migrate

# Verificar status das migrations
docker compose exec app php spark migrate:status
```

### 6.3. Popular Banco com Dados Iniciais (Seeders)

```bash
# Criar usuário administrador
docker compose exec app php spark db:seed AdminSeeder

# Outros seeders (se necessário)
docker compose exec app php spark db:seed CompanySeeder
```

---

## 🌐 Passo 7: Configurar Nginx/Domínio (Opcional)

### Opção A: Usar Porta 80 Diretamente

Se o servidor não tiver outro servidor web:

```bash
# A aplicação já está rodando na porta 80
# Acessar via: http://IP-DO-SERVIDOR
```

### Opção B: Configurar Nginx Reverso Proxy

Se você quiser usar um domínio com SSL:

```bash
# Instalar Nginx no host
sudo apt-get install -y nginx certbot python3-certbot-nginx

# Criar configuração
sudo nano /etc/nginx/sites-available/ponto-eletronico
```

**Conteúdo do arquivo:**

```nginx
server {
    listen 80;
    server_name seu-dominio.com.br www.seu-dominio.com.br;

    location / {
        proxy_pass http://localhost:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

**Ativar site:**

```bash
sudo ln -s /etc/nginx/sites-available/ponto-eletronico /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

**Configurar SSL (HTTPS) com Let's Encrypt:**

```bash
sudo certbot --nginx -d seu-dominio.com.br -d www.seu-dominio.com.br
```

---

## ✅ Passo 8: Verificação Final

### 8.1. Verificar Containers

```bash
docker compose ps

# Todos devem estar "Up" e "healthy"
```

### 8.2. Verificar Logs

```bash
docker compose logs app --tail=50

# Não deve ter erros críticos
```

### 8.3. Testar Aplicação

```bash
# Testar endpoint de saúde
curl http://localhost/health

# Testar página principal
curl -I http://localhost/
# Esperado: HTTP/1.1 200 OK
```

### 8.4. Acessar via Browser

Abra no navegador:
- **HTTP:** `http://seu-dominio.com.br` ou `http://IP-DO-SERVIDOR`
- **HTTPS:** `https://seu-dominio.com.br` (se configurou SSL)

---

## 🔒 Passo 9: Segurança Adicional (Recomendado)

### 9.1. Configurar Firewall

```bash
# Permitir apenas portas necessárias
sudo ufw allow 22/tcp      # SSH
sudo ufw allow 80/tcp      # HTTP
sudo ufw allow 443/tcp     # HTTPS
sudo ufw enable

# Verificar status
sudo ufw status
```

### 9.2. Bloquear Acesso Direto às Portas de Banco

```bash
# MySQL e Redis devem ser acessíveis apenas internamente
# Já configurado no docker-compose.yml (sem "ports:" expostas)
```

### 9.3. Backups Automáticos

```bash
# Criar script de backup
sudo mkdir -p /backup/ponto-eletronico
sudo nano /usr/local/bin/backup-ponto.sh
```

**Conteúdo do script:**

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backup/ponto-eletronico"

# Backup do banco de dados
docker compose exec -T mysql mysqldump -u root -p${DB_ROOT_PASSWORD} ponto_eletronico > ${BACKUP_DIR}/ponto_db_${DATE}.sql

# Comprimir
gzip ${BACKUP_DIR}/ponto_db_${DATE}.sql

# Manter apenas últimos 7 dias
find ${BACKUP_DIR} -name "ponto_db_*.sql.gz" -mtime +7 -delete

echo "Backup completed: ${DATE}"
```

**Tornar executável:**

```bash
sudo chmod +x /usr/local/bin/backup-ponto.sh
```

**Agendar no crontab (diário às 2h):**

```bash
sudo crontab -e

# Adicionar linha:
0 2 * * * /usr/local/bin/backup-ponto.sh >> /var/log/backup-ponto.log 2>&1
```

---

## 🔄 Passo 10: Manutenção e Atualizações

### Atualizar Código

```bash
cd /var/www/ponto-eletronico

# Baixar atualizações
git pull origin claude/fix-composer-setup-warning-01NedH4Ms8iQLTecqkxf9rPx

# Reconstruir containers
docker compose down
docker compose build --no-cache
docker compose up -d

# Executar migrations (se houver)
docker compose exec app php spark migrate
```

### Reiniciar Serviços

```bash
# Reiniciar todos
docker compose restart

# Reiniciar apenas um serviço
docker compose restart app
```

### Ver Uso de Recursos

```bash
# Monitorar em tempo real
docker stats

# Ver uso de disco
docker system df
```

---

## 🐛 Troubleshooting Comum

### Problema: Container não inicia

```bash
# Ver logs detalhados
docker compose logs app

# Verificar .env
docker compose exec app cat .env | grep -v PASSWORD
```

### Problema: Erro de conexão ao MySQL

```bash
# Verificar se MySQL está rodando
docker compose ps mysql

# Testar conexão
docker compose exec mysql mysqladmin ping -h localhost
```

### Problema: Permissões negadas

```bash
# Ajustar permissões do diretório writable
docker compose exec app chown -R www-data:www-data /var/www/html/writable
docker compose exec app chmod -R 775 /var/www/html/writable
```

### Problema: DeepFace API lenta

```bash
# Aumentar recursos no docker-compose.yml
# Seção "deepface" > "deploy" > "resources"
# Editar e reconstruir
```

---

## 📊 Monitoramento

### Logs Centralizados

```bash
# Ver logs PHP
docker compose exec app tail -f writable/logs/log-$(date +%Y-%m-%d).log

# Ver logs Nginx
docker compose exec app tail -f /var/log/nginx/access.log
docker compose exec app tail -f /var/log/nginx/error.log
```

### Health Checks

```bash
# Verificar saúde de todos os serviços
docker compose ps

# Verificar endpoint de saúde
curl http://localhost/health
```

---

## 📞 Suporte

**Desenvolvido por:** Support Solo Sondagens 🇧🇷

**Documentação adicional:**
- [DOCKER_README.md](./DOCKER_README.md) - Guia completo Docker
- [DOCKER_SETUP_FIX.md](./DOCKER_SETUP_FIX.md) - Troubleshooting
- [README.md](./README.md) - Documentação principal

---

## ✅ Checklist de Deploy

Use este checklist para garantir que tudo foi configurado corretamente:

- [ ] Docker e Docker Compose V2 instalados
- [ ] Repositório clonado e branch correta (`claude/fix-composer-setup-warning-01NedH4Ms8iQLTecqkxf9rPx`)
- [ ] Arquivo `.env` criado e configurado com senhas fortes
- [ ] Chave de encriptação gerada (`encryption.key`)
- [ ] Build das imagens concluído sem erros
- [ ] Todos os containers iniciados e "healthy"
- [ ] Migrations executadas com sucesso
- [ ] Seeder AdminSeeder executado
- [ ] Aplicação acessível via browser
- [ ] Firewall configurado (portas 22, 80, 443)
- [ ] Backup automático configurado (opcional)
- [ ] SSL/HTTPS configurado (opcional, mas recomendado)
- [ ] Logs verificados sem erros críticos

---

**Status:** ✅ **PRONTO PARA DEPLOY EM PRODUÇÃO**

**Última Atualização:** 16/Nov/2025
**Versão do Guia:** 1.0
