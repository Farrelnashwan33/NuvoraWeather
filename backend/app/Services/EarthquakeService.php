<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EarthquakeService
{
    protected string $bmkgBaseUrl = 'https://data.bmkg.go.id/DataMKG/TEWS/';
    protected int $cacheTtlMinutes = 3;

    /**
     * Get latest earthquake from BMKG autogempa.json
     */
    public function getLatestEarthquake(?float $userLat = null, ?float $userLon = null): ?array
    {
        $cacheKey = 'bmkg_autogempa_latest';

        $data = Cache::remember($cacheKey, now()->addMinutes($this->cacheTtlMinutes), function () {
            try {
                $response = Http::timeout(6)
                    ->withHeaders(['User-Agent' => 'NuvoraWeather-DisasterMonitor/1.0'])
                    ->get($this->bmkgBaseUrl . 'autogempa.json');

                if (!$response->successful()) {
                    Log::warning("BMKG autogempa failed with status: " . $response->status());
                    return null;
                }

                $json = $response->json();
                return $json['Infogempa']['gempa'] ?? null;
            } catch (\Exception $e) {
                Log::error("BMKG autogempa error: " . $e->getMessage());
                return null;
            }
        });

        if (!$data) {
            return null;
        }

        return $this->normalizeEarthquakeItem($data, $userLat, $userLon);
    }

    /**
     * Get list of recent earthquakes from BMKG (M 5.0+ and Felt/Dirasakan)
     */
    public function getRecentEarthquakes(?float $userLat = null, ?float $userLon = null, ?string $filter = null): array
    {
        $cacheKey = 'bmkg_recent_all_earthquakes';

        $rawList = Cache::remember($cacheKey, now()->addMinutes($this->cacheTtlMinutes), function () {
            $list = [];

            // 1. Fetch M 5.0+ recent earthquakes
            try {
                $resM5 = Http::timeout(6)
                    ->withHeaders(['User-Agent' => 'NuvoraWeather-DisasterMonitor/1.0'])
                    ->get($this->bmkgBaseUrl . 'gempaterkini.json');

                if ($resM5->successful()) {
                    $m5Items = $resM5->json()['Infogempa']['gempa'] ?? [];
                    foreach ($m5Items as $item) {
                        $item['source_type'] = 'm5_plus';
                        $list[] = $item;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("BMKG gempaterkini error: " . $e->getMessage());
            }

            // 2. Fetch felt earthquakes (gempadirasakan.json)
            try {
                $resFelt = Http::timeout(6)
                    ->withHeaders(['User-Agent' => 'NuvoraWeather-DisasterMonitor/1.0'])
                    ->get($this->bmkgBaseUrl . 'gempadirasakan.json');

                if ($resFelt->successful()) {
                    $feltItems = $resFelt->json()['Infogempa']['gempa'] ?? [];
                    foreach ($feltItems as $item) {
                        $item['source_type'] = 'felt';
                        $list[] = $item;
                    }
                }
            } catch (\Exception $e) {
                Log::warning("BMKG gempadirasakan error: " . $e->getMessage());
            }

            return $list;
        });

        // Deduplicate and normalize
        $normalized = [];
        $seen = [];

        foreach ($rawList as $raw) {
            $uniqueKey = ($raw['DateTime'] ?? '') . '_' . ($raw['Coordinates'] ?? '');
            if (isset($seen[$uniqueKey])) continue;
            $seen[$uniqueKey] = true;

            $item = $this->normalizeEarthquakeItem($raw, $userLat, $userLon);
            if ($item) {
                $normalized[] = $item;
            }
        }

        // Apply filter if specified
        if ($filter) {
            $normalized = array_filter($normalized, function ($eq) use ($filter) {
                return match ($filter) {
                    'm3' => $eq['magnitude'] >= 3.0,
                    'm4' => $eq['magnitude'] >= 4.0,
                    'm5' => $eq['magnitude'] >= 5.0,
                    'felt' => !empty($eq['felt_status']) && $eq['felt_status'] !== '-',
                    default => true,
                };
            });
        }

        // Sort by timestamp descending
        usort($normalized, function ($a, $b) {
            return strtotime($b['datetime'] ?? '0') <=> strtotime($a['datetime'] ?? '0');
        });

        return array_values($normalized);
    }

    /**
     * Normalize BMKG raw earthquake object into consistent UI response
     */
    protected function normalizeEarthquakeItem(array $raw, ?float $userLat, ?float $userLon): array
    {
        $coordStr = $raw['Coordinates'] ?? '';
        $coords = explode(',', $coordStr);
        $lat = isset($coords[0]) ? (float) trim($coords[0]) : null;
        $lon = isset($coords[1]) ? (float) trim($coords[1]) : null;

        $mag = (float) ($raw['Magnitude'] ?? 0);
        $depthStr = $raw['Kedalaman'] ?? '0 km';
        $depthNum = (float) filter_var($depthStr, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        $shakemapName = $raw['Shakemap'] ?? null;
        $shakemapUrl = $shakemapName ? $this->bmkgBaseUrl . $shakemapName : null;

        $potensi = $raw['Potensi'] ?? 'Tidak berpotensi tsunami';
        $isTsunamiPotential = str_contains(strtolower($potensi), 'berpotensi tsunami') && !str_contains(strtolower($potensi), 'tidak');

        $distanceKm = null;
        $distanceFormatted = null;

        if ($userLat !== null && $userLon !== null && $lat !== null && $lon !== null) {
            $distanceKm = round($this->haversineDistance($userLat, $userLon, $lat, $lon));
            $distanceFormatted = "{$distanceKm} km dari lokasi Anda";
        }

        // Magnitude level categorization (strictly descriptive, not safety assurance)
        $level = 'LOW';
        $levelColor = 'blue';
        if ($mag >= 5.0) {
            $level = 'HIGH';
            $levelColor = 'rose';
        } elseif ($mag >= 4.0) {
            $level = 'MODERATE';
            $levelColor = 'amber';
        }

        return [
            'id' => md5(($raw['DateTime'] ?? '') . $coordStr),
            'datetime' => $raw['DateTime'] ?? null,
            'date' => $raw['Tanggal'] ?? '',
            'time_wib' => $raw['Jam'] ?? '',
            'display_time' => ($raw['Tanggal'] ?? '') . ' • ' . ($raw['Jam'] ?? ''),
            'magnitude' => $mag,
            'depth' => $depthStr,
            'depth_km' => $depthNum,
            'latitude' => $lat,
            'longitude' => $lon,
            'coordinates' => $coordStr,
            'location' => $raw['Wilayah'] ?? '',
            'potensi_tsunami' => $potensi,
            'is_tsunami_potential' => $isTsunamiPotential,
            'felt_status' => $raw['Dirasakan'] ?? null,
            'shakemap_url' => $shakemapUrl,
            'shakemap_image' => $shakemapName,
            'level' => $level,
            'level_color' => $levelColor,
            'distance_km' => $distanceKm,
            'distance_formatted' => $distanceFormatted,
            'source' => 'BMKG (Badan Meteorologi, Klimatologi, dan Geofisika)',
            'updated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Calculate distance between two coordinates in kilometers using Haversine formula
     */
    protected function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
