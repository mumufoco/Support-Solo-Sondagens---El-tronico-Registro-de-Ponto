<?php

/**
 * MIGRATION RUNNER - TEMPORARY SCRIPT
 *
 * Execute migrations via browser
 * DELETE THIS FILE after migrations complete!
 *
 * Access: https://ponto.supportsondagens.com.br/migrate.php
 */

// Security: Only allow from localhost or specific IP
$allowedIPs = [
    '127.0.0.1',
    '::1',
    '51.222.31.175', // VPS IP
];

if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIPs, true)) {
    die('Access denied. This script can only be run from authorized IPs.');
}

// Load CodeIgniter
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();

require FCPATH . '../vendor/autoload.php';

// Bootstrap the framework
require $paths->systemDirectory . '/Boot.php';

// Get the CodeIgniter instance
$app = \CodeIgniter\Config\Services::codeigniter();
$app->initialize();

// Load database
$db = \Config\Database::connect();

echo "<h1>Sistema de Ponto Eletrônico - Database Migration</h1>\n";
echo "<pre>\n";

try {
    // Run migrations
    $migrate = \Config\Services::migrations();

    echo "Running migrations...\n";

    if ($migrate->latest()) {
        echo "\n✅ SUCCESS! All migrations executed successfully!\n\n";

        // Show migration status
        echo "Migration History:\n";
        echo "=================\n";

        $history = $migrate->getHistory();

        if (empty($history)) {
            echo "No migrations found.\n";
        } else {
            foreach ($history as $batch => $migrations) {
                echo "\nBatch $batch:\n";
                foreach ($migrations as $migration) {
                    echo "  - " . $migration['version'] . " : " . $migration['class'] . "\n";
                }
            }
        }

        echo "\n✅ Database is ready!\n";
        echo "\n⚠️  DELETE THIS FILE (migrate.php) NOW!\n";

    } else {
        echo "\n❌ ERROR running migrations!\n";
        echo "Error: " . $migrate->error ?? 'Unknown error';
    }

} catch (\Exception $e) {
    echo "\n❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";

echo "\n<hr>\n";
echo "<h2>Database Connection Test:</h2>\n";
echo "<pre>\n";

try {
    $db = \Config\Database::connect();
    echo "✅ Database connected successfully!\n";
    echo "Database: " . $db->database . "\n";
    echo "Host: " . $db->hostname . "\n";

    // List tables
    $tables = $db->listTables();
    echo "\nTables in database:\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }

} catch (\Exception $e) {
    echo "❌ Database connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
