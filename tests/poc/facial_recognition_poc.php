<?php

/**
 * POC - Reconhecimento Facial em Produção
 *
 * Teste com 5 funcionários voluntários reais
 * Target: Taxa de acerto >90%
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Services\Biometric\DeepFaceService;

class FacialRecognitionPOC
{
    private DeepFaceService $deepFace;
    private array $results = [];
    private string $photosPath;
    private string $reportPath;

    public function __construct()
    {
        $this->deepFace = new DeepFaceService();
        $this->photosPath = __DIR__ . '/photos/';
        $this->reportPath = __DIR__ . '/../../tests/_output/poc_facial_report.csv';

        echo "=== POC Reconhecimento Facial ===" . PHP_EOL;
        echo "Iniciado em: " . date('Y-m-d H:i:s') . PHP_EOL . PHP_EOL;
    }

    /**
     * Executar POC completo
     */
    public function run(): void
    {
        // 1. Health check da API
        if (!$this->healthCheck()) {
            die("❌ DeepFace API offline!" . PHP_EOL);
        }

        // 2. Cadastrar 5 funcionários
        $employees = $this->enrollEmployees();

        // 3. Testar reconhecimento em diferentes condições
        $this->testRecognitionConditions($employees);

        // 4. Testar anti-spoofing
        $this->testAntiSpoofing($employees[0]);

        // 5. Testar fotos não cadastradas
        $this->testUnknownFaces();

        // 6. Gerar relatório
        $this->generateReport();
    }

    /**
     * Verificar se DeepFace está online
     */
    private function healthCheck(): bool
    {
        echo "Verificando DeepFace API... ";

        $result = $this->deepFace->healthCheck();

        if ($result) {
            echo "✅ Online" . PHP_EOL . PHP_EOL;
            return true;
        }

        echo "❌ Offline" . PHP_EOL;
        return false;
    }

    /**
     * Cadastrar 5 funcionários voluntários
     */
    private function enrollEmployees(): array
    {
        echo "Cadastrando funcionários..." . PHP_EOL;

        $employees = [
            ['id' => 1, 'name' => 'João Silva'],
            ['id' => 2, 'name' => 'Maria Santos'],
            ['id' => 3, 'name' => 'Pedro Oliveira'],
            ['id' => 4, 'name' => 'Ana Costa'],
            ['id' => 5, 'name' => 'Carlos Mendes'],
        ];

        foreach ($employees as &$employee) {
            $photoPath = $this->photosPath . "employee_{$employee['id']}_base.jpg";

            if (!file_exists($photoPath)) {
                echo "  ⚠️  Foto não encontrada: {$photoPath}" . PHP_EOL;
                continue;
            }

            $startTime = microtime(true);
            $result = $this->deepFace->enroll($employee['id'], $photoPath);
            $endTime = microtime(true);

            $employee['enrolled'] = $result['success'] ?? false;
            $employee['enroll_time'] = round(($endTime - $startTime) * 1000); // ms

            if ($employee['enrolled']) {
                echo "  ✅ {$employee['name']} cadastrado ({$employee['enroll_time']}ms)" . PHP_EOL;
            } else {
                echo "  ❌ {$employee['name']} FALHOU: " . ($result['error'] ?? 'Erro desconhecido') . PHP_EOL;
            }
        }

        echo PHP_EOL;
        return $employees;
    }

    /**
     * Testar reconhecimento em diferentes condições
     */
    private function testRecognitionConditions(array $employees): void
    {
        echo "Testando reconhecimento em diferentes condições..." . PHP_EOL . PHP_EOL;

        $conditions = [
            'morning' => 'Manhã (luz natural)',
            'afternoon' => 'Tarde',
            'night' => 'Noite (luz artificial)',
            'with_glasses' => 'Com óculos',
            'without_glasses' => 'Sem óculos',
            'distance_30cm' => 'Distância 30cm',
            'distance_50cm' => 'Distância 50cm',
            'distance_1m' => 'Distância 1m',
        ];

        foreach ($employees as $employee) {
            if (!$employee['enrolled']) continue;

            echo "Funcionário: {$employee['name']}" . PHP_EOL;

            foreach ($conditions as $condition => $description) {
                $photoPath = $this->photosPath . "employee_{$employee['id']}_{$condition}.jpg";

                if (!file_exists($photoPath)) {
                    continue; // Pular se foto não existir
                }

                $startTime = microtime(true);
                $result = $this->deepFace->recognize($photoPath);
                $endTime = microtime(true);

                $recognized = $result['recognized'] ?? false;
                $similarity = $result['similarity'] ?? 0;
                $responseTime = round(($endTime - $startTime) * 1000);

                $status = $recognized ? '✅' : '❌';

                echo "  {$status} {$description}: ";
                echo "Similaridade={$similarity}, ";
                echo "Tempo={$responseTime}ms" . PHP_EOL;

                // Salvar resultado
                $this->results[] = [
                    'employee_id' => $employee['id'],
                    'employee_name' => $employee['name'],
                    'condition' => $description,
                    'recognized' => $recognized ? 'Sim' : 'Não',
                    'similarity' => $similarity,
                    'response_time_ms' => $responseTime,
                ];
            }

            echo PHP_EOL;
        }
    }

    /**
     * Testar anti-spoofing (foto impressa, foto de tela)
     */
    private function testAntiSpoofing(array $employee): void
    {
        echo "Testando anti-spoofing..." . PHP_EOL;

        $spoofTests = [
            'printed_photo' => 'Foto impressa',
            'screen_photo' => 'Foto de tela (celular)',
        ];

        foreach ($spoofTests as $type => $description) {
            $photoPath = $this->photosPath . "employee_{$employee['id']}_{$type}.jpg";

            if (!file_exists($photoPath)) {
                echo "  ⚠️  Teste pulado: {$description} (foto não encontrada)" . PHP_EOL;
                continue;
            }

            $result = $this->deepFace->recognize($photoPath);

            $blocked = !($result['success'] ?? false);

            if ($blocked) {
                echo "  ✅ {$description}: BLOQUEADO (anti-spoofing funcionou)" . PHP_EOL;
            } else {
                echo "  ❌ {$description}: PASSOU (anti-spoofing FALHOU!)" . PHP_EOL;
            }

            $this->results[] = [
                'employee_id' => $employee['id'],
                'employee_name' => $employee['name'],
                'condition' => "Anti-spoofing: {$description}",
                'recognized' => $blocked ? 'Não (bloqueado)' : 'Sim (FALHA)',
                'similarity' => $result['similarity'] ?? 0,
                'response_time_ms' => 0,
            ];
        }

        echo PHP_EOL;
    }

    /**
     * Testar com pessoas não cadastradas
     */
    private function testUnknownFaces(): void
    {
        echo "Testando pessoas não cadastradas..." . PHP_EOL;

        $unknownPhotos = glob($this->photosPath . 'unknown_*.jpg');

        foreach ($unknownPhotos as $photoPath) {
            $result = $this->deepFace->recognize($photoPath);

            $recognized = $result['recognized'] ?? false;

            if (!$recognized) {
                echo "  ✅ Pessoa não reconhecida (correto)" . PHP_EOL;
            } else {
                echo "  ❌ Pessoa reconhecida como ID {$result['employee_id']} (FALSO POSITIVO!)" . PHP_EOL;
            }

            $this->results[] = [
                'employee_id' => 0,
                'employee_name' => 'Desconhecido',
                'condition' => 'Pessoa não cadastrada',
                'recognized' => $recognized ? 'Sim (ERRO!)' : 'Não',
                'similarity' => $result['best_similarity'] ?? 0,
                'response_time_ms' => 0,
            ];
        }

        echo PHP_EOL;
    }

    /**
     * Gerar relatório CSV
     */
    private function generateReport(): void
    {
        echo "Gerando relatório..." . PHP_EOL;

        $fp = fopen($this->reportPath, 'w');

        // Cabeçalho
        fputcsv($fp, [
            'Funcionário ID',
            'Nome',
            'Condição',
            'Reconhecido',
            'Similaridade',
            'Tempo (ms)',
        ]);

        // Dados
        foreach ($this->results as $result) {
            fputcsv($fp, [
                $result['employee_id'],
                $result['employee_name'],
                $result['condition'],
                $result['recognized'],
                $result['similarity'],
                $result['response_time_ms'],
            ]);
        }

        fclose($fp);

        // Calcular estatísticas
        $totalTests = count($this->results);
        $successfulRecognitions = count(array_filter($this->results, function ($r) {
            return $r['recognized'] === 'Sim';
        }));

        $accuracyRate = $totalTests > 0 ? ($successfulRecognitions / $totalTests) * 100 : 0;

        echo PHP_EOL;
        echo "=== Resultados Finais ===" . PHP_EOL;
        echo "Total de testes: {$totalTests}" . PHP_EOL;
        echo "Reconhecimentos bem-sucedidos: {$successfulRecognitions}" . PHP_EOL;
        echo "Taxa de acerto: " . round($accuracyRate, 2) . "%" . PHP_EOL . PHP_EOL;

        if ($accuracyRate >= 90) {
            echo "✅ META ATINGIDA (>90%)" . PHP_EOL;
        } elseif ($accuracyRate >= 85) {
            echo "⚠️  Taxa aceitável, mas abaixo da meta. Considere ajustar threshold." . PHP_EOL;
        } else {
            echo "❌ Taxa de acerto BAIXA (<85%). Revisar modelo ou considerar alternativa." . PHP_EOL;
        }

        echo PHP_EOL;
        echo "Relatório salvo em: {$this->reportPath}" . PHP_EOL;
    }
}

// Executar POC
$poc = new FacialRecognitionPOC();
$poc->run();
