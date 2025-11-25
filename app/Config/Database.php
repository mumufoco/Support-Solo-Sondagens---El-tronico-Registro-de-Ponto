<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Define APPPATH if not already defined (for standalone script usage)
 * This allows Database.php to be loaded outside of CI4 boot process
 */
if (!defined('APPPATH')) {
    define('APPPATH', realpath(__DIR__ . '/../') . DIRECTORY_SEPARATOR);
}

class Database extends Config
{
    /**
     * The directory that holds the Migrations and Seeds directories.
     */
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    /**
     * Lets you choose which connection group to use if no other is specified.
     */
    public string $defaultGroup = 'default';

    /**
     * The default database connection.
     * Now configured to use environment variables for flexibility
     *
     * PRODUCTION: Uses MySQL/MariaDB
     * DEVELOPMENT: Can use SQLite for local testing
     */
    public array $default = [
        'DSN'          => '',
        'hostname'     => '',
        'username'     => '',
        'password'     => '',
        'database'     => '',
        'DBDriver'     => 'MySQLi',  // Changed from Postgre to MySQLi for production
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'      => (ENVIRONMENT !== 'production'),
        'charset'      => 'utf8mb4',
        'DBCollat'     => 'utf8mb4_general_ci',
        'swapPre'      => '',
        'encrypt'      => false,
        'compress'     => false,
        'strictOn'     => false,
        'failover'     => [],
        'port'         => 3306,  // Changed from 6543 (Postgre) to 3306 (MySQL)
        'numberNative' => false,
        'dateFormat'   => [
            'date'     => 'Y-m-d',
            'datetime' => 'Y-m-d H:i:s',
            'time'     => 'H:i:s',
        ],
    ];

    /**
     * This database connection is used when running PHPUnit database tests.
     */
    public array $tests = [
        'DSN'         => '',
        'hostname'    => 'localhost',
        'username'    => 'root',
        'password'    => '',
        'database'    => 'ponto_eletronico_test',
        'DBDriver'    => 'MySQLi',
        'DBPrefix'    => '',
        'pConnect'    => false,
        'DBDebug'     => true,
        'charset'     => '',  // Will be set in constructor based on DBDriver
        'DBCollat'    => '',  // Will be set in constructor based on DBDriver
        'swapPre'     => '',
        'encrypt'     => false,
        'compress'    => false,
        'strictOn'    => false,
        'failover'    => [],
        'port'        => 3306,
    ];

    public function __construct()
    {
        parent::__construct();

        // Load database configuration from environment variables
        $this->default['hostname'] = env('database.default.hostname', 'localhost');
        $this->default['username'] = env('database.default.username', 'root');
        $this->default['password'] = env('database.default.password', '');
        $this->default['database'] = env('database.default.database', 'ponto_eletronico');
        $this->default['DBDriver'] = env('database.default.DBDriver', 'MySQLi');  // Default to MySQL for production
        $this->default['port']     = env('database.default.port', 3306);  // MySQL port
        $this->default['charset']  = env('database.default.charset', 'utf8mb4');
        $this->default['DBCollat'] = env('database.default.DBCollat', 'utf8mb4_general_ci');

        // Ensure that we always set the database group to 'tests' if
        // we are currently running an automated test suite, so that
        // we don't overwrite live data on accident.
        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';
        }

        // Set charset and collation based on database driver
        $driver = $this->default['DBDriver'];

        if ($driver === 'Postgre') {
            $this->default['charset'] = 'utf8';
            $this->default['DBCollat'] = 'utf8_general_ci';
        } elseif ($driver === 'SQLite3') {
            // SQLite doesn't use charset/collation the same way
            $this->default['charset'] = '';
            $this->default['DBCollat'] = '';
        } elseif (in_array($driver, ['MySQLi', 'SQLSRV'])) {
            $this->default['charset'] = 'utf8mb4';
            $this->default['DBCollat'] = 'utf8mb4_general_ci';
        }
    }
}
