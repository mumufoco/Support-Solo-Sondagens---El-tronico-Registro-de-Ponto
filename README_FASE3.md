# Fase 3: Autenticação e Perfis

## Sistema de Ponto Eletrônico

Documentação da Fase 3 conforme `plano_Inicial_R2` (Semana 5-6).

---

## 📋 Visão Geral

A Fase 3 implementa o sistema de autenticação completo com CodeIgniter Shield e dashboards personalizados por perfil de usuário.

**Status**: ⚠️ **GUIA DE IMPLEMENTAÇÃO** - Requer execução de comandos

**Pré-requisitos**:
- Fase 0 ✅ Concluída
- Fase 1 ✅ Concluída
- Fase 2 ✅ Concluída
- Banco de dados MySQL configurado
- CodeIgniter Shield instalado via composer

---

## ✅ Comandos da Fase 3

### Comando 3.1: Sistema de Autenticação ⚠️

**Objetivo**: Implementar autenticação com Shield, 3 perfis e filtros de segurança.

**Componentes**:
- LoginController com proteção brute force (5 tentativas = 15min bloqueio)
- RegisterController com validação de CPF (regex + checksum)
- Senha forte: mínimo 8 chars (maiúscula+minúscula+número+especial)
- Hash: Argon2ID
- Sessions com regeneração de ID após login
- 3 perfis (groups):
  - `admin` (id=1) - Todas permissões
  - `gestor` (id=2) - Gerenciar funcionários, aprovar justificativas
  - `funcionario` (id=3) - Registrar ponto, ver próprios dados
- Filtros: AuthFilter, AdminFilter, ManagerFilter

**Status**: 📄 Guia de implementação em `FASE3_IMPLEMENTATION_GUIDE.md`

### Comando 3.2: Dashboards por Perfil ⚠️

**Objetivo**: Criar interfaces personalizadas para cada perfil.

**Dashboards**:

1. **AdminDashboard**:
   - Cards com totais (funcionários ativos, marcações hoje, pendências)
   - Gráfico de linha (Chart.js) - marcações últimos 7 dias
   - Lista de alertas (saldos negativos, certificados expirando, consentimentos LGPD)
   - Atalhos rápidos (configurações, relatórios)

2. **ManagerDashboard**:
   - Card com resumo da equipe
   - Tabela de justificativas pendentes (aprovar/rejeitar)
   - Calendário mensal (FullCalendar.js) com presenças/faltas
   - Botão "Bater Ponto"

3. **EmployeeDashboard**:
   - Botão grande "BATER PONTO" (verde se pode, cinza se fora do horário)
   - Card com resumo do mês (horas trabalhadas/esperadas/saldo)
   - Lista das últimas 10 marcações
   - Link para justificar falta
   - Design mobile-first responsivo

**Status**: 📄 Guia de implementação em `FASE3_IMPLEMENTATION_GUIDE.md`

---

## 🚀 Como Implementar

### Passo 1: Configurar Shield

```bash
# 1. Publicar configurações do Shield
php spark shield:setup

# 2. Executar migrations do Shield
php spark migrate --all

# Output esperado:
# - auth_identities
# - auth_logins
# - auth_token_logins
# - auth_remember_tokens
# - auth_groups_users
# - auth_permissions_users
# - auth_groups
# - auth_permissions
```

### Passo 2: Criar Grupos e Permissões

```bash
# Executar seeder de grupos
php spark db:seed AuthGroupsSeeder

# Verificar criação
php spark db:table auth_groups
```

**Grupos criados**:
| ID | Nome | Descrição |
|----|------|-----------|
| 1 | admin | Administrador - Acesso Total |
| 2 | gestor | Gestor - Gerencia Equipe |
| 3 | funcionario | Funcionário - Registro de Ponto |

### Passo 3: Seguir o Guia de Implementação

Consulte `FASE3_IMPLEMENTATION_GUIDE.md` para:
- ✅ Código completo dos controllers
- ✅ Filtros de autenticação
- ✅ Validações customizadas (CPF, senha forte)
- ✅ Controllers dos 3 dashboards
- ✅ Views com Bootstrap 5
- ✅ Exemplos de integração

---

## 📂 Estrutura de Arquivos

```
/
├── app/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   ├── LoginController.php          # ⚠️ CRIAR
│   │   │   └── RegisterController.php       # ⚠️ CRIAR
│   │   ├── Admin/
│   │   │   └── DashboardController.php      # ⚠️ CRIAR
│   │   ├── Gestor/
│   │   │   └── DashboardController.php      # ⚠️ CRIAR
│   │   └── DashboardController.php          # ⚠️ CRIAR (funcionário)
│   │
│   ├── Filters/
│   │   ├── AuthFilter.php                   # ⚠️ CRIAR/ATUALIZAR
│   │   ├── AdminFilter.php                  # ⚠️ CRIAR
│   │   └── ManagerFilter.php                # ⚠️ CRIAR
│   │
│   ├── Validation/
│   │   └── CustomRules.php                  # ⚠️ CRIAR (CPF, senha forte)
│   │
│   ├── Database/Seeds/
│   │   └── AuthGroupsSeeder.php             # ⚠️ CRIAR
│   │
│   ├── Config/
│   │   ├── Filters.php                      # ⚠️ ATUALIZAR (adicionar filtros)
│   │   └── Validation.php                   # ⚠️ ATUALIZAR (add CustomRules)
│   │
│   └── Views/
│       ├── auth/
│       │   ├── login.php                    # ⚠️ CRIAR
│       │   └── register.php                 # ⚠️ CRIAR
│       ├── admin/
│       │   └── dashboard.php                # ⚠️ CRIAR
│       ├── gestor/
│       │   └── dashboard.php                # ⚠️ CRIAR
│       └── dashboard/
│           └── employee.php                 # ⚠️ CRIAR
│
└── FASE3_IMPLEMENTATION_GUIDE.md            # ✅ CRIADO
```

---

## 🔐 Recursos de Segurança

### Proteção Brute Force

LoginController implementa throttling:
- Máximo 5 tentativas por IP
- Bloqueio de 15 minutos após exceder
- Usa `service('throttle')`

### Validação de CPF

Validação completa com:
- ✅ Formatação (11 dígitos)
- ✅ Sequências repetidas (111.111.111-11 = inválido)
- ✅ Cálculo dos dígitos verificadores

### Senha Forte

Requisitos obrigatórios:
- ✅ Mínimo 8 caracteres
- ✅ Letra maiúscula
- ✅ Letra minúscula
- ✅ Número
- ✅ Caractere especial

Exemplo válido: `SenhaForte@123`

### Session Security

- Regeneração de session ID após login
- CSRF protection em todos formulários
- Logout destrói sessão completamente

---

## 🎨 Interface dos Dashboards

### Admin Dashboard

**Layout**:
```
┌─────────────────────────────────────────┐
│  [Card: 150 Funcionários] [Card: 89 Marcações Hoje]  │
│  [Card: 5 Pendências] [Card: 3 Consentimentos]      │
├─────────────────────────────────────────┤
│  📊 Gráfico: Marcações Últimos 7 Dias   │
│  (Chart.js - Linha)                     │
├─────────────────────────────────────────┤
│  ⚠️  Alertas:                            │
│  • 3 saldos negativos                   │
│  • 2 certificados expirando             │
├─────────────────────────────────────────┤
│  [Configurações] [Relatórios] [...]     │
└─────────────────────────────────────────┘
```

**Bibliotecas**:
- Chart.js v4.0+ (gráfico de linha)
- Bootstrap 5.3 (layout responsivo)

### Gestor Dashboard

**Layout**:
```
┌─────────────────────────────────────────┐
│  [Card: Equipe - 25 funcionários]       │
├─────────────────────────────────────────┤
│  📋 Justificativas Pendentes:           │
│  ┌─────────────────────────────────┐   │
│  │ João Silva | 10/01 | Falta      │   │
│  │ [Aprovar] [Rejeitar]            │   │
│  ├─────────────────────────────────┤   │
│  │ Maria Santos | 11/01 | Atraso   │   │
│  │ [Aprovar] [Rejeitar]            │   │
│  └─────────────────────────────────┘   │
├─────────────────────────────────────────┤
│  📅 Calendário Mensal (FullCalendar)    │
│  (Verde: Presença | Vermelho: Falta)    │
├─────────────────────────────────────────┤
│  [BATER PONTO]                          │
└─────────────────────────────────────────┘
```

**Bibliotecas**:
- FullCalendar.js v6.0+ (calendário)
- Bootstrap 5.3

### Funcionário Dashboard

**Layout**:
```
┌─────────────────────────────────────────┐
│     ┌───────────────────────┐           │
│     │ BATER PONTO           │           │
│     │ (Verde/Cinza)         │           │
│     └───────────────────────┘           │
├─────────────────────────────────────────┤
│  📊 Resumo Janeiro/2025:                │
│  • Trabalhadas: 120h                    │
│  • Esperadas: 160h                      │
│  • Saldo: -40h                          │
├─────────────────────────────────────────┤
│  🕐 Últimas Marcações:                  │
│  • 15/01 08:05 - ENTRADA                │
│  • 15/01 12:00 - INTERVALO-INÍCIO       │
│  • 15/01 13:00 - INTERVALO-FIM          │
│  • 15/01 18:10 - SAÍDA                  │
│  ...                                    │
├─────────────────────────────────────────┤
│  [Justificar Falta/Atraso]              │
└─────────────────────────────────────────┘
```

**Responsivo**: Mobile-first com breakpoints

---

## 🧪 Testes

### Teste de Login

```bash
# Criar usuário admin manualmente
php spark shield:user create admin@ponto.com.br Admin@123 admin

# Adicionar ao grupo admin
php spark shield:group add 1 admin

# Acessar /login e testar
```

### Teste de Filtros

```bash
# 1. Tentar acessar /admin/dashboard sem login
# Deve redirecionar para /login

# 2. Login como funcionário
# Tentar acessar /admin/dashboard
# Deve redirecionar com mensagem de erro

# 3. Login como admin
# Acessar /admin/dashboard
# Deve funcionar normalmente
```

### Teste de Validação CPF

CPFs válidos para teste:
- `111.111.111-11` - **INVÁLIDO** (sequência)
- `123.456.789-09` - **VÁLIDO**
- `000.000.000-00` - **INVÁLIDO** (zeros)
- `529.982.247-25` - **VÁLIDO**

### Teste de Senha Forte

Senhas para teste:
- `admin123` - **INVÁLIDA** (sem maiúscula, sem especial)
- `Admin123` - **INVÁLIDA** (sem caractere especial)
- `Admin@123` - **VÁLIDA** ✅
- `SenhaForte@2025` - **VÁLIDA** ✅

---

## 📝 Checklist de Validação

Antes de prosseguir para Fase 4:

**Autenticação**:
- [ ] Shield instalado e migrations rodadas
- [ ] 3 grupos criados (admin, gestor, funcionario)
- [ ] Login funciona com validação de e-mail/senha
- [ ] Proteção brute force ativa (5 tentativas)
- [ ] Registro valida CPF com checksum
- [ ] Registro exige senha forte
- [ ] Session regenera ID após login
- [ ] Logout destrói sessão

**Filtros**:
- [ ] AuthFilter bloqueia rotas protegidas
- [ ] AdminFilter permite só admin em /admin/*
- [ ] ManagerFilter permite gestor/admin em /gestor/*
- [ ] Filtros redirecionam corretamente

**Dashboards**:
- [ ] AdminDashboard mostra cards com totais
- [ ] AdminDashboard exibe gráfico Chart.js
- [ ] AdminDashboard lista alertas
- [ ] GestorDashboard mostra justificativas pendentes
- [ ] GestorDashboard permite aprovar/rejeitar
- [ ] GestorDashboard exibe calendário FullCalendar
- [ ] EmployeeDashboard mostra botão bater ponto
- [ ] EmployeeDashboard calcula saldo do mês
- [ ] EmployeeDashboard lista últimas marcações
- [ ] Todos dashboards são responsivos mobile

---

## 🎯 Próximos Passos

### Fase 4: Registro de Ponto Core (Semana 7-8)

1. Implementar TimePunchController
2. Validação de horário permitido (±15min tolerância)
3. Detecção automática de tipo (entrada/saída/intervalo)
4. Geração de NSR sequencial único
5. Cálculo de hash SHA-256
6. Geração de comprovante PDF (Portaria 671/2021)
7. QR Code no comprovante para validação

---

## 📚 Referências

- [CodeIgniter Shield Docs](https://shield.codeigniter.com/)
- [Bootstrap 5 Docs](https://getbootstrap.com/docs/5.3/)
- [Chart.js Docs](https://www.chartjs.org/)
- [FullCalendar Docs](https://fullcalendar.io/)
- [Portaria MTE 671/2021](http://www.normaslegais.com.br/legislacao/portariamte671_2021.htm)

---

## ⚠️ Notas Importantes

1. **Shield não está pré-configurado**: É necessário executar `php spark shield:setup` manualmente
2. **Migrations obrigatórias**: Shield cria várias tabelas necessárias
3. **Código fornecido é completo**: Todos controllers e filtros estão em `FASE3_IMPLEMENTATION_GUIDE.md`
4. **Implementação manual necessária**: Copiar código do guia para os arquivos
5. **Testes essenciais**: Validar todos filtros antes de prosseguir

---

## ✅ Status da Fase 3

**STATUS**: 📄 **GUIA DE IMPLEMENTAÇÃO CRIADO**

A Fase 3 fornece código completo e documentação detalhada em:
- ✅ `FASE3_IMPLEMENTATION_GUIDE.md` - Código completo de todos componentes
- ✅ `README_FASE3.md` - Este arquivo (resumo e instruções)

**Próxima ação**: Seguir o guia de implementação passo a passo.

**Data de Criação**: 2025-01-15

---

**Desenvolvido com ❤️ para empresas brasileiras**
