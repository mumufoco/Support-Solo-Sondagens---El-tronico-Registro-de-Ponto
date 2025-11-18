<?php

namespace App\Controllers\Warning;

use App\Controllers\BaseController;
use App\Models\EmployeeModel;
use App\Models\WarningModel;
use App\Models\AuditLogModel;
use App\Services\NotificationService;
use App\Services\WarningPDFService;
use App\Services\SMSService;

/**
 * Warning Controller
 *
 * Handles employee warnings/disciplinary actions (Managers/Admins only)
 * Complies with CLT Art. 482 and company regulations
 */
class WarningController extends BaseController
{
    protected EmployeeModel $employeeModel;
    protected WarningModel $warningModel;
    protected AuditLogModel $auditModel;
    protected NotificationService $notificationService;
    protected WarningPDFService $pdfService;
    protected SMSService $smsService;

    public function __construct()
    {
        $this->employeeModel = new EmployeeModel();
        $this->warningModel = new WarningModel();
        $this->auditModel = new AuditLogModel();
        $this->notificationService = new NotificationService();
        $this->pdfService = new WarningPDFService();
        $this->smsService = new SMSService();
        helper(['form', 'datetime', 'format']);
    }

    /**
     * List warnings
     * GET /warnings
     */
    public function index()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || !in_array($employee['role'], ['admin', 'gestor'])) {
            return redirect()->to('/dashboard')
                ->with('error', 'Acesso negado. Apenas gestores e administradores podem acessar advertências.');
        }

        $perPage = 20;
        $warningType = $this->request->getGet('warning_type') ?? 'all';
        $status = $this->request->getGet('status') ?? 'all';

        // Build query
        $query = $this->warningModel;

        // Filter by department (managers)
        if ($employee['role'] === 'gestor') {
            $employeeIds = $this->employeeModel
                ->where('department', $employee['department'])
                ->findColumn('id');
            $query->whereIn('employee_id', $employeeIds);
        }

        // Filter by warning type
        if ($warningType !== 'all') {
            $query->where('warning_type', $warningType);
        }

        // Filter by status
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $warnings = $query->orderBy('occurrence_date', 'DESC')
            ->paginate($perPage);

        // Get employee names
        foreach ($warnings as &$warning) {
            $emp = $this->employeeModel->find($warning->employee_id);
            $warning->employee_name = $emp ? $emp->name : 'Desconhecido';

            $issuer = $this->employeeModel->find($warning->issued_by);
            $warning->issuer_name = $issuer ? $issuer->name : 'Desconhecido';
        }

        // Count by type and status
        $counts = [
            'all' => $this->warningModel->countAllResults(false),
            'verbal' => $this->warningModel->where('warning_type', 'verbal')->countAllResults(false),
            'escrita' => $this->warningModel->where('warning_type', 'escrita')->countAllResults(false),
            'suspensao' => $this->warningModel->where('warning_type', 'suspensao')->countAllResults(false),
            'pendente' => $this->warningModel->where('status', 'pendente-assinatura')->countAllResults(false),
            'assinado' => $this->warningModel->where('status', 'assinado')->countAllResults(false),
            'recusado' => $this->warningModel->where('status', 'recusado')->countAllResults(false),
        ];

        return view('warnings/index', [
            'employee' => $employee,
            'warnings' => $warnings,
            'pager' => $this->warningModel->pager,
            'warningType' => $warningType,
            'status' => $status,
            'counts' => $counts,
        ]);
    }

    /**
     * Show create form
     * GET /warnings/create
     */
    public function create()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || !in_array($employee['role'], ['admin', 'gestor'])) {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        // Get employees (based on role)
        $query = $this->employeeModel->where('active', true);

        if ($employee['role'] === 'gestor') {
            $query->where('department', $employee['department']);
        }

        $employees = $query->orderBy('name', 'ASC')->findAll();

        return view('warnings/create', [
            'employee' => $employee,
            'employees' => $employees,
        ]);
    }

    /**
     * Store new warning
     * POST /warnings
     */
    public function store()
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || !in_array($employee['role'], ['admin', 'gestor'])) {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        // Validation rules
        $rules = [
            'employee_id' => 'required|integer',
            'warning_type' => 'required|in_list[verbal,escrita,suspensao]',
            'occurrence_date' => 'required|valid_date',
            'reason' => 'required|min_length[50]|max_length[5000]',
            'evidence_files.*' => 'permit_empty|max_size[evidence_files.*,10240]|ext_in[evidence_files.*,pdf,jpg,jpeg,png,doc,docx]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $employeeId = $this->request->getPost('employee_id');

        // Check if manager can warn this employee
        if ($employee['role'] === 'gestor') {
            $targetEmployee = $this->employeeModel->find($employeeId);
            if (!$targetEmployee || $targetEmployee->department !== $employee['department']) {
                return redirect()->back()
                    ->with('error', 'Você só pode advertir funcionários do seu departamento.');
            }
        }

        // Check if employee is at limit (3 warnings)
        if ($this->warningModel->isAtLimit($employeeId)) {
            return redirect()->back()
                ->with('warning', 'ATENÇÃO: Este funcionário já possui 3 advertências. Considere medidas adicionais.');
        }

        // Handle evidence file uploads
        $evidenceFiles = [];
        $files = $this->request->getFiles();

        if (isset($files['evidence_files'])) {
            $uploadPath = WRITEPATH . 'uploads/warnings/evidence/';

            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $fileCount = 0;
            foreach ($files['evidence_files'] as $file) {
                if ($file->isValid() && !$file->hasMoved() && $fileCount < 5) {
                    $newName = $file->getRandomName();
                    $file->move($uploadPath, $newName);
                    $evidenceFiles[] = 'uploads/warnings/evidence/' . $newName;
                    $fileCount++;
                }
            }
        }

        // Create warning
        $data = [
            'employee_id' => $employeeId,
            'issued_by' => $employee['id'],
            'warning_type' => $this->request->getPost('warning_type'),
            'occurrence_date' => $this->request->getPost('occurrence_date'),
            'reason' => $this->request->getPost('reason'),
            'evidence_files' => $evidenceFiles,
            'status' => 'pendente-assinatura',
        ];

        $warningId = $this->warningModel->insert($data);

        if (!$warningId) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao criar advertência.');
        }

        // Generate PDF with formal template
        $targetEmployee = $this->employeeModel->find($employeeId);
        $issuer = $this->employeeModel->find($employee['id']);

        $pdfResult = $this->pdfService->generateWarningPDF($warningId, [
            'warning' => $this->warningModel->find($warningId),
            'employee' => $targetEmployee,
            'issuer' => $issuer,
        ]);

        if ($pdfResult['success']) {
            // Sign PDF with ICP-Brasil certificate of issuer
            $signedPdf = $this->pdfService->signPDFWithICP($pdfResult['filepath'], $employee['id']);

            if ($signedPdf['success']) {
                // Update warning with PDF path
                $this->warningModel->update($warningId, [
                    'pdf_path' => $signedPdf['filepath']
                ]);
            }
        }

        // Log warning
        $this->auditModel->log(
            $employee['id'],
            'WARNING_ISSUED',
            'warnings',
            $warningId,
            null,
            $data,
            "Advertência {$data['warning_type']} emitida para funcionário ID {$employeeId}",
            'warning'
        );

        // Send notification to employee with link to sign
        if ($targetEmployee) {
            $this->notificationService->create(
                $employeeId,
                'Nova Advertência Recebida',
                "Você recebeu uma advertência ({$data['warning_type']}). Clique para visualizar e assinar o documento.",
                'danger',
                '/warnings/' . $warningId . '/sign'
            );

            // Send email
            $email = \Config\Services::email();
            $email->setTo($targetEmployee->email);
            $email->setSubject('Advertência - Assinatura Necessária');
            $email->setMessage(view('emails/warning_notification', [
                'employee' => $targetEmployee,
                'warning_type' => $data['warning_type'],
                'link' => base_url('/warnings/' . $warningId . '/sign')
            ]));
            $email->send();
        }

        return redirect()->to('/warnings')
            ->with('success', 'Advertência emitida com sucesso. Notificação enviada ao funcionário.');
    }

    /**
     * Show warning details
     * GET /warnings/{id}
     */
    public function show($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return redirect()->to('/login')->with('error', 'Você precisa estar autenticado.');
        }

        $warning = $this->warningModel->find($id);

        if (!$warning) {
            return redirect()->to('/warnings')
                ->with('error', 'Advertência não encontrada.');
        }

        // Check permissions
        if ($employee['role'] === 'funcionario' && $warning->employee_id !== $employee['id']) {
            return redirect()->to('/dashboard')
                ->with('error', 'Acesso negado.');
        }

        if ($employee['role'] === 'gestor') {
            $warningEmployee = $this->employeeModel->find($warning->employee_id);
            if ($warningEmployee->department !== $employee['department']) {
                return redirect()->to('/warnings')
                    ->with('error', 'Acesso negado.');
            }
        }

        // Get employee and issuer data
        $warningEmployee = $this->employeeModel->find($warning->employee_id);
        $issuer = $this->employeeModel->find($warning->issued_by);

        // Check if 48h passed without signature
        $hoursElapsed = 0;
        if ($warning->status === 'pendente-assinatura') {
            $createdTime = strtotime($warning->created_at);
            $hoursElapsed = (time() - $createdTime) / 3600;
        }

        return view('warnings/show', [
            'employee' => $employee,
            'warning' => $warning,
            'warningEmployee' => $warningEmployee,
            'issuer' => $issuer,
            'hoursElapsed' => $hoursElapsed,
            'canAddWitness' => $hoursElapsed >= 48 && $warning->status === 'pendente-assinatura',
        ]);
    }

    /**
     * Show sign form (Employee)
     * GET /warnings/{id}/sign
     */
    public function signForm($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return redirect()->to('/login')->with('error', 'Você precisa estar autenticado.');
        }

        $warning = $this->warningModel->find($id);

        if (!$warning) {
            return redirect()->to('/warnings')
                ->with('error', 'Advertência não encontrada.');
        }

        // Only the warned employee can sign
        if ($warning->employee_id !== $employee['id']) {
            return redirect()->to('/warnings/' . $id)
                ->with('error', 'Apenas o funcionário advertido pode assinar a advertência.');
        }

        if ($warning->status !== 'pendente-assinatura') {
            return redirect()->to('/warnings/' . $id)
                ->with('info', 'Esta advertência já foi processada.');
        }

        $warningEmployee = $this->employeeModel->find($warning->employee_id);
        $issuer = $this->employeeModel->find($warning->issued_by);

        return view('warnings/sign', [
            'employee' => $employee,
            'warning' => $warning,
            'warningEmployee' => $warningEmployee,
            'issuer' => $issuer,
        ]);
    }

    /**
     * Sign warning (Employee)
     * POST /warnings/{id}/sign
     */
    public function sign($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return redirect()->to('/login')->with('error', 'Você precisa estar autenticado.');
        }

        $warning = $this->warningModel->find($id);

        if (!$warning) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Advertência não encontrada.'
            ]);
        }

        // Only the warned employee can sign
        if ($warning->employee_id !== $employee['id']) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Apenas o funcionário advertido pode assinar.'
            ]);
        }

        if ($warning->status !== 'pendente-assinatura') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Esta advertência já foi processada.'
            ]);
        }

        // Check if terms were accepted
        if (!$this->request->getPost('terms_accepted')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Você deve aceitar os termos para assinar.'
            ]);
        }

        $signatureMethod = $this->request->getPost('signature_method');

        if ($signatureMethod === 'icp') {
            // ICP-Brasil certificate signature
            $certificateFile = $this->request->getFile('certificate');
            $certificatePassword = $this->request->getPost('certificate_password');

            if (!$certificateFile || !$certificateFile->isValid()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Certificado ICP-Brasil inválido.'
                ]);
            }

            // Validate and sign PDF
            $signResult = $this->pdfService->signPDFWithICPUpload(
                $warning->pdf_path,
                $certificateFile,
                $certificatePassword,
                $employee['id']
            );

            if (!$signResult['success']) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Erro ao assinar com certificado ICP: ' . $signResult['error']
                ]);
            }

            $signature = 'ICP-Brasil: ' . $signResult['certificate_name'];

        } elseif ($signatureMethod === 'sms') {
            // SMS verification code
            $smsCode = $this->request->getPost('sms_code');

            if (!$smsCode) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Código SMS é obrigatório.'
                ]);
            }

            // Verify SMS code
            $verifyResult = $this->smsService->verifyCode($employee['id'], $smsCode);

            if (!$verifyResult['success']) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Código SMS inválido ou expirado.'
                ]);
            }

            $signature = 'Assinatura Eletrônica (SMS) - Código verificado em ' . date('Y-m-d H:i:s');

        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Método de assinatura inválido.'
            ]);
        }

        // Update warning
        $this->warningModel->sign($id, $signature);

        // Generate final PDF with both signatures
        $targetEmployee = $this->employeeModel->find($warning->employee_id);
        $issuer = $this->employeeModel->find($warning->issued_by);

        $this->pdfService->generateFinalPDF($id, [
            'warning' => $this->warningModel->find($id),
            'employee' => $targetEmployee,
            'issuer' => $issuer,
        ]);

        // Log signing
        $this->auditModel->log(
            $employee['id'],
            'WARNING_SIGNED',
            'warnings',
            $id,
            ['status' => 'pendente-assinatura'],
            ['status' => 'assinado', 'signature' => $signature],
            "Advertência ID {$id} assinada pelo funcionário",
            'info'
        );

        // Notify issuer
        $this->notificationService->create(
            $warning->issued_by,
            'Advertência Assinada',
            "{$employee['name']} assinou a advertência emitida.",
            'success',
            '/warnings/' . $id
        );

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Advertência assinada com sucesso.'
        ]);
    }

    /**
     * Send SMS code for signature
     * POST /warnings/{id}/send-sms
     */
    public function sendSMSCode($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Não autenticado.'
            ]);
        }

        $warning = $this->warningModel->find($id);

        if (!$warning || $warning->employee_id !== $employee['id']) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Advertência não encontrada ou sem permissão.'
            ]);
        }

        // Get employee phone
        $targetEmployee = $this->employeeModel->find($employee['id']);

        if (!$targetEmployee->phone) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Número de telefone não cadastrado.'
            ]);
        }

        // Send SMS code
        $result = $this->smsService->sendVerificationCode($employee['id'], $targetEmployee->phone);

        return $this->response->setJSON($result);
    }

    /**
     * Show add witness form (Manager/Admin after 48h)
     * GET /warnings/{id}/add-witness
     */
    public function addWitnessForm($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || !in_array($employee['role'], ['admin', 'gestor'])) {
            return redirect()->to('/dashboard')->with('error', 'Acesso negado.');
        }

        $warning = $this->warningModel->find($id);

        if (!$warning) {
            return redirect()->to('/warnings')
                ->with('error', 'Advertência não encontrada.');
        }

        // Check if 48h has passed
        $createdTime = strtotime($warning->created_at);
        $hoursElapsed = (time() - $createdTime) / 3600;

        if ($hoursElapsed < 48) {
            return redirect()->to('/warnings/' . $id)
                ->with('error', 'Testemunha só pode ser adicionada após 48 horas sem assinatura.');
        }

        if ($warning->status !== 'pendente-assinatura') {
            return redirect()->to('/warnings/' . $id)
                ->with('info', 'Esta advertência já foi processada.');
        }

        $warningEmployee = $this->employeeModel->find($warning->employee_id);
        $issuer = $this->employeeModel->find($warning->issued_by);

        return view('warnings/add_witness', [
            'employee' => $employee,
            'warning' => $warning,
            'warningEmployee' => $warningEmployee,
            'issuer' => $issuer,
        ]);
    }

    /**
     * Refuse signature with witness (After 48h)
     * POST /warnings/{id}/refuse-signature
     */
    public function refuseSignature($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || !in_array($employee['role'], ['admin', 'gestor'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Acesso negado.'
            ]);
        }

        $warning = $this->warningModel->find($id);

        if (!$warning) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Advertência não encontrada.'
            ]);
        }

        // Check if 48h has passed
        $createdTime = strtotime($warning->created_at);
        $hoursElapsed = (time() - $createdTime) / 3600;

        if ($hoursElapsed < 48) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Testemunha só pode ser adicionada após 48 horas.'
            ]);
        }

        // Validation
        $rules = [
            'witness_name' => 'required|min_length[3]|max_length[255]',
            'witness_cpf' => 'required|exact_length[14]',
            'witness_signature' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Dados da testemunha inválidos.',
                'errors' => $this->validator->getErrors()
            ]);
        }

        // Update warning with witness
        $this->warningModel->refuseSignature(
            $id,
            $this->request->getPost('witness_name'),
            $this->request->getPost('witness_cpf'),
            $this->request->getPost('witness_signature')
        );

        // Generate final PDF with witness signature
        $targetEmployee = $this->employeeModel->find($warning->employee_id);
        $issuer = $this->employeeModel->find($warning->issued_by);

        $this->pdfService->generateFinalPDF($id, [
            'warning' => $this->warningModel->find($id),
            'employee' => $targetEmployee,
            'issuer' => $issuer,
        ]);

        // Log refusal
        $this->auditModel->log(
            $employee['id'],
            'WARNING_REFUSED',
            'warnings',
            $id,
            ['status' => 'pendente-assinatura'],
            ['status' => 'recusado'],
            "Advertência ID {$id} marcada como recusada com testemunha",
            'warning'
        );

        // Notify HR/Admin
        $admins = $this->employeeModel->where('role', 'admin')->findAll();
        foreach ($admins as $admin) {
            $this->notificationService->create(
                $admin->id,
                'Advertência Recusada',
                "Funcionário {$targetEmployee->name} recusou advertência. Testemunha adicionada.",
                'warning',
                '/warnings/' . $id
            );
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Testemunha adicionada. Advertência marcada como recusada.'
        ]);
    }

    /**
     * Show employee warning dashboard with timeline
     * GET /warnings/dashboard/{employeeId}
     */
    public function dashboard($employeeId = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return redirect()->to('/login')->with('error', 'Você precisa estar autenticado.');
        }

        // If no employee ID, show current user's dashboard
        if (!$employeeId) {
            $employeeId = $employee['id'];
        }

        $targetEmployee = $this->employeeModel->find($employeeId);

        if (!$targetEmployee) {
            return redirect()->to('/dashboard')
                ->with('error', 'Funcionário não encontrado.');
        }

        // Check permissions
        if ($employee['role'] === 'funcionario' && $employeeId !== $employee['id']) {
            return redirect()->to('/dashboard')
                ->with('error', 'Acesso negado.');
        }

        if ($employee['role'] === 'gestor' && $targetEmployee->department !== $employee['department']) {
            return redirect()->to('/warnings')
                ->with('error', 'Acesso negado.');
        }

        // Get warnings timeline
        $timeline = $this->warningModel->getTimeline($employeeId);
        $totalWarnings = $this->warningModel->getTotalWarnings($employeeId);
        $warningsByType = [
            'verbal' => $this->warningModel->getCountByType($employeeId, 'verbal'),
            'escrita' => $this->warningModel->getCountByType($employeeId, 'escrita'),
            'suspensao' => $this->warningModel->getCountByType($employeeId, 'suspensao'),
        ];

        $atLimit = $this->warningModel->isAtLimit($employeeId);

        return view('warnings/dashboard', [
            'employee' => $employee,
            'targetEmployee' => $targetEmployee,
            'timeline' => $timeline,
            'totalWarnings' => $totalWarnings,
            'warningsByType' => $warningsByType,
            'atLimit' => $atLimit,
        ]);
    }

    /**
     * Download warning PDF
     * GET /warnings/{id}/download
     */
    public function downloadPDF($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee) {
            return redirect()->to('/login')->with('error', 'Você precisa estar autenticado.');
        }

        $warning = $this->warningModel->find($id);

        if (!$warning || !$warning->pdf_path) {
            return redirect()->to('/warnings')
                ->with('error', 'PDF não encontrado.');
        }

        // Check permissions
        if ($employee['role'] === 'funcionario' && $warning->employee_id !== $employee['id']) {
            return redirect()->to('/dashboard')
                ->with('error', 'Acesso negado.');
        }

        $filepath = WRITEPATH . $warning->pdf_path;

        if (!file_exists($filepath)) {
            return redirect()->to('/warnings/' . $id)
                ->with('error', 'Arquivo PDF não encontrado.');
        }

        return $this->response->download($filepath, null)->setFileName('advertencia_' . $id . '.pdf');
    }

    /**
     * Delete warning (Admin only)
     * DELETE /warnings/{id}
     */
    public function delete($id = null)
    {
        $employee = $this->getAuthenticatedEmployee();

        if (!$employee || $employee['role'] !== 'admin') {
            return redirect()->to('/dashboard')
                ->with('error', 'Apenas administradores podem excluir advertências.');
        }

        $warning = $this->warningModel->find($id);

        if (!$warning) {
            return redirect()->to('/warnings')
                ->with('error', 'Advertência não encontrada.');
        }

        // Delete evidence files
        if (!empty($warning->evidence_files)) {
            foreach ($warning->evidence_files as $file) {
                $filepath = WRITEPATH . $file;
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
            }
        }

        // Delete PDF
        if ($warning->pdf_path) {
            $filepath = WRITEPATH . $warning->pdf_path;
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }

        // Log deletion
        $this->auditModel->log(
            $employee['id'],
            'WARNING_DELETED',
            'warnings',
            $id,
            (array) $warning,
            null,
            "Advertência ID {$id} excluída",
            'critical'
        );

        $this->warningModel->delete($id);

        return redirect()->to('/warnings')
            ->with('success', 'Advertência excluída com sucesso.');
    }

    /**
     * Get authenticated employee from session
     */
    protected function getAuthenticatedEmployee(): ?array
    {
        if (!session()->has('employee_id')) {
            return null;
        }

        $employeeId = session()->get('employee_id');
        $employee = $this->employeeModel->find($employeeId);

        if (!$employee) {
            return null;
        }

        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'email' => $employee->email,
            'role' => $employee->role,
            'department' => $employee->department,
        ];
    }
}
