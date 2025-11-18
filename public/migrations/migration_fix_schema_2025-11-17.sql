-- ============================================================================
-- MIGRATION SCRIPT: Fix Schema Bugs
-- Data: 2025-11-17
-- Versão: 1.0
-- ============================================================================
--
-- DESCRIÇÃO:
-- Este script corrige incompatibilidades entre os Models do CodeIgniter e o
-- schema do banco de dados que foram descobertas durante debugging em PHP 8.4
--
-- BUGS CORRIGIDOS:
-- 1. employees: Falta coluna `deleted_at` para soft deletes
-- 2. audit_logs: Nomes de colunas incompatíveis com AuditLogModel
--
-- IMPORTANTE:
-- - Execute este script em instalações EXISTENTES que já têm o database.sql antigo
-- - Para instalações NOVAS, use o public/database.sql atualizado
-- - Este script é IDEMPOTENTE (pode ser executado múltiplas vezes com segurança)
--
-- USO:
-- mysql -u usuario -p nome_banco < migration_fix_schema_2025-11-17.sql
--
-- ============================================================================

USE `supportson_suppPONTO`; -- Altere para o nome do seu banco

-- ============================================================================
-- FIX 1: employees - Adicionar coluna deleted_at para soft deletes
-- ============================================================================

-- Verifica se a coluna já existe antes de adicionar
SET @dbname = DATABASE();
SET @tablename = 'employees';
SET @columnname = 'deleted_at';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT \'Column deleted_at already exists in employees\' AS result;',
  'ALTER TABLE employees ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL COMMENT \'Soft delete timestamp\' AFTER updated_at;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ============================================================================
-- FIX 2: audit_logs - Renomear colunas e adicionar novas colunas
-- ============================================================================

-- 2.1: Renomear `event` para `action`
SET @columnname = 'event';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = 'audit_logs')
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'ALTER TABLE audit_logs CHANGE COLUMN event action VARCHAR(100) NOT NULL COMMENT \'Ação realizada\';',
  'SELECT \'Column event already renamed to action\' AS result;'
));
PREPARE alterIfExists FROM @preparedStatement;
EXECUTE alterIfExists;
DEALLOCATE PREPARE alterIfExists;

-- 2.2: Renomear `auditable_type` para `entity_type`
SET @columnname = 'auditable_type';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = 'audit_logs')
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'ALTER TABLE audit_logs CHANGE COLUMN auditable_type entity_type VARCHAR(100) NULL COMMENT \'Tipo de entidade\';',
  'SELECT \'Column auditable_type already renamed to entity_type\' AS result;'
));
PREPARE alterIfExists FROM @preparedStatement;
EXECUTE alterIfExists;
DEALLOCATE PREPARE alterIfExists;

-- 2.3: Renomear `auditable_id` para `entity_id`
SET @columnname = 'auditable_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = 'audit_logs')
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'ALTER TABLE audit_logs CHANGE COLUMN auditable_id entity_id INT(11) UNSIGNED NULL COMMENT \'ID da entidade\';',
  'SELECT \'Column auditable_id already renamed to entity_id\' AS result;'
));
PREPARE alterIfExists FROM @preparedStatement;
EXECUTE alterIfExists;
DEALLOCATE PREPARE alterIfExists;

-- 2.4: Adicionar coluna `url`
SET @tablename = 'audit_logs';
SET @columnname = 'url';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT \'Column url already exists in audit_logs\' AS result;',
  'ALTER TABLE audit_logs ADD COLUMN url VARCHAR(255) NULL COMMENT \'URL da requisição\' AFTER user_agent;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 2.5: Adicionar coluna `method`
SET @columnname = 'method';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT \'Column method already exists in audit_logs\' AS result;',
  'ALTER TABLE audit_logs ADD COLUMN method VARCHAR(10) NULL COMMENT \'Método HTTP\' AFTER url;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 2.6: Adicionar coluna `description`
SET @columnname = 'description';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT \'Column description already exists in audit_logs\' AS result;',
  'ALTER TABLE audit_logs ADD COLUMN description TEXT NULL COMMENT \'Descrição do evento\' AFTER method;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 2.7: Adicionar coluna `level`
SET @columnname = 'level';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT \'Column level already exists in audit_logs\' AS result;',
  'ALTER TABLE audit_logs ADD COLUMN level VARCHAR(20) DEFAULT \'info\' COMMENT \'Nível do log (info, warning, error)\' AFTER description;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 2.8: Adicionar coluna `updated_at`
SET @columnname = 'updated_at';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT \'Column updated_at already exists in audit_logs\' AS result;',
  'ALTER TABLE audit_logs ADD COLUMN updated_at DATETIME NULL AFTER created_at;'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ============================================================================
-- FIX 3: Recriar índices do audit_logs (após renomeação de colunas)
-- ============================================================================

-- Drop old index if exists
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = 'audit_logs')
      AND (INDEX_NAME = 'idx_event')
  ) > 0,
  'DROP INDEX idx_event ON audit_logs;',
  'SELECT \'Index idx_event does not exist\' AS result;'
));
PREPARE dropIfExists FROM @preparedStatement;
EXECUTE dropIfExists;
DEALLOCATE PREPARE dropIfExists;

-- Create new index on `action`
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = 'audit_logs')
      AND (INDEX_NAME = 'idx_action')
  ) > 0,
  'SELECT \'Index idx_action already exists\' AS result;',
  'CREATE INDEX idx_action ON audit_logs(action);'
));
PREPARE createIfNotExists FROM @preparedStatement;
EXECUTE createIfNotExists;
DEALLOCATE PREPARE createIfNotExists;

-- Drop old index idx_auditable
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = 'audit_logs')
      AND (INDEX_NAME = 'idx_auditable')
  ) > 0,
  'DROP INDEX idx_auditable ON audit_logs;',
  'SELECT \'Index idx_auditable does not exist\' AS result;'
));
PREPARE dropIfExists FROM @preparedStatement;
EXECUTE dropIfExists;
DEALLOCATE PREPARE dropIfExists;

-- Create new index on entity_type, entity_id
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = 'audit_logs')
      AND (INDEX_NAME = 'idx_entity')
  ) > 0,
  'SELECT \'Index idx_entity already exists\' AS result;',
  'CREATE INDEX idx_entity ON audit_logs(entity_type, entity_id);'
));
PREPARE createIfNotExists FROM @preparedStatement;
EXECUTE createIfNotExists;
DEALLOCATE PREPARE createIfNotExists;

-- ============================================================================
-- Verificação Final
-- ============================================================================

SELECT 'Migration completed successfully!' AS status;

-- Mostrar estrutura atualizada das tabelas
SHOW CREATE TABLE employees;
SHOW CREATE TABLE audit_logs;
