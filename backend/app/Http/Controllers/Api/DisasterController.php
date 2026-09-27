<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DisasterService;
use App\Services\EarthquakeService;
use App\Services\FloodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisasterController extends Controller
{
    protected EarthquakeService $earthquakeService;
    protected FloodService $floodService;
    protected DisasterService $disasterService;

    public function __construct(
        EarthquakeService $earthquakeService,
        FloodService $floodService,
        DisasterService $disasterService
    ) {
        $this->earthquakeService = $earthquakeService;
        $this->floodService = $floodService;
        $this->disasterService = $disasterService;
    }

    /**
     * Get latest earthquake from BMKG
     */
    public function latestEarthquake(Request $request): JsonResponse
    {
        $lat = $request->input('latitude', $request->input('lat'));
        $lon = $request->input('longitude', $request->input('lon'));

        $userLat = $lat !== null ? (float) $lat : null;
        $userLon = $lon !== null ? (float) $lon : null;

        $earthquake = $this->earthquakeService->getLatestEarthquake($userLat, $userLon);

        if (!$earthquake) {
            return response()->json([
                'success' => false,
                'message' => 'Data gempa BMKG sementara tidak tersedia.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'data' => $earthquake,
            'source' => 'BMKG (Badan Meteorologi, Klimatologi, dan Geofisika)',
            'last_updated' => date('d M Y H:i:s') . ' WIB',
        ]);
    }

    /**
     * Get list of recent earthquakes with optional filters
     */
    public function earthquakes(Request $request): JsonResponse
    {
        $lat = $request->input('latitude', $request->input('lat'));
        $lon = $request->input('longitude', $request->input('lon'));
        $filter = $request->input('filter'); // 'm3' | 'm4' | 'm5' | 'felt'

        $userLat = $lat !== null ? (float) $lat : null;
        $userLon = $lon !== null ? (float) $lon : null;

        $list = $this->earthquakeService->getRecentEarthquakes($userLat, $userLon, $filter);

        return response()->json([
            'success' => true,
            'count' => count($list),
            'data' => $list,
            'source' => 'BMKG Open Data (TEWS)',
            'last_updated' => date('d M Y H:i:s') . ' WIB',
        ]);
    }

    /**
     * Get nearby earthquakes relative to user GPS
     */
    public function nearbyEarthquakes(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $lat = (float) $request->input('latitude');
        $lon = (float) $request->input('longitude');

        $latest = $this->earthquakeService->getLatestEarthquake($lat, $lon);
        $recent = $this->earthquakeService->getRecentEarthquakes($lat, $lon);

        return response()->json([
            'success' => true,
            'user_coordinates' => ['latitude' => $lat, 'longitude' => $lon],
            'latest' => $latest,
            'recent' => $recent,
            'source' => 'BMKG',
        ]);
    }

    /**
     * Get list of official flood monitoring stations
     */
    public function floods(Request $request): JsonResponse
    {
        $lat = $request->input('latitude', $request->input('lat'));
        $lon = $request->input('longitude', $request->input('lon'));
        $province = $request->input('province');

        $userLat = $lat !== null ? (float) $lat : null;
        $userLon = $lon !== null ? (float) $lon : null;

        $stations = $this->floodService->getMonitoringStations($userLat, $userLon, $province);

        return response()->json([
            'success' => true,
            'count' => count($stations),
            'data' => $stations,
            'source' => 'Balai Wilayah Sungai (BWS/BBWS) & Dinas Sumber Daya Air',
            'hazard_risk_source' => 'BNPB / InaRISK Portal',
            'last_updated' => date('d M Y H:i:s') . ' WIB',
        ]);
    }

    /**
     * Get flood stations nearest to user GPS
     */
    public function nearbyFloods(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $lat = (float) $request->input('latitude');
        $lon = (float) $request->input('longitude');

        $stations = $this->floodService->getMonitoringStations($lat, $lon);

        return response()->json([
            'success' => true,
            'user_coordinates' => ['latitude' => $lat, 'longitude' => $lon],
            'nearest_station' => $stations[0] ?? null,
            'stations' => $stations,
        ]);
    }

    /**
     * Disaster telemetry & health status
     */
    public function status(): JsonResponse
    {
        $status = $this->disasterService->getSystemStatus();
        return response()->json($status);
    }
}
