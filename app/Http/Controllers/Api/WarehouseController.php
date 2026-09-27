<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Http\Requests\Warehouse\StoreWarehouseRequest;
use App\Http\Requests\Warehouse\UpdateWarehouseRequest;

use App\Models\Warehouse;

use App\Services\WarehouseService;

use Illuminate\Http\JsonResponse;

class WarehouseController extends Controller
{
    public function __construct(
        private WarehouseService $warehouseService
    ) {
    }

    /**
     * Get all warehouses.
     */
    public function index(): JsonResponse
    {
        $warehouses = $this->warehouseService->getWarehouses();

        return response()->json([
            'success' => true,
            'data' => [
                'warehouses' => $warehouses,
            ],
        ]);
    }

    /**
     * Create a new warehouse.
     */
    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $warehouse = $this->warehouseService->create(
            auth()->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Warehouse created successfully.',
            'data' => [
                'warehouse' => $warehouse,
            ],
        ], 201);
    }

    /**
     * Update an existing warehouse.
     */
    public function update(
        UpdateWarehouseRequest $request,
        Warehouse $warehouse
    ): JsonResponse {
        $warehouse = $this->warehouseService->update(
            auth()->user(),
            $warehouse,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Warehouse updated successfully.',
            'data' => [
                'warehouse' => $warehouse,
            ],
        ]);
    }

    /**
     * Toggle an existing warehouse status.
     */
    public function toggleStatus(Warehouse $warehouse): JsonResponse
    {
        $warehouse = $this->warehouseService->toggleStatus(
            auth()->user(),
            $warehouse
        );

        return response()->json([
            'success' => true,
            'message' => 'Warehouse status updated successfully.',
            'data' => [
                'warehouse' => $warehouse,
            ],
        ]);
    }

    /**
     * Delete an existing warehouse.
     */
    public function destroy(Warehouse $warehouse): JsonResponse
    {
        $this->warehouseService->delete(
            auth()->user(),
            $warehouse
        );

        return response()->json([
            'success' => true,
            'message' => 'Warehouse deleted successfully.',
        ]);
    }
}