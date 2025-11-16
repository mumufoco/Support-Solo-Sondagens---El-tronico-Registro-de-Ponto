<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class GeofenceSeeder extends Seeder
{
    public function run()
    {
        $db = \Config\Database::connect();
        $builder = $db->table('geofences');

        // Get admin user ID for created_by
        $adminUser = $db->table('employees')
            ->where('email', 'admin@ponto.com.br')
            ->get()
            ->getRow();

        if (!$adminUser) {
            echo "⚠️  Admin user not found. Run AdminUserSeeder first.\n";
            return;
        }

        // Example geofences
        $geofences = [
            [
                'name'          => 'Sede - Escritório Principal',
                'description'   => 'Cerca virtual da sede da empresa',
                'center_lat'    => -23.5505, // São Paulo - Praça da Sé (exemplo)
                'center_lng'    => -46.6333,
                'radius_meters' => 100,
                'address'       => 'Praça da Sé - Centro Histórico de São Paulo, São Paulo - SP',
                'active'        => true,
                'color'         => '#3388ff', // Azul
                'created_by'    => $adminUser->id,
            ],
            [
                'name'          => 'Filial - Zona Sul',
                'description'   => 'Cerca virtual da filial zona sul',
                'center_lat'    => -23.6236, // Morumbi (exemplo)
                'center_lng'    => -46.6997,
                'radius_meters' => 150,
                'address'       => 'Av. Roque Petroni Júnior - Morumbi, São Paulo - SP',
                'active'        => false, // Desativada por padrão
                'color'         => '#ff6b6b', // Vermelho
                'created_by'    => $adminUser->id,
            ],
            [
                'name'          => 'Depósito - Zona Leste',
                'description'   => 'Cerca virtual do depósito',
                'center_lat'    => -23.5418, // Tatuapé (exemplo)
                'center_lng'    => -46.5733,
                'radius_meters' => 200,
                'address'       => 'Praça Silvio Romero - Tatuapé, São Paulo - SP',
                'active'        => false, // Desativada por padrão
                'color'         => '#51cf66', // Verde
                'created_by'    => $adminUser->id,
            ],
        ];

        // Insert geofences
        $insertedCount = 0;
        foreach ($geofences as $geofence) {
            // Check if geofence already exists
            $existing = $builder->where('name', $geofence['name'])->get()->getRow();

            if (!$existing) {
                $geofence['created_at'] = date('Y-m-d H:i:s');
                $geofence['updated_at'] = date('Y-m-d H:i:s');
                $builder->insert($geofence);
                $insertedCount++;
                echo "   ✓ Geofence '{$geofence['name']}' created\n";
            } else {
                echo "   - Geofence '{$geofence['name']}' already exists\n";
            }
        }

        echo "\n✅ Geofences seeded successfully!\n";
        echo "   Total geofences: " . count($geofences) . "\n";
        echo "   New geofences inserted: {$insertedCount}\n";
        echo "   Active geofences: 1 (Sede - Escritório Principal)\n";
        echo "   Inactive geofences: 2 (activate via admin panel)\n";
    }
}
