-- ==================================================================
-- MySQL Performance Optimization Settings
-- ==================================================================
--
-- These settings should be added to /etc/mysql/my.cnf or
-- /etc/mysql/mysql.conf.d/mysqld.cnf
--
-- Restart MySQL after applying: sudo systemctl restart mysql
-- ==================================================================

-- [mysqld]

-- ====================  InnoDB Buffer Pool ====================
-- Set to 50-70% of total RAM for dedicated MySQL server
-- For 4GB RAM: 2-3GB
-- For 8GB RAM: 5-6GB
innodb_buffer_pool_size = 2G

-- Number of buffer pool instances (recommended: 1 per GB, max 64)
innodb_buffer_pool_instances = 2

-- ====================  Connection Settings ====================
-- Maximum number of concurrent connections
max_connections = 200

-- Maximum allowed packet size (for large blobs/text)
max_allowed_packet = 64M

-- Connection timeout
wait_timeout = 600
interactive_timeout = 600

-- ====================  Query Cache ====================
-- DEPRECATED in MySQL 8.0+ (removed completely)
-- query_cache_type = 1
-- query_cache_size = 64M
-- query_cache_limit = 2M

-- ====================  InnoDB Settings ====================
-- Log file size (affects recovery time and write performance)
innodb_log_file_size = 256M
innodb_log_buffer_size = 16M

-- Flush method (O_DIRECT recommended for dedicated server)
innodb_flush_method = O_DIRECT

-- Flush log at each transaction commit (1 = safest, slower)
innodb_flush_log_at_trx_commit = 1

-- File per table (recommended)
innodb_file_per_table = 1

-- ====================  Slow Query Log ====================
-- Enable slow query logging
slow_query_log = 1
slow_query_log_file = /var/log/mysql/mysql-slow.log
long_query_time = 1

-- Log queries not using indexes
log_queries_not_using_indexes = 1

-- Throttle slow query logging
log_throttle_queries_not_using_indexes = 10

-- ====================  General Log (for debugging only) ====================
-- WARNING: High overhead, disable in production
-- general_log = 0
-- general_log_file = /var/log/mysql/mysql.log

-- ====================  Binary Log ====================
-- Enable for replication or point-in-time recovery
server-id = 1
log_bin = /var/log/mysql/mysql-bin.log
expire_logs_days = 7
max_binlog_size = 100M

-- ====================  Table Cache ====================
table_open_cache = 4000
table_definition_cache = 2000

-- ====================  Thread Settings ====================
thread_cache_size = 50
thread_stack = 256K

-- ====================  Sort and Join Buffers ====================
sort_buffer_size = 4M
read_buffer_size = 2M
read_rnd_buffer_size = 4M
join_buffer_size = 4M

-- ====================  Temporary Tables ====================
tmp_table_size = 64M
max_heap_table_size = 64M

-- ====================  MyISAM (if used) ====================
key_buffer_size = 32M
myisam_sort_buffer_size = 128M

-- ==================================================================
-- Apply settings and verify
-- ==================================================================

-- Check current settings:
-- SHOW VARIABLES LIKE 'innodb_buffer_pool_size';
-- SHOW VARIABLES LIKE 'max_connections';
-- SHOW VARIABLES LIKE 'slow_query_log';

-- Check InnoDB status:
-- SHOW ENGINE INNODB STATUS\G

-- Show processlist:
-- SHOW FULL PROCESSLIST;

-- Analyze slow queries:
-- mysqldumpslow -s t -t 10 /var/log/mysql/mysql-slow.log

-- ==================================================================
-- Performance Tuning Commands
-- ==================================================================

-- Analyze tables (updates index statistics)
-- ANALYZE TABLE time_punches, employees, audit_logs;

-- Optimize tables (defragments and rebuilds indexes)
-- OPTIMIZE TABLE time_punches;

-- Check table for errors
-- CHECK TABLE time_punches;

-- Repair table (if errors found)
-- REPAIR TABLE time_punches;

-- ==================================================================
-- Monitoring Queries
-- ==================================================================

-- Buffer pool hit ratio (should be >99%)
-- SELECT
--     (1 - (Innodb_buffer_pool_reads / Innodb_buffer_pool_read_requests)) * 100
--     AS buffer_pool_hit_ratio
-- FROM
--     (SELECT
--         VARIABLE_VALUE AS Innodb_buffer_pool_read_requests
--     FROM information_schema.GLOBAL_STATUS
--     WHERE VARIABLE_NAME = 'Innodb_buffer_pool_read_requests') AS requests,
--     (SELECT
--         VARIABLE_VALUE AS Innodb_buffer_pool_reads
--     FROM information_schema.GLOBAL_STATUS
--     WHERE VARIABLE_NAME = 'Innodb_buffer_pool_reads') AS reads;

-- Connection usage
-- SELECT
--     VARIABLE_VALUE AS current_connections,
--     @@max_connections AS max_connections,
--     ROUND((VARIABLE_VALUE / @@max_connections) * 100, 2) AS usage_percent
-- FROM information_schema.GLOBAL_STATUS
-- WHERE VARIABLE_NAME = 'Threads_connected';

-- Table sizes
-- SELECT
--     TABLE_NAME,
--     ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS 'Size (MB)',
--     TABLE_ROWS,
--     ROUND(INDEX_LENGTH / 1024 / 1024, 2) AS 'Index Size (MB)'
-- FROM information_schema.TABLES
-- WHERE TABLE_SCHEMA = DATABASE()
-- ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC;

-- ==================================================================
-- Index Usage Statistics
-- ==================================================================

-- Show index usage (requires MySQL 5.6+)
-- SELECT
--     OBJECT_SCHEMA AS database_name,
--     OBJECT_NAME AS table_name,
--     INDEX_NAME,
--     COUNT_READ,
--     COUNT_WRITE,
--     COUNT_FETCH,
--     COUNT_INSERT,
--     COUNT_UPDATE,
--     COUNT_DELETE
-- FROM performance_schema.table_io_waits_summary_by_index_usage
-- WHERE OBJECT_SCHEMA = DATABASE()
-- ORDER BY COUNT_READ DESC;

-- Unused indexes (never read)
-- SELECT
--     OBJECT_SCHEMA AS database_name,
--     OBJECT_NAME AS table_name,
--     INDEX_NAME
-- FROM performance_schema.table_io_waits_summary_by_index_usage
-- WHERE OBJECT_SCHEMA = DATABASE()
-- AND INDEX_NAME IS NOT NULL
-- AND INDEX_NAME != 'PRIMARY'
-- AND COUNT_STAR = 0
-- ORDER BY OBJECT_SCHEMA, OBJECT_NAME;
