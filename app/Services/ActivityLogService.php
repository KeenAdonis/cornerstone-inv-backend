<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ActivityLogService
{
    public function log(
        User $user,
        string $action,
        string $module,
        string $description,
        ?Branch $branch = null,
        ?Warehouse $warehouse = null,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => $user->id,

            'branch_id' =>
                $branch?->id,

            'warehouse_id' =>
                $warehouse?->id,

            'action' =>
                $action,

            'module' =>
                $module,

            'description' =>
                $description,

            'subject_type' =>
                $subject?->getMorphClass(),

            'subject_id' =>
                $subject?->getKey(),

            'old_values' =>
                $oldValues,

            'new_values' =>
                $newValues,

            'ip_address' =>
                request()->ip(),

            'user_agent' =>
                request()->userAgent(),
        ]);
    }

    public function logUnauthenticated(
        string $action,
        string $module,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => null,
            'branch_id' => null,
            'warehouse_id' => null,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'subject_type' => null,
            'subject_id' => null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function getLogsForUser(
        User $user,
        array $filters = []
    ): Collection {
        $query = ActivityLog::query()
            ->with([
                'user',
                'branch',
                'warehouse',
                'subject',
            ]);

        if ($user->role === 'admin') {
            $this->applyAdminFilters(
                $query,
                $filters
            );
        }

        if ($user->role === 'branch_coordinator') {
            $branchIds =
                $user->assignedBranches()
                    ->pluck('branches.id')
                    ->map(fn($id) => (int) $id)
                    ->values()
                    ->all();

            if (
                $user->branch_id !== null &&
                !in_array(
                    $user->branch_id,
                    $branchIds,
                    true
                )
            ) {
                $branchIds[] = $user->branch_id;
            }

            if (empty($branchIds)) {
                throw ValidationException::withMessages([
                    'activity_logs' =>
                        'No branch assignment is available for this user.',
                ]);
            }

            $query->whereIn(
                'branch_id',
                $branchIds
            );
        }

        if ($user->role === 'warehouse_coordinator') {
            $warehouseIds =
                $user->assignedWarehouses()
                    ->pluck('warehouses.id')
                    ->map(fn($id) => (int) $id)
                    ->values()
                    ->all();

            if (
                $user->warehouse_id !== null &&
                !in_array(
                    $user->warehouse_id,
                    $warehouseIds,
                    true
                )
            ) {
                $warehouseIds[] =
                    $user->warehouse_id;
            }

            if (empty($warehouseIds)) {
                throw ValidationException::withMessages([
                    'activity_logs' =>
                        'No warehouse assignment is available for this user.',
                ]);
            }

            $query->whereIn(
                'warehouse_id',
                $warehouseIds
            );
        }

        $this->applyCommonFilters(
            $query,
            $filters
        );

        return $query
            ->latest()
            ->get();
    }

    private function applyAdminFilters(
        $query,
        array $filters
    ): void {
        if (
            !empty($filters['user_id'])
        ) {
            $query->where(
                'user_id',
                $filters['user_id']
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

        if (
            !empty($filters['warehouse_id'])
        ) {
            $query->where(
                'warehouse_id',
                $filters['warehouse_id']
            );
        }
    }

    private function applyCommonFilters(
        $query,
        array $filters
    ): void {
        if (
            !empty($filters['module'])
        ) {
            $query->where(
                'module',
                $filters['module']
            );
        }

        if (
            !empty($filters['action'])
        ) {
            $query->where(
                'action',
                $filters['action']
            );
        }

        if (
            !empty($filters['date_from'])
        ) {
            $query->whereDate(
                'created_at',
                '>=',
                $filters['date_from']
            );
        }

        if (
            !empty($filters['date_to'])
        ) {
            $query->whereDate(
                'created_at',
                '<=',
                $filters['date_to']
            );
        }
    }
}