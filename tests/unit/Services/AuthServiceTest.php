<?php

namespace Tests\Unit\Services;

use App\Services\Auth\AuthService;
use App\Models\EmployeeModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * Testes Unitários para AuthService
 *
 * Testa autenticação, proteção brute force, e validações de senha
 */
class AuthServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = null;

    protected AuthService $authService;
    protected EmployeeModel $employeeModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authService = new AuthService();
        $this->employeeModel = new EmployeeModel();

        // Criar usuário de teste
        $this->employeeModel->insert([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'cpf' => '12345678901',
            'password' => password_hash('Password@123', PASSWORD_ARGON2ID),
            'role' => 'employee',
            'active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Teste: Login com credenciais válidas deve retornar dados do usuário
     */
    public function testLoginValid(): void
    {
        $result = $this->authService->login('test@example.com', 'Password@123');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals('Test User', $result['name']);
    }

    /**
     * Teste: Login com credenciais inválidas deve retornar false
     */
    public function testLoginInvalid(): void
    {
        $result = $this->authService->login('test@example.com', 'WrongPassword');

        $this->assertFalse($result);
    }

    /**
     * Teste: Login com email não existente deve retornar false
     */
    public function testLoginNonExistentEmail(): void
    {
        $result = $this->authService->login('nonexistent@example.com', 'Password@123');

        $this->assertFalse($result);
    }

    /**
     * Teste: Senha deve ser hasheada com Argon2id
     */
    public function testPasswordHash(): void
    {
        $password = 'TestPassword@123';
        $hash = $this->authService->hashPassword($password);

        // Verifica se é um hash válido
        $this->assertNotEquals($password, $hash);

        // Verifica se o Argon2id está sendo usado
        $this->assertStringContainsString('$argon2id$', $hash);

        // Verifica se o password_verify funciona
        $this->assertTrue(password_verify($password, $hash));
    }

    /**
     * Teste: Proteção Brute Force - 5 tentativas devem bloquear por 15 minutos
     */
    public function testBruteForceProtection(): void
    {
        $email = 'test@example.com';

        // Fazer 5 tentativas falhas
        for ($i = 0; $i < 5; $i++) {
            $this->authService->login($email, 'WrongPassword');
        }

        // 6ª tentativa deve ser bloqueada, mesmo com senha correta
        $result = $this->authService->login($email, 'Password@123');

        $this->assertFalse($result);

        // Verificar mensagem de bloqueio
        $blocked = $this->authService->isBlocked($email);
        $this->assertTrue($blocked);
    }

    /**
     * Teste: Verificar se bloqueio expira após 15 minutos
     */
    public function testBruteForceExpiration(): void
    {
        $email = 'test@example.com';

        // Simular 5 tentativas falhas
        for ($i = 0; $i < 5; $i++) {
            $this->authService->recordFailedAttempt($email);
        }

        // Usuário deve estar bloqueado
        $this->assertTrue($this->authService->isBlocked($email));

        // Simular passagem de 15 minutos (900 segundos)
        // Usando manipulação de tempo no banco de dados
        $db = Database::connect();
        $db->query("UPDATE login_attempts SET created_at = DATE_SUB(NOW(), INTERVAL 16 MINUTE) WHERE email = ?", [$email]);

        // Agora não deve estar mais bloqueado
        $this->assertFalse($this->authService->isBlocked($email));
    }

    /**
     * Teste: Tentativa bem-sucedida deve limpar histórico de falhas
     */
    public function testSuccessfulLoginClearsFailedAttempts(): void
    {
        $email = 'test@example.com';

        // Fazer 3 tentativas falhas
        for ($i = 0; $i < 3; $i++) {
            $this->authService->login($email, 'WrongPassword');
        }

        // Login bem-sucedido
        $this->authService->login($email, 'Password@123');

        // Contador de tentativas deve estar zerado
        $attempts = $this->authService->getFailedAttempts($email);
        $this->assertEquals(0, $attempts);
    }

    /**
     * Teste: Validação de senha forte
     */
    public function testPasswordValidation(): void
    {
        // Senha válida: 8+ chars, maiúscula, minúscula, número, especial
        $this->assertTrue($this->authService->validatePasswordStrength('Password@123'));

        // Senha muito curta
        $this->assertFalse($this->authService->validatePasswordStrength('Pass@1'));

        // Sem maiúscula
        $this->assertFalse($this->authService->validatePasswordStrength('password@123'));

        // Sem minúscula
        $this->assertFalse($this->authService->validatePasswordStrength('PASSWORD@123'));

        // Sem número
        $this->assertFalse($this->authService->validatePasswordStrength('Password@abc'));

        // Sem caractere especial
        $this->assertFalse($this->authService->validatePasswordStrength('Password123'));
    }

    /**
     * Teste: Usuário inativo não pode fazer login
     */
    public function testInactiveUserCannotLogin(): void
    {
        // Criar usuário inativo
        $this->employeeModel->insert([
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'cpf' => '98765432100',
            'password' => password_hash('Password@123', PASSWORD_ARGON2ID),
            'role' => 'employee',
            'active' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $result = $this->authService->login('inactive@example.com', 'Password@123');

        $this->assertFalse($result);
    }

    /**
     * Teste: Geração de token JWT para API
     */
    public function testGenerateJWT(): void
    {
        $userId = 1;
        $email = 'test@example.com';

        $token = $this->authService->generateJWT($userId, $email);

        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        // JWT tem 3 partes separadas por '.'
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
    }

    /**
     * Teste: Validação de token JWT
     */
    public function testValidateJWT(): void
    {
        $userId = 1;
        $email = 'test@example.com';

        $token = $this->authService->generateJWT($userId, $email);
        $payload = $this->authService->validateJWT($token);

        $this->assertIsArray($payload);
        $this->assertEquals($userId, $payload['user_id']);
        $this->assertEquals($email, $payload['email']);
    }

    /**
     * Teste: Token JWT inválido deve retornar false
     */
    public function testInvalidJWT(): void
    {
        $invalidToken = 'invalid.token.here';

        $result = $this->authService->validateJWT($invalidToken);

        $this->assertFalse($result);
    }
}
