-- ============================================================================
-- SISTEMA DE PONTO ELETRÔNICO - DATABASE SCHEMA COMPLETO
-- ============================================================================
-- Versão: Fase 17+ Híbrida Completa
-- Data: 2024-11-16
-- Conformidade: Portaria MTE 671/2021, CLT Art. 74, LGPD Lei 13.709/2018
-- ============================================================================
--
-- INSTRUÇÕES DE USO:
--
-- 1. Crie o banco de dados:
--    CREATE DATABASE ponto_eletronico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--
-- 2. Importe este arquivo:
--    mysql -u usuario -p ponto_eletronico < database.sql
--
-- 3. Ou via phpMyAdmin: Importar > Selecionar arquivo
--
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================================
-- TABELA: employees (Funcionários)
-- ============================================================================

DROP TABLE IF EXISTS `employees`;

CREATE TABLE `employees` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Nome completo do funcionário',
  `email` varchar(255) NOT NULL COMMENT 'E-mail único para login',
  `password` varchar(255) NOT NULL COMMENT 'Senha hash Argon2id',
  `cpf` varchar(14) NOT NULL COMMENT 'CPF formatado (XXX.XXX.XXX-XX)',
  `unique_code` varchar(10) NOT NULL COMMENT 'Código único para registro de ponto',
  `role` enum('admin','gestor','funcionario') NOT NULL DEFAULT 'funcionario' COMMENT 'Perfil de acesso',
  `department` varchar(100) DEFAULT NULL COMMENT 'Departamento',
  `position` varchar(100) DEFAULT NULL COMMENT 'Cargo',
  `expected_hours_daily` decimal(4,2) NOT NULL DEFAULT 8.00 COMMENT 'Jornada diária esperada em horas',
  `work_schedule_start` time DEFAULT NULL COMMENT 'Horário de início do expediente',
  `work_schedule_end` time DEFAULT NULL COMMENT 'Horário de fim do expediente',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Funcionário ativo no sistema',
  `extra_hours_balance` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Saldo de horas extras (positivo)',
  `owed_hours_balance` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Saldo de horas devidas (negativo)',
  `manager_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'ID do gestor responsável',
  `two_factor_secret` varchar(32) DEFAULT NULL COMMENT 'Secret para TOTP 2FA',
  `two_factor_enabled` tinyint(1) NOT NULL DEFAULT 0 COMMENT '2FA habilitado',
  `two_factor_backup_codes` text DEFAULT NULL COMMENT 'Códigos de backup para 2FA (JSON)',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL COMMENT 'Soft delete timestamp',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `cpf` (`cpf`),
  UNIQUE KEY `unique_code` (`unique_code`),
  KEY `idx_role` (`role`),
  KEY `idx_department` (`department`),
  KEY `idx_active` (`active`),
  KEY `idx_manager_id` (`manager_id`),
  CONSTRAINT `fk_employees_manager` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Funcionários do sistema';

-- ============================================================================
-- TABELA: time_punches (Registros de Ponto)
-- ============================================================================

DROP TABLE IF EXISTS `time_punches`;

CREATE TABLE `time_punches` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) UNSIGNED NOT NULL COMMENT 'Funcionário',
  `punch_time` datetime NOT NULL COMMENT 'Data/hora do registro',
  `punch_type` enum('entrada','saida','intervalo_inicio','intervalo_fim') NOT NULL COMMENT 'Tipo de registro',
  `method` enum('biometria','facial','codigo','manual','webservice') NOT NULL DEFAULT 'codigo' COMMENT 'Método de registro',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP do dispositivo',
  `geolocation` varchar(100) DEFAULT NULL COMMENT 'Lat,Long do registro',
  `notes` text DEFAULT NULL COMMENT 'Observações',
  `signature` text DEFAULT NULL COMMENT 'Assinatura digital ICP-Brasil',
  `hash` varchar(64) DEFAULT NULL COMMENT 'Hash SHA-256 para integridade',
  `device_fingerprint` varchar(255) DEFAULT NULL COMMENT 'Identificador do dispositivo',
  `biometric_score` decimal(5,2) DEFAULT NULL COMMENT 'Score de confiança biométrica (0-100)',
  `facial_confidence` decimal(5,2) DEFAULT NULL COMMENT 'Confiança do reconhecimento facial',
  `is_anomaly` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Marcado como anomalia pelo ML',
  `anomaly_reason` varchar(255) DEFAULT NULL COMMENT 'Razão da anomalia',
  `validated_at` datetime DEFAULT NULL COMMENT 'Data/hora da validação',
  `validated_by` int(11) UNSIGNED DEFAULT NULL COMMENT 'Quem validou',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_punch_time` (`punch_time`),
  KEY `idx_punch_type` (`punch_type`),
  KEY `idx_method` (`method`),
  KEY `idx_hash` (`hash`),
  KEY `idx_employee_date` (`employee_id`, `punch_time`),
  CONSTRAINT `fk_time_punches_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_time_punches_validator` FOREIGN KEY (`validated_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registros de ponto eletrônico';

-- ============================================================================
-- TABELA: biometric_templates (Templates Biométricos)
-- ============================================================================

DROP TABLE IF EXISTS `biometric_templates`;

CREATE TABLE `biometric_templates` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) UNSIGNED NOT NULL COMMENT 'Funcionário',
  `finger` enum('polegar_direito','indicador_direito','medio_direito','anelar_direito','mindinho_direito','polegar_esquerdo','indicador_esquerdo','medio_esquerdo','anelar_esquerdo','mindinho_esquerdo') NOT NULL COMMENT 'Dedo cadastrado',
  `template_data` mediumblob NOT NULL COMMENT 'Dados do template biométrico criptografado',
  `template_format` varchar(50) NOT NULL DEFAULT 'ISO_19794_2' COMMENT 'Formato do template',
  `quality_score` decimal(5,2) DEFAULT NULL COMMENT 'Qualidade da captura (0-100)',
  `encryption_version` int(11) NOT NULL DEFAULT 1 COMMENT 'Versão da criptografia',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Template ativo',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_finger` (`finger`),
  KEY `idx_active` (`is_active`),
  CONSTRAINT `fk_biometric_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Templates biométricos (digitais)';

-- ============================================================================
-- TABELA: justifications (Justificativas)
-- ============================================================================

DROP TABLE IF EXISTS `justifications`;

CREATE TABLE `justifications` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) UNSIGNED NOT NULL COMMENT 'Funcionário',
  `date` date NOT NULL COMMENT 'Data da justificativa',
  `type` enum('falta','atraso','saida_antecipada','ausencia_parcial','outros') NOT NULL COMMENT 'Tipo',
  `reason` text NOT NULL COMMENT 'Motivo da justificativa',
  `attachment` varchar(255) DEFAULT NULL COMMENT 'Caminho do anexo (atestado, etc)',
  `status` enum('pendente','aprovada','rejeitada') NOT NULL DEFAULT 'pendente' COMMENT 'Status',
  `reviewed_by` int(11) UNSIGNED DEFAULT NULL COMMENT 'Quem avaliou',
  `reviewed_at` datetime DEFAULT NULL COMMENT 'Data da avaliação',
  `review_notes` text DEFAULT NULL COMMENT 'Observações da avaliação',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_date` (`date`),
  KEY `idx_status` (`status`),
  KEY `idx_type` (`type`),
  CONSTRAINT `fk_justifications_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_justifications_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Justificativas de ausências';

-- ============================================================================
-- TABELA: geofences (Cercas Virtuais)
-- ============================================================================

DROP TABLE IF EXISTS `geofences`;

CREATE TABLE `geofences` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT 'Nome da cerca',
  `latitude` decimal(10,8) NOT NULL COMMENT 'Latitude central',
  `longitude` decimal(11,8) NOT NULL COMMENT 'Longitude central',
  `radius` int(11) NOT NULL COMMENT 'Raio em metros',
  `active` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Cerca ativa',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Cercas virtuais para validação de localização';

-- ============================================================================
-- TABELA: warnings (Advertências)
-- ============================================================================

DROP TABLE IF EXISTS `warnings`;

CREATE TABLE `warnings` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) UNSIGNED NOT NULL COMMENT 'Funcionário advertido',
  `type` enum('verbal','escrita','suspensao','demissao_justa_causa') NOT NULL COMMENT 'Tipo de advertência',
  `reason` text NOT NULL COMMENT 'Motivo da advertência',
  `description` text DEFAULT NULL COMMENT 'Descrição detalhada',
  `date` date NOT NULL COMMENT 'Data da advertência',
  `issued_by` int(11) UNSIGNED NOT NULL COMMENT 'Quem emitiu',
  `acknowledged` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Funcionário ciente',
  `acknowledged_at` datetime DEFAULT NULL COMMENT 'Data da ciência',
  `attachment` varchar(255) DEFAULT NULL COMMENT 'Documento anexo',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_type` (`type`),
  KEY `idx_date` (`date`),
  CONSTRAINT `fk_warnings_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_warnings_issuer` FOREIGN KEY (`issued_by`) REFERENCES `employees` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Advertências e ocorrências disciplinares';

-- ============================================================================
-- TABELA: user_consents (Consentimentos LGPD)
-- ============================================================================

DROP TABLE IF EXISTS `user_consents`;

CREATE TABLE `user_consents` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) UNSIGNED NOT NULL COMMENT 'Funcionário',
  `consent_type` varchar(50) NOT NULL COMMENT 'Tipo de consentimento',
  `purpose` text NOT NULL COMMENT 'Finalidade do tratamento',
  `data_types` text NOT NULL COMMENT 'Tipos de dados (JSON)',
  `granted` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Consentimento concedido',
  `granted_at` datetime DEFAULT NULL COMMENT 'Data da concessão',
  `revoked_at` datetime DEFAULT NULL COMMENT 'Data da revogação',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP do consentimento',
  `user_agent` varchar(255) DEFAULT NULL COMMENT 'User agent',
  `version` int(11) NOT NULL DEFAULT 1 COMMENT 'Versão do termo',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_consent_type` (`consent_type`),
  KEY `idx_granted` (`granted`),
  CONSTRAINT `fk_consents_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Consentimentos LGPD';

-- ============================================================================
-- TABELA: audit_logs (Logs de Auditoria)
-- ============================================================================

DROP TABLE IF EXISTS `audit_logs`;

CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'Usuário responsável',
  `action` varchar(100) NOT NULL COMMENT 'Ação realizada',
  `entity_type` varchar(100) DEFAULT NULL COMMENT 'Tipo de entidade',
  `entity_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'ID da entidade',
  `old_values` text DEFAULT NULL COMMENT 'Valores antigos (JSON)',
  `new_values` text DEFAULT NULL COMMENT 'Valores novos (JSON)',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP',
  `user_agent` varchar(255) DEFAULT NULL COMMENT 'User agent',
  `url` varchar(255) DEFAULT NULL COMMENT 'URL da requisição',
  `method` varchar(10) DEFAULT NULL COMMENT 'Método HTTP',
  `description` text DEFAULT NULL COMMENT 'Descrição do evento',
  `level` varchar(20) DEFAULT 'info' COMMENT 'Nível do log (info, warning, error)',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_entity` (`entity_type`, `entity_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Logs de auditoria do sistema';

-- ============================================================================
-- TABELA: notifications (Notificações)
-- ============================================================================

DROP TABLE IF EXISTS `notifications`;

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) UNSIGNED NOT NULL COMMENT 'Destinatário',
  `type` varchar(50) NOT NULL COMMENT 'Tipo de notificação',
  `title` varchar(255) NOT NULL COMMENT 'Título',
  `message` text NOT NULL COMMENT 'Mensagem',
  `data` text DEFAULT NULL COMMENT 'Dados adicionais (JSON)',
  `read` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Lida',
  `read_at` datetime DEFAULT NULL COMMENT 'Data da leitura',
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal' COMMENT 'Prioridade',
  `channel` enum('system','email','push','sms') NOT NULL DEFAULT 'system' COMMENT 'Canal',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_read` (`read`),
  KEY `idx_type` (`type`),
  KEY `idx_priority` (`priority`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_notifications_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notificações do sistema';

-- ============================================================================
-- TABELA: settings (Configurações)
-- ============================================================================

DROP TABLE IF EXISTS `settings`;

CREATE TABLE `settings` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL COMMENT 'Chave da configuração',
  `value` text DEFAULT NULL COMMENT 'Valor',
  `type` enum('string','number','boolean','json','encrypted') NOT NULL DEFAULT 'string' COMMENT 'Tipo do valor',
  `group` varchar(50) DEFAULT 'general' COMMENT 'Grupo da configuração',
  `description` varchar(255) DEFAULT NULL COMMENT 'Descrição',
  `is_public` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Acessível publicamente',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`),
  KEY `idx_group` (`group`),
  KEY `idx_is_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configurações do sistema';

-- ============================================================================
-- TABELA: timesheet_consolidated (Espelho de Ponto)
-- ============================================================================

DROP TABLE IF EXISTS `timesheet_consolidated`;

CREATE TABLE `timesheet_consolidated` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) UNSIGNED NOT NULL COMMENT 'Funcionário',
  `date` date NOT NULL COMMENT 'Data',
  `entry_time` time DEFAULT NULL COMMENT 'Entrada',
  `break_start` time DEFAULT NULL COMMENT 'Início intervalo',
  `break_end` time DEFAULT NULL COMMENT 'Fim intervalo',
  `exit_time` time DEFAULT NULL COMMENT 'Saída',
  `total_hours` decimal(5,2) DEFAULT NULL COMMENT 'Total de horas trabalhadas',
  `extra_hours` decimal(5,2) DEFAULT NULL COMMENT 'Horas extras',
  `owed_hours` decimal(5,2) DEFAULT NULL COMMENT 'Horas devidas',
  `late_arrival` int(11) DEFAULT NULL COMMENT 'Minutos de atraso',
  `early_departure` int(11) DEFAULT NULL COMMENT 'Minutos de saída antecipada',
  `status` enum('normal','falta','justificada','feriado','folga','ferias') NOT NULL DEFAULT 'normal',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_date` (`employee_id`, `date`),
  KEY `idx_date` (`date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_timesheet_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Espelho de ponto consolidado';

-- ============================================================================
-- TABELA: data_exports (Exportações de Dados)
-- ============================================================================

DROP TABLE IF EXISTS `data_exports`;

CREATE TABLE `data_exports` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'Funcionário (NULL = geral)',
  `export_type` varchar(50) NOT NULL COMMENT 'Tipo de exportação',
  `format` enum('pdf','csv','xlsx','json','xml') NOT NULL DEFAULT 'pdf',
  `filename` varchar(255) NOT NULL COMMENT 'Nome do arquivo',
  `filepath` varchar(500) NOT NULL COMMENT 'Caminho do arquivo',
  `status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `error_message` text DEFAULT NULL,
  `requested_by` int(11) UNSIGNED NOT NULL COMMENT 'Quem solicitou',
  `parameters` text DEFAULT NULL COMMENT 'Parâmetros (JSON)',
  `file_size` bigint(20) DEFAULT NULL COMMENT 'Tamanho em bytes',
  `expires_at` datetime DEFAULT NULL COMMENT 'Data de expiração',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_status` (`status`),
  KEY `idx_requested_by` (`requested_by`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_exports_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_exports_requester` FOREIGN KEY (`requested_by`) REFERENCES `employees` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Exportações de dados e relatórios';

-- ============================================================================
-- TABELA: push_subscriptions (WebPush)
-- ============================================================================

DROP TABLE IF EXISTS `push_subscriptions`;

CREATE TABLE `push_subscriptions` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) UNSIGNED NOT NULL,
  `endpoint` text NOT NULL,
  `public_key` varchar(255) NOT NULL,
  `auth_token` varchar(255) NOT NULL,
  `content_encoding` varchar(20) DEFAULT 'aes128gcm',
  `user_agent` varchar(255) DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_employee_id` (`employee_id`),
  CONSTRAINT `fk_push_subscriptions_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Assinaturas WebPush';

-- ============================================================================
-- TABELA: chat_messages (Mensagens de Chat)
-- ============================================================================

DROP TABLE IF EXISTS `chat_messages`;

CREATE TABLE `chat_messages` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) UNSIGNED NOT NULL COMMENT 'Remetente',
  `receiver_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'Destinatário (NULL = broadcast)',
  `message` text NOT NULL COMMENT 'Mensagem',
  `type` enum('text','file','system') NOT NULL DEFAULT 'text',
  `attachment` varchar(255) DEFAULT NULL,
  `read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sender_id` (`sender_id`),
  KEY `idx_receiver_id` (`receiver_id`),
  KEY `idx_read` (`read`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_chat_sender` FOREIGN KEY (`sender_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_chat_receiver` FOREIGN KEY (`receiver_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Mensagens do chat interno';

-- ============================================================================
-- TABELA: chat_rooms (Salas de Chat)
-- ============================================================================

DROP TABLE IF EXISTS `chat_rooms`;

CREATE TABLE `chat_rooms` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('public','private','group') NOT NULL DEFAULT 'public',
  `created_by` int(11) UNSIGNED NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  CONSTRAINT `fk_chat_rooms_creator` FOREIGN KEY (`created_by`) REFERENCES `employees` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Salas de chat';

-- ============================================================================
-- TABELA: report_queue (Fila de Relatórios)
-- ============================================================================

DROP TABLE IF EXISTS `report_queue`;

CREATE TABLE `report_queue` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL,
  `parameters` text DEFAULT NULL COMMENT 'JSON',
  `status` enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
  `priority` int(11) NOT NULL DEFAULT 5,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `max_attempts` int(11) NOT NULL DEFAULT 3,
  `error_message` text DEFAULT NULL,
  `result_path` varchar(500) DEFAULT NULL,
  `requested_by` int(11) UNSIGNED NOT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_requested_by` (`requested_by`),
  CONSTRAINT `fk_report_queue_requester` FOREIGN KEY (`requested_by`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Fila de geração de relatórios assíncronos';

-- ============================================================================
-- TABELA: oauth_tokens (OAuth 2.0 Tokens - Fase 17+)
-- ============================================================================

DROP TABLE IF EXISTS `oauth_tokens`;

CREATE TABLE `oauth_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) UNSIGNED NOT NULL,
  `access_token` varchar(255) NOT NULL,
  `refresh_token` varchar(255) DEFAULT NULL,
  `token_type` varchar(20) NOT NULL DEFAULT 'Bearer',
  `scope` varchar(255) DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `revoked` tinyint(1) NOT NULL DEFAULT 0,
  `revoked_at` datetime DEFAULT NULL,
  `client_id` varchar(100) DEFAULT NULL COMMENT 'ID do cliente OAuth',
  `device_info` text DEFAULT NULL COMMENT 'Informações do dispositivo (JSON)',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `access_token` (`access_token`),
  UNIQUE KEY `refresh_token` (`refresh_token`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_expires_at` (`expires_at`),
  KEY `idx_revoked` (`revoked`),
  CONSTRAINT `fk_oauth_tokens_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='OAuth 2.0 Tokens para API móvel';

-- ============================================================================
-- TABELA: push_notification_tokens (FCM Tokens - Fase 17+)
-- ============================================================================

DROP TABLE IF EXISTS `push_notification_tokens`;

CREATE TABLE `push_notification_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) UNSIGNED NOT NULL,
  `token` varchar(500) NOT NULL COMMENT 'FCM Token',
  `platform` enum('android','ios','web') NOT NULL,
  `device_info` text DEFAULT NULL COMMENT 'JSON com info do dispositivo',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `idx_employee_id` (`employee_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_platform` (`platform`),
  CONSTRAINT `fk_push_tokens_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tokens FCM para push notifications';

-- ============================================================================
-- TABELA: rate_limits (Rate Limiting - Fase 17+)
-- ============================================================================

DROP TABLE IF EXISTS `rate_limits`;

CREATE TABLE `rate_limits` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `identifier` varchar(255) NOT NULL COMMENT 'IP, user_id, ou chave única',
  `action` varchar(100) NOT NULL COMMENT 'Ação sendo limitada',
  `hits` int(11) NOT NULL DEFAULT 1,
  `reset_at` datetime NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `identifier_action` (`identifier`, `action`),
  KEY `idx_reset_at` (`reset_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Controle de rate limiting';

-- ============================================================================
-- TABELA: migrations (CodeIgniter Migrations)
-- ============================================================================

DROP TABLE IF EXISTS `migrations`;

CREATE TABLE `migrations` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Controle de migrations do CodeIgniter';

-- ============================================================================
-- VIEWS (Views para Relatórios)
-- ============================================================================

-- View: Employee Performance Overview
DROP VIEW IF EXISTS `vw_employee_performance`;

CREATE VIEW `vw_employee_performance` AS
SELECT
    e.id,
    e.name,
    e.email,
    e.department,
    e.position,
    e.extra_hours_balance,
    e.owed_hours_balance,
    COUNT(DISTINCT tp.id) as total_punches,
    COUNT(DISTINCT j.id) as total_justifications,
    COUNT(DISTINCT w.id) as total_warnings,
    AVG(tc.total_hours) as avg_daily_hours
FROM employees e
LEFT JOIN time_punches tp ON e.id = tp.employee_id
LEFT JOIN justifications j ON e.id = j.employee_id
LEFT JOIN warnings w ON e.id = w.employee_id
LEFT JOIN timesheet_consolidated tc ON e.id = tc.employee_id
WHERE e.active = 1
GROUP BY e.id;

-- View: Daily Attendance Summary
DROP VIEW IF EXISTS `vw_daily_attendance`;

CREATE VIEW `vw_daily_attendance` AS
SELECT
    DATE(punch_time) as date,
    COUNT(DISTINCT employee_id) as total_employees,
    COUNT(CASE WHEN punch_type = 'entrada' THEN 1 END) as total_entries,
    COUNT(CASE WHEN punch_type = 'saida' THEN 1 END) as total_exits,
    COUNT(CASE WHEN method = 'biometria' THEN 1 END) as biometric_count,
    COUNT(CASE WHEN method = 'facial' THEN 1 END) as facial_count,
    COUNT(CASE WHEN is_anomaly = 1 THEN 1 END) as anomaly_count
FROM time_punches
GROUP BY DATE(punch_time);

-- ============================================================================
-- DADOS INICIAIS
-- ============================================================================

-- Inserir configurações padrão
INSERT INTO `settings` (`key`, `value`, `type`, `group`, `description`, `is_public`) VALUES
('company_name', 'Empresa LTDA', 'string', 'company', 'Nome da empresa', 1),
('company_cnpj', '00.000.000/0000-00', 'string', 'company', 'CNPJ da empresa', 1),
('timezone', 'America/Sao_Paulo', 'string', 'general', 'Fuso horário do sistema', 1),
('date_format', 'd/m/Y', 'string', 'general', 'Formato de data', 1),
('time_format', 'H:i', 'string', 'general', 'Formato de hora', 1),
('tolerance_minutes', '10', 'number', 'timesheet', 'Tolerância de atraso em minutos', 0),
('extra_hours_enabled', 'true', 'boolean', 'timesheet', 'Habilitar horas extras', 0),
('max_extra_hours_daily', '2', 'number', 'timesheet', 'Máximo de horas extras por dia', 0),
('require_geolocation', 'false', 'boolean', 'punch', 'Exigir geolocalização no registro', 0),
('biometric_threshold', '70', 'number', 'biometric', 'Score mínimo de biometria (0-100)', 0),
('facial_threshold', '85', 'number', 'biometric', 'Score mínimo de reconhecimento facial (0-100)', 0),
('session_timeout', '7200', 'number', 'security', 'Timeout de sessão em segundos', 0),
('password_min_length', '8', 'number', 'security', 'Tamanho mínimo da senha', 0),
('enable_2fa', 'true', 'boolean', 'security', 'Habilitar autenticação 2FA', 0),
('notification_email', 'admin@empresa.com.br', 'string', 'notifications', 'Email para notificações', 0),
('enable_push_notifications', 'true', 'boolean', 'notifications', 'Habilitar push notifications', 0),
('enable_rate_limiting', 'true', 'boolean', 'security', 'Habilitar rate limiting', 0),
('api_rate_limit', '100', 'number', 'security', 'Limite de requisições por minuto', 0),
('enable_audit_log', 'true', 'boolean', 'security', 'Habilitar logs de auditoria', 0),
('lgpd_dpo_email', 'dpo@empresa.com.br', 'string', 'lgpd', 'Email do DPO (LGPD)', 1);

-- ============================================================================
-- NOTA: Usuário Admin
-- ============================================================================
-- O usuário administrador será criado via instalador web ou seeder
-- Email padrão: admin@example.com
-- Senha padrão: Admin@123 (alterar após primeiro login!)
-- ============================================================================

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- ============================================================================
-- FIM DO ARQUIVO
-- ============================================================================
-- Total de tabelas: 23
-- Total de views: 2
-- Configurações iniciais: 20
--
-- Sistema pronto para uso!
-- ============================================================================
