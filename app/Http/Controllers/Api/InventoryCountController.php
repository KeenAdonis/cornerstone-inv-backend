<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryCountRequest;
use App\Services\InventoryCountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryCountController extends Controller
{
    public function __construct(
        private InventoryCountService $inventoryCountService
    ) {
    }

    public function index(
        Request $request
    ): JsonResponse {
        $inventoryCounts =
            $this->inventoryCountService->getForUser(
                $request->user(),
                $request->integer('branch_id')
            );

        return response()->json([
            'success' => true,
            'data' => [
                'inventory_counts' =>
                    $inventoryCounts,
            ],
        ]);
    }

    public function store(
        StoreInventoryCountRequest $request
    ): JsonResponse {
        $inventoryCount =
            $this->inventoryCountService->create(
                $request->user(),
                (int) $request->validated('branch_id'),
                $request->validated('items'),
                $request->validated('notes')
            );

        return response()->json([
            'message' =>
                'Inventory count completed successfully.',

            'data' =>
                $inventoryCount,
        ], 201);
    }
}