<?php

namespace App\Services;

use App\Models\WeatherLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    protected string $weatherApiUrl;
    protected string $geocodingApiUrl;
    protected int $cacheTtlMinutes;

    public function __construct()
    {
        $this->weatherApiUrl = config('services.open_meteo.weather_url', 'https://api.open-meteo.com/v1/forecast');
        $this->geocodingApiUrl = config('services.open_meteo.geocoding_url', 'https://geocoding-api.open-meteo.com/v1/search');
        $this->cacheTtlMinutes = (int) config('services.open_meteo.cache_ttl', 15);
    }

    /**
     * Get complete weather data for a coordinate
     */
    public function getWeather(float $latitude, float $longitude, ?string $cityName = null, ?string $country = null): array
    {
        $cacheKey = sprintf('weather_data_%.2f_%.2f', $latitude, $longitude);

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheTtlMinutes), function () use ($latitude, $longitude, $cityName, $country) {
            $startTime = microtime(true);
            $statusCode = 200;

            try {
                $response = Http::timeout(10)->get($this->weatherApiUrl, [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'current' => 'temperature_2m,relative_humidity_2m,apparent_temperature,is_day,precipitation,weather_code,surface_pressure,wind_speed_10m,wind_direction_10m,dew_point_2m,uv_index,visibility',
                    'hourly' => 'temperature_2m,relative_humidity_2m,apparent_temperature,precipitation_probability,precipitation,weather_code,surface_pressure,visibility,wind_speed_10m,wind_direction_10m,uv_index,is_day',
                    'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,apparent_temperature_max,apparent_temperature_min,sunrise,sunset,uv_index_max,precipitation_sum,precipitation_probability_max,wind_speed_10m_max,wind_direction_10m_dominant',
                    'timezone' => 'auto',
                ]);

                $statusCode = $response->status();

                if (!$response->successful()) {
                    throw new \Exception("Weather API returned status {$statusCode}: " . $response->body());
                }

                $data = $response->json();
                $normalized = $this->normalizeWeatherData($data, $latitude, $longitude, $cityName, $country);

                $this->logRequest($cityName, $latitude, $longitude, $statusCode);

                return $normalized;
            } catch (\Exception $e) {
                Log::error("WeatherService Error: " . $e->getMessage(), [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                ]);

                $this->logRequest($cityName, $latitude, $longitude, $statusCode === 200 ? 500 : $statusCode);

                throw $e;
            }
        });
    }

    /**
     * Search cities and remote Indonesian regions using hybrid geocoding
     * (Curated Indonesian Remote Database + OpenStreetMap Nominatim + Open-Meteo)
     */
    public function searchCities(string $query): array
    {
        $trimmed = trim($query);
        if (strlen($trimmed) < 2) {
            return [];
        }

        $cacheKey = 'city_search_v2_' . md5(strtolower($trimmed));

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($trimmed) {
            $allResults = [];
            $seenCoords = [];

            // 1. Check curated Indonesian remote & outermost regions
            $localMatches = IndonesiaRegions::search($trimmed);
            foreach ($localMatches as $loc) {
                $coordKey = sprintf('%.2f_%.2f', $loc['latitude'], $loc['longitude']);
                $seenCoords[$coordKey] = true;
                $allResults[] = $loc;
            }

            // 2. Query OpenStreetMap Nominatim specifically for Indonesian administrative divisions, villages, islands & kecamatan
            try {
                $osmResponse = Http::timeout(4)
                    ->withHeaders(['User-Agent' => 'NuvoraWeatherApp/2.0 (weather@nuvora.local)'])
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'q' => $trimmed,
                        'format' => 'json',
                        'addressdetails' => 1,
                        'countrycodes' => 'id',
                        'limit' => 10,
                    ]);

                if ($osmResponse->successful()) {
                    $osmData = $osmResponse->json() ?? [];
                    foreach ($osmData as $item) {
                        $lat = (float) ($item['lat'] ?? 0);
                        $lon = (float) ($item['lon'] ?? 0);
                        $coordKey = sprintf('%.2f_%.2f', $lat, $lon);

                        if (!isset($seenCoords[$coordKey])) {
                            $seenCoords[$coordKey] = true;
                            $addr = $item['address'] ?? [];
                            $name = $item['name'] ?? $addr['village'] ?? $addr['town'] ?? $addr['city'] ?? $addr['county'] ?? $trimmed;

                            // Format Indonesian administrative hierarchy (Desa/Kelurahan, Kecamatan, Kabupaten/Kota, Provinsi)
                            $adminParts = [];
                            if (!empty($addr['village']) && $addr['village'] !== $name) $adminParts[] = 'Desa ' . $addr['village'];
                            if (!empty($addr['suburb'])) $adminParts[] = 'Kel. ' . $addr['suburb'];
                            if (!empty($addr['municipality'])) $adminParts[] = 'Kec. ' . $addr['municipality'];
                            if (!empty($addr['city_district'])) $adminParts[] = 'Kec. ' . $addr['city_district'];
                            if (!empty($addr['city'])) $adminParts[] = $addr['city'];
                            if (!empty($addr['county'])) $adminParts[] = $addr['county'];
                            if (!empty($addr['state'])) $adminParts[] = $addr['state'];

                            $adminStr = !empty($adminParts) ? implode(', ', array_unique($adminParts)) : ($addr['state'] ?? 'Indonesia');

                            $allResults[] = [
                                'id' => 'osm_' . ($item['place_id'] ?? rand(1000, 99999)),
                                'name' => $name,
                                'country' => 'Indonesia',
                                'country_code' => 'ID',
                                'flag' => '🇮🇩',
                                'admin1' => $adminStr,
                                'badge' => $item['type'] === 'island' ? 'Pulau' : ($item['type'] === 'village' ? 'Desa/Kelurahan' : 'Wilayah Indonesia'),
                                'latitude' => $lat,
                                'longitude' => $lon,
                                'timezone' => 'Asia/Jakarta',
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning("Nominatim Indonesia geocoding error: " . $e->getMessage());
            }

            // 3. Query Open-Meteo Geocoding for global & general city names
            try {
                $response = Http::timeout(4)->get($this->geocodingApiUrl, [
                    'name' => $trimmed,
                    'count' => 10,
                    'language' => 'en',
                    'format' => 'json',
                ]);

                if ($response->successful()) {
                    $results = $response->json('results') ?? [];
                    foreach ($results as $item) {
                        $lat = (float) ($item['latitude'] ?? 0);
                        $lon = (float) ($item['longitude'] ?? 0);
                        $coordKey = sprintf('%.2f_%.2f', $lat, $lon);

                        if (!isset($seenCoords[$coordKey])) {
                            $seenCoords[$coordKey] = true;
                            $countryCode = $item['country_code'] ?? '';
                            $isIndo = strtoupper($countryCode) === 'ID';

                            $allResults[] = [
                                'id' => $item['id'] ?? null,
                                'name' => $item['name'] ?? '',
                                'country' => $item['country'] ?? '',
                                'country_code' => $countryCode,
                                'flag' => $this->getCountryFlagEmoji($countryCode),
                                'admin1' => $item['admin1'] ?? null,
                                'badge' => $isIndo ? 'Kota/Kabupaten' : 'Global',
                                'latitude' => $lat,
                                'longitude' => $lon,
                                'timezone' => $item['timezone'] ?? 'UTC',
                                'population' => $item['population'] ?? null,
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Open-Meteo Geocoding Error: " . $e->getMessage());
            }

            return array_slice($allResults, 0, 18);
        });
    }

    /**
     * Get curated list of remote / 3T Indonesian regions for quick explorer
     */
    public static function getCuratedIndonesianRegions(): array
    {
        return IndonesiaRegions::getRemoteRegions();
    }

    /**
     * Reverse geocode coordinates to get location name
     */
    public function reverseGeocode(float $latitude, float $longitude): array
    {
        $cacheKey = sprintf('reverse_geo_%.3f_%.3f', $latitude, $longitude);

        return Cache::remember($cacheKey, now()->addDays(1), function () use ($latitude, $longitude) {
            try {
                // Free reverse geocoding via bigdatacloud
                $response = Http::timeout(5)->get('https://api.bigdatacloud.net/data/reverse-geocode-client', [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'localityLanguage' => 'en',
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $city = $data['city'] ?? $data['locality'] ?? $data['principalSubdivision'] ?? 'Current Location';
                    $country = $data['countryName'] ?? '';
                    $countryCode = $data['countryCode'] ?? '';

                    return [
                        'city' => $city,
                        'country' => $country,
                        'country_code' => $countryCode,
                        'flag' => $this->getCountryFlagEmoji($countryCode),
                        'latitude' => $latitude,
                        'longitude' => $longitude,
                    ];
                }
            } catch (\Exception $e) {
                Log::warning("Reverse Geocoding failed: " . $e->getMessage());
            }

            return [
                'city' => 'Custom Location',
                'country' => sprintf('%.2f°, %.2f°', $latitude, $longitude),
                'country_code' => '',
                'flag' => '📍',
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];
        });
    }

    /**
     * Normalize weather data into structured UI JSON
     */
    protected function normalizeWeatherData(array $raw, float $latitude, float $longitude, ?string $cityName, ?string $country): array
    {
        $current = $raw['current'] ?? [];
        $hourly = $raw['hourly'] ?? [];
        $daily = $raw['daily'] ?? [];
        $timezone = $raw['timezone'] ?? 'UTC';
        $elevation = $raw['elevation'] ?? 0;

        $isDay = (bool) ($current['is_day'] ?? 1);
        $wmoCode = (int) ($current['weather_code'] ?? 0);
        $weatherInfo = $this->interpretWmoCode($wmoCode, $isDay);

        // Calculate daylight arc progress
        $sunriseTime = $daily['sunrise'][0] ?? null;
        $sunsetTime = $daily['sunset'][0] ?? null;
        $sunProgress = $this->calculateSunProgress($sunriseTime, $sunsetTime, $current['time'] ?? 'now', $timezone);

        // Normalize Hourly (24 items)
        $hourlyList = [];
        $currentTimeIso = $current['time'] ?? date('Y-m-d\TH:00');
        $hourlyTimes = $hourly['time'] ?? [];
        $startIndex = 0;

        // Find current or nearest hour index
        foreach ($hourlyTimes as $idx => $t) {
            if ($t >= $currentTimeIso) {
                $startIndex = $idx;
                break;
            }
        }

        $hourlyLimit = min(24, count($hourlyTimes) - $startIndex);
        for ($i = 0; $i < $hourlyLimit; $i++) {
            $idx = $startIndex + $i;
            $hWmo = (int) ($hourly['weather_code'][$idx] ?? 0);
            $hIsDay = (bool) ($hourly['is_day'][$idx] ?? 1);
            $hInfo = $this->interpretWmoCode($hWmo, $hIsDay);

            $hourlyList[] = [
                'time' => $hourlyTimes[$idx],
                'display_time' => date('H:i', strtotime($hourlyTimes[$idx])),
                'temperature' => round((float) ($hourly['temperature_2m'][$idx] ?? 0)),
                'apparent_temperature' => round((float) ($hourly['apparent_temperature'][$idx] ?? 0)),
                'weather_code' => $hWmo,
                'condition' => $hInfo['condition'],
                'icon' => $hInfo['icon'],
                'precipitation_probability' => (int) ($hourly['precipitation_probability'][$idx] ?? 0),
                'precipitation' => (float) ($hourly['precipitation'][$idx] ?? 0),
                'humidity' => (int) ($hourly['relative_humidity_2m'][$idx] ?? 0),
                'wind_speed' => round((float) ($hourly['wind_speed_10m'][$idx] ?? 0), 1),
                'wind_direction' => (int) ($hourly['wind_direction_10m'][$idx] ?? 0),
                'uv_index' => round((float) ($hourly['uv_index'][$idx] ?? 0), 1),
                'is_day' => $hIsDay,
            ];
        }

        // Normalize Daily (7 days)
        $dailyList = [];
        $dailyTimes = $daily['time'] ?? [];
        $dailyCount = min(7, count($dailyTimes));

        for ($i = 0; $i < $dailyCount; $i++) {
            $dWmo = (int) ($daily['weather_code'][$i] ?? 0);
            $dInfo = $this->interpretWmoCode($dWmo, true);
            $dDate = $dailyTimes[$i];
            $dTimestamp = strtotime($dDate);

            $dayLabel = match ($i) {
                0 => 'Today',
                1 => 'Tomorrow',
                default => date('D', $dTimestamp),
            };

            $dailyList[] = [
                'date' => $dDate,
                'day_label' => $dayLabel,
                'full_day' => date('l, d M', $dTimestamp),
                'weather_code' => $dWmo,
                'condition' => $dInfo['condition'],
                'icon' => $dInfo['icon'],
                'temp_max' => round((float) ($daily['temperature_2m_max'][$i] ?? 0)),
                'temp_min' => round((float) ($daily['temperature_2m_min'][$i] ?? 0)),
                'apparent_temp_max' => round((float) ($daily['apparent_temperature_max'][$i] ?? 0)),
                'apparent_temp_min' => round((float) ($daily['apparent_temperature_min'][$i] ?? 0)),
                'precipitation_probability' => (int) ($daily['precipitation_probability_max'][$i] ?? 0),
                'precipitation_sum' => (float) ($daily['precipitation_sum'][$i] ?? 0),
                'uv_index_max' => round((float) ($daily['uv_index_max'][$i] ?? 0), 1),
                'wind_speed_max' => round((float) ($daily['wind_speed_10m_max'][$i] ?? 0), 1),
                'sunrise' => isset($daily['sunrise'][$i]) ? date('H:i', strtotime($daily['sunrise'][$i])) : '--:--',
                'sunset' => isset($daily['sunset'][$i]) ? date('H:i', strtotime($daily['sunset'][$i])) : '--:--',
            ];
        }

        $windDeg = (int) ($current['wind_direction_10m'] ?? 0);
        $uvVal = (float) ($current['uv_index'] ?? 0);
        $visibilityM = (float) ($current['visibility'] ?? 10000);
        $visibilityKm = round($visibilityM / 1000, 1);

        return [
            'location' => [
                'city' => $cityName ?? 'Bandung',
                'country' => $country ?? 'Indonesia',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'elevation' => $elevation,
                'timezone' => $timezone,
            ],
            'current' => [
                'temperature' => round((float) ($current['temperature_2m'] ?? 0)),
                'feels_like' => round((float) ($current['apparent_temperature'] ?? 0)),
                'weather_code' => $wmoCode,
                'condition' => $weatherInfo['condition'],
                'description' => $weatherInfo['description'],
                'icon' => $weatherInfo['icon'],
                'background_theme' => $weatherInfo['theme'],
                'is_day' => $isDay,
                'time' => $current['time'] ?? date('Y-m-d\TH:i'),
                'display_time' => date('H:i', strtotime($current['time'] ?? 'now')),
                'humidity' => (int) ($current['relative_humidity_2m'] ?? 0),
                'humidity_status' => $this->getHumidityStatus((int) ($current['relative_humidity_2m'] ?? 0)),
                'wind_speed' => round((float) ($current['wind_speed_10m'] ?? 0), 1),
                'wind_direction_deg' => $windDeg,
                'wind_direction_cardinal' => $this->degreesToCardinal($windDeg),
                'pressure' => round((float) ($current['surface_pressure'] ?? 1013.25)),
                'pressure_status' => $this->getPressureStatus((float) ($current['surface_pressure'] ?? 1013.25)),
                'visibility_km' => $visibilityKm,
                'visibility_status' => $this->getVisibilityStatus($visibilityKm),
                'uv_index' => round($uvVal, 1),
                'uv_level' => $this->getUvLevel($uvVal),
                'dew_point' => round((float) ($current['dew_point_2m'] ?? 0)),
                'precipitation' => (float) ($current['precipitation'] ?? 0),
                'temp_max' => $dailyList[0]['temp_max'] ?? round((float) ($current['temperature_2m'] ?? 0)),
                'temp_min' => $dailyList[0]['temp_min'] ?? round((float) ($current['temperature_2m'] ?? 0)),
            ],
            'sun' => [
                'sunrise' => $dailyList[0]['sunrise'] ?? '05:45',
                'sunset' => $dailyList[0]['sunset'] ?? '17:55',
                'raw_sunrise' => $sunriseTime,
                'raw_sunset' => $sunsetTime,
                'daylight_duration' => $this->calculateDaylightDuration($sunriseTime, $sunsetTime),
                'progress_percent' => $sunProgress['percent'],
                'is_sun_up' => $sunProgress['is_up'],
                'status_text' => $sunProgress['status_text'],
            ],
            'hourly' => $hourlyList,
            'daily' => $dailyList,
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'provider' => 'Meta-CahayaMedia',
                'cached_ttl_min' => $this->cacheTtlMinutes,
            ],
        ];
    }

    /**
     * Map WMO codes to human readable metadata, icons and background themes
     */
    public function interpretWmoCode(int $code, bool $isDay = true): array
    {
        return match ($code) {
            0 => [
                'condition' => 'Clear Sky',
                'description' => $isDay ? 'Clear sunny skies with bright conditions' : 'Clear starry night with optimal visibility',
                'icon' => $isDay ? 'sun' : 'moon',
                'theme' => $isDay ? 'sunny' : 'clear-night',
            ],
            1 => [
                'condition' => 'Mainly Clear',
                'description' => $isDay ? 'Mostly sunny with occasional light breeze' : 'Mostly clear night sky',
                'icon' => $isDay ? 'sun-cloud' : 'moon-cloud',
                'theme' => $isDay ? 'sunny' : 'clear-night',
            ],
            2 => [
                'condition' => 'Partly Cloudy',
                'description' => 'Scattered clouds offering pleasant shade',
                'icon' => $isDay ? 'cloud-sun' : 'cloud-moon',
                'theme' => $isDay ? 'partly-cloudy' : 'partly-cloudy-night',
            ],
            3 => [
                'condition' => 'Overcast',
                'description' => 'Dense cloud cover spanning across the horizon',
                'icon' => 'cloud',
                'theme' => 'cloudy',
            ],
            45, 48 => [
                'condition' => 'Foggy',
                'description' => 'Low visibility due to persistent dense fog',
                'icon' => 'cloud-fog',
                'theme' => 'foggy',
            ],
            51, 53, 55 => [
                'condition' => 'Drizzle',
                'description' => 'Gentle light drizzle falling periodically',
                'icon' => 'cloud-drizzle',
                'theme' => 'rainy',
            ],
            56, 57 => [
                'condition' => 'Freezing Drizzle',
                'description' => 'Freezing drizzle with icy ground conditions',
                'icon' => 'cloud-snow',
                'theme' => 'snowy',
            ],
            61, 63 => [
                'condition' => 'Moderate Rain',
                'description' => 'Steady rainfall throughout the area',
                'icon' => 'cloud-rain',
                'theme' => 'rainy',
            ],
            65 => [
                'condition' => 'Heavy Rain',
                'description' => 'Intense downpour with potential road runoff',
                'icon' => 'cloud-heavy-rain',
                'theme' => 'rainy',
            ],
            66, 67 => [
                'condition' => 'Freezing Rain',
                'description' => 'Freezing rain creating hazardous slippery conditions',
                'icon' => 'cloud-snow',
                'theme' => 'snowy',
            ],
            71, 73, 75, 77 => [
                'condition' => 'Snowfall',
                'description' => 'Crisp snowflakes blanketing the terrain',
                'icon' => 'snowflake',
                'theme' => 'snowy',
            ],
            80, 81, 82 => [
                'condition' => 'Rain Showers',
                'description' => 'Passing convective rain showers',
                'icon' => 'cloud-rain',
                'theme' => 'rainy',
            ],
            85, 86 => [
                'condition' => 'Snow Showers',
                'description' => 'Gusty snow flurries and intermittent accumulation',
                'icon' => 'cloud-snow',
                'theme' => 'snowy',
            ],
            95 => [
                'condition' => 'Thunderstorm',
                'description' => 'Active lightning, thunder and gusty squalls',
                'icon' => 'cloud-lightning',
                'theme' => 'thunderstorm',
            ],
            96, 99 => [
                'condition' => 'Thunderstorm & Hail',
                'description' => 'Severe electrical storm accompanied by hail stones',
                'icon' => 'cloud-lightning',
                'theme' => 'thunderstorm',
            ],
            default => [
                'condition' => 'Variable',
                'description' => 'Typical seasonal atmospheric conditions',
                'icon' => $isDay ? 'sun-cloud' : 'cloud',
                'theme' => 'partly-cloudy',
            ],
        };
    }

    protected function degreesToCardinal(int $deg): string
    {
        $cardinals = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];
        $idx = (int) round(($deg % 360) / 22.5);
        return $cardinals[$idx % 16];
    }

    protected function getUvLevel(float $uv): array
    {
        if ($uv <= 2.9) {
            return ['level' => 'Low', 'advice' => 'Minimal sun protection required.', 'color' => 'emerald'];
        }
        if ($uv <= 5.9) {
            return ['level' => 'Moderate', 'advice' => 'Wear sunscreen and seek shade midday.', 'color' => 'amber'];
        }
        if ($uv <= 7.9) {
            return ['level' => 'High', 'advice' => 'Cover up, sunglasses & SPF 30+ recommended.', 'color' => 'orange'];
        }
        if ($uv <= 10.9) {
            return ['level' => 'Very High', 'advice' => 'Extra protection essential. Avoid midday sun.', 'color' => 'red'];
        }
        return ['level' => 'Extreme', 'advice' => 'Stay indoors during peak sunlight hours.', 'color' => 'purple'];
    }

    protected function getHumidityStatus(int $humidity): string
    {
        if ($humidity < 30) return 'Dry';
        if ($humidity <= 60) return 'Optimal Comfort';
        if ($humidity <= 80) return 'Humid';
        return 'Very Humid';
    }

    protected function getPressureStatus(float $pressure): string
    {
        if ($pressure < 1000) return 'Low (Stormy)';
        if ($pressure <= 1020) return 'Normal / Balanced';
        return 'High (Stable)';
    }

    protected function getVisibilityStatus(float $km): string
    {
        if ($km >= 10) return 'Excellent (Crystal Clear)';
        if ($km >= 5) return 'Good Visibility';
        if ($km >= 2) return 'Moderate (Hazy)';
        return 'Poor (Fog / Mist)';
    }

    protected function calculateSunProgress(?string $sunrise, ?string $sunset, string $currentTime, string $timezone): array
    {
        if (!$sunrise || !$sunset) {
            return ['percent' => 50, 'is_up' => true, 'status_text' => 'Sun in transit'];
        }

        $riseTs = strtotime($sunrise);
        $setTs = strtotime($sunset);
        $nowTs = strtotime($currentTime);

        if ($nowTs < $riseTs) {
            $minsUntil = round(($riseTs - $nowTs) / 60);
            return [
                'percent' => 0,
                'is_up' => false,
                'status_text' => sprintf('Sunrise in %s', $this->formatMinutes($minsUntil)),
            ];
        }

        if ($nowTs > $setTs) {
            $minsSince = round(($nowTs - $setTs) / 60);
            return [
                'percent' => 100,
                'is_up' => false,
                'status_text' => sprintf('Sunset was %s ago', $this->formatMinutes($minsSince)),
            ];
        }

        $totalSpan = $setTs - $riseTs;
        $elapsed = $nowTs - $riseTs;
        $pct = $totalSpan > 0 ? min(100, max(0, round(($elapsed / $totalSpan) * 100))) : 50;
        $minsUntilSet = round(($setTs - $nowTs) / 60);

        return [
            'percent' => $pct,
            'is_up' => true,
            'status_text' => sprintf('Sunset in %s', $this->formatMinutes($minsUntilSet)),
        ];
    }

    protected function calculateDaylightDuration(?string $sunrise, ?string $sunset): string
    {
        if (!$sunrise || !$sunset) return '12h 00m';
        $diff = strtotime($sunset) - strtotime($sunrise);
        if ($diff <= 0) return '12h 00m';
        $hours = floor($diff / 3600);
        $mins = floor(($diff % 3600) / 60);
        return sprintf('%dh %02dm', $hours, $mins);
    }

    protected function formatMinutes(int $minutes): string
    {
        if ($minutes < 60) {
            return "{$minutes}m";
        }
        $h = floor($minutes / 60);
        $m = $minutes % 60;
        return $m > 0 ? "{$h}h {$m}m" : "{$h}h";
    }

    protected function getCountryFlagEmoji(string $countryCode): string
    {
        $code = strtoupper(trim($countryCode));
        if (strlen($code) !== 2) {
            return '🌐';
        }
        // Regional indicator symbol formula
        $r1 = 127397 + ord($code[0]);
        $r2 = 127397 + ord($code[1]);
        return mb_chr($r1, 'UTF-8') . mb_chr($r2, 'UTF-8');
    }

    protected function logRequest(?string $city, ?float $latitude, ?float $longitude, int $status): void
    {
        try {
            WeatherLog::create([
                'city' => $city,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'response_status' => $status,
                'requested_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning("Could not log weather request: " . $e->getMessage());
        }
    }
}
