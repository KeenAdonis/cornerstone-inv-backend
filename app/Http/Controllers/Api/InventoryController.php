<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\UpdateInventoryLevelsRequest;
use App\Models\Inventory;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $inventory =
            $this->inventoryService->getInventoryForUser(
                $request->user(),
                $request->only([
                    'location_type',
                    'area',
                    'branch_id',
                    'warehouse_id',
                ])
            );

        return response()->json([
            'success' => true,
            'data' => [
                'inventory' => $inventory,
            ],
        ]);
    }

    public function updateStockLevels(
        UpdateInventoryLevelsRequest $request,
        Inventory $inventory
    ): JsonResponse {
        $updatedInventory =
            $this->inventoryService->updateStockLevels(
                $request->user(),
                $inventory,
                $request->validated()
            );

        return response()->json([
            'success' => true,
            'message' =>
                'Inventory stock levels updated successfully.',
            'data' => [
                'inventory' => $updatedInventory,
            ],
        ]);
    }
}