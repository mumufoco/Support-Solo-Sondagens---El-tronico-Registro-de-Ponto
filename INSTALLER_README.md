# 🚀 Instalador Web - Sistema de Ponto Eletrônico

**Versão:** 2.0.0
**Data:** 18/11/2024
**Status:** ✅ Completo e Testado

---

## 📋 Visão Geral

Este é o **instalador web interativo** do Sistema de Ponto Eletrônico Brasileiro. Ele foi completamente reescrito para oferecer:

✅ **Interface visual amigável**
✅ **Teste de conexão MySQL ANTES de prosseguir**
✅ **Validação em cada etapa**
✅ **Feedback em tempo real**
✅ **Criação automática do banco de dados**
✅ **Proteção contra reinstalação acidental**

---

## 🎯 Como Usar

### Passo 1: Acessar o Instalador

Após fazer upload dos arquivos para o servidor, acesse:

```
http://seu-dominio.com/install
```

ou em desenvolvimento local:

```
http://localhost:8080/install
```

### Passo 2: Seguir o Assistente

O instalador possui 5 etapas:

#### **Etapa 1: Verificação de Requisitos** ✓
- Verifica versão do PHP (≥ 8.1)
- Verifica extensões necessárias (mysqli, pdo_mysql, intl, etc)
- Verifica permissões de escrita

**Se algo falhar:**
```bash
# Instalar extensões
sudo apt-get install php8.4-{mysqli,pdo-mysql,intl,mbstring,json,xml,curl,gd,zip}

# Corrigir permissões
sudo chmod -R 755 /var/www/ponto-eletronico
sudo chmod -R 777 /var/www/ponto-eletronico/writable
```

---

#### **Etapa 2: Configuração do Banco de Dados** 🔍

Esta é a etapa **MAIS IMPORTANTE** - com teste de conexão obrigatório!

**Campos:**
- **Host:** localhost (ou IP do servidor MySQL)
- **Porta:** 3306 (padrão)
- **Nome do Banco:** ponto_eletronico
- **Usuário:** root (ou usuário com permissões CREATE)
- **Senha:** senha do MySQL

**🔍 TESTE DE CONEXÃO OBRIGATÓRIO:**

1. Preencha todos os campos
2. Clique em **"Testar Conexão com MySQL"**
3. Aguarde a validação completa:
   - ✅ Conecta ao servidor MySQL
   - ✅ Verifica versão do MySQL
   - ✅ Cria o banco (se não existir)
   - ✅ Testa permissões CREATE/INSERT/SELECT
   - ✅ Valida configurações do servidor

4. **Só pode prosseguir se o teste passar!**

**Console de Output:**
```
Tentando conectar em localhost:3306...
✅ Conexão com MySQL estabelecida!
Versão do MySQL: 8.0.35
Banco de dados 'ponto_eletronico' não existe. Tentando criar...
✅ Banco de dados 'ponto_eletronico' criado com sucesso!
✅ Permissões de CREATE/DROP validadas.
✅ Permissões de INSERT/SELECT validadas.
Max Connections: 151

✅ Conexão testada com sucesso! Todas as permissões validadas.
```

**Erros Comuns e Soluções:**

| Erro | Causa | Solução |
|------|-------|---------|
| `Access denied for user` | Senha incorreta | Verifique credenciais MySQL |
| `Can't connect to MySQL` | MySQL não rodando | `sudo systemctl start mysql` |
| `Access denied to database` | Sem permissões | `GRANT ALL ON ponto_eletronico.* TO 'user'@'%'` |

---

#### **Etapa 3: Executar Migrations** 📦

Cria a estrutura de tabelas no banco de dados.

**O que é criado:**
- `employees` - Funcionários
- `timesheets` - Registros de ponto
- `remember_tokens` - Tokens "Lembrar de mim"
- `audit_logs` - Logs de auditoria
- `biometric_templates` - Templates biométricos criptografados
- `leave_requests` - Solicitações de férias/afastamento
- `warnings` - Advertências
- `geofences` - Geofencing
- E outras tabelas...

**Processo:**
1. Clique em **"Executar Migrations"**
2. Aguarde a criação das tabelas
3. Verifique o console de output

**Output esperado:**
```
Iniciando execução das migrations...
✅ Conexão com banco estabelecida.
Encontradas 15 migrations.
✅ Todas as migrations executadas com sucesso!
Tabelas criadas: employees, timesheets, remember_tokens, audit_logs, ...

✅ Estrutura do banco de dados criada com sucesso!
```

---

#### **Etapa 4: Dados Iniciais** 👤

Cria o usuário administrador e dados de exemplo (opcionais).

**Campos Obrigatórios:**
- **Nome:** Nome completo do administrador
- **E-mail:** Email para login (único)
- **Senha:** Mínimo 8 caracteres
- **Confirmar Senha:** Deve ser idêntica

**Recomendações de Senha:**
- Pelo menos 8 caracteres
- Letras maiúsculas e minúsculas
- Números
- Caracteres especiais (!@#$%^&*)

**Exemplo de senha forte:**
```
Admin@2024!Forte
```

**Dados de Exemplo (Opcional):**
- [ ] Incluir dados de exemplo

Se marcado, cria:
- Gestor de teste (gestor@teste.com / Gestor@123456)
- Funcionário de teste (funcionario@teste.com / Func@123456)

**Output esperado:**
```
Iniciando inserção de dados iniciais...
✅ Usuário administrador criado: admin@exemplo.com
✅ Dados de exemplo criados.

✅ Dados iniciais inseridos com sucesso!

✅ Instalação Concluída!
Credenciais de Acesso:
E-mail: admin@exemplo.com
Senha: (a que você definiu)
```

---

#### **Etapa 5: Finalização** 🎉

Instalação concluída!

**O que foi feito:**
- ✅ Banco de dados MySQL configurado
- ✅ Todas as tabelas criadas
- ✅ Usuário administrador criado
- ✅ Arquivo `.env` configurado com encryption key segura
- ✅ Arquivo `writable/installed.lock` criado (impede reinstalação)

**Próximos passos:**
1. **Fazer login** com as credenciais criadas
2. **Alterar senha** do administrador
3. **Configurar ambiente de produção** (se aplicável)
4. **Revisar guias de segurança**

---

## 🔒 Segurança do Instalador

### Proteção Contra Reinstalação

Ao completar a instalação, um arquivo `writable/installed.lock` é criado.

**Este arquivo impede:**
- Acesso à rota `/install` novamente
- Reinstalação acidental
- Sobrescrita de dados existentes

**Para reinstalar (CUIDADO - apaga dados!):**

1. **Ambiente de desenvolvimento:**
   ```
   http://localhost:8080/install/force-reinstall
   ```

2. **Manualmente:**
   ```bash
   # Deletar arquivo de lock
   rm writable/installed.lock

   # Deletar .env
   rm .env

   # Recriar banco (APAGA TUDO!)
   mysql -u root -p -e "DROP DATABASE ponto_eletronico; CREATE DATABASE ponto_eletronico;"
   ```

### Arquivo .env Gerado

O instalador cria automaticamente o arquivo `.env` com:

```ini
CI_ENVIRONMENT = production
app.baseURL = 'http://localhost/'
app.forceGlobalSecureRequests = false

database.default.hostname = localhost
database.default.database = ponto_eletronico
database.default.username = ponto_user
database.default.password = [senha fornecida]
database.default.DBDriver = MySQLi
database.default.port = 3306

encryption.key = base64:[chave de 32 bytes gerada automaticamente]

session.driver = 'CodeIgniter\Session\Handlers\FileHandler'
session.expiration = 7200
session.matchIP = true
security.csrfProtection = 'session'
```

**🔐 IMPORTANTE:**
- A `encryption.key` é gerada automaticamente (32 bytes seguros)
- **NUNCA** compartilhe este arquivo
- **NUNCA** commite no Git (já está no `.gitignore`)

---

## ⚙️ Configuração Pós-Instalação

### Para Ambiente de Produção

Edite o arquivo `.env`:

```ini
# Alterar ambiente
CI_ENVIRONMENT = production

# Forçar HTTPS
app.forceGlobalSecureRequests = true

# Configurar domínio real
app.baseURL = 'https://seu-dominio.com/'

# Desativar debug
CI_DEBUG = false
```

### Configurar SSL/HTTPS

```bash
# Instalar Certbot
sudo apt-get install certbot python3-certbot-nginx

# Obter certificado
sudo certbot --nginx -d seu-dominio.com

# Renovação automática
sudo crontab -e
# Adicionar: 0 3 * * * certbot renew --quiet
```

### Configurar Firewall

```bash
# Permitir HTTP/HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Bloquear MySQL externamente
sudo ufw deny 3306/tcp

# Ativar firewall
sudo ufw enable
```

### Backup Automático

```bash
# Criar script de backup
sudo nano /usr/local/bin/backup_ponto.sh

# Adicionar no crontab
sudo crontab -e
# 0 2 * * * /usr/local/bin/backup_ponto.sh
```

**Ver detalhes em:** `PRODUCTION_SETUP_README.md`

---

## 🐛 Troubleshooting

### Problema 1: "Can't connect to MySQL server"

**Causa:** MySQL não está rodando

**Solução:**
```bash
# Verificar status
sudo systemctl status mysql

# Iniciar MySQL
sudo systemctl start mysql

# Ativar na inicialização
sudo systemctl enable mysql
```

---

### Problema 2: "Access denied for user"

**Causa:** Credenciais incorretas ou sem permissões

**Solução:**
```bash
# Resetar senha do root MySQL
sudo mysql
ALTER USER 'root'@'localhost' IDENTIFIED BY 'nova_senha';
FLUSH PRIVILEGES;
EXIT;

# Ou criar novo usuário
mysql -u root -p
CREATE USER 'ponto_user'@'%' IDENTIFIED BY 'senha_forte';
GRANT ALL PRIVILEGES ON ponto_eletronico.* TO 'ponto_user'@'%';
FLUSH PRIVILEGES;
EXIT;
```

---

### Problema 3: "Failed to create database"

**Causa:** Usuário sem permissão CREATE DATABASE

**Solução:**
```bash
mysql -u root -p
GRANT ALL PRIVILEGES ON *.* TO 'ponto_user'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EXIT;
```

---

### Problema 4: "writable/ not writable"

**Causa:** Permissões de arquivo

**Solução:**
```bash
# Definir proprietário correto
sudo chown -R www-data:www-data /var/www/ponto-eletronico

# Definir permissões
sudo chmod -R 755 /var/www/ponto-eletronico
sudo chmod -R 777 /var/www/ponto-eletronico/writable
```

---

### Problema 5: Página em branco após instalação

**Causa:** Erro no PHP ou .env malformado

**Solução:**
```bash
# Verificar logs
sudo tail -f /var/log/nginx/error.log
# ou
sudo tail -f /var/log/apache2/error.log

# Verificar logs do PHP
tail -f writable/logs/*.log

# Verificar sintaxe do .env
cat .env
# Procurar por aspas não fechadas ou caracteres especiais
```

---

### Problema 6: Migrations falham

**Causa:** Migrations já executadas ou erro de sintaxe

**Solução:**
```bash
# Ver status das migrations
php spark migrate:status

# Rollback (CUIDADO - apaga dados!)
php spark migrate:rollback

# Tentar novamente
php spark migrate

# Se persistir, recriar banco
mysql -u root -p -e "DROP DATABASE ponto_eletronico; CREATE DATABASE ponto_eletronico;"
# E rodar instalador novamente
```

---

## 📊 Checklist de Instalação

Use este checklist para garantir instalação completa:

### Pré-Instalação
- [ ] MySQL instalado e rodando
- [ ] PHP 8.1+ instalado com extensões necessárias
- [ ] Composer instalado (para desenvolvimento)
- [ ] Permissões corretas em `writable/`
- [ ] Arquivos do sistema uploaded para servidor

### Durante Instalação
- [ ] Etapa 1: Todos os requisitos ✅ verdes
- [ ] Etapa 2: Teste de conexão MySQL ✅ passou
- [ ] Etapa 3: Migrations executadas sem erros
- [ ] Etapa 4: Usuário admin criado com senha forte
- [ ] Etapa 5: Arquivo `.env` criado

### Pós-Instalação
- [ ] Login com credenciais do admin funciona
- [ ] Dashboard carrega corretamente
- [ ] Alterar senha do admin
- [ ] Configurar `.env` para produção (se aplicável)
- [ ] Configurar SSL/HTTPS
- [ ] Configurar firewall
- [ ] Configurar backup automático
- [ ] Revisar guias de segurança

---

## 🔧 Arquivos do Instalador

```
app/Controllers/InstallController.php    - Controller principal (500+ linhas)
app/Views/install/
  ├── layout.php                         - Layout base com CSS/JS
  ├── welcome.php                        - Tela inicial
  ├── requirements.php                   - Verificação de requisitos
  ├── database.php                       - Config MySQL + teste conexão
  ├── migrations.php                     - Execução das migrations
  ├── seed.php                           - Dados iniciais
  └── finish.php                         - Finalização
app/Config/Routes.php                    - Rotas do instalador
writable/installed.lock                  - Arquivo de lock (criado após)
```

---

## 🆚 Diferenças da Versão Anterior

| Aspecto | Versão 1.0 (Shell) | Versão 2.0 (Web) |
|---------|-------------------|------------------|
| **Interface** | Linha de comando | Interface web visual |
| **Validação** | Após tentar salvar | ANTES de prosseguir |
| **Teste de Conexão** | Não tinha | ✅ Obrigatório |
| **Feedback** | Texto simples | Console em tempo real |
| **Erro de Conexão** | Instalação falhava | Avisa antes de salvar |
| **Criação de DB** | Manual | ✅ Automática |
| **UX** | Técnica | ✅ Amigável |
| **Proteção** | Nenhuma | ✅ Lock file |

---

## 📞 Suporte

Se encontrar problemas:

1. **Verifique os logs:**
   ```bash
   tail -f writable/logs/*.log
   ```

2. **Consulte documentação:**
   - `PRODUCTION_SETUP_README.md`
   - `SECURITY_TESTING_GUIDE.md`
   - `MYSQL_INSTALLATION_GUIDE.md`

3. **Teste manualmente:**
   ```bash
   # Testar conexão MySQL
   mysql -h localhost -u ponto_user -p ponto_eletronico

   # Verificar tabelas
   SHOW TABLES;

   # Verificar usuários
   SELECT id, name, email, role FROM employees;
   ```

---

## 🎯 Resultado Final

Após instalação bem-sucedida, você terá:

✅ **Sistema Funcionando:**
- MySQL configurado
- 15+ tabelas criadas
- Usuário admin pronto
- `.env` configurado

✅ **Segurança:**
- Encryption key única
- CSRF protection ativo
- Session segura
- Passwords com BCrypt

✅ **Pronto para Usar:**
- Login funcionando
- Dashboard acessível
- Todos os módulos operacionais
- API REST disponível

---

**Desenvolvido com ❤️ para Support Solo Sondagens**
**Versão 2.0.0 | 18/11/2024**
