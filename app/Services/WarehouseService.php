<?php

namespace App\Services;

use App\Models\Warehouse;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Collection;

class WarehouseService
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }
    /**
     * Get all warehouses.
     */
    public function getWarehouses(): Collection
    {
        return Warehouse::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'code',
                'area',
                'address',
                'status',
            ]);
    }

    /**
     * Create a new warehouse.
     */
    public function create(
        User $user,
        array $data
    ): Warehouse {
        $warehouse = Warehouse::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'area' => $data['area'],
            'address' => $data['address'],
            'status' => $data['status'] ?? 'active',
        ]);

        $this->activityLogService->log(
            user: $user,
            action: 'created',
            module: 'warehouse',
            description:
            'Created warehouse ' .
            $warehouse->name .
            '.',
            subject: $warehouse,
            newValues: $warehouse->only([
                'name',
                'code',
                'area',
                'address',
                'status',
            ]),
        );

        return $warehouse;
    }

    /**
     * Update an existing warehouse.
     */
    public function update(
        User $user,
        Warehouse $warehouse,
        array $data
    ): Warehouse {
        $oldValues = $warehouse->only([
            'name',
            'code',
            'area',
            'address',
            'status',
        ]);

        $warehouse->update([
            'name' => $data['name'],
            'code' => $data['code'],
            'area' => $data['area'],
            'address' => $data['address'],
            'status' => $data['status'] ?? $warehouse->status,
        ]);

        $warehouse = $warehouse->fresh();

        $this->activityLogService->log(
            user: $user,
            action: 'updated',
            module: 'warehouse',
            description:
            'Updated warehouse ' .
            $warehouse->name .
            '.',
            subject: $warehouse,
            oldValues: $oldValues,
            newValues: $warehouse->only([
                'name',
                'code',
                'area',
                'address',
                'status',
            ]),
        );

        return $warehouse;
    }

    /**
     * Toggle an existing warehouse status.
     */
    public function toggleStatus(
        User $user,
        Warehouse $warehouse
    ): Warehouse {
        $oldStatus = $warehouse->status;

        $warehouse->status =
            $oldStatus === 'active'
            ? 'inactive'
            : 'active';

        $warehouse->save();

        $this->activityLogService->log(
            user: $user,
            action: 'status_changed',
            module: 'warehouse',
            description:
            'Changed warehouse ' .
            $warehouse->name .
            ' status from ' .
            $oldStatus .
            ' to ' .
            $warehouse->status .
            '.',
            subject: $warehouse,
            oldValues: [
                'status' => $oldStatus,
            ],
            newValues: [
                'status' => $warehouse->status,
            ],
        );

        return $warehouse;
    }

    /**
     * Delete an existing warehouse.
     */
    public function delete(
        User $user,
        Warehouse $warehouse
    ): void {
        $this->activityLogService->log(
            user: $user,
            action: 'deleted',
            module: 'warehouse',
            description:
            'Deleted warehouse ' .
            $warehouse->name .
            '.',
            subject: $warehouse,
            oldValues: $warehouse->only([
                'name',
                'code',
                'area',
                'address',
                'status',
            ]),
        );

        $warehouse->delete();
    }
}