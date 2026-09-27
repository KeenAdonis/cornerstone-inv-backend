<?php

namespace App\Services;

use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }

    /**
     * Get all system users.
     */
    public function getUsers(): Collection
    {
        return User::query()
            ->with([
                'branch',
                'warehouse',
                'assignedBranches',
                'assignedWarehouses',
            ])
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'role',
                'branch_id',
                'warehouse_id',
                'status',
            ]);
    }

    /**
     * Create a new system user.
     */
    public function create(
        User $authenticatedUser,
        array $data
    ): User {
        return DB::transaction(function () use (
            $authenticatedUser,
            $data
        ) {
            $branchIds =
                $data['branch_ids']
                ?? (
                    isset($data['branch_id'])
                        ? [$data['branch_id']]
                        : []
                );

            $warehouseIds =
                $data['warehouse_ids']
                ?? (
                    isset($data['warehouse_id'])
                        ? [$data['warehouse_id']]
                        : []
                );

            $branchIds = array_values(
                array_unique(
                    array_filter($branchIds)
                )
            );

            $warehouseIds = array_values(
                array_unique(
                    array_filter($warehouseIds)
                )
            );

            $user = User::create([
                'name' =>
                    $data['name'],

                'email' =>
                    $data['email'],

                'password' =>
                    $data['password'],

                'role' =>
                    $data['role'],

                'branch_id' =>
                    $data['role'] ===
                        'branch_coordinator'
                        ? ($branchIds[0] ?? null)
                        : null,

                'warehouse_id' =>
                    $data['role'] ===
                        'warehouse_coordinator'
                        ? ($warehouseIds[0] ?? null)
                        : null,

                'status' =>
                    $data['status'] ?? 'active',
            ]);

            if (
                $data['role'] ===
                'branch_coordinator'
            ) {
                $user->assignedBranches()->sync(
                    $branchIds
                );
            }

            if (
                $data['role'] ===
                'warehouse_coordinator'
            ) {
                $user->assignedWarehouses()->sync(
                    $warehouseIds
                );
            }

            $user->load([
                'branch',
                'warehouse',
                'assignedBranches',
                'assignedWarehouses',
            ]);

            $this->activityLogService->log(
                user: $authenticatedUser,
                action: 'created',
                module: 'user',
                description:
                    'Created user ' .
                    $user->name .
                    '.',
                subject: $user,
                newValues: [
                    'name' =>
                        $user->name,

                    'email' =>
                        $user->email,

                    'role' =>
                        $user->role,

                    'status' =>
                        $user->status,

                    'branch_ids' =>
                        $branchIds,

                    'warehouse_ids' =>
                        $warehouseIds,
                ],
            );

            return $user;
        });
    }

    /**
     * Update an existing system user.
     */
    public function update(
        User $authenticatedUser,
        User $user,
        array $data
    ): User {
        return DB::transaction(function () use (
            $authenticatedUser,
            $user,
            $data
        ) {
            $oldValues = [
                'name' =>
                    $user->name,

                'email' =>
                    $user->email,

                'role' =>
                    $user->role,

                'status' =>
                    $user->status,

                'branch_ids' =>
                    $user->assignedBranches()
                        ->pluck('branches.id')
                        ->map(
                            fn ($id) => (int) $id
                        )
                        ->values()
                        ->all(),

                'warehouse_ids' =>
                    $user->assignedWarehouses()
                        ->pluck('warehouses.id')
                        ->map(
                            fn ($id) => (int) $id
                        )
                        ->values()
                        ->all(),
            ];

            $branchIds =
                $data['branch_ids']
                ?? (
                    isset($data['branch_id'])
                        ? [$data['branch_id']]
                        : []
                );

            $warehouseIds =
                $data['warehouse_ids']
                ?? (
                    isset($data['warehouse_id'])
                        ? [$data['warehouse_id']]
                        : []
                );

            $branchIds = array_values(
                array_unique(
                    array_filter($branchIds)
                )
            );

            $warehouseIds = array_values(
                array_unique(
                    array_filter($warehouseIds)
                )
            );

            $user->name =
                $data['name'];

            $user->email =
                $data['email'];

            $user->role =
                $data['role'];

            $user->branch_id =
                $data['role'] ===
                    'branch_coordinator'
                    ? ($branchIds[0] ?? null)
                    : null;

            $user->warehouse_id =
                $data['role'] ===
                    'warehouse_coordinator'
                    ? ($warehouseIds[0] ?? null)
                    : null;

            $user->status =
                $data['status'] ?? 'active';

            if (!empty($data['password'])) {
                $user->password =
                    $data['password'];
            }

            $user->save();

            if (
                $data['role'] ===
                'branch_coordinator'
            ) {
                $user->assignedBranches()->sync(
                    $branchIds
                );

                $user->assignedWarehouses()->detach();
            } else {
                $user->assignedBranches()->detach();
            }

            if (
                $data['role'] ===
                'warehouse_coordinator'
            ) {
                $user->assignedWarehouses()->sync(
                    $warehouseIds
                );
            } else {
                $user->assignedWarehouses()->detach();
            }

            $user->load([
                'branch',
                'warehouse',
                'assignedBranches',
                'assignedWarehouses',
            ]);

            $newValues = [
                'name' =>
                    $user->name,

                'email' =>
                    $user->email,

                'role' =>
                    $user->role,

                'status' =>
                    $user->status,

                'branch_ids' =>
                    $user->assignedBranches
                        ->pluck('id')
                        ->map(
                            fn ($id) => (int) $id
                        )
                        ->values()
                        ->all(),

                'warehouse_ids' =>
                    $user->assignedWarehouses
                        ->pluck('id')
                        ->map(
                            fn ($id) => (int) $id
                        )
                        ->values()
                        ->all(),
            ];

            $this->activityLogService->log(
                user: $authenticatedUser,
                action: 'updated',
                module: 'user',
                description:
                    'Updated user ' .
                    $user->name .
                    '.',
                subject: $user,
                oldValues: $oldValues,
                newValues: $newValues,
            );

            return $user;
        });
    }

    /**
     * Toggle a system user's active status.
     */
    public function toggleStatus(
        User $authenticatedUser,
        User $user
    ): User {
        $oldStatus =
            $user->status;

        $user->status =
            $oldStatus === 'active'
            ? 'inactive'
            : 'active';

        $user->save();

        $this->activityLogService->log(
            user: $authenticatedUser,
            action: 'status_changed',
            module: 'user',
            description:
                'Changed user ' .
                $user->name .
                ' status from ' .
                $oldStatus .
                ' to ' .
                $user->status .
                '.',
            subject: $user,
            oldValues: [
                'status' =>
                    $oldStatus,
            ],
            newValues: [
                'status' =>
                    $user->status,
            ],
        );

        return $user;
    }

    /**
     * Soft delete a system user.
     */
    public function delete(
        User $authenticatedUser,
        User $user
    ): void {
        if ($user->is($authenticatedUser)) {
            throw new \RuntimeException(
                'You cannot delete your own account.'
            );
        }

        $this->activityLogService->log(
            user: $authenticatedUser,
            action: 'deleted',
            module: 'user',
            description:
                'Deleted user ' .
                $user->name .
                '.',
            subject: $user,
            oldValues: [
                'name' =>
                    $user->name,

                'email' =>
                    $user->email,

                'role' =>
                    $user->role,

                'status' =>
                    $user->status,

                'branch_ids' =>
                    $user->assignedBranches()
                        ->pluck('branches.id')
                        ->map(
                            fn ($id) => (int) $id
                        )
                        ->values()
                        ->all(),

                'warehouse_ids' =>
                    $user->assignedWarehouses()
                        ->pluck('warehouses.id')
                        ->map(
                            fn ($id) => (int) $id
                        )
                        ->values()
                        ->all(),
            ],
        );

        $user->delete();
    }
}