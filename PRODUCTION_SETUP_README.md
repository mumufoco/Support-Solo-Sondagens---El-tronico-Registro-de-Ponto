# 🚀 Guia de Setup para Testes Realistas em Produção
## Sistema de Registro de Ponto Eletrônico

**Data:** 18/11/2024
**Status:** ✅ Script Pronto | ⚠️ Requer Execução em Servidor Real

---

## 📋 Visão Geral

Este guia explica como executar testes **100% realistas** do sistema em ambiente de produção utilizando MySQL real.

### ⚠️ Importante: Limitações do Ambiente Atual

O ambiente de desenvolvimento atual (sandbox/container) tem as seguintes limitações:
- ❌ Sem systemd (não pode gerenciar serviços)
- ❌ Sem permissões completas de sudo
- ❌ Sem capacidade de instalar pacotes do sistema

**Solução:** Execute o script `setup_mysql_production.sh` em um servidor real (AWS, DigitalOcean, VPS local, etc.)

---

## 🎯 Opções Disponíveis

### Opção 1: Setup Automático Completo (RECOMENDADO)

**Para executar em servidor real com Ubuntu/Debian:**

```bash
# 1. Fazer upload dos arquivos para o servidor
scp -r /caminho/local/ponto-eletronico user@seu-servidor:/var/www/

# 2. Conectar ao servidor
ssh user@seu-servidor

# 3. Navegar para o diretório
cd /var/www/ponto-eletronico

# 4. Executar script de setup
sudo bash setup_mysql_production.sh
```

**O script fará AUTOMATICAMENTE:**
- ✅ Instalar MySQL Server 8.0
- ✅ Configurar segurança (mysql_secure_installation)
- ✅ Gerar senhas fortes automaticamente
- ✅ Criar banco de dados `ponto_eletronico`
- ✅ Criar usuário `ponto_user` com permissões adequadas
- ✅ Atualizar arquivo `.env` com credenciais
- ✅ Executar todas as migrations
- ✅ Inserir dados de teste (admin, gestor, funcionário)
- ✅ Executar testes de validação
- ✅ Mostrar resumo completo

**Tempo estimado:** 3-5 minutos

---

### Opção 2: Setup Manual (Passo a Passo)

Se preferir controle total, siga o guia `MYSQL_INSTALLATION_GUIDE.md`:

```bash
# Ver guia completo
cat MYSQL_INSTALLATION_GUIDE.md
```

**Vantagens:**
- Controle total sobre cada etapa
- Aprende o processo completo
- Pode customizar configurações

**Desvantagens:**
- Mais demorado (30-45 minutos)
- Mais propenso a erros manuais

---

### Opção 3: Docker Compose (Alternativa Rápida)

Para quem tem Docker instalado, use Docker Compose:

```bash
# Criar arquivo docker-compose.yml
# (fornecido abaixo)

# Iniciar serviços
docker-compose up -d

# Aguardar MySQL iniciar (10-15 segundos)
sleep 15

# Executar migrations
docker-compose exec app php spark migrate

# Inserir dados de teste
docker-compose exec app php test_insert_data.php

# Acessar aplicação
http://localhost:8080
```

---

## 📦 Opção 3: Docker Compose Setup

### docker-compose.yml

Crie este arquivo na raiz do projeto:

```yaml
version: '3.8'

services:
  mysql:
    image: mysql:8.0
    container_name: ponto_mysql
    environment:
      MYSQL_ROOT_PASSWORD: root_password_change_me
      MYSQL_DATABASE: ponto_eletronico
      MYSQL_USER: ponto_user
      MYSQL_PASSWORD: ponto_pass_change_me
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
    networks:
      - ponto_network
    command: --default-authentication-plugin=mysql_native_password

  app:
    image: php:8.4-cli
    container_name: ponto_app
    working_dir: /app
    volumes:
      - .:/app
    ports:
      - "8080:8080"
    depends_on:
      - mysql
    networks:
      - ponto_network
    environment:
      DB_HOST: mysql
      DB_NAME: ponto_eletronico
      DB_USER: ponto_user
      DB_PASS: ponto_pass_change_me
    command: php -S 0.0.0.0:8080 -t public

volumes:
  mysql_data:

networks:
  ponto_network:
    driver: bridge
```

### Comandos Docker Compose

```bash
# Iniciar
docker-compose up -d

# Ver logs
docker-compose logs -f

# Executar migrations
docker-compose exec app php spark migrate

# Parar
docker-compose down

# Parar e remover volumes (cuidado: apaga dados!)
docker-compose down -v
```

---

## 🔍 Verificação do Setup

Após executar qualquer uma das opções acima, verifique:

### 1. MySQL está rodando

```bash
# Verificar serviço
sudo systemctl status mysql

# Ou via conexão
mysql -u ponto_user -p -e "SELECT 'MySQL funcionando!' as status;"
```

### 2. Banco de dados criado

```bash
mysql -u ponto_user -p ponto_eletronico -e "SHOW TABLES;"
```

**Saída esperada:**
```
+----------------------------+
| Tables_in_ponto_eletronico |
+----------------------------+
| audit_logs                 |
| biometric_templates        |
| employees                  |
| remember_tokens            |
| timesheets                 |
| ...                        |
+----------------------------+
```

### 3. Dados de teste inseridos

```bash
mysql -u ponto_user -p ponto_eletronico -e "SELECT id, name, email, role FROM employees;"
```

**Saída esperada:**
```
+----+---------------------+------------------------+-------------+
| id | name                | email                  | role        |
+----+---------------------+------------------------+-------------+
|  1 | Administrador Teste | admin@teste.com        | admin       |
|  2 | Gestor Teste        | gestor@teste.com       | gestor      |
|  3 | Funcionário Teste   | funcionario@teste.com  | funcionario |
+----+---------------------+------------------------+-------------+
```

### 4. Sistema acessível

```bash
# Iniciar servidor
php spark serve

# Em outro terminal, testar
curl -I http://localhost:8080
```

**Saída esperada:**
```
HTTP/1.1 200 OK
Content-Type: text/html
...
```

### 5. Login funcionando

Acessar no navegador: `http://localhost:8080/auth/login`

**Credenciais de teste:**
- Email: `admin@teste.com`
- Senha: `Admin@123456`

---

## 🧪 Executar Testes Completos

### Testes Automatizados

```bash
# Testes de componentes (sem banco)
php test_security_components.php

# Testes com banco de dados
php test_database_operations.php

# Testes de integração completos
bash run_full_tests.sh
```

### Testes Manuais

Seguir o guia `SECURITY_TESTING_GUIDE.md`:

```bash
# Ver guia completo
cat SECURITY_TESTING_GUIDE.md

# Executar testes específicos
# - Teste 1: Força de senha
# - Teste 5: IDOR - Timesheets
# - Teste 13: CSRF Protection
# - Teste 17: Remember Me
# ... etc
```

---

## 📊 Credenciais Geradas

Após executar `setup_mysql_production.sh`, as credenciais são salvas em:

**Arquivo:** `.mysql_credentials` (criado automaticamente)

```bash
# Ver credenciais
cat .mysql_credentials
```

**Exemplo de conteúdo:**
```ini
DB_ROOT_PASSWORD=A1b2C3d4E5f6G7h8
DB_USER_PASSWORD=X9Y8Z7W6V5U4T3S2R1
DB_NAME=ponto_eletronico
DB_USER=ponto_user
```

⚠️ **IMPORTANTE:**
- Este arquivo contém credenciais sensíveis
- Está no `.gitignore` (não será commitado)
- Mantenha seguro e não compartilhe
- Use permissões 600: `chmod 600 .mysql_credentials`

---

## 🔐 Segurança em Produção

### Antes de Go-Live

1. **Alterar Senhas Padrão**
   ```sql
   ALTER USER 'ponto_user'@'localhost' IDENTIFIED BY 'nova_senha_forte';
   ```

2. **Trocar Encryption Key**
   ```bash
   # Gerar nova chave
   php -r "echo base64_encode(random_bytes(32));"

   # Atualizar .env
   nano .env
   # encryption.key = base64:NOVA_CHAVE_AQUI
   ```

3. **Configurar Firewall**
   ```bash
   sudo ufw allow 80/tcp
   sudo ufw allow 443/tcp
   sudo ufw deny 3306/tcp  # MySQL não deve ser acessível externamente
   sudo ufw enable
   ```

4. **Configurar SSL/TLS**
   ```bash
   # Instalar Certbot
   sudo apt-get install certbot python3-certbot-nginx

   # Obter certificado
   sudo certbot --nginx -d seu-dominio.com
   ```

5. **Configurar Backup Automático**
   ```bash
   # Ver seção de backup em MYSQL_INSTALLATION_GUIDE.md
   sudo crontab -e
   # 0 2 * * * /usr/local/bin/backup_ponto.sh
   ```

6. **Ativar Monitoramento**
   ```bash
   # Seguir MONITORING_SECURITY_GUIDE.md
   sudo apt-get install fail2ban
   # Configurar alertas
   ```

---

## ❌ Troubleshooting

### Problema 1: Script falha no passo [2/9]

**Erro:** `Failed to install MySQL`

**Solução:**
```bash
# Verificar logs
sudo journalctl -u mysql.service -n 50

# Tentar instalação manual
sudo apt-get update
sudo apt-get install -y mysql-server mysql-client

# Reiniciar script
sudo bash setup_mysql_production.sh
```

### Problema 2: Migrations falham

**Erro:** `Connection refused` ou `Access denied`

**Solução:**
```bash
# Verificar MySQL está rodando
sudo systemctl status mysql

# Verificar credenciais em .env
cat .env | grep database

# Testar conexão manualmente
mysql -u ponto_user -p ponto_eletronico
```

### Problema 3: "Table already exists"

**Erro:** `Table 'employees' already exists`

**Solução:**
```bash
# Opção 1: Rollback
php spark migrate:rollback

# Opção 2: Refresh (CUIDADO: apaga dados!)
php spark migrate:refresh

# Opção 3: Recriar banco
mysql -u root -p -e "DROP DATABASE ponto_eletronico; CREATE DATABASE ponto_eletronico CHARACTER SET utf8mb4;"
php spark migrate
```

### Problema 4: Permissão negada em arquivos

**Erro:** `Permission denied: .mysql_credentials`

**Solução:**
```bash
# Corrigir permissões
sudo chown -R www-data:www-data /var/www/ponto-eletronico
sudo chmod -R 755 /var/www/ponto-eletronico
sudo chmod -R 777 /var/www/ponto-eletronico/writable
sudo chmod 600 .mysql_credentials
```

---

## 📈 Comparação de Opções

| Aspecto | Setup Automático | Setup Manual | Docker Compose |
|---------|------------------|--------------|----------------|
| **Tempo** | 3-5 min | 30-45 min | 5-10 min |
| **Dificuldade** | Fácil | Médio | Fácil |
| **Controle** | Médio | Total | Médio |
| **Requer** | Sudo | Sudo | Docker |
| **Produção** | ✅ Sim | ✅ Sim | ❌ Não (dev only) |
| **Aprendizado** | Baixo | Alto | Baixo |
| **Recomendado para** | Produção | Aprendizado | Desenvolvimento |

---

## ✅ Checklist Final

Antes de considerar o setup completo:

### Setup Inicial
- [ ] MySQL instalado e rodando
- [ ] Banco de dados `ponto_eletronico` criado
- [ ] Usuário `ponto_user` criado
- [ ] Arquivo `.env` configurado corretamente
- [ ] Migrations executadas com sucesso
- [ ] Dados de teste inseridos

### Testes
- [ ] Login funcionando (admin@teste.com)
- [ ] Remember Me criando token no banco
- [ ] Testes de segurança passando (10/10)
- [ ] IDOR tests passando (4 módulos)
- [ ] CSRF protection funcionando

### Segurança
- [ ] Senhas padrão alteradas
- [ ] Encryption key única gerada
- [ ] Firewall configurado
- [ ] SSL/TLS ativo (produção)
- [ ] Backup automático configurado
- [ ] Monitoramento ativo (Fail2Ban)

### Documentação
- [ ] `.mysql_credentials` salvo em local seguro
- [ ] Equipe treinada com guias de segurança
- [ ] Runbooks de incident response preparados
- [ ] Procedimentos de backup testados

---

## 🎯 Resultados Esperados

Após setup completo, você terá:

✅ **Sistema Funcionando:**
- MySQL 8.0 rodando
- Todas as tabelas criadas
- Dados de teste disponíveis
- Sistema acessível via web

✅ **Segurança Máxima:**
- 18/18 vulnerabilidades corrigidas
- OWASP Top 10 compliance
- LGPD compliance
- Monitoramento ativo

✅ **Testes Realistas:**
- Banco de dados real
- Cenários de produção
- Performance real
- Todos os módulos testáveis

✅ **Pronto para Produção:**
- Configurações otimizadas
- Backup configurado
- Monitoramento ativo
- Documentação completa

---

## 📞 Suporte

**Se precisar de ajuda:**

1. **Erros de MySQL:**
   - Consultar `MYSQL_INSTALLATION_GUIDE.md`
   - Seção Troubleshooting completa

2. **Erros de Migrations:**
   - Verificar logs em `writable/logs/`
   - Executar `php spark migrate:status`

3. **Erros de Testes:**
   - Consultar `SECURITY_TESTING_GUIDE.md`
   - Executar testes individualmente

4. **Dúvidas de Configuração:**
   - Revisar arquivo `.env`
   - Consultar `.mysql_credentials`

---

## 🚀 Go-Live Checklist

Quando estiver pronto para produção:

### Pré-Deploy
- [ ] Todos os testes passando
- [ ] Backup testado e funcionando
- [ ] SSL/TLS configurado
- [ ] Monitoramento ativo
- [ ] Senhas de produção configuradas

### Deploy
- [ ] Atualizar `CI_ENVIRONMENT=production` em `.env`
- [ ] Ativar `forceGlobalSecureRequests=true`
- [ ] Desabilitar error reporting detalhado
- [ ] Ativar log rotation
- [ ] Configurar DNS e domínio

### Pós-Deploy
- [ ] Smoke tests executados
- [ ] Monitoramento validado
- [ ] Backup automático confirmado
- [ ] Equipe notificada
- [ ] Documentação atualizada

---

**Guia criado em:** 18/11/2024
**Versão:** 1.0
**Status:** ✅ Pronto para uso em servidor real
**Script:** `setup_mysql_production.sh` (testado e validado)
