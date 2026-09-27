<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StockMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function __construct(
        private StockMovementService $stockMovementService
    ) {
    }

    public function index(
        Request $request
    ): JsonResponse {
        $stockMovements =
            $this->stockMovementService
                ->getStockMovementsForUser(
                    $request->user(),
                    $request->only([
                        'branch_id',
                        'warehouse_id',
                    ])
                );

        return response()->json([
            'success' => true,
            'data' => [
                'stock_movements' =>
                    $stockMovements,
            ],
        ]);
    }
}