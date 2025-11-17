# 🗄️ QUAL SCRIPT USAR PARA RESOLVER O MYSQL?

## ⚡ DECISÃO RÁPIDA (Escolha sua situação)

| Sua Situação | Execute Este Script | Tempo |
|--------------|---------------------|-------|
| 🏠 **Tenho acesso root** (meu computador/VPS) | `./instalar-mysql.sh` | 5-10 min |
| 🌐 **Hospedagem compartilhada** (cPanel/Plesk) | `./configurar-banco-producao.sh` | 2-3 min |
| ✅ **MySQL já instalado** mas sem banco | `./create-database.sh` | 1 min |
| ❓ **Não sei minha situação** | `./FIX_ERRO_500.sh` | 3 min |
| 🧪 **Só quero testar** | `php public/test-db-connection.php` | 10 seg |

---

## 🎯 GUIA DETALHADO

### Situação 1️⃣: Você Está no SEU COMPUTADOR ou VPS com ROOT

**Exemplo:** Ubuntu, Debian, Linux Mint, CentOS, macOS

✅ **Execute:**
```bash
./instalar-mysql.sh
```

**O script vai:**
1. Detectar seu sistema operacional
2. Tentar instalar via Docker (mais fácil)
3. Se não tiver Docker, instalar MySQL nativo
4. Criar banco de dados
5. Testar conexão
6. Mostrar próximos passos

---

### Situação 2️⃣: HOSPEDAGEM COMPARTILHADA (cPanel/Plesk)

**Exemplo:** HostGator, Locaweb, UOL Host, GoDaddy

**Primeiro:** Criar banco no painel de controle da hospedagem
1. Acesse cPanel/Plesk
2. Vá em "MySQL Databases"
3. Crie banco: `ponto_eletronico`
4. Crie usuário MySQL
5. Associe usuário ao banco (ALL PRIVILEGES)

**Depois, execute:**
```bash
./configurar-banco-producao.sh
```

**O script vai:**
1. Pedir as credenciais que você anotou
2. Atualizar .env automaticamente
3. Testar conexão
4. Executar migrations (criar tabelas)
5. Criar usuário admin

---

### Situação 3️⃣: MySQL JÁ ESTÁ INSTALADO

**Como saber:** Execute `mysql --version` e não dá erro

✅ **Execute:**
```bash
./create-database.sh
```

**O script vai:**
1. Criar banco de dados
2. Opcionalmente criar usuário específico
3. Testar conexão

---

### Situação 4️⃣: NÃO SEI / ERRO GENÉRICO

✅ **Execute:**
```bash
./FIX_ERRO_500.sh
```

**O script vai:**
1. Detectar se MySQL está instalado
2. Detectar se está rodando
3. Tentar corrigir automaticamente
4. Mostrar o que você precisa fazer

---

## 📚 DOCUMENTAÇÃO COMPLETA

| Arquivo | O Que É | Quando Ler |
|---------|---------|------------|
| `INICIO_RAPIDO.md` | Início rápido | Ler primeiro |
| `INSTALAR_MYSQL.md` | Guia completo de instalação | Se quer entender tudo |
| `CONFIGURAR_BANCO_PRODUCAO.md` | Guia para produção | Se usa hospedagem compartilhada |
| `DIAGNOSTICO_ERRO_500.md` | Análise técnica completa | Para entender o erro |

---

## 🆘 COMANDOS ÚTEIS

### Testar Conexão
```bash
php public/test-db-connection.php
```

### Testar Sistema Completo
```bash
php public/test-error-500.php
```

### Ver Logs
```bash
tail -f writable/logs/log-*.php
```

### Executar Migrations
```bash
php spark migrate
```

### Criar Usuário Admin
```bash
php spark shield:user create
```

### Iniciar Servidor
```bash
php spark serve
```

---

## 🎯 DEPOIS QUE MYSQL FUNCIONAR

```bash
# 1. Criar estrutura do banco
php spark migrate

# 2. Criar usuário admin
php spark shield:user create
# Email: admin@empresa.com
# Senha: (escolha forte)

# 3. Iniciar sistema
php spark serve

# 4. Acessar
http://localhost:8080
```

---

## ✅ CHECKLIST DE SUCESSO

Você saberá que deu certo quando:

- [ ] `php public/test-db-connection.php` mostra "✅ CONEXÃO ESTABELECIDA"
- [ ] `php spark migrate` executa sem erros
- [ ] Sistema não mostra mais erro 500
- [ ] Consegue acessar página de login
- [ ] Consegue fazer login com usuário criado

---

## 🔧 SCRIPTS DISPONÍVEIS (Resumo)

```bash
./instalar-mysql.sh              # Instalar MySQL do zero
./configurar-banco-producao.sh   # Configurar MySQL existente (hospedagem)
./create-database.sh             # Só criar banco (MySQL já instalado)
./FIX_ERRO_500.sh                # Diagnóstico + correção automática
./setup-permissions.sh           # Corrigir permissões de diretórios
```

---

## 📊 FLUXOGRAMA DE DECISÃO

```
Você tem acesso ROOT (sudo)?
│
├─ SIM → Tem Docker instalado?
│        │
│        ├─ SIM → Execute: docker-compose up -d mysql
│        │
│        └─ NÃO → Execute: ./instalar-mysql.sh
│
└─ NÃO → Está em hospedagem compartilhada?
         │
         ├─ SIM → Execute: ./configurar-banco-producao.sh
         │         (depois de criar banco no cPanel)
         │
         └─ NÃO → Execute: ./FIX_ERRO_500.sh
                   (para diagnóstico)
```

---

## ❓ PERGUNTAS FREQUENTES

### "Qual é o mais rápido?"
`./instalar-mysql.sh` com Docker (5 min)

### "Não tenho acesso root, e agora?"
Use `./configurar-banco-producao.sh` e configure com MySQL da hospedagem

### "Qual é o mais recomendado?"
Docker via `./instalar-mysql.sh` (opção 1)

### "Dá para usar SQLite ao invés de MySQL?"
Não, o sistema foi projetado especificamente para MySQL

### "Não sei nada de terminal, qual usar?"
`./FIX_ERRO_500.sh` - ele explica tudo passo a passo

---

**Última atualização:** 2025-11-16
**Sistema:** Ponto Eletrônico Brasileiro v1.0
