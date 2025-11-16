<?php

namespace App\Services;

use App\Models\GeofenceModel;
use App\Models\SettingModel;

/**
 * Geolocation Service
 *
 * Handles geolocation validation and geofencing calculations
 */
class GeolocationService
{
    protected $geofenceModel;
    protected $settingModel;

    const EARTH_RADIUS_METERS = 6371000; // Earth's radius in meters

    public function __construct()
    {
        $this->geofenceModel = new GeofenceModel();
        $this->settingModel = new SettingModel();
    }

    /**
     * Validate coordinates
     *
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    public function validateCoordinates(float $latitude, float $longitude): array
    {
        // Validate latitude range (-90 to 90)
        if ($latitude < -90 || $latitude > 90) {
            return [
                'valid' => false,
                'error' => 'Latitude inválida. Deve estar entre -90 e 90.',
            ];
        }

        // Validate longitude range (-180 to 180)
        if ($longitude < -180 || $longitude > 180) {
            return [
                'valid' => false,
                'error' => 'Longitude inválida. Deve estar entre -180 e 180.',
            ];
        }

        // Check if coordinates are not (0, 0) - likely invalid
        if ($latitude === 0.0 && $longitude === 0.0) {
            return [
                'valid' => false,
                'error' => 'Coordenadas inválidas (0, 0).',
            ];
        }

        return [
            'valid' => true,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    }

    /**
     * Check if point is within any active geofence
     *
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    public function validateGeofence(float $latitude, float $longitude): array
    {
        // Validate coordinates first
        $validation = $this->validateCoordinates($latitude, $longitude);

        if (!$validation['valid']) {
            return $validation;
        }

        // Check if geofencing is required
        $requireGeofence = $this->settingModel->get('require_geofence', false);

        if (!$requireGeofence) {
            return [
                'valid' => true,
                'message' => 'Geofencing não é obrigatório.',
                'geofence_required' => false,
            ];
        }

        // Get all active geofences
        $geofences = $this->geofenceModel
            ->where('active', true)
            ->findAll();

        if (empty($geofences)) {
            // No geofences configured, allow access
            return [
                'valid' => true,
                'message' => 'Nenhuma cerca virtual configurada.',
                'geofence_required' => true,
                'geofences_configured' => false,
            ];
        }

        // Check if point is within any geofence
        $matchedGeofence = null;
        $nearestGeofence = null;
        $nearestDistance = PHP_FLOAT_MAX;

        foreach ($geofences as $geofence) {
            $distance = $this->calculateDistance(
                $latitude,
                $longitude,
                $geofence->latitude,
                $geofence->longitude
            );

            // Track nearest geofence
            if ($distance < $nearestDistance) {
                $nearestDistance = $distance;
                $nearestGeofence = $geofence;
            }

            // Check if within radius
            if ($distance <= $geofence->radius_meters) {
                $matchedGeofence = $geofence;
                break;
            }
        }

        if ($matchedGeofence) {
            return [
                'valid' => true,
                'geofence_matched' => true,
                'geofence' => [
                    'id' => $matchedGeofence->id,
                    'name' => $matchedGeofence->name,
                    'distance_meters' => $this->calculateDistance(
                        $latitude,
                        $longitude,
                        $matchedGeofence->latitude,
                        $matchedGeofence->longitude
                    ),
                ],
            ];
        }

        // Not within any geofence
        return [
            'valid' => false,
            'geofence_matched' => false,
            'error' => 'Você está fora da área permitida para registro de ponto.',
            'nearest_geofence' => [
                'name' => $nearestGeofence->name,
                'distance_meters' => round($nearestDistance, 2),
                'distance_readable' => $this->formatDistance($nearestDistance),
            ],
        ];
    }

    /**
     * Calculate distance between two points using Haversine formula
     *
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float Distance in meters
     */
    public function calculateDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        // Convert degrees to radians
        $lat1Rad = deg2rad($lat1);
        $lon1Rad = deg2rad($lon1);
        $lat2Rad = deg2rad($lat2);
        $lon2Rad = deg2rad($lon2);

        // Haversine formula
        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLon = $lon2Rad - $lon1Rad;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
            cos($lat1Rad) * cos($lat2Rad) *
            sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = self::EARTH_RADIUS_METERS * $c;

        return $distance;
    }

    /**
     * Check if point is within a specific geofence
     *
     * @param float $latitude
     * @param float $longitude
     * @param int $geofenceId
     * @return array
     */
    public function isWithinGeofence(float $latitude, float $longitude, int $geofenceId): array
    {
        $geofence = $this->geofenceModel->find($geofenceId);

        if (!$geofence) {
            return [
                'within' => false,
                'error' => 'Cerca virtual não encontrada.',
            ];
        }

        $distance = $this->calculateDistance(
            $latitude,
            $longitude,
            $geofence->latitude,
            $geofence->longitude
        );

        $within = $distance <= $geofence->radius_meters;

        return [
            'within' => $within,
            'geofence' => [
                'id' => $geofence->id,
                'name' => $geofence->name,
                'center' => [
                    'latitude' => $geofence->latitude,
                    'longitude' => $geofence->longitude,
                ],
                'radius_meters' => $geofence->radius_meters,
            ],
            'distance_meters' => round($distance, 2),
            'distance_readable' => $this->formatDistance($distance),
        ];
    }

    /**
     * Get all geofences with distance from a point
     *
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    public function getGeofencesWithDistance(float $latitude, float $longitude): array
    {
        $geofences = $this->geofenceModel
            ->where('active', true)
            ->findAll();

        $result = [];

        foreach ($geofences as $geofence) {
            $distance = $this->calculateDistance(
                $latitude,
                $longitude,
                $geofence->latitude,
                $geofence->longitude
            );

            $result[] = [
                'id' => $geofence->id,
                'name' => $geofence->name,
                'description' => $geofence->description,
                'center' => [
                    'latitude' => $geofence->latitude,
                    'longitude' => $geofence->longitude,
                ],
                'radius_meters' => $geofence->radius_meters,
                'distance_meters' => round($distance, 2),
                'distance_readable' => $this->formatDistance($distance),
                'within' => $distance <= $geofence->radius_meters,
            ];
        }

        // Sort by distance
        usort($result, function ($a, $b) {
            return $a['distance_meters'] <=> $b['distance_meters'];
        });

        return $result;
    }

    /**
     * Get nearest geofence
     *
     * @param float $latitude
     * @param float $longitude
     * @return array|null
     */
    public function getNearestGeofence(float $latitude, float $longitude): ?array
    {
        $geofences = $this->getGeofencesWithDistance($latitude, $longitude);

        return !empty($geofences) ? $geofences[0] : null;
    }

    /**
     * Format distance for human readability
     *
     * @param float $meters
     * @return string
     */
    public function formatDistance(float $meters): string
    {
        if ($meters < 1000) {
            return round($meters, 0) . ' metros';
        }

        $kilometers = $meters / 1000;
        return round($kilometers, 2) . ' km';
    }

    /**
     * Get address from coordinates (reverse geocoding)
     * Uses Nominatim OpenStreetMap API
     *
     * @param float $latitude
     * @param float $longitude
     * @return array
     */
    public function reverseGeocode(float $latitude, float $longitude): array
    {
        try {
            $client = \Config\Services::curlrequest();

            $url = 'https://nominatim.openstreetmap.org/reverse';
            $params = [
                'format' => 'json',
                'lat' => $latitude,
                'lon' => $longitude,
                'zoom' => 18,
                'addressdetails' => 1,
            ];

            $response = $client->get($url, [
                'query' => $params,
                'headers' => [
                    'User-Agent' => 'PontoEletronico/1.0',
                ],
                'timeout' => 5,
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() !== 200) {
                return [
                    'success' => false,
                    'error' => 'Erro ao obter endereço.',
                ];
            }

            $data = json_decode($response->getBody(), true);

            if (!$data || isset($data['error'])) {
                return [
                    'success' => false,
                    'error' => 'Endereço não encontrado.',
                ];
            }

            $address = $data['address'] ?? [];

            return [
                'success' => true,
                'formatted_address' => $data['display_name'] ?? '',
                'address' => [
                    'road' => $address['road'] ?? '',
                    'suburb' => $address['suburb'] ?? '',
                    'city' => $address['city'] ?? $address['town'] ?? $address['village'] ?? '',
                    'state' => $address['state'] ?? '',
                    'country' => $address['country'] ?? '',
                    'postcode' => $address['postcode'] ?? '',
                ],
                'raw' => $data,
            ];

        } catch (\Exception $e) {
            log_message('error', 'Reverse geocode error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Erro ao conectar com serviço de geolocalização.',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get coordinates from address (geocoding)
     * Uses Nominatim OpenStreetMap API
     *
     * @param string $address
     * @return array
     */
    public function geocode(string $address): array
    {
        try {
            $client = \Config\Services::curlrequest();

            $url = 'https://nominatim.openstreetmap.org/search';
            $params = [
                'format' => 'json',
                'q' => $address,
                'limit' => 1,
                'addressdetails' => 1,
            ];

            $response = $client->get($url, [
                'query' => $params,
                'headers' => [
                    'User-Agent' => 'PontoEletronico/1.0',
                ],
                'timeout' => 5,
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() !== 200) {
                return [
                    'success' => false,
                    'error' => 'Erro ao obter coordenadas.',
                ];
            }

            $data = json_decode($response->getBody(), true);

            if (empty($data)) {
                return [
                    'success' => false,
                    'error' => 'Endereço não encontrado.',
                ];
            }

            $result = $data[0];

            return [
                'success' => true,
                'latitude' => (float) $result['lat'],
                'longitude' => (float) $result['lon'],
                'formatted_address' => $result['display_name'],
                'address' => $result['address'] ?? [],
            ];

        } catch (\Exception $e) {
            log_message('error', 'Geocode error: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => 'Erro ao conectar com serviço de geolocalização.',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate area of a circle (geofence coverage)
     *
     * @param float $radiusMeters
     * @return array
     */
    public function calculateGeofenceArea(float $radiusMeters): array
    {
        $areaSquareMeters = pi() * pow($radiusMeters, 2);

        return [
            'radius_meters' => $radiusMeters,
            'area_square_meters' => round($areaSquareMeters, 2),
            'area_square_kilometers' => round($areaSquareMeters / 1000000, 4),
            'diameter_meters' => $radiusMeters * 2,
        ];
    }

    /**
     * Get geofence statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $totalGeofences = $this->geofenceModel->countAllResults(false);
        $activeGeofences = $this->geofenceModel->where('active', true)->countAllResults();

        return [
            'total_geofences' => $totalGeofences,
            'active_geofences' => $activeGeofences,
            'inactive_geofences' => $totalGeofences - $activeGeofences,
            'geofencing_required' => $this->settingModel->get('require_geofence', false),
            'geolocation_required' => $this->settingModel->get('require_geolocation', false),
        ];
    }
}
