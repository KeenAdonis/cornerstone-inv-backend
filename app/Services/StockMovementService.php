<?php

namespace App\Services;

use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class StockMovementService
{
    /**
     * Get stock movement transactions accessible to the user.
     */
    public function getStockMovementsForUser(
        User $user,
        array $filters = []
    ): Collection {
        $query = StockMovement::query()
            ->with([
                'product.category',
                'purchaseOrder',
                'fromWarehouse',
                'fromBranch',
                'toWarehouse',
                'toBranch',
                'creator',
            ]);

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

            $query->where(function ($query) use (
                $warehouseId
            ) {
                $query
                    ->where(
                        'from_warehouse_id',
                        $warehouseId
                    )
                    ->orWhere(
                        'to_warehouse_id',
                        $warehouseId
                    );
            });
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

            $query->where(function ($query) use (
                $branchId
            ) {
                $query
                    ->where(
                        'from_branch_id',
                        $branchId
                    )
                    ->orWhere(
                        'to_branch_id',
                        $branchId
                    );
            });
        }

        if ($user->role === 'admin') {
            $this->applyAdminFilters(
                $query,
                $filters
            );
        }

        if (
            !in_array(
                $user->role,
                [
                    'admin',
                    'warehouse_coordinator',
                    'branch_coordinator',
                ],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'user' =>
                    'You are not authorized to view stock movement transactions.',
            ]);
        }

        return $query
            ->latest('moved_at')
            ->get();
    }

    /**
     * Apply inventory location filters for administrators.
     */
    private function applyAdminFilters(
        $query,
        array $filters
    ): void {
        if (
            !empty($filters['branch_id'])
        ) {
            $query->where(function ($query) use (
                $filters
            ) {
                $query
                    ->where(
                        'from_branch_id',
                        $filters['branch_id']
                    )
                    ->orWhere(
                        'to_branch_id',
                        $filters['branch_id']
                    );
            });
        }

        if (
            !empty($filters['warehouse_id'])
        ) {
            $query->where(function ($query) use (
                $filters
            ) {
                $query
                    ->where(
                        'from_warehouse_id',
                        $filters['warehouse_id']
                    )
                    ->orWhere(
                        'to_warehouse_id',
                        $filters['warehouse_id']
                    );
            });
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
}