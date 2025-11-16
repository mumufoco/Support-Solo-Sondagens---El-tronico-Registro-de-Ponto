<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AuthGroupsSeeder extends Seeder
{
    public function run()
    {
        echo "Creating auth groups for CodeIgniter Shield...\n";

        // Verificar se a tabela existe
        if (!$this->db->tableExists('auth_groups')) {
            echo "ERROR: Table 'auth_groups' does not exist.\n";
            echo "Please run Shield migrations first: php spark migrate --all\n";
            return;
        }

        // Limpar grupos existentes (opcional - comentar se não quiser limpar)
        // $this->db->table('auth_groups')->truncate();
        // $this->db->table('auth_permissions')->truncate();
        // $this->db->table('auth_groups_permissions')->truncate();

        // Criar grupos
        $groups = [
            [
                'name' => 'admin',
                'description' => 'Administrador - Acesso Total ao Sistema',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'gestor',
                'description' => 'Gestor - Gerencia Equipe e Aprovações',
                'created_at' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'funcionario',
                'description' => 'Funcionário - Registro de Ponto',
                'created_at' => date('Y-m-d H:i:s'),
            ],
        ];

        foreach ($groups as $group) {
            // Verificar se já existe
            $existing = $this->db->table('auth_groups')
                ->where('name', $group['name'])
                ->get()
                ->getRow();

            if (!$existing) {
                $this->db->table('auth_groups')->insert($group);
                echo "✓ Created group: {$group['name']}\n";
            } else {
                echo "- Group already exists: {$group['name']}\n";
            }
        }

        // Criar permissões
        $permissions = [
            // Admin - todas permissões
            ['name' => 'admin.*', 'description' => 'All admin permissions'],

            // Gestor
            ['name' => 'manage.employees', 'description' => 'Manage employees'],
            ['name' => 'approve.justifications', 'description' => 'Approve justifications'],
            ['name' => 'view.reports', 'description' => 'View reports'],
            ['name' => 'manage.team', 'description' => 'Manage team'],

            // Funcionário
            ['name' => 'clock.inout', 'description' => 'Clock in/out'],
            ['name' => 'view.own.data', 'description' => 'View own data'],
            ['name' => 'submit.justification', 'description' => 'Submit justification'],
        ];

        foreach ($permissions as $permission) {
            $existing = $this->db->table('auth_permissions')
                ->where('name', $permission['name'])
                ->get()
                ->getRow();

            if (!$existing) {
                $permission['created_at'] = date('Y-m-d H:i:s');
                $this->db->table('auth_permissions')->insert($permission);
                echo "✓ Created permission: {$permission['name']}\n";
            }
        }

        // Associar permissões aos grupos
        $groupPermissions = [
            'admin' => ['admin.*'],
            'gestor' => ['manage.employees', 'approve.justifications', 'view.reports', 'manage.team', 'clock.inout', 'view.own.data'],
            'funcionario' => ['clock.inout', 'view.own.data', 'submit.justification'],
        ];

        foreach ($groupPermissions as $groupName => $permissionNames) {
            $group = $this->db->table('auth_groups')
                ->where('name', $groupName)
                ->get()
                ->getRow();

            if (!$group) {
                continue;
            }

            foreach ($permissionNames as $permName) {
                $permission = $this->db->table('auth_permissions')
                    ->where('name', $permName)
                    ->get()
                    ->getRow();

                if (!$permission) {
                    continue;
                }

                // Verificar se já existe a associação
                $existing = $this->db->table('auth_groups_permissions')
                    ->where('group_id', $group->id)
                    ->where('permission_id', $permission->id)
                    ->get()
                    ->getRow();

                if (!$existing) {
                    $this->db->table('auth_groups_permissions')->insert([
                        'group_id' => $group->id,
                        'permission_id' => $permission->id,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    echo "✓ Associated permission '{$permName}' to group '{$groupName}'\n";
                }
            }
        }

        echo "\n✅ Auth groups seeder completed successfully!\n";
        echo "\nGroups created:\n";
        echo "  1. admin - Full system access\n";
        echo "  2. gestor - Team management and approvals\n";
        echo "  3. funcionario - Time clock access\n";
    }
}
