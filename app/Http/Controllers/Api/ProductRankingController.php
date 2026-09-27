<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductRankingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductRankingController extends Controller
{
    public function __construct(
        private ProductRankingService $productRankingService
    ) {
    }

    public function index(
        Request $request
    ): JsonResponse {
        $ranking =
            $this->productRankingService->getRanking(
                $request->only([
                    'branch_id',
                    'warehouse_id',
                    'date_from',
                    'date_to',
                ])
            );

        return response()->json([
            'success' => true,

            'data' => [
                'product_rankings' =>
                    $ranking,
            ],
        ]);
    }
}