<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CctvService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CctvController extends Controller
{
    protected CctvService $cctvService;

    public function __construct(CctvService $cctvService)
    {
        $this->cctvService = $cctvService;
    }

    /**
     * Get CCTV cameras by viewport bounding box or filter
     */
    public function index(Request $request): JsonResponse
    {
        $north = $request->filled('north') ? (float) $request->query('north') : null;
        $south = $request->filled('south') ? (float) $request->query('south') : null;
        $east = $request->filled('east') ? (float) $request->query('east') : null;
        $west = $request->filled('west') ? (float) $request->query('west') : null;
        $region = $request->query('region');
        $province = $request->query('province');
        $status = $request->query('status');
        $limit = min(120, max(10, (int) $request->query('limit', 80)));

        $data = $this->cctvService->getCameras($north, $south, $east, $west, $region, $province, $status, $limit);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Search CCTV cameras
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->query('q', '');
        $results = $this->cctvService->searchCameras($query);

        return response()->json([
            'success' => true,
            'query' => $query,
            'results' => $results,
        ]);
    }

    /**
     * Get camera detail
     */
    public function show(string|int $id): JsonResponse
    {
        $camera = $this->cctvService->getCameraDetail($id);

        if (!$camera) {
            return response()->json([
                'success' => false,
                'message' => 'Kamera CCTV tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $camera,
        ]);
    }

    /**
     * Get registered CCTV official sources
     */
    public function sources(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'sources' => $this->cctvService->getSources(),
        ]);
    }
}
