# ✅ Checklist de Deploy - Sistema de Ponto Eletrônico

**Branch:** `claude/fix-composer-setup-warning-01NedH4Ms8iQLTecqkxf9rPx`
**Data:** 16 de Novembro de 2025

---

## 📋 PRÉ-DEPLOY (No servidor de produção)

### Requisitos de Sistema
- [ ] Servidor com Ubuntu 20.04+ / Debian 11+ / CentOS 8+
- [ ] CPU: Mínimo 2 cores (Recomendado: 4 cores)
- [ ] RAM: Mínimo 4GB (Recomendado: 8GB)
- [ ] Disco: Mínimo 20GB livres
- [ ] Portas 80 e 443 disponíveis

### Instalação de Dependências
- [ ] Sistema atualizado (`apt-get update && apt-get upgrade`)
- [ ] Git instalado (`apt-get install git`)
- [ ] Curl/Wget instalado (`apt-get install curl wget`)
- [ ] Docker Engine 20.10+ instalado
- [ ] Docker Compose V2 (plugin) instalado
- [ ] Docker daemon rodando (`systemctl status docker`)
- [ ] Usuário adicionado ao grupo docker (opcional)

### Verificação
```bash
# Executar e confirmar versões
docker --version              # >= 20.10.x
docker compose version        # >= v2.x.x
systemctl status docker       # Active: active (running)
```

---

## 📦 CLONE E CONFIGURAÇÃO

### Clonar Repositório
- [ ] Diretório criado: `/var/www/ponto-eletronico`
- [ ] Repositório clonado
- [ ] Branch correta: `claude/fix-composer-setup-warning-01NedH4Ms8iQLTecqkxf9rPx`
- [ ] Arquivos Docker verificados:
  - [ ] `Dockerfile` existe
  - [ ] `docker-compose.yml` existe
  - [ ] `deepface-api/Dockerfile` existe
  - [ ] `docker/entrypoint.sh` existe

```bash
# Verificar
cd /var/www/ponto-eletronico
git branch  # Deve mostrar: claude/fix-composer-setup-warning-01NedH4Ms8iQLTecqkxf9rPx
ls -lh Dockerfile docker-compose.yml
```

---

## ⚙️ CONFIGURAÇÃO DO .ENV

### Criar Arquivo
- [ ] `.env.example` copiado para `.env`
- [ ] Permissões ajustadas (`chmod 600 .env`)

### Configurações Obrigatórias (CRÍTICAS!)

#### Ambiente
- [ ] `CI_ENVIRONMENT = production`
- [ ] `app.baseURL` configurado com domínio real

#### Segurança
- [ ] `encryption.key` gerado (32 bytes base64)
  ```bash
  # Comando: echo "base64:$(openssl rand -base64 32)"
  ```
- [ ] Senha MySQL alterada (`database.default.password`)
- [ ] Senha root MySQL alterada (`DB_ROOT_PASSWORD`)
- [ ] Senha Redis alterada (`REDIS_PASSWORD`)
- [ ] API Key DeepFace alterada (`DEEPFACE_API_KEY`)

#### Banco de Dados
- [ ] `database.default.hostname = mysql`
- [ ] `database.default.database = ponto_eletronico`
- [ ] `database.default.username = ponto_user`
- [ ] `database.default.password` = **[SENHA FORTE ÚNICA]**
- [ ] `DB_ROOT_PASSWORD` = **[SENHA FORTE ÚNICA]**

#### Redis
- [ ] `REDIS_HOST = redis`
- [ ] `REDIS_PASSWORD` = **[SENHA FORTE ÚNICA]**
- [ ] `REDIS_PORT = 6379`

#### DeepFace
- [ ] `DEEPFACE_API_URL = http://deepface:5000`
- [ ] `DEEPFACE_API_KEY` = **[CHAVE FORTE ÚNICA]**
- [ ] `DEEPFACE_THRESHOLD = 0.40`
- [ ] `DEEPFACE_MODEL = VGG-Face`

#### Email (Opcional)
- [ ] `email.SMTPHost` configurado
- [ ] `email.SMTPUser` configurado
- [ ] `email.SMTPPass` configurado
- [ ] `email.fromEmail` configurado

### Validação Final .env
```bash
# NUNCA deve retornar "SuaSenhaMySQLForte123!" ou outras senhas de exemplo
grep -i "senha" .env | grep -v "^#"
```

---

## 🚀 BUILD E INICIALIZAÇÃO

### Build das Imagens
- [ ] Build executado: `docker compose build --no-cache`
- [ ] Build concluído sem erros
- [ ] Tempo de build: ~5-10 minutos

### Iniciar Serviços
- [ ] Containers iniciados:
  - **Produção:** `docker compose up -d`
  - **Dev:** `docker compose --profile development up -d`
- [ ] Aguardar 30-60 segundos para inicialização completa

### Verificar Status
```bash
docker compose ps

# Verificar se todos estão "Up" e "healthy":
# ✓ ponto_app        - Up (healthy)
# ✓ ponto_mysql      - Up (healthy)
# ✓ ponto_redis      - Up (healthy)
# ✓ ponto_deepface   - Up (healthy)
```

---

## 🗃️ BANCO DE DADOS

### Verificar MySQL
- [ ] MySQL acessível: `docker compose exec mysql mysql -u ponto_user -p`
- [ ] Banco `ponto_eletronico` existe

### Executar Migrations
- [ ] Migrations executadas: `docker compose exec app php spark migrate`
- [ ] Status verificado: `docker compose exec app php spark migrate:status`
- [ ] Sem erros

### Popular Dados Iniciais
- [ ] AdminSeeder executado: `docker compose exec app php spark db:seed AdminSeeder`
- [ ] Usuário admin criado com sucesso
- [ ] Credenciais admin anotadas

---

## 🌐 ACESSO E TESTES

### Testar Acesso HTTP
- [ ] Aplicação responde: `curl -I http://localhost`
- [ ] Status: `HTTP/1.1 200 OK` ou `302 Found`
- [ ] Acessível via browser: `http://IP-DO-SERVIDOR`

### Testar Funcionalidades
- [ ] Página de login carrega
- [ ] Login com admin funciona
- [ ] Dashboard carrega sem erros
- [ ] Cadastro de funcionário funciona
- [ ] Biometria facial funciona (teste básico)

### Verificar Logs
```bash
# Sem erros críticos
docker compose logs app --tail=100

# Sem erros PHP fatal
docker compose exec app tail -f writable/logs/log-$(date +%Y-%m-%d).log
```

---

## 🔒 SEGURANÇA ADICIONAL

### Firewall
- [ ] UFW instalado: `apt-get install ufw`
- [ ] Porta 22 (SSH) permitida: `ufw allow 22/tcp`
- [ ] Porta 80 (HTTP) permitida: `ufw allow 80/tcp`
- [ ] Porta 443 (HTTPS) permitida: `ufw allow 443/tcp`
- [ ] Firewall ativado: `ufw enable`
- [ ] Status verificado: `ufw status`

### SSL/HTTPS (Recomendado)
- [ ] Nginx instalado no host (se usar proxy reverso)
- [ ] Certbot instalado: `apt-get install certbot python3-certbot-nginx`
- [ ] Certificado SSL obtido: `certbot --nginx -d seu-dominio.com.br`
- [ ] Redirecionamento HTTP → HTTPS configurado
- [ ] Certificado válido (testar no browser)

### Backups
- [ ] Diretório de backup criado: `/backup/ponto-eletronico`
- [ ] Script de backup criado: `/usr/local/bin/backup-ponto.sh`
- [ ] Script executável: `chmod +x /usr/local/bin/backup-ponto.sh`
- [ ] Crontab configurado (diário às 2h):
  ```bash
  0 2 * * * /usr/local/bin/backup-ponto.sh >> /var/log/backup-ponto.log 2>&1
  ```
- [ ] Teste de backup executado manualmente

---

## 📊 MONITORAMENTO

### Logs
- [ ] Logs PHP acessíveis: `docker compose exec app tail -f writable/logs/log-*.log`
- [ ] Logs Nginx acessíveis: `docker compose logs -f app`
- [ ] Logs MySQL acessíveis: `docker compose logs -f mysql`
- [ ] Logs DeepFace acessíveis: `docker compose logs -f deepface`

### Health Checks
- [ ] Endpoint de saúde app: `curl http://localhost/health`
- [ ] Endpoint de saúde DeepFace: `curl http://localhost:5000/health` (interno)
- [ ] Todos os containers "healthy": `docker compose ps`

### Performance
- [ ] Uso de recursos monitorado: `docker stats`
- [ ] CPU app < 80%
- [ ] Memória app < 80%
- [ ] Disco < 80%

---

## 📚 DOCUMENTAÇÃO E HANDOVER

### Documentação Entregue
- [ ] `DEPLOY_PRODUCTION.md` - Guia completo passo a passo
- [ ] `QUICK_DEPLOY.sh` - Script automatizado de deploy
- [ ] `DOCKER_README.md` - Guia completo Docker
- [ ] `DOCKER_SETUP_FIX.md` - Troubleshooting Docker
- [ ] `DEPLOY_CHECKLIST.md` - Esta checklist

### Informações Registradas
- [ ] Credenciais admin salvas em local seguro (gerenciador de senhas)
- [ ] Senhas de banco de dados registradas
- [ ] API Keys registradas
- [ ] Domínio/IP do servidor anotado
- [ ] Informações de acesso SSH anotadas

### Testes de Aceitação
- [ ] Login e logout funcionando
- [ ] Cadastro de funcionários funcionando
- [ ] Registro de ponto funcionando
- [ ] Cadastro de biometria facial funcionando
- [ ] Verificação biométrica funcionando
- [ ] Relatórios gerando corretamente
- [ ] Emails sendo enviados (se configurado)

---

## ✅ VALIDAÇÃO FINAL

### Checklist Crítico de Segurança
- [ ] ⚠️ **NENHUMA senha padrão em uso** (ex: "SuaSenhaMySQLForte123!")
- [ ] ⚠️ **Encryption key gerada e única** (não é base64:GERE-UMA-CHAVE...)
- [ ] ⚠️ **Portas de banco NÃO expostas externamente** (3306, 6379)
- [ ] ⚠️ **Firewall configurado** (apenas 22, 80, 443 abertas)
- [ ] ⚠️ **SSL/HTTPS ativado** (produção)
- [ ] ⚠️ **Backups automáticos configurados**
- [ ] ⚠️ **Arquivo .env com permissões 600**

### Performance
- [ ] Tempo de resposta < 2s para páginas principais
- [ ] Biometria facial processa em < 3s
- [ ] Banco de dados respondendo rapidamente

### Estabilidade
- [ ] Sistema rodando por 24h sem crashes
- [ ] Todos os containers "healthy" consistentemente
- [ ] Sem erros críticos nos logs

---

## 🎉 DEPLOY COMPLETO!

### Assinaturas

**Executado por:** _________________________
**Data:** ___/___/______
**Validado por:** _________________________
**Data:** ___/___/______

### Observações Finais
```
_________________________________________________________________

_________________________________________________________________

_________________________________________________________________
```

---

## 📞 Suporte Pós-Deploy

**Comandos Úteis de Emergência:**

```bash
# Ver logs em tempo real
docker compose logs -f app

# Reiniciar apenas app
docker compose restart app

# Reiniciar tudo
docker compose restart

# Parar tudo
docker compose stop

# Status de todos os containers
docker compose ps

# Uso de recursos
docker stats

# Backup manual emergencial
docker compose exec -T mysql mysqldump -u root -pSENHA ponto_eletronico > emergency_backup_$(date +%Y%m%d_%H%M%S).sql
```

**Documentação:**
- 📘 Guia completo: [DEPLOY_PRODUCTION.md](./DEPLOY_PRODUCTION.md)
- 🐳 Docker: [DOCKER_README.md](./DOCKER_README.md)
- 🔧 Troubleshooting: [DOCKER_SETUP_FIX.md](./DOCKER_SETUP_FIX.md)

---

**Desenvolvido por Support Solo Sondagens** 🇧🇷
**Última atualização:** 16/Nov/2025
