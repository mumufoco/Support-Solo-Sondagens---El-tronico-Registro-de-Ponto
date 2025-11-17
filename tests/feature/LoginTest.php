<?php

namespace Tests\Feature;

use App\Models\EmployeeModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Login Feature Tests
 */
class LoginTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $refresh = true;
    protected $namespace = 'App';

    protected function setUp(): void
    {
        parent::setUp();

        // Criar usuário de teste
        $model = new EmployeeModel();
        $model->insert([
            'name' => 'Test User',
            'email' => 'test@empresa.com',
            'password' => 'TestPassword123!@#',
            'cpf' => '123.456.789-00',
            'role' => 'funcionario',
            'active' => true,
        ]);
    }

    public function testLoginPageLoads()
    {
        $result = $this->get('/auth/login');

        $result->assertOK();
        $result->assertSee('Login');
    }

    public function testLoginWithValidCredentials()
    {
        $result = $this->post('/auth/authenticate', [
            'email' => 'test@empresa.com',
            'password' => 'TestPassword123!@#',
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('logged_in', true);
    }

    public function testLoginWithInvalidPassword()
    {
        $result = $this->post('/auth/authenticate', [
            'email' => 'test@empresa.com',
            'password' => 'WrongPassword123!@#',
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('error');
    }

    public function testLoginWithNonExistentEmail()
    {
        $result = $this->post('/auth/authenticate', [
            'email' => 'nonexistent@empresa.com',
            'password' => 'TestPassword123!@#',
        ]);

        $result->assertRedirect();
        $result->assertSessionHas('error');
    }
}
