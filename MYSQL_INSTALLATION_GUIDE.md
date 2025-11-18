# 🗄️ Guia de Instalação e Configuração do MySQL
## Sistema de Registro de Ponto Eletrônico

**Última Atualização:** 18/11/2024
**Objetivo:** Instalar e configurar MySQL para testes em produção

---

## 📋 Pré-requisitos

- ✅ Sistema operacional Linux (Debian/Ubuntu)
- ✅ Permissões de sudo
- ✅ PHP 8.4+ com extensões mysqli e pdo_mysql (já instalado)
- ✅ Composer instalado (já instalado)

---

## 🚀 Instalação do MySQL

### Passo 1: Instalar MySQL Server

```bash
# Atualizar repositórios
sudo apt-get update

# Instalar MySQL Server e Client
sudo apt-get install -y mysql-server mysql-client

# Verificar instalação
mysql --version
```

**Saída esperada:**
```
mysql  Ver 8.0.x for Linux on x86_64 (MySQL Community Server - GPL)
```

### Passo 2: Iniciar MySQL Service

```bash
# Iniciar serviço
sudo systemctl start mysql

# Verificar status
sudo systemctl status mysql

# Habilitar inicialização automática
sudo systemctl enable mysql
```

**Saída esperada:**
```
● mysql.service - MySQL Community Server
   Loaded: loaded (/lib/systemd/system/mysql.service; enabled)
   Active: active (running)
```

### Passo 3: Configuração Inicial de Segurança

```bash
# Executar script de segurança
sudo mysql_secure_installation
```

**Responda as perguntas:**

1. **VALIDATE PASSWORD COMPONENT?**
   - Responda: `y` (Yes)
   - Nível: `2` (STRONG - 12+ caracteres, maiúsculas, minúsculas, números, especiais)

2. **Set root password?**
   - Responda: `y` (Yes)
   - Digite uma senha forte (mínimo 12 caracteres)
   - **IMPORTANTE:** Anote esta senha! Você precisará dela.

3. **Remove anonymous users?**
   - Responda: `y` (Yes)

4. **Disallow root login remotely?**
   - Responda: `y` (Yes)

5. **Remove test database?**
   - Responda: `y` (Yes)

6. **Reload privilege tables now?**
   - Responda: `y` (Yes)

---

## 🔐 Criar Banco de Dados e Usuário

### Passo 1: Conectar ao MySQL como Root

```bash
sudo mysql -u root -p
```

Digite a senha do root que você definiu.

### Passo 2: Criar Banco de Dados

```sql
-- Criar banco de dados
CREATE DATABASE ponto_eletronico
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Verificar criação
SHOW DATABASES;
```

### Passo 3: Criar Usuário da Aplicação

```sql
-- Criar usuário com senha forte
CREATE USER 'ponto_user'@'localhost'
    IDENTIFIED WITH mysql_native_password
    BY 'SUA_SENHA_FORTE_AQUI_12345@';

-- IMPORTANTE: Substitua 'SUA_SENHA_FORTE_AQUI_12345@' por uma senha forte
-- Exemplo de senha forte: P0nt0El3tr0n!c0@2024
```

### Passo 4: Conceder Permissões

```sql
-- Conceder permissões necessárias (Least Privilege)
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, ALTER
    ON ponto_eletronico.*
    TO 'ponto_user'@'localhost';

-- Aplicar permissões
FLUSH PRIVILEGES;

-- Verificar permissões
SHOW GRANTS FOR 'ponto_user'@'localhost';
```

**Saída esperada:**
```
+---------------------------------------------------------------------------------+
| Grants for ponto_user@localhost                                                |
+---------------------------------------------------------------------------------+
| GRANT USAGE ON *.* TO `ponto_user`@`localhost`                                 |
| GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, ALTER                     |
|   ON `ponto_eletronico`.* TO `ponto_user`@`localhost`                         |
+---------------------------------------------------------------------------------+
```

### Passo 5: Testar Conexão

```sql
-- Sair do MySQL
EXIT;
```

```bash
# Testar conexão com novo usuário
mysql -u ponto_user -p ponto_eletronico
```

Digite a senha do `ponto_user`. Se conectar com sucesso, está tudo OK!

```sql
-- Verificar banco de dados atual
SELECT DATABASE();

-- Sair
EXIT;
```

---

## ⚙️ Configurar Aplicação

### Passo 1: Editar Arquivo .env

```bash
# Navegar para o diretório da aplicação
cd /home/user/Support-Solo-Sondagens---El-tronico-Registro-de-Ponto

# Editar .env
nano .env
```

### Passo 2: Atualizar Credenciais do Banco

Procure a seção `DATABASE` e atualize:

```ini
#--------------------------------------------------------------------
# DATABASE
#--------------------------------------------------------------------

database.default.hostname = localhost
database.default.database = ponto_eletronico
database.default.username = ponto_user
database.default.password = SUA_SENHA_DO_PONTO_USER
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = 3306
```

**IMPORTANTE:** Substitua `SUA_SENHA_DO_PONTO_USER` pela senha que você definiu.

**Salvar e sair:** Ctrl+O, Enter, Ctrl+X

### Passo 3: Verificar Conexão via PHP

```bash
# Executar script de teste
php test_basic.php
```

**Saída esperada (seção MySQL):**
```
--- Banco de Dados ---
✅ MySQL conectado (servidor disponível)
```

Se ainda aparecer `❌ MySQL não disponível`, verifique:
1. MySQL está rodando: `sudo systemctl status mysql`
2. Credenciais no .env estão corretas
3. Usuário tem permissões no banco

---

## 🗃️ Executar Migrations

### Passo 1: Verificar Migrations Disponíveis

```bash
php spark migrate:status
```

**Saída esperada:**
```
+----------------------+-------------------+--------------+
| Group                | Version           | Filename     |
+----------------------+-------------------+--------------+
| default              | 2024-11-18-000001 | create_rem...| Not Run
| ...                  | ...               | ...          | Not Run
+----------------------+-------------------+--------------+
```

### Passo 2: Executar Todas as Migrations

```bash
php spark migrate
```

**Saída esperada:**
```
Running: 2024_11_18_000001_create_remember_tokens_table
Migrated: 2024_11_18_000001_create_remember_tokens_table
...
Done
```

### Passo 3: Verificar Tabelas Criadas

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

### Passo 4: Verificar Estrutura da Tabela remember_tokens

```bash
mysql -u ponto_user -p ponto_eletronico -e "DESCRIBE remember_tokens;"
```

**Saída esperada:**
```
+--------------+--------------+------+-----+---------+----------------+
| Field        | Type         | Null | Key | Default | Extra          |
+--------------+--------------+------+-----+---------+----------------+
| id           | int          | NO   | PRI | NULL    | auto_increment |
| employee_id  | int unsigned | NO   | MUL | NULL    |                |
| token_hash   | varchar(255) | NO   |     | NULL    |                |
| selector     | varchar(64)  | NO   | MUL | NULL    |                |
| ip_address   | varchar(45)  | YES  |     | NULL    |                |
| user_agent   | text         | YES  |     | NULL    |                |
| expires_at   | datetime     | NO   |     | NULL    |                |
| last_used_at | datetime     | YES  |     | NULL    |                |
| created_at   | timestamp    | NO   |     | current|                |
| updated_at   | timestamp    | NO   |     | current|                |
+--------------+--------------+------+-----+---------+----------------+
```

---

## 🎯 Inserir Dados de Teste

### Criar Usuário Administrador

```bash
mysql -u ponto_user -p ponto_eletronico
```

```sql
-- Inserir usuário admin de teste
-- Senha: Admin@123456 (hasheada com BCrypt cost 12)
INSERT INTO employees (
    name,
    email,
    password,
    role,
    active,
    created_at,
    updated_at
) VALUES (
    'Administrador Teste',
    'admin@teste.com',
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/lewPAAa4pQRLfT4SG',
    'admin',
    1,
    NOW(),
    NOW()
);

-- Verificar inserção
SELECT id, name, email, role FROM employees;

-- Sair
EXIT;
```

**IMPORTANTE:** A senha hasheada acima corresponde a `Admin@123456`. Altere após primeiro login!

### Criar Funcionário de Teste

```sql
INSERT INTO employees (
    name,
    email,
    password,
    role,
    active,
    created_at,
    updated_at
) VALUES (
    'Funcionário Teste',
    'funcionario@teste.com',
    '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/lewPAAa4pQRLfT4SG',
    'funcionario',
    1,
    NOW(),
    NOW()
);
```

---

## 🚀 Iniciar Servidor

### Passo 1: Testar Inicialização

```bash
# Iniciar servidor de desenvolvimento
php spark serve
```

**Saída esperada:**
```
CodeIgniter v4.x.x development server started on http://localhost:8080
Press Ctrl-C to quit.
```

### Passo 2: Acessar no Navegador

Abra seu navegador e acesse:
```
http://localhost:8080
```

Se tudo estiver correto, você verá a página inicial do sistema.

### Passo 3: Testar Login

1. Acesse: `http://localhost:8080/auth/login`
2. Email: `admin@teste.com`
3. Senha: `Admin@123456`
4. Marque "Lembrar-me" para testar Fix #17
5. Clique em "Entrar"

Se login for bem-sucedido:
- ✅ Banco de dados está funcionando
- ✅ Autenticação está funcionando
- ✅ Remember Me está funcionando (se marcado)

### Passo 4: Verificar Token Remember Me

Após login com "Lembrar-me" marcado:

```bash
# Verificar que token foi criado
mysql -u ponto_user -p ponto_eletronico \
  -e "SELECT id, employee_id, selector, LEFT(token_hash, 20) as token_preview, expires_at FROM remember_tokens ORDER BY created_at DESC LIMIT 5;"
```

**Saída esperada:**
```
+----+-------------+----------------------------------+----------------------+---------------------+
| id | employee_id | selector                         | token_preview        | expires_at          |
+----+-------------+----------------------------------+----------------------+---------------------+
|  1 |           1 | a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6 | 6c7d8e9f0a1b2c3d4e5f | 2024-12-18 12:00:00 |
+----+-------------+----------------------------------+----------------------+---------------------+
```

---

## 🧪 Executar Testes Completos

Agora que o MySQL está configurado, execute todos os testes:

```bash
# Testes de componentes (já executado)
php test_security_components.php

# Testes completos (seguir guia)
# Consultar SECURITY_TESTING_GUIDE.md para testes completos com banco de dados
```

---

## 🔧 Configurações Avançadas (Opcional)

### Otimização de Performance

Editar arquivo de configuração do MySQL:

```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

Adicionar/modificar:

```ini
[mysqld]
# Performance
innodb_buffer_pool_size = 256M
innodb_log_file_size = 64M
max_connections = 100

# Segurança
bind-address = 127.0.0.1
local-infile = 0

# Slow Query Log (para debugging)
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 2
log_queries_not_using_indexes = 1

# Character Set
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
```

Reiniciar MySQL:

```bash
sudo systemctl restart mysql
```

### Backup Automático

Criar script de backup:

```bash
sudo nano /usr/local/bin/backup_ponto.sh
```

```bash
#!/bin/bash
# Backup do banco de dados do sistema de ponto

BACKUP_DIR="/var/backups/mysql"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="ponto_eletronico"
DB_USER="ponto_user"
DB_PASS="SUA_SENHA_AQUI"

# Criar diretório se não existir
mkdir -p $BACKUP_DIR

# Fazer backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/ponto_$DATE.sql

# Comprimir
gzip $BACKUP_DIR/ponto_$DATE.sql

# Deletar backups com mais de 30 dias
find $BACKUP_DIR -name "ponto_*.sql.gz" -mtime +30 -delete

echo "Backup concluído: ponto_$DATE.sql.gz"
```

Tornar executável:

```bash
sudo chmod +x /usr/local/bin/backup_ponto.sh
```

Adicionar ao cron (diário às 2h):

```bash
sudo crontab -e
```

Adicionar linha:

```
0 2 * * * /usr/local/bin/backup_ponto.sh >> /var/log/backup_ponto.log 2>&1
```

---

## ❌ Troubleshooting

### Problema 1: MySQL não inicia

**Sintoma:**
```
Job for mysql.service failed because the control process exited with error code.
```

**Soluções:**
```bash
# Verificar logs
sudo journalctl -u mysql.service -n 50 --no-pager

# Verificar arquivo de erro do MySQL
sudo cat /var/log/mysql/error.log

# Tentar reiniciar
sudo systemctl restart mysql

# Se falhar, remover arquivos de lock
sudo rm /var/run/mysqld/mysqld.sock
sudo rm /var/run/mysqld/mysqld.pid
sudo systemctl restart mysql
```

### Problema 2: Acesso negado ao conectar

**Sintoma:**
```
ERROR 1045 (28000): Access denied for user 'ponto_user'@'localhost'
```

**Soluções:**
```bash
# Verificar usuário existe
sudo mysql -u root -p
```

```sql
SELECT User, Host FROM mysql.user WHERE User = 'ponto_user';

-- Se não existir, criar novamente
CREATE USER 'ponto_user'@'localhost' IDENTIFIED BY 'sua_senha';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, INDEX, ALTER ON ponto_eletronico.* TO 'ponto_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Problema 3: Migration falha

**Sintoma:**
```
CodeIgniter\Database\Exceptions\DatabaseException:
Table 'employees' already exists
```

**Solução:**
```bash
# Verificar status das migrations
php spark migrate:status

# Rollback da última migration
php spark migrate:rollback

# Executar novamente
php spark migrate
```

### Problema 4: "Too many connections"

**Sintoma:**
```
ERROR 1040 (HY000): Too many connections
```

**Solução:**
```bash
sudo mysql -u root -p
```

```sql
-- Verificar conexões atuais
SHOW PROCESSLIST;

-- Matar conexões ociosas
-- (substituir ID pelos IDs das conexões ociosas)
KILL 123;

-- Aumentar max_connections
SET GLOBAL max_connections = 200;

-- Tornar permanente
EXIT;
```

```bash
sudo nano /etc/mysql/mysql.conf.d/mysqld.cnf
```

Adicionar:
```ini
max_connections = 200
```

```bash
sudo systemctl restart mysql
```

---

## 📞 Suporte

**Se continuar com problemas:**

1. Verificar logs do MySQL:
   ```bash
   sudo tail -f /var/log/mysql/error.log
   ```

2. Verificar logs da aplicação:
   ```bash
   tail -f writable/logs/log-$(date +%Y-%m-%d).log
   ```

3. Consultar documentação:
   - MySQL: https://dev.mysql.com/doc/
   - CodeIgniter: https://codeigniter.com/user_guide/

---

## ✅ Checklist Final

Antes de considerar a instalação completa, verifique:

- [ ] MySQL instalado e rodando
- [ ] mysql_secure_installation executado
- [ ] Banco de dados `ponto_eletronico` criado
- [ ] Usuário `ponto_user` criado com permissões corretas
- [ ] Arquivo `.env` atualizado com credenciais
- [ ] Teste de conexão via PHP bem-sucedido
- [ ] Migrations executadas com sucesso
- [ ] Tabelas criadas corretamente
- [ ] Usuário admin de teste criado
- [ ] Login funcionando via navegador
- [ ] Remember Me token criado após login
- [ ] Backup automático configurado (opcional)

---

**Guia criado em:** 18/11/2024
**Versão:** 1.0
**Status:** ✅ Pronto para uso
