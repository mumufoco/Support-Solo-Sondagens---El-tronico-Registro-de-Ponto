<?php

namespace Tests\Unit\Models;

use App\Models\EmployeeModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * EmployeeModel Unit Tests
 */
class EmployeeModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $migrateOnce = false;
    protected $refresh = true;
    protected $namespace = 'App';
    protected $seed = 'App\Database\Seeds\TestSeeder';

    protected EmployeeModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new EmployeeModel();
    }

    public function testEmployeeCanBeCreated()
    {
        $data = [
            'name' => 'João Silva',
            'cpf' => '123.456.789-00',
            'email' => 'joao.silva@example.com',
            'password' => password_hash('senha123', PASSWORD_ARGON2ID),
            'role' => 'funcionario',
            'department' => 'TI',
            'active' => true,
        ];

        $employeeId = $this->model->insert($data);

        $this->assertIsInt($employeeId);
        $this->assertGreaterThan(0, $employeeId);

        $employee = $this->model->find($employeeId);
        $this->assertEquals('João Silva', $employee->name);
        $this->assertEquals('joao.silva@example.com', $employee->email);
    }

    public function testEmployeeCanBeFoundByEmail()
    {
        $employee = $this->model->where('email', 'admin@pontoeletronico.com.br')->first();

        $this->assertNotNull($employee);
        $this->assertEquals('Administrador do Sistema', $employee->name);
    }

    public function testEmployeeCanBeFoundByCpf()
    {
        $employee = $this->model->where('cpf', '000.000.000-00')->first();

        $this->assertNotNull($employee);
        $this->assertEquals('Administrador do Sistema', $employee->name);
    }

    public function testActiveEmployeesOnly()
    {
        $activeEmployees = $this->model->where('active', true)->findAll();

        foreach ($activeEmployees as $employee) {
            $this->assertTrue($employee->active);
        }
    }

    public function testEmployeesFilteredByDepartment()
    {
        $department = 'TI';
        $employees = $this->model->where('department', $department)->findAll();

        foreach ($employees as $employee) {
            $this->assertEquals($department, $employee->department);
        }
    }

    public function testEmployeesFilteredByRole()
    {
        $role = 'admin';
        $employees = $this->model->where('role', $role)->findAll();

        foreach ($employees as $employee) {
            $this->assertEquals($role, $employee->role);
        }
    }

    public function testEmployeeCanBeUpdated()
    {
        $employee = $this->model->where('email', 'admin@pontoeletronico.com.br')->first();
        $originalName = $employee->name;

        $this->model->update($employee->id, ['name' => 'Nome Atualizado']);

        $updatedEmployee = $this->model->find($employee->id);
        $this->assertEquals('Nome Atualizado', $updatedEmployee->name);

        // Restore
        $this->model->update($employee->id, ['name' => $originalName]);
    }

    public function testEmployeeCanBeSoftDeleted()
    {
        $data = [
            'name' => 'Funcionário Temporário',
            'cpf' => '999.999.999-99',
            'email' => 'temp@example.com',
            'password' => password_hash('senha123', PASSWORD_ARGON2ID),
            'role' => 'funcionario',
            'department' => 'RH',
            'active' => true,
        ];

        $employeeId = $this->model->insert($data);

        // Deactivate instead of delete
        $this->model->update($employeeId, ['active' => false]);

        $employee = $this->model->find($employeeId);
        $this->assertFalse($employee->active);
    }

    public function testEmployeeValidation()
    {
        $invalidData = [
            'name' => 'A', // Too short
            'email' => 'invalid-email', // Invalid format
            'cpf' => '123', // Invalid CPF
        ];

        $result = $this->model->insert($invalidData);

        $this->assertFalse($result);
        $this->assertNotEmpty($this->model->errors());
    }
}
