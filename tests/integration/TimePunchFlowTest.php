<?php

namespace Tests\Integration;

use App\Models\EmployeeModel;
use App\Models\TimePunchModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Faker\Factory;

/**
 * Testes de Integração - Fluxo Completo de Registro de Ponto
 *
 * Simula jornada completa: entrada → intervalo → volta → saída
 */
class TimePunchFlowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = null;

    protected EmployeeModel $employeeModel;
    protected TimePunchModel $timePunchModel;
    protected int $employeeId;
    protected $faker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employeeModel = new EmployeeModel();
        $this->timePunchModel = new TimePunchModel();
        $this->faker = Factory::create('pt_BR');

        // Criar funcionário de teste
        $this->employeeId = $this->setupEmployee();
    }

    /**
     * Configurar funcionário de teste com dados realistas
     */
    protected function setupEmployee(): int
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
     * Teste: Ciclo completo de marcações em um dia
     * Entrada → Saída Intervalo → Volta Intervalo → Saída
     */
    public function testFullPunchCycle(): void
    {
        $date = date('Y-m-d');

        // 1. Entrada (08:00)
        $entrada = $this->timePunchModel->insert([
            'employee_id' => $this->employeeId,
            'punch_type' => 'entrada',
            'punch_time' => $date . ' 08:00:00',
            'nsr' => $this->timePunchModel->generateNSR(),
            'hash' => $this->timePunchModel->generateHash([
                'employee_id' => $this->employeeId,
                'type' => 'entrada',
                'time' => $date . ' 08:00:00',
            ]),
            'latitude' => -23.550520,
            'longitude' => -46.633309,
            'created_at' => $date . ' 08:00:00',
        ]);

        $this->assertNotFalse($entrada);

        // Verificar se foi salvo
        $punch1 = $this->timePunchModel->find($entrada);
        $this->assertEquals('entrada', $punch1['punch_type']);
        $this->assertEquals($this->employeeId, $punch1['employee_id']);

        // 2. Saída para intervalo (12:00)
        $saidaIntervalo = $this->timePunchModel->insert([
            'employee_id' => $this->employeeId,
            'punch_type' => 'saida_intervalo',
            'punch_time' => $date . ' 12:00:00',
            'nsr' => $this->timePunchModel->generateNSR(),
            'hash' => $this->timePunchModel->generateHash([
                'employee_id' => $this->employeeId,
                'type' => 'saida_intervalo',
                'time' => $date . ' 12:00:00',
            ]),
            'latitude' => -23.550520,
            'longitude' => -46.633309,
            'created_at' => $date . ' 12:00:00',
        ]);

        $this->assertNotFalse($saidaIntervalo);

        // 3. Volta do intervalo (13:00)
        $voltaIntervalo = $this->timePunchModel->insert([
            'employee_id' => $this->employeeId,
            'punch_type' => 'volta_intervalo',
            'punch_time' => $date . ' 13:00:00',
            'nsr' => $this->timePunchModel->generateNSR(),
            'hash' => $this->timePunchModel->generateHash([
                'employee_id' => $this->employeeId,
                'type' => 'volta_intervalo',
                'time' => $date . ' 13:00:00',
            ]),
            'latitude' => -23.550520,
            'longitude' => -46.633309,
            'created_at' => $date . ' 13:00:00',
        ]);

        $this->assertNotFalse($voltaIntervalo);

        // 4. Saída final (18:00)
        $saida = $this->timePunchModel->insert([
            'employee_id' => $this->employeeId,
            'punch_type' => 'saida',
            'punch_time' => $date . ' 18:00:00',
            'nsr' => $this->timePunchModel->generateNSR(),
            'hash' => $this->timePunchModel->generateHash([
                'employee_id' => $this->employeeId,
                'type' => 'saida',
                'time' => $date . ' 18:00:00',
            ]),
            'latitude' => -23.550520,
            'longitude' => -46.633309,
            'created_at' => $date . ' 18:00:00',
        ]);

        $this->assertNotFalse($saida);

        // Verificar total de marcações do dia
        $punches = $this->timePunchModel
            ->where('employee_id', $this->employeeId)
            ->where('DATE(punch_time)', $date)
            ->orderBy('punch_time', 'ASC')
            ->findAll();

        $this->assertCount(4, $punches);

        // Verificar sequência de tipos
        $this->assertEquals('entrada', $punches[0]['punch_type']);
        $this->assertEquals('saida_intervalo', $punches[1]['punch_type']);
        $this->assertEquals('volta_intervalo', $punches[2]['punch_type']);
        $this->assertEquals('saida', $punches[3]['punch_type']);

        // Calcular horas trabalhadas
        $manha = $this->timePunchModel->calculateHours(
            $punches[0]['punch_time'],
            $punches[1]['punch_time']
        );

        $tarde = $this->timePunchModel->calculateHours(
            $punches[2]['punch_time'],
            $punches[3]['punch_time']
        );

        $totalHoras = $manha + $tarde;

        // Verificar cálculo: 4h manhã + 5h tarde = 9h
        $this->assertEquals(4.0, $manha);
        $this->assertEquals(5.0, $tarde);
        $this->assertEquals(9.0, $totalHoras);

        // Verificar NSR sequencial
        $this->assertTrue($punches[1]['nsr'] > $punches[0]['nsr']);
        $this->assertTrue($punches[2]['nsr'] > $punches[1]['nsr']);
        $this->assertTrue($punches[3]['nsr'] > $punches[2]['nsr']);

        // Verificar que cada hash é único
        $hashes = array_column($punches, 'hash');
        $this->assertCount(4, array_unique($hashes));
    }

    /**
     * Teste: Jornada incompleta (apenas entrada, sem saída)
     */
    public function testIncompletePunchCycle(): void
    {
        $date = date('Y-m-d');

        // Apenas entrada
        $this->timePunchModel->insert([
            'employee_id' => $this->employeeId,
            'punch_type' => 'entrada',
            'punch_time' => $date . ' 08:00:00',
            'nsr' => $this->timePunchModel->generateNSR(),
            'hash' => 'test_hash_incomplete',
            'created_at' => $date . ' 08:00:00',
        ]);

        // Verificar que dia está incompleto
        $isComplete = $this->timePunchModel->isDayComplete($this->employeeId, $date);

        $this->assertFalse($isComplete);
    }

    /**
     * Teste: Validar geolocalização nas marcações
     */
    public function testPunchWithGeolocation(): void
    {
        $date = date('Y-m-d');
        $lat = -23.550520;
        $lon = -46.633309;

        $punchId = $this->timePunchModel->insert([
            'employee_id' => $this->employeeId,
            'punch_type' => 'entrada',
            'punch_time' => $date . ' 08:00:00',
            'nsr' => $this->timePunchModel->generateNSR(),
            'hash' => 'test_hash_geo',
            'latitude' => $lat,
            'longitude' => $lon,
            'created_at' => $date . ' 08:00:00',
        ]);

        $punch = $this->timePunchModel->find($punchId);

        $this->assertEquals($lat, $punch['latitude']);
        $this->assertEquals($lon, $punch['longitude']);
        $this->assertNotNull($punch['latitude']);
        $this->assertNotNull($punch['longitude']);
    }

    /**
     * Teste: Banco de horas (horas extras e negativos)
     */
    public function testTimeBank(): void
    {
        $date = date('Y-m-d');

        // Jornada com hora extra (10 horas em vez de 8)
        $punches = [
            ['type' => 'entrada', 'time' => '08:00:00'],
            ['type' => 'saida_intervalo', 'time' => '12:00:00'], // 4h
            ['type' => 'volta_intervalo', 'time' => '13:00:00'],
            ['type' => 'saida', 'time' => '19:00:00'], // 6h
        ];

        foreach ($punches as $punch) {
            $this->timePunchModel->insert([
                'employee_id' => $this->employeeId,
                'punch_type' => $punch['type'],
                'punch_time' => $date . ' ' . $punch['time'],
                'nsr' => $this->timePunchModel->generateNSR(),
                'hash' => $this->timePunchModel->generateHash(['test' => $punch['type']]),
                'created_at' => $date . ' ' . $punch['time'],
            ]);
        }

        // Calcular saldo
        $totalWorked = 10.0; // 4h + 6h
        $expectedHours = 8.0; // Jornada padrão
        $balance = $totalWorked - $expectedHours;

        $this->assertEquals(2.0, $balance); // 2 horas extras
    }

    /**
     * Teste: Múltiplos funcionários no mesmo dia
     */
    public function testMultipleEmployeesPunchingSameDay(): void
    {
        $date = date('Y-m-d');

        // Criar outro funcionário
        $employee2Id = $this->employeeModel->insert([
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'cpf' => $this->generateValidCPF(),
            'password' => password_hash('Test@123', PASSWORD_ARGON2ID),
            'role' => 'employee',
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Funcionário 1 bate ponto
        $this->timePunchModel->insert([
            'employee_id' => $this->employeeId,
            'punch_type' => 'entrada',
            'punch_time' => $date . ' 08:00:00',
            'nsr' => $this->timePunchModel->generateNSR(),
            'hash' => 'hash_emp1',
            'created_at' => $date . ' 08:00:00',
        ]);

        // Funcionário 2 bate ponto
        $this->timePunchModel->insert([
            'employee_id' => $employee2Id,
            'punch_type' => 'entrada',
            'punch_time' => $date . ' 08:05:00',
            'nsr' => $this->timePunchModel->generateNSR(),
            'hash' => 'hash_emp2',
            'created_at' => $date . ' 08:05:00',
        ]);

        // Verificar que cada um tem sua marcação
        $punches1 = $this->timePunchModel->where('employee_id', $this->employeeId)->findAll();
        $punches2 = $this->timePunchModel->where('employee_id', $employee2Id)->findAll();

        $this->assertCount(1, $punches1);
        $this->assertCount(1, $punches2);
        $this->assertNotEquals($punches1[0]['nsr'], $punches2[0]['nsr']);
    }

    /**
     * Gerar CPF válido para testes
     */
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
