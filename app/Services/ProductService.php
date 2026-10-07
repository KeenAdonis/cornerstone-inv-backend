<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }
    /**
     * Get all products with their categories.
     */
    public function getAll(): Collection
    {
        return Product::with('category')
            ->latest()
            ->get();
    }

    /**
     * Create a new product.
     */
    public function create(
        User $user,
        array $data
    ): Product {
        $product = Product::create($data);

        $product->load('category');

        $this->activityLogService->log(
            user: $user,
            action: 'created',
            module: 'product',
            description:
            'Created product ' .
            $product->name .
            '.',
            subject: $product,
            newValues: $product->only([
                'name',
                'product_code',
                'sku',
                'category_id',
                'status',
            ]),
        );

        return $product;

    }

    /**
     * Update an existing product.
     */
    public function update(
        User $user,
        Product $product,
        array $data
    ): Product {
        $oldValues = $product->only([
            'name',
            'product_code',
            'sku',
            'category_id',
            'status',
        ]);

        $product->update($data);

        $product->load('category');

        $this->activityLogService->log(
            user: $user,
            action: 'updated',
            module: 'product',
            description:
            'Updated product ' .
            $product->name .
            '.',
            subject: $product,
            oldValues: $oldValues,
            newValues: $product->only([
                'name',
                'product_code',
                'sku',
                'category_id',
                'status',
            ]),
        );

        return $product;
    }

    /**
     * Delete a product.
     */
    public function delete(
        User $user,
        Product $product
    ): void {
        $this->activityLogService->log(
            user: $user,
            action: 'deleted',
            module: 'product',
            description:
            'Deleted product ' .
            $product->name .
            '.',
            subject: $product,
            oldValues: $product->toArray(),
        );

        $product->delete();
    }

    /**
     * Toggle the product status.
     */
    public function toggleStatus(
        User $user,
        Product $product
    ): Product {
        $oldStatus = $product->status;

        $product->update([
            'status' => $oldStatus === 'active'
                ? 'inactive'
                : 'active',
        ]);

        $product->load('category');

        $this->activityLogService->log(
            user: $user,
            action: 'status_changed',
            module: 'product',
            description:
            'Changed product ' .
            $product->name .
            ' status from ' .
            $oldStatus .
            ' to ' .
            $product->status .
            '.',
            subject: $product,
            oldValues: [
                'status' => $oldStatus,
            ],
            newValues: [
                'status' => $product->status,
            ],
        );

        return $product;
    }
}