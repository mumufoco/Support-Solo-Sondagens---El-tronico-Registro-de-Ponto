# 🔧 Como Corrigir Erro de Sessão (writable/session)

## ❌ Erro que Você Está Vendo

```
CRITICAL - ErrorException: touch(): Unable to create file writable/session/ci_session...
because No such file or directory
```

## 🔍 Causa do Problema

O CodeIgniter 4 tenta criar arquivos de sessão em `writable/session/` mas:

1. **Diretório não existe** (raro, mas possível se não foi enviado via FTP)
2. **Sem permissões de escrita** (mais comum em shared hosting)
3. **Owner incorreto** (diretório pertence a outro usuário)

---

## ✅ SOLUÇÃO 1: Via cPanel (Mais Fácil)

### Passo 1: Acesse o Gerenciador de Arquivos

1. Faça login no **cPanel**
2. Vá em **Gerenciador de Arquivos** (File Manager)
3. Navegue até a pasta do projeto

### Passo 2: Verifique se o Diretório Existe

1. Abra a pasta `writable/`
2. Verifique se a pasta `session/` existe
   - ✅ **Se EXISTE:** Vá para o Passo 3
   - ❌ **Se NÃO EXISTE:** Clique em "Nova Pasta" → Nome: `session` → Criar

### Passo 3: Ajuste as Permissões

1. **Clique com botão direito** na pasta `writable/`
2. Selecione **"Alterar Permissões"** ou **"Change Permissions"**
3. Configure para **`755`** (rwxr-xr-x):
   - ✅ Owner: Read, Write, Execute
   - ✅ Group: Read, Execute
   - ✅ Public: Read, Execute

4. ✅ **IMPORTANTE:** Marque a opção **"Recurse into subdirectories"** ou **"Aplicar recursivamente"**

5. Clique em **"Alterar"** ou **"Change Permissions"**

### Passo 4: Se Ainda Não Funcionar (Shared Hosting)

Em alguns servidores compartilhados, você precisa de permissões mais abertas:

1. **Clique com botão direito** na pasta `writable/`
2. Selecione **"Alterar Permissões"**
3. Configure para **`777`** (rwxrwxrwx):
   - ✅ Marque TODAS as caixas
   - ✅ Marque "Recurse into subdirectories"
4. Clique em **"Alterar"**

**⚠️ NOTA DE SEGURANÇA:**
- `777` é menos seguro mas necessário em alguns shared hostings
- A pasta `writable/` está protegida por `.htaccess` (sem acesso web)
- Em VPS/Dedicado, use `755` sempre que possível

---

## ✅ SOLUÇÃO 2: Via SSH (Avançado)

Se você tem acesso SSH ao servidor:

### Método A: Script Automático (Recomendado)

```bash
# Faça upload do arquivo setup-permissions.sh para a raiz do projeto

# Torne o script executável
chmod +x setup-permissions.sh

# Execute o script
./setup-permissions.sh
```

O script irá:
- ✅ Criar todos os diretórios necessários
- ✅ Configurar permissões corretas (775/664)
- ✅ Criar arquivos de segurança (.htaccess)
- ✅ Prevenir listagem de diretórios

### Método B: Manual

```bash
# Entre no diretório do projeto
cd /caminho/para/o/projeto

# Crie os diretórios (se não existirem)
mkdir -p writable/session
mkdir -p writable/cache
mkdir -p writable/logs
mkdir -p writable/uploads
mkdir -p writable/biometric/faces
mkdir -p writable/biometric/fingerprints

# Configure permissões
chmod -R 755 writable/
find writable -type d -exec chmod 775 {} \;
find writable -type f -exec chmod 664 {} \;

# Verifique se está gravável
[ -w writable/session ] && echo "✓ Session gravável" || echo "✗ Session NÃO gravável"
```

### Se AINDA não funcionar (Shared Hosting):

```bash
# Use 777 como último recurso
chmod -R 777 writable/
```

---

## ✅ SOLUÇÃO 3: Via FTP (FileZilla, WinSCP, etc.)

### Passo 1: Conecte-se via FTP

1. Abra seu cliente FTP (FileZilla, WinSCP, etc.)
2. Conecte-se ao servidor
3. Navegue até a pasta do projeto

### Passo 2: Verifique o Diretório

1. Entre na pasta `writable/`
2. Verifique se a pasta `session/` existe
   - Se não existir: **Clique direito → Criar Diretório → Nome: `session`**

### Passo 3: Ajuste Permissões

**No FileZilla:**
1. Clique direito em `writable/`
2. **File Permissions** ou **Permissões de Arquivo**
3. Digite **`755`** no campo numérico
4. ✅ Marque **"Recurse into subdirectories"**
5. ✅ Marque **"Apply to directories only"**
6. Clique **OK**

**Repita para arquivos:**
1. Clique direito em `writable/`
2. **File Permissions**
3. Digite **`644`** no campo numérico
4. ✅ Marque **"Recurse into subdirectories"**
5. ✅ Marque **"Apply to files only"**
6. Clique **OK**

---

## 🧪 Testando a Correção

Após aplicar as correções:

1. **Limpe o cache do navegador** (Ctrl+Shift+Delete)
2. Acesse o site: `http://ponto.supportsondagens.com.br/`
3. Se aparecer a página de login = **✅ SUCESSO!**
4. Se ainda der erro = Veja "Diagnóstico Avançado" abaixo

---

## 🔍 Diagnóstico Avançado (Se o Problema Persistir)

### Verifique as Permissões Atuais

**Via cPanel/FTP:**
- Pasta `writable/`: deve mostrar `755` ou `777`
- Pasta `writable/session/`: deve mostrar `755` ou `777`

**Via SSH:**
```bash
ls -la writable/
ls -la writable/session/
```

**Saída esperada:**
```
drwxrwxr-x  (775) ou drwxr-xr-x (755) ou drwxrwxrwx (777)
```

### Teste de Gravação

**Via SSH:**
```bash
# Teste se consegue criar arquivo
touch writable/session/test.txt

# Se funcionar, você verá:
# (sem erro)

# Limpe o teste
rm writable/session/test.txt
```

**Se der erro:** O usuário do PHP não tem permissão de escrita

### Identifique o Usuário do PHP

**Via SSH:**
```bash
# Descubra qual usuário o PHP está usando
ps aux | grep php
# ou
ps aux | grep apache
# ou
ps aux | grep nginx
```

**Saída típica:**
```
www-data   1234  0.0  1.2  ...  php-fpm
apache     1234  0.0  1.2  ...  httpd
```

**Solução:**
```bash
# Mude o owner do diretório writable para o usuário correto
chown -R www-data:www-data writable/
# ou
chown -R apache:apache writable/
# ou (shared hosting - substitua USERNAME pelo seu usuário)
chown -R USERNAME:USERNAME writable/
```

---

## 📋 Checklist de Verificação

- [ ] Pasta `writable/session/` existe
- [ ] Permissões de `writable/` são `755` ou `777`
- [ ] Permissões aplicadas recursivamente em subpastas
- [ ] Arquivo `.htaccess` existe em `writable/`
- [ ] Cache do navegador foi limpo
- [ ] Testou acessar o site novamente

---

## 🆘 Se NADA Funcionar

### Diagnóstico de LOG

**Via SSH ou cPanel File Manager**, abra:
```
writable/logs/log-[DATA_HOJE].log
```

Procure por:
- Mensagens de erro relacionadas a `session`
- Mensagens de erro relacionadas a `writable`
- Informações sobre permissões negadas

### Alternativa: Use Sessão em Banco de Dados

Se o problema persistir, você pode configurar o CodeIgniter para salvar sessões no banco de dados ao invés de arquivos:

**Edite `app/Config/App.php`:**
```php
// Linha ~92
public string $sessionDriver = 'CodeIgniter\Session\Handlers\DatabaseHandler';
public string $sessionSavePath = 'ci_sessions';  // Nome da tabela
```

**Crie a tabela de sessões via SSH:**
```bash
php spark session:migration
php spark migrate --all
```

**Ou execute manualmente no phpMyAdmin:**
```sql
CREATE TABLE ci_sessions (
    id varchar(128) NOT NULL,
    ip_address varchar(45) NOT NULL,
    timestamp int(10) unsigned DEFAULT 0 NOT NULL,
    data blob NOT NULL,
    PRIMARY KEY (id),
    KEY ci_sessions_timestamp (timestamp)
);
```

---

## ✅ Arquivos Criados para Ajudar

Após executar o script `setup-permissions.sh`, os seguintes arquivos foram criados:

| Arquivo | Propósito |
|---------|-----------|
| `writable/.htaccess` | Bloqueia acesso web ao diretório writable |
| `writable/*/index.html` | Previne listagem de diretórios |
| Diretórios necessários | session, cache, logs, uploads, biometric, exports |

---

## 📞 Suporte

Se após seguir todos os passos o problema persistir:

1. Verifique os logs em `writable/logs/`
2. Anote a mensagem de erro EXATA
3. Verifique as permissões atuais com `ls -la writable/`
4. Entre em contato com o suporte do servidor compartilhado

**Informações úteis para o suporte:**
- PHP Version: (verifique em phpinfo)
- Servidor: Apache ou nginx
- Tipo de hospedagem: Shared, VPS, Dedicado
- Permissões atuais de `writable/`
