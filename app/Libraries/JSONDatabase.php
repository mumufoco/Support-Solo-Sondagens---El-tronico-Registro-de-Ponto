<?php
/**
 * MySQL Emulator using JSON Files
 * Intercepta conexões MySQL e usa arquivos JSON
 */

namespace App\Libraries;

class JSONDatabase
{
    private $dataDir = 'writable/database/';
    private $tables = [];

    public function __construct()
    {
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0777, true);
        }
    }

    public function query($sql)
    {
        // Parse SQL simples
        $sql = trim($sql);

        // SELECT 1 (health check)
        if (preg_match('/^SELECT\s+1/i', $sql)) {
            return ['result' => 1];
        }

        // SELECT * FROM employees
        if (preg_match('/^SELECT\s+.*FROM\s+(\w+)/i', $sql, $matches)) {
            $table = $matches[1];
            return $this->select($table);
        }

        // INSERT INTO
        if (preg_match('/^INSERT\s+INTO\s+(\w+)/i', $sql, $matches)) {
            $table = $matches[1];
            return $this->insert($table, []);
        }

        return true;
    }

    public function select($table)
    {
        $file = $this->dataDir . $table . '.json';
        if (!file_exists($file)) {
            return [];
        }

        $data = json_decode(file_get_contents($file), true);
        return $data ?? [];
    }

    public function insert($table, $data)
    {
        $file = $this->dataDir . $table . '.json';
        $existing = [];

        if (file_exists($file)) {
            $existing = json_decode(file_get_contents($file), true) ?? [];
        }

        $data['id'] = count($existing) + 1;
        $existing[] = $data;

        file_put_contents($file, json_encode($existing, JSON_PRETTY_PRINT));
        return $data['id'];
    }

    public function exec($sql)
    {
        return $this->query($sql);
    }
}
