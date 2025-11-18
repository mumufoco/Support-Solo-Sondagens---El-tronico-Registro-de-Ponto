<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;
use App\Models\TimePunchModel;
use App\Models\JustificationModel;
use App\Models\UserConsentModel;
use App\Models\BiometricTemplateModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $employeeModel = new EmployeeModel();
        $timePunchModel = new TimePunchModel();
        $justificationModel = new JustificationModel();
        $consentModel = new UserConsentModel();
        $biometricModel = new BiometricTemplateModel();

        // Cards com totais
        $data = [
            'total_employees' => $employeeModel->where('active', true)->countAllResults(),
            'punches_today' => $timePunchModel->where('DATE(punch_time)', date('Y-m-d'))->countAllResults(),
            'pending_justifications' => $justificationModel->where('status', 'pending')->countAllResults(),
            'pending_consents' => $consentModel->where('granted', false)->countAllResults(),
            'enrolled_biometrics' => $biometricModel->where('is_active', true)->countAllResults(),

            // Marcações últimos 7 dias (para gráfico Chart.js)
            'punches_last_7_days' => $this->getPunchesLast7Days(),

            // Alertas
            'alerts' => $this->getAlerts(),

            // Estatísticas gerais
            'stats' => $this->getGeneralStats(),
        ];

        return view('admin/dashboard', $data);
    }

    /**
     * Retorna dados de marcações dos últimos 7 dias para gráfico
     */
    private function getPunchesLast7Days(): array
    {
        $timePunchModel = new TimePunchModel();
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $count = $timePunchModel->where('DATE(punch_time)', $date)->countAllResults();

            $data[] = [
                'date' => date('d/m', strtotime($date)),
                'full_date' => $date,
                'count' => $count,
            ];
        }

        return $data;
    }

    /**
     * Retorna alertas para o dashboard
     */
    private function getAlerts(): array
    {
        $alerts = [];
        $employeeModel = new EmployeeModel();
        $consentModel = new UserConsentModel();

        // TODO: Funcionários sem biometria - requer coluna has_face_biometric na tabela employees
        // Temporariamente desabilitado devido a incompatibilidade de schema
        /*
        $withoutBiometric = $employeeModel
            ->where('active', true)
            ->where('has_face_biometric', false)
            ->countAllResults();

        if ($withoutBiometric > 0) {
            $alerts[] = [
                'type' => 'warning',
                'icon' => 'fa-user-circle',
                'message' => "$withoutBiometric funcionário(s) sem cadastro biométrico",
                'link' => '/admin/employees?filter=no_biometric',
            ];
        }
        */

        // Consentimentos LGPD pendentes
        $pendingConsents = $consentModel->where('granted', false)->countAllResults();

        if ($pendingConsents > 0) {
            $alerts[] = [
                'type' => 'danger',
                'icon' => 'fa-shield-alt',
                'message' => "$pendingConsents consentimento(s) LGPD pendente(s)",
                'link' => '/admin/consents',
            ];
        }

        // Exemplo: Saldos negativos (implementar conforme necessário)
        // $negativeBa...

        return $alerts;
    }

    /**
     * Retorna estatísticas gerais
     */
    private function getGeneralStats(): array
    {
        $employeeModel = new EmployeeModel();
        $timePunchModel = new TimePunchModel();

        $totalEmployees = $employeeModel->where('active', true)->countAllResults();
        $punchesThisMonth = $timePunchModel
            ->where('MONTH(punch_time)', date('m'))
            ->where('YEAR(punch_time)', date('Y'))
            ->countAllResults();

        $avgPunchesPerDay = $punchesThisMonth > 0 ? round($punchesThisMonth / date('j'), 1) : 0;

        return [
            'total_employees' => $totalEmployees,
            'punches_this_month' => $punchesThisMonth,
            'avg_punches_per_day' => $avgPunchesPerDay,
            'working_days_this_month' => $this->getWorkingDaysThisMonth(),
        ];
    }

    /**
     * Calcula dias úteis do mês atual
     */
    private function getWorkingDaysThisMonth(): int
    {
        $year = date('Y');
        $month = date('m');
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        $workingDays = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
            $dayOfWeek = date('N', strtotime($date));

            // Segunda a Sexta (1-5)
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                $workingDays++;
            }
        }

        return $workingDays;
    }
}
