<?php

namespace Tests\Unit\Services;

use App\Services\Biometric\DeepFaceService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\HTTP\MockResponse;

/**
 * Testes Unitários para DeepFaceService
 *
 * Testa integração com API DeepFace usando mocks
 */
class DeepFaceServiceTest extends CIUnitTestCase
{
    protected DeepFaceService $deepFaceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->deepFaceService = new DeepFaceService();
    }

    /**
     * Teste: Cadastro de rosto bem-sucedido
     */
    public function testEnrollSuccess(): void
    {
        // Mock da resposta da API
        $mockResponse = [
            'success' => true,
            'message' => 'Face enrolled successfully',
            'employee_id' => 1,
            'face_encoding' => 'base64_encoded_data...',
        ];

        // Simular resposta bem-sucedida
        $this->deepFaceService->setMockResponse($mockResponse, 200);

        $result = $this->deepFaceService->enroll(1, '/path/to/photo.jpg');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('employee_id', $result);
        $this->assertEquals(1, $result['employee_id']);
    }

    /**
     * Teste: Tentativa de cadastrar foto sem rosto
     */
    public function testEnrollNoFace(): void
    {
        // Mock da resposta quando não há rosto na foto
        $mockResponse = [
            'success' => false,
            'error' => 'No face detected in the image',
        ];

        $this->deepFaceService->setMockResponse($mockResponse, 400);

        $result = $this->deepFaceService->enroll(1, '/path/to/no_face.jpg');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('No face', $result['error']);
    }

    /**
     * Teste: Reconhecimento bem-sucedido
     */
    public function testRecognizeSuccess(): void
    {
        // Mock da resposta de reconhecimento
        $mockResponse = [
            'success' => true,
            'recognized' => true,
            'employee_id' => 1,
            'similarity' => 0.95,
            'confidence' => 'high',
        ];

        $this->deepFaceService->setMockResponse($mockResponse, 200);

        $result = $this->deepFaceService->recognize('/path/to/test_photo.jpg');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['recognized']);
        $this->assertEquals(1, $result['employee_id']);
        $this->assertGreaterThan(0.90, $result['similarity']);
    }

    /**
     * Teste: Reconhecimento sem match
     */
    public function testRecognizeNoMatch(): void
    {
        // Mock quando nenhum funcionário é reconhecido
        $mockResponse = [
            'success' => true,
            'recognized' => false,
            'message' => 'No match found',
            'best_similarity' => 0.35,
        ];

        $this->deepFaceService->setMockResponse($mockResponse, 200);

        $result = $this->deepFaceService->recognize('/path/to/unknown_face.jpg');

        $this->assertTrue($result['success']);
        $this->assertFalse($result['recognized']);
        $this->assertArrayHasKey('message', $result);
    }

    /**
     * Teste: Verificação de similaridade entre duas fotos
     */
    public function testVerifyMatch(): void
    {
        // Mock de verificação com match
        $mockResponse = [
            'success' => true,
            'verified' => true,
            'similarity' => 0.92,
            'distance' => 0.08,
        ];

        $this->deepFaceService->setMockResponse($mockResponse, 200);

        $result = $this->deepFaceService->verify('/path/photo1.jpg', '/path/photo2.jpg');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['verified']);
        $this->assertGreaterThan(0.90, $result['similarity']);
    }

    /**
     * Teste: Verificação sem match
     */
    public function testVerifyNoMatch(): void
    {
        // Mock de verificação sem match
        $mockResponse = [
            'success' => true,
            'verified' => false,
            'similarity' => 0.30,
            'distance' => 0.70,
        ];

        $this->deepFaceService->setMockResponse($mockResponse, 200);

        $result = $this->deepFaceService->verify('/path/photo1.jpg', '/path/photo_different.jpg');

        $this->assertTrue($result['success']);
        $this->assertFalse($result['verified']);
        $this->assertLessThan(0.40, $result['similarity']);
    }

    /**
     * Teste: Health check da API
     */
    public function testHealthCheck(): void
    {
        $mockResponse = [
            'status' => 'ok',
            'version' => '0.0.89',
            'model' => 'VGG-Face',
        ];

        $this->deepFaceService->setMockResponse($mockResponse, 200);

        $result = $this->deepFaceService->healthCheck();

        $this->assertTrue($result);
    }

    /**
     * Teste: Health check com API offline
     */
    public function testHealthCheckOffline(): void
    {
        // Simular timeout ou erro de conexão
        $this->deepFaceService->setMockResponse(null, 0);

        $result = $this->deepFaceService->healthCheck();

        $this->assertFalse($result);
    }

    /**
     * Teste: Análise de atributos faciais
     */
    public function testAnalyzeFace(): void
    {
        // Mock de análise facial
        $mockResponse = [
            'success' => true,
            'age' => 32,
            'gender' => 'Man',
            'emotion' => 'happy',
            'race' => 'asian',
        ];

        $this->deepFaceService->setMockResponse($mockResponse, 200);

        $result = $this->deepFaceService->analyze('/path/to/photo.jpg');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('age', $result);
        $this->assertArrayHasKey('gender', $result);
        $this->assertArrayHasKey('emotion', $result);
    }

    /**
     * Teste: Detecção de múltiplos rostos
     */
    public function testMultipleFacesDetected(): void
    {
        // Mock quando detecta mais de um rosto
        $mockResponse = [
            'success' => false,
            'error' => 'Multiple faces detected. Please ensure only one person is in the photo.',
            'faces_count' => 3,
        ];

        $this->deepFaceService->setMockResponse($mockResponse, 400);

        $result = $this->deepFaceService->enroll(1, '/path/to/group_photo.jpg');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('faces_count', $result);
        $this->assertGreaterThan(1, $result['faces_count']);
    }

    /**
     * Teste: Anti-spoofing - detecção de foto impressa
     */
    public function testAntiSpoofingPrintedPhoto(): void
    {
        // Mock de detecção de spoofing
        $mockResponse = [
            'success' => false,
            'error' => 'Spoofing attempt detected',
            'spoof_type' => 'print',
            'confidence' => 0.85,
        ];

        $this->deepFaceService->setMockResponse($mockResponse, 403);

        $result = $this->deepFaceService->recognize('/path/to/printed_photo.jpg');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Spoofing', $result['error']);
    }

    /**
     * Teste: Timeout na requisição
     */
    public function testRequestTimeout(): void
    {
        // Simular timeout (demorou mais de 30 segundos)
        $this->deepFaceService->setTimeout(1); // 1 segundo para teste
        $this->deepFaceService->setMockResponse(null, 0);

        $result = $this->deepFaceService->recognize('/path/to/photo.jpg');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Teste: Retry automático após falha
     */
    public function testAutoRetry(): void
    {
        // Primeira tentativa falha, segunda sucede
        $failResponse = ['success' => false, 'error' => 'Temporary error'];
        $successResponse = ['success' => true, 'recognized' => true, 'employee_id' => 1];

        $this->deepFaceService->setRetryResponses([
            [$failResponse, 500],
            [$failResponse, 500],
            [$successResponse, 200], // Sucesso na 3ª tentativa
        ]);

        $result = $this->deepFaceService->recognize('/path/to/photo.jpg', 3);

        $this->assertTrue($result['success']);
    }

    /**
     * Teste: Validação de threshold personalizado
     */
    public function testCustomThreshold(): void
    {
        // Similaridade limítrofe
        $mockResponse = [
            'success' => true,
            'recognized' => true,
            'employee_id' => 1,
            'similarity' => 0.38, // Abaixo do threshold padrão (0.40)
        ];

        $this->deepFaceService->setMockResponse($mockResponse, 200);

        // Com threshold 0.40 (padrão) não deve reconhecer
        $result1 = $this->deepFaceService->recognize('/path/to/photo.jpg', 1, 0.40);
        $this->assertFalse($result1['recognized']);

        // Com threshold 0.35 deve reconhecer
        $result2 = $this->deepFaceService->recognize('/path/to/photo.jpg', 1, 0.35);
        $this->assertTrue($result2['recognized']);
    }

    /**
     * Teste: Logging de requisições
     */
    public function testRequestLogging(): void
    {
        $mockResponse = ['success' => true, 'recognized' => true, 'employee_id' => 1];
        $this->deepFaceService->setMockResponse($mockResponse, 200);

        $this->deepFaceService->enableLogging(true);
        $this->deepFaceService->recognize('/path/to/photo.jpg');

        $logs = $this->deepFaceService->getLogs();

        $this->assertNotEmpty($logs);
        $this->assertArrayHasKey('request', $logs[0]);
        $this->assertArrayHasKey('response', $logs[0]);
        $this->assertArrayHasKey('duration', $logs[0]);
    }

    /**
     * Teste: Cache de reconhecimento
     */
    public function testRecognitionCache(): void
    {
        $mockResponse = ['success' => true, 'recognized' => true, 'employee_id' => 1];
        $this->deepFaceService->setMockResponse($mockResponse, 200);

        // Primeira chamada deve fazer requisição
        $result1 = $this->deepFaceService->recognizeWithCache('/path/to/photo.jpg');
        $this->assertTrue($result1['success']);
        $this->assertFalse($result1['from_cache'] ?? false);

        // Segunda chamada com mesma foto deve retornar do cache
        $result2 = $this->deepFaceService->recognizeWithCache('/path/to/photo.jpg');
        $this->assertTrue($result2['success']);
        $this->assertTrue($result2['from_cache'] ?? false);
    }
}
