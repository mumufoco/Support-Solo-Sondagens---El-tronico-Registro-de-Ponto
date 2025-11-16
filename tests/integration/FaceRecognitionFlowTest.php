<?php

namespace Tests\Integration;

use App\Models\EmployeeModel;
use App\Models\BiometricTemplateModel;
use App\Services\Biometric\DeepFaceService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Faker\Factory;

/**
 * Testes de Integração - Fluxo de Reconhecimento Facial
 *
 * Cadastro, reconhecimento e validação com DeepFace (mockado)
 */
class FaceRecognitionFlowTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = null;

    protected EmployeeModel $employeeModel;
    protected BiometricTemplateModel $biometricModel;
    protected DeepFaceService $deepFaceService;
    protected int $employeeId;
    protected $faker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employeeModel = new EmployeeModel();
        $this->biometricModel = new BiometricTemplateModel();
        $this->deepFaceService = new DeepFaceService();
        $this->faker = Factory::create('pt_BR');

        // Criar funcionário de teste
        $this->employeeId = $this->createEmployee();
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
     * Teste: Cadastrar rosto e reconhecer com mesma foto (deve passar)
     */
    public function testEnrollAndRecognize(): void
    {
        // Mock da resposta de cadastro
        $enrollResponse = [
            'success' => true,
            'message' => 'Face enrolled successfully',
            'employee_id' => $this->employeeId,
            'face_encoding' => base64_encode('fake_encoding_data'),
        ];

        $this->deepFaceService->setMockResponse($enrollResponse, 200);

        // 1. Cadastrar rosto
        $photoPath = '/fake/path/photo_employee_' . $this->employeeId . '.jpg';
        $enrollResult = $this->deepFaceService->enroll($this->employeeId, $photoPath);

        $this->assertTrue($enrollResult['success']);

        // Salvar template biométrico no banco
        $templateId = $this->biometricModel->insert([
            'employee_id' => $this->employeeId,
            'template_type' => 'face',
            'template_data' => $enrollResult['face_encoding'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->assertNotFalse($templateId);

        // Verificar se template foi salvo
        $template = $this->biometricModel->find($templateId);
        $this->assertEquals($this->employeeId, $template['employee_id']);
        $this->assertEquals('face', $template['template_type']);

        // Mock da resposta de reconhecimento (mesma foto = alta similaridade)
        $recognizeResponse = [
            'success' => true,
            'recognized' => true,
            'employee_id' => $this->employeeId,
            'similarity' => 0.95,
            'confidence' => 'high',
        ];

        $this->deepFaceService->setMockResponse($recognizeResponse, 200);

        // 2. Tentar reconhecer com mesma foto
        $recognizeResult = $this->deepFaceService->recognize($photoPath);

        $this->assertTrue($recognizeResult['success']);
        $this->assertTrue($recognizeResult['recognized']);
        $this->assertEquals($this->employeeId, $recognizeResult['employee_id']);
        $this->assertGreaterThan(0.90, $recognizeResult['similarity']);
    }

    /**
     * Teste: Tentar reconhecer com foto diferente (deve falhar)
     */
    public function testRecognizeWithDifferentPhoto(): void
    {
        // Cadastrar funcionário
        $enrollResponse = [
            'success' => true,
            'employee_id' => $this->employeeId,
            'face_encoding' => base64_encode('encoding_original'),
        ];

        $this->deepFaceService->setMockResponse($enrollResponse, 200);
        $this->deepFaceService->enroll($this->employeeId, '/path/photo_original.jpg');

        // Salvar template
        $this->biometricModel->insert([
            'employee_id' => $this->employeeId,
            'template_type' => 'face',
            'template_data' => base64_encode('encoding_original'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Mock de reconhecimento com foto diferente (baixa similaridade)
        $recognizeResponse = [
            'success' => true,
            'recognized' => false,
            'message' => 'No match found',
            'best_similarity' => 0.25,
        ];

        $this->deepFaceService->setMockResponse($recognizeResponse, 200);

        // Tentar reconhecer com foto diferente
        $recognizeResult = $this->deepFaceService->recognize('/path/photo_different.jpg');

        $this->assertTrue($recognizeResult['success']);
        $this->assertFalse($recognizeResult['recognized']);
        $this->assertLessThan(0.40, $recognizeResult['best_similarity']);
    }

    /**
     * Teste: Anti-spoofing - detectar foto impressa
     */
    public function testAntiSpoofingPrintedPhoto(): void
    {
        // Mock de resposta quando detecta spoofing
        $spoofResponse = [
            'success' => false,
            'error' => 'Spoofing attempt detected: printed photo',
            'spoof_type' => 'print',
            'confidence' => 0.87,
        ];

        $this->deepFaceService->setMockResponse($spoofResponse, 403);

        $result = $this->deepFaceService->recognize('/path/printed_photo.jpg');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Spoofing', $result['error']);
    }

    /**
     * Teste: Anti-spoofing - detectar foto de tela (celular mostrando foto)
     */
    public function testAntiSpoofingScreenPhoto(): void
    {
        $spoofResponse = [
            'success' => false,
            'error' => 'Spoofing attempt detected: screen display',
            'spoof_type' => 'screen',
            'confidence' => 0.92,
        ];

        $this->deepFaceService->setMockResponse($spoofResponse, 403);

        $result = $this->deepFaceService->recognize('/path/screen_photo.jpg');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('screen', strtolower($result['error']));
    }

    /**
     * Teste: Múltiplos rostos na foto (deve rejeitar)
     */
    public function testMultipleFacesRejected(): void
    {
        $multipleResponse = [
            'success' => false,
            'error' => 'Multiple faces detected. Please ensure only one person is in the photo.',
            'faces_count' => 3,
        ];

        $this->deepFaceService->setMockResponse($multipleResponse, 400);

        $result = $this->deepFaceService->enroll($this->employeeId, '/path/group_photo.jpg');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('faces_count', $result);
        $this->assertGreaterThan(1, $result['faces_count']);
    }

    /**
     * Teste: Foto sem rosto detectado
     */
    public function testNoFaceDetected(): void
    {
        $noFaceResponse = [
            'success' => false,
            'error' => 'No face detected in the image',
        ];

        $this->deepFaceService->setMockResponse($noFaceResponse, 400);

        $result = $this->deepFaceService->enroll($this->employeeId, '/path/landscape.jpg');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No face', $result['error']);
    }

    /**
     * Teste: Verificar similaridade entre duas fotos
     */
    public function testVerifyTwoPhotos(): void
    {
        // Mesma pessoa, fotos diferentes
        $verifyResponse = [
            'success' => true,
            'verified' => true,
            'similarity' => 0.88,
            'distance' => 0.12,
        ];

        $this->deepFaceService->setMockResponse($verifyResponse, 200);

        $result = $this->deepFaceService->verify(
            '/path/photo1.jpg',
            '/path/photo2.jpg'
        );

        $this->assertTrue($result['success']);
        $this->assertTrue($result['verified']);
        $this->assertGreaterThan(0.80, $result['similarity']);
    }

    /**
     * Teste: Threshold personalizado para reconhecimento
     */
    public function testCustomThreshold(): void
    {
        // Similaridade limítrofe
        $recognizeResponse = [
            'success' => true,
            'recognized' => true,
            'employee_id' => $this->employeeId,
            'similarity' => 0.38,
        ];

        $this->deepFaceService->setMockResponse($recognizeResponse, 200);

        // Com threshold padrão (0.40) não deve reconhecer
        $result1 = $this->deepFaceService->recognizeWithThreshold('/path/photo.jpg', 0.40);
        $this->assertFalse($result1['recognized']);

        // Com threshold menor (0.35) deve reconhecer
        $this->deepFaceService->setMockResponse($recognizeResponse, 200);
        $result2 = $this->deepFaceService->recognizeWithThreshold('/path/photo.jpg', 0.35);
        $this->assertTrue($result2['recognized']);
    }

    /**
     * Teste: Atualizar template biométrico
     */
    public function testUpdateBiometricTemplate(): void
    {
        // Cadastro inicial
        $this->biometricModel->insert([
            'employee_id' => $this->employeeId,
            'template_type' => 'face',
            'template_data' => base64_encode('old_encoding'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Atualizar com novo template
        $newEnrollResponse = [
            'success' => true,
            'employee_id' => $this->employeeId,
            'face_encoding' => base64_encode('new_encoding_updated'),
        ];

        $this->deepFaceService->setMockResponse($newEnrollResponse, 200);

        // Deletar template antigo
        $this->biometricModel
            ->where('employee_id', $this->employeeId)
            ->where('template_type', 'face')
            ->delete();

        // Inserir novo
        $newTemplateId = $this->biometricModel->insert([
            'employee_id' => $this->employeeId,
            'template_type' => 'face',
            'template_data' => base64_encode('new_encoding_updated'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $template = $this->biometricModel->find($newTemplateId);
        $this->assertEquals(base64_encode('new_encoding_updated'), $template['template_data']);
    }

    /**
     * Teste: Análise de atributos faciais (idade, gênero, emoção)
     */
    public function testFaceAttributesAnalysis(): void
    {
        $analyzeResponse = [
            'success' => true,
            'age' => 28,
            'gender' => 'Man',
            'emotion' => 'happy',
            'race' => 'asian',
        ];

        $this->deepFaceService->setMockResponse($analyzeResponse, 200);

        $result = $this->deepFaceService->analyze('/path/photo.jpg');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('age', $result);
        $this->assertArrayHasKey('gender', $result);
        $this->assertArrayHasKey('emotion', $result);
        $this->assertIsInt($result['age']);
    }

    /**
     * Teste: Múltiplos funcionários cadastrados
     */
    public function testMultipleEmployeesEnrolled(): void
    {
        // Criar 3 funcionários
        $employees = [];
        for ($i = 0; $i < 3; $i++) {
            $empId = $this->createEmployee();
            $employees[] = $empId;

            // Mock de cadastro bem-sucedido
            $enrollResponse = [
                'success' => true,
                'employee_id' => $empId,
                'face_encoding' => base64_encode('encoding_' . $empId),
            ];

            $this->deepFaceService->setMockResponse($enrollResponse, 200);
            $this->deepFaceService->enroll($empId, "/path/photo_{$empId}.jpg");

            // Salvar template
            $this->biometricModel->insert([
                'employee_id' => $empId,
                'template_type' => 'face',
                'template_data' => base64_encode('encoding_' . $empId),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        // Verificar que todos foram cadastrados
        $templates = $this->biometricModel
            ->whereIn('employee_id', $employees)
            ->findAll();

        $this->assertCount(3, $templates);
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
