<?php
/**
 * POC 4: Teste de Performance de Queue com Redis
 *
 * Objetivo: Validar throughput e latência do sistema de filas
 * Critério de Sucesso: > 50 jobs/segundo com 1 worker
 *
 * REQUER: Docker Compose rodando com Redis
 * Instalar: composer require predis/predis
 */

// Verificar se Predis está disponível
if (!class_exists('Predis\Client')) {
    echo "========================================\n";
    echo "POC 4: TESTE DE PERFORMANCE REDIS\n";
    echo "========================================\n\n";
    echo "❌ Biblioteca Predis não encontrada!\n\n";
    echo "Instale com: composer require predis/predis\n\n";
    echo "Criando composer.json...\n";

    $composerJson = [
        'require' => [
            'predis/predis' => '^2.0'
        ]
    ];

    file_put_contents(__DIR__ . '/composer.json', json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    echo "✅ composer.json criado!\n";
    echo "\nExecute:\n";
    echo "  cd " . __DIR__ . "\n";
    echo "  composer install\n";
    echo "  php redis_queue_test.php\n\n";

    exit(1);
}

use Predis\Client as RedisClient;

class QueueService
{
    private RedisClient $redis;
    private string $queueName;

    public function __construct(string $host = '127.0.0.1', int $port = 6379, string $queueName = 'poc_queue')
    {
        $this->redis = new RedisClient([
            'scheme' => 'tcp',
            'host' => $host,
            'port' => $port,
        ]);
        $this->queueName = $queueName;
    }

    /**
     * Adiciona job à fila
     */
    public function push(array $jobData): bool
    {
        try {
            $this->redis->rpush($this->queueName, [json_encode($jobData)]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remove e retorna próximo job da fila
     */
    public function pop(): ?array
    {
        try {
            $data = $this->redis->lpop($this->queueName);
            return $data ? json_decode($data, true) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Retorna tamanho atual da fila
     */
    public function size(): int
    {
        try {
            return $this->redis->llen($this->queueName);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Limpa toda a fila
     */
    public function clear(): void
    {
        $this->redis->del($this->queueName);
    }

    /**
     * Verifica se Redis está conectado
     */
    public function isConnected(): bool
    {
        try {
            $this->redis->ping();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Processa job simulado
     */
    public function processJob(array $job): bool
    {
        // Simular processamento (ex: enviar email, gerar relatório, etc)
        usleep(rand(10000, 50000)); // 10-50ms
        return true;
    }
}

class Worker
{
    private QueueService $queue;
    private int $processedJobs = 0;
    private int $failedJobs = 0;
    private array $processingTimes = [];

    public function __construct(QueueService $queue)
    {
        $this->queue = $queue;
    }

    /**
     * Processa jobs da fila
     */
    public function process(int $maxJobs = 0): array
    {
        $startTime = microtime(true);
        $jobsProcessed = 0;

        while (true) {
            if ($maxJobs > 0 && $jobsProcessed >= $maxJobs) {
                break;
            }

            $job = $this->queue->pop();

            if ($job === null) {
                // Fila vazia
                break;
            }

            $jobStartTime = microtime(true);

            try {
                $this->queue->processJob($job);
                $this->processedJobs++;
                $jobsProcessed++;

                $processingTime = (microtime(true) - $jobStartTime) * 1000; // em ms
                $this->processingTimes[] = $processingTime;
            } catch (\Exception $e) {
                $this->failedJobs++;
            }
        }

        $totalTime = microtime(true) - $startTime;

        return [
            'processed' => $jobsProcessed,
            'total_time' => $totalTime,
            'throughput' => $totalTime > 0 ? $jobsProcessed / $totalTime : 0,
            'avg_processing_time' => count($this->processingTimes) > 0
                ? array_sum($this->processingTimes) / count($this->processingTimes)
                : 0
        ];
    }

    public function getStats(): array
    {
        return [
            'processed' => $this->processedJobs,
            'failed' => $this->failedJobs,
            'avg_time_ms' => count($this->processingTimes) > 0
                ? array_sum($this->processingTimes) / count($this->processingTimes)
                : 0,
            'min_time_ms' => count($this->processingTimes) > 0 ? min($this->processingTimes) : 0,
            'max_time_ms' => count($this->processingTimes) > 0 ? max($this->processingTimes) : 0,
        ];
    }
}

// ============================================
// SCRIPT DE TESTE
// ============================================

echo "========================================\n";
echo "POC 4: TESTE DE PERFORMANCE REDIS QUEUE\n";
echo "========================================\n\n";

// Configuração
$redisHost = getenv('REDIS_HOST') ?: '127.0.0.1';
$redisPort = (int)(getenv('REDIS_PORT') ?: 6379);

echo "Configuração:\n";
echo "  Host: $redisHost\n";
echo "  Port: $redisPort\n\n";

$queue = new QueueService($redisHost, $redisPort, 'poc_test_queue');

// Check 1: Verificar conexão com Redis
echo "Check 1: Verificando conexão com Redis...\n";
echo str_repeat('-', 50) . "\n";

if (!$queue->isConnected()) {
    echo "❌ Redis não está disponível!\n\n";
    echo "Instruções:\n";
    echo "1. Certifique-se de que Docker está rodando\n";
    echo "2. Execute: cd ../docker && docker-compose up -d\n";
    echo "3. Aguarde Redis iniciar\n";
    echo "4. Execute este script novamente\n\n";
    exit(1);
}

echo "✅ Redis conectado!\n\n";

// Limpar fila antes de iniciar
$queue->clear();

// Teste 1: Push de 1000 jobs
echo "Teste 1: Adicionando 1000 jobs à fila...\n";
echo str_repeat('-', 50) . "\n";

$startTime = microtime(true);
$jobCount = 1000;

for ($i = 1; $i <= $jobCount; $i++) {
    $queue->push([
        'id' => $i,
        'type' => 'email',
        'data' => [
            'to' => "user{$i}@example.com",
            'subject' => 'Test Email',
            'body' => 'This is a test email'
        ],
        'created_at' => time()
    ]);
}

$pushTime = microtime(true) - $startTime;
$pushThroughput = $jobCount / $pushTime;

echo "  Jobs adicionados: $jobCount\n";
echo sprintf("  Tempo total: %.2f segundos\n", $pushTime);
echo sprintf("  Throughput: %.0f jobs/segundo\n", $pushThroughput);
echo sprintf("  Tamanho da fila: %d\n\n", $queue->size());

// Teste 2: Processar com 1 worker
echo "Teste 2: Processando com 1 worker...\n";
echo str_repeat('-', 50) . "\n";

$worker1 = new Worker($queue);
$result1 = $worker1->process($jobCount);

echo sprintf("  Jobs processados: %d\n", $result1['processed']);
echo sprintf("  Tempo total: %.2f segundos\n", $result1['total_time']);
echo sprintf("  Throughput: %.0f jobs/segundo\n", $result1['throughput']);
echo sprintf("  Tempo médio por job: %.2f ms\n\n", $result1['avg_processing_time']);

$stats1 = $worker1->getStats();

// Teste 3: Adicionar 3000 jobs e processar com 3 workers simulados
echo "Teste 3: Processando 3000 jobs (simulando 3 workers)...\n";
echo str_repeat('-', 50) . "\n";

// Adicionar jobs
for ($i = 1; $i <= 3000; $i++) {
    $queue->push([
        'id' => $i,
        'type' => 'report',
        'data' => ['report_id' => $i],
        'created_at' => time()
    ]);
}

echo sprintf("  Jobs adicionados: 3000\n");
echo sprintf("  Tamanho da fila: %d\n\n", $queue->size());

// Simular 3 workers processando em sequência (em produção seria paralelo)
$workers = [];
$totalProcessed = 0;
$overallStartTime = microtime(true);

for ($w = 1; $w <= 3; $w++) {
    $worker = new Worker($queue);
    $result = $worker->process(1000); // Cada worker processa até 1000 jobs
    $workers[] = [
        'worker_id' => $w,
        'result' => $result,
        'stats' => $worker->getStats()
    ];
    $totalProcessed += $result['processed'];
}

$overallTime = microtime(true) - $overallStartTime;
$overallThroughput = $totalProcessed / $overallTime;

echo "  Resultados:\n";
foreach ($workers as $w) {
    echo sprintf("    Worker %d: %d jobs em %.2fs (%.0f jobs/s)\n",
        $w['worker_id'],
        $w['result']['processed'],
        $w['result']['total_time'],
        $w['result']['throughput']
    );
}
echo sprintf("\n  Total processado: %d jobs\n", $totalProcessed);
echo sprintf("  Tempo total: %.2f segundos\n", $overallTime);
echo sprintf("  Throughput combinado: %.0f jobs/segundo\n\n", $overallThroughput);

// Teste 4: Latência
echo "Teste 4: Teste de latência (push + pop)...\n";
echo str_repeat('-', 50) . "\n";

$latencies = [];
$iterations = 100;

for ($i = 0; $i < $iterations; $i++) {
    $startLatency = microtime(true);

    $queue->push(['test' => 'latency', 'iteration' => $i]);
    $queue->pop();

    $latency = (microtime(true) - $startLatency) * 1000; // em ms
    $latencies[] = $latency;
}

$avgLatency = array_sum($latencies) / count($latencies);
$minLatency = min($latencies);
$maxLatency = max($latencies);
$p95Latency = $latencies[intval($iterations * 0.95)];

echo sprintf("  Iterações: %d\n", $iterations);
echo sprintf("  Latência média: %.2f ms\n", $avgLatency);
echo sprintf("  Latência mínima: %.2f ms\n", $minLatency);
echo sprintf("  Latência máxima: %.2f ms\n", $maxLatency);
echo sprintf("  Latência p95: %.2f ms\n\n", $p95Latency);

// Resumo Final
echo "========================================\n";
echo "RESUMO DO POC 4 - REDIS QUEUE\n";
echo "========================================\n\n";

echo "Performance:\n";
echo sprintf("  Push throughput: %.0f jobs/s\n", $pushThroughput);
echo sprintf("  Processamento (1 worker): %.0f jobs/s\n", $result1['throughput']);
echo sprintf("  Processamento (3 workers): %.0f jobs/s\n", $overallThroughput);
echo sprintf("  Latência média: %.2f ms\n", $avgLatency);
echo "\n";

// Critério de Sucesso: > 50 jobs/segundo com 1 worker
$passed = $result1['throughput'] >= 50;

echo "Critério de Sucesso: > 50 jobs/segundo com 1 worker\n";
echo sprintf("Resultado: %.0f jobs/segundo\n", $result1['throughput']);
echo sprintf("Status: %s\n\n", $passed ? '✅ POC PASSOU' : '❌ POC FALHOU');

if ($passed) {
    echo "✅ Redis Queue está validado e pronto para uso!\n";
    echo "✅ Performance excelente para sistema de filas assíncronas!\n";
} else {
    echo "❌ Performance abaixo do esperado.\n";
    echo "⚠️  Considerar otimizações ou RabbitMQ como alternativa.\n";
}

echo "\nRecomendações:\n";
echo "  - Em produção, use múltiplos workers em paralelo (processes)\n";
echo "  - Configure supervisor/systemd para manter workers rodando\n";
echo "  - Implemente retry com backoff exponencial\n";
echo "  - Monitore tamanho da fila com alertas\n";
echo "  - Use Redis persistence (AOF) para durabilidade\n\n";

echo "========================================\n";

// Limpar fila
$queue->clear();

exit($passed ? 0 : 1);
