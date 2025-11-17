# 🗄️ GUIA: Como Instalar MySQL para o Sistema

Este guia apresenta **3 opções** para resolver o problema do MySQL no sistema de Ponto Eletrônico.

---

## 📋 RESUMO DAS OPÇÕES

| Opção | Facilidade | Tempo | Requer | Recomendado |
|-------|------------|-------|--------|-------------|
| **1. Docker** | ⭐⭐⭐⭐⭐ Muito Fácil | 5-10 min | Docker instalado | ✅ **SIM** |
| **2. MySQL Local** | ⭐⭐⭐ Médio | 30-60 min | Acesso root | ⚠️ Se não tiver Docker |
| **3. MySQL Remoto** | ⭐⭐⭐⭐ Fácil | 10 min | Servidor MySQL externo | ⚠️ Para produção |

---

## 🐳 OPÇÃO 1: USAR DOCKER (RECOMENDADO)

### ✅ Por que usar Docker?
- ✅ **Mais fácil e rápido**
- ✅ Não "suja" o sistema
- ✅ Configuração isolada
- ✅ Fácil de remover depois
- ✅ **Já está configurado no projeto** (docker-compose.yml)

### 📦 Passo 1: Instalar Docker

```bash
# Para Debian/Ubuntu/Linux Mint
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Adicionar seu usuário ao grupo docker (para não precisar de sudo)
sudo usermod -aG docker $USER

# Aplicar mudanças (ou fazer logout/login)
newgrp docker

# Verificar instalação
docker --version
docker-compose --version
```

**Resultado esperado:**
```
Docker version 24.0.x
Docker Compose version v2.x.x
```

### 🚀 Passo 2: Iniciar MySQL via Docker

```bash
# Entrar no diretório do projeto
cd /caminho/para/Support-Solo-Sondagens---El-tronico-Registro-de-Ponto

# Iniciar APENAS o MySQL (não precisa subir todos os containers)
docker-compose up -d mysql

# Verificar se está rodando
docker-compose ps

# Ver logs do MySQL (para confirmar que iniciou)
docker-compose logs -f mysql
```

**Aguardar aparecer:**
```
mysql_1  | [Server] /usr/sbin/mysqld: ready for connections.
```

### ✅ Passo 3: Verificar Conexão

```bash
# Testar conexão
php public/test-db-connection.php

# OU
curl http://localhost:8080/test-db-connection.php
```

**Deve aparecer:** ✅ CONEXÃO ESTABELECIDA COM SUCESSO!

### 🎯 Passo 4: Executar Migrations

```bash
# Criar estrutura do banco de dados
php spark migrate

# Criar usuário admin (seguir instruções)
php spark shield:user create
```

### 🎊 Pronto! Sistema Funcionando

```bash
# Iniciar servidor de desenvolvimento
php spark serve

# Acessar no navegador
http://localhost:8080
```

---

## 💻 OPÇÃO 2: INSTALAR MYSQL LOCALMENTE

### ⚠️ Use esta opção se:
- Não pode/quer instalar Docker
- Tem acesso root ao sistema
- Quer MySQL permanente no sistema

### 📦 Passo 1: Instalar MySQL Server

#### Para Debian/Ubuntu/Linux Mint:
```bash
# Atualizar pacotes
sudo apt-get update

# Instalar MySQL Server
sudo apt-get install mysql-server -y

# Verificar instalação
mysql --version
```

#### Para CentOS/RHEL/Fedora:
```bash
# Instalar MySQL
sudo dnf install mysql-server -y

# OU (CentOS 7)
sudo yum install mysql-server -y
```

#### Para macOS:
```bash
# Usando Homebrew
brew install mysql

# Iniciar MySQL
brew services start mysql
```

### 🔧 Passo 2: Configurar MySQL

```bash
# Iniciar serviço
sudo systemctl start mysql
sudo systemctl enable mysql  # Iniciar automaticamente no boot

# Verificar status
sudo systemctl status mysql
```

**Deve aparecer:** `Active: active (running)`

### 🔐 Passo 3: Configurar Segurança (Opcional mas Recomendado)

```bash
# Executar script de segurança
sudo mysql_secure_installation

# Responder:
# - Set root password? Y -> Definir senha forte
# - Remove anonymous users? Y
# - Disallow root login remotely? Y
# - Remove test database? Y
# - Reload privilege tables? Y
```

### 🗄️ Passo 4: Criar Banco de Dados

```bash
# Conectar ao MySQL como root
sudo mysql -u root -p
# OU (se não tiver senha ainda)
sudo mysql
```

**Dentro do MySQL, executar:**
```sql
-- Criar banco de dados
CREATE DATABASE ponto_eletronico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Criar usuário (OPCIONAL - mais seguro que usar root)
CREATE USER 'ponto_user'@'localhost' IDENTIFIED BY 'SenhaForte123!';

-- Dar permissões
GRANT ALL PRIVILEGES ON ponto_eletronico.* TO 'ponto_user'@'localhost';

-- Aplicar mudanças
FLUSH PRIVILEGES;

-- Sair
EXIT;
```

### ⚙️ Passo 5: Atualizar Configuração (.env)

Se criou usuário específico, editar `.env`:

```bash
nano .env
```

**Alterar:**
```ini
database.default.hostname = localhost
database.default.database = ponto_eletronico
database.default.username = ponto_user
database.default.password = SenhaForte123!
database.default.port = 3306
```

**Se usar root (não recomendado para produção):**
```ini
database.default.username = root
database.default.password = SUA_SENHA_ROOT
```

### ✅ Passo 6: Testar e Configurar

```bash
# Testar conexão
php public/test-db-connection.php

# Executar migrations
php spark migrate

# Criar usuário admin
php spark shield:user create

# Iniciar sistema
php spark serve
```

---

## 🌐 OPÇÃO 3: USAR MYSQL REMOTO

### ⚠️ Use esta opção se:
- Tem acesso a um servidor MySQL em outro lugar
- Usa serviço de hospedagem compartilhada
- Tem MySQL em outra máquina da rede

### 🔧 Configuração

**1. Obter credenciais do MySQL remoto:**
- Hostname (ex: `192.168.1.100`, `mysql.seuservidor.com`)
- Porta (geralmente `3306`)
- Usuário
- Senha
- Nome do banco

**2. Editar `.env`:**
```bash
nano .env
```

**3. Atualizar configurações:**
```ini
database.default.hostname = mysql.seuservidor.com  # OU IP
database.default.database = ponto_eletronico
database.default.username = seu_usuario
database.default.password = sua_senha
database.default.port = 3306
```

**4. Testar conexão:**
```bash
php public/test-db-connection.php
```

**5. Executar migrations:**
```bash
php spark migrate
```

---

## 🚨 SOLUÇÃO DE PROBLEMAS

### Problema: "Can't connect to local MySQL server"

**Causa:** MySQL não está rodando

**Solução:**
```bash
# Verificar status
sudo systemctl status mysql

# Se não estiver rodando, iniciar
sudo systemctl start mysql

# Ver logs de erro
sudo tail -f /var/log/mysql/error.log
```

### Problema: "Access denied for user 'root'@'localhost'"

**Causa:** Senha incorreta ou usuário sem permissões

**Solução 1 - Resetar senha root:**
```bash
# Parar MySQL
sudo systemctl stop mysql

# Iniciar em modo seguro
sudo mysqld_safe --skip-grant-tables &

# Conectar sem senha
mysql -u root

# Resetar senha
FLUSH PRIVILEGES;
ALTER USER 'root'@'localhost' IDENTIFIED BY 'NovaSenha123!';
EXIT;

# Reiniciar MySQL normalmente
sudo systemctl restart mysql
```

**Solução 2 - Usar sudo:**
```bash
# Em alguns sistemas, root só funciona com sudo
sudo mysql -u root
```

### Problema: "Database 'ponto_eletronico' doesn't exist"

**Solução:**
```bash
mysql -u root -p <<EOF
CREATE DATABASE ponto_eletronico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EOF
```

### Problema: Docker não encontrado

**Solução:**
```bash
# Instalar Docker
curl -fsSL https://get.docker.com | sh

# Ou manualmente (Ubuntu/Debian)
sudo apt-get update
sudo apt-get install docker.io docker-compose -y
sudo systemctl start docker
sudo systemctl enable docker
```

---

## 📊 COMPARAÇÃO DAS OPÇÕES

### Vantagens e Desvantagens

#### 🐳 Docker
**Vantagens:**
- ✅ Instalação rápida (5-10 minutos)
- ✅ Não afeta o sistema
- ✅ Fácil de remover
- ✅ Já configurado no projeto
- ✅ Inclui Redis e DeepFace

**Desvantagens:**
- ❌ Requer Docker instalado
- ❌ Usa mais memória RAM
- ❌ Precisa estar rodando para funcionar

#### 💻 MySQL Local
**Vantagens:**
- ✅ Integrado ao sistema
- ✅ Sempre disponível
- ✅ Usa menos memória que Docker
- ✅ Melhor performance

**Desvantagens:**
- ❌ Instalação mais demorada
- ❌ Requer configuração manual
- ❌ "Suja" o sistema
- ❌ Mais difícil de remover

#### 🌐 MySQL Remoto
**Vantagens:**
- ✅ Não precisa instalar nada localmente
- ✅ Configuração simples
- ✅ Ideal para produção

**Desvantagens:**
- ❌ Depende de rede
- ❌ Pode ter latência
- ❌ Precisa de servidor MySQL externo

---

## 🎯 RECOMENDAÇÃO FINAL

### Para Desenvolvimento Local:
**Use Docker** (Opção 1) - Mais prático e rápido

### Para Servidor de Produção:
**MySQL Local** (Opção 2) - Melhor performance

### Para Hospedagem Compartilhada:
**MySQL Remoto** (Opção 3) - Fornecido pelo host

---

## 🆘 PRECISA DE AJUDA?

Se encontrar problemas:

1. **Verifique os logs:**
   ```bash
   # Docker
   docker-compose logs mysql

   # MySQL Local
   sudo tail -f /var/log/mysql/error.log
   ```

2. **Execute o diagnóstico:**
   ```bash
   php public/test-db-connection.php
   php public/test-error-500.php
   ```

3. **Execute o script automático:**
   ```bash
   ./FIX_ERRO_500.sh
   ```

4. **Consulte a documentação:**
   ```bash
   cat DIAGNOSTICO_ERRO_500.md
   ```

---

## ✅ CHECKLIST DE SUCESSO

Após instalar MySQL, você deve conseguir:

- [ ] ✅ `php public/test-db-connection.php` mostra "CONEXÃO ESTABELECIDA"
- [ ] ✅ `php spark migrate` executa sem erros
- [ ] ✅ `php spark serve` inicia o servidor
- [ ] ✅ `http://localhost:8080` mostra página de login (não erro 500)
- [ ] ✅ Consegue fazer login com usuário criado

---

**Data:** 2025-11-16
**Versão:** 1.0
**Sistema:** Ponto Eletrônico Brasileiro
