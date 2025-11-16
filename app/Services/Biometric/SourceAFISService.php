<?php

namespace App\Services\Biometric;

use CodeIgniter\HTTP\CURLRequest;
use Exception;

/**
 * SourceAFIS Service
 *
 * Handles fingerprint recognition using SourceAFIS algorithm
 * Supports multiple backends: Mock, API REST, or PHP native algorithm
 *
 * SourceAFIS is a fingerprint recognition engine with:
 * - ISO/IEC 19794-2 standard compliance
 * - Minutiae-based matching
 * - High accuracy (FAR < 0.01%, FRR < 1%)
 */
class SourceAFISService
{
    private string $mode;
    private ?string $apiUrl;
    private int $timeout;
    private float $threshold;
    private CURLRequest $client;

    /**
     * Constructor
     *
     * @param string|null $mode Backend mode: 'api', 'native', 'mock' (default: from env)
     */
    public function __construct(?string $mode = null)
    {
        $this->mode = $mode ?? getenv('SOURCEAFIS_MODE') ?: 'native';
        $this->apiUrl = getenv('SOURCEAFIS_API_URL') ?: 'http://localhost:5001';
        $this->timeout = (int)(getenv('SOURCEAFIS_TIMEOUT') ?: 30);
        $this->threshold = (float)(getenv('SOURCEAFIS_THRESHOLD') ?: 0.40);

        $this->client = \Config\Services::curlrequest([
            'timeout' => $this->timeout,
            'http_errors' => false,
        ]);
    }

    /**
     * Extract fingerprint template from image
     *
     * @param string $imagePath Path to fingerprint image
     * @return array{success: bool, template?: string, error?: string, minutiae_count?: int}
     */
    public function extractTemplate(string $imagePath): array
    {
        if (!file_exists($imagePath)) {
            return [
                'success' => false,
                'error' => 'Image file not found',
            ];
        }

        switch ($this->mode) {
            case 'api':
                return $this->extractTemplateAPI($imagePath);

            case 'native':
                return $this->extractTemplateNative($imagePath);

            case 'mock':
            default:
                return $this->extractTemplateMock($imagePath);
        }
    }

    /**
     * Compare two fingerprint templates
     *
     * @param string $template1 First template (probe)
     * @param string $template2 Second template (reference)
     * @return array{success: bool, similarity?: float, match?: bool, error?: string}
     */
    public function compareTemplates(string $template1, string $template2): array
    {
        switch ($this->mode) {
            case 'api':
                return $this->compareTemplatesAPI($template1, $template2);

            case 'native':
                return $this->compareTemplatesNative($template1, $template2);

            case 'mock':
            default:
                return $this->compareTemplatesMock($template1, $template2);
        }
    }

    /**
     * Verify fingerprint against stored template
     *
     * @param string $imagePath Path to fingerprint image to verify
     * @param string $storedTemplate Stored template from database
     * @return array{success: bool, match?: bool, similarity?: float, error?: string}
     */
    public function verify(string $imagePath, string $storedTemplate): array
    {
        // Extract template from image
        $extractResult = $this->extractTemplate($imagePath);

        if (!$extractResult['success']) {
            return [
                'success' => false,
                'error' => $extractResult['error'] ?? 'Failed to extract template',
            ];
        }

        // Compare with stored template
        $compareResult = $this->compareTemplates(
            $extractResult['template'],
            $storedTemplate
        );

        if (!$compareResult['success']) {
            return [
                'success' => false,
                'error' => $compareResult['error'] ?? 'Failed to compare templates',
            ];
        }

        $similarity = $compareResult['similarity'] ?? 0.0;
        $match = $similarity >= $this->threshold;

        return [
            'success' => true,
            'match' => $match,
            'similarity' => $similarity,
            'threshold' => $this->threshold,
        ];
    }

    /**
     * Health check
     *
     * @return array{success: bool, mode: string, api_available?: bool, error?: string}
     */
    public function health(): array
    {
        $result = [
            'success' => true,
            'mode' => $this->mode,
        ];

        if ($this->mode === 'api') {
            try {
                $response = $this->client->get($this->apiUrl . '/health', [
                    'timeout' => 5,
                ]);

                $result['api_available'] = $response->getStatusCode() === 200;

                if (!$result['api_available']) {
                    $result['success'] = false;
                    $result['error'] = 'SourceAFIS API is not responding';
                }
            } catch (Exception $e) {
                $result['success'] = false;
                $result['api_available'] = false;
                $result['error'] = 'Cannot connect to SourceAFIS API: ' . $e->getMessage();
            }
        }

        return $result;
    }

    // ==================== API Backend Methods ====================

    /**
     * Extract template via API
     */
    private function extractTemplateAPI(string $imagePath): array
    {
        try {
            $imageData = base64_encode(file_get_contents($imagePath));

            $response = $this->client->post($this->apiUrl . '/extract', [
                'json' => [
                    'image' => $imageData,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody(), true);

            if ($statusCode !== 200) {
                return [
                    'success' => false,
                    'error' => $body['error'] ?? 'API request failed',
                ];
            }

            return [
                'success' => true,
                'template' => $body['template'],
                'minutiae_count' => $body['minutiae_count'] ?? null,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'API error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Compare templates via API
     */
    private function compareTemplatesAPI(string $template1, string $template2): array
    {
        try {
            $response = $this->client->post($this->apiUrl . '/compare', [
                'json' => [
                    'template1' => $template1,
                    'template2' => $template2,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody(), true);

            if ($statusCode !== 200) {
                return [
                    'success' => false,
                    'error' => $body['error'] ?? 'API request failed',
                ];
            }

            return [
                'success' => true,
                'similarity' => (float)$body['similarity'],
                'match' => (bool)$body['match'],
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'API error: ' . $e->getMessage(),
            ];
        }
    }

    // ==================== Native PHP Backend Methods ====================

    /**
     * Extract template using native PHP algorithm
     *
     * This is a simplified minutiae extraction algorithm.
     * For production, use actual SourceAFIS library via API.
     */
    private function extractTemplateNative(string $imagePath): array
    {
        try {
            // Load image
            $image = $this->loadImage($imagePath);

            if ($image === false) {
                return [
                    'success' => false,
                    'error' => 'Failed to load image',
                ];
            }

            // Convert to grayscale
            imagefilter($image, IMG_FILTER_GRAYSCALE);

            // Extract minutiae (simplified)
            $minutiae = $this->extractMinutiaeSimplified($image);

            // Generate template string
            $template = base64_encode(json_encode($minutiae));

            imagedestroy($image);

            return [
                'success' => true,
                'template' => $template,
                'minutiae_count' => count($minutiae),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Extraction error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Compare templates using native PHP algorithm
     */
    private function compareTemplatesNative(string $template1, string $template2): array
    {
        try {
            $minutiae1 = json_decode(base64_decode($template1), true);
            $minutiae2 = json_decode(base64_decode($template2), true);

            if (!$minutiae1 || !$minutiae2) {
                return [
                    'success' => false,
                    'error' => 'Invalid template format',
                ];
            }

            // Calculate similarity using minutiae matching
            $similarity = $this->calculateMinutiaeSimilarity($minutiae1, $minutiae2);

            return [
                'success' => true,
                'similarity' => $similarity,
                'match' => $similarity >= $this->threshold,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Comparison error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Simplified minutiae extraction
     *
     * Extracts ridge ending and bifurcation points.
     * This is a basic implementation; production should use SourceAFIS.
     *
     * @param resource $image GD image resource
     * @return array Array of minutiae points
     */
    private function extractMinutiaeSimplified($image): array
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $minutiae = [];

        // Apply binarization threshold
        $threshold = 127;

        // Scan image for ridge patterns (simplified)
        for ($y = 2; $y < $height - 2; $y += 3) {
            for ($x = 2; $x < $width - 2; $x += 3) {
                $pixel = imagecolorat($image, $x, $y);
                $gray = $pixel & 0xFF;

                if ($gray < $threshold) {
                    // Check neighborhood for ridge ending or bifurcation
                    $neighbors = $this->countRidgeNeighbors($image, $x, $y, $threshold);

                    // Ridge ending: 1 neighbor
                    // Bifurcation: 3 neighbors
                    if ($neighbors === 1 || $neighbors === 3) {
                        $minutiae[] = [
                            'x' => $x,
                            'y' => $y,
                            'type' => $neighbors === 1 ? 'ending' : 'bifurcation',
                            'angle' => $this->estimateRidgeAngle($image, $x, $y, $threshold),
                        ];
                    }
                }
            }
        }

        return $minutiae;
    }

    /**
     * Count ridge neighbors (8-connectivity)
     */
    private function countRidgeNeighbors($image, int $x, int $y, int $threshold): int
    {
        $count = 0;
        $offsets = [
            [-1, -1], [0, -1], [1, -1],
            [-1,  0],          [1,  0],
            [-1,  1], [0,  1], [1,  1],
        ];

        foreach ($offsets as [$dx, $dy]) {
            $nx = $x + $dx;
            $ny = $y + $dy;

            if ($nx >= 0 && $nx < imagesx($image) && $ny >= 0 && $ny < imagesy($image)) {
                $pixel = imagecolorat($image, $nx, $ny);
                $gray = $pixel & 0xFF;

                if ($gray < $threshold) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Estimate ridge angle at point
     */
    private function estimateRidgeAngle($image, int $x, int $y, int $threshold): float
    {
        // Simplified gradient-based angle estimation
        $gx = 0;
        $gy = 0;

        for ($dy = -1; $dy <= 1; $dy++) {
            for ($dx = -1; $dx <= 1; $dx++) {
                $nx = $x + $dx;
                $ny = $y + $dy;

                if ($nx >= 0 && $nx < imagesx($image) && $ny >= 0 && $ny < imagesy($image)) {
                    $pixel = imagecolorat($image, $nx, $ny);
                    $gray = $pixel & 0xFF;

                    $gx += $dx * $gray;
                    $gy += $dy * $gray;
                }
            }
        }

        return atan2($gy, $gx);
    }

    /**
     * Calculate minutiae similarity
     *
     * Uses distance-based matching with orientation constraint
     */
    private function calculateMinutiaeSimilarity(array $minutiae1, array $minutiae2): float
    {
        if (empty($minutiae1) || empty($minutiae2)) {
            return 0.0;
        }

        $matches = 0;
        $maxDistance = 20; // pixels
        $maxAngleDiff = 0.5; // radians (~28 degrees)

        foreach ($minutiae1 as $m1) {
            foreach ($minutiae2 as $m2) {
                // Must be same type
                if ($m1['type'] !== $m2['type']) {
                    continue;
                }

                // Calculate Euclidean distance
                $distance = sqrt(
                    pow($m1['x'] - $m2['x'], 2) +
                    pow($m1['y'] - $m2['y'], 2)
                );

                if ($distance > $maxDistance) {
                    continue;
                }

                // Check angle difference
                $angleDiff = abs($m1['angle'] - $m2['angle']);
                if ($angleDiff > pi()) {
                    $angleDiff = 2 * pi() - $angleDiff;
                }

                if ($angleDiff <= $maxAngleDiff) {
                    $matches++;
                    break; // Each minutia can only match once
                }
            }
        }

        // Calculate similarity as ratio of matched minutiae
        $avgCount = (count($minutiae1) + count($minutiae2)) / 2;

        return min(1.0, $matches / max(1, $avgCount));
    }

    /**
     * Load image from file
     */
    private function loadImage(string $path)
    {
        $imageInfo = getimagesize($path);

        if ($imageInfo === false) {
            return false;
        }

        $mimeType = $imageInfo['mime'];

        switch ($mimeType) {
            case 'image/jpeg':
                return imagecreatefromjpeg($path);
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/gif':
                return imagecreatefromgif($path);
            case 'image/bmp':
                return imagecreatefrombmp($path);
            default:
                return false;
        }
    }

    // ==================== Mock Backend Methods ====================

    /**
     * Extract template (mock)
     *
     * Returns a deterministic template based on file hash
     */
    private function extractTemplateMock(string $imagePath): array
    {
        $hash = hash_file('sha256', $imagePath);

        return [
            'success' => true,
            'template' => $hash,
            'minutiae_count' => 42, // Mock count
        ];
    }

    /**
     * Compare templates (mock)
     *
     * Returns perfect match if hashes are equal
     */
    private function compareTemplatesMock(string $template1, string $template2): array
    {
        $similarity = $template1 === $template2 ? 1.0 : 0.0;

        return [
            'success' => true,
            'similarity' => $similarity,
            'match' => $similarity >= $this->threshold,
        ];
    }
}
