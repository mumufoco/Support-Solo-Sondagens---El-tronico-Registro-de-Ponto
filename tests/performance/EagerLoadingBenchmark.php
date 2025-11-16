<?php

namespace Tests\Performance;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Models\EmployeeModel;
use App\Models\TimePunchModel;
use App\Models\JustificationModel;
use App\Models\WarningModel;

/**
 * Eager Loading vs N+1 Queries Performance Benchmark
 *
 * Tests the performance improvement of eager loading methods added to EmployeeModel:
 * - getWithRelations()
 * - getWithPunchStats()
 * - getActiveWithDepartment()
 *
 * Run with: php spark test --filter EagerLoadingBenchmark
 */
class EagerLoadingBenchmark extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $employeeModel;
    protected $timePunchModel;
    protected $justificationModel;
    protected $warningModel;
    protected $db;
    protected $results = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->employeeModel = new EmployeeModel();
        $this->timePunchModel = new TimePunchModel();
        $this->justificationModel = new JustificationModel();
        $this->warningModel = new WarningModel();
        $this->db = \Config\Database::connect();

        // Enable query profiling
        $this->db->enableQueryLog();
    }

    /**
     * Benchmark: N+1 Query Problem (Traditional Approach)
     */
    public function testN1QueryProblem()
    {
        echo "\n\n=== BENCHMARK: N+1 Query Problem (Traditional) ===\n";

        $limit = 20;

        // Clear query log
        $this->db->resetDataCache();

        $start = microtime(true);
        $queryCountStart = count($this->db->getQueries());

        // Get employees (1 query)
        $employees = $this->employeeModel->where('active', 1)->limit($limit)->find();

        // For each employee, get related data (N queries)
        $employeeData = [];
        foreach ($employees as $employee) {
            $employeeData[] = [
                'employee' => $employee,
                'manager' => $employee->manager_id
                    ? $this->employeeModel->find($employee->manager_id)
                    : null,
                'punches_count' => $this->timePunchModel
                    ->where('employee_id', $employee->id)
                    ->countAllResults(),
                'justifications_count' => $this->justificationModel
                    ->where('employee_id', $employee->id)
                    ->countAllResults(),
                'warnings_count' => $this->warningModel
                    ->where('employee_id', $employee->id)
                    ->countAllResults(),
            ];
        }

        $end = microtime(true);
        $totalTime = ($end - $start);
        $queryCountEnd = count($this->db->getQueries());
        $totalQueries = $queryCountEnd - $queryCountStart;

        echo "Employees loaded: " . count($employees) . "\n";
        echo "Total queries executed: $totalQueries\n";
        echo "Total Time: " . number_format($totalTime * 1000, 2) . "ms\n";
        echo "Average per employee: " . number_format(($totalTime / count($employees)) * 1000, 2) . "ms\n";

        $this->results['n_plus_1'] = [
            'employees' => count($employees),
            'total_queries' => $totalQueries,
            'total_time_ms' => $totalTime * 1000,
            'avg_per_employee_ms' => ($totalTime / count($employees)) * 1000,
        ];

        $this->assertGreaterThan(1, $totalQueries, "Should execute multiple queries (N+1 problem)");
    }

    /**
     * Benchmark: Eager Loading with getWithRelations()
     */
    public function testEagerLoading()
    {
        echo "\n\n=== BENCHMARK: Eager Loading (Optimized) ===\n";

        $limit = 20;

        // Get employee IDs to load
        $employeeIds = $this->employeeModel->where('active', 1)
            ->limit($limit)
            ->findColumn('id');

        // Clear query log
        $this->db->resetDataCache();

        $start = microtime(true);
        $queryCountStart = count($this->db->getQueries());

        // Use eager loading (1 query with JOINs)
        $employees = $this->employeeModel->getWithRelations($employeeIds);

        $end = microtime(true);
        $totalTime = ($end - $start);
        $queryCountEnd = count($this->db->getQueries());
        $totalQueries = $queryCountEnd - $queryCountStart;

        echo "Employees loaded: " . count($employees) . "\n";
        echo "Total queries executed: $totalQueries\n";
        echo "Total Time: " . number_format($totalTime * 1000, 2) . "ms\n";
        echo "Average per employee: " . number_format(($totalTime / count($employees)) * 1000, 2) . "ms\n";

        $this->results['eager_loading'] = [
            'employees' => count($employees),
            'total_queries' => $totalQueries,
            'total_time_ms' => $totalTime * 1000,
            'avg_per_employee_ms' => ($totalTime / count($employees)) * 1000,
        ];

        // Calculate improvement
        if (isset($this->results['n_plus_1'])) {
            $queryReduction = $this->results['n_plus_1']['total_queries'] - $totalQueries;
            $timeReduction = (($this->results['n_plus_1']['total_time_ms'] - $totalTime * 1000)
                / $this->results['n_plus_1']['total_time_ms']) * 100;
            $speedup = $this->results['n_plus_1']['total_time_ms'] / ($totalTime * 1000);

            echo "\nImprovement:\n";
            echo "  Query Reduction: $queryReduction queries saved\n";
            echo "  Time Reduction: " . number_format($timeReduction, 1) . "%\n";
            echo "  Speedup: " . number_format($speedup, 2) . "x faster\n";

            $this->results['eager_loading']['query_reduction'] = $queryReduction;
            $this->results['eager_loading']['time_reduction_pct'] = $timeReduction;
            $this->results['eager_loading']['speedup'] = $speedup;
        }

        $this->assertLessThan(5, $totalQueries, "Should execute minimal queries with eager loading");
    }

    /**
     * Benchmark: getWithPunchStats() Performance
     */
    public function testGetWithPunchStats()
    {
        echo "\n\n=== BENCHMARK: getWithPunchStats() Performance ===\n";

        $startDate = date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d');
        $limit = 20;

        $employeeIds = $this->employeeModel->where('active', 1)
            ->limit($limit)
            ->findColumn('id');

        // Clear query log
        $this->db->resetDataCache();

        $start = microtime(true);
        $queryCountStart = count($this->db->getQueries());

        $employees = $this->employeeModel->getWithPunchStats($employeeIds, $startDate, $endDate);

        $end = microtime(true);
        $totalTime = ($end - $start);
        $queryCountEnd = count($this->db->getQueries());
        $totalQueries = $queryCountEnd - $queryCountStart;

        echo "Employees loaded: " . count($employees) . "\n";
        echo "Date range: $startDate to $endDate\n";
        echo "Total queries executed: $totalQueries\n";
        echo "Total Time: " . number_format($totalTime * 1000, 2) . "ms\n";
        echo "Average per employee: " . number_format(($totalTime / count($employees)) * 1000, 2) . "ms\n";

        $this->results['punch_stats'] = [
            'employees' => count($employees),
            'total_queries' => $totalQueries,
            'total_time_ms' => $totalTime * 1000,
            'avg_per_employee_ms' => ($totalTime / count($employees)) * 1000,
        ];

        // Verify that punch stats are included
        if (!empty($employees)) {
            $firstEmployee = $employees[0];
            echo "\nSample employee data:\n";
            echo "  Total Punches: " . ($firstEmployee['total_punches'] ?? 0) . "\n";
            echo "  Total Entrances: " . ($firstEmployee['total_entrances'] ?? 0) . "\n";
            echo "  Total Exits: " . ($firstEmployee['total_exits'] ?? 0) . "\n";

            $this->assertArrayHasKey('total_punches', $firstEmployee,
                "Should include punch statistics");
        }

        $this->assertLessThan(5, $totalQueries, "Should use minimal queries with aggregation");
    }

    /**
     * Benchmark: getActiveWithDepartment() Performance
     */
    public function testGetActiveWithDepartment()
    {
        echo "\n\n=== BENCHMARK: getActiveWithDepartment() Performance ===\n";

        $department = 'TI';

        // Clear query log
        $this->db->resetDataCache();

        $start = microtime(true);
        $queryCountStart = count($this->db->getQueries());

        $employees = $this->employeeModel->getActiveWithDepartment($department);

        $end = microtime(true);
        $totalTime = ($end - $start);
        $queryCountEnd = count($this->db->getQueries());
        $totalQueries = $queryCountEnd - $queryCountStart;

        echo "Employees loaded: " . count($employees) . "\n";
        echo "Department: $department\n";
        echo "Total queries executed: $totalQueries\n";
        echo "Total Time: " . number_format($totalTime * 1000, 2) . "ms\n";
        if (count($employees) > 0) {
            echo "Average per employee: " . number_format(($totalTime / count($employees)) * 1000, 2) . "ms\n";
        }

        $this->results['department_filter'] = [
            'employees' => count($employees),
            'total_queries' => $totalQueries,
            'total_time_ms' => $totalTime * 1000,
        ];

        $this->assertEquals(1, $totalQueries, "Should execute exactly 1 query");
        $this->assertLessThan(50, $totalTime * 1000, "Should be faster than 50ms");
    }

    /**
     * Benchmark: Comparison - Multiple Small Queries vs One Large Query
     */
    public function testMultipleSmallVsOneLarge()
    {
        echo "\n\n=== BENCHMARK: Multiple Small Queries vs One Large Query ===\n";

        $limit = 10;

        // === Multiple Small Queries ===
        $this->db->resetDataCache();
        $start = microtime(true);
        $queryCountStart = count($this->db->getQueries());

        $employees = $this->employeeModel->where('active', 1)->limit($limit)->find();
        foreach ($employees as $employee) {
            // One query per employee
            $this->timePunchModel->where('employee_id', $employee->id)
                ->where('punch_time >=', date('Y-m-d', strtotime('-7 days')))
                ->findAll();
        }

        $end = microtime(true);
        $multipleTime = ($end - $start);
        $multipleQueries = count($this->db->getQueries()) - $queryCountStart;

        echo "Multiple Small Queries:\n";
        echo "  Queries: $multipleQueries\n";
        echo "  Time: " . number_format($multipleTime * 1000, 2) . "ms\n";

        // === One Large Query ===
        $this->db->resetDataCache();
        $start = microtime(true);
        $queryCountStart = count($this->db->getQueries());

        $employeeIds = array_column($employees, 'id');
        $punches = $this->timePunchModel
            ->whereIn('employee_id', $employeeIds)
            ->where('punch_time >=', date('Y-m-d', strtotime('-7 days')))
            ->findAll();

        $end = microtime(true);
        $singleTime = ($end - $start);
        $singleQueries = count($this->db->getQueries()) - $queryCountStart;

        echo "\nOne Large Query:\n";
        echo "  Queries: $singleQueries\n";
        echo "  Time: " . number_format($singleTime * 1000, 2) . "ms\n";

        $speedup = $multipleTime / $singleTime;
        $queryReduction = $multipleQueries - $singleQueries;

        echo "\nImprovement:\n";
        echo "  Query Reduction: $queryReduction\n";
        echo "  Speedup: " . number_format($speedup, 2) . "x\n";

        $this->results['batch_query'] = [
            'multiple_queries' => $multipleQueries,
            'multiple_time_ms' => $multipleTime * 1000,
            'single_queries' => $singleQueries,
            'single_time_ms' => $singleTime * 1000,
            'speedup' => $speedup,
            'query_reduction' => $queryReduction,
        ];

        $this->assertGreaterThan(1, $speedup, "Single large query should be faster");
    }

    /**
     * Display summary at the end
     */
    protected function tearDown(): void
    {
        if (!empty($this->results)) {
            echo "\n\n" . str_repeat("=", 70) . "\n";
            echo "EAGER LOADING BENCHMARK SUMMARY\n";
            echo str_repeat("=", 70) . "\n";

            // N+1 vs Eager Loading
            if (isset($this->results['n_plus_1']) && isset($this->results['eager_loading'])) {
                echo "\nN+1 Problem vs Eager Loading:\n";
                echo "  N+1 Queries:     " . $this->results['n_plus_1']['total_queries'] . "\n";
                echo "  N+1 Time:        " . number_format($this->results['n_plus_1']['total_time_ms'], 2) . "ms\n";
                echo "\n";
                echo "  Eager Queries:   " . $this->results['eager_loading']['total_queries'] . "\n";
                echo "  Eager Time:      " . number_format($this->results['eager_loading']['total_time_ms'], 2) . "ms\n";
                echo "\n";
                echo "  Queries Saved:   " . $this->results['eager_loading']['query_reduction'] . "\n";
                echo "  Time Reduction:  " . number_format($this->results['eager_loading']['time_reduction_pct'], 1) . "%\n";
                echo "  Speedup:         " . number_format($this->results['eager_loading']['speedup'], 2) . "x\n";
            }

            // Specialized Methods
            if (isset($this->results['punch_stats'])) {
                echo "\ngetWithPunchStats():\n";
                echo "  Queries: " . $this->results['punch_stats']['total_queries'] . "\n";
                echo "  Time:    " . number_format($this->results['punch_stats']['total_time_ms'], 2) . "ms\n";
            }

            if (isset($this->results['department_filter'])) {
                echo "\ngetActiveWithDepartment():\n";
                echo "  Queries: " . $this->results['department_filter']['total_queries'] . "\n";
                echo "  Time:    " . number_format($this->results['department_filter']['total_time_ms'], 2) . "ms\n";
            }

            // Batch Query
            if (isset($this->results['batch_query'])) {
                echo "\nBatch Query Optimization:\n";
                echo "  Multiple small queries: " . $this->results['batch_query']['multiple_queries'] .
                     " (" . number_format($this->results['batch_query']['multiple_time_ms'], 2) . "ms)\n";
                echo "  One large query:        " . $this->results['batch_query']['single_queries'] .
                     " (" . number_format($this->results['batch_query']['single_time_ms'], 2) . "ms)\n";
                echo "  Speedup:                " . number_format($this->results['batch_query']['speedup'], 2) . "x\n";
            }

            echo "\n" . str_repeat("=", 70) . "\n";
        }

        parent::tearDown();
    }
}
