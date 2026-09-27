<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardAnalyticsService;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardAnalyticsController extends Controller
{
    public function __construct(
        private DashboardAnalyticsService $dashboardAnalyticsService
    ) {
    }

    /**
     * Get product demand ranking based on
     * completed purchase orders.
     */
    public function productDemand(
        Request $request
    ): JsonResponse {
        $limit = min(
            max(
                (int) $request->query(
                    'limit',
                    10
                ),
                1
            ),
            50
        );

        $products =
            $this->dashboardAnalyticsService
                ->getProductDemandRanking(
                    $limit
                );

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products,
            ],
        ]);
    }
}