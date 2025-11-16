<?php

namespace Tests\Unit\Models;

use App\Models\TimePunchModel;
use App\Models\EmployeeModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Testes Unitários para TimePunchModel
 *
 * Testa cálculo de horas, validação de marcações, e geração de NSR
 */
class TimePunchModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = null;

    protected TimePunchModel $timePunchModel;
    protected EmployeeModel $employeeModel;
    protected int $employeeId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->timePunchModel = new TimePunchModel();
        $this->employeeModel = new EmployeeModel();

        // Criar funcionário de teste
        $this->employeeId = $this->employeeModel->insert([
            'name' => 'Test Employee',
            'email' => 'employee@test.com',
            'cpf' => '12345678901',
            'password' => password_hash('Test@123', PASSWORD_ARGON2ID),
            'role' => 'employee',
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Teste: Cálculo de horas trabalhadas (entrada 08:00, saída 12:00 = 4h)
     */
    public function testCalculateHours(): void
    {
        $startTime = '2024-01-15 08:00:00';
        $endTime = '2024-01-15 12:00:00';

        $hours = $this->timePunchModel->calculateHours($startTime, $endTime);

        $this->assertEquals(4.0, $hours);
    }

    /**
     * Teste: Cálculo de horas com intervalos
     */
    public function testCalculateHoursWithBreak(): void
    {
        // Entrada: 08:00
        // Saída Intervalo: 12:00
        // Volta Intervalo: 13:00
        // Saída: 18:00
        // Total: 9 horas trabalhadas (4h manhã + 5h tarde)

        $morning = $this->timePunchModel->calculateHours(
            '2024-01-15 08:00:00',
            '2024-01-15 12:00:00'
        );

        $afternoon = $this->timePunchModel->calculateHours(
            '2024-01-15 13:00:00',
            '2024-01-15 18:00:00'
        );

        $total = $morning + $afternoon;

        $this->assertEquals(4.0, $morning);
        $this->assertEquals(5.0, $afternoon);
        $this->assertEquals(9.0, $total);
    }

    /**
     * Teste: Validação de pareamento - entrada sem saída = incompleto
     */
    public function testValidatePairing(): void
    {
        // Registrar apenas entrada
        $this->timePunchModel->insert([
            'employee_id' => $this->employeeId,
            'punch_type' => 'entrada',
            'punch_time' => '2024-01-15 08:00:00',
            'nsr' => $this->timePunchModel->generateNSR(),
            'hash' => 'test_hash_1',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $isComplete = $this->timePunchModel->isDayComplete($this->employeeId, '2024-01-15');

        $this->assertFalse($isComplete);
    }

    /**
     * Teste: Validação de pareamento completo
     */
    public function testValidatePairingComplete(): void
    {
        // Registrar entrada e saída
        $this->timePunchModel->insert([
            'employee_id' => $this->employeeId,
            'punch_type' => 'entrada',
            'punch_time' => '2024-01-15 08:00:00',
            'nsr' => $this->timePunchModel->generateNSR(),
            'hash' => 'test_hash_1',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->timePunchModel->insert([
            'employee_id' => $this->employeeId,
            'punch_type' => 'saida',
            'punch_time' => '2024-01-15 18:00:00',
            'nsr' => $this->timePunchModel->generateNSR(),
            'hash' => 'test_hash_2',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $isComplete = $this->timePunchModel->isDayComplete($this->employeeId, '2024-01-15');

        $this->assertTrue($isComplete);
    }

    /**
     * Teste: Geração de NSR (Numeração Sequencial de Registros) deve ser único e sequencial
     */
    public function testGenerateNSR(): void
    {
        $nsr1 = $this->timePunchModel->generateNSR();
        $nsr2 = $this->timePunchModel->generateNSR();
        $nsr3 = $this->timePunchModel->generateNSR();

        // Cada NSR deve ser único
        $this->assertNotEquals($nsr1, $nsr2);
        $this->assertNotEquals($nsr2, $nsr3);
        $this->assertNotEquals($nsr1, $nsr3);

        // NSR deve ser sequencial
        $this->assertEquals($nsr1 + 1, $nsr2);
        $this->assertEquals($nsr2 + 1, $nsr3);

        // NSR deve ser número inteiro
        $this->assertIsInt($nsr1);
        $this->assertGreaterThan(0, $nsr1);
    }

    /**
     * Teste: Hash SHA-256 deve ser gerado corretamente
     */
    public function testGenerateHash(): void
    {
        $data = [
            'employee_id' => $this->employeeId,
            'punch_type' => 'entrada',
            'punch_time' => '2024-01-15 08:00:00',
            'nsr' => 1001,
        ];

        $hash = $this->timePunchModel->generateHash($data);

        // Hash SHA-256 tem 64 caracteres hexadecimais
        $this->assertIsString($hash);
        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    /**
     * Teste: Hash deve ser único para dados diferentes
     */
    public function testHashUniqueness(): void
    {
        $data1 = [
            'employee_id' => $this->employeeId,
            'punch_type' => 'entrada',
            'punch_time' => '2024-01-15 08:00:00',
            'nsr' => 1001,
        ];

        $data2 = [
            'employee_id' => $this->employeeId,
            'punch_type' => 'entrada',
            'punch_time' => '2024-01-15 08:00:01', // 1 segundo de diferença
            'nsr' => 1002,
        ];

        $hash1 = $this->timePunchModel->generateHash($data1);
        $hash2 = $this->timePunchModel->generateHash($data2);

        $this->assertNotEquals($hash1, $hash2);
    }

    /**
     * Teste: Não permitir marcações com menos de 1 minuto de intervalo
     */
    public function testPreventDuplicatePunches(): void
    {
        $punchTime = '2024-01-15 08:00:00';

        // Primeira marcação
        $this->timePunchModel->insert([
            'employee_id' => $this->employeeId,
            'punch_type' => 'entrada',
            'punch_time' => $punchTime,
            'nsr' => $this->timePunchModel->generateNSR(),
            'hash' => $this->timePunchModel->generateHash(['test' => '1']),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Tentar registrar novamente 30 segundos depois
        $canPunch = $this->timePunchModel->canPunch(
            $this->employeeId,
            '2024-01-15 08:00:30'
        );

        $this->assertFalse($canPunch);
    }

    /**
     * Teste: Permitir marcação após 1 minuto
     */
    public function testAllowPunchAfterOneMinute(): void
    {
        $punchTime = '2024-01-15 08:00:00';

        // Primeira marcação
        $this->timePunchModel->insert([
            'employee_id' => $this->employeeId,
            'punch_type' => 'entrada',
            'punch_time' => $punchTime,
            'nsr' => $this->timePunchModel->generateNSR(),
            'hash' => $this->timePunchModel->generateHash(['test' => '1']),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Marcar 2 minutos depois
        $canPunch = $this->timePunchModel->canPunch(
            $this->employeeId,
            '2024-01-15 08:02:00'
        );

        $this->assertTrue($canPunch);
    }

    /**
     * Teste: Máximo de 4 marcações por dia (entrada, saída intervalo, volta, saída)
     */
    public function testMaximumPunchesPerDay(): void
    {
        $date = '2024-01-15';

        // Registrar 4 marcações
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
                'hash' => $this->timePunchModel->generateHash(['test' => $punch['type']]),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // Verificar se já atingiu o máximo
        $punchCount = $this->timePunchModel->getPunchCountForDay($this->employeeId, $date);

        $this->assertEquals(4, $punchCount);

        // Não deve permitir 5ª marcação
        $canPunchAgain = $this->timePunchModel->canPunchToday($this->employeeId, $date);

        $this->assertFalse($canPunchAgain);
    }

    /**
     * Teste: Detectar tipo de marcação baseado no último registro
     */
    public function testDetectPunchType(): void
    {
        // Sem marcações, deve ser 'entrada'
        $type = $this->timePunchModel->detectNextPunchType($this->employeeId, '2024-01-15');
        $this->assertEquals('entrada', $type);

        // Após entrada, deve ser 'saida_intervalo' ou 'saida'
        $this->timePunchModel->insert([
            'employee_id' => $this->employeeId,
            'punch_type' => 'entrada',
            'punch_time' => '2024-01-15 08:00:00',
            'nsr' => $this->timePunchModel->generateNSR(),
            'hash' => 'test_hash',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $type = $this->timePunchModel->detectNextPunchType($this->employeeId, '2024-01-15');
        $this->assertContains($type, ['saida_intervalo', 'saida']);
    }

    /**
     * Teste: Obter última marcação do funcionário
     */
    public function testGetLastPunch(): void
    {
        $punchTime = '2024-01-15 08:00:00';

        $this->timePunchModel->insert([
            'employee_id' => $this->employeeId,
            'punch_type' => 'entrada',
            'punch_time' => $punchTime,
            'nsr' => $this->timePunchModel->generateNSR(),
            'hash' => 'test_hash',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $lastPunch = $this->timePunchModel->getLastPunch($this->employeeId, '2024-01-15');

        $this->assertIsArray($lastPunch);
        $this->assertEquals('entrada', $lastPunch['punch_type']);
        $this->assertEquals($punchTime, $lastPunch['punch_time']);
    }
}
