<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FeaturedCity;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;

class CityController extends Controller
{
    protected WeatherService $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    /**
     * Get active featured cities with lightweight preview weather
     */
    public function featured(): JsonResponse
    {
        $cities = FeaturedCity::where('is_active', true)->get();

        $enriched = $cities->map(function ($city) {
            try {
                $weather = $this->weatherService->getWeather($city->latitude, $city->longitude, $city->city_name, $city->country);
                return [
                    'id' => $city->id,
                    'city_name' => $city->city_name,
                    'country' => $city->country,
                    'latitude' => $city->latitude,
                    'longitude' => $city->longitude,
                    'temperature' => $weather['current']['temperature'] ?? null,
                    'condition' => $weather['current']['condition'] ?? null,
                    'icon' => $weather['current']['icon'] ?? 'sun',
                    'humidity' => $weather['current']['humidity'] ?? null,
                    'wind_speed' => $weather['current']['wind_speed'] ?? null,
                    'is_day' => $weather['current']['is_day'] ?? true,
                ];
            } catch (\Exception $e) {
                return [
                    'id' => $city->id,
                    'city_name' => $city->city_name,
                    'country' => $city->country,
                    'latitude' => $city->latitude,
                    'longitude' => $city->longitude,
                    'temperature' => null,
                    'condition' => null,
                    'icon' => 'cloud',
                    'humidity' => null,
                    'wind_speed' => null,
                    'is_day' => true,
                ];
            }
        });

        return response()->json([
            'success' => true,
            'data' => $enriched,
        ]);
    }
}
