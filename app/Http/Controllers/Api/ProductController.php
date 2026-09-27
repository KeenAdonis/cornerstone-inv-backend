<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {
    }

    /**
     * Display all products.
     */
    public function index(): JsonResponse
    {
        $products = $this->productService->getAll();

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products,
            ],
        ]);
    }

    /**
     * Store a new product.
     */
    public function store(
        StoreProductRequest $request
    ): JsonResponse {
        $product = $this->productService->create(
            auth()->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
            'data' => [
                'product' => $product,
            ],
        ], 201);
    }

    /**
     * Update an existing product.
     */
    public function update(
        UpdateProductRequest $request,
        Product $product
    ): JsonResponse {
        $product = $this->productService->update(
            auth()->user(),
            $product,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data' => [
                'product' => $product,
            ],
        ]);
    }

    /**
     * Delete a product.
     */
    public function destroy(
        Product $product
    ): JsonResponse {
        $this->productService->delete(
            auth()->user(),
            $product
        );

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ]);
    }

    /**
     * Toggle the product status.
     */
    public function toggleStatus(
        Product $product
    ): JsonResponse {
        $product = $this->productService->toggleStatus(
            auth()->user(),
            $product
        );

        return response()->json([
            'success' => true,
            'message' => 'Product status updated successfully.',
            'data' => [
                'product' => $product,
            ],
        ]);
    }
}