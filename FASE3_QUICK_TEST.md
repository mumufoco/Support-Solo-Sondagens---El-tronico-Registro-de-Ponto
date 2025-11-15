# Guia Rápido de Testes - Fase 3

## 🚀 Setup Rápido (5 minutos)

```bash
# 1. Entrar no container
docker-compose exec app bash

# 2. Instalar Shield
composer require codeigniter4/shield

# 3. Configurar Shield
php spark shield:setup

# 4. Rodar migrations
php spark migrate --all

# 5. Criar grupos
php spark db:seed AuthGroupsSeeder

# 6. Criar usuário admin
php spark shield:user create
# Email: admin@ponto.com.br
# Username: admin
# Password: Admin@123

# 7. Adicionar ao grupo admin
php spark shield:user addgroup admin@ponto.com.br admin

# 8. Limpar cache
php spark cache:clear
```

---

## ✅ Testes Essenciais (10 minutos)

### 1️⃣ Teste de Registro

**URL:** `http://localhost:8080/auth/register`

**Preencher:**
- Nome: João Silva
- Email: joao@test.com
- CPF: `123.456.789-09` (válido) ou `529.982.247-25` (válido)
- Senha: `Joao@123`
- Confirmar: `Joao@123`

**Resultado esperado:**
✅ "Conta criada com sucesso" → Redireciona para login

**Testar CPF inválido:**
- CPF: `111.111.111-11` → ❌ Deve rejeitar

**Testar senha fraca:**
- Senha: `admin123` → ❌ Deve rejeitar

---

### 2️⃣ Teste de Login como Admin

**URL:** `http://localhost:8080/auth/login`

**Credenciais:**
- Email: `admin@ponto.com.br`
- Senha: `Admin@123`

**Resultado esperado:**
✅ Redireciona para `/dashboard/admin`
✅ Mostra 4 cards com estatísticas
✅ Exibe gráfico Chart.js (marcações últimos 7 dias)
✅ Lista de alertas

---

### 3️⃣ Teste de Filtro de Autorização

**Passo 1:** Fazer logout
- URL: `http://localhost:8080/auth/logout`

**Passo 2:** Tentar acessar área admin sem login
- URL: `http://localhost:8080/dashboard/admin`

**Resultado esperado:**
✅ Redireciona para `/auth/login`
✅ Mensagem: "Você precisa fazer login"

**Passo 3:** Login como funcionário
```bash
# Criar funcionário
php spark shield:user create
# Email: func@test.com
# Password: Func@123

php spark shield:user addgroup func@test.com funcionario
```

**Passo 4:** Tentar acessar dashboard admin
- URL: `http://localhost:8080/dashboard/admin`

**Resultado esperado:**
✅ Acesso negado
✅ Mensagem de erro

---

### 4️⃣ Teste de Brute Force

**Na tela de login:**
1. Tentar 5x com senha errada
2. Na 6ª tentativa: ✅ "Muitas tentativas. Aguarde 15 minutos."

---

## 🔍 Verificação no Banco

```bash
# Entrar no MySQL
docker-compose exec mysql mysql -u root -proot ponto_eletronico

# Verificar grupos
SELECT * FROM auth_groups;
# Deve ter: admin (1), gestor (2), funcionario (3)

# Verificar permissões
SELECT * FROM auth_permissions;
# Deve ter 8 permissões

# Verificar usuário admin
SELECT u.email, g.name FROM users u
JOIN auth_groups_users gu ON u.id = gu.user_id
JOIN auth_groups g ON g.id = gu.group_id
WHERE u.email = 'admin@ponto.com.br';
# Deve retornar: admin@ponto.com.br | admin
```

---

## ✅ Checklist Mínimo

- [ ] Shield instalado e configurado
- [ ] Migrations executadas
- [ ] Grupos criados (3 grupos)
- [ ] Usuário admin criado
- [ ] Login funciona
- [ ] Dashboard admin exibe corretamente
- [ ] Filtros bloqueiam acesso não autorizado
- [ ] CPF inválido é rejeitado
- [ ] Senha fraca é rejeitada

---

## 🎯 Se tudo passou: Fase 3 está 100% funcional!

➡️ **Próximo:** Fase 5 - Registro por Código e QR

---

**Tempo estimado:** 15 minutos
**Dificuldade:** Fácil
