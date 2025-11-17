# 🗄️ CONFIGURAR BANCO DE DADOS EM PRODUÇÃO

## ⚠️ IMPORTANTE: Este Ambiente Não Permite Instalação

Você está em um ambiente que **não permite instalar MySQL localmente**.

Este é um ambiente típico de:
- Hospedagem compartilhada (cPanel)
- Container/sandbox de desenvolvimento
- Ambiente cloud gerenciado

---

## ✅ SOLUÇÃO: Usar MySQL do Servidor de Hospedagem

### 📋 PASSO 1: Obter Credenciais MySQL

**No painel de controle da sua hospedagem (cPanel, Plesk, etc.):**

1. Acesse **MySQL Databases** ou **Bancos de Dados MySQL**
2. Crie um novo banco de dados chamado: `ponto_eletronico`
3. Crie um usuário MySQL
4. Adicione o usuário ao banco com **TODAS PERMISSÕES**
5. Anote as credenciais:

```
Hostname: _______________________ (geralmente 'localhost')
Database: ponto_eletronico
Username: _______________________
Password: _______________________
Port: 3306 (padrão)
```

**Exemplos comuns de hostname:**
- `localhost` (maioria dos casos)
- `127.0.0.1`
- `mysql.seudominio.com.br`
- Algum IP fornecido pela hospedagem

---

### 📋 PASSO 2: Atualizar Arquivo .env

Execute os comandos abaixo substituindo pelos seus dados:

```bash
# Exemplo com dados fictícios - SUBSTITUA PELOS SEUS!

# Editar .env
nano .env

# OU usar sed para atualizar automaticamente:
sed -i 's/database.default.hostname = .*/database.default.hostname = localhost/' .env
sed -i 's/database.default.database = .*/database.default.database = ponto_eletronico/' .env
sed -i 's/database.default.username = .*/database.default.username = seu_usuario_mysql/' .env
sed -i 's/database.default.password = .*/database.default.password = sua_senha_mysql/' .env
```

**Ou edite manualmente o arquivo `.env`:**

```ini
#--------------------------------------------------------------------
# DATABASE
#--------------------------------------------------------------------
database.default.hostname = localhost           # ← ALTERE AQUI
database.default.database = ponto_eletronico    # ← ALTERE AQUI
database.default.username = seu_usuario_mysql   # ← ALTERE AQUI
database.default.password = sua_senha_mysql     # ← ALTERE AQUI
database.default.DBDriver = MySQLi
database.default.port = 3306
database.default.charset = utf8mb4
database.default.DBCollat = utf8mb4_unicode_ci

# Environment variables for compatibility
DB_HOST = localhost                             # ← ALTERE AQUI
DB_DATABASE = ponto_eletronico                  # ← ALTERE AQUI
DB_USERNAME = seu_usuario_mysql                 # ← ALTERE AQUI
DB_PASSWORD = sua_senha_mysql                   # ← ALTERE AQUI
```

---

### 📋 PASSO 3: Testar Conexão

```bash
# Testar se as credenciais estão corretas
php public/test-db-connection.php
```

**Resultado esperado:**
```
✅ CONEXÃO ESTABELECIDA COM SUCESSO!
```

**Se der erro:**
- Verifique hostname (pode ser diferente de localhost)
- Verifique se usuário tem permissões no banco
- Verifique se senha está correta
- Verifique se o banco existe

---

### 📋 PASSO 4: Executar Migrations (Criar Tabelas)

```bash
# Criar toda estrutura do banco de dados
php spark migrate

# Verificar se criou as tabelas
php public/test-db-connection.php
```

Deve mostrar lista de tabelas criadas:
- employees
- time_punches
- justifications
- companies
- departments
- etc.

---

### 📋 PASSO 5: Criar Usuário Administrador

```bash
# Criar primeiro usuário do sistema
php spark shield:user create

# Será solicitado:
# Email: admin@empresa.com
# Username: admin
# Password: (escolha senha forte)
```

---

### 📋 PASSO 6: Testar Sistema

```bash
# Se hospedagem tem servidor web configurado, acesse:
https://seudominio.com.br

# Se for ambiente local com servidor PHP:
php spark serve
# E acesse: http://localhost:8080
```

---

## 🔧 SCRIPT DE CONFIGURAÇÃO AUTOMÁTICA

Criei um script para facilitar. Execute:

```bash
./configurar-banco-producao.sh
```

Ele vai:
1. Solicitar credenciais MySQL
2. Atualizar .env automaticamente
3. Testar conexão
4. Executar migrations
5. Criar usuário admin

---

## ⚠️ HOSPEDAGENS ESPECÍFICAS

### cPanel (HostGator, Locaweb, etc.)

1. **Criar Banco:**
   - MySQL Databases → Create Database
   - Nome: `usuario_ponto` (cPanel adiciona prefixo automaticamente)

2. **Criar Usuário:**
   - MySQL Users → Create User
   - Anotar usuário e senha

3. **Associar:**
   - Add User to Database
   - Marcar ALL PRIVILEGES

4. **Importar (se tiver SQL):**
   - phpMyAdmin → Import
   - Selecionar arquivo database.sql

### Plesk

1. Databases → Add Database
2. Criar usuário
3. Associar permissões
4. Anotar credenciais

### DirectAdmin

1. MySQL Management → Create new database
2. Seguir wizard de criação

---

## 🆘 PROBLEMAS COMUNS

### "Access denied for user"

**Causa:** Senha incorreta ou usuário sem permissões

**Solução:**
- Resetar senha no painel de controle
- Verificar se usuário tem permissões no banco
- Verificar se está usando usuário correto (cPanel adiciona prefixo)

### "Unknown database 'ponto_eletronico'"

**Causa:** Banco não existe

**Solução:**
- Criar banco no painel de controle
- Verificar se nome está correto (pode ter prefixo)
- Atualizar nome no .env

### "Can't connect to MySQL server"

**Causa:** Hostname incorreto

**Solução:**
- Verificar hostname correto na hospedagem
- Pode ser diferente de 'localhost'
- Algumas hospedagens usam IP específico

### "Too many connections"

**Causa:** Limite de conexões atingido

**Solução:**
- Aguardar alguns minutos
- Verificar plano de hospedagem (pode ter limite)
- Fechar conexões abertas

---

## 📊 CHECKLIST DE SUCESSO

Após configurar, você deve ter:

- [ ] ✅ Banco de dados criado no servidor
- [ ] ✅ Usuário MySQL criado e associado
- [ ] ✅ Arquivo .env atualizado com credenciais
- [ ] ✅ `php public/test-db-connection.php` retorna sucesso
- [ ] ✅ `php spark migrate` executado sem erros
- [ ] ✅ Tabelas criadas no banco (visíveis no phpMyAdmin)
- [ ] ✅ Usuário admin criado
- [ ] ✅ Sistema acessível sem erro 500

---

## 📞 PRÓXIMOS PASSOS

Depois que o banco estiver configurado:

1. **Configurar permissões dos diretórios:**
   ```bash
   ./setup-permissions.sh
   ```

2. **Configurar .htaccess** (se Apache)

3. **Configurar SSL/HTTPS** (Let's Encrypt)

4. **Importar funcionários**

5. **Configurar email no .env**

6. **Testar funcionalidades principais**

---

**Data:** 2025-11-16
**Sistema:** Ponto Eletrônico Brasileiro
