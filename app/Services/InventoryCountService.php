<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryCount;
use App\Models\User;

use App\Services\ActivityLogService;

use Illuminate\Support\Facades\DB;

use Illuminate\Validation\ValidationException;

class InventoryCountService
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }
    public function create(
        User $user,
        int $branchId,
        array $items,
        ?string $notes = null
    ): InventoryCount {
        $this->authorizeBranchAssignment(
            $user,
            $branchId
        );

        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' =>
                    'At least one inventory item is required.',
            ]);
        }

        return DB::transaction(
            function () use ($user, $branchId, $items, $notes) {
                $inventoryCount =
                    InventoryCount::create([
                        'branch_id' =>
                            $branchId,

                        'counted_by' =>
                            $user->id,

                        'counted_at' =>
                            now(),

                        'status' =>
                            'completed',

                        'notes' =>
                            $notes,
                    ]);

                foreach ($items as $item) {
                    $inventoryId =
                        (int) $item['inventory_id'];

                    $countedQuantity =
                        (float) $item['counted_quantity'];

                    if ($countedQuantity < 0) {
                        throw ValidationException::withMessages([
                            'items' =>
                                'Counted quantity cannot be negative.',
                        ]);
                    }

                    $inventory =
                        Inventory::query()
                            ->whereKey($inventoryId)
                            ->where(
                                'branch_id',
                                $branchId
                            )
                            ->lockForUpdate()
                            ->first();

                    if (!$inventory) {
                        throw ValidationException::withMessages([
                            'items' =>
                                'One or more inventory records do not belong to the selected branch.',
                        ]);
                    }

                    $systemQuantity =
                        (float) $inventory->quantity;

                    if ($countedQuantity > $systemQuantity) {
                        throw ValidationException::withMessages([
                            'items' =>
                                "Physical count for {$inventory->product->name} cannot be greater than the system quantity of {$systemQuantity}.",
                        ]);
                    }

                    $variance =
                        $countedQuantity -
                        $systemQuantity;

                    $inventoryCount
                        ->items()
                        ->create([
                            'inventory_id' =>
                                $inventory->id,

                            'product_id' =>
                                $inventory->product_id,

                            'system_quantity' =>
                                $systemQuantity,

                            'counted_quantity' =>
                                $countedQuantity,

                            'variance' =>
                                $variance,
                        ]);

                    $inventory->update([
                        'quantity' =>
                            $countedQuantity,
                    ]);
                }

                $inventoryCount->load([
                    'branch',
                    'countedBy',
                    'items.product',
                    'items.inventory',
                ]);

                $this->activityLogService->log(
                    user: $user,
                    action: 'created',
                    module: 'inventory_count',
                    description:
                    'Completed inventory count for ' .
                    $inventoryCount->branch->name .
                    '.',
                    branch:
                    $inventoryCount->branch,
                    subject:
                    $inventoryCount,
                    newValues: [
                        'counted_at' =>
                            $inventoryCount->counted_at,
                        'status' =>
                            $inventoryCount->status,
                        'items' =>
                            $inventoryCount->items
                                ->map(
                                    fn($item) => [
                                        'product_id' =>
                                            $item->product_id,
                                        'system_quantity' =>
                                            $item->system_quantity,
                                        'counted_quantity' =>
                                            $item->counted_quantity,
                                        'variance' =>
                                            $item->variance,
                                    ]
                                )
                                ->values()
                                ->all(),
                    ],
                );

                return $inventoryCount;
            }
        );
    }

    public function getForUser(
        User $user,
        ?int $branchId = null
    ) {
        if ($user->role === 'branch_coordinator') {
            if ($branchId === null) {
                $branchId =
                    $user->branch_id
                    ?? $user->assignedBranches()
                        ->value('branches.id');
            }

            if ($branchId === null) {
                throw ValidationException::withMessages([
                    'branch_id' =>
                        'No branch assignment is available for this user.',
                ]);
            }

            $this->authorizeBranchAssignment(
                $user,
                $branchId
            );
        }

        return InventoryCount::query()
            ->with([
                'branch',
                'countedBy',
                'items.product',
            ])
            ->when(
                $branchId !== null,
                function ($query) use ($branchId) {
                    $query->where(
                        'branch_id',
                        $branchId
                    );
                }
            )
            ->latest('counted_at')
            ->get();
    }

    private function authorizeBranchAssignment(
        User $user,
        int $branchId
    ): void {
        if ($user->role === 'admin') {
            return;
        }

        if (
            $user->role ===
            'branch_coordinator'
        ) {
            if (
                $user->assignedBranches()
                    ->whereKey($branchId)
                    ->exists()
            ) {
                return;
            }

            /*
             * Backward compatibility for existing
             * users that still use branch_id.
             */
            if (
                $user->branch_id ===
                $branchId
            ) {
                return;
            }
        }

        throw ValidationException::withMessages([
            'branch_id' =>
                'You are not authorized to perform an inventory count for this branch.',
        ]);
    }
}