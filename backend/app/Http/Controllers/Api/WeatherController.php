<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeatherController extends Controller
{
    protected WeatherService $weatherService;

    public function __construct(WeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    /**
     * Get complete current weather & forecast bundle
     */
    public function current(Request $request): JsonResponse
    {
        $lat = $request->input('lat', $request->input('latitude'));
        $lon = $request->input('lon', $request->input('longitude'));
        $city = $request->input('city');
        $country = $request->input('country');

        // Default to Bandung (-6.9175, 107.6191) if neither coordinate nor city provided
        if ($lat === null || $lon === null) {
            if ($city) {
                $search = $this->weatherService->searchCities($city);
                if (!empty($search)) {
                    $first = $search[0];
                    $lat = $first['latitude'];
                    $lon = $first['longitude'];
                    $city = $first['name'];
                    $country = $first['country'];
                } else {
                    $lat = -6.9175;
                    $lon = 107.6191;
                    $city = 'Bandung';
                    $country = 'Indonesia';
                }
            } else {
                $lat = -6.9175;
                $lon = 107.6191;
                $city = 'Bandung';
                $country = 'Indonesia';
            }
        }

        try {
            $data = $this->weatherService->getWeather((float) $lat, (float) $lon, $city, $country);
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch weather data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get hourly forecast
     */
    public function hourly(Request $request): JsonResponse
    {
        $lat = (float) $request->input('lat', -6.9175);
        $lon = (float) $request->input('lon', 107.6191);

        try {
            $data = $this->weatherService->getWeather($lat, $lon);
            return response()->json([
                'success' => true,
                'location' => $data['location'],
                'hourly' => $data['hourly'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get 7-day daily forecast
     */
    public function daily(Request $request): JsonResponse
    {
        $lat = (float) $request->input('lat', -6.9175);
        $lon = (float) $request->input('lon', 107.6191);

        try {
            $data = $this->weatherService->getWeather($lat, $lon);
            return response()->json([
                'success' => true,
                'location' => $data['location'],
                'daily' => $data['daily'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search city autocomplete
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', $request->input('city', ''));

        if (strlen(trim($query)) < 2) {
            return response()->json([
                'success' => true,
                'results' => [],
            ]);
        }

        $results = $this->weatherService->searchCities($query);

        return response()->json([
            'success' => true,
            'query' => $query,
            'count' => count($results),
            'results' => $results,
        ]);
    }

    /**
     * Get weather by user coordinate & reverse geocode
     */
    public function location(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $lat = (float) $request->input('latitude');
        $lon = (float) $request->input('longitude');

        $geo = $this->weatherService->reverseGeocode($lat, $lon);

        try {
            $data = $this->weatherService->getWeather($lat, $lon, $geo['city'], $geo['country']);
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of curated remote / outermost Indonesian regions
     */
    public function indonesiaRegions(): JsonResponse
    {
        $regions = \App\Services\IndonesiaRegions::getRemoteRegions();
        return response()->json([
            'success' => true,
            'count' => count($regions),
            'regions' => $regions,
        ]);
    }
}
