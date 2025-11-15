# Guia de Setup - Fase 3: Autenticação e Perfis

## 🎯 Objetivo

Este guia contém os comandos necessários para **completar 100% da Fase 3**, configurando o CodeIgniter Shield e testando todo o sistema de autenticação.

---

## ✅ O que já está implementado (Código)

- ✅ `app/Views/auth/register.php` - View de cadastro (NOVO)
- ✅ `app/Views/auth/login.php` - View de login
- ✅ `app/Controllers/Admin/DashboardController.php` - Dashboard admin
- ✅ `app/Controllers/Gestor/DashboardController.php` - Dashboard gestor
- ✅ `app/Controllers/Dashboard/DashboardController.php` - Dashboard funcionário
- ✅ `app/Controllers/Auth/LoginController.php` - Controller de login
- ✅ `app/Controllers/Auth/RegisterController.php` - Controller de registro
- ✅ `app/Controllers/Auth/LogoutController.php` - Controller de logout
- ✅ `app/Filters/AuthFilter.php` - Filtro de autenticação
- ✅ `app/Filters/AdminFilter.php` - Filtro de admin
- ✅ `app/Filters/ManagerFilter.php` - Filtro de gestor
- ✅ `app/Database/Seeds/AuthGroupsSeeder.php` - Seeder de grupos
- ✅ `app/Config/Routes.php` - Rotas ajustadas

---

## 📋 Passo a Passo de Setup

### Passo 1: Acessar o Container PHP

```bash
# Se estiver usando Docker (recomendado)
docker-compose exec app bash

# OU, se o serviço tiver outro nome
docker ps  # Verificar nome do container
docker exec -it <nome_do_container> bash
```

### Passo 2: Instalar Dependências do Composer

```bash
# Dentro do container
composer install

# Ou se já instalado, atualizar
composer update codeigniter4/shield
```

### Passo 3: Configurar CodeIgniter Shield

```bash
# Publicar arquivos de configuração do Shield
php spark shield:setup

# Output esperado:
# Publishing Shield config files...
# ✓ Config\Auth.php created
# ✓ Config\AuthGroups.php created
# ✓ Config\AuthToken.php created
```

Isso criará os arquivos:
- `app/Config/Auth.php` - Configuração principal
- `app/Config/AuthGroups.php` - Configuração de grupos
- `app/Config/AuthToken.php` - Configuração de tokens

### Passo 4: Executar Migrations do Shield

```bash
# Executar todas migrations (incluindo Shield)
php spark migrate --all

# Output esperado:
# Running migrations...
# ✓ 2020-12-28-223112: CreateAuthTables
#   Created table: auth_identities
#   Created table: auth_logins
#   Created table: auth_token_logins
#   Created table: auth_remember_tokens
#   Created table: auth_groups_users
#   Created table: auth_permissions_users
# ✓ 2021-07-04-041948: CreateAuthGroupsTables
#   Created table: auth_groups
#   Created table: auth_permissions
#   Created table: auth_groups_permissions
# ✓ ... (outras migrations do projeto)
```

### Passo 5: Criar Grupos e Permissões

```bash
# Executar o seeder de grupos
php spark db:seed AuthGroupsSeeder

# Output esperado:
# Seeding: App\Database\Seeds\AuthGroupsSeeder
# ✓ Created group: admin (ID: 1)
# ✓ Created group: gestor (ID: 2)
# ✓ Created group: funcionario (ID: 3)
# ✓ Created 8 permissions
# ✓ Assigned permissions to groups
# Seeded: App\Database\Seeds\AuthGroupsSeeder
```

**Grupos criados:**

| ID | Nome | Descrição | Permissões |
|----|------|-----------|------------|
| 1 | admin | Administrador | `admin.*` (todas) |
| 2 | gestor | Gestor | `manage.employees`, `approve.justifications`, `view.reports`, `manage.team`, `clock.inout` |
| 3 | funcionario | Funcionário | `clock.inout`, `view.own.data`, `submit.justification` |

### Passo 6: Verificar Grupos Criados

```bash
# Listar grupos no banco
php spark db:table auth_groups

# Ou via MySQL diretamente
docker-compose exec mysql mysql -u root -p<senha> ponto_eletronico -e "SELECT * FROM auth_groups;"
```

### Passo 7: Criar Usuário Admin de Teste

**Opção A: Via Spark (Recomendado)**

```bash
# Criar usuário admin
php spark shield:user create

# Será solicitado:
# Email: admin@ponto.com.br
# Username: admin
# Password: Admin@123

# Adicionar ao grupo admin
php spark shield:user addgroup admin@ponto.com.br admin
```

**Opção B: Via Seeder (se já existir AdminUserSeeder)**

```bash
php spark db:seed AdminUserSeeder
```

**Opção C: Via SQL Direto**

```sql
-- Inserir usuário admin manualmente
INSERT INTO users (email, username, password_hash, active, created_at)
VALUES ('admin@ponto.com.br', 'admin', '$argon2id$v=19$m=65536,t=4,p=1$...(hash do Admin@123)', 1, NOW());

-- Associar ao grupo admin (ID 1)
INSERT INTO auth_groups_users (user_id, group_id, created_at)
VALUES (LAST_INSERT_ID(), 1, NOW());
```

### Passo 8: Criar Usuários de Teste para Outros Perfis

**Gestor:**

```bash
php spark shield:user create
# Email: gestor@ponto.com.br
# Username: gestor
# Password: Gestor@123

php spark shield:user addgroup gestor@ponto.com.br gestor
```

**Funcionário:**

```bash
php spark shield:user create
# Email: funcionario@ponto.com.br
# Username: funcionario
# Password: Func@123

php spark shield:user addgroup funcionario@ponto.com.br funcionario
```

### Passo 9: Configurar Filtros no CodeIgniter

Verifique se o arquivo `app/Config/Filters.php` tem os filtros registrados:

```php
<?php
// app/Config/Filters.php

public $aliases = [
    // ... outros filtros
    'auth'    => \App\Filters\AuthFilter::class,
    'admin'   => \App\Filters\AdminFilter::class,
    'manager' => \App\Filters\ManagerFilter::class,
];
```

Se não estiver, adicione manualmente.

### Passo 10: Limpar Cache

```bash
# Limpar cache do CodeIgniter
php spark cache:clear

# Reiniciar servidor (se necessário)
exit  # Sair do container
docker-compose restart app
```

---

## 🧪 Testes Funcionais

### Teste 1: Cadastro de Novo Usuário

1. **Acessar página de cadastro:**
   ```
   http://localhost:8080/auth/register
   ```

2. **Preencher formulário:**
   - Nome: João da Silva
   - E-mail: joao@test.com
   - CPF: 123.456.789-09 (use um CPF válido com checksum correto)
   - Senha: Joao@123
   - Confirmar Senha: Joao@123

3. **Verificar:**
   - ✅ CPF deve aceitar apenas 11 dígitos numéricos
   - ✅ CPFs inválidos devem ser rejeitados (ex: 111.111.111-11)
   - ✅ Senha fraca deve ser rejeitada
   - ✅ Sucesso: mensagem "Conta criada com sucesso"
   - ✅ Redirecionamento para /auth/login

**CPFs válidos para teste:**
- `123.456.789-09` ✅
- `529.982.247-25` ✅
- `111.111.111-11` ❌ (sequência)
- `123.456.789-00` ❌ (checksum errado)

### Teste 2: Login como Admin

1. **Acessar:**
   ```
   http://localhost:8080/auth/login
   ```

2. **Fazer login:**
   - Email: `admin@ponto.com.br`
   - Senha: `Admin@123`

3. **Verificar:**
   - ✅ Redirecionamento automático para `/dashboard/admin`
   - ✅ Dashboard admin exibe:
     - 4 cards com estatísticas
     - Gráfico Chart.js (marcações últimos 7 dias)
     - Lista de alertas
     - Atalhos rápidos

### Teste 3: Login como Gestor

1. **Fazer login:**
   - Email: `gestor@ponto.com.br`
   - Senha: `Gestor@123`

2. **Verificar:**
   - ✅ Redirecionamento para `/dashboard/manager`
   - ✅ Dashboard gestor exibe:
     - Cards com resumo da equipe
     - Tabela de justificativas pendentes
     - Botões Aprovar/Rejeitar

### Teste 4: Login como Funcionário

1. **Fazer login:**
   - Email: `funcionario@ponto.com.br`
   - Senha: `Func@123`

2. **Verificar:**
   - ✅ Redirecionamento para `/dashboard` (employee)
   - ✅ Dashboard exibe botão "Bater Ponto"
   - ✅ Resumo do mês

### Teste 5: Filtros de Autorização

**Teste 5.1: Acesso sem autenticação**

1. **Fazer logout:**
   ```
   http://localhost:8080/auth/logout
   ```

2. **Tentar acessar área protegida:**
   ```
   http://localhost:8080/dashboard/admin
   ```

3. **Verificar:**
   - ✅ Deve redirecionar para `/auth/login`
   - ✅ Mensagem: "Você precisa fazer login para acessar esta página"

**Teste 5.2: Acesso com perfil incorreto**

1. **Login como funcionário:**
   - Email: `funcionario@ponto.com.br`
   - Senha: `Func@123`

2. **Tentar acessar dashboard admin:**
   ```
   http://localhost:8080/dashboard/admin
   ```

3. **Verificar:**
   - ✅ Deve redirecionar com erro
   - ✅ Mensagem: "Acesso negado. Você não tem permissão."

### Teste 6: Proteção Brute Force

1. **Na tela de login, tentar 5x com senha errada:**
   - Email: `admin@ponto.com.br`
   - Senha: `senhaerrada`

2. **Na 6ª tentativa:**
   - ✅ Deve mostrar: "Muitas tentativas de login. Aguarde 15 minutos."
   - ✅ IP bloqueado por 15 minutos

3. **Aguardar 15 minutos e tentar novamente:**
   - ✅ Deve permitir nova tentativa

### Teste 7: Validação de Senha Forte

1. **Acessar cadastro:**
   ```
   http://localhost:8080/auth/register
   ```

2. **Testar senhas fracas:**

| Senha | Resultado | Motivo |
|-------|-----------|--------|
| `admin123` | ❌ Rejeitada | Falta maiúscula e especial |
| `Admin123` | ❌ Rejeitada | Falta caractere especial |
| `Admin@123` | ✅ Aceita | Atende todos requisitos |
| `abc` | ❌ Rejeitada | Menos de 8 caracteres |
| `ADMIN@123` | ❌ Rejeitada | Falta minúscula |

### Teste 8: Dashboard Admin - Chart.js

1. **Login como admin**

2. **Verificar gráfico:**
   - ✅ Gráfico de linha carregando
   - ✅ Dados dos últimos 7 dias
   - ✅ Eixo X: datas
   - ✅ Eixo Y: quantidade de marcações

3. **Verificar no console do navegador (F12):**
   - ✅ Sem erros JavaScript
   - ✅ Chart.js carregado corretamente

### Teste 9: Dashboard Gestor - Aprovação de Justificativas

1. **Login como gestor**

2. **Visualizar justificativas pendentes**

3. **Clicar em "Aprovar":**
   - ✅ Mensagem de sucesso
   - ✅ Status alterado para "aprovada"
   - ✅ Log de auditoria criado

4. **Verificar no banco:**
   ```sql
   SELECT * FROM justifications WHERE id = X;
   -- status deve ser 'approved'
   -- approved_by deve ser o ID do gestor
   -- approved_at deve ter timestamp
   ```

---

## 🔍 Verificação de Banco de Dados

### Verificar Grupos

```sql
-- Listar grupos
SELECT * FROM auth_groups;

-- Output esperado:
-- +----+-------------+------------------------------------------+
-- | id | name        | description                              |
-- +----+-------------+------------------------------------------+
-- |  1 | admin       | Administrador - Acesso Total ao Sistema  |
-- |  2 | gestor      | Gestor - Gerencia Equipe e Aprovações    |
-- |  3 | funcionario | Funcionário - Registro de Ponto          |
-- +----+-------------+------------------------------------------+
```

### Verificar Permissões

```sql
-- Listar permissões
SELECT * FROM auth_permissions;

-- Output esperado:
-- +----+------------------------+---------------------+
-- | id | name                   | description         |
-- +----+------------------------+---------------------+
-- |  1 | admin.*                | Todas permissões    |
-- |  2 | manage.employees       | Gerenciar funcionários |
-- |  3 | approve.justifications | Aprovar justificativas |
-- |  4 | view.reports           | Ver relatórios      |
-- |  5 | manage.team            | Gerenciar equipe    |
-- |  6 | clock.inout            | Registrar ponto     |
-- |  7 | view.own.data          | Ver próprios dados  |
-- |  8 | submit.justification   | Enviar justificativa |
-- +----+------------------------+---------------------+
```

### Verificar Associação Grupos-Permissões

```sql
-- Ver quais permissões cada grupo tem
SELECT
    g.name as grupo,
    p.name as permissao
FROM auth_groups g
JOIN auth_groups_permissions gp ON g.id = gp.group_id
JOIN auth_permissions p ON p.id = gp.permission_id
ORDER BY g.id, p.id;

-- Output esperado:
-- +-------------+------------------------+
-- | grupo       | permissao              |
-- +-------------+------------------------+
-- | admin       | admin.*                |
-- | gestor      | manage.employees       |
-- | gestor      | approve.justifications |
-- | gestor      | view.reports           |
-- | gestor      | manage.team            |
-- | gestor      | clock.inout            |
-- | funcionario | clock.inout            |
-- | funcionario | view.own.data          |
-- | funcionario | submit.justification   |
-- +-------------+------------------------+
```

### Verificar Usuários e Grupos

```sql
-- Ver quais usuários pertencem a quais grupos
SELECT
    u.email,
    g.name as grupo
FROM users u
JOIN auth_groups_users gu ON u.id = gu.user_id
JOIN auth_groups g ON g.id = gu.group_id;

-- Output esperado:
-- +---------------------------+-------------+
-- | email                     | grupo       |
-- +---------------------------+-------------+
-- | admin@ponto.com.br        | admin       |
-- | gestor@ponto.com.br       | gestor      |
-- | funcionario@ponto.com.br  | funcionario |
-- +---------------------------+-------------+
```

---

## 🐛 Troubleshooting

### Problema 1: "Shield não encontrado"

**Solução:**
```bash
composer require codeigniter4/shield:^1.0
composer install
php spark shield:setup
```

### Problema 2: "Tabelas auth_* não existem"

**Solução:**
```bash
php spark migrate:refresh --all
php spark db:seed AuthGroupsSeeder
```

### Problema 3: "Rota não encontrada"

**Solução:**
```bash
# Limpar cache de rotas
php spark cache:clear

# Verificar routes
php spark routes | grep dashboard
```

### Problema 4: "Login não redireciona corretamente"

**Verificar:**
1. Usuário está no grupo correto?
   ```sql
   SELECT u.email, g.name FROM users u
   JOIN auth_groups_users gu ON u.id = gu.user_id
   JOIN auth_groups g ON g.id = gu.group_id
   WHERE u.email = 'admin@ponto.com.br';
   ```

2. LoginController tem lógica de redirecionamento?
   ```php
   // Deve ter algo como:
   if ($user->inGroup('admin')) {
       return redirect()->to('/dashboard/admin');
   }
   ```

### Problema 5: "Chart.js não carrega"

**Solução:**
1. Verificar console do navegador (F12)
2. Verificar conexão CDN:
   ```html
   <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
   ```
3. Verificar formato dos dados:
   ```javascript
   const punchesData = <?= json_encode($punches_last_7_days) ?>;
   ```

---

## ✅ Checklist Final - Fase 3 100% Completa

### Código Implementado
- [x] ✅ View de login (`app/Views/auth/login.php`)
- [x] ✅ View de registro (`app/Views/auth/register.php`)
- [x] ✅ Controllers de autenticação (Login, Register, Logout)
- [x] ✅ Dashboard Admin com Chart.js
- [x] ✅ Dashboard Gestor com aprovações
- [x] ✅ Dashboard Funcionário
- [x] ✅ Filtros de autorização (Auth, Admin, Manager)
- [x] ✅ Validações customizadas (CPF, senha forte)
- [x] ✅ Seeder de grupos e permissões
- [x] ✅ Rotas configuradas corretamente

### Setup Executado
- [ ] Shield instalado (`composer require codeigniter4/shield`)
- [ ] Shield configurado (`php spark shield:setup`)
- [ ] Migrations executadas (`php spark migrate --all`)
- [ ] Grupos criados (`php spark db:seed AuthGroupsSeeder`)
- [ ] Usuários de teste criados (admin, gestor, funcionario)
- [ ] Filtros registrados em `app/Config/Filters.php`

### Testes Aprovados
- [ ] Cadastro de usuário funciona
- [ ] Validação de CPF funciona (aceita válidos, rejeita inválidos)
- [ ] Validação de senha forte funciona
- [ ] Login como admin redireciona para `/dashboard/admin`
- [ ] Login como gestor redireciona para `/dashboard/manager`
- [ ] Login como funcionário redireciona para `/dashboard`
- [ ] Filtros bloqueiam acesso não autorizado
- [ ] Proteção brute force funciona (5 tentativas)
- [ ] Logout destrói sessão corretamente
- [ ] Chart.js carrega no dashboard admin
- [ ] Aprovação de justificativas funciona no dashboard gestor

---

## 🎯 Próximos Passos

Após completar esta checklist:

1. ✅ Fase 3 estará 100% completa
2. ➡️ Prosseguir para **Fase 5: Registro por Código e QR** (Semana 9)
   - Interface web para registro
   - Geração de QR Codes
   - Validação online de comprovantes

---

## 📚 Referências

- [CodeIgniter Shield Docs](https://shield.codeigniter.com/)
- [CodeIgniter 4 Docs](https://codeigniter.com/user_guide/)
- [Chart.js Docs](https://www.chartjs.org/)
- [Bootstrap 5 Docs](https://getbootstrap.com/docs/5.3/)

---

**Data de Criação:** 15/11/2025
**Autor:** Sistema de Ponto Eletrônico - Fase 3
**Versão:** 1.0
