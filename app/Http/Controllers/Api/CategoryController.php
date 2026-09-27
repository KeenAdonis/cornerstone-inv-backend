<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(
        private CategoryService $categoryService
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $this->categoryService->getCategories(),
            ],
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create(
            auth()->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data' => [
                'category' => $category,
            ],
        ], 201);
    }

    public function update(
        UpdateCategoryRequest $request,
        Category $category
    ): JsonResponse {
        $category = $this->categoryService->update(
            auth()->user(),
            $category,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data' => [
                'category' => $category,
            ],
        ]);
    }

    public function toggleStatus(Category $category): JsonResponse
    {
        $category = $this->categoryService->toggleStatus(
            auth()->user(),
            $category
        );

        return response()->json([
            'success' => true,
            'message' => 'Category status updated successfully.',
            'data' => [
                'category' => $category,
            ],
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->categoryService->delete(
            auth()->user(),
            $category
        );

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully.',
        ]);
    }
}