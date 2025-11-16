# 📊 Database SQL - Guia de Uso

## Visão Geral

Este arquivo `database.sql` contém o **schema completo** do Sistema de Ponto Eletrônico, incluindo todas as 23 tabelas, 2 views e configurações iniciais.

## 📋 Conteúdo do Arquivo

### Tabelas Criadas (23 tabelas)

#### 🔹 Tabelas Principais
1. **employees** - Funcionários e usuários do sistema
2. **time_punches** - Registros de ponto eletrônico
3. **biometric_templates** - Templates biométricos (digitais)
4. **justifications** - Justificativas de ausências
5. **warnings** - Advertências disciplinares

#### 🔹 Tabelas de Suporte
6. **geofences** - Cercas virtuais para validação de localização
7. **user_consents** - Consentimentos LGPD
8. **audit_logs** - Logs de auditoria
9. **notifications** - Notificações do sistema
10. **settings** - Configurações do sistema
11. **timesheet_consolidated** - Espelho de ponto consolidado
12. **data_exports** - Exportações de dados e relatórios

#### 🔹 Tabelas de Chat/Comunicação
13. **push_subscriptions** - Assinaturas WebPush
14. **chat_messages** - Mensagens do chat interno
15. **chat_rooms** - Salas de chat

#### 🔹 Tabelas de Processamento
16. **report_queue** - Fila de geração de relatórios assíncronos

#### 🔹 Tabelas da Fase 17+ (Segurança Híbrida)
17. **oauth_tokens** - Tokens OAuth 2.0 para API móvel
18. **push_notification_tokens** - Tokens FCM para push notifications
19. **rate_limits** - Controle de rate limiting

#### 🔹 Tabela Sistema
20. **migrations** - Controle de migrations do CodeIgniter

### Views (2)
- **vw_employee_performance** - Visão de performance dos funcionários
- **vw_daily_attendance** - Resumo diário de presença

### Dados Iniciais
- **20 configurações do sistema** (settings)
- Configurações de empresa, timezone, segurança, LGPD, etc.

---

## 🚀 Como Usar

### Opção 1: Via Linha de Comando (MySQL)

```bash
# 1. Criar o banco de dados
mysql -u root -p -e "CREATE DATABASE ponto_eletronico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Importar o arquivo SQL
mysql -u root -p ponto_eletronico < public/database.sql

# 3. Verificar a importação
mysql -u root -p ponto_eletronico -e "SHOW TABLES;"
```

### Opção 2: Via phpMyAdmin

1. Acesse phpMyAdmin
2. Crie um novo banco de dados:
   - Nome: `ponto_eletronico`
   - Collation: `utf8mb4_unicode_ci`
3. Selecione o banco criado
4. Clique em **"Importar"**
5. Selecione o arquivo `database.sql`
6. Clique em **"Executar"**

### Opção 3: Via HeidiSQL / MySQL Workbench

1. Conecte ao servidor MySQL
2. Crie novo banco de dados: `ponto_eletronico`
3. Selecione: File > Run SQL file
4. Escolha: `public/database.sql`
5. Execute

### Opção 4: Via Instalador Web

O instalador web (`public/install.php`) **automaticamente** executa as migrations, então você **NÃO precisa** importar o `database.sql` manualmente se usar o instalador.

---

## 📊 Estrutura Detalhada

### Employees (Funcionários)
```sql
Campos principais:
- id, name, email, password (Argon2id)
- cpf, unique_code (para ponto)
- role (admin/gestor/funcionario)
- department, position
- two_factor_secret, two_factor_enabled (2FA)
- extra_hours_balance, owed_hours_balance
- manager_id (hierarquia)
```

### Time Punches (Registros de Ponto)
```sql
Campos principais:
- employee_id, punch_time
- punch_type (entrada/saida/intervalo_inicio/intervalo_fim)
- method (biometria/facial/codigo/manual/webservice)
- geolocation, ip_address
- biometric_score, facial_confidence
- signature (ICP-Brasil), hash (SHA-256)
- is_anomaly (detecção ML)
```

### OAuth Tokens (Fase 17+)
```sql
Campos principais:
- employee_id, access_token, refresh_token
- token_type (Bearer), scope
- expires_at, revoked
- client_id, device_info
```

### Settings (Configurações)
```sql
20 configurações pré-inseridas:
- company_name, company_cnpj
- timezone, date_format, time_format
- tolerance_minutes, extra_hours_enabled
- biometric_threshold, facial_threshold
- enable_2fa, enable_push_notifications
- lgpd_dpo_email
```

---

## 🔒 Segurança e Conformidade

### LGPD (Lei 13.709/2018)
- ✅ Tabela `user_consents` para consentimentos
- ✅ Tabela `audit_logs` para rastreabilidade
- ✅ Campo `lgpd_dpo_email` nas configurações
- ✅ Criptografia de dados biométricos

### Portaria MTE 671/2021
- ✅ Registro de ponto com assinatura digital
- ✅ Hash SHA-256 para integridade
- ✅ Geolocalização opcional
- ✅ Espelho de ponto consolidado

### Segurança Implementada
- ✅ Passwords com Argon2id
- ✅ Autenticação 2FA (TOTP)
- ✅ OAuth 2.0 para API
- ✅ Rate Limiting
- ✅ Audit Logs completos
- ✅ Tokens com expiração

---

## 🛠️ Pós-Instalação

### 1. Criar Usuário Administrador

**Via SQL:**
```sql
INSERT INTO employees (
    name, email, cpf, password, role, department, position,
    unique_code, active, created_at, updated_at
) VALUES (
    'Administrador',
    'admin@empresa.com.br',
    '000.000.000-00',
    '$argon2id$v=19$m=65536,t=4,p=2$base64encodedstring',  -- Hash de 'Admin@123'
    'admin',
    'Administração',
    'Administrador',
    'ADM000001',
    1,
    NOW(),
    NOW()
);
```

**Via Instalador Web:**
O instalador já cria o usuário admin automaticamente com a senha que você definir.

### 2. Ajustar Configurações

```sql
-- Atualizar nome da empresa
UPDATE settings SET value = 'Sua Empresa LTDA' WHERE `key` = 'company_name';

-- Atualizar CNPJ
UPDATE settings SET value = '12.345.678/0001-90' WHERE `key` = 'company_cnpj';

-- Atualizar email de notificações
UPDATE settings SET value = 'contato@suaempresa.com' WHERE `key` = 'notification_email';

-- Atualizar email do DPO (LGPD)
UPDATE settings SET value = 'dpo@suaempresa.com' WHERE `key` = 'lgpd_dpo_email';
```

### 3. Verificar Instalação

```sql
-- Contar tabelas
SELECT COUNT(*) as total_tabelas FROM information_schema.tables
WHERE table_schema = 'ponto_eletronico';
-- Deve retornar: 23

-- Verificar configurações
SELECT COUNT(*) as total_configs FROM settings;
-- Deve retornar: 20

-- Verificar views
SELECT COUNT(*) as total_views FROM information_schema.views
WHERE table_schema = 'ponto_eletronico';
-- Deve retornar: 2
```

---

## 📐 Relacionamentos (Foreign Keys)

```
employees (1) ----< (N) time_punches
employees (1) ----< (N) justifications
employees (1) ----< (N) warnings
employees (1) ----< (N) biometric_templates
employees (1) ----< (N) oauth_tokens
employees (1) ----< (N) push_notification_tokens
employees (1) ----< (N) user_consents
employees (1) ----< (N) notifications
employees (1) ----< (N) audit_logs
employees (1) ----< (N) timesheet_consolidated

employees (1) ----< (N) employees (manager_id - auto-relacionamento)
```

---

## 🔍 Queries Úteis

### Relatório de Presença do Dia
```sql
SELECT
    e.name,
    e.department,
    MIN(CASE WHEN tp.punch_type = 'entrada' THEN tp.punch_time END) as entrada,
    MIN(CASE WHEN tp.punch_type = 'saida' THEN tp.punch_time END) as saida
FROM employees e
LEFT JOIN time_punches tp ON e.id = tp.employee_id
    AND DATE(tp.punch_time) = CURDATE()
WHERE e.active = 1
GROUP BY e.id, e.name, e.department
ORDER BY e.name;
```

### Funcionários com Horas Extras
```sql
SELECT
    name,
    department,
    extra_hours_balance,
    owed_hours_balance
FROM employees
WHERE extra_hours_balance > 0 OR owed_hours_balance > 0
ORDER BY extra_hours_balance DESC;
```

### Advertências do Mês Atual
```sql
SELECT
    e.name,
    w.type,
    w.reason,
    w.date
FROM warnings w
JOIN employees e ON w.employee_id = e.id
WHERE MONTH(w.date) = MONTH(CURDATE())
    AND YEAR(w.date) = YEAR(CURDATE())
ORDER BY w.date DESC;
```

### Registros Anômalos (ML)
```sql
SELECT
    e.name,
    tp.punch_time,
    tp.punch_type,
    tp.anomaly_reason
FROM time_punches tp
JOIN employees e ON tp.employee_id = e.id
WHERE tp.is_anomaly = 1
    AND DATE(tp.punch_time) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
ORDER BY tp.punch_time DESC;
```

---

## ⚠️ Avisos Importantes

### 1. Backup
**SEMPRE** faça backup antes de importar:
```bash
mysqldump -u root -p ponto_eletronico > backup_$(date +%Y%m%d_%H%M%S).sql
```

### 2. Permissões MySQL
Certifique-se de que o usuário tem permissões adequadas:
```sql
GRANT ALL PRIVILEGES ON ponto_eletronico.* TO 'ponto_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Charset
**SEMPRE** use `utf8mb4` e `utf8mb4_unicode_ci`:
- Suporta todos os caracteres Unicode (incluindo emojis)
- Obrigatório para nomes brasileiros com acentuação
- Requerido para conformidade LGPD

### 4. Tamanho do Arquivo
- O arquivo SQL tem ~25KB
- Importação leva ~2-5 segundos
- Banco vazio ocupa ~512KB

---

## 🆚 database.sql vs Instalador Web

| Aspecto | database.sql | Instalador Web |
|---------|--------------|----------------|
| **Método** | Import SQL direto | Migrations via spark |
| **Velocidade** | Muito rápido (~2s) | Médio (~30s) |
| **Facilidade** | Requer conhecimento MySQL | Interface gráfica |
| **Ideal para** | Desenvolvedores, servidores | Usuários finais |
| **Customização** | Total (edite o SQL) | Limitada |
| **Criação de Admin** | Manual | Automática |
| **.env** | Manual | Automático |

**Recomendação:**
- **Desenvolvimento/Testing**: Use `database.sql` (mais rápido)
- **Produção/Clientes**: Use instalador web (mais fácil)

---

## 📚 Referências

- [MySQL CREATE TABLE](https://dev.mysql.com/doc/refman/8.0/en/create-table.html)
- [Portaria MTE 671/2021](http://www.in.gov.br/web/dou/-/portaria-n-671-de-8-de-novembro-de-2021-357604199)
- [LGPD Lei 13.709/2018](http://www.planalto.gov.br/ccivil_03/_ato2015-2018/2018/lei/l13709.htm)
- [CodeIgniter 4 Migrations](https://codeigniter.com/user_guide/dbmgmt/migration.html)

---

## 🆘 Troubleshooting

### Erro: "Table already exists"
```bash
# Dropar todas as tabelas primeiro
mysql -u root -p ponto_eletronico -e "DROP DATABASE ponto_eletronico;"
mysql -u root -p -e "CREATE DATABASE ponto_eletronico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p ponto_eletronico < public/database.sql
```

### Erro: "Access denied"
```sql
-- Verificar permissões
SHOW GRANTS FOR 'seu_usuario'@'localhost';

-- Conceder permissões
GRANT ALL PRIVILEGES ON ponto_eletronico.* TO 'seu_usuario'@'localhost';
FLUSH PRIVILEGES;
```

### Erro: "Packet too large"
```bash
# Aumentar max_allowed_packet
mysql -u root -p -e "SET GLOBAL max_allowed_packet=67108864;"
# Ou edite my.cnf: max_allowed_packet=64M
```

### Verificar Charset
```sql
SELECT
    table_schema,
    table_name,
    table_collation
FROM information_schema.tables
WHERE table_schema = 'ponto_eletronico';
-- Todos devem ser utf8mb4_unicode_ci
```

---

**Sistema de Ponto Eletrônico** © 2024
Conforme Portaria MTE 671/2021 e LGPD Lei 13.709/2018
