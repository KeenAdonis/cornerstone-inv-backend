<?php

namespace App\Services;

use App\Models\Product;
use App\Models\User;

use App\Services\ActivityLogService;

use Illuminate\Database\Eloquent\Collection;

use Illuminate\Support\Facades\Storage;

use Illuminate\Http\UploadedFile;

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
            ->get()
            ->each(fn(Product $product) => $this->addImageUrl($product));
    }

    /**
     * Add the public image URL to a product.
     */
    private function addImageUrl(Product $product): Product
    {
        $product->setAttribute(
            'image_url',
            $product->image_path
            ? Storage::disk('public')->url($product->image_path)
            : null
        );

        return $product;
    }

    /**
     * Create a new product.
     */
    public function create(
        User $user,
        array $data
    ): Product {
        $image = $data['image'] ?? null;

        unset($data['image']);

        if ($image instanceof UploadedFile) {
            $data['image_path'] = $image->store(
                'products',
                'public'
            );
        }

        try {
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
                    'image_path',
                ]),
            );

            return $this->addImageUrl($product);
        } catch (\Throwable $exception) {
            if (isset($data['image_path'])) {
                Storage::disk('public')->delete(
                    $data['image_path']
                );
            }

            throw $exception;
        }
    }



    /**
     * Update an existing product.
     */
    public function update(
        User $user,
        Product $product,
        array $data
    ): Product {
        $image = $data['image'] ?? null;

        unset($data['image']);

        $oldValues = $product->only([
            'name',
            'product_code',
            'sku',
            'category_id',
            'status',
            'image_path',
        ]);

        $oldImagePath = $product->image_path;
        $newImagePath = null;

        if ($image instanceof UploadedFile) {
            $newImagePath = $image->store(
                'products',
                'public'
            );

            $data['image_path'] = $newImagePath;
        }

        try {
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
                    'image_path',
                ]),
            );

            if (
                $newImagePath !== null &&
                $oldImagePath !== null &&
                $oldImagePath !== $newImagePath
            ) {
                Storage::disk('public')->delete(
                    $oldImagePath
                );
            }

            return $this->addImageUrl($product);
        } catch (\Throwable $exception) {
            if ($newImagePath !== null) {
                Storage::disk('public')->delete(
                    $newImagePath
                );
            }

            throw $exception;
        }
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