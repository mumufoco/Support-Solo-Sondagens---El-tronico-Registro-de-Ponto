<?php

namespace Tests\Performance;

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\Biometric\FacialRecognitionCache;

/**
 * Facial Recognition Cache Performance Benchmark
 *
 * Tests the performance and behavior of FacialRecognitionCache with LRU eviction
 *
 * Run with: php spark test --filter FacialRecognitionCacheBenchmark
 */
class FacialRecognitionCacheBenchmark extends CIUnitTestCase
{
    protected $cache;
    protected $results = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new FacialRecognitionCache();
        $this->cache->clear();
    }

    /**
     * Benchmark: Cache Get - Cold Cache (Miss)
     */
    public function testColdCacheGet()
    {
        echo "\n\n=== BENCHMARK: Facial Recognition Cache - Cold Cache ===\n";

        $iterations = 100;
        $hashes = [];

        // Generate test hashes
        for ($i = 0; $i < $iterations; $i++) {
            $hashes[] = hash('sha256', "test_image_$i");
        }

        $start = microtime(true);
        $misses = 0;

        foreach ($hashes as $hash) {
            $result = $this->cache->get($hash);
            if ($result === null) {
                $misses++;
            }
        }

        $end = microtime(true);
        $totalTime = ($end - $start);
        $avgTime = $totalTime / $iterations;

        echo "Iterations: $iterations\n";
        echo "Cache Misses: $misses\n";
        echo "Total Time: " . number_format($totalTime, 4) . "s\n";
        echo "Average Time: " . number_format($avgTime * 1000, 2) . "ms (per get)\n";
        echo "Gets/Second: " . number_format($iterations / $totalTime, 2) . "\n";

        $this->results['cold_cache_get'] = [
            'iterations' => $iterations,
            'misses' => $misses,
            'total_time' => $totalTime,
            'avg_time_ms' => $avgTime * 1000,
            'ops_per_sec' => $iterations / $totalTime,
        ];

        $this->assertEquals($iterations, $misses, "All gets should miss on cold cache");
        $this->assertLessThan(5, $avgTime * 1000, "Cache miss should be faster than 5ms");
    }

    /**
     * Benchmark: Cache Set Performance
     */
    public function testCacheSet()
    {
        echo "\n\n=== BENCHMARK: Facial Recognition Cache - Set Performance ===\n";

        $iterations = 100;

        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $hash = hash('sha256', "test_image_$i");
            $result = [
                'employee_id' => 1,
                'confidence' => 0.95,
                'distance' => 0.05,
                'verified' => true,
            ];

            $this->cache->set($hash, $result, true);
        }

        $end = microtime(true);
        $totalTime = ($end - $start);
        $avgTime = $totalTime / $iterations;

        echo "Iterations: $iterations\n";
        echo "Total Time: " . number_format($totalTime, 4) . "s\n";
        echo "Average Time: " . number_format($avgTime * 1000, 2) . "ms (per set)\n";
        echo "Sets/Second: " . number_format($iterations / $totalTime, 2) . "\n";

        $this->results['cache_set'] = [
            'iterations' => $iterations,
            'total_time' => $totalTime,
            'avg_time_ms' => $avgTime * 1000,
            'ops_per_sec' => $iterations / $totalTime,
        ];

        $this->assertLessThan(10, $avgTime * 1000, "Cache set should be faster than 10ms");
    }

    /**
     * Benchmark: Cache Get - Hot Cache (Hit)
     */
    public function testHotCacheGet()
    {
        echo "\n\n=== BENCHMARK: Facial Recognition Cache - Hot Cache ===\n";

        $iterations = 1000;
        $numEntries = 50;

        // Pre-populate cache
        $hashes = [];
        for ($i = 0; $i < $numEntries; $i++) {
            $hash = hash('sha256', "test_image_$i");
            $hashes[] = $hash;

            $result = [
                'employee_id' => $i,
                'confidence' => 0.95,
                'verified' => true,
            ];

            $this->cache->set($hash, $result, true);
        }

        $start = microtime(true);
        $hits = 0;

        for ($i = 0; $i < $iterations; $i++) {
            // Randomly select a hash
            $hash = $hashes[array_rand($hashes)];
            $result = $this->cache->get($hash);
            if ($result !== null) {
                $hits++;
            }
        }

        $end = microtime(true);
        $totalTime = ($end - $start);
        $avgTime = $totalTime / $iterations;

        echo "Iterations: $iterations\n";
        echo "Cache Hits: $hits\n";
        echo "Hit Rate: " . number_format(($hits / $iterations) * 100, 2) . "%\n";
        echo "Total Time: " . number_format($totalTime, 4) . "s\n";
        echo "Average Time: " . number_format($avgTime * 1000, 2) . "ms (per get)\n";
        echo "Gets/Second: " . number_format($iterations / $totalTime, 2) . "\n";

        $this->results['hot_cache_get'] = [
            'iterations' => $iterations,
            'hits' => $hits,
            'hit_rate' => ($hits / $iterations) * 100,
            'total_time' => $totalTime,
            'avg_time_ms' => $avgTime * 1000,
            'ops_per_sec' => $iterations / $totalTime,
        ];

        // Calculate speedup vs simulated API call
        $simulatedAPITime = 2000; // 2 seconds per DeepFace API call
        $timeSaved = ($hits * $simulatedAPITime) - ($totalTime * 1000);
        $speedup = $simulatedAPITime / ($avgTime * 1000);

        echo "\nSimulated DeepFace API time: {$simulatedAPITime}ms\n";
        echo "Cache speedup: " . number_format($speedup, 0) . "x faster\n";
        echo "Time saved for $hits recognitions: " . number_format($timeSaved / 1000, 2) . "s\n";

        $this->results['hot_cache_get']['speedup'] = $speedup;
        $this->results['hot_cache_get']['time_saved_s'] = $timeSaved / 1000;

        $this->assertGreaterThan(95, ($hits / $iterations) * 100, "Hit rate should be >95%");
        $this->assertLessThan(2, $avgTime * 1000, "Hot cache hit should be faster than 2ms");
    }

    /**
     * Benchmark: LRU Eviction Performance
     */
    public function testLRUEviction()
    {
        echo "\n\n=== BENCHMARK: LRU Eviction Performance ===\n";

        // Get max entries (protected property, using reflection)
        $reflection = new \ReflectionClass($this->cache);
        $property = $reflection->getProperty('maxCacheEntries');
        $property->setAccessible(true);
        $maxEntries = $property->getValue($this->cache);

        echo "Max Cache Entries: $maxEntries\n";

        // Fill cache to trigger eviction
        $start = microtime(true);

        for ($i = 0; $i < $maxEntries + 100; $i++) { // Overfill by 100
            $hash = hash('sha256', "test_image_$i");
            $result = ['employee_id' => $i, 'verified' => true];
            $this->cache->set($hash, $result, true);
        }

        $end = microtime(true);
        $totalTime = ($end - $start);

        $metrics = $this->cache->getMetrics();

        echo "\nEntries added: " . ($maxEntries + 100) . "\n";
        echo "Current entries: " . $metrics['total_entries'] . "\n";
        echo "Total Time: " . number_format($totalTime, 4) . "s\n";
        echo "Average Time per Set: " . number_format(($totalTime / ($maxEntries + 100)) * 1000, 2) . "ms\n";

        $this->results['lru_eviction'] = [
            'max_entries' => $maxEntries,
            'entries_added' => $maxEntries + 100,
            'final_entries' => $metrics['total_entries'],
            'total_time' => $totalTime,
            'avg_set_time_ms' => ($totalTime / ($maxEntries + 100)) * 1000,
        ];

        $this->assertLessThanOrEqual($maxEntries, $metrics['total_entries'],
            "Cache should enforce LRU limit");
    }

    /**
     * Benchmark: Cache Metrics Tracking Performance
     */
    public function testMetricsTracking()
    {
        echo "\n\n=== BENCHMARK: Metrics Tracking Overhead ===\n";

        $iterations = 500;

        // Populate some data
        for ($i = 0; $i < 50; $i++) {
            $hash = hash('sha256', "test_image_$i");
            $this->cache->set($hash, ['employee_id' => $i], true);
        }

        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            // Mix of hits and misses
            if ($i % 2 === 0) {
                $hash = hash('sha256', "test_image_" . ($i % 50));
                $this->cache->get($hash); // Hit
            } else {
                $hash = hash('sha256', "new_image_$i");
                $this->cache->get($hash); // Miss
            }
        }

        $end = microtime(true);
        $totalTime = ($end - $start);

        $metrics = $this->cache->getMetrics();

        echo "Iterations: $iterations\n";
        echo "Hits: " . $metrics['hits'] . "\n";
        echo "Misses: " . $metrics['misses'] . "\n";
        echo "Hit Rate: " . number_format($metrics['hit_rate'], 2) . "%\n";
        echo "Total Time: " . number_format($totalTime, 4) . "s\n";
        echo "Average Time: " . number_format(($totalTime / $iterations) * 1000, 2) . "ms\n";

        $this->results['metrics_tracking'] = [
            'iterations' => $iterations,
            'hits' => $metrics['hits'],
            'misses' => $metrics['misses'],
            'hit_rate' => $metrics['hit_rate'],
            'total_time' => $totalTime,
            'avg_time_ms' => ($totalTime / $iterations) * 1000,
        ];

        $this->assertLessThan(5, ($totalTime / $iterations) * 1000,
            "Metrics tracking overhead should be minimal");
    }

    /**
     * Benchmark: Image Hash Generation
     */
    public function testImageHashGeneration()
    {
        echo "\n\n=== BENCHMARK: Image Hash Generation ===\n";

        // Create a test image file
        $tempImage = sys_get_temp_dir() . '/test_facial_image.jpg';
        $img = imagecreatetruecolor(640, 480);
        imagejpeg($img, $tempImage, 90);
        imagedestroy($img);

        $iterations = 100;

        // Test hash from file
        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $hash = FacialRecognitionCache::hashImage($tempImage, true);
        }

        $end = microtime(true);
        $fileHashTime = ($end - $start) / $iterations;

        echo "Hash from file path:\n";
        echo "  Iterations: $iterations\n";
        echo "  Average Time: " . number_format($fileHashTime * 1000, 2) . "ms\n";

        // Test hash from content
        $imageContent = file_get_contents($tempImage);

        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $hash = FacialRecognitionCache::hashImage($imageContent, false);
        }

        $end = microtime(true);
        $contentHashTime = ($end - $start) / $iterations;

        echo "\nHash from content:\n";
        echo "  Iterations: $iterations\n";
        echo "  Average Time: " . number_format($contentHashTime * 1000, 2) . "ms\n";

        $this->results['hash_generation'] = [
            'file_hash_ms' => $fileHashTime * 1000,
            'content_hash_ms' => $contentHashTime * 1000,
            'iterations' => $iterations,
        ];

        // Cleanup
        unlink($tempImage);

        $this->assertLessThan(10, $fileHashTime * 1000, "File hash should be faster than 10ms");
        $this->assertLessThan(5, $contentHashTime * 1000, "Content hash should be faster than 5ms");
    }

    /**
     * Display summary at the end
     */
    protected function tearDown(): void
    {
        if (!empty($this->results)) {
            echo "\n\n" . str_repeat("=", 70) . "\n";
            echo "FACIAL RECOGNITION CACHE BENCHMARK SUMMARY\n";
            echo str_repeat("=", 70) . "\n";

            // Cache Performance
            if (isset($this->results['cold_cache_get']) && isset($this->results['hot_cache_get'])) {
                echo "\nCache Performance:\n";
                echo "  Cold Cache (miss): " . number_format($this->results['cold_cache_get']['avg_time_ms'], 2) . "ms\n";
                echo "  Hot Cache (hit):   " . number_format($this->results['hot_cache_get']['avg_time_ms'], 2) . "ms\n";
                echo "  Cache Set:         " . number_format($this->results['cache_set']['avg_time_ms'], 2) . "ms\n";
            }

            // DeepFace API Speedup
            if (isset($this->results['hot_cache_get']['speedup'])) {
                echo "\nDeepFace API Comparison:\n";
                echo "  Simulated API time: 2000ms\n";
                echo "  Cache hit time:     " . number_format($this->results['hot_cache_get']['avg_time_ms'], 2) . "ms\n";
                echo "  Speedup:            " . number_format($this->results['hot_cache_get']['speedup'], 0) . "x faster\n";
                echo "  Time saved (1000 recognitions): " .
                     number_format($this->results['hot_cache_get']['time_saved_s'], 2) . "s\n";
            }

            // LRU Eviction
            if (isset($this->results['lru_eviction'])) {
                echo "\nLRU Eviction:\n";
                echo "  Max Entries:   " . $this->results['lru_eviction']['max_entries'] . "\n";
                echo "  Final Entries: " . $this->results['lru_eviction']['final_entries'] . "\n";
                echo "  Avg Set Time:  " . number_format($this->results['lru_eviction']['avg_set_time_ms'], 2) . "ms\n";
            }

            // Hash Generation
            if (isset($this->results['hash_generation'])) {
                echo "\nHash Generation:\n";
                echo "  From File:    " . number_format($this->results['hash_generation']['file_hash_ms'], 2) . "ms\n";
                echo "  From Content: " . number_format($this->results['hash_generation']['content_hash_ms'], 2) . "ms\n";
            }

            echo "\n" . str_repeat("=", 70) . "\n";
        }

        // Cleanup
        $this->cache->clear();

        parent::tearDown();
    }
}
