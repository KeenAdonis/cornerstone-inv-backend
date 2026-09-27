<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\User;

use App\Services\ActivityLogService;

use Illuminate\Database\Eloquent\Collection;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;

use Illuminate\Validation\ValidationException;

class StockAdjustmentService
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }
    /**
     * Get stock adjustment transactions accessible to the user.
     */
    public function getStockAdjustmentsForUser(
        User $user,
        array $filters = []
    ): Collection {
        $query = StockAdjustment::query()
            ->with([
                'warehouse',
                'branch',
                'creator',
                'items.product',
            ])
            ->latest();

        if (
            $user->role ===
            'warehouse_coordinator'
        ) {
            $warehouseId =
                $filters['warehouse_id'] ??
                null;

            if ($warehouseId !== null) {
                $this->authorizeWarehouseAssignment(
                    $user,
                    (int) $warehouseId
                );
            } else {
                $warehouseId =
                    $this->getDefaultWarehouseId(
                        $user
                    );
            }

            return $query
                ->where(
                    'warehouse_id',
                    $warehouseId
                )
                ->get();
        }

        if (
            $user->role ===
            'branch_coordinator'
        ) {
            $branchId =
                $filters['branch_id'] ??
                null;

            if ($branchId !== null) {
                $this->authorizeBranchAssignment(
                    $user,
                    (int) $branchId
                );
            } else {
                $branchId =
                    $this->getDefaultBranchId(
                        $user
                    );
            }

            return $query
                ->where(
                    'branch_id',
                    $branchId
                )
                ->get();
        }

        if ($user->role === 'admin') {
            $this->applyAdminFilters(
                $query,
                $filters
            );

            return $query->get();
        }

        throw ValidationException::withMessages([
            'user' =>
                'You are not authorized to view stock adjustment transactions.',
        ]);
    }

    /**
     * Create a stock adjustment and update the user's assigned inventory.
     *
     * @param  array{
     *     type: string,
     *     reason: string,
     *     adjusted_at: string,
     *     notes?: string|null,
     *     items: array<int, array{
     *         product_id: int,
     *         quantity: numeric
     *     }>
     * } $data
     */
    /**
     * Create a stock adjustment and update the user's assigned inventory.
     *
     * @param  array{
     *     type: string,
     *     reason: string,
     *     adjusted_at: string,
     *     notes?: string|null,
     *     branch_id?: int|null,
     *     warehouse_id?: int|null,
     *     items: array<int, array{
     *         product_id: int,
     *         quantity: numeric
     *     }>
     * } $data
     */
    public function create(
        User $user,
        array $data
    ): StockAdjustment {
        if (
            !in_array(
                $user->role,
                [
                    'warehouse_coordinator',
                    'branch_coordinator',
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'user' =>
                    'Only warehouse and branch coordinators can create stock adjustments.',
            ]);
        }

        $warehouseId = null;
        $branchId = null;

        if (
            $user->role ===
            'warehouse_coordinator'
        ) {
            $warehouseId =
                $data['warehouse_id'] ??
                $this->getDefaultWarehouseId(
                    $user
                );

            $this->authorizeWarehouseAssignment(
                $user,
                (int) $warehouseId
            );
        }

        if (
            $user->role ===
            'branch_coordinator'
        ) {
            $branchId =
                $data['branch_id'] ??
                $this->getDefaultBranchId(
                    $user
                );

            $this->authorizeBranchAssignment(
                $user,
                (int) $branchId
            );
        }

        return DB::transaction(
            function () use ($user, $data, $warehouseId, $branchId) {
                $stockAdjustment =
                    StockAdjustment::create([
                        'warehouse_id' =>
                            $warehouseId,

                        'branch_id' =>
                            $branchId,

                        'reference_number' =>
                            $this->generateReferenceNumber(),

                        'type' =>
                            $data['type'],

                        'reason' =>
                            $data['reason'],

                        'adjusted_at' =>
                            $data['adjusted_at'],

                        'created_by' =>
                            $user->id,

                        'notes' =>
                            $data['notes'] ?? null,
                    ]);

                foreach (
                    $data['items']
                    as $item
                ) {
                    $quantity =
                        (float) $item['quantity'];

                    $stockAdjustment
                        ->items()
                        ->create([
                            'product_id' =>
                                $item['product_id'],

                            'quantity' =>
                                $quantity,
                        ]);

                    $inventoryQuery =
                        Inventory::query()
                            ->where(
                                'product_id',
                                $item['product_id']
                            );

                    if ($warehouseId !== null) {
                        $inventoryQuery->where(
                            'warehouse_id',
                            $warehouseId
                        );
                    }

                    if ($branchId !== null) {
                        $inventoryQuery->where(
                            'branch_id',
                            $branchId
                        );
                    }

                    $inventory =
                        $inventoryQuery
                            ->lockForUpdate()
                            ->first();

                    if (
                        $data['type'] ===
                        'increase'
                    ) {
                        if ($inventory) {
                            $inventory->increment(
                                'quantity',
                                $quantity
                            );
                        } else {
                            Inventory::create([
                                'product_id' =>
                                    $item['product_id'],

                                'warehouse_id' =>
                                    $warehouseId,

                                'branch_id' =>
                                    $branchId,

                                'quantity' =>
                                    $quantity,

                                'par_level' =>
                                    0,

                                'reorder_level' =>
                                    0,
                            ]);
                        }

                        StockMovement::create([
                            'product_id' =>
                                $item['product_id'],

                            'purchase_order_id' =>
                                null,

                            'movement_type' =>
                                'adjustment',

                            'quantity' =>
                                $quantity,

                            'from_warehouse_id' =>
                                null,

                            'from_branch_id' =>
                                null,

                            'to_warehouse_id' =>
                                $warehouseId,

                            'to_branch_id' =>
                                $branchId,

                            'moved_at' =>
                                $data['adjusted_at'],

                            'created_by' =>
                                $user->id,

                            'notes' =>
                                $data['notes'] ?? null,
                        ]);

                        continue;
                    }

                    if (!$inventory) {
                        throw ValidationException::withMessages([
                            'items' =>
                                'Cannot decrease stock because the inventory record does not exist.',
                        ]);
                    }

                    $currentQuantity =
                        (float) $inventory->quantity;

                    if (
                        $quantity >
                        $currentQuantity
                    ) {
                        throw ValidationException::withMessages([
                            'items' =>
                                'Cannot decrease stock below zero. The requested adjustment exceeds the available inventory.',
                        ]);
                    }

                    $inventory->decrement(
                        'quantity',
                        $quantity
                    );

                    StockMovement::create([
                        'product_id' =>
                            $item['product_id'],

                        'purchase_order_id' =>
                            null,

                        'movement_type' =>
                            'adjustment',

                        'quantity' =>
                            $quantity,

                        'from_warehouse_id' =>
                            $warehouseId,

                        'from_branch_id' =>
                            $branchId,

                        'to_warehouse_id' =>
                            null,

                        'to_branch_id' =>
                            null,

                        'moved_at' =>
                            $data['adjusted_at'],

                        'created_by' =>
                            $user->id,

                        'notes' =>
                            $data['notes'] ?? null,
                    ]);
                }

                $stockAdjustment->load([
                    'warehouse',
                    'branch',
                    'creator',
                    'items.product',
                ]);

                $this->activityLogService->log(
                    user: $user,
                    action: 'created',
                    module: 'stock_adjustment',
                    description:
                    'Created stock adjustment ' .
                    $stockAdjustment->reference_number .
                    '.',
                    branch:
                    $stockAdjustment->branch,
                    warehouse:
                    $stockAdjustment->warehouse,
                    subject:
                    $stockAdjustment,
                    newValues: [
                        'type' =>
                            $stockAdjustment->type,
                        'reason' =>
                            $stockAdjustment->reason,
                        'adjusted_at' =>
                            $stockAdjustment->adjusted_at,
                        'items' =>
                            $stockAdjustment->items
                                ->map(
                                    fn($item) => [
                                        'product_id' =>
                                            $item->product_id,
                                        'quantity' =>
                                            $item->quantity,
                                    ]
                                )
                                ->values()
                                ->all(),
                    ],
                );

                return $stockAdjustment;
            }
        );
    }

    /**
     * Apply inventory filters for administrators.
     */
    private function applyAdminFilters(
        $query,
        array $filters
    ): void {
        if (
            !empty($filters['branch_id'])
        ) {
            $query->where(
                'branch_id',
                $filters['branch_id']
            );
        }

        if (
            !empty($filters['warehouse_id'])
        ) {
            $query->where(
                'warehouse_id',
                $filters['warehouse_id']
            );
        }
    }

    /**
     * Verify that the user is assigned to the branch.
     */
    private function authorizeBranchAssignment(
        User $user,
        int $branchId
    ): void {
        if (
            $this->userHasBranchAssignment(
                $user,
                $branchId
            )
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'branch_id' =>
                'You are not authorized to access this branch.',
        ]);
    }

    /**
     * Verify that the user is assigned to the warehouse.
     */
    private function authorizeWarehouseAssignment(
        User $user,
        int $warehouseId
    ): void {
        if (
            $this->userHasWarehouseAssignment(
                $user,
                $warehouseId
            )
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'warehouse_id' =>
                'You are not authorized to access this warehouse.',
        ]);
    }

    /**
     * Check branch assignment with legacy compatibility.
     */
    private function userHasBranchAssignment(
        User $user,
        int $branchId
    ): bool {
        if (
            $user->assignedBranches()
                ->whereKey($branchId)
                ->exists()
        ) {
            return true;
        }

        return $user->branch_id ===
            $branchId;
    }

    /**
     * Check warehouse assignment with legacy compatibility.
     */
    private function userHasWarehouseAssignment(
        User $user,
        int $warehouseId
    ): bool {
        if (
            $user->assignedWarehouses()
                ->whereKey($warehouseId)
                ->exists()
        ) {
            return true;
        }

        return $user->warehouse_id ===
            $warehouseId;
    }

    /**
     * Get the user's default branch.
     */
    private function getDefaultBranchId(
        User $user
    ): int {
        if (
            $user->branch_id !== null &&
            $this->userHasBranchAssignment(
                $user,
                $user->branch_id
            )
        ) {
            return $user->branch_id;
        }

        $branchId =
            $user->assignedBranches()
                ->value('branches.id');

        if ($branchId !== null) {
            return (int) $branchId;
        }

        throw ValidationException::withMessages([
            'branch_id' =>
                'No branch assignment is available for this user.',
        ]);
    }

    /**
     * Get the user's default warehouse.
     */
    private function getDefaultWarehouseId(
        User $user
    ): int {
        if (
            $user->warehouse_id !== null &&
            $this->userHasWarehouseAssignment(
                $user,
                $user->warehouse_id
            )
        ) {
            return $user->warehouse_id;
        }

        $warehouseId =
            $user->assignedWarehouses()
                ->value('warehouses.id');

        if ($warehouseId !== null) {
            return (int) $warehouseId;
        }

        throw ValidationException::withMessages([
            'warehouse_id' =>
                'No warehouse assignment is available for this user.',
        ]);
    }

    /**
     * Generate a unique stock adjustment reference number.
     */
    private function generateReferenceNumber(): string
    {
        do {
            $referenceNumber =
                'ADJ-' .
                now()->format('YmdHis') .
                '-' .
                Str::upper(
                    Str::random(4)
                );
        } while (
            StockAdjustment::query()
                ->where(
                    'reference_number',
                    $referenceNumber
                )
                ->exists()
        );

        return $referenceNumber;
    }
}