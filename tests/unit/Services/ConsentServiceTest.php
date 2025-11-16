<?php

namespace Tests\Unit\Services;

use App\Services\LGPD\ConsentService;
use App\Models\UserConsentModel;
use App\Models\EmployeeModel;
use App\Models\BiometricTemplateModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Testes Unitários para ConsentService (LGPD)
 *
 * Testa consentimentos, revogações e exclusão de dados biométricos
 */
class ConsentServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = null;

    protected ConsentService $consentService;
    protected UserConsentModel $consentModel;
    protected EmployeeModel $employeeModel;
    protected BiometricTemplateModel $biometricModel;
    protected int $employeeId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consentService = new ConsentService();
        $this->consentModel = new UserConsentModel();
        $this->employeeModel = new EmployeeModel();
        $this->biometricModel = new BiometricTemplateModel();

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
     * Teste: Concessão de consentimento deve salvar corretamente
     */
    public function testGrantConsent(): void
    {
        $result = $this->consentService->grant(
            $this->employeeId,
            'biometric_data',
            'Eu concordo com o processamento dos meus dados biométricos'
        );

        $this->assertTrue($result);

        // Verificar no banco de dados
        $consent = $this->consentModel->where([
            'user_id' => $this->employeeId,
            'consent_type' => 'biometric_data',
        ])->first();

        $this->assertNotNull($consent);
        $this->assertTrue((bool)$consent['granted']);
        $this->assertNotNull($consent['granted_at']);
    }

    /**
     * Teste: Revogação de consentimento
     */
    public function testRevokeConsent(): void
    {
        // Primeiro conceder
        $this->consentService->grant(
            $this->employeeId,
            'biometric_data',
            'Consentimento inicial'
        );

        // Depois revogar
        $result = $this->consentService->revoke(
            $this->employeeId,
            'biometric_data',
            'Não quero mais compartilhar dados'
        );

        $this->assertTrue($result);

        // Verificar no banco
        $consent = $this->consentModel->where([
            'user_id' => $this->employeeId,
            'consent_type' => 'biometric_data',
        ])->first();

        $this->assertFalse((bool)$consent['granted']);
        $this->assertNotNull($consent['revoked_at']);
        $this->assertEquals('Não quero mais compartilhar dados', $consent['revoke_reason']);
    }

    /**
     * Teste: Revogação deve deletar dados biométricos
     */
    public function testRevokeDeletesBiometricData(): void
    {
        // Criar template biométrico
        $templateId = $this->biometricModel->insert([
            'employee_id' => $this->employeeId,
            'template_type' => 'face',
            'template_data' => base64_encode('fake_biometric_data'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Conceder consentimento
        $this->consentService->grant(
            $this->employeeId,
            'biometric_data',
            'Consentimento'
        );

        // Revogar
        $this->consentService->revoke(
            $this->employeeId,
            'biometric_data',
            'Revogação'
        );

        // Dados biométricos devem ter sido deletados
        $template = $this->biometricModel->find($templateId);
        $this->assertNull($template);
    }

    /**
     * Teste: Verificar se consentimento foi concedido
     */
    public function testHasConsent(): void
    {
        // Sem consentimento
        $has = $this->consentService->hasConsent($this->employeeId, 'biometric_data');
        $this->assertFalse($has);

        // Conceder consentimento
        $this->consentService->grant($this->employeeId, 'biometric_data', 'Sim');

        // Agora deve ter
        $has = $this->consentService->hasConsent($this->employeeId, 'biometric_data');
        $this->assertTrue($has);
    }

    /**
     * Teste: Múltiplos tipos de consentimento
     */
    public function testMultipleConsentTypes(): void
    {
        // Conceder diferentes tipos
        $this->consentService->grant($this->employeeId, 'biometric_data', 'Sim');
        $this->consentService->grant($this->employeeId, 'geolocation', 'Sim');
        $this->consentService->grant($this->employeeId, 'data_sharing', 'Sim');

        // Verificar cada um
        $this->assertTrue($this->consentService->hasConsent($this->employeeId, 'biometric_data'));
        $this->assertTrue($this->consentService->hasConsent($this->employeeId, 'geolocation'));
        $this->assertTrue($this->consentService->hasConsent($this->employeeId, 'data_sharing'));

        // Revogar apenas um
        $this->consentService->revoke($this->employeeId, 'geolocation', 'Não quero mais');

        // Outros devem continuar ativos
        $this->assertTrue($this->consentService->hasConsent($this->employeeId, 'biometric_data'));
        $this->assertFalse($this->consentService->hasConsent($this->employeeId, 'geolocation'));
        $this->assertTrue($this->consentService->hasConsent($this->employeeId, 'data_sharing'));
    }

    /**
     * Teste: Histórico de consentimentos
     */
    public function testConsentHistory(): void
    {
        // Conceder
        $this->consentService->grant($this->employeeId, 'biometric_data', 'Primeira concessão');

        // Revogar
        $this->consentService->revoke($this->employeeId, 'biometric_data', 'Revogação 1');

        // Conceder novamente
        $this->consentService->grant($this->employeeId, 'biometric_data', 'Segunda concessão');

        // Obter histórico
        $history = $this->consentService->getHistory($this->employeeId, 'biometric_data');

        // Deve ter pelo menos 3 registros
        $this->assertGreaterThanOrEqual(3, count($history));
    }

    /**
     * Teste: Exportação de dados do usuário (LGPD Art. 18)
     */
    public function testDataExport(): void
    {
        // Conceder consentimentos
        $this->consentService->grant($this->employeeId, 'biometric_data', 'Sim');
        $this->consentService->grant($this->employeeId, 'geolocation', 'Sim');

        // Exportar dados
        $data = $this->consentService->exportUserData($this->employeeId);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('personal_info', $data);
        $this->assertArrayHasKey('consents', $data);
        $this->assertArrayHasKey('time_punches', $data);

        // Verificar consentimentos exportados
        $this->assertCount(2, $data['consents']);
    }

    /**
     * Teste: Anonimização de dados após término de vínculo
     */
    public function testDataAnonymization(): void
    {
        // Criar dados do funcionário
        $this->consentService->grant($this->employeeId, 'biometric_data', 'Sim');

        // Anonimizar
        $result = $this->consentService->anonymizeUserData($this->employeeId);

        $this->assertTrue($result);

        // Verificar se dados foram anonimizados
        $employee = $this->employeeModel->find($this->employeeId);

        $this->assertStringContainsString('ANONIMIZADO', $employee['name']);
        $this->assertStringContainsString('anonimizado', $employee['email']);
    }

    /**
     * Teste: Consentimento obrigatório para cadastrar biometria
     */
    public function testBiometricRequiresConsent(): void
    {
        // Tentar cadastrar biometria sem consentimento
        $canRegister = $this->consentService->canRegisterBiometric($this->employeeId);

        $this->assertFalse($canRegister);

        // Conceder consentimento
        $this->consentService->grant($this->employeeId, 'biometric_data', 'Sim');

        // Agora pode cadastrar
        $canRegister = $this->consentService->canRegisterBiometric($this->employeeId);

        $this->assertTrue($canRegister);
    }

    /**
     * Teste: Notificação de DPO quando consentimento é revogado
     */
    public function testDPONotificationOnRevoke(): void
    {
        $this->consentService->grant($this->employeeId, 'biometric_data', 'Sim');

        // Capturar notificações
        $this->consentService->enableNotificationCapture();

        $this->consentService->revoke($this->employeeId, 'biometric_data', 'Não quero mais');

        $notifications = $this->consentService->getCapturedNotifications();

        // Deve ter enviado notificação para o DPO
        $this->assertNotEmpty($notifications);
        $this->assertStringContainsString('DPO', $notifications[0]['recipient']);
    }

    /**
     * Teste: Período de retenção de dados (LGPD)
     */
    public function testDataRetentionPeriod(): void
    {
        $retentionDays = $this->consentService->getRetentionPeriod('biometric_data');

        // Deve ser configurado (ex: 10 anos = 3650 dias)
        $this->assertGreaterThan(0, $retentionDays);
        $this->assertEquals(3650, $retentionDays); // 10 anos conforme legislação
    }

    /**
     * Teste: Verificar consentimentos pendentes
     */
    public function testPendingConsents(): void
    {
        // Usuário sem consentimentos
        $pending = $this->consentService->getPendingConsents($this->employeeId);

        // Deve ter consentimentos pendentes (biometric_data, geolocation, etc.)
        $this->assertNotEmpty($pending);
        $this->assertContains('biometric_data', $pending);
        $this->assertContains('geolocation', $pending);

        // Conceder um consentimento
        $this->consentService->grant($this->employeeId, 'biometric_data', 'Sim');

        // Verificar novamente
        $pending = $this->consentService->getPendingConsents($this->employeeId);

        // biometric_data não deve estar mais pendente
        $this->assertNotContains('biometric_data', $pending);
    }

    /**
     * Teste: Validar assinatura digital do consentimento (opcional)
     */
    public function testConsentSignature(): void
    {
        $ipAddress = '192.168.1.1';
        $userAgent = 'Mozilla/5.0 Test Browser';

        $result = $this->consentService->grantWithSignature(
            $this->employeeId,
            'biometric_data',
            'Consentimento',
            $ipAddress,
            $userAgent
        );

        $this->assertTrue($result);

        // Verificar se IP e User-Agent foram salvos
        $consent = $this->consentModel->where([
            'user_id' => $this->employeeId,
            'consent_type' => 'biometric_data',
        ])->first();

        $this->assertEquals($ipAddress, $consent['ip_address']);
        $this->assertEquals($userAgent, $consent['user_agent']);
    }
}
