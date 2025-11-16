<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\Analytics\DashboardService;

/**
 * Dashboard Controller
 *
 * Displays analytics dashboard with KPIs and charts
 *
 * @package App\Controllers
 */
class DashboardController extends BaseController
{
    protected DashboardService $dashboardService;

    public function __construct()
    {
        $this->dashboardService = new DashboardService();
    }

    /**
     * Dashboard home page
     *
     * @return string
     */
    public function index()
    {
        // Check if user is logged in
        $employeeId = session()->get('employee_id');

        if (!$employeeId) {
            return redirect()->to('/auth/login');
        }

        // Get filters from query string
        $filters = [
            'startDate' => $this->request->getGet('start_date') ?? date('Y-m-d'),
            'endDate' => $this->request->getGet('end_date') ?? date('Y-m-d'),
            'departmentId' => $this->request->getGet('department_id') ? (int) $this->request->getGet('department_id') : null,
        ];

        // Get dashboard data
        $data = $this->dashboardService->getDashboardData($filters);

        // Add employee info
        $employeeModel = new \App\Models\EmployeeModel();
        $employee = $employeeModel->find($employeeId);

        $data['employee'] = $employee;
        $data['page_title'] = 'Dashboard Analytics';

        return view('dashboard/index', $data);
    }

    /**
     * Get dashboard data as JSON (for AJAX/API)
     *
     * @return ResponseInterface
     */
    public function getData()
    {
        // Check if user is logged in
        $employeeId = session()->get('employee_id');

        if (!$employeeId) {
            return $this->response->setJSON([
                'error' => true,
                'message' => 'Unauthorized',
            ])->setStatusCode(401);
        }

        // Get filters from query string
        $filters = [
            'startDate' => $this->request->getGet('start_date') ?? date('Y-m-d'),
            'endDate' => $this->request->getGet('end_date') ?? date('Y-m-d'),
            'departmentId' => $this->request->getGet('department_id') ? (int) $this->request->getGet('department_id') : null,
        ];

        // Get dashboard data
        $data = $this->dashboardService->getDashboardData($filters);

        return $this->response->setJSON([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Export dashboard data as CSV
     *
     * @return ResponseInterface
     */
    public function exportCSV()
    {
        // Check if user is logged in
        $employeeId = session()->get('employee_id');

        if (!$employeeId) {
            return redirect()->to('/auth/login');
        }

        // Get filters
        $filters = [
            'startDate' => $this->request->getGet('start_date') ?? date('Y-m-d'),
            'endDate' => $this->request->getGet('end_date') ?? date('Y-m-d'),
            'departmentId' => $this->request->getGet('department_id') ? (int) $this->request->getGet('department_id') : null,
        ];

        // Get data
        $data = $this->dashboardService->getDashboardData($filters);

        // Generate CSV
        $csv = $this->generateCSV($data, $filters);

        // Set headers for download
        $filename = 'dashboard_' . date('Y-m-d_His') . '.csv';

        return $this->response
            ->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($csv);
    }

    /**
     * Generate CSV from dashboard data
     *
     * @param array $data
     * @param array $filters
     * @return string
     */
    protected function generateCSV(array $data, array $filters): string
    {
        $output = fopen('php://temp', 'r+');

        // Header
        fputcsv($output, ['Dashboard Analytics Report']);
        fputcsv($output, ['Period', $filters['startDate'] . ' to ' . $filters['endDate']]);
        fputcsv($output, []);

        // KPIs
        fputcsv($output, ['Key Performance Indicators']);
        fputcsv($output, ['Metric', 'Value']);

        foreach ($data['kpis'] as $key => $value) {
            $label = ucwords(str_replace('_', ' ', $key));
            fputcsv($output, [$label, $value]);
        }

        fputcsv($output, []);

        // Top Employees
        fputcsv($output, ['Top Employees by Hours Worked']);
        fputcsv($output, ['Name', 'Department', 'Total Hours']);

        foreach ($data['top_employees'] as $employee) {
            fputcsv($output, [
                $employee['name'],
                $employee['department'],
                $employee['total_hours'],
            ]);
        }

        fputcsv($output, []);

        // Recent Activity
        fputcsv($output, ['Recent Activity']);
        fputcsv($output, ['Employee', 'Department', 'Punch Time', 'Type']);

        foreach ($data['recent_activity'] as $activity) {
            fputcsv($output, [
                $activity['employee_name'],
                $activity['department'],
                $activity['formatted_time'],
                $activity['punch_type'] ?? 'regular',
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
