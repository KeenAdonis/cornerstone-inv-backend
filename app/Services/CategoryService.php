<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;

use App\Services\ActivityLogService;

use Illuminate\Database\Eloquent\Collection;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CategoryService
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }
    public function getCategories(): Collection
    {
        return Category::query()
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'description',
                'status',
            ]);
    }

    public function create(
        User $user,
        array $data
    ): Category {
        $category = Category::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'active',
        ]);

        $this->activityLogService->log(
            user: $user,
            action: 'created',
            module: 'category',
            description:
            'Created category ' .
            $category->name .
            '.',
            subject: $category,
            newValues: $category->only([
                'name',
                'description',
                'status',
            ]),
        );

        return $category;
    }

    public function update(
        User $user,
        Category $category,
        array $data
    ): Category {
        $oldValues = $category->only([
            'name',
            'description',
            'status',
        ]);

        $category->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? $category->status,
        ]);

        $category = $category->fresh();

        $this->activityLogService->log(
            user: $user,
            action: 'updated',
            module: 'category',
            description:
            'Updated category ' .
            $category->name .
            '.',
            subject: $category,
            oldValues: $oldValues,
            newValues: $category->only([
                'name',
                'description',
                'status',
            ]),
        );

        return $category;
    }

    public function delete(
        User $user,
        Category $category
    ): void {
        if ($category->products()->exists()) {
            throw new ConflictHttpException(
                'This category cannot be deleted because it is currently assigned to one or more products.'
            );
        }

        $this->activityLogService->log(
            user: $user,
            action: 'deleted',
            module: 'category',
            description:
            'Deleted category ' .
            $category->name .
            '.',
            subject: $category,
            oldValues: $category->only([
                'name',
                'description',
                'status',
            ]),
        );

        $category->delete();
    }

    public function toggleStatus(
        User $user,
        Category $category
    ): Category {
        $oldStatus = $category->status;

        $category->status = $oldStatus === 'active'
            ? 'inactive'
            : 'active';

        $category->save();

        $this->activityLogService->log(
            user: $user,
            action: 'status_changed',
            module: 'category',
            description:
            'Changed category ' .
            $category->name .
            ' status from ' .
            $oldStatus .
            ' to ' .
            $category->status .
            '.',
            subject: $category,
            oldValues: [
                'status' => $oldStatus,
            ],
            newValues: [
                'status' => $category->status,
            ],
        );

        return $category;
    }
}