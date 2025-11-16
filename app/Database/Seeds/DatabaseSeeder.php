<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        echo "\n";
        echo "========================================\n";
        echo "  DATABASE SEEDING - PONTO ELETRÔNICO  \n";
        echo "========================================\n\n";

        // Seed in correct order (respecting foreign keys)

        echo "1️⃣  Seeding Admin User...\n";
        echo "----------------------------------------\n";
        $this->call('AdminUserSeeder');
        echo "\n";

        echo "2️⃣  Seeding Settings...\n";
        echo "----------------------------------------\n";
        $this->call('SettingsSeeder');
        echo "\n";

        echo "3️⃣  Seeding Geofences...\n";
        echo "----------------------------------------\n";
        $this->call('GeofenceSeeder');
        echo "\n";

        echo "========================================\n";
        echo "  ✅ DATABASE SEEDING COMPLETED!        \n";
        echo "========================================\n\n";

        echo "🎯 Next Steps:\n";
        echo "   1. Access the system: http://localhost:8000\n";
        echo "   2. Login with:\n";
        echo "      Email: admin@ponto.com.br\n";
        echo "      Password: Admin@123\n";
        echo "   3. ⚠️  IMPORTANT: Change the admin password!\n";
        echo "   4. Configure company settings\n";
        echo "   5. Add employees\n";
        echo "   6. Configure geofences\n\n";
    }
}
