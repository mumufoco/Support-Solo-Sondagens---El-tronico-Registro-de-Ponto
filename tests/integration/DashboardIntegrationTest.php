<?php

namespace Tests\Integration;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Services\Analytics\DashboardService;

/**
 * Dashboard Integration Test
 *
 * Tests dashboard analytics with real data:
 * 1. KPI calculations
 * 2. Chart data generation
 * 3. Filtering by date and department
 * 4. Top employees ranking
 * 5. Activity timeline
 */
class DashboardIntegrationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = false;
    protected $refresh = false;

    protected DashboardService $dashboardService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dashboardService = new DashboardService();
    }

    /**
     * Test KPI calculations return valid data
     */
    public function testKPICalculations()
    {
        $kpis = $this->dashboardService->getOverviewKPIs(
            date('Y-m-d'),
            date('Y-m-d')
        );

        // Check all expected KPIs are present
        $this->assertArrayHasKey('total_employees', $kpis);
        $this->assertArrayHasKey('active_employees', $kpis);
        $this->assertArrayHasKey('punches_today', $kpis);
        $this->assertArrayHasKey('total_hours', $kpis);
        $this->assertArrayHasKey('pending_approvals', $kpis);
        $this->assertArrayHasKey('departments_count', $kpis);
        $this->assertArrayHasKey('avg_hours_per_employee', $kpis);

        // Check data types
        $this->assertIsInt($kpis['total_employees']);
        $this->assertIsInt($kpis['active_employees']);
        $this->assertIsInt($kpis['punches_today']);
        $this->assertIsFloat($kpis['total_hours']);
        $this->assertIsInt($kpis['pending_approvals']);
        $this->assertIsInt($kpis['departments_count']);
        $this->assertIsFloat($kpis['avg_hours_per_employee']);

        // Check logical constraints
        $this->assertGreaterThanOrEqual(0, $kpis['total_employees']);
        $this->assertGreaterThanOrEqual(0, $kpis['active_employees']);
        $this->assertLessThanOrEqual($kpis['total_employees'], $kpis['active_employees']);
    }

    /**
     * Test total employees count
     */
    public function testGetTotalEmployees()
    {
        $total = $this->dashboardService->getTotalEmployees();

        $this->assertIsInt($total);
        $this->assertGreaterThanOrEqual(0, $total);
    }

    /**
     * Test active employees count
     */
    public function testGetActiveEmployees()
    {
        $active = $this->dashboardService->getActiveEmployees();

        $this->assertIsInt($active);
        $this->assertGreaterThanOrEqual(0, $active);

        // Active should never exceed total
        $total = $this->dashboardService->getTotalEmployees();
        $this->assertLessThanOrEqual($total, $active);
    }

    /**
     * Test punches count for period
     */
    public function testGetPunchesCount()
    {
        $count = $this->dashboardService->getPunchesCount(
            date('Y-m-d', strtotime('-7 days')),
            date('Y-m-d')
        );

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    /**
     * Test total hours worked calculation
     */
    public function testGetTotalHoursWorked()
    {
        $hours = $this->dashboardService->getTotalHoursWorked(
            date('Y-m-d', strtotime('-30 days')),
            date('Y-m-d')
        );

        $this->assertIsFloat($hours);
        $this->assertGreaterThanOrEqual(0.0, $hours);
    }

    /**
     * Test pending approvals count
     */
    public function testGetPendingApprovals()
    {
        $pending = $this->dashboardService->getPendingApprovals();

        $this->assertIsInt($pending);
        $this->assertGreaterThanOrEqual(0, $pending);
    }

    /**
     * Test departments count
     */
    public function testGetDepartmentsCount()
    {
        $count = $this->dashboardService->getDepartmentsCount();

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    /**
     * Test average hours per employee
     */
    public function testGetAverageHoursPerEmployee()
    {
        $avg = $this->dashboardService->getAverageHoursPerEmployee(
            date('Y-m-d', strtotime('-7 days')),
            date('Y-m-d')
        );

        $this->assertIsFloat($avg);
        $this->assertGreaterThanOrEqual(0.0, $avg);
    }

    /**
     * Test punches by hour chart data
     */
    public function testGetPunchesByHour()
    {
        $data = $this->dashboardService->getPunchesByHour(date('Y-m-d'));

        $this->assertIsArray($data);
        $this->assertArrayHasKey('labels', $data);
        $this->assertArrayHasKey('data', $data);

        // Should have 24 hours
        $this->assertCount(24, $data['labels']);
        $this->assertCount(24, $data['data']);

        // All data points should be non-negative integers
        foreach ($data['data'] as $count) {
            $this->assertIsInt($count);
            $this->assertGreaterThanOrEqual(0, $count);
        }
    }

    /**
     * Test hours by department chart data
     */
    public function testGetHoursByDepartment()
    {
        $data = $this->dashboardService->getHoursByDepartment(
            date('Y-m-d', strtotime('-30 days')),
            date('Y-m-d')
        );

        $this->assertIsArray($data);
        $this->assertArrayHasKey('labels', $data);
        $this->assertArrayHasKey('data', $data);

        // Labels and data should have same count
        $this->assertCount(count($data['labels']), $data['data']);

        // All hours should be non-negative
        foreach ($data['data'] as $hours) {
            $this->assertIsFloat($hours);
            $this->assertGreaterThanOrEqual(0.0, $hours);
        }
    }

    /**
     * Test employee status distribution
     */
    public function testGetEmployeeStatusDistribution()
    {
        $data = $this->dashboardService->getEmployeeStatusDistribution();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('labels', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('colors', $data);

        // Should have 3 statuses: Trabalhando, Disponível, Inativo
        $this->assertCount(3, $data['labels']);
        $this->assertCount(3, $data['data']);
        $this->assertCount(3, $data['colors']);

        // All counts should be non-negative
        foreach ($data['data'] as $count) {
            $this->assertIsInt($count);
            $this->assertGreaterThanOrEqual(0, $count);
        }
    }

    /**
     * Test recent activity
     */
    public function testGetRecentActivity()
    {
        $activity = $this->dashboardService->getRecentActivity(10);

        $this->assertIsArray($activity);
        $this->assertLessThanOrEqual(10, count($activity));

        // Check structure of each activity item
        foreach ($activity as $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('employee_id', $item);
            $this->assertArrayHasKey('employee_name', $item);
            $this->assertArrayHasKey('department', $item);
            $this->assertArrayHasKey('punch_time', $item);
            $this->assertArrayHasKey('formatted_time', $item);
        }
    }

    /**
     * Test top employees by hours
     */
    public function testGetTopEmployeesByHours()
    {
        $topEmployees = $this->dashboardService->getTopEmployeesByHours(
            date('Y-m-d', strtotime('-30 days')),
            date('Y-m-d'),
            10
        );

        $this->assertIsArray($topEmployees);
        $this->assertLessThanOrEqual(10, count($topEmployees));

        // Check structure and ordering
        $previousHours = PHP_FLOAT_MAX;

        foreach ($topEmployees as $employee) {
            $this->assertArrayHasKey('id', $employee);
            $this->assertArrayHasKey('name', $employee);
            $this->assertArrayHasKey('department', $employee);
            $this->assertArrayHasKey('total_hours', $employee);

            // Should be ordered by hours descending
            $this->assertLessThanOrEqual($previousHours, $employee['total_hours']);
            $previousHours = $employee['total_hours'];

            // Hours should be non-negative
            $this->assertGreaterThanOrEqual(0.0, $employee['total_hours']);
        }
    }

    /**
     * Test attendance rate calculation
     */
    public function testGetAttendanceRate()
    {
        $rate = $this->dashboardService->getAttendanceRate(
            date('Y-m-d', strtotime('-7 days')),
            date('Y-m-d')
        );

        $this->assertIsFloat($rate);
        $this->assertGreaterThanOrEqual(0.0, $rate);
        $this->assertLessThanOrEqual(100.0, $rate);
    }

    /**
     * Test get departments for filter
     */
    public function testGetDepartments()
    {
        $departments = $this->dashboardService->getDepartments();

        $this->assertIsArray($departments);

        foreach ($departments as $dept) {
            $this->assertArrayHasKey('id', $dept);
            $this->assertArrayHasKey('name', $dept);
        }
    }

    /**
     * Test complete dashboard data
     */
    public function testGetDashboardData()
    {
        $data = $this->dashboardService->getDashboardData([
            'startDate' => date('Y-m-d'),
            'endDate' => date('Y-m-d'),
        ]);

        // Check main sections
        $this->assertArrayHasKey('kpis', $data);
        $this->assertArrayHasKey('charts', $data);
        $this->assertArrayHasKey('recent_activity', $data);
        $this->assertArrayHasKey('top_employees', $data);
        $this->assertArrayHasKey('attendance_rate', $data);
        $this->assertArrayHasKey('departments', $data);
        $this->assertArrayHasKey('filters', $data);

        // Check charts subsections
        $this->assertArrayHasKey('punches_by_hour', $data['charts']);
        $this->assertArrayHasKey('hours_by_department', $data['charts']);
        $this->assertArrayHasKey('employee_status', $data['charts']);
    }

    /**
     * Test dashboard with department filter
     */
    public function testDashboardWithDepartmentFilter()
    {
        // Get all departments
        $departments = $this->dashboardService->getDepartments();

        if (count($departments) > 0) {
            $deptId = $departments[0]['id'];

            // Get data filtered by department
            $data = $this->dashboardService->getDashboardData([
                'startDate' => date('Y-m-d'),
                'endDate' => date('Y-m-d'),
                'departmentId' => $deptId,
            ]);

            $this->assertEquals($deptId, $data['filters']['departmentId']);
            $this->assertIsArray($data['kpis']);
        } else {
            $this->markTestSkipped('No departments available for testing');
        }
    }

    /**
     * Test dashboard with date range filter
     */
    public function testDashboardWithDateRangeFilter()
    {
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d');

        $data = $this->dashboardService->getDashboardData([
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);

        $this->assertEquals($startDate, $data['filters']['startDate']);
        $this->assertEquals($endDate, $data['filters']['endDate']);
    }

    /**
     * Test data consistency across different time periods
     */
    public function testDataConsistencyAcrossTimePeriods()
    {
        // Get data for today
        $todayData = $this->dashboardService->getDashboardData([
            'startDate' => date('Y-m-d'),
            'endDate' => date('Y-m-d'),
        ]);

        // Get data for last 7 days
        $weekData = $this->dashboardService->getDashboardData([
            'startDate' => date('Y-m-d', strtotime('-7 days')),
            'endDate' => date('Y-m-d'),
        ]);

        // Total employees should be same regardless of date filter
        $this->assertEquals(
            $todayData['kpis']['total_employees'],
            $weekData['kpis']['total_employees']
        );

        // Active employees should be same
        $this->assertEquals(
            $todayData['kpis']['active_employees'],
            $weekData['kpis']['active_employees']
        );

        // Punches and hours might differ
        // Week data should have >= today's data
        $this->assertGreaterThanOrEqual(
            $todayData['kpis']['punches_today'],
            $weekData['kpis']['punches_today']
        );
    }

    /**
     * Test edge case: empty data
     */
    public function testEmptyDataHandling()
    {
        // Test with future date (should have no data)
        $futureDate = date('Y-m-d', strtotime('+1 year'));

        $data = $this->dashboardService->getDashboardData([
            'startDate' => $futureDate,
            'endDate' => $futureDate,
        ]);

        // Should still return valid structure
        $this->assertIsArray($data);
        $this->assertArrayHasKey('kpis', $data);

        // Punches should be 0
        $this->assertEquals(0, $data['kpis']['punches_today']);

        // Total hours should be 0
        $this->assertEquals(0.0, $data['kpis']['total_hours']);
    }

    /**
     * Test chart data formatting
     */
    public function testChartDataFormatting()
    {
        $data = $this->dashboardService->getDashboardData([
            'startDate' => date('Y-m-d'),
            'endDate' => date('Y-m-d'),
        ]);

        // Punches by hour should have proper format
        $punchesByHour = $data['charts']['punches_by_hour'];
        $this->assertIsArray($punchesByHour['labels']);
        $this->assertIsArray($punchesByHour['data']);

        // Hours by department should have proper format
        $hoursByDept = $data['charts']['hours_by_department'];
        $this->assertIsArray($hoursByDept['labels']);
        $this->assertIsArray($hoursByDept['data']);

        // Employee status should have proper format
        $empStatus = $data['charts']['employee_status'];
        $this->assertIsArray($empStatus['labels']);
        $this->assertIsArray($empStatus['data']);
        $this->assertIsArray($empStatus['colors']);
    }
}
