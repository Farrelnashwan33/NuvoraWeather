<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DisasterService;
use App\Services\EarthquakeService;
use App\Services\FloodService;
use App\Services\TrafficService;
use App\Services\CctvService;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class MonitoringController extends Controller
{
    /**
     * Integrated Indonesia Monitoring Overview Telemetry
     */
    public function overview(
        WeatherService $weatherService,
        TrafficService $trafficService,
        CctvService $cctvService,
        EarthquakeService $earthquakeService,
        FloodService $floodService
    ): JsonResponse {
        $data = Cache::remember('monitoring_hub_overview_v1', now()->addMinutes(2), function () use ($weatherService, $trafficService, $cctvService, $earthquakeService, $floodService) {
            $latestEq = $earthquakeService->getLatestEarthquake();
            $floods = $floodService->getMonitoringStations();
            $cctvs = $cctvService->getCameras(limit: 30);
            $traffic = $trafficService->getTrafficData();

            $floodWarningCount = 0;
            foreach ($floods as $f) {
                if (in_array($f['status'], ['SIAGA', 'BAHAYA', 'WASPADA'])) {
                    $floodWarningCount++;
                }
            }

            return [
                'weather' => [
                    'title' => 'Cuaca Nasional',
                    'active_stations' => 38,
                    'status' => 'Normal Berawan',
                    'updated_at' => now()->toIso8601String(),
                ],
                'traffic' => [
                    'title' => 'Lalu Lintas Nasional',
                    'monitored_segments' => $traffic['total'] ?? 14,
                    'status' => 'Terpantau Lancar - Padat Terkendali',
                    'rush_hour_active' => ((int) now()->format('H') >= 16 && (int) now()->format('H') <= 19),
                ],
                'cctv' => [
                    'title' => 'CCTV Lalu Lintas',
                    'total_online' => count(array_filter($cctvs['cameras'] ?? [], fn($c) => $c['status'] === 'online')),
                    'sources_count' => 7,
                    'status' => 'Feed Aktif',
                ],
                'disaster' => [
                    'title' => 'Bencana & Alam',
                    'latest_earthquake' => $latestEq ? [
                        'magnitude' => $latestEq['magnitude'],
                        'wilayah' => $latestEq['wilayah'],
                        'tanggal' => $latestEq['tanggal'],
                        'jam' => $latestEq['jam'],
                        'status_label' => $latestEq['status_label'],
                    ] : null,
                    'flood_alerts' => $floodWarningCount,
                    'status' => $floodWarningCount > 0 ? 'Peringatan Siaga Banjir' : 'Kondisi Sungai Normal',
                ],
                'updated_at' => now()->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
