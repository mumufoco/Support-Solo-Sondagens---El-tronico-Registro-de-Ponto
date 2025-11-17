<?php

namespace App\Services\Backup;

use CodeIgniter\I18n\Time;
use Config\Database;

/**
 * Database Backup Service
 *
 * Realiza backup automatizado do banco de dados MySQL
 *
 * Funcionalidades:
 * - Backup completo do banco
 * - Compressão automática (gzip)
 * - Rotação de backups antigos
 * - Upload para S3/FTP (opcional)
 * - Notificação de sucesso/falha
 */
class DatabaseBackupService
{
    protected string $backupPath;
    protected array $dbConfig;
    protected int $daysToKeep = 30;

    public function __construct()
    {
        $this->backupPath = WRITEPATH . 'backups/database/';

        // Criar diretório se não existir
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }

        // Configuração do banco
        $db = Database::connect();
        $this->dbConfig = $db->getConnectOptions();
    }

    /**
     * Cria backup do banco de dados
     */
    public function createBackup(): array
    {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "backup_{$this->dbConfig['database']}_{$timestamp}.sql";
        $filepath = $this->backupPath . $filename;
        $gzipPath = $filepath . '.gz';

        try {
            // Comando mysqldump
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s --port=%s %s > %s 2>&1',
                escapeshellarg($this->dbConfig['username']),
                escapeshellarg($this->dbConfig['password']),
                escapeshellarg($this->dbConfig['hostname']),
                escapeshellarg($this->dbConfig['port'] ?? 3306),
                escapeshellarg($this->dbConfig['database']),
                escapeshellarg($filepath)
            );

            // Executar backup
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \RuntimeException('Falha ao executar mysqldump: ' . implode("\n", $output));
            }

            // Verificar se arquivo foi criado
            if (!file_exists($filepath) || filesize($filepath) === 0) {
                throw new \RuntimeException('Arquivo de backup vazio ou não criado');
            }

            $filesize = filesize($filepath);

            // Comprimir com gzip
            $this->compressFile($filepath, $gzipPath);

            // Remover arquivo não comprimido
            unlink($filepath);

            $result = [
                'success' => true,
                'filename' => basename($gzipPath),
                'filepath' => $gzipPath,
                'size' => filesize($gzipPath),
                'original_size' => $filesize,
                'compression_ratio' => round((1 - filesize($gzipPath) / $filesize) * 100, 2),
                'timestamp' => $timestamp,
            ];

            // Log sucesso
            log_message('info', 'Backup criado com sucesso: ' . $filename);

            // Enviar notificação
            $this->sendNotification($result);

            return $result;

        } catch (\Exception $e) {
            $error = [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => $timestamp,
            ];

            log_message('error', 'Falha no backup: ' . $e->getMessage());
            $this->sendNotification($error);

            return $error;
        }
    }

    /**
     * Comprime arquivo com gzip
     */
    protected function compressFile(string $source, string $dest): void
    {
        $bufferSize = 4096;
        $file = fopen($source, 'rb');
        $zipped = gzopen($dest, 'wb9'); // Máxima compressão

        while (!feof($file)) {
            gzwrite($zipped, fread($file, $bufferSize));
        }

        fclose($file);
        gzclose($zipped);
    }

    /**
     * Remove backups antigos
     */
    public function cleanOldBackups(): int
    {
        $deleted = 0;
        $cutoffDate = Time::now()->subDays($this->daysToKeep);

        $files = glob($this->backupPath . 'backup_*.sql.gz');

        foreach ($files as $file) {
            $fileDate = filemtime($file);

            if ($fileDate < $cutoffDate->getTimestamp()) {
                if (unlink($file)) {
                    $deleted++;
                    log_message('info', 'Backup antigo removido: ' . basename($file));
                }
            }
        }

        return $deleted;
    }

    /**
     * Lista backups disponíveis
     */
    public function listBackups(): array
    {
        $backups = [];
        $files = glob($this->backupPath . 'backup_*.sql.gz');

        // Ordenar por data (mais recente primeiro)
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'filepath' => $file,
                'size' => filesize($file),
                'size_human' => $this->formatBytes(filesize($file)),
                'date' => date('Y-m-d H:i:s', filemtime($file)),
                'age_days' => floor((time() - filemtime($file)) / 86400),
            ];
        }

        return $backups;
    }

    /**
     * Restaura backup
     */
    public function restoreBackup(string $backupFile): array
    {
        try {
            if (!file_exists($backupFile)) {
                throw new \RuntimeException('Arquivo de backup não encontrado');
            }

            // Descomprimir
            $sqlFile = str_replace('.gz', '', $backupFile);
            $this->decompressFile($backupFile, $sqlFile);

            // Comando mysql restore
            $command = sprintf(
                'mysql --user=%s --password=%s --host=%s --port=%s %s < %s 2>&1',
                escapeshellarg($this->dbConfig['username']),
                escapeshellarg($this->dbConfig['password']),
                escapeshellarg($this->dbConfig['hostname']),
                escapeshellarg($this->dbConfig['port'] ?? 3306),
                escapeshellarg($this->dbConfig['database']),
                escapeshellarg($sqlFile)
            );

            exec($command, $output, $returnCode);

            // Remover arquivo SQL temporário
            unlink($sqlFile);

            if ($returnCode !== 0) {
                throw new \RuntimeException('Falha ao restaurar backup: ' . implode("\n", $output));
            }

            $result = [
                'success' => true,
                'filename' => basename($backupFile),
                'message' => 'Backup restaurado com sucesso',
            ];

            log_message('info', 'Backup restaurado: ' . basename($backupFile));

            return $result;

        } catch (\Exception $e) {
            $error = [
                'success' => false,
                'error' => $e->getMessage(),
            ];

            log_message('error', 'Falha ao restaurar backup: ' . $e->getMessage());

            return $error;
        }
    }

    /**
     * Descomprime arquivo gzip
     */
    protected function decompressFile(string $source, string $dest): void
    {
        $bufferSize = 4096;
        $file = gzopen($source, 'rb');
        $outFile = fopen($dest, 'wb');

        while (!gzeof($file)) {
            fwrite($outFile, gzread($file, $bufferSize));
        }

        fclose($outFile);
        gzclose($file);
    }

    /**
     * Envia notificação de backup
     */
    protected function sendNotification(array $result): void
    {
        $email = \Config\Services::email();
        $recipients = explode(',', env('BACKUP_EMAILS', env('ALERT_EMAILS', 'admin@supportsondagens.com.br')));

        if ($result['success']) {
            $subject = '[BACKUP OK] Backup do banco de dados concluído';
            $body = "
            <h2>Backup Realizado com Sucesso</h2>
            <p><strong>Arquivo:</strong> {$result['filename']}</p>
            <p><strong>Tamanho Original:</strong> " . $this->formatBytes($result['original_size']) . "</p>
            <p><strong>Tamanho Comprimido:</strong> " . $this->formatBytes($result['size']) . "</p>
            <p><strong>Taxa de Compressão:</strong> {$result['compression_ratio']}%</p>
            <p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>
            <hr>
            <p><em>Backup automático do Sistema de Ponto Eletrônico</em></p>
            ";
        } else {
            $subject = '[BACKUP FALHOU] Erro no backup do banco de dados';
            $body = "
            <h2>Falha no Backup</h2>
            <p><strong>Erro:</strong> {$result['error']}</p>
            <p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>
            <p style='color: red;'><strong>AÇÃO NECESSÁRIA:</strong> Verifique os logs do sistema e execute o backup manualmente.</p>
            <hr>
            <p><em>Alerta automático do Sistema de Ponto Eletrônico</em></p>
            ";
        }

        foreach ($recipients as $recipient) {
            $email->setTo(trim($recipient));
            $email->setSubject($subject);
            $email->setMessage($body);

            try {
                $email->send();
            } catch (\Exception $e) {
                log_message('error', 'Falha ao enviar notificação de backup: ' . $e->getMessage());
            }
        }
    }

    /**
     * Formata bytes em formato legível
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Define dias para manter backups
     */
    public function setDaysToKeep(int $days): void
    {
        $this->daysToKeep = $days;
    }
}
