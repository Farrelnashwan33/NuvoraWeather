<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\FeaturedCity;
use App\Models\WeatherLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    /**
     * Get admin overview metrics and charts
     */
    public function stats(): JsonResponse
    {
        $totalRequests = WeatherLog::count();
        $successfulRequests = WeatherLog::where('response_status', 200)->count();
        $errorRequests = WeatherLog::where('response_status', '!=', 200)->count();

        $successRate = $totalRequests > 0 ? round(($successfulRequests / $totalRequests) * 100, 1) : 100.0;

        $topCities = WeatherLog::select('city', DB::raw('count(*) as count'))
            ->whereNotNull('city')
            ->groupBy('city')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $recent24hRequests = WeatherLog::where('requested_at', '>=', now()->subHours(24))->count();

        $featuredCount = FeaturedCity::count();
        $activeFeaturedCount = FeaturedCity::where('is_active', true)->count();

        return response()->json([
            'success' => true,
            'stats' => [
                'total_requests' => $totalRequests,
                'requests_24h' => $recent24hRequests,
                'success_rate' => $successRate,
                'error_requests' => $errorRequests,
                'featured_cities_total' => $featuredCount,
                'featured_cities_active' => $activeFeaturedCount,
                'api_status' => 'operational',
                'api_provider' => 'Meta-CahayaMedia Engine v2',
            ],
            'top_cities' => $topCities,
        ]);
    }

    /**
     * Get paginated logs
     */
    public function logs(Request $request): JsonResponse
    {
        $limit = (int) $request->input('limit', 20);
        $logs = WeatherLog::orderByDesc('requested_at')->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Get all featured cities for admin management
     */
    public function featuredCities(): JsonResponse
    {
        $cities = FeaturedCity::orderBy('id', 'asc')->get();

        return response()->json([
            'success' => true,
            'cities' => $cities,
        ]);
    }

    /**
     * Create featured city
     */
    public function storeCity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'city_name' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'is_active' => 'boolean',
        ]);

        $city = FeaturedCity::create([
            'city_name' => $validated['city_name'],
            'country' => $validated['country'],
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Featured city added successfully',
            'city' => $city,
        ]);
    }

    /**
     * Update featured city
     */
    public function updateCity(Request $request, int $id): JsonResponse
    {
        $city = FeaturedCity::findOrFail($id);

        $validated = $request->validate([
            'city_name' => 'sometimes|required|string|max:100',
            'country' => 'sometimes|required|string|max:100',
            'latitude' => 'sometimes|required|numeric|between:-90,90',
            'longitude' => 'sometimes|required|numeric|between:-180,180',
            'is_active' => 'sometimes|boolean',
        ]);

        $city->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Featured city updated successfully',
            'city' => $city,
        ]);
    }

    /**
     * Delete featured city
     */
    public function deleteCity(int $id): JsonResponse
    {
        $city = FeaturedCity::findOrFail($id);
        $city->delete();

        return response()->json([
            'success' => true,
            'message' => 'Featured city removed successfully',
        ]);
    }

    /**
     * Get all app settings
     */
    public function getSettings(): JsonResponse
    {
        $settings = AppSetting::all()->pluck('value', 'key')->toArray();

        $defaults = [
            'app_name' => 'Nuvora Weather',
            'tagline' => 'Know Your Weather. Plan Your Day.',
            'cache_ttl_minutes' => '15',
            'default_city' => 'Bandung',
            'default_country' => 'Indonesia',
            'default_lat' => '-6.9175',
            'default_lon' => '107.6191',
            'system_notice' => '',
            'rate_limit_per_min' => '60',
        ];

        return response()->json([
            'success' => true,
            'settings' => array_merge($defaults, $settings),
        ]);
    }

    /**
     * Save app settings
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->all();

        foreach ($data as $key => $val) {
            if (is_string($key)) {
                AppSetting::setVal($key, is_array($val) ? json_encode($val) : (string) $val);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
        ]);
    }
}
