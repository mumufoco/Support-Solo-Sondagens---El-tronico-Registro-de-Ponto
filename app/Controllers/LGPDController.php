<?php

namespace App\Controllers;

use App\Services\LGPD\ConsentService;
use App\Services\LGPD\DataExportService;
use App\Models\UserConsentModel;
use App\Models\EmployeeModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * LGPDController
 *
 * Controller for LGPD compliance features
 * - Consent management
 * - Data portability (Art. 19)
 * - Data export requests
 */
class LGPDController extends BaseController
{
    protected ConsentService $consentService;
    protected DataExportService $exportService;
    protected UserConsentModel $consentModel;
    protected EmployeeModel $employeeModel;

    public function __construct()
    {
        $this->consentService = new ConsentService();
        $this->exportService = new DataExportService();
        $this->consentModel = new UserConsentModel();
        $this->employeeModel = new EmployeeModel();
    }

    /**
     * Consent portal - Main page
     */
    public function consents(): string
    {
        $employeeId = session()->get('employee_id');

        if (!$employeeId) {
            return redirect()->to('/login')->with('error', 'Você precisa estar autenticado');
        }

        $employee = $this->employeeModel->find($employeeId);
        $consents = $this->consentService->getEmployeeConsents($employeeId);

        // Get available consent types with descriptions
        $consentTypes = [
            'biometric_face' => [
                'label' => 'Biometria Facial',
                'purpose' => 'Captura e processamento de dados biométricos faciais para registro de ponto eletrônico',
                'legal_basis' => 'LGPD Art. 11, II, a - Cumprimento de obrigação legal (CLT)',
                'required' => true,
            ],
            'biometric_fingerprint' => [
                'label' => 'Biometria Digital (Impressão Digital)',
                'purpose' => 'Captura e processamento de impressões digitais para registro de ponto eletrônico',
                'legal_basis' => 'LGPD Art. 11, II, a - Cumprimento de obrigação legal (CLT)',
                'required' => true,
            ],
            'geolocation' => [
                'label' => 'Geolocalização',
                'purpose' => 'Coleta de dados de localização GPS para validação de registros de ponto em campo',
                'legal_basis' => 'LGPD Art. 7, I - Mediante consentimento',
                'required' => false,
            ],
            'data_processing' => [
                'label' => 'Processamento de Dados Pessoais',
                'purpose' => 'Processamento de dados pessoais para gestão de recursos humanos e folha de pagamento',
                'legal_basis' => 'LGPD Art. 7, V - Execução de contrato',
                'required' => true,
            ],
            'marketing' => [
                'label' => 'Comunicações de Marketing',
                'purpose' => 'Envio de comunicações sobre eventos, treinamentos e benefícios da empresa',
                'legal_basis' => 'LGPD Art. 7, I - Mediante consentimento',
                'required' => false,
            ],
            'data_sharing' => [
                'label' => 'Compartilhamento de Dados',
                'purpose' => 'Compartilhamento de dados com parceiros para administração de benefícios (plano de saúde, vale-refeição, etc)',
                'legal_basis' => 'LGPD Art. 7, V - Execução de contrato',
                'required' => false,
            ],
        ];

        return view('lgpd/consents', [
            'employee' => $employee,
            'consents' => $consents,
            'consentTypes' => $consentTypes,
            'title' => 'Gestão de Consentimentos LGPD',
        ]);
    }

    /**
     * Grant consent
     */
    public function grantConsent(): ResponseInterface
    {
        $employeeId = session()->get('employee_id');

        if (!$employeeId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Não autenticado',
            ])->setStatusCode(401);
        }

        $consentType = $this->request->getPost('consent_type');
        $purpose = $this->request->getPost('purpose');
        $consentText = $this->request->getPost('consent_text');
        $legalBasis = $this->request->getPost('legal_basis');
        $version = $this->request->getPost('version') ?? '1.0';

        if (!$consentType || !$purpose || !$consentText) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Dados incompletos',
            ])->setStatusCode(400);
        }

        $result = $this->consentService->grant(
            $employeeId,
            $consentType,
            $purpose,
            $consentText,
            $legalBasis,
            $version
        );

        return $this->response->setJSON($result);
    }

    /**
     * Revoke consent
     */
    public function revokeConsent(): ResponseInterface
    {
        $employeeId = session()->get('employee_id');

        if (!$employeeId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Não autenticado',
            ])->setStatusCode(401);
        }

        $consentType = $this->request->getPost('consent_type');
        $reason = $this->request->getPost('reason');

        if (!$consentType) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tipo de consentimento não informado',
            ])->setStatusCode(400);
        }

        $result = $this->consentService->revoke($employeeId, $consentType, $reason);

        return $this->response->setJSON($result);
    }

    /**
     * Request data export (LGPD Art. 19)
     */
    public function requestExport(): ResponseInterface
    {
        $employeeId = session()->get('employee_id');

        if (!$employeeId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Não autenticado',
            ])->setStatusCode(401);
        }

        $employee = $this->employeeModel->find($employeeId);

        if (!$employee) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Funcionário não encontrado',
            ])->setStatusCode(404);
        }

        // Check rate limiting (1 export per 24 hours)
        $db = \Config\Database::connect();
        $recentExport = $db->table('data_exports')
            ->where('employee_id', $employeeId)
            ->where('created_at >', date('Y-m-d H:i:s', strtotime('-24 hours')))
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getRow();

        if ($recentExport) {
            $nextAvailable = date('d/m/Y H:i', strtotime($recentExport->created_at . ' +24 hours'));

            return $this->response->setJSON([
                'success' => false,
                'message' => "Você já solicitou uma exportação recentemente. Próxima disponível em: {$nextAvailable}",
            ])->setStatusCode(429);
        }

        $result = $this->exportService->exportUserData($employeeId, $employee->email);

        return $this->response->setJSON($result);
    }

    /**
     * Download export file
     */
    public function downloadExport(string $exportId): ResponseInterface
    {
        $employeeId = session()->get('employee_id');

        if (!$employeeId) {
            return redirect()->to('/login')->with('error', 'Você precisa estar autenticado');
        }

        // Validate export belongs to user
        $db = \Config\Database::connect();
        $export = $db->table('data_exports')
            ->where('export_id', $exportId)
            ->where('employee_id', $employeeId)
            ->where('status', 'completed')
            ->get()
            ->getRow();

        if (!$export) {
            return redirect()->to('/lgpd/consents')->with('error', 'Exportação não encontrada');
        }

        // Check expiration
        if (strtotime($export->expires_at) < time()) {
            return redirect()->to('/lgpd/consents')->with('error', 'Exportação expirada. Solicite uma nova.');
        }

        $filePath = WRITEPATH . 'exports/lgpd/' . $exportId . '.zip';

        if (!file_exists($filePath)) {
            return redirect()->to('/lgpd/consents')->with('error', 'Arquivo não encontrado');
        }

        // Update download count
        $db->table('data_exports')
            ->where('id', $export->id)
            ->update([
                'download_count' => ($export->download_count ?? 0) + 1,
                'last_downloaded_at' => date('Y-m-d H:i:s'),
            ]);

        // Return file for download
        return $this->response->download($filePath, null)->setFileName('meus_dados_lgpd.zip');
    }

    /**
     * Admin: ANPD Report
     * Requires admin/DPO role
     */
    public function anpdReport(): string
    {
        // Check admin/DPO permission
        if (!$this->hasPermission('view_lgpd_reports')) {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado');
        }

        $startDate = $this->request->getGet('start_date') ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $this->request->getGet('end_date') ?? date('Y-m-d');

        $report = $this->consentService->generateANPDReport($startDate, $endDate);

        return view('lgpd/anpd_report', [
            'report' => $report,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'title' => 'Relatório ANPD',
        ]);
    }

    /**
     * Admin: Export ANPD report to PDF
     */
    public function exportANPDReport(): ResponseInterface
    {
        if (!$this->hasPermission('view_lgpd_reports')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso negado',
            ])->setStatusCode(403);
        }

        $startDate = $this->request->getGet('start_date') ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $this->request->getGet('end_date') ?? date('Y-m-d');

        $report = $this->consentService->generateANPDReport($startDate, $endDate);

        // Generate PDF (simplified version - can be enhanced)
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetCreator('Sistema de Ponto Eletrônico');
        $pdf->SetAuthor(env('COMPANY_NAME', 'Empresa'));
        $pdf->SetTitle('Relatório ANPD - Atividades de Tratamento de Dados');

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);

        $pdf->AddPage();

        $html = view('lgpd/anpd_report_pdf', ['report' => $report]);
        $pdf->writeHTML($html, true, false, true, false, '');

        $fileName = 'relatorio_anpd_' . $startDate . '_' . $endDate . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setBody($pdf->Output($fileName, 'S'));
    }

    /**
     * Check user permission
     */
    protected function hasPermission(string $permission): bool
    {
        $role = session()->get('role');

        $permissions = [
            'admin' => ['view_lgpd_reports', 'manage_consents'],
            'dpo' => ['view_lgpd_reports', 'manage_consents'],
            'manager' => ['view_lgpd_reports'],
        ];

        return isset($permissions[$role]) && in_array($permission, $permissions[$role]);
    }
}
