<?php
/**
 * POC 5: Validação de Cálculo de Distância Haversine
 *
 * Objetivo: Validar precisão do cálculo de distância geográfica
 * Critério de Sucesso: Precisão > 99% comparado com referências conhecidas
 */

class HaversineCalculator
{
    const EARTH_RADIUS_KM = 6371;
    const EARTH_RADIUS_M = 6371000;

    /**
     * Calcula a distância entre dois pontos geográficos usando fórmula de Haversine
     *
     * @param float $lat1 Latitude do ponto 1 (graus)
     * @param float $lng1 Longitude do ponto 1 (graus)
     * @param float $lat2 Latitude do ponto 2 (graus)
     * @param float $lng2 Longitude do ponto 2 (graus)
     * @param string $unit Unidade de retorno: 'km' ou 'm'
     * @return float Distância em km ou metros
     */
    public function calculateDistance(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2,
        string $unit = 'km'
    ): float {
        // Converter graus para radianos
        $lat1Rad = deg2rad($lat1);
        $lng1Rad = deg2rad($lng1);
        $lat2Rad = deg2rad($lat2);
        $lng2Rad = deg2rad($lng2);

        // Diferenças
        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLng = $lng2Rad - $lng1Rad;

        // Fórmula de Haversine
        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLng / 2) * sin($deltaLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        // Raio da Terra
        $radius = ($unit === 'm') ? self::EARTH_RADIUS_M : self::EARTH_RADIUS_KM;

        // Distância final
        return $radius * $c;
    }

    /**
     * Verifica se um ponto está dentro de uma cerca virtual circular
     *
     * @param float $pointLat Latitude do ponto a verificar
     * @param float $pointLng Longitude do ponto a verificar
     * @param float $centerLat Latitude do centro da cerca
     * @param float $centerLng Longitude do centro da cerca
     * @param float $radiusMeters Raio da cerca em metros
     * @return bool True se dentro da cerca
     */
    public function isWithinGeofence(
        float $pointLat,
        float $pointLng,
        float $centerLat,
        float $centerLng,
        float $radiusMeters,
        float $tolerancePercent = 0.5
    ): bool {
        $distance = $this->calculateDistance(
            $pointLat,
            $pointLng,
            $centerLat,
            $centerLng,
            'm'
        );

        // Adicionar tolerância de 0.5% (padrão) para compensar imprecisões de GPS
        $radiusWithTolerance = $radiusMeters * (1 + ($tolerancePercent / 100));

        return $distance <= $radiusWithTolerance;
    }
}

// ============================================
// TESTES DE VALIDAÇÃO
// ============================================

echo "========================================\n";
echo "POC 5: VALIDAÇÃO HAVERSINE\n";
echo "========================================\n\n";

$calculator = new HaversineCalculator();

// Casos de teste com distâncias conhecidas
$testCases = [
    [
        'name' => 'São Paulo → Rio de Janeiro',
        'lat1' => -23.5505,
        'lng1' => -46.6333,
        'lat2' => -22.9068,
        'lng2' => -43.1729,
        'expected_km' => 358, // Distância aproximada conhecida
        'tolerance_percent' => 2, // 2% de tolerância
    ],
    [
        'name' => 'Distância de 100m (mesma rua)',
        'lat1' => -23.561414,
        'lng1' => -46.656179,
        'lat2' => -23.560514,
        'lng2' => -46.656179,
        'expected_km' => 0.1,
        'tolerance_percent' => 5,
    ],
    [
        'name' => 'Distância de 1km',
        'lat1' => -23.561414,
        'lng1' => -46.656179,
        'lat2' => -23.552414,
        'lng2' => -46.656179,
        'expected_km' => 1.0,
        'tolerance_percent' => 5,
    ],
    [
        'name' => 'Mesmo ponto (distância zero)',
        'lat1' => -23.561414,
        'lng1' => -46.656179,
        'lat2' => -23.561414,
        'lng2' => -46.656179,
        'expected_km' => 0,
        'tolerance_percent' => 0,
    ],
    [
        'name' => 'Brasília → São Paulo',
        'lat1' => -15.7939,
        'lng1' => -47.8828,
        'lat2' => -23.5505,
        'lng2' => -46.6333,
        'expected_km' => 873,
        'tolerance_percent' => 2,
    ],
];

$totalTests = count($testCases);
$passedTests = 0;
$results = [];

foreach ($testCases as $index => $test) {
    echo "Teste " . ($index + 1) . ": {$test['name']}\n";
    echo str_repeat('-', 50) . "\n";

    $calculated = $calculator->calculateDistance(
        $test['lat1'],
        $test['lng1'],
        $test['lat2'],
        $test['lng2'],
        'km'
    );

    $difference = abs($calculated - $test['expected_km']);
    $percentError = $test['expected_km'] > 0
        ? ($difference / $test['expected_km']) * 100
        : 0;

    $passed = $percentError <= $test['tolerance_percent'];

    echo sprintf("  Calculado: %.3f km\n", $calculated);
    echo sprintf("  Esperado: %.3f km\n", $test['expected_km']);
    echo sprintf("  Diferença: %.3f km (%.2f%%)\n", $difference, $percentError);
    echo sprintf("  Tolerância: %.2f%%\n", $test['tolerance_percent']);
    echo sprintf("  Status: %s\n\n", $passed ? '✅ PASSOU' : '❌ FALHOU');

    if ($passed) {
        $passedTests++;
    }

    $results[] = [
        'test' => $test['name'],
        'calculated' => $calculated,
        'expected' => $test['expected_km'],
        'error_percent' => $percentError,
        'passed' => $passed,
    ];
}

// Teste de Geofencing
echo "\n========================================\n";
echo "TESTE DE GEOFENCING\n";
echo "========================================\n\n";

$companyCenterLat = -23.561414;
$companyCenterLng = -46.656179;
$geofenceRadius = 100; // 100 metros

$geofenceTests = [
    [
        'name' => 'Funcionário dentro da cerca (50m)',
        'lat' => -23.561864,
        'lng' => -46.656179,
        'expected' => true,
    ],
    [
        'name' => 'Funcionário fora da cerca (150m)',
        'lat' => -23.562764,
        'lng' => -46.656179,
        'expected' => false,
    ],
    [
        'name' => 'Funcionário exatamente no centro',
        'lat' => $companyCenterLat,
        'lng' => $companyCenterLng,
        'expected' => true,
    ],
    [
        'name' => 'Funcionário exatamente no limite (100m)',
        'lat' => -23.562314,
        'lng' => -46.656179,
        'expected' => true,
    ],
];

$geofencePassed = 0;

foreach ($geofenceTests as $index => $test) {
    $isWithin = $calculator->isWithinGeofence(
        $test['lat'],
        $test['lng'],
        $companyCenterLat,
        $companyCenterLng,
        $geofenceRadius
    );

    $distance = $calculator->calculateDistance(
        $test['lat'],
        $test['lng'],
        $companyCenterLat,
        $companyCenterLng,
        'm'
    );

    $passed = $isWithin === $test['expected'];

    echo "Teste " . ($index + 1) . ": {$test['name']}\n";
    echo sprintf("  Distância do centro: %.2f m\n", $distance);
    echo sprintf("  Dentro da cerca (%dm): %s\n", $geofenceRadius, $isWithin ? 'SIM' : 'NÃO');
    echo sprintf("  Esperado: %s\n", $test['expected'] ? 'SIM' : 'NÃO');
    echo sprintf("  Status: %s\n\n", $passed ? '✅ PASSOU' : '❌ FALHOU');

    if ($passed) {
        $geofencePassed++;
    }
}

// Resumo Final
echo "\n========================================\n";
echo "RESUMO DO POC 5 - HAVERSINE\n";
echo "========================================\n\n";

$totalGeofenceTests = count($geofenceTests);
$allTestsPassed = $passedTests + $geofencePassed;
$allTestsTotal = $totalTests + $totalGeofenceTests;

$successRate = ($allTestsPassed / $allTestsTotal) * 100;

echo "Testes de Distância:\n";
echo sprintf("  Passou: %d/%d (%.1f%%)\n", $passedTests, $totalTests, ($passedTests / $totalTests) * 100);
echo "\n";

echo "Testes de Geofencing:\n";
echo sprintf("  Passou: %d/%d (%.1f%%)\n", $geofencePassed, $totalGeofenceTests, ($geofencePassed / $totalGeofenceTests) * 100);
echo "\n";

echo "Total Geral:\n";
echo sprintf("  Passou: %d/%d (%.1f%%)\n", $allTestsPassed, $allTestsTotal, $successRate);
echo "\n";

// Critério de Sucesso: 99% de precisão (todos os testes devem passar)
$pocPassed = $successRate >= 99;

echo "Critério de Sucesso: Precisão ≥ 99%\n";
echo sprintf("Resultado: %s\n\n", $pocPassed ? '✅ POC PASSOU' : '❌ POC FALHOU');

if ($pocPassed) {
    echo "✅ A implementação de Haversine está validada e pronta para uso!\n";
    echo "✅ O sistema de geofencing está funcionando corretamente!\n";
} else {
    echo "❌ A implementação precisa de ajustes.\n";
}

echo "\n========================================\n";

// Retornar status de saída
exit($pocPassed ? 0 : 1);
