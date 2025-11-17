<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\Backup\DatabaseBackupService;

/**
 * Database Backup Command
 *
 * Comando para gerenciar backups do banco de dados
 *
 * Uso:
 * php spark backup:database          - Cria backup
 * php spark backup:database --clean  - Remove backups antigos
 * php spark backup:database --list   - Lista backups disponíveis
 */
class DatabaseBackup extends BaseCommand
{
    protected $group       = 'Backup';
    protected $name        = 'backup:database';
    protected $description = 'Cria e gerencia backups do banco de dados';

    protected $usage = 'backup:database [options]';
    protected $arguments = [];
    protected $options = [
        '--clean'  => 'Remove backups antigos (>30 dias)',
        '--list'   => 'Lista todos os backups disponíveis',
    ];

    public function run(array $params)
    {
        CLI::write('===========================================', 'blue');
        CLI::write('  Sistema de Backup do Banco de Dados', 'blue');
        CLI::write('===========================================', 'blue');
        CLI::newLine();

        $backup = new DatabaseBackupService();

        // Listar backups
        if (CLI::getOption('list')) {
            $backups = $backup->listBackups();

            if (empty($backups)) {
                CLI::write('Nenhum backup encontrado.', 'yellow');
                return;
            }

            CLI::write('Backups Disponíveis:', 'cyan');
            CLI::write('─────────────────────────────────────────────────────────────────────', 'cyan');

            foreach ($backups as $b) {
                CLI::write("Arquivo: {$b['filename']}", 'white');
                CLI::write("  Data: {$b['date']} ({$b['age_days']} dias atrás)", 'white');
                CLI::write("  Tamanho: {$b['size_human']}", 'white');
                CLI::newLine();
            }

            CLI::write("Total: " . count($backups) . " backup(s)", 'green');
            return;
        }

        // Limpar backups antigos
        if (CLI::getOption('clean')) {
            CLI::write('Removendo backups antigos (>30 dias)...', 'yellow');
            $deleted = $backup->cleanOldBackups();
            CLI::write("✓ {$deleted} backup(s) antigo(s) removido(s)", 'green');
            CLI::newLine();
            return;
        }

        // Criar backup
        CLI::write('Iniciando backup do banco de dados...', 'yellow');
        CLI::newLine();

        $result = $backup->createBackup();

        if ($result['success']) {
            CLI::write('✓ Backup criado com sucesso!', 'green');
            CLI::newLine();
            CLI::write('Detalhes:', 'cyan');
            CLI::write('─────────────────────────────────────────', 'cyan');
            CLI::write("Arquivo: {$result['filename']}", 'white');
            CLI::write("Tamanho Original: " . $this->formatBytes($result['original_size']), 'white');
            CLI::write("Tamanho Comprimido: " . $this->formatBytes($result['size']), 'white');
            CLI::write("Taxa de Compressão: {$result['compression_ratio']}%", 'white');
            CLI::write("Localização: {$result['filepath']}", 'white');
            CLI::newLine();
            CLI::write('Notificações enviadas para: ' . env('BACKUP_EMAILS', 'admin@supportsondagens.com.br'), 'white');
        } else {
            CLI::write('✗ Falha ao criar backup!', 'red');
            CLI::write('Erro: ' . $result['error'], 'red');
            CLI::newLine();
        }

        CLI::newLine();
        CLI::write('Backup concluído.', 'blue');
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
