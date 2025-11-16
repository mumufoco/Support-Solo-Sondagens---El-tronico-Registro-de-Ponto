<?php

namespace Tests\Unit\Services;

use App\Services\Geolocation\GeofenceService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Testes Unitários para GeofenceService
 *
 * Testa cálculo de distância (Haversine) e validação de cerca virtual
 */
class GeofenceServiceTest extends CIUnitTestCase
{
    protected GeofenceService $geofenceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->geofenceService = new GeofenceService();
    }

    /**
     * Teste: Cálculo de distância usando fórmula de Haversine
     *
     * Coordenadas conhecidas:
     * - Ponto A: Praça da Sé, São Paulo (-23.550520, -46.633309)
     * - Ponto B: Av. Paulista, São Paulo (-23.561414, -46.656166)
     * Distância aproximada: 2.8 km
     */
    public function testHaversineDistance(): void
    {
        $lat1 = -23.550520; // Praça da Sé
        $lon1 = -46.633309;

        $lat2 = -23.561414; // Av. Paulista
        $lon2 = -46.656166;

        $distance = $this->geofenceService->calculateDistance($lat1, $lon1, $lat2, $lon2);

        // Distância em metros, esperado aproximadamente 2800m (2.8km)
        $this->assertGreaterThan(2700, $distance);
        $this->assertLessThan(2900, $distance);
    }

    /**
     * Teste: Distância entre pontos idênticos deve ser zero
     */
    public function testDistanceZeroForSamePoint(): void
    {
        $lat = -23.550520;
        $lon = -46.633309;

        $distance = $this->geofenceService->calculateDistance($lat, $lon, $lat, $lon);

        $this->assertEquals(0.0, $distance);
    }

    /**
     * Teste: Ponto dentro da cerca virtual deve retornar true
     */
    public function testCheckGeofenceInside(): void
    {
        // Centro da cerca: Praça da Sé
        $centerLat = -23.550520;
        $centerLon = -46.633309;
        $radius = 100; // 100 metros

        // Ponto próximo (50 metros de distância aproximadamente)
        $testLat = -23.550970;
        $testLon = -46.633309;

        $isInside = $this->geofenceService->isWithinGeofence(
            $testLat,
            $testLon,
            $centerLat,
            $centerLon,
            $radius
        );

        $this->assertTrue($isInside);
    }

    /**
     * Teste: Ponto fora da cerca virtual deve retornar false
     */
    public function testCheckGeofenceOutside(): void
    {
        // Centro da cerca: Praça da Sé
        $centerLat = -23.550520;
        $centerLon = -46.633309;
        $radius = 100; // 100 metros

        // Ponto distante (Av. Paulista, ~2.8km)
        $testLat = -23.561414;
        $testLon = -46.656166;

        $isInside = $this->geofenceService->isWithinGeofence(
            $testLat,
            $testLon,
            $centerLat,
            $centerLon,
            $radius
        );

        $this->assertFalse($isInside);
    }

    /**
     * Teste: Ponto exatamente no limite da cerca (edge case)
     */
    public function testGeofenceBoundary(): void
    {
        $centerLat = -23.550520;
        $centerLon = -46.633309;
        $radius = 100;

        // Calcular ponto que está exatamente a 100 metros
        // Aproximadamente 0.0009 graus de latitude = 100 metros
        $boundaryLat = $centerLat + 0.0009;
        $boundaryLon = $centerLon;

        $isInside = $this->geofenceService->isWithinGeofence(
            $boundaryLat,
            $boundaryLon,
            $centerLat,
            $centerLon,
            $radius
        );

        // Ponto no limite deve ser considerado dentro
        $this->assertTrue($isInside);
    }

    /**
     * Teste: Validação de coordenadas inválidas
     */
    public function testInvalidCoordinates(): void
    {
        // Latitude deve estar entre -90 e 90
        $this->expectException(\InvalidArgumentException::class);

        $this->geofenceService->calculateDistance(
            95.0,  // Latitude inválida
            -46.633309,
            -23.550520,
            -46.633309
        );
    }

    /**
     * Teste: Validação de longitude inválida
     */
    public function testInvalidLongitude(): void
    {
        // Longitude deve estar entre -180 e 180
        $this->expectException(\InvalidArgumentException::class);

        $this->geofenceService->calculateDistance(
            -23.550520,
            200.0,  // Longitude inválida
            -23.550520,
            -46.633309
        );
    }

    /**
     * Teste: Cálculo de distância entre cidades diferentes
     *
     * - São Paulo (-23.550520, -46.633309)
     * - Rio de Janeiro (-22.906847, -43.172897)
     * Distância aproximada: 360 km
     */
    public function testLongDistance(): void
    {
        $spLat = -23.550520;
        $spLon = -46.633309;

        $rjLat = -22.906847;
        $rjLon = -43.172897;

        $distance = $this->geofenceService->calculateDistance($spLat, $spLon, $rjLat, $rjLon);

        // Distância em metros, esperado aproximadamente 360.000m (360km)
        $this->assertGreaterThan(350000, $distance);
        $this->assertLessThan(370000, $distance);
    }

    /**
     * Teste: Raio negativo deve lançar exceção
     */
    public function testNegativeRadius(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->geofenceService->isWithinGeofence(
            -23.550520,
            -46.633309,
            -23.550520,
            -46.633309,
            -100 // Raio negativo
        );
    }

    /**
     * Teste: Raio zero deve funcionar (ponto exato)
     */
    public function testZeroRadius(): void
    {
        $lat = -23.550520;
        $lon = -46.633309;

        // Mesmo ponto com raio zero
        $isInside = $this->geofenceService->isWithinGeofence($lat, $lon, $lat, $lon, 0);
        $this->assertTrue($isInside);

        // Ponto diferente com raio zero
        $isOutside = $this->geofenceService->isWithinGeofence(
            $lat + 0.0001,
            $lon,
            $lat,
            $lon,
            0
        );
        $this->assertFalse($isOutside);
    }

    /**
     * Teste: Formatar distância para exibição legível
     */
    public function testFormatDistance(): void
    {
        // Menos de 1km
        $formatted = $this->geofenceService->formatDistance(500);
        $this->assertEquals('500m', $formatted);

        // Mais de 1km
        $formatted = $this->geofenceService->formatDistance(2800);
        $this->assertEquals('2.8km', $formatted);

        // Exatamente 1km
        $formatted = $this->geofenceService->formatDistance(1000);
        $this->assertEquals('1.0km', $formatted);
    }

    /**
     * Teste: Obter bearing (direção) entre dois pontos
     */
    public function testGetBearing(): void
    {
        $lat1 = -23.550520;
        $lon1 = -46.633309;

        // Ponto ao sul
        $lat2 = -23.560520;
        $lon2 = -46.633309;

        $bearing = $this->geofenceService->getBearing($lat1, $lon1, $lat2, $lon2);

        // Bearing para o sul deve estar próximo de 180°
        $this->assertGreaterThan(170, $bearing);
        $this->assertLessThan(190, $bearing);
    }

    /**
     * Teste: Validar múltiplas cercas virtuais
     */
    public function testMultipleGeofences(): void
    {
        $testLat = -23.550520;
        $testLon = -46.633309;

        $geofences = [
            ['lat' => -23.550520, 'lon' => -46.633309, 'radius' => 100, 'name' => 'Matriz'],
            ['lat' => -23.561414, 'lon' => -46.656166, 'radius' => 100, 'name' => 'Filial'],
        ];

        $matches = $this->geofenceService->checkMultipleGeofences($testLat, $testLon, $geofences);

        // Deve estar apenas dentro da Matriz
        $this->assertCount(1, $matches);
        $this->assertEquals('Matriz', $matches[0]['name']);
    }
}
