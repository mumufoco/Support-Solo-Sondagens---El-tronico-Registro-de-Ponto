<?php

namespace App\Services\Queue;

use App\Models\ReportQueueModel;
use App\Models\EmployeeModel;
use App\Services\ReportService;
use App\Services\EmailService;
use Exception;

/**
 * Report Queue Service
 *
 * Manages background processing of large reports
 */
class ReportQueueService
{
    protected ReportQueueModel $queueModel;
    protected ReportService $reportService;
    protected EmailService $emailService;
    protected EmployeeModel $employeeModel;

    public function __construct()
    {
        $this->queueModel = new ReportQueueModel();
        $this->reportService = new ReportService();
        $this->emailService = new EmailService();
        $this->employeeModel = new EmployeeModel();
    }

    /**
     * Add report to queue
     *
     * @param int $employeeId Employee requesting the report
     * @param string $reportType Type of report
     * @param string $format Output format (pdf, excel, csv)
     * @param array $filters Report filters
     * @return array{success: bool, job_id?: string, error?: string}
     */
    public function enqueue(
        int $employeeId,
        string $reportType,
        string $format,
        array $filters = []
    ): array {
        try {
            $jobData = [
                'employee_id'   => $employeeId,
                'report_type'   => $reportType,
                'report_format' => $format,
                'filters'       => json_encode($filters),
                'status'        => 'pending',
            ];

            $jobId = $this->queueModel->insert($jobData);

            if (!$jobId) {
                return [
                    'success' => false,
                    'error'   => 'Failed to enqueue report job',
                ];
            }

            // Get the generated job_id
            $job = $this->queueModel->find($jobId);

            log_message('info', "Report queued: {$job->job_id} for employee {$employeeId}");

            return [
                'success' => true,
                'job_id'  => $job->job_id,
            ];
        } catch (Exception $e) {
            log_message('error', 'Failed to enqueue report: ' . $e->getMessage());

            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Process next pending job from queue
     *
     * @return array{success: bool, processed: bool, job_id?: string, error?: string}
     */
    public function processNext(): array
    {
        $jobs = $this->queueModel->getPendingJobs(1);

        if (empty($jobs)) {
            return [
                'success'   => true,
                'processed' => false,
                'message'   => 'No pending jobs',
            ];
        }

        $job = $jobs[0];

        return $this->processJob($job);
    }

    /**
     * Process all pending jobs
     *
     * @param int $maxJobs Maximum number of jobs to process
     * @return array{success: bool, processed: int, failed: int}
     */
    public function processAll(int $maxJobs = 10): array
    {
        $jobs = $this->queueModel->getPendingJobs($maxJobs);

        $processed = 0;
        $failed = 0;

        foreach ($jobs as $job) {
            $result = $this->processJob($job);

            if ($result['success']) {
                $processed++;
            } else {
                $failed++;
            }
        }

        return [
            'success'   => true,
            'processed' => $processed,
            'failed'    => $failed,
        ];
    }

    /**
     * Process a specific job
     *
     * @param object $job Job object from database
     * @return array{success: bool, job_id: string, error?: string}
     */
    protected function processJob(object $job): array
    {
        try {
            log_message('info', "Processing report job: {$job->job_id}");

            // Mark as processing
            $this->queueModel->markAsProcessing($job->job_id);

            // Decode filters
            $filters = json_decode($job->filters, true) ?? [];

            // Update progress: 10%
            $this->queueModel->updateProgress($job->job_id, 10);

            // Generate report data
            $result = $this->reportService->generateReportData($job->report_type, $filters);

            if (!$result['success']) {
                throw new Exception($result['error'] ?? 'Failed to generate report data');
            }

            // Update progress: 50%
            $this->queueModel->updateProgress($job->job_id, 50);

            $data = $result['data'];

            // Generate output file
            $filePath = $this->generateOutputFile(
                $job->report_type,
                $data,
                $job->report_format,
                $filters,
                $job->job_id
            );

            // Update progress: 90%
            $this->queueModel->updateProgress($job->job_id, 90);

            // Mark as completed
            $this->queueModel->markAsCompleted($job->job_id, $filePath);

            // Send notification email
            $this->sendCompletionEmail($job, $filePath);

            log_message('info', "Report job completed: {$job->job_id}");

            return [
                'success' => true,
                'job_id'  => $job->job_id,
            ];
        } catch (Exception $e) {
            log_message('error', "Report job failed: {$job->job_id} - " . $e->getMessage());

            $this->queueModel->markAsFailed($job->job_id, $e->getMessage());

            // Send failure notification
            $this->sendFailureEmail($job, $e->getMessage());

            return [
                'success' => false,
                'job_id'  => $job->job_id,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate output file
     *
     * @param string $type Report type
     * @param array $data Report data
     * @param string $format Output format
     * @param array $filters Filters applied
     * @param string $jobId Job ID for filename
     * @return string Path to generated file
     */
    protected function generateOutputFile(
        string $type,
        array $data,
        string $format,
        array $filters,
        string $jobId
    ): string {
        $outputDir = WRITEPATH . 'reports/';

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $filename = "{$type}_{$jobId}.{$format}";
        $filePath = $outputDir . $filename;

        switch ($format) {
            case 'pdf':
                $this->reportService->generatePDF($type, $data, $filters, $filePath);
                break;

            case 'excel':
                $this->reportService->generateExcel($type, $data, $filters, $filePath);
                break;

            case 'csv':
                $this->reportService->generateCSV($type, $data, $filters, $filePath);
                break;

            default:
                throw new Exception("Unsupported format: {$format}");
        }

        return $filePath;
    }

    /**
     * Send completion notification email
     *
     * @param object $job
     * @param string $filePath
     */
    protected function sendCompletionEmail(object $job, string $filePath): void
    {
        try {
            $employee = $this->employeeModel->find($job->employee_id);

            if (!$employee || !$employee->email) {
                return;
            }

            $downloadUrl = base_url("reports/download/{$job->job_id}");

            $subject = "Relatório Pronto para Download";
            $message = "
                <h2>Seu relatório está pronto!</h2>
                <p>O relatório <strong>{$job->report_type}</strong> que você solicitou foi gerado com sucesso.</p>
                <p><a href=\"{$downloadUrl}\" style=\"background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;\">Baixar Relatório</a></p>
                <p><small>Este link expirará em 7 dias.</small></p>
            ";

            $this->emailService->send($employee->email, $subject, $message);
        } catch (Exception $e) {
            log_message('warning', 'Failed to send completion email: ' . $e->getMessage());
        }
    }

    /**
     * Send failure notification email
     *
     * @param object $job
     * @param string $errorMessage
     */
    protected function sendFailureEmail(object $job, string $errorMessage): void
    {
        try {
            $employee = $this->employeeModel->find($job->employee_id);

            if (!$employee || !$employee->email) {
                return;
            }

            $subject = "Erro ao Gerar Relatório";
            $message = "
                <h2>Erro ao gerar relatório</h2>
                <p>Infelizmente houve um erro ao gerar o relatório <strong>{$job->report_type}</strong>.</p>
                <p><strong>Erro:</strong> {$errorMessage}</p>
                <p>Por favor, entre em contato com o suporte ou tente novamente.</p>
            ";

            $this->emailService->send($employee->email, $subject, $message);
        } catch (Exception $e) {
            log_message('warning', 'Failed to send failure email: ' . $e->getMessage());
        }
    }

    /**
     * Get job status
     *
     * @param string $jobId
     * @return array|null
     */
    public function getStatus(string $jobId): ?array
    {
        $job = $this->queueModel->getByJobId($jobId);

        if (!$job) {
            return null;
        }

        return [
            'job_id'       => $job->job_id,
            'status'       => $job->status,
            'progress'     => $job->progress,
            'created_at'   => $job->created_at,
            'started_at'   => $job->started_at,
            'completed_at' => $job->completed_at,
            'error'        => $job->error_message,
            'download_url' => $job->status === 'completed'
                ? base_url("reports/download/{$job->job_id}")
                : null,
        ];
    }
}
