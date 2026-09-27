<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class DisasterService
{
    protected EarthquakeService $earthquakeService;
    protected FloodService $floodService;

    public function __construct(EarthquakeService $earthquakeService, FloodService $floodService)
    {
        $this->earthquakeService = $earthquakeService;
        $this->floodService = $floodService;
    }

    /**
     * Get disaster telemetry status for admin and monitoring
     */
    public function getSystemStatus(): array
    {
        $bmkgStatus = 'operational';
        $bmkgLatencyMs = 0;

        try {
            $t0 = microtime(true);
            $res = Http::timeout(3)->get('https://data.bmkg.go.id/DataMKG/TEWS/autogempa.json');
            $bmkgLatencyMs = round((microtime(true) - $t0) * 1000);
            $bmkgStatus = $res->successful() ? 'operational' : 'degraded';
        } catch (\Exception $e) {
            $bmkgStatus = 'offline';
        }

        $latestEq = $this->earthquakeService->getLatestEarthquake();
        $recentEqs = $this->earthquakeService->getRecentEarthquakes();
        $floodStations = $this->floodService->getMonitoringStations();

        $activeWarnings = 0;
        if ($latestEq && $latestEq['is_tsunami_potential']) $activeWarnings++;
        foreach ($floodStations as $fs) {
            if (in_array($fs['status'], ['BAHAYA', 'SIAGA'])) $activeWarnings++;
        }

        return [
            'success' => true,
            'bmkg_api' => [
                'status' => $bmkgStatus,
                'latency_ms' => $bmkgLatencyMs,
                'provider' => 'BMKG Open Data (TEWS)',
                'endpoint' => 'https://data.bmkg.go.id/DataMKG/TEWS/',
                'last_sync' => now()->toIso8601String(),
            ],
            'flood_sensors' => [
                'status' => 'operational',
                'stations_count' => count($floodStations),
                'provider' => 'BBWS / Dinas SDA / BPBD Open Telemetry',
                'last_sync' => now()->toIso8601String(),
            ],
            'summary' => [
                'latest_earthquake_mag' => $latestEq['magnitude'] ?? null,
                'latest_earthquake_location' => $latestEq['location'] ?? null,
                'recent_earthquakes_count' => count($recentEqs),
                'flood_stations_monitored' => count($floodStations),
                'active_critical_warnings' => $activeWarnings,
            ],
            'disclaimer' => 'Informasi pada halaman ini bersumber dari data pihak ketiga (BMKG, BNPB, BBWS, BPBD) dan dapat mengalami keterlambatan atau perubahan.',
        ];
    }
}
