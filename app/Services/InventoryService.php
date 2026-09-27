<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }
    
    public function getInventoryForUser(
        User $user,
        array $filters = []
    ): Collection {
        $query = Inventory::query()
            ->with([
                'product.category',
                'warehouse',
                'branch',
            ]);

        if ($user->role === 'warehouse_coordinator') {
            $warehouseId =
                $filters['warehouse_id'] ?? null;

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
                ->latest()
                ->get();
        }

        if ($user->role === 'branch_coordinator') {
            $branchId =
                $filters['branch_id'] ?? null;

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
                ->latest()
                ->get();
        }

        if ($user->role === 'admin') {
            $this->applyAdminFilters(
                $query,
                $filters
            );
        }

        return $query
            ->latest()
            ->get();
    }

    public function updateStockLevels(
        User $user,
        Inventory $inventory,
        array $data
    ): Inventory {
        $this->authorizeInventoryAccess(
            $user,
            $inventory
        );

        $parLevel = (float) $data['par_level'];
        $reorderLevel = (float) $data['reorder_level'];

        if ($parLevel < 0) {
            throw ValidationException::withMessages([
                'par_level' =>
                    'The PAR level cannot be negative.',
            ]);
        }

        if ($reorderLevel < 0) {
            throw ValidationException::withMessages([
                'reorder_level' =>
                    'The reorder level cannot be negative.',
            ]);
        }

        if ($reorderLevel > $parLevel) {
            throw ValidationException::withMessages([
                'reorder_level' =>
                    'The reorder level cannot be greater than the PAR level.',
            ]);
        }

        $oldValues = [
            'par_level' => $inventory->par_level,
            'reorder_level' => $inventory->reorder_level,
        ];

        $inventory->update([
            'par_level' => $parLevel,
            'reorder_level' => $reorderLevel,
        ]);

        $inventory->load([
            'product.category',
            'warehouse',
            'branch',
        ]);

        $this->activityLogService->log(
            user: $user,
            action: 'updated',
            module: 'inventory',
            description:
            'Updated stock levels for ' .
            $inventory->product->name .
            '.',
            branch: $inventory->branch,
            warehouse: $inventory->warehouse,
            subject: $inventory,
            oldValues: $oldValues,
            newValues: [
                'par_level' => $inventory->par_level,
                'reorder_level' => $inventory->reorder_level,
            ],
        );

        return $inventory;
    }

    private function applyAdminFilters(
        $query,
        array $filters
    ): void {
        $locationType =
            $filters['location_type'] ?? null;

        if ($locationType === 'branch') {
            $query->whereNotNull('branch_id');

            if (
                !empty($filters['area'])
            ) {
                $query->whereHas(
                    'branch',
                    function ($branchQuery) use ($filters) {
                        $branchQuery->where(
                            'area',
                            $filters['area']
                        );
                    }
                );
            }

            if (
                !empty($filters['branch_id'])
            ) {
                $query->where(
                    'branch_id',
                    $filters['branch_id']
                );
            }

            return;
        }

        if ($locationType === 'warehouse') {
            $query->whereNotNull('warehouse_id');

            if (
                !empty($filters['warehouse_id'])
            ) {
                $query->where(
                    'warehouse_id',
                    $filters['warehouse_id']
                );
            }

            return;
        }
    }

    private function authorizeInventoryAccess(
        User $user,
        Inventory $inventory
    ): void {
        if ($user->role === 'admin') {
            return;
        }

        if (
            $user->role ===
            'warehouse_coordinator'
        ) {
            if (
                $inventory->warehouse_id !== null &&
                $this->userHasWarehouseAssignment(
                    $user,
                    $inventory->warehouse_id
                )
            ) {
                return;
            }
        }

        if (
            $user->role ===
            'branch_coordinator'
        ) {
            if (
                $inventory->branch_id !== null &&
                $this->userHasBranchAssignment(
                    $user,
                    $inventory->branch_id
                )
            ) {
                return;
            }
        }

        throw ValidationException::withMessages([
            'inventory' =>
                'You are not authorized to update this inventory record.',
        ]);
    }

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

        /*
         * Backward compatibility for existing
         * users that have not yet been migrated
         * to the assignment table.
         */
        return $user->branch_id === $branchId;
    }

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

        /*
         * Backward compatibility for existing
         * users that have not yet been migrated
         * to the assignment table.
         */
        return $user->warehouse_id ===
            $warehouseId;
    }

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
}