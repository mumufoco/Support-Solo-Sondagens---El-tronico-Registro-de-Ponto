<?php

namespace Tests\Feature\Controllers;

use App\Models\EmployeeModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * AuthController Feature Tests
 */
class AuthControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate = true;
    protected $migrateOnce = false;
    protected $refresh = true;
    protected $namespace = 'App';
    protected $seed = 'App\Database\Seeds\TestSeeder';

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testLoginPageIsAccessible()
    {
        $result = $this->get('/login');

        $result->assertStatus(200);
        $result->assertSee('Login');
        $result->assertSee('Email');
        $result->assertSee('Senha');
    }

    public function testLoginWithValidCredentials()
    {
        $result = $this->post('/login', [
            'email' => 'admin@pontoeletronico.com.br',
            'password' => 'admin123',
        ]);

        $result->assertRedirectTo('/dashboard');
        $this->assertTrue(session()->has('employee_id'));
    }

    public function testLoginWithInvalidCredentials()
    {
        $result = $this->post('/login', [
            'email' => 'admin@pontoeletronico.com.br',
            'password' => 'wrong-password',
        ]);

        $result->assertRedirectTo('/login');
        $result->assertSessionHas('error');
        $this->assertFalse(session()->has('employee_id'));
    }

    public function testLoginWithNonExistentEmail()
    {
        $result = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $result->assertRedirectTo('/login');
        $result->assertSessionHas('error');
    }

    public function testLoginWithInactiveAccount()
    {
        // Create inactive employee
        $employeeModel = new EmployeeModel();
        $employeeId = $employeeModel->insert([
            'name' => 'Funcionário Inativo',
            'cpf' => '888.888.888-88',
            'email' => 'inactive@example.com',
            'password' => password_hash('senha123', PASSWORD_ARGON2ID),
            'role' => 'funcionario',
            'department' => 'RH',
            'active' => false,
        ]);

        $result = $this->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'senha123',
        ]);

        $result->assertRedirectTo('/login');
        $result->assertSessionHas('error');
    }

    public function testLoginValidation()
    {
        // Missing email
        $result = $this->post('/login', [
            'password' => 'password123',
        ]);

        $result->assertRedirectTo('/login');
        $result->assertSessionHas('errors');

        // Missing password
        $result = $this->post('/login', [
            'email' => 'admin@pontoeletronico.com.br',
        ]);

        $result->assertRedirectTo('/login');
        $result->assertSessionHas('errors');
    }

    public function testLogout()
    {
        // First login
        $this->post('/login', [
            'email' => 'admin@pontoeletronico.com.br',
            'password' => 'admin123',
        ]);

        $this->assertTrue(session()->has('employee_id'));

        // Then logout
        $result = $this->get('/logout');

        $result->assertRedirectTo('/login');
        $this->assertFalse(session()->has('employee_id'));
    }

    public function testForgotPasswordPageIsAccessible()
    {
        $result = $this->get('/forgot-password');

        $result->assertStatus(200);
        $result->assertSee('Recuperar Senha');
    }

    public function testForgotPasswordWithValidEmail()
    {
        $result = $this->post('/forgot-password', [
            'email' => 'admin@pontoeletronico.com.br',
        ]);

        $result->assertRedirectTo('/login');
        $result->assertSessionHas('success');
    }

    public function testForgotPasswordWithInvalidEmail()
    {
        $result = $this->post('/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $result->assertRedirectTo('/forgot-password');
        $result->assertSessionHas('error');
    }

    public function testAuthenticatedUserCanAccessDashboard()
    {
        // Login first
        $this->post('/login', [
            'email' => 'admin@pontoeletronico.com.br',
            'password' => 'admin123',
        ]);

        $result = $this->get('/dashboard');

        $result->assertStatus(200);
        $result->assertSee('Dashboard');
    }

    public function testUnauthenticatedUserCannotAccessDashboard()
    {
        $result = $this->get('/dashboard');

        $result->assertRedirectTo('/login');
    }

    public function testRememberMeFunctionality()
    {
        $result = $this->post('/login', [
            'email' => 'admin@pontoeletronico.com.br',
            'password' => 'admin123',
            'remember' => '1',
        ]);

        $result->assertRedirectTo('/dashboard');
        $this->assertTrue(session()->has('employee_id'));

        // Cookie should be set (implementation dependent)
        // This is a placeholder for actual cookie testing
    }
}
