<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockAdjustment\StoreStockAdjustmentRequest;
use App\Services\StockAdjustmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockAdjustmentController extends Controller
{
    public function __construct(
        private StockAdjustmentService $stockAdjustmentService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $stockAdjustments =
            $this->stockAdjustmentService
                ->getStockAdjustmentsForUser(
                    $request->user(),
                    $request->only([
                        'branch_id',
                        'warehouse_id',
                    ])
                );

        return response()->json([
            'success' => true,
            'data' => [
                'stock_adjustments' =>
                    $stockAdjustments,
            ],
        ]);
    }

    public function store(
        StoreStockAdjustmentRequest $request
    ): JsonResponse {
        $stockAdjustment =
            $this->stockAdjustmentService->create(
                $request->user(),
                $request->validated()
            );

        return response()->json([
            'success' => true,
            'message' =>
                'Stock adjustment created successfully.',
            'data' => [
                'stock_adjustment' =>
                    $stockAdjustment,
            ],
        ], 201);
    }
}