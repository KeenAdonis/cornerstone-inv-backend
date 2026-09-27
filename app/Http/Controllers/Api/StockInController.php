<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockIn\StoreStockInRequest;
use App\Services\StockInService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockInController extends Controller
{
    public function __construct(
        private StockInService $stockInService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $stockIns = $this->stockInService->getStockInsForUser(
            $request->user()
        );

        return response()->json([
            'success' => true,
            'data' => [
                'stock_ins' => $stockIns,
            ],
        ]);
    }

    public function store(StoreStockInRequest $request): JsonResponse
    {
        $stockIn = $this->stockInService->create(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Stock-in transaction created successfully.',
            'data' => [
                'stock_in' => $stockIn,
            ],
        ], 201);
    }
}