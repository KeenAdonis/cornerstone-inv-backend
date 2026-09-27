<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Collection;

class BranchService
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }
    /**
     * Get all branches.
     */
    public function getBranches(): Collection
    {
        return Branch::query()
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
     * Create a new branch.
     */
    public function create(
        User $user,
        array $data
    ): Branch {
        $branch = Branch::create([
            'name' => $data['name'],
            'code' => $data['code'],
            'area' => $data['area'],
            'address' => $data['address'],
            'status' => $data['status'] ?? 'active',
        ]);

        $this->activityLogService->log(
            user: $user,
            action: 'created',
            module: 'branch',
            description:
            'Created branch ' .
            $branch->name .
            '.',
            subject: $branch,
            newValues: $branch->only([
                'name',
                'code',
                'area',
                'address',
                'status',
            ]),
        );

        return $branch;
    }

    /**
     * Update an existing branch.
     */
    public function update(
        User $user,
        Branch $branch,
        array $data
    ): Branch {
        $oldValues = $branch->only([
            'name',
            'code',
            'area',
            'address',
            'status',
        ]);

        $branch->update([
            'name' => $data['name'],
            'code' => $data['code'],
            'area' => $data['area'],
            'address' => $data['address'],
            'status' => $data['status'] ?? $branch->status,
        ]);

        $branch = $branch->fresh();

        $this->activityLogService->log(
            user: $user,
            action: 'updated',
            module: 'branch',
            description:
            'Updated branch ' .
            $branch->name .
            '.',
            subject: $branch,
            oldValues: $oldValues,
            newValues: $branch->only([
                'name',
                'code',
                'area',
                'address',
                'status',
            ]),
        );

        return $branch;
    }

    /**
     * Toggle a branch's active status.
     */
    public function toggleStatus(
        User $user,
        Branch $branch
    ): Branch {
        $oldStatus = $branch->status;

        $branch->status =
            $oldStatus === 'active'
            ? 'inactive'
            : 'active';

        $branch->save();

        $this->activityLogService->log(
            user: $user,
            action: 'status_changed',
            module: 'branch',
            description:
            'Changed branch ' .
            $branch->name .
            ' status from ' .
            $oldStatus .
            ' to ' .
            $branch->status .
            '.',
            subject: $branch,
            oldValues: [
                'status' => $oldStatus,
            ],
            newValues: [
                'status' => $branch->status,
            ],
        );

        return $branch;
    }

    /**
     * Soft delete a branch.
     */
    public function delete(
        User $user,
        Branch $branch
    ): void {
        $this->activityLogService->log(
            user: $user,
            action: 'deleted',
            module: 'branch',
            description:
            'Deleted branch ' .
            $branch->name .
            '.',
            subject: $branch,
            oldValues: $branch->only([
                'name',
                'code',
                'area',
                'address',
                'status',
            ]),
        );

        $branch->delete();
    }
}