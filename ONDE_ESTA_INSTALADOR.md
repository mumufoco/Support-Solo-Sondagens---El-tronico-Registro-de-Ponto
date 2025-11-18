# 📍 Onde Está o Instalador? - Guia Rápido

## ✅ Agora Você Tem 2 Versões

Criei o instalador em **DOIS locais** para funcionar em qualquer configuração de servidor:

### 📁 Versão 1: Raiz do Projeto
```
install.php
```
**Caminhos internos:** `__DIR__ . '/writable/...'`

### 📁 Versão 2: Pasta Public
```
public/install.php
```
**Caminhos internos:** `__DIR__ . '/../writable/...'` (ajustado)

---

## 🔍 Qual Usar?

### Como Descobrir Sua Configuração

**Opção 1: Testar Ambos** (mais fácil)

Tente acessar:
```
http://seu-dominio.com/install.php
```

✅ **Se funcionar** → Seu server aponta para RAIZ
❌ **Se der 404** → Seu server aponta para PUBLIC

Então tente:
```
http://seu-dominio.com/public/install.php
```

✅ **Se funcionar** → Use este

---

**Opção 2: Verificar Configuração do Server**

```bash
# Apache
grep DocumentRoot /etc/apache2/sites-enabled/*.conf

# Nginx
grep root /etc/nginx/sites-enabled/default
```

**Resultado:**
```
# Se aparecer:
DocumentRoot /var/www/ponto-eletronico
→ Use: http://seu-dominio.com/install.php

# Se aparecer:
DocumentRoot /var/www/ponto-eletronico/public
→ Use: http://seu-dominio.com/install.php (já estará em public/)
```

---

## 📊 Cenários Comuns

### Cenário 1: Servidor Compartilhado / cPanel
```
DocumentRoot → public_html/ ou htdocs/
```
✅ **Provavelmente usa PUBLIC**

**Estrutura:**
```
public_html/
├── .htaccess
├── index.php          ← CodeIgniter bootstrap
├── install.php        ← USE ESTE
└── ...
```

**Acesso:**
```
http://seu-dominio.com/install.php
```

---

### Cenário 2: VPS / Servidor Dedicado (Configuração Padrão)
```
DocumentRoot → /var/www/projeto/public
```
✅ **USA PUBLIC**

**Estrutura:**
```
/var/www/projeto/
├── app/
├── writable/
├── public/              ← DocumentRoot aponta aqui
│   ├── index.php
│   └── install.php      ← USE ESTE
└── install.php          ← Não acessível
```

**Acesso:**
```
http://seu-dominio.com/install.php
```

---

### Cenário 3: VPS com DocumentRoot na Raiz (Não recomendado)
```
DocumentRoot → /var/www/projeto
```
✅ **USA RAIZ**

**Estrutura:**
```
/var/www/projeto/
├── app/
├── writable/
├── public/
├── index.php
└── install.php          ← USE ESTE
```

**Acesso:**
```
http://seu-dominio.com/install.php
```

---

### Cenário 4: Localhost / Desenvolvimento
```
DocumentRoot → C:\xampp\htdocs\projeto
```
✅ **GERALMENTE USA RAIZ**

**Acesso:**
```
http://localhost:8080/install.php
ou
http://localhost/projeto/install.php
```

---

## 🚀 Teste Rápido

**Cole este comando no terminal do servidor:**

```bash
# Descubra qual usar
echo "Testando configuração..."

if [ -f "public/install.php" ]; then
    echo "✅ public/install.php existe"
    echo "Acesse: http://seu-dominio.com/install.php"
fi

if [ -f "install.php" ]; then
    echo "✅ install.php (raiz) existe"
fi

# Ver DocumentRoot
if command -v apache2 &> /dev/null; then
    echo ""
    echo "DocumentRoot do Apache:"
    grep DocumentRoot /etc/apache2/sites-enabled/*.conf 2>/dev/null || echo "Não encontrado"
fi

if command -v nginx &> /dev/null; then
    echo ""
    echo "Root do Nginx:"
    grep "root " /etc/nginx/sites-enabled/default 2>/dev/null || echo "Não encontrado"
fi
```

---

## 🎯 Recomendação para Seu Caso

Baseado no erro que você teve, seu servidor **provavelmente usa PUBLIC**.

### Tente Primeiro:
```
http://seu-dominio.com/install.php
```

**Se der erro 404, provavelmente o document root está na raiz.**

Nesse caso, o arquivo que está em **`install.php`** (raiz) já vai funcionar, mas você estará acessando direto sem passar pelo `public/`.

---

## ❓ FAQ

### P: E se os dois existem?

**R:** Use o de `public/` (é a configuração correta do CodeIgniter 4).

### P: Por que criar em dois lugares?

**R:** Para funcionar em QUALQUER servidor, sem você precisar ajustar caminhos.

### P: Qual a diferença entre eles?

**R:** Apenas os caminhos internos:
- **Raiz:** `__DIR__ . '/writable/...'`
- **Public:** `__DIR__ . '/../writable/...'`

### P: Posso deletar um depois?

**R:** Sim! Após instalar, delete ambos:
```bash
rm install.php
rm public/install.php
```

### P: Como sei qual está sendo usado?

**R:** Veja o console do instalador:
```
📝 Criando arquivo .env...
   Caminho: /var/www/projeto/.env  ← Se aparecer caminho absoluto
```

---

## 🎯 Teste no Seu Servidor Agora

### 1. Faça Pull
```bash
git pull origin claude/fix-installer-error-01H6vTMYKdEEfonfAf42jUUY
```

### 2. Verifique os Dois Arquivos
```bash
ls -lh install.php
ls -lh public/install.php
```

Ambos devem existir (38 KB cada).

### 3. Tente Acessar
```
http://seu-dominio.com/install.php
```

✅ **Se abrir a tela do instalador** → Está funcionando!

❌ **Se der 404** → Seu server tem configuração diferente. Me avise!

---

## ✅ Resumo

```
Você tem DUAS versões do instalador:

1. install.php (raiz)
   → Funciona se DocumentRoot = raiz do projeto

2. public/install.php
   → Funciona se DocumentRoot = public/
   → Caminhos ajustados automaticamente

TESTE: http://seu-dominio.com/install.php

Se funcionar → Perfeito! Prossiga com instalação
Se não → Me informe e ajusto
```

**Agora pode testar no seu servidor!** 🚀
