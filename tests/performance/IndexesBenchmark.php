<?php

namespace Tests\Performance;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Indexes Performance Benchmark
 *
 * Tests the performance improvement of composite indexes added in migration
 * 2024_01_22_000001_add_performance_indexes.php
 *
 * Run with: php spark test --filter IndexesBenchmark
 */
class IndexesBenchmark extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $db;
    protected $results = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = \Config\Database::connect();
    }

    /**
     * Benchmark: Employee + Date Query (time_punches)
     *
     * Tests: idx_employee_date (employee_id, punch_time DESC)
     */
    public function testEmployeeDateQuery()
    {
        echo "\n\n=== BENCHMARK: Employee + Date Query ===\n";

        $employeeId = 1;
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d');

        $sql = "
            SELECT *
            FROM time_punches
            WHERE employee_id = ?
              AND punch_time BETWEEN ? AND ?
            ORDER BY punch_time DESC
            LIMIT 100
        ";

        // Explain query
        $explain = $this->db->query("EXPLAIN $sql", [$employeeId, $startDate, $endDate])
            ->getResultArray();

        echo "EXPLAIN:\n";
        print_r($explain);

        // Benchmark
        $iterations = 100;
        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->db->query($sql, [$employeeId, $startDate, $endDate])->getResult();
        }

        $end = microtime(true);
        $totalTime = ($end - $start);
        $avgTime = $totalTime / $iterations;

        echo "\nIterations: $iterations\n";
        echo "Total Time: " . number_format($totalTime, 4) . "s\n";
        echo "Average Time: " . number_format($avgTime * 1000, 2) . "ms\n";
        echo "Queries/Second: " . number_format($iterations / $totalTime, 2) . "\n";

        $this->results['employee_date_query'] = [
            'iterations' => $iterations,
            'total_time' => $totalTime,
            'avg_time_ms' => $avgTime * 1000,
            'qps' => $iterations / $totalTime,
            'uses_index' => $this->usesIndex($explain, 'idx_employee_date'),
        ];

        $this->assertLessThan(50, $avgTime * 1000, "Query should be faster than 50ms");
    }

    /**
     * Benchmark: Punch Type + Date Query
     *
     * Tests: idx_type_date (punch_type, punch_time DESC)
     */
    public function testPunchTypeDateQuery()
    {
        echo "\n\n=== BENCHMARK: Punch Type + Date Query ===\n";

        $punchType = 'entrada';
        $startDate = date('Y-m-d', strtotime('-7 days'));

        $sql = "
            SELECT *
            FROM time_punches
            WHERE punch_type = ?
              AND punch_time >= ?
            ORDER BY punch_time DESC
            LIMIT 50
        ";

        // Explain
        $explain = $this->db->query("EXPLAIN $sql", [$punchType, $startDate])
            ->getResultArray();

        echo "EXPLAIN:\n";
        print_r($explain);

        // Benchmark
        $iterations = 100;
        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->db->query($sql, [$punchType, $startDate])->getResult();
        }

        $end = microtime(true);
        $totalTime = ($end - $start);
        $avgTime = $totalTime / $iterations;

        echo "\nIterations: $iterations\n";
        echo "Total Time: " . number_format($totalTime, 4) . "s\n";
        echo "Average Time: " . number_format($avgTime * 1000, 2) . "ms\n";
        echo "Queries/Second: " . number_format($iterations / $totalTime, 2) . "\n";

        $this->results['type_date_query'] = [
            'iterations' => $iterations,
            'total_time' => $totalTime,
            'avg_time_ms' => $avgTime * 1000,
            'qps' => $iterations / $totalTime,
            'uses_index' => $this->usesIndex($explain, 'idx_type_date'),
        ];

        $this->assertLessThan(50, $avgTime * 1000, "Query should be faster than 50ms");
    }

    /**
     * Benchmark: Geofence Query
     *
     * Tests: idx_geofence (within_geofence, punch_time DESC)
     */
    public function testGeofenceQuery()
    {
        echo "\n\n=== BENCHMARK: Geofence Query ===\n";

        $sql = "
            SELECT employee_id, COUNT(*) as count
            FROM time_punches
            WHERE within_geofence = 0
              AND punch_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY employee_id
            ORDER BY count DESC
        ";

        // Explain
        $explain = $this->db->query("EXPLAIN $sql")->getResultArray();

        echo "EXPLAIN:\n";
        print_r($explain);

        // Benchmark
        $iterations = 50;
        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->db->query($sql)->getResult();
        }

        $end = microtime(true);
        $totalTime = ($end - $start);
        $avgTime = $totalTime / $iterations;

        echo "\nIterations: $iterations\n";
        echo "Total Time: " . number_format($totalTime, 4) . "s\n";
        echo "Average Time: " . number_format($avgTime * 1000, 2) . "ms\n";
        echo "Queries/Second: " . number_format($iterations / $totalTime, 2) . "\n";

        $this->results['geofence_query'] = [
            'iterations' => $iterations,
            'total_time' => $totalTime,
            'avg_time_ms' => $avgTime * 1000,
            'qps' => $iterations / $totalTime,
            'uses_index' => $this->usesIndex($explain, 'idx_geofence'),
        ];

        $this->assertLessThan(100, $avgTime * 1000, "Query should be faster than 100ms");
    }

    /**
     * Benchmark: Audit Log User Action Query
     *
     * Tests: idx_user_action_date (user_id, action, created_at DESC)
     */
    public function testAuditLogQuery()
    {
        echo "\n\n=== BENCHMARK: Audit Log Query ===\n";

        $userId = 1;
        $action = 'login';
        $startDate = date('Y-m-d', strtotime('-30 days'));

        $sql = "
            SELECT *
            FROM audit_logs
            WHERE user_id = ?
              AND action = ?
              AND created_at >= ?
            ORDER BY created_at DESC
            LIMIT 50
        ";

        // Explain
        $explain = $this->db->query("EXPLAIN $sql", [$userId, $action, $startDate])
            ->getResultArray();

        echo "EXPLAIN:\n";
        print_r($explain);

        // Benchmark
        $iterations = 100;
        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->db->query($sql, [$userId, $action, $startDate])->getResult();
        }

        $end = microtime(true);
        $totalTime = ($end - $start);
        $avgTime = $totalTime / $iterations;

        echo "\nIterations: $iterations\n";
        echo "Total Time: " . number_format($totalTime, 4) . "s\n";
        echo "Average Time: " . number_format($avgTime * 1000, 2) . "ms\n";
        echo "Queries/Second: " . number_format($iterations / $totalTime, 2) . "\n";

        $this->results['audit_log_query'] = [
            'iterations' => $iterations,
            'total_time' => $totalTime,
            'avg_time_ms' => $avgTime * 1000,
            'qps' => $iterations / $totalTime,
            'uses_index' => $this->usesIndex($explain, 'idx_user_action_date'),
        ];

        $this->assertLessThan(50, $avgTime * 1000, "Query should be faster than 50ms");
    }

    /**
     * Benchmark: Employee Department Active Query
     *
     * Tests: idx_department_active (department, active, name)
     */
    public function testEmployeeDepartmentQuery()
    {
        echo "\n\n=== BENCHMARK: Employee Department Query ===\n";

        $department = 'TI';

        $sql = "
            SELECT *
            FROM employees
            WHERE department = ?
              AND active = 1
            ORDER BY name
        ";

        // Explain
        $explain = $this->db->query("EXPLAIN $sql", [$department])
            ->getResultArray();

        echo "EXPLAIN:\n";
        print_r($explain);

        // Benchmark
        $iterations = 100;
        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $this->db->query($sql, [$department])->getResult();
        }

        $end = microtime(true);
        $totalTime = ($end - $start);
        $avgTime = $totalTime / $iterations;

        echo "\nIterations: $iterations\n";
        echo "Total Time: " . number_format($totalTime, 4) . "s\n";
        echo "Average Time: " . number_format($avgTime * 1000, 2) . "ms\n";
        echo "Queries/Second: " . number_format($iterations / $totalTime, 2) . "\n";

        $this->results['department_query'] = [
            'iterations' => $iterations,
            'total_time' => $totalTime,
            'avg_time_ms' => $avgTime * 1000,
            'qps' => $iterations / $totalTime,
            'uses_index' => $this->usesIndex($explain, 'idx_department_active'),
        ];

        $this->assertLessThan(20, $avgTime * 1000, "Query should be faster than 20ms");
    }

    /**
     * Check if query uses a specific index
     */
    protected function usesIndex(array $explain, string $indexName): bool
    {
        foreach ($explain as $row) {
            if (isset($row['key']) && $row['key'] === $indexName) {
                return true;
            }
            if (isset($row['possible_keys']) && strpos($row['possible_keys'], $indexName) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Display summary at the end
     */
    protected function tearDown(): void
    {
        if (!empty($this->results)) {
            echo "\n\n" . str_repeat("=", 70) . "\n";
            echo "BENCHMARK SUMMARY\n";
            echo str_repeat("=", 70) . "\n";

            foreach ($this->results as $name => $data) {
                echo "\n$name:\n";
                echo "  Average Time: " . number_format($data['avg_time_ms'], 2) . "ms\n";
                echo "  Queries/Second: " . number_format($data['qps'], 2) . "\n";
                echo "  Uses Index: " . ($data['uses_index'] ? 'YES ✓' : 'NO ✗') . "\n";
            }

            echo "\n" . str_repeat("=", 70) . "\n";
        }

        parent::tearDown();
    }
}
