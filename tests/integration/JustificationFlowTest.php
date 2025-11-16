<?php

namespace Tests\Integration;

use App\Models\EmployeeModel;
use App\Models\JustificationModel;
use App\Models\NotificationModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Faker\Factory;

/**
 * Testes de Integração - Fluxo de Justificativas
 *
 * Criação, aprovação, rejeição e notificações
 */
class JustificationFlowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = null;

    protected EmployeeModel $employeeModel;
    protected JustificationModel $justificationModel;
    protected NotificationModel $notificationModel;
    protected int $employeeId;
    protected int $managerId;
    protected $faker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employeeModel = new EmployeeModel();
        $this->justificationModel = new JustificationModel();
        $this->notificationModel = new NotificationModel();
        $this->faker = Factory::create('pt_BR');

        // Criar funcionário e gestor
        $this->employeeId = $this->createEmployee('employee');
        $this->managerId = $this->createEmployee('manager');
    }

    /**
     * Criar funcionário ou gestor para testes
     */
    private function createEmployee(string $role): int
    {
        return $this->employeeModel->insert([
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'cpf' => $this->generateValidCPF(),
            'password' => password_hash('Test@123', PASSWORD_ARGON2ID),
            'role' => $role,
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Teste: Criar justificativa e aprovar
     */
    public function testCreateAndApprove(): void
    {
        // 1. Funcionário cria justificativa
        $justificationId = $this->justificationModel->insert([
            'employee_id' => $this->employeeId,
            'absence_date' => date('Y-m-d', strtotime('-1 day')),
            'justification_type' => 'medical',
            'description' => 'Consulta médica',
            'attachment' => 'justifications/atestado_123.pdf',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->assertNotFalse($justificationId);

        // Verificar se foi criada
        $justification = $this->justificationModel->find($justificationId);
        $this->assertEquals('pending', $justification['status']);
        $this->assertEquals($this->employeeId, $justification['employee_id']);

        // 2. Gestor aprova
        $updated = $this->justificationModel->update($justificationId, [
            'status' => 'approved',
            'reviewed_by' => $this->managerId,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'review_notes' => 'Aprovado conforme atestado médico',
        ]);

        $this->assertTrue($updated);

        // Verificar aprovação
        $justification = $this->justificationModel->find($justificationId);
        $this->assertEquals('approved', $justification['status']);
        $this->assertEquals($this->managerId, $justification['reviewed_by']);
        $this->assertNotNull($justification['reviewed_at']);

        // 3. Verificar notificação para funcionário
        $notification = $this->notificationModel->where([
            'user_id' => $this->employeeId,
            'type' => 'justification_approved',
        ])->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('aprovada', strtolower($notification['message']));
    }

    /**
     * Teste: Criar justificativa e rejeitar com motivo
     */
    public function testCreateAndReject(): void
    {
        // 1. Funcionário cria justificativa
        $justificationId = $this->justificationModel->insert([
            'employee_id' => $this->employeeId,
            'absence_date' => date('Y-m-d', strtotime('-2 days')),
            'justification_type' => 'personal',
            'description' => 'Assuntos pessoais',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // 2. Gestor rejeita
        $rejectReason = 'Justificativa insuficiente. Por favor, anexe documento comprobatório.';

        $updated = $this->justificationModel->update($justificationId, [
            'status' => 'rejected',
            'reviewed_by' => $this->managerId,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'review_notes' => $rejectReason,
        ]);

        $this->assertTrue($updated);

        // Verificar rejeição
        $justification = $this->justificationModel->find($justificationId);
        $this->assertEquals('rejected', $justification['status']);
        $this->assertEquals($rejectReason, $justification['review_notes']);

        // 3. Verificar notificação para funcionário
        $notification = $this->notificationModel->where([
            'user_id' => $this->employeeId,
            'type' => 'justification_rejected',
        ])->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('rejeitada', strtolower($notification['message']));
    }

    /**
     * Teste: Justificativa com anexo
     */
    public function testJustificationWithAttachment(): void
    {
        // Simular upload de arquivo
        $attachmentPath = 'justifications/medical_cert_' . uniqid() . '.pdf';

        $justificationId = $this->justificationModel->insert([
            'employee_id' => $this->employeeId,
            'absence_date' => date('Y-m-d'),
            'justification_type' => 'medical',
            'description' => 'Consulta cardiologista',
            'attachment' => $attachmentPath,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $justification = $this->justificationModel->find($justificationId);

        $this->assertNotNull($justification['attachment']);
        $this->assertEquals($attachmentPath, $justification['attachment']);
        $this->assertStringContainsString('.pdf', $justification['attachment']);
    }

    /**
     * Teste: Múltiplas justificativas do mesmo funcionário
     */
    public function testMultipleJustifications(): void
    {
        // Criar 3 justificativas
        for ($i = 1; $i <= 3; $i++) {
            $this->justificationModel->insert([
                'employee_id' => $this->employeeId,
                'absence_date' => date('Y-m-d', strtotime("-{$i} days")),
                'justification_type' => 'medical',
                'description' => "Justificativa {$i}",
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $justifications = $this->justificationModel
            ->where('employee_id', $this->employeeId)
            ->findAll();

        $this->assertCount(3, $justifications);
    }

    /**
     * Teste: Tipos diferentes de justificativas
     */
    public function testDifferentJustificationTypes(): void
    {
        $types = [
            'medical' => 'Consulta médica',
            'dental' => 'Dentista',
            'family' => 'Acompanhar familiar',
            'personal' => 'Assunto pessoal',
            'legal' => 'Compromisso legal',
        ];

        foreach ($types as $type => $description) {
            $id = $this->justificationModel->insert([
                'employee_id' => $this->employeeId,
                'absence_date' => date('Y-m-d'),
                'justification_type' => $type,
                'description' => $description,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $this->assertNotFalse($id);
        }

        $justifications = $this->justificationModel
            ->where('employee_id', $this->employeeId)
            ->findAll();

        $this->assertCount(5, $justifications);

        // Verificar que cada tipo foi criado
        $createdTypes = array_column($justifications, 'justification_type');
        foreach (array_keys($types) as $type) {
            $this->assertContains($type, $createdTypes);
        }
    }

    /**
     * Teste: Histórico de revisões
     */
    public function testJustificationHistory(): void
    {
        // Criar justificativa
        $justificationId = $this->justificationModel->insert([
            'employee_id' => $this->employeeId,
            'absence_date' => date('Y-m-d'),
            'justification_type' => 'medical',
            'description' => 'Exame médico',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Primeira revisão: pendente → aprovada
        $this->justificationModel->update($justificationId, [
            'status' => 'approved',
            'reviewed_by' => $this->managerId,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);

        // Segunda revisão: aprovada → cancelada (caso de erro)
        $this->justificationModel->update($justificationId, [
            'status' => 'cancelled',
            'review_notes' => 'Cancelado por erro de lançamento',
        ]);

        $justification = $this->justificationModel->find($justificationId);
        $this->assertEquals('cancelled', $justification['status']);
    }

    /**
     * Teste: Validação de data de ausência
     */
    public function testAbsenceDateValidation(): void
    {
        // Data no passado (válido)
        $pastDate = date('Y-m-d', strtotime('-5 days'));
        $id = $this->justificationModel->insert([
            'employee_id' => $this->employeeId,
            'absence_date' => $pastDate,
            'justification_type' => 'medical',
            'description' => 'Teste data passada',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->assertNotFalse($id);

        // Verificar data salva corretamente
        $justification = $this->justificationModel->find($id);
        $this->assertEquals($pastDate, $justification['absence_date']);
    }

    /**
     * Teste: Listar justificativas pendentes do gestor
     */
    public function testListPendingJustifications(): void
    {
        // Criar várias justificativas de diferentes funcionários
        $employee2Id = $this->createEmployee('employee');

        // Funcionário 1: 2 pendentes, 1 aprovada
        $this->justificationModel->insert([
            'employee_id' => $this->employeeId,
            'absence_date' => date('Y-m-d', strtotime('-1 day')),
            'justification_type' => 'medical',
            'description' => 'Pendente 1',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->justificationModel->insert([
            'employee_id' => $this->employeeId,
            'absence_date' => date('Y-m-d', strtotime('-2 days')),
            'justification_type' => 'dental',
            'description' => 'Pendente 2',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->justificationModel->insert([
            'employee_id' => $this->employeeId,
            'absence_date' => date('Y-m-d', strtotime('-3 days')),
            'justification_type' => 'personal',
            'description' => 'Aprovada',
            'status' => 'approved',
            'reviewed_by' => $this->managerId,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Funcionário 2: 1 pendente
        $this->justificationModel->insert([
            'employee_id' => $employee2Id,
            'absence_date' => date('Y-m-d', strtotime('-1 day')),
            'justification_type' => 'family',
            'description' => 'Pendente 3',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Listar apenas pendentes
        $pending = $this->justificationModel
            ->where('status', 'pending')
            ->findAll();

        $this->assertCount(3, $pending);

        // Verificar que apenas pendentes foram retornadas
        foreach ($pending as $justification) {
            $this->assertEquals('pending', $justification['status']);
        }
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
