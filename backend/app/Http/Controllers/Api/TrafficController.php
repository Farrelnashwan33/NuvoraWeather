<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TrafficService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrafficController extends Controller
{
    protected TrafficService $trafficService;

    public function __construct(TrafficService $trafficService)
    {
        $this->trafficService = $trafficService;
    }

    /**
     * Get traffic conditions by viewport or filter
     */
    public function index(Request $request): JsonResponse
    {
        $north = $request->filled('north') ? (float) $request->query('north') : null;
        $south = $request->filled('south') ? (float) $request->query('south') : null;
        $east = $request->filled('east') ? (float) $request->query('east') : null;
        $west = $request->filled('west') ? (float) $request->query('west') : null;
        $region = $request->query('region', 'all');
        $status = $request->query('status');

        $data = $this->trafficService->getTrafficData($north, $south, $east, $west, $region, $status);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Search traffic road or area
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->query('q', '');
        $results = $this->trafficService->searchTraffic($query);

        return response()->json([
            'success' => true,
            'query' => $query,
            'results' => $results,
        ]);
    }

    /**
     * Get specific area / road details
     */
    public function area(string $id): JsonResponse
    {
        $detail = $this->trafficService->getAreaTraffic($id);

        if (!$detail) {
            return response()->json([
                'success' => false,
                'message' => 'Data wilayah/jalan tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $detail,
        ]);
    }

    /**
     * Get region hierarchy for dropdowns
     */
    public function hierarchy(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'regions' => TrafficService::getRegionHierarchy(),
        ]);
    }
}
