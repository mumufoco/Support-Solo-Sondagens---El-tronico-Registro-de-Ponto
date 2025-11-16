<?php

namespace Tests\Performance;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use App\Services\Config\ConfigService;
use App\Models\SettingModel;

/**
 * ConfigService Cache Performance Benchmark
 *
 * Tests the performance improvement of ConfigService caching vs direct database queries
 *
 * Run with: php spark test --filter ConfigServiceBenchmark
 */
class ConfigServiceBenchmark extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $configService;
    protected $settingModel;
    protected $cache;
    protected $results = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->configService = new ConfigService();
        $this->settingModel = new SettingModel();
        $this->cache = \Config\Services::cache();
    }

    /**
     * Benchmark: Single Config Get - Cold Cache
     */
    public function testSingleGetColdCache()
    {
        echo "\n\n=== BENCHMARK: Single Config Get (Cold Cache) ===\n";

        $key = 'company_name';
        $iterations = 100;

        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            // Clear cache before each get to simulate cold cache
            $this->cache->delete('config_' . $key);
            $value = $this->configService->get($key);
        }

        $end = microtime(true);
        $totalTime = ($end - $start);
        $avgTime = $totalTime / $iterations;

        echo "Iterations: $iterations\n";
        echo "Total Time: " . number_format($totalTime, 4) . "s\n";
        echo "Average Time: " . number_format($avgTime * 1000, 2) . "ms (per query)\n";
        echo "Queries/Second: " . number_format($iterations / $totalTime, 2) . "\n";

        $this->results['single_get_cold'] = [
            'iterations' => $iterations,
            'total_time' => $totalTime,
            'avg_time_ms' => $avgTime * 1000,
            'qps' => $iterations / $totalTime,
        ];

        $this->assertLessThan(50, $avgTime * 1000, "Cold cache query should be faster than 50ms");
    }

    /**
     * Benchmark: Single Config Get - Hot Cache
     */
    public function testSingleGetHotCache()
    {
        echo "\n\n=== BENCHMARK: Single Config Get (Hot Cache) ===\n";

        $key = 'company_name';
        $iterations = 1000; // More iterations since it's fast

        // Warm up cache
        $this->configService->get($key);

        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            // Cache is hot - should hit cache every time
            $value = $this->configService->get($key);
        }

        $end = microtime(true);
        $totalTime = ($end - $start);
        $avgTime = $totalTime / $iterations;

        echo "Iterations: $iterations\n";
        echo "Total Time: " . number_format($totalTime, 4) . "s\n";
        echo "Average Time: " . number_format($avgTime * 1000, 2) . "ms (per query)\n";
        echo "Queries/Second: " . number_format($iterations / $totalTime, 2) . "\n";

        $this->results['single_get_hot'] = [
            'iterations' => $iterations,
            'total_time' => $totalTime,
            'avg_time_ms' => $avgTime * 1000,
            'qps' => $iterations / $totalTime,
        ];

        // Calculate speedup
        if (isset($this->results['single_get_cold'])) {
            $speedup = $this->results['single_get_cold']['avg_time_ms'] / $avgTime * 1000;
            echo "\nSpeedup vs Cold Cache: " . number_format($speedup, 2) . "x faster\n";
            $this->results['single_get_hot']['speedup'] = $speedup;
        }

        $this->assertLessThan(5, $avgTime * 1000, "Hot cache query should be faster than 5ms");
    }

    /**
     * Benchmark: Direct Database Query (No Cache)
     */
    public function testDirectDatabaseQuery()
    {
        echo "\n\n=== BENCHMARK: Direct Database Query (No ConfigService) ===\n";

        $key = 'company_name';
        $iterations = 100;

        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            // Direct database query without caching
            $setting = $this->settingModel->where('key', $key)->first();
        }

        $end = microtime(true);
        $totalTime = ($end - $start);
        $avgTime = $totalTime / $iterations;

        echo "Iterations: $iterations\n";
        echo "Total Time: " . number_format($totalTime, 4) . "s\n";
        echo "Average Time: " . number_format($avgTime * 1000, 2) . "ms (per query)\n";
        echo "Queries/Second: " . number_format($iterations / $totalTime, 2) . "\n";

        $this->results['direct_db'] = [
            'iterations' => $iterations,
            'total_time' => $totalTime,
            'avg_time_ms' => $avgTime * 1000,
            'qps' => $iterations / $totalTime,
        ];

        $this->assertLessThan(100, $avgTime * 1000, "Direct DB query should be faster than 100ms");
    }

    /**
     * Benchmark: Batch Get Many (Multiple Keys)
     */
    public function testGetMany()
    {
        echo "\n\n=== BENCHMARK: Get Many (Batch Query) ===\n";

        $keys = ['company_name', 'company_email', 'work_schedule_start', 'work_schedule_end', 'tolerance_minutes'];
        $iterations = 100;

        // Clear cache
        $this->configService->clearCache();

        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            // Clear cache to simulate cold cache
            foreach ($keys as $key) {
                $this->cache->delete('config_' . $key);
            }
            $values = $this->configService->getMany($keys);
        }

        $end = microtime(true);
        $totalTime = ($end - $start);
        $avgTime = $totalTime / $iterations;

        echo "Iterations: $iterations\n";
        echo "Keys per query: " . count($keys) . "\n";
        echo "Total Time: " . number_format($totalTime, 4) . "s\n";
        echo "Average Time: " . number_format($avgTime * 1000, 2) . "ms (per batch)\n";
        echo "Average per Key: " . number_format(($avgTime * 1000) / count($keys), 2) . "ms\n";
        echo "Batches/Second: " . number_format($iterations / $totalTime, 2) . "\n";

        $this->results['get_many'] = [
            'iterations' => $iterations,
            'keys_per_query' => count($keys),
            'total_time' => $totalTime,
            'avg_time_ms' => $avgTime * 1000,
            'avg_per_key_ms' => ($avgTime * 1000) / count($keys),
            'qps' => $iterations / $totalTime,
        ];

        $this->assertLessThan(100, $avgTime * 1000, "Batch query should be faster than 100ms");
    }

    /**
     * Benchmark: Get All Settings
     */
    public function testGetAll()
    {
        echo "\n\n=== BENCHMARK: Get All Settings ===\n";

        $iterations = 50;

        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            // Clear cache
            $this->cache->delete('config_all');
            $settings = $this->configService->getAll();
        }

        $end = microtime(true);
        $totalTime = ($end - $start);
        $avgTime = $totalTime / $iterations;

        echo "Iterations: $iterations\n";
        echo "Total Time: " . number_format($totalTime, 4) . "s\n";
        echo "Average Time: " . number_format($avgTime * 1000, 2) . "ms (per query)\n";
        echo "Queries/Second: " . number_format($iterations / $totalTime, 2) . "\n";

        $this->results['get_all'] = [
            'iterations' => $iterations,
            'total_time' => $totalTime,
            'avg_time_ms' => $avgTime * 1000,
            'qps' => $iterations / $totalTime,
        ];

        $this->assertLessThan(100, $avgTime * 1000, "Get all query should be faster than 100ms");
    }

    /**
     * Benchmark: Cache Hit Rate Simulation
     */
    public function testCacheHitRate()
    {
        echo "\n\n=== BENCHMARK: Cache Hit Rate Simulation ===\n";

        $keys = ['company_name', 'company_email', 'work_schedule_start'];
        $iterations = 1000;
        $cacheHits = 0;
        $cacheMisses = 0;

        // Clear cache
        $this->configService->clearCache();

        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            // Randomly select a key
            $key = $keys[array_rand($keys)];

            // Check if in cache
            $cacheKey = 'config_' . $key;
            if ($this->cache->get($cacheKey) !== null) {
                $cacheHits++;
            } else {
                $cacheMisses++;
            }

            // Get value (will cache it)
            $this->configService->get($key);
        }

        $end = microtime(true);
        $totalTime = ($end - $start);
        $hitRate = ($cacheHits / $iterations) * 100;

        echo "Iterations: $iterations\n";
        echo "Cache Hits: $cacheHits\n";
        echo "Cache Misses: $cacheMisses\n";
        echo "Hit Rate: " . number_format($hitRate, 2) . "%\n";
        echo "Total Time: " . number_format($totalTime, 4) . "s\n";
        echo "Average Time: " . number_format(($totalTime / $iterations) * 1000, 2) . "ms\n";

        $this->results['cache_hit_rate'] = [
            'iterations' => $iterations,
            'hits' => $cacheHits,
            'misses' => $cacheMisses,
            'hit_rate' => $hitRate,
            'total_time' => $totalTime,
            'avg_time_ms' => ($totalTime / $iterations) * 1000,
        ];

        $this->assertGreaterThan(50, $hitRate, "Cache hit rate should be > 50% for repeated queries");
    }

    /**
     * Display summary at the end
     */
    protected function tearDown(): void
    {
        if (!empty($this->results)) {
            echo "\n\n" . str_repeat("=", 70) . "\n";
            echo "CONFIG SERVICE CACHE BENCHMARK SUMMARY\n";
            echo str_repeat("=", 70) . "\n";

            // Single Get Comparison
            if (isset($this->results['single_get_cold']) && isset($this->results['single_get_hot'])) {
                echo "\nSingle Get Performance:\n";
                echo "  Cold Cache: " . number_format($this->results['single_get_cold']['avg_time_ms'], 2) . "ms\n";
                echo "  Hot Cache:  " . number_format($this->results['single_get_hot']['avg_time_ms'], 2) . "ms\n";
                if (isset($this->results['single_get_hot']['speedup'])) {
                    echo "  Speedup:    " . number_format($this->results['single_get_hot']['speedup'], 2) . "x\n";
                }
            }

            // Direct DB Comparison
            if (isset($this->results['direct_db']) && isset($this->results['single_get_hot'])) {
                echo "\nCache vs Direct DB:\n";
                echo "  Direct DB:  " . number_format($this->results['direct_db']['avg_time_ms'], 2) . "ms\n";
                echo "  With Cache: " . number_format($this->results['single_get_hot']['avg_time_ms'], 2) . "ms\n";
                $improvement = (($this->results['direct_db']['avg_time_ms'] - $this->results['single_get_hot']['avg_time_ms'])
                    / $this->results['direct_db']['avg_time_ms']) * 100;
                echo "  Improvement: " . number_format($improvement, 1) . "%\n";
            }

            // Batch Query
            if (isset($this->results['get_many'])) {
                echo "\nBatch Query (getMany):\n";
                echo "  Keys: " . $this->results['get_many']['keys_per_query'] . "\n";
                echo "  Total: " . number_format($this->results['get_many']['avg_time_ms'], 2) . "ms\n";
                echo "  Per Key: " . number_format($this->results['get_many']['avg_per_key_ms'], 2) . "ms\n";
            }

            // Cache Hit Rate
            if (isset($this->results['cache_hit_rate'])) {
                echo "\nCache Hit Rate:\n";
                echo "  Hits: " . $this->results['cache_hit_rate']['hits'] . "\n";
                echo "  Misses: " . $this->results['cache_hit_rate']['misses'] . "\n";
                echo "  Hit Rate: " . number_format($this->results['cache_hit_rate']['hit_rate'], 2) . "%\n";
            }

            echo "\n" . str_repeat("=", 70) . "\n";
        }

        parent::tearDown();
    }
}
