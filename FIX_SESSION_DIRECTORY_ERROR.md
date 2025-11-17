# 🔴 FIX URGENTE: Diretório writable/session Não Existe

## ❌ ERRO ATUAL

```
CRITICAL - ErrorException: touch(): Unable to create file
writable/session/ci_sessionbbd4684434608857915fe953fd7dea35
because No such file or directory
```

---

## 🎯 CAUSA

O diretório `writable/session` **não existe** no servidor de produção!

---

## ✅ SOLUÇÃO RÁPIDA (2 minutos)

### Via SSH (Recomendado)

```bash
# 1. Conectar ao servidor via SSH
ssh usuario@ponto.supportsondagens.com.br

# 2. Ir para o diretório do projeto
cd ~/public_html/ponto.supportsondagens.com.br
# OU
cd public_html/ponto.supportsondagens.com.br

# 3. Executar script de setup
bash setup-production-directories.sh
```

### Via Terminal do cPanel

1. **Acessar cPanel**
2. **Terminal** (ou Advanced Terminal)
3. **Executar:**

```bash
cd public_html/ponto.supportsondagens.com.br
bash setup-production-directories.sh
```

### Via File Manager do cPanel (Manual)

Se não tem acesso SSH/Terminal:

1. **Acessar cPanel → File Manager**
2. **Navegar até:** `public_html/ponto.supportsondagens.com.br/writable`
3. **Criar pasta:** `session`
4. **Clicar com botão direito na pasta `session`** → Permissions
5. **Definir permissões:** `775` (Read/Write/Execute para owner e group)
6. **Repetir para outras pastas:**
   - `writable/cache` → 775
   - `writable/logs` → 775
   - `writable/uploads` → 775

---

## 🚀 SOLUÇÃO COMPLETA (Script Automático)

O script `setup-production-directories.sh` faz TUDO automaticamente:

### O que o script faz:

- ✅ Cria **todos os diretórios** necessários
- ✅ Ajusta **permissões** corretas (775/664)
- ✅ Cria **arquivos de segurança** (.htaccess, index.html)
- ✅ Remove **sessões antigas**
- ✅ Limpa **cache**
- ✅ **Testa** criação de arquivo de sessão
- ✅ **Verifica** que tudo está OK

### Como executar:

```bash
# Conectar ao servidor
ssh usuario@servidor

# Navegar para o projeto
cd ~/public_html/ponto.supportsondagens.com.br

# Executar setup
bash setup-production-directories.sh
```

**Tempo:** 1-2 minutos
**Resultado:** Sistema funcionando!

---

## 📋 DIRETÓRIOS QUE SERÃO CRIADOS

```
writable/
├── session/              ← CRÍTICO (erro atual)
├── cache/
│   └── data/
├── logs/
├── debugbar/
├── uploads/
├── exports/
└── biometric/
    ├── faces/
    └── fingerprints/
```

---

## 🧪 TESTAR SE FUNCIONOU

### Teste 1: Verificar diretório
```bash
ls -la writable/session/
```

**Deve mostrar:**
```
drwxrwxr-x 2 usuario usuario 4096 Nov 16 23:30 .
```

### Teste 2: Acessar o site
```
https://ponto.supportsondagens.com.br
```

**Resultado esperado:**
- ✅ Carrega página de login
- ❌ **NÃO** mostra erro de sessão
- ❌ **NÃO** fica em loop

### Teste 3: Ver logs
```bash
tail -f writable/logs/log-$(date +%Y-%m-%d).php
```

**Não deve ter mais:**
```
Unable to create file writable/session
```

---

## 🆘 SE NÃO FUNCIONAR

### Problema 1: Permissões negadas

```bash
# Executar com sudo (se tiver acesso)
sudo bash setup-production-directories.sh

# OU ajustar owner
sudo chown -R seu_usuario:seu_usuario writable/
```

### Problema 2: Script não encontrado

```bash
# Verificar se está no diretório correto
pwd
# Deve mostrar: /home/usuario/public_html/ponto.supportsondagens.com.br

# Se não estiver, navegar:
cd ~/public_html/ponto.supportsondagens.com.br

# Verificar se script existe
ls -l setup-production-directories.sh

# Se não existir, fazer upload do script
```

### Problema 3: Erro "bash: command not found"

```bash
# Usar sh ao invés de bash
sh setup-production-directories.sh
```

---

## 🔧 CRIAÇÃO MANUAL (Se script não funcionar)

### Via Linha de Comando:

```bash
# Navegar para projeto
cd ~/public_html/ponto.supportsondagens.com.br

# Criar diretórios
mkdir -p writable/session
mkdir -p writable/cache/data
mkdir -p writable/logs
mkdir -p writable/uploads

# Ajustar permissões
chmod -R 775 writable/
find writable -type f -exec chmod 664 {} \;

# Criar arquivo de segurança
cat > writable/.htaccess <<'EOF'
<IfModule authz_core_module>
    Require all denied
</IfModule>
EOF

# Limpar sessões antigas
rm -f writable/session/ci_session*

# Testar
touch writable/session/test.tmp && rm writable/session/test.tmp
echo "✅ Sessão pode ser criada!"
```

### Via cPanel File Manager:

1. **File Manager** → `public_html/ponto.supportsondagens.com.br`
2. **Entrar em** `writable/`
3. **+ Folder** → Nome: `session` → Create
4. **Clicar direito em `session`** → Permissions → `755` ou `775`
5. **Repetir** para: `cache`, `logs`, `uploads`

---

## ✅ CHECKLIST DE VERIFICAÇÃO

Após executar a correção, marque:

- [ ] ✅ Diretório `writable/session` existe
- [ ] ✅ Permissões `775` em `writable/session`
- [ ] ✅ Consegue criar arquivo de teste em `writable/session`
- [ ] ✅ Site carrega sem erro de sessão
- [ ] ✅ Login funciona
- [ ] ✅ Não há mais erro nos logs

---

## 🎯 CAUSA RAIZ DO PROBLEMA

**Por que o diretório não existia?**

1. `.gitignore` ignora conteúdo de `writable/session/*`
2. Git não envia pastas vazias para repositório
3. Ao fazer deploy, a pasta não é criada
4. Sistema tenta criar sessão → falha → erro!

**Solução permanente:**
O script `setup-production-directories.sh` deve ser executado em **TODA instalação nova**

---

## 📞 COMANDOS ÚTEIS

```bash
# Ver estrutura de writable
tree writable/ -L 2

# Verificar permissões
ls -la writable/

# Verificar se pode escrever
touch writable/session/test.tmp && echo "OK" || echo "ERRO"

# Limpar tudo e recriar
rm -rf writable/session/*
bash setup-production-directories.sh

# Ver logs em tempo real
tail -f writable/logs/log-$(date +%Y-%m-%d).php
```

---

## 🚨 IMPORTANTE

### NÃO esqueça de:

1. ✅ **Executar no SERVIDOR DE PRODUÇÃO** (não localmente)
2. ✅ **Verificar permissões** após criar diretórios
3. ✅ **Testar acesso ao site** após correção
4. ✅ **Limpar cache do navegador** (Ctrl+Shift+Del)

### Permissões corretas:

```
writable/          → 775 (drwxrwxr-x)
writable/session/  → 775 (drwxrwxr-x)
writable/cache/    → 775 (drwxrwxr-x)
writable/logs/     → 775 (drwxrwxr-x)
.env               → 600 (-rw-------)
```

---

## 🎉 APÓS A CORREÇÃO

**O sistema deve:**
- ✅ Criar sessões normalmente
- ✅ Login funcionar
- ✅ Não ter mais loop de redirect
- ✅ Dashboard carregar
- ✅ Funcionalidades funcionarem

---

**Tempo estimado de correção:** 2-5 minutos
**Dificuldade:** Fácil (executar 1 comando)
**Impacto:** Resolve 100% do problema

---

**Data:** 2025-11-16
**Erro:** CRITICAL - Unable to create file writable/session
**Solução:** Criar estrutura de diretórios no servidor
