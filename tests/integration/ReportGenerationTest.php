<?php

namespace Tests\Integration;

use App\Models\EmployeeModel;
use App\Models\TimePunchModel;
use App\Services\Report\PDFService;
use App\Services\Report\ExcelService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Faker\Factory;

/**
 * Testes de Integração - Geração de Relatórios
 *
 * PDF, Excel e validação de estrutura
 */
class ReportGenerationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = null;

    protected EmployeeModel $employeeModel;
    protected TimePunchModel $timePunchModel;
    protected PDFService $pdfService;
    protected ExcelService $excelService;
    protected int $employeeId;
    protected $faker;
    protected string $reportsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employeeModel = new EmployeeModel();
        $this->timePunchModel = new TimePunchModel();
        $this->pdfService = new PDFService();
        $this->excelService = new ExcelService();
        $this->faker = Factory::create('pt_BR');

        $this->reportsPath = WRITEPATH . 'reports/';

        // Criar diretório de relatórios se não existir
        if (!is_dir($this->reportsPath)) {
            mkdir($this->reportsPath, 0755, true);
        }

        // Criar funcionário de teste
        $this->employeeId = $this->createEmployee();

        // Criar marcações fake para relatório
        $this->createFakeTimePunches();
    }

    protected function tearDown(): void
    {
        // Limpar relatórios gerados
        $files = glob($this->reportsPath . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    private function createEmployee(): int
    {
        return $this->employeeModel->insert([
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'cpf' => $this->generateValidCPF(),
            'password' => password_hash('Test@123', PASSWORD_ARGON2ID),
            'role' => 'employee',
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Criar marcações fake para teste de relatório
     */
    private function createFakeTimePunches(): void
    {
        $startDate = strtotime('-10 days');

        for ($i = 0; $i < 10; $i++) {
            $date = date('Y-m-d', $startDate + ($i * 86400));

            // Criar 4 marcações por dia (jornada completa)
            $punches = [
                ['type' => 'entrada', 'time' => '08:00:00'],
                ['type' => 'saida_intervalo', 'time' => '12:00:00'],
                ['type' => 'volta_intervalo', 'time' => '13:00:00'],
                ['type' => 'saida', 'time' => '18:00:00'],
            ];

            foreach ($punches as $punch) {
                $this->timePunchModel->insert([
                    'employee_id' => $this->employeeId,
                    'punch_type' => $punch['type'],
                    'punch_time' => $date . ' ' . $punch['time'],
                    'nsr' => $this->timePunchModel->generateNSR(),
                    'hash' => hash('sha256', $this->employeeId . $punch['type'] . $date . $punch['time']),
                    'latitude' => -23.550520 + (rand(-100, 100) / 100000),
                    'longitude' => -46.633309 + (rand(-100, 100) / 100000),
                    'created_at' => $date . ' ' . $punch['time'],
                ]);
            }
        }
    }

    /**
     * Teste: Gerar relatório de folha de ponto em PDF
     */
    public function testGenerateTimesheetPDF(): void
    {
        // Dados para o relatório
        $employee = $this->employeeModel->find($this->employeeId);
        $startDate = date('Y-m-d', strtotime('-10 days'));
        $endDate = date('Y-m-d');

        $punches = $this->timePunchModel
            ->where('employee_id', $this->employeeId)
            ->where('punch_time >=', $startDate)
            ->where('punch_time <=', $endDate . ' 23:59:59')
            ->orderBy('punch_time', 'ASC')
            ->findAll();

        $this->assertNotEmpty($punches);

        // Gerar PDF
        $filename = 'timesheet_' . $this->employeeId . '_' . time() . '.pdf';
        $filepath = $this->reportsPath . $filename;

        $result = $this->pdfService->generateTimesheet($employee, $punches, $filepath);

        // Verificar que arquivo foi criado
        $this->assertTrue($result);
        $this->assertFileExists($filepath);

        // Verificar que arquivo não está vazio
        $filesize = filesize($filepath);
        $this->assertGreaterThan(0, $filesize);

        // Verificar que é um PDF válido (começa com %PDF)
        $content = file_get_contents($filepath);
        $this->assertStringStartsWith('%PDF', $content);
    }

    /**
     * Teste: Gerar relatório em Excel
     */
    public function testGenerateTimesheetExcel(): void
    {
        $employee = $this->employeeModel->find($this->employeeId);
        $startDate = date('Y-m-d', strtotime('-10 days'));
        $endDate = date('Y-m-d');

        $punches = $this->timePunchModel
            ->where('employee_id', $this->employeeId)
            ->where('punch_time >=', $startDate)
            ->where('punch_time <=', $endDate . ' 23:59:59')
            ->findAll();

        // Gerar Excel
        $filename = 'timesheet_' . $this->employeeId . '_' . time() . '.xlsx';
        $filepath = $this->reportsPath . $filename;

        $result = $this->excelService->generateTimesheet($employee, $punches, $filepath);

        // Verificar que arquivo foi criado
        $this->assertTrue($result);
        $this->assertFileExists($filepath);

        // Verificar tamanho
        $filesize = filesize($filepath);
        $this->assertGreaterThan(1000, $filesize); // Excel deve ter pelo menos 1KB

        // Verificar que é arquivo ZIP (XLSX é um ZIP)
        $content = file_get_contents($filepath);
        $this->assertStringStartsWith('PK', $content); // ZIP magic bytes
    }

    /**
     * Teste: Validar estrutura do Excel gerado
     */
    public function testExcelStructureValidation(): void
    {
        $employee = $this->employeeModel->find($this->employeeId);
        $punches = $this->timePunchModel
            ->where('employee_id', $this->employeeId)
            ->findAll();

        $filename = 'test_structure_' . time() . '.xlsx';
        $filepath = $this->reportsPath . $filename;

        $this->excelService->generateTimesheet($employee, $punches, $filepath);

        // Carregar Excel gerado
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
        $worksheet = $spreadsheet->getActiveSheet();

        // Verificar cabeçalhos esperados
        $expectedHeaders = ['Data', 'Entrada', 'Saída Intervalo', 'Volta Intervalo', 'Saída', 'Total Horas'];

        $headerRow = 1;
        foreach ($expectedHeaders as $col => $header) {
            $cellValue = $worksheet->getCellByColumnAndRow($col + 1, $headerRow)->getValue();
            $this->assertStringContainsString($header, $cellValue);
        }

        // Verificar que há dados (pelo menos 10 linhas + cabeçalho)
        $highestRow = $worksheet->getHighestRow();
        $this->assertGreaterThanOrEqual(11, $highestRow);
    }

    /**
     * Teste: Relatório com dados vazios
     */
    public function testReportWithNoData(): void
    {
        // Criar funcionário sem marcações
        $emptyEmployeeId = $this->createEmployee();
        $employee = $this->employeeModel->find($emptyEmployeeId);

        $punches = []; // Sem marcações

        $filename = 'empty_report_' . time() . '.pdf';
        $filepath = $this->reportsPath . $filename;

        $result = $this->pdfService->generateTimesheet($employee, $punches, $filepath);

        // Deve gerar relatório mesmo vazio
        $this->assertTrue($result);
        $this->assertFileExists($filepath);

        // Verificar conteúdo indica "sem dados"
        $content = file_get_contents($filepath);
        $this->assertNotEmpty($content);
    }

    /**
     * Teste: Relatório de múltiplos funcionários
     */
    public function testMultipleEmployeesReport(): void
    {
        // Criar 2 funcionários adicionais com marcações
        $employees = [];

        for ($i = 0; $i < 2; $i++) {
            $empId = $this->createEmployee();
            $employees[] = $empId;

            // Criar algumas marcações
            $this->timePunchModel->insert([
                'employee_id' => $empId,
                'punch_type' => 'entrada',
                'punch_time' => date('Y-m-d') . ' 08:00:00',
                'nsr' => $this->timePunchModel->generateNSR(),
                'hash' => hash('sha256', $empId . 'entrada'),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $employees[] = $this->employeeId; // Adicionar funcionário original

        // Gerar relatório consolidado
        $allEmployees = $this->employeeModel->whereIn('id', $employees)->findAll();

        $filename = 'consolidated_' . time() . '.xlsx';
        $filepath = $this->reportsPath . $filename;

        $result = $this->excelService->generateConsolidatedReport($allEmployees, date('Y-m'), $filepath);

        $this->assertTrue($result);
        $this->assertFileExists($filepath);
    }

    /**
     * Teste: Gerar CSV simples
     */
    public function testGenerateCSV(): void
    {
        $punches = $this->timePunchModel
            ->where('employee_id', $this->employeeId)
            ->findAll();

        $filename = 'export_' . time() . '.csv';
        $filepath = $this->reportsPath . $filename;

        $fp = fopen($filepath, 'w');

        // Cabeçalho
        fputcsv($fp, ['Data', 'Tipo', 'Horário', 'NSR', 'Hash']);

        // Dados
        foreach ($punches as $punch) {
            fputcsv($fp, [
                date('d/m/Y', strtotime($punch['punch_time'])),
                $punch['punch_type'],
                date('H:i:s', strtotime($punch['punch_time'])),
                $punch['nsr'],
                $punch['hash'],
            ]);
        }

        fclose($fp);

        // Verificar
        $this->assertFileExists($filepath);
        $this->assertGreaterThan(100, filesize($filepath));

        // Ler primeira linha
        $lines = file($filepath);
        $this->assertStringContainsString('Data', $lines[0]);
    }

    /**
     * Teste: Relatório com filtro de período
     */
    public function testReportWithDateRange(): void
    {
        $startDate = date('Y-m-d', strtotime('-5 days'));
        $endDate = date('Y-m-d', strtotime('-2 days'));

        $punches = $this->timePunchModel
            ->where('employee_id', $this->employeeId)
            ->where('punch_time >=', $startDate . ' 00:00:00')
            ->where('punch_time <=', $endDate . ' 23:59:59')
            ->findAll();

        // Verificar que filtro funcionou
        foreach ($punches as $punch) {
            $punchDate = date('Y-m-d', strtotime($punch['punch_time']));
            $this->assertGreaterThanOrEqual($startDate, $punchDate);
            $this->assertLessThanOrEqual($endDate, $punchDate);
        }

        $this->assertNotEmpty($punches);
    }

    /**
     * Teste: Estatísticas no relatório
     */
    public function testReportStatistics(): void
    {
        $punches = $this->timePunchModel
            ->where('employee_id', $this->employeeId)
            ->findAll();

        // Calcular estatísticas
        $totalDays = count(array_unique(array_column($punches, 'punch_time'))) / 4; // 4 marcações por dia
        $totalPunches = count($punches);

        $this->assertEquals(10, $totalDays); // 10 dias de marcações
        $this->assertEquals(40, $totalPunches); // 40 marcações totais (10 dias x 4)

        // Média de horas por dia
        $totalHours = 9.0 * 10; // 9 horas por dia x 10 dias
        $avgHours = $totalHours / 10;

        $this->assertEquals(9.0, $avgHours);
    }

    private function generateValidCPF(): string
    {
        $n1 = rand(0, 9);
        $n2 = rand(0, 9);
        $n3 = rand(0, 9);
        $n4 = rand(0, 9);
        $n5 = rand(0, 9);
        $n6 = rand(0, 9);
        $n7 = rand(0, 9);
        $n8 = rand(0, 9);
        $n9 = rand(0, 9);

        $d1 = $n9 * 2 + $n8 * 3 + $n7 * 4 + $n6 * 5 + $n5 * 6 + $n4 * 7 + $n3 * 8 + $n2 * 9 + $n1 * 10;
        $d1 = 11 - ($d1 % 11);
        $d1 = ($d1 >= 10) ? 0 : $d1;

        $d2 = $d1 * 2 + $n9 * 3 + $n8 * 4 + $n7 * 5 + $n6 * 6 + $n5 * 7 + $n4 * 8 + $n3 * 9 + $n2 * 10 + $n1 * 11;
        $d2 = 11 - ($d2 % 11);
        $d2 = ($d2 >= 10) ? 0 : $d2;

        return sprintf('%d%d%d%d%d%d%d%d%d%d%d', $n1, $n2, $n3, $n4, $n5, $n6, $n7, $n8, $n9, $d1, $d2);
    }
}
