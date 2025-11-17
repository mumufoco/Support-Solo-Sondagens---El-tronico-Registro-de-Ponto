<?php

namespace Tests\Unit\Models;

use App\Models\EmployeeModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Employee Model Unit Tests
 */
class EmployeeModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = 'App';
    protected EmployeeModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new EmployeeModel();
    }

    public function testCreateEmployeeWithValidData()
    {
        $data = [
            'name' => 'João Silva Teste',
            'email' => 'joao.teste@empresa.com',
            'password' => 'SenhaForte123!@#',
            'cpf' => '123.456.789-00',
            'role' => 'funcionario',
        ];

        $id = $this->model->insert($data);
        $this->assertIsNumeric($id);
        $this->assertGreaterThan(0, $id);
    }

    public function testPasswordIsHashedOnCreate()
    {
        $password = 'SenhaForte123!@#';

        $data = [
            'name' => 'Maria Teste',
            'email' => 'maria.teste@empresa.com',
            'password' => $password,
            'cpf' => '987.654.321-00',
            'role' => 'funcionario',
        ];

        $id = $this->model->insert($data);
        $employee = $this->model->find($id);

        $this->assertNotEquals($password, $employee->password);
        $this->assertTrue(password_verify($password, $employee->password));
    }

    public function testFindByEmail()
    {
        $email = 'busca@empresa.com';

        $id = $this->model->insert([
            'name' => 'Funcionário Busca',
            'email' => $email,
            'password' => 'SenhaForte123!@#',
            'cpf' => '555.666.777-88',
            'role' => 'funcionario',
        ]);

        $employee = $this->model->findByEmail($email);

        $this->assertNotNull($employee);
        $this->assertEquals($id, $employee->id);
    }
}
