# 🚀 Instalação no Servidor Compartilhado

URL: **https://ponto.supportsondagens.com.br**

## 📋 Passo a Passo

### 1️⃣ Configurar Credenciais do MySQL

Edite o arquivo `.env` e preencha as credenciais do MySQL:

```env
database.default.hostname = localhost
database.default.database = SEU_BANCO_DE_DADOS
database.default.username = SEU_USUARIO
database.default.password = SUA_SENHA
database.default.DBDriver = MySQLi
database.default.port = 3306
```

**Onde encontrar as credenciais:**
- Entre no **cPanel** ou **Plesk** do seu servidor
- Vá em **MySQL Databases** ou **Banco de Dados MySQL**
- Anote o nome do banco, usuário e senha

### 2️⃣ Criar o Banco de Dados

No cPanel/Plesk:
1. Crie um novo banco de dados MySQL
2. Crie um usuário MySQL
3. Associe o usuário ao banco com **TODAS as permissões**

### 3️⃣ Verificar Permissões

Certifique-se que os diretórios writable têm permissão 777:

```bash
chmod 777 writable
chmod 777 writable/cache
chmod 777 writable/logs
chmod 777 writable/session
chmod 777 writable/uploads
chmod 777 writable/database
```

### 4️⃣ Executar o Instalador

Acesse: **https://ponto.supportsondagens.com.br/install.php**

O instalador vai:
- ✅ Verificar requisitos do sistema
- ✅ Testar conexão com MySQL
- ✅ Criar tabelas do banco de dados
- ✅ Popular dados iniciais
- ✅ Criar usuário administrador

### 5️⃣ Login no Sistema

Após a instalação:
- URL de Login: **https://ponto.supportsondagens.com.br/auth/login**
- Email: Use o email criado no instalador
- Senha: Use a senha criada no instalador

---

## 🔧 Problemas Comuns

### ❌ Erro "Unable to connect to the database"

**Solução:**
1. Verifique se as credenciais no `.env` estão corretas
2. Verifique se o usuário MySQL tem permissão no banco
3. Teste a conexão no instalador antes de continuar

### ❌ Erro 500 em todas as páginas

**Soluções:**
1. Verifique os logs: `writable/logs/log-YYYY-MM-DD.log`
2. Certifique-se que `writable` tem permissão 777
3. Verifique se o `.env` está configurado corretamente
4. Acesse `/debug.php` para diagnóstico completo

### ❌ Página em branco ou timeout

**Solução:**
1. Aumente o limite de memória no PHP (128MB mínimo)
2. Aumente o tempo de execução (60 segundos mínimo)
3. Verifique se o `vendor/` foi enviado completamente

---

## 📝 Configurações Recomendadas para PHP

No cPanel, configure:

```ini
memory_limit = 256M
max_execution_time = 60
upload_max_filesize = 10M
post_max_size = 10M
```

---

## ✅ Verificação Final

Após instalação, teste:

- [ ] `/health` - Deve retornar status "healthy"
- [ ] `/auth/login` - Deve mostrar tela de login
- [ ] `/dashboard` - Deve redirecionar para login (se não autenticado)
- [ ] Login com admin - Deve acessar dashboard admin

---

## 🆘 Suporte

Se houver problemas:
1. Acesse `/debug.php` e copie a saída
2. Verifique `writable/logs/` para mensagens de erro
3. Verifique se MySQL está acessível do servidor compartilhado
