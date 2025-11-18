# 🗄️ Estrutura Completa do Banco de Dados

## Sistema de Ponto Eletrônico - PostgreSQL/Supabase

---

## 📊 Resumo Geral

### Estatísticas do Banco

| Métrica | Valor |
|---------|-------|
| **Total de Tabelas** | 19 |
| **Total de Índices** | 60+ |
| **Total de Políticas RLS** | 60+ |
| **Total de Triggers** | 12 |
| **Total de Funções** | 5 |
| **Registros Iniciais** | 16 (1 admin + 15 settings) |

---

## 📋 Lista Completa de Tabelas

### 1. **Gestão de Usuários e Autenticação**

#### `employees` (Funcionários)
**Propósito:** Tabela central do sistema, armazena todos os funcionários

**Colunas principais:**
- `id` (UUID) - Chave primária
- `name`, `email`, `password` - Dados básicos
- `cpf`, `unique_code` - Identificadores únicos
- `role` (admin/gestor/funcionario) - Controle de acesso
- `manager_id` - Hierarquia organizacional
- `department`, `position` - Dados organizacionais
- `expected_hours_daily` - Jornada esperada
- `work_schedule_start`, `work_schedule_end` - Horário de trabalho
- `active` - Status ativo/inativo
- `extra_hours_balance`, `owed_hours_balance` - Banco de horas
- `two_factor_*` - Autenticação 2FA
- `created_at`, `updated_at`, `deleted_at` - Timestamps

**Índices:** 6 índices
**Políticas RLS:** 6 políticas
**Trigger:** update_updated_at

---

### 2. **Registro de Ponto**

#### `time_punches` (Registros de Ponto)
**Propósito:** Armazena todos os registros de ponto dos funcionários

**Colunas principais:**
- `id` (UUID) - Chave primária
- `employee_id` (UUID) - FK para employees
- `punch_time` - Data/hora do registro
- `punch_type` (entrada/saida/pausa_inicio/pausa_fim)
- `punch_method` (codigo/qrcode/facial/biometria)
- `latitude`, `longitude`, `location_accuracy` - Geolocalização
- `location_address` - Endereço reverso
- `device_info`, `ip_address` - Dados do dispositivo
- `is_geofence_valid` - Validação de cerca virtual
- `photo_path`, `similarity_score` - Reconhecimento facial
- `validation_hash` - Hash SHA-256 para integridade
- `notes` - Observações

**Índices:** 3 índices
**Políticas RLS:** 6 políticas
**Trigger:** auto_consolidate_on_punch (consolida automaticamente)

#### `timesheet_consolidated` (Consolidação Diária)
**Propósito:** Consolidação automática dos registros de ponto por dia

**Colunas principais:**
- `id` (UUID) - Chave primária
- `employee_id`, `work_date` - Funcionário e data
- `first_punch_time`, `last_punch_time` - Primeiro e último registro
- `total_work_hours` - Total de horas trabalhadas
- `break_hours` - Horas de pausa
- `net_work_hours` - Horas líquidas trabalhadas
- `expected_hours` - Horas esperadas
- `extra_hours` - Horas extras
- `owed_hours` - Horas devidas
- `punch_count` - Quantidade de registros
- `has_justification` - Tem justificativa
- `is_holiday`, `is_weekend` - Flags especiais
- `status` (incomplete/complete/reviewed/approved)
- `nsr` - Número Sequencial de Registro (MTE)
- `validation_hash` - Hash para conformidade

**Índices:** 4 índices
**Políticas RLS:** 4 políticas
**Trigger:** update_updated_at

---

### 3. **Biometria**

#### `biometric_templates` (Templates Biométricos)
**Propósito:** Armazena templates para reconhecimento biométrico

**Colunas principais:**
- `id` (UUID) - Chave primária
- `employee_id` (UUID) - FK para employees
- `template_type` (facial/fingerprint)
- `template_data` - Template criptografado
- `quality_score` - Score de qualidade
- `is_primary` - Template principal
- `active` - Ativo/inativo
- `enrolled_at` - Data de cadastro
- `last_used_at` - Último uso

**Índices:** 3 índices
**Políticas RLS:** 2 políticas
**Trigger:** update_updated_at

---

### 4. **Localização e Geofencing**

#### `geofences` (Cercas Virtuais)
**Propósito:** Define áreas permitidas para registro de ponto

**Colunas principais:**
- `id` (UUID) - Chave primária
- `name`, `description` - Identificação
- `latitude`, `longitude` - Coordenadas do centro
- `radius_meters` - Raio em metros
- `active` - Ativo/inativo
- `applies_to_all` - Aplica a todos os funcionários
- `color` - Cor para visualização no mapa
- `created_by` (UUID) - Quem criou

**Índices:** 2 índices
**Políticas RLS:** 2 políticas
**Trigger:** update_updated_at

---

### 5. **Justificativas e Ausências**

#### `justifications` (Justificativas)
**Propósito:** Gerencia justificativas de ausências e faltas

**Colunas principais:**
- `id` (UUID) - Chave primária
- `employee_id` (UUID) - Funcionário
- `justification_date` - Data da ausência
- `reason` (atestado_medico/falta_justificada/licenca/ferias/outro)
- `description` - Detalhes
- `attachment_path` - Anexo (atestado, etc)
- `status` (pendente/aprovada/rejeitada)
- `reviewed_by` (UUID) - Quem revisou
- `reviewed_at` - Quando foi revisado
- `review_notes` - Observações da revisão

**Índices:** 3 índices
**Políticas RLS:** 5 políticas
**Trigger:** update_updated_at

---

### 6. **Sistema de Advertências**

#### `warnings` (Advertências)
**Propósito:** Gerencia advertências disciplinares (conformidade CLT)

**Colunas principais:**
- `id` (UUID) - Chave primária
- `employee_id` (UUID) - Funcionário advertido
- `warning_type` (verbal/escrita/suspensao/demissao_justa_causa)
- `severity` (leve/media/grave/gravissima)
- `title`, `description` - Detalhes da advertência
- `issued_by` (UUID) - Quem emitiu
- `issued_at` - Data de emissão
- `employee_signature` - Assinatura digital do funcionário
- `employee_signed_at` - Data da assinatura
- `employee_refused` - Se recusou a assinar
- `employee_refusal_reason` - Motivo da recusa
- `witness1_*`, `witness2_*` - Testemunhas
- `pdf_path` - Caminho do PDF gerado
- `status` (pendente/assinada/recusada/cancelada)

**Índices:** 3 índices
**Políticas RLS:** 5 políticas
**Trigger:** update_updated_at

---

### 7. **Chat em Tempo Real**

#### `chat_rooms` (Salas de Chat)
**Propósito:** Salas de conversa (privadas ou grupos)

**Colunas principais:**
- `id` (UUID) - Chave primária
- `name` - Nome da sala (para grupos)
- `type` (private/group/channel)
- `description` - Descrição
- `avatar_url` - Avatar do grupo
- `created_by` (UUID) - Criador
- `is_group` - É grupo ou privado
- `last_message_at` - Última mensagem (para ordenação)

**Índices:** 3 índices
**Políticas RLS:** 3 políticas
**Trigger:** update_updated_at

#### `chat_room_members` (Membros das Salas)
**Propósito:** Relaciona funcionários com salas de chat

**Colunas principais:**
- `id` (UUID) - Chave primária
- `room_id` (UUID) - Sala
- `user_id` (UUID) - Usuário
- `role` (owner/admin/member)
- `joined_at` - Entrada na sala
- `last_read_at` - Última leitura (para contadores)
- `notifications_enabled` - Notificações ativas

**Índices:** 2 índices
**Políticas RLS:** 2 políticas

#### `chat_messages` (Mensagens)
**Propósito:** Mensagens do chat

**Colunas principais:**
- `id` (UUID) - Chave primária
- `room_id` (UUID) - Sala
- `sender_id` (UUID) - Remetente
- `message_type` (text/image/file/audio/video/system)
- `content` - Conteúdo da mensagem
- `file_url`, `file_name`, `file_size` - Arquivo anexo
- `reply_to_id` (UUID) - Resposta a outra mensagem
- `is_edited`, `edited_at` - Edição
- `is_deleted`, `deleted_at` - Exclusão lógica

**Índices:** 3 índices
**Políticas RLS:** 3 políticas
**Trigger:** update_room_last_message (atualiza timestamp da sala)

#### `chat_message_reactions` (Reações)
**Propósito:** Reações emoji às mensagens

**Colunas principais:**
- `id` (UUID) - Chave primária
- `message_id` (UUID) - Mensagem
- `user_id` (UUID) - Usuário
- `emoji` - Emoji da reação

**Índices:** 2 índices
**Políticas RLS:** 3 políticas

#### `chat_online_users` (Status Online)
**Propósito:** Status de presença dos usuários

**Colunas principais:**
- `user_id` (UUID) - Chave primária
- `last_seen_at` - Última atividade
- `status` (online/away/busy/offline)
- `updated_at` - Última atualização

**Políticas RLS:** 2 políticas
**Trigger:** update_updated_at

---

### 8. **Notificações**

#### `notifications` (Notificações)
**Propósito:** Sistema de notificações in-app

**Colunas principais:**
- `id` (UUID) - Chave primária
- `employee_id` (UUID) - Destinatário
- `title`, `message` - Conteúdo
- `type` (info/success/warning/error/alert)
- `is_read` - Lida ou não
- `action_url` - URL de ação
- `read_at` - Data de leitura

**Índices:** 3 índices
**Políticas RLS:** 3 políticas

#### `push_subscriptions` (Push Notifications)
**Propósito:** Assinaturas para notificações push (Web Push API)

**Colunas principais:**
- `id` (UUID) - Chave primária
- `employee_id` (UUID) - Funcionário
- `endpoint` - Endpoint Web Push
- `p256dh_key`, `auth_key` - Chaves de criptografia
- `user_agent`, `device_name` - Informações do dispositivo
- `active` - Ativo/inativo
- `last_used_at` - Último uso

**Índices:** 2 índices
**Políticas RLS:** 1 política

---

### 9. **Relatórios e Exportações**

#### `report_queue` (Fila de Relatórios)
**Propósito:** Fila assíncrona para geração de relatórios

**Colunas principais:**
- `id` (UUID) - Chave primária
- `requested_by` (UUID) - Solicitante
- `report_type` - Tipo de relatório
- `parameters` (JSONB) - Parâmetros da geração
- `status` (pending/processing/completed/failed/expired)
- `file_path` - Caminho do arquivo gerado
- `file_format` (pdf/xlsx/csv/json/zip)
- `file_size` - Tamanho do arquivo
- `error_message` - Mensagem de erro (se houver)
- `progress` - Progresso da geração (0-100)
- `started_at`, `completed_at`, `expires_at` - Timestamps

**Índices:** 3 índices
**Políticas RLS:** 3 políticas

#### `data_exports` (Exportações LGPD)
**Propósito:** Exportações de dados para conformidade LGPD

**Colunas principais:**
- `id` (UUID) - Chave primária
- `employee_id` (UUID) - Solicitante
- `export_type` (complete/personal_data/timesheet/biometric/communications)
- `status` (pending/processing/completed/failed/expired)
- `file_path` - Arquivo ZIP gerado
- `file_size` - Tamanho
- `download_count` - Contagem de downloads
- `last_downloaded_at` - Último download
- `expires_at` - Expiração (30 dias após geração)

**Índices:** 3 índices
**Políticas RLS:** 3 políticas

---

### 10. **Configurações e Logs**

#### `settings` (Configurações)
**Propósito:** Configurações globais do sistema

**Colunas principais:**
- `id` (UUID) - Chave primária
- `key` - Chave única da configuração
- `value` - Valor
- `type` (string/integer/decimal/boolean/json)
- `description` - Descrição
- `is_public` - Visível para não-admins

**Registros:** 15 configurações padrão
**Índices:** 1 índice
**Políticas RLS:** 2 políticas
**Trigger:** update_updated_at

#### `audit_logs` (Logs de Auditoria)
**Propósito:** Auditoria completa de ações (conformidade LGPD)

**Colunas principais:**
- `id` (UUID) - Chave primária
- `user_id` (UUID) - Usuário que executou
- `action` - Ação realizada
- `entity_type` - Tipo de entidade
- `entity_id` (UUID) - ID da entidade
- `old_values`, `new_values` (JSONB) - Valores antes/depois
- `description` - Descrição
- `ip_address`, `user_agent` - Dados do cliente
- `level` (info/warning/error) - Nível de log

**Índices:** 4 índices
**Políticas RLS:** 2 políticas
**Trigger:** auto-inserção através da aplicação

#### `user_consents` (Consentimentos LGPD)
**Propósito:** Gerencia consentimentos de dados (conformidade LGPD)

**Colunas principais:**
- `id` (UUID) - Chave primária
- `employee_id` (UUID) - Funcionário
- `consent_type` - Tipo de consentimento
- `consent_version` - Versão dos termos
- `granted` - Concedido ou não
- `ip_address`, `user_agent` - Dados da concessão
- `granted_at` - Data de concessão
- `revoked_at` - Data de revogação (se aplicável)

**Índices:** 2 índices
**Políticas RLS:** 3 políticas

---

## 🔧 Funções do Banco de Dados

### 1. `update_updated_at_column()`
**Propósito:** Atualiza automaticamente o campo updated_at
**Trigger em:** 9 tabelas

### 2. `calculate_work_hours(employee_id, work_date)`
**Propósito:** Calcula horas trabalhadas, pausas, extras e devidas
**Retorna:** Record com total_hours, break_hours, net_hours, extra_hours, owed_hours

### 3. `generate_nsr(employee_id, work_date)`
**Propósito:** Gera Número Sequencial de Registro (conformidade MTE)
**Retorna:** INTEGER no formato YYYYMMDDNNN

### 4. `check_geofence(latitude, longitude)`
**Propósito:** Verifica se coordenadas estão dentro de cerca virtual ativa
**Usa:** Fórmula de Haversine para cálculo de distância
**Retorna:** BOOLEAN

### 5. `update_room_last_message()`
**Propósito:** Atualiza timestamp de última mensagem em salas de chat
**Trigger em:** chat_messages (INSERT)

### 6. `auto_consolidate_timesheet()`
**Propósito:** Consolida automaticamente registros de ponto
**Trigger em:** time_punches (INSERT/UPDATE)
**Ações:**
- Calcula horas trabalhadas
- Gera NSR
- Cria/atualiza registro em timesheet_consolidated

---

## 🔐 Segurança Implementada

### Row Level Security (RLS)

**Total de Políticas:** 60+ políticas ativas

**Padrões de Políticas:**

1. **Admins:** Acesso total a todas as tabelas
2. **Gestores:** Acesso aos dados de sua equipe (hierarquia)
3. **Funcionários:** Acesso apenas aos próprios dados
4. **Sistema:** Políticas especiais para operações automáticas

### Validações (Check Constraints)

Total de 15+ constraints de validação:
- Tipos de registro de ponto válidos
- Métodos de autenticação válidos
- Roles válidos (admin/gestor/funcionario)
- Status válidos em workflows
- Tipos de mensagens válidos
- E mais...

---

## 📊 Relacionamentos (Foreign Keys)

**Total:** 25+ relacionamentos

**Principais:**
- employees → employees (manager_id) - Hierarquia
- time_punches → employees - Registros de ponto
- chat_messages → chat_rooms - Mensagens
- chat_room_members → employees - Membros
- justifications → employees - Justificativas
- warnings → employees - Advertências
- notifications → employees - Notificações
- audit_logs → employees - Auditoria
- E mais...

---

## 🎯 Funcionalidades Suportadas

### ✅ Registro de Ponto
- 4 métodos: código, QR Code, facial, biometria
- Geolocalização com validação de cerca virtual
- Consolidação automática diária
- Cálculo automático de horas extras/devidas

### ✅ Gestão de Pessoas
- Hierarquia organizacional (gestores)
- Controle de jornada personalizado
- Banco de horas individual
- Autenticação 2FA

### ✅ Biometria
- Templates faciais e digitais
- Múltiplos templates por funcionário
- Score de qualidade
- Controle de uso

### ✅ Justificativas
- Workflow de aprovação
- Anexos de documentos
- Histórico completo

### ✅ Advertências
- Conformidade CLT
- Assinaturas digitais
- Testemunhas
- Recusa documentada
- PDF automático

### ✅ Chat Corporativo
- Conversas privadas e grupos
- Reações a mensagens
- Status de presença
- Histórico completo

### ✅ Notificações
- In-app
- Push notifications (Web Push)
- Múltiplos dispositivos

### ✅ Relatórios
- Geração assíncrona
- Múltiplos formatos (PDF, Excel, CSV)
- Fila de processamento

### ✅ LGPD
- Consentimentos versionados
- Exportação de dados
- Auditoria de 10 anos
- Direito ao esquecimento preparado

---

## 📈 Performance

### Índices Criados: 60+

**Estratégia:**
- Índices em chaves estrangeiras
- Índices compostos para queries frequentes
- Índices em campos de busca/filtro
- Índices parciais onde apropriado

**Principais:**
- Índices de data em registros temporais
- Índices de status em workflows
- Índices de relacionamentos
- Índices para ordenação

---

## 🎯 Conformidade Legal

### ✅ Portaria MTE 671/2021
- NSR (Número Sequencial de Registro)
- Hash SHA-256 para validação
- Registro de geolocalização
- Múltiplos métodos de autenticação

### ✅ CLT Art. 74
- Registro de jornada completo
- Controle de horas extras
- Sistema de advertências

### ✅ LGPD Lei 13.709/2018
- Consentimentos explícitos
- Auditoria completa
- Exportação de dados
- Minimização de dados
- Segurança (RLS + criptografia)

---

## 🔄 Próximos Passos Recomendados

1. **Índices Adicionais:** Monitorar queries e adicionar índices conforme necessário
2. **Particionamento:** Considerar particionamento de tabelas grandes (time_punches, audit_logs)
3. **Arquivamento:** Implementar rotina de arquivamento de dados antigos
4. **Backup:** Configurar backup automático incremental
5. **Monitoring:** Implementar alertas de performance

---

**Sistema de Ponto Eletrônico Brasileiro**
**Banco de Dados PostgreSQL (Supabase)**
**19 Tabelas | 60+ Índices | 60+ Políticas RLS | 12 Triggers | 5 Funções**

✅ **ESTRUTURA COMPLETA E OTIMIZADA**
