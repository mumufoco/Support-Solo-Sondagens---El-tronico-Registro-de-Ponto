<?php
/**
 * Mock Database Bootstrap
 * Substitui o UserModel com dados mockados para testes sem banco de dados
 */

namespace App\Models;

use CodeIgniter\Model;

/**
 * UserModel Mockado para testes
 */
class UserModel extends Model
{
    protected $table = 'employees';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'name', 'email', 'password', 'cpf', 'unique_code', 'role',
        'department', 'position', 'active', 'manager_id'
    ];

    // Dados mockados de usuários
    private $mockUsers = [
        [
            'id' => 1,
            'name' => 'Admin Teste',
            'email' => 'admin@test.com',
            'password' => '$argon2id$v=19$m=65536,t=4,p=1$REhIbUVYMUg3dW5mQ2RaSw$vepYM5fblxV1Owul1IhX1uJ3Axy4nCceqlquYS8wtbg', // admin123
            'cpf' => '111.111.111-11',
            'unique_code' => 'ADM001',
            'role' => 'admin',
            'department' => 'TI',
            'position' => 'Administrador',
            'active' => 1,
            'manager_id' => null
        ],
        [
            'id' => 2,
            'name' => 'Gestor Teste',
            'email' => 'manager@test.com',
            'password' => '$argon2id$v=19$m=65536,t=4,p=1$MExmRXd4LjN0UkFId1BibQ$SPMwrYJ2JZ/VxN8/V+nJIOApRYB/zt1aoJIYQlsm3mE', // manager123
            'cpf' => '222.222.222-22',
            'unique_code' => 'MGR001',
            'role' => 'gestor',
            'department' => 'RH',
            'position' => 'Gerente',
            'active' => 1,
            'manager_id' => null
        ]
    ];

    public function findByEmail(string $email)
    {
        log_message('debug', '[MOCK UserModel] findByEmail called with: ' . $email);

        foreach ($this->mockUsers as $user) {
            if ($user['email'] === $email) {
                log_message('debug', '[MOCK UserModel] User found: ' . json_encode($user));
                return (object) $user;
            }
        }

        log_message('debug', '[MOCK UserModel] User not found for email: ' . $email);
        return null;
    }

    public function find($id = null)
    {
        log_message('debug', '[MOCK UserModel] find called with id: ' . $id);

        if ($id === null) {
            return $this->mockUsers;
        }

        foreach ($this->mockUsers as $user) {
            if ($user['id'] == $id) {
                log_message('debug', '[MOCK UserModel] User found by id: ' . json_encode($user));
                return (object) $user;
            }
        }

        log_message('debug', '[MOCK UserModel] User not found for id: ' . $id);
        return null;
    }

    public function where($field, $value = null)
    {
        // Retorna o próprio objeto para chain
        return $this;
    }

    public function first()
    {
        log_message('debug', '[MOCK UserModel] first() called');
        return !empty($this->mockUsers) ? (object) $this->mockUsers[0] : null;
    }
}

// Avisar que estamos usando mock
log_message('info', '🎭 [MOCK] UserModel mockado carregado com sucesso!');
log_message('info', '🎭 [MOCK] Credenciais de teste: admin@test.com / admin123');
