<?php

namespace App\Controllers\API;

use CodeIgniter\RESTful\ResourceController;
use App\Services\Analytics\DashboardService;

/**
 * Dashboard API Controller
 *
 * Provides dashboard analytics data for mobile app
 *
 * Endpoints:
 * - GET /api/dashboard - Get complete dashboard data
 * - GET /api/dashboard/kpis - Get KPIs only
 * - GET /api/dashboard/charts - Get chart data only
 * - GET /api/dashboard/activity - Get recent activity
 *
 * @package App\Controllers\API
 */
class DashboardController extends ResourceController
{
    protected $format = 'json';

    protected DashboardService $dashboardService;

    public function __construct()
    {
        $this->dashboardService = new DashboardService();
    }

    /**
     * Get complete dashboard data
     *
     * GET /api/dashboard
     *
     * Query parameters:
     * - start_date: Start date (Y-m-d)
     * - end_date: End date (Y-m-d)
     * - department_id: Department filter
     *
     * Response:
     * {
     *   "success": true,
     *   "data": {
     *     "kpis": {...},
     *     "charts": {...},
     *     "recent_activity": [...],
     *     "top_employees": [...],
     *     "attendance_rate": 95.5
     *   }
     * }
     *
     * @return ResponseInterface
     */
    public function index()
    {
        // Get employee ID from authenticated token
        $employeeId = $this->request->employeeId ?? null;

        if (!$employeeId) {
            return $this->failUnauthorized('Authentication required');
        }

        // Get filters from query parameters
        $filters = [
            'startDate' => $this->request->getGet('start_date') ?? date('Y-m-d'),
            'endDate' => $this->request->getGet('end_date') ?? date('Y-m-d'),
            'departmentId' => $this->request->getGet('department_id') ? (int) $this->request->getGet('department_id') : null,
        ];

        // Get dashboard data
        $data = $this->dashboardService->getDashboardData($filters);

        return $this->respond([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get KPIs only
     *
     * GET /api/dashboard/kpis
     *
     * @return ResponseInterface
     */
    public function kpis()
    {
        $employeeId = $this->request->employeeId ?? null;

        if (!$employeeId) {
            return $this->failUnauthorized('Authentication required');
        }

        $filters = [
            'startDate' => $this->request->getGet('start_date') ?? date('Y-m-d'),
            'endDate' => $this->request->getGet('end_date') ?? date('Y-m-d'),
            'departmentId' => $this->request->getGet('department_id') ? (int) $this->request->getGet('department_id') : null,
        ];

        $kpis = $this->dashboardService->getOverviewKPIs(
            $filters['startDate'],
            $filters['endDate'],
            $filters['departmentId']
        );

        return $this->respond([
            'success' => true,
            'data' => $kpis,
        ]);
    }

    /**
     * Get chart data only
     *
     * GET /api/dashboard/charts
     *
     * @return ResponseInterface
     */
    public function charts()
    {
        $employeeId = $this->request->employeeId ?? null;

        if (!$employeeId) {
            return $this->failUnauthorized('Authentication required');
        }

        $startDate = $this->request->getGet('start_date') ?? date('Y-m-d');
        $endDate = $this->request->getGet('end_date') ?? date('Y-m-d');
        $departmentId = $this->request->getGet('department_id') ? (int) $this->request->getGet('department_id') : null;

        $charts = [
            'punches_by_hour' => $this->dashboardService->getPunchesByHour($startDate, $departmentId),
            'hours_by_department' => $this->dashboardService->getHoursByDepartment($startDate, $endDate),
            'employee_status' => $this->dashboardService->getEmployeeStatusDistribution($departmentId),
        ];

        return $this->respond([
            'success' => true,
            'data' => $charts,
        ]);
    }

    /**
     * Get recent activity
     *
     * GET /api/dashboard/activity
     *
     * Query parameters:
     * - limit: Number of results (default: 10)
     * - department_id: Department filter
     *
     * @return ResponseInterface
     */
    public function activity()
    {
        $employeeId = $this->request->employeeId ?? null;

        if (!$employeeId) {
            return $this->failUnauthorized('Authentication required');
        }

        $limit = $this->request->getGet('limit') ? (int) $this->request->getGet('limit') : 10;
        $departmentId = $this->request->getGet('department_id') ? (int) $this->request->getGet('department_id') : null;

        $activity = $this->dashboardService->getRecentActivity($limit, $departmentId);

        return $this->respond([
            'success' => true,
            'data' => $activity,
        ]);
    }

    /**
     * Get top employees by hours
     *
     * GET /api/dashboard/top-employees
     *
     * Query parameters:
     * - start_date: Start date
     * - end_date: End date
     * - limit: Number of results (default: 10)
     * - department_id: Department filter
     *
     * @return ResponseInterface
     */
    public function topEmployees()
    {
        $employeeId = $this->request->employeeId ?? null;

        if (!$employeeId) {
            return $this->failUnauthorized('Authentication required');
        }

        $startDate = $this->request->getGet('start_date') ?? date('Y-m-d');
        $endDate = $this->request->getGet('end_date') ?? date('Y-m-d');
        $limit = $this->request->getGet('limit') ? (int) $this->request->getGet('limit') : 10;
        $departmentId = $this->request->getGet('department_id') ? (int) $this->request->getGet('department_id') : null;

        $topEmployees = $this->dashboardService->getTopEmployeesByHours(
            $startDate,
            $endDate,
            $limit,
            $departmentId
        );

        return $this->respond([
            'success' => true,
            'data' => $topEmployees,
        ]);
    }

    /**
     * Get attendance rate
     *
     * GET /api/dashboard/attendance
     *
     * @return ResponseInterface
     */
    public function attendance()
    {
        $employeeId = $this->request->employeeId ?? null;

        if (!$employeeId) {
            return $this->failUnauthorized('Authentication required');
        }

        $startDate = $this->request->getGet('start_date') ?? date('Y-m-d');
        $endDate = $this->request->getGet('end_date') ?? date('Y-m-d');
        $departmentId = $this->request->getGet('department_id') ? (int) $this->request->getGet('department_id') : null;

        $rate = $this->dashboardService->getAttendanceRate($startDate, $endDate, $departmentId);

        return $this->respond([
            'success' => true,
            'data' => [
                'attendance_rate' => $rate,
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            ],
        ]);
    }

    /**
     * Get departments list
     *
     * GET /api/dashboard/departments
     *
     * @return ResponseInterface
     */
    public function departments()
    {
        $employeeId = $this->request->employeeId ?? null;

        if (!$employeeId) {
            return $this->failUnauthorized('Authentication required');
        }

        $departments = $this->dashboardService->getDepartments();

        return $this->respond([
            'success' => true,
            'data' => $departments,
        ]);
    }
}
