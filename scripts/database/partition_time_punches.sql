-- ==================================================================
-- Table Partitioning Script for time_punches
-- ==================================================================
--
-- Partitions the time_punches table by year for better performance
-- on large datasets. This script should be run AFTER all data has
-- been migrated or on a fresh installation.
--
-- WARNING: This requires downtime as it recreates the table structure
-- ==================================================================

-- Check current table size
SELECT
    TABLE_NAME,
    ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS 'Size (MB)',
    TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'time_punches';

-- Backup existing data (recommended before partitioning)
-- CREATE TABLE time_punches_backup AS SELECT * FROM time_punches;

-- Drop existing foreign keys (will be recreated after partitioning)
ALTER TABLE time_punches DROP FOREIGN KEY time_punches_employee_id_foreign;

-- Partition table by year using RANGE on punch_time
-- Note: PRIMARY KEY must include the partitioning column
ALTER TABLE time_punches
PARTITION BY RANGE (YEAR(punch_time)) (
    PARTITION p2023 VALUES LESS THAN (2024),
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027),
    PARTITION p2027 VALUES LESS THAN (2028),
    PARTITION p2028 VALUES LESS THAN (2029),
    PARTITION p2029 VALUES LESS THAN (2030),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- Recreate foreign key
ALTER TABLE time_punches
ADD CONSTRAINT time_punches_employee_id_foreign
FOREIGN KEY (employee_id) REFERENCES employees(id)
ON DELETE CASCADE ON UPDATE CASCADE;

-- Verify partitioning
SELECT
    PARTITION_NAME,
    PARTITION_EXPRESSION,
    PARTITION_DESCRIPTION,
    TABLE_ROWS
FROM INFORMATION_SCHEMA.PARTITIONS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'time_punches'
ORDER BY PARTITION_ORDINAL_POSITION;

-- ==================================================================
-- Benefits of partitioning:
-- 1. Faster queries with date range filters (partition pruning)
-- 2. Easier maintenance (drop old partitions instead of DELETE)
-- 3. Better backup/restore (partition-level operations)
-- 4. Improved concurrency (locks at partition level)
-- ==================================================================

-- Example: Add new partition for 2030
-- ALTER TABLE time_punches ADD PARTITION (PARTITION p2030 VALUES LESS THAN (2031));

-- Example: Drop old partition (removes all data from 2023)
-- ALTER TABLE time_punches DROP PARTITION p2023;

-- Example: Query showing partition pruning
-- EXPLAIN PARTITIONS
-- SELECT * FROM time_punches
-- WHERE punch_time >= '2025-01-01' AND punch_time < '2026-01-01';
