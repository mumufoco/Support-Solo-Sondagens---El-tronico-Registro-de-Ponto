# 🧪 Dados de Teste e Stored Procedures

## Sistema de Ponto Eletrônico - Dados de Demonstração

---

## 📊 Dados de Teste Criados

### 👥 Funcionários (7 no total)

#### 1. **Administrador**
```
Nome:     Administrador do Sistema
Email:    admin@ponto.com.br
Senha:    Admin@123
CPF:      000.000.000-00
Código:   ADMIN001
Role:     admin
Depto:    TI
```

#### 2. **Gestor de RH**
```
Nome:     Maria Silva
Email:    maria.silva@empresa.com.br
Senha:    Test@123
CPF:      111.111.111-11
Código:   GEST0001
Role:     gestor
Depto:    RH
Jornada:  08:00 às 17:00
```

#### 3. **Gestor de TI**
```
Nome:     João Santos
Email:    joao.santos@empresa.com.br
Senha:    Test@123
CPF:      222.222.222-22
Código:   GEST0002
Role:     gestor
Depto:    TI
Jornada:  09:00 às 18:00
```

#### 4. **Analista de RH**
```
Nome:     Ana Paula Oliveira
Email:    ana.oliveira@empresa.com.br
Senha:    Test@123
CPF:      333.333.333-33
Código:   FUNC0001
Role:     funcionario
Depto:    RH
Gestor:   Maria Silva
Jornada:  08:00 às 17:00
```

#### 5. **Desenvolvedor**
```
Nome:     Carlos Eduardo Costa
Email:    carlos.costa@empresa.com.br
Senha:    Test@123
CPF:      444.444.444-44
Código:   FUNC0002
Role:     funcionario
Depto:    TI
Gestor:   João Santos
Jornada:  09:00 às 18:00
```

#### 6. **Vendedora**
```
Nome:     Beatriz Fernandes
Email:    beatriz.fernandes@empresa.com.br
Senha:    Test@123
CPF:      555.555.555-55
Código:   FUNC0003
Role:     funcionario
Depto:    Vendas
Gestor:   (sem gestor)
Jornada:  08:30 às 17:30
```

#### 7. **Contador**
```
Nome:     Ricardo Almeida
Email:    ricardo.almeida@empresa.com.br
Senha:    Test@123
CPF:      666.666.666-66
Código:   FUNC0004
Role:     funcionario
Depto:    Financeiro
Gestor:   (sem gestor)
Jornada:  08:00 às 17:00
```

### 📌 Hierarquia Organizacional

```
Empresa Demo
├── TI (Administrador)
│
├── RH
│   ├── Maria Silva (Gestor)
│   └── Ana Paula Oliveira (Funcionária)
│
├── TI
│   ├── João Santos (Gestor)
│   └── Carlos Eduardo Costa (Funcionário)
│
├── Vendas
│   └── Beatriz Fernandes (Funcionária)
│
└── Financeiro
    └── Ricardo Almeida (Funcionário)
```

### ⏰ Registros de Ponto

**Total:** 36 registros criados

**Período:** Últimos 5 dias úteis

**Funcionários com registros:** Todos os 6 funcionários de teste (exceto admin)

**Tipos de registro:**
- Entrada (no horário previsto)
- Saída (no horário previsto + variação aleatória)

**Método:** Código único

**Geolocalização:** São Paulo, SP (-23.550520, -46.633308)

**Geofence:** Todos válidos

---

## 📈 Views Criadas (8 views)

### 1. `v_employee_summary`
**Propósito:** Resumo completo de funcionários

**Colunas:**
- Dados pessoais (nome, email, CPF, código)
- Dados organizacionais (role, departamento, cargo)
- Status (ativo/inativo)
- Banco de horas (extras e devidas)
- Nome do gestor
- Estatísticas (pontos hoje, justificativas pendentes, advertências)

**Uso:**
```sql
SELECT * FROM v_employee_summary;
```

### 2. `v_daily_attendance`
**Propósito:** Frequência diária de todos os funcionários

**Colunas:**
- Funcionário e departamento
- Primeira entrada e última saída
- Quantidade de registros
- Status (presente/justificado/ausente/fim_de_semana)
- Horas trabalhadas, extras e devidas

**Uso:**
```sql
SELECT * FROM v_daily_attendance
WHERE status = 'ausente';
```

### 3. `v_monthly_hours`
**Propósito:** Horas trabalhadas por funcionário por mês

**Colunas:**
- Funcionário e departamento
- Mês de referência
- Dias trabalhados
- Total de horas (trabalhadas, esperadas, extras, devidas)
- Dias completos e justificados

**Uso:**
```sql
SELECT * FROM v_monthly_hours
WHERE month = DATE_TRUNC('month', CURRENT_DATE);
```

### 4. `v_pending_justifications`
**Propósito:** Justificativas aguardando aprovação

**Colunas:**
- Funcionário e gestor
- Data da justificativa
- Motivo e descrição
- Dias pendentes

**Uso:**
```sql
SELECT * FROM v_pending_justifications
ORDER BY days_pending DESC;
```

### 5. `v_chat_unread_messages`
**Propósito:** Contagem de mensagens não lidas por sala

**Colunas:**
- Usuário e sala
- Nome e tipo da sala
- Quantidade não lida
- Última mensagem

**Uso:**
```sql
SELECT * FROM v_chat_unread_messages
WHERE user_id = 'UUID_DO_USUARIO';
```

### 6. `v_department_statistics`
**Propósito:** Estatísticas agregadas por departamento

**Colunas:**
- Total de funcionários (ativos e inativos)
- Quantidade de gestores
- Média de horas extras/devidas
- Presentes hoje
- Advertências último mês

**Uso:**
```sql
SELECT * FROM v_department_statistics
ORDER BY total_employees DESC;
```

### 7. `v_late_arrivals`
**Propósito:** Atrasos dos últimos 30 dias

**Colunas:**
- Funcionário e departamento
- Horário do registro
- Horário esperado
- Minutos de atraso
- Localização e validação geofence

**Uso:**
```sql
SELECT * FROM v_late_arrivals
WHERE minutes_late > 15;
```

### 8. `v_overtime_summary`
**Propósito:** Resumo de horas extras

**Colunas:**
- Funcionário e departamento
- Saldo de horas extras
- Dias com hora extra
- Total de horas extras no mês
- Média diária

**Uso:**
```sql
SELECT * FROM v_overtime_summary
ORDER BY total_overtime_last_month DESC
LIMIT 10;
```

---

## 🔧 Stored Procedures Criadas (5 procedures)

### 1. `sp_register_punch()`
**Propósito:** Registrar ponto com validações automáticas

**Parâmetros:**
- `p_employee_id` (UUID) - ID do funcionário
- `p_punch_type` (VARCHAR) - entrada/saida/pausa_inicio/pausa_fim
- `p_punch_method` (VARCHAR) - codigo/qrcode/facial/biometria
- `p_latitude` (DECIMAL) - Opcional
- `p_longitude` (DECIMAL) - Opcional
- `p_photo_path` (TEXT) - Opcional

**Retorna:**
- `success` (BOOLEAN) - Sucesso ou falha
- `message` (TEXT) - Mensagem descritiva
- `punch_id` (UUID) - ID do registro criado
- `is_valid` (BOOLEAN) - Validação geofence

**Validações:**
- Verifica se funcionário existe e está ativo
- Valida sequência de registros (não pode registrar entrada duas vezes)
- Valida geofence se coordenadas fornecidas
- Primeiro registro do dia deve ser entrada

**Uso:**
```sql
SELECT * FROM sp_register_punch(
    'UUID_DO_FUNCIONARIO',
    'entrada',
    'codigo',
    -23.550520,
    -46.633308
);
```

**Exemplo de retorno:**
```
success | message                          | punch_id      | is_valid
--------|----------------------------------|---------------|----------
true    | Ponto registrado com sucesso     | uuid-aqui     | true
```

### 2. `sp_approve_justification()`
**Propósito:** Aprovar ou rejeitar justificativa com notificação automática

**Parâmetros:**
- `p_justification_id` (UUID) - ID da justificativa
- `p_reviewer_id` (UUID) - ID do revisor
- `p_approved` (BOOLEAN) - true = aprovada, false = rejeitada
- `p_notes` (TEXT) - Opcional - observações

**Retorna:**
- `success` (BOOLEAN) - Sucesso ou falha
- `message` (TEXT) - Mensagem descritiva

**Validações:**
- Verifica se justificativa existe
- Verifica se revisor tem permissão (admin ou gestor)
- Atualiza status da justificativa
- Cria notificação automática para o funcionário

**Uso:**
```sql
SELECT * FROM sp_approve_justification(
    'UUID_DA_JUSTIFICATIVA',
    'UUID_DO_GESTOR',
    true,
    'Atestado médico válido'
);
```

### 3. `sp_calculate_employee_balance()`
**Propósito:** Calcular banco de horas de um funcionário em um período

**Parâmetros:**
- `p_employee_id` (UUID) - ID do funcionário
- `p_start_date` (DATE) - Data inicial
- `p_end_date` (DATE) - Data final

**Retorna:**
- `total_days` (INTEGER) - Total de dias no período
- `total_hours_worked` (DECIMAL) - Total de horas trabalhadas
- `total_expected_hours` (DECIMAL) - Total de horas esperadas
- `total_extra_hours` (DECIMAL) - Total de horas extras
- `total_owed_hours` (DECIMAL) - Total de horas devidas
- `balance` (DECIMAL) - Saldo (extras - devidas)

**Uso:**
```sql
SELECT * FROM sp_calculate_employee_balance(
    'UUID_DO_FUNCIONARIO',
    '2025-01-01',
    '2025-01-31'
);
```

**Exemplo de retorno:**
```
total_days | total_hours | expected_hours | extra_hours | owed_hours | balance
-----------|-------------|----------------|-------------|------------|---------
20         | 165.50      | 160.00         | 5.50        | 0.00       | 5.50
```

### 4. `sp_get_dashboard_metrics()`
**Propósito:** Obter métricas do dashboard baseado no role do usuário

**Parâmetros:**
- `p_user_id` (UUID) - ID do usuário logado

**Retorna:**
- `total_employees` (INTEGER) - Total de funcionários
- `active_employees` (INTEGER) - Funcionários ativos
- `present_today` (INTEGER) - Presentes hoje
- `absent_today` (INTEGER) - Ausentes hoje
- `pending_justifications` (INTEGER) - Justificativas pendentes
- `warnings_last_month` (INTEGER) - Advertências último mês
- `total_extra_hours` (DECIMAL) - Total horas extras
- `total_owed_hours` (DECIMAL) - Total horas devidas

**Comportamento por Role:**
- **Admin:** Vê dados de toda empresa
- **Gestor:** Vê dados de sua equipe
- **Funcionário:** Vê apenas seus próprios dados

**Uso:**
```sql
SELECT * FROM sp_get_dashboard_metrics('UUID_DO_USUARIO');
```

### 5. `sp_cleanup_old_data()`
**Propósito:** Limpar dados antigos e expirados (manutenção)

**Sem parâmetros**

**Retorna:**
- `table_name` (TEXT) - Nome da tabela limpa
- `rows_deleted` (INTEGER) - Quantidade de registros removidos

**Limpeza executada:**
- Notificações lidas com mais de 90 dias
- Mensagens de chat deletadas com mais de 30 dias
- Relatórios expirados ou completos com prazo expirado
- Exportações LGPD expiradas
- Atualiza status offline de usuários inativos (1 hora)

**Uso:**
```sql
SELECT * FROM sp_cleanup_old_data();
```

**Exemplo de retorno:**
```
table_name          | rows_deleted
--------------------|-------------
notifications       | 156
chat_messages       | 42
report_queue        | 8
data_exports        | 3
chat_online_users   | 12
```

**Recomendação:** Executar diariamente via cron job

---

## 🔍 Exemplos de Consultas Úteis

### Funcionários por Departamento
```sql
SELECT
    department,
    COUNT(*) as total,
    COUNT(CASE WHEN active THEN 1 END) as ativos
FROM employees
WHERE deleted_at IS NULL
GROUP BY department
ORDER BY total DESC;
```

### Frequência de Hoje
```sql
SELECT
    employee_name,
    department,
    first_entry,
    last_exit,
    status,
    net_work_hours
FROM v_daily_attendance
ORDER BY department, employee_name;
```

### Top 10 Funcionários com Mais Horas Extras
```sql
SELECT
    name,
    department,
    extra_hours_balance,
    owed_hours_balance
FROM employees
WHERE active = true
ORDER BY extra_hours_balance DESC
LIMIT 10;
```

### Últimos 20 Registros de Ponto
```sql
SELECT
    e.name,
    tp.punch_time,
    tp.punch_type,
    tp.punch_method,
    tp.is_geofence_valid
FROM time_punches tp
JOIN employees e ON e.id = tp.employee_id
ORDER BY tp.punch_time DESC
LIMIT 20;
```

### Justificativas Pendentes por Gestor
```sql
SELECT
    manager_name,
    COUNT(*) as pendentes,
    AVG(days_pending) as media_dias_pendente
FROM v_pending_justifications
GROUP BY manager_name
ORDER BY pendentes DESC;
```

---

## 🧪 Script de Teste Completo

Execute este script para testar o sistema:

```sql
-- 1. Verificar funcionários
SELECT COUNT(*) as total_funcionarios FROM employees WHERE active = true;

-- 2. Verificar registros de ponto
SELECT COUNT(*) as total_registros FROM time_punches;

-- 3. Testar registro de ponto
SELECT * FROM sp_register_punch(
    (SELECT id FROM employees WHERE email = 'ana.oliveira@empresa.com.br'),
    'entrada',
    'codigo',
    -23.550520,
    -46.633308
);

-- 4. Ver resumo de funcionários
SELECT * FROM v_employee_summary ORDER BY name;

-- 5. Ver frequência de hoje
SELECT * FROM v_daily_attendance;

-- 6. Calcular banco de horas
SELECT * FROM sp_calculate_employee_balance(
    (SELECT id FROM employees WHERE email = 'carlos.costa@empresa.com.br'),
    CURRENT_DATE - INTERVAL '30 days',
    CURRENT_DATE
);

-- 7. Métricas do dashboard (como admin)
SELECT * FROM sp_get_dashboard_metrics(
    (SELECT id FROM employees WHERE email = 'admin@ponto.com.br')
);

-- 8. Ver estatísticas por departamento
SELECT * FROM v_department_statistics;
```

---

## 📊 Resumo dos Dados

| Componente | Quantidade |
|------------|------------|
| **Funcionários** | 7 |
| **Gestores** | 2 |
| **Funcionários comuns** | 5 |
| **Departamentos** | 4 |
| **Registros de Ponto** | 36 |
| **Views** | 8 |
| **Stored Procedures** | 5 |
| **Configurações** | 15 |

---

## ✅ Checklist de Validação

- [x] 7 funcionários criados
- [x] 2 gestores com hierarquia
- [x] 36 registros de ponto (5 dias úteis)
- [x] 8 views funcionando
- [x] 5 stored procedures testadas
- [x] Geofence validado
- [x] Senha padrão: Test@123 (todos os funcionários)
- [x] Documentação completa

---

## 🎯 Próximos Testes Sugeridos

1. **Testar Workflow de Justificativa:**
   - Criar justificativa para um funcionário
   - Aprovar como gestor
   - Verificar notificação criada

2. **Testar Chat:**
   - Criar sala de chat
   - Adicionar membros
   - Enviar mensagens
   - Testar reações

3. **Testar Relatórios:**
   - Solicitar relatório mensal
   - Verificar fila de processamento
   - Download do arquivo gerado

4. **Testar Advertências:**
   - Criar advertência
   - Adicionar testemunhas
   - Gerar PDF
   - Coletar assinaturas

5. **Testar LGPD:**
   - Solicitar exportação de dados
   - Revogar consentimento
   - Verificar auditoria

---

**Sistema de Ponto Eletrônico Brasileiro**
**Dados de Teste e Procedures Prontos para Uso**

✅ **7 Funcionários | 36 Registros | 8 Views | 5 Procedures**
