<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;

use App\Models\Branch;

use App\Services\BranchService;

use Illuminate\Http\JsonResponse;

class BranchController extends Controller
{
    public function __construct(
        private BranchService $branchService
    ) {
    }

    /**
     * Get all branches.
     */
    public function index(): JsonResponse
    {
        $branches = $this->branchService->getBranches();

        return response()->json([
            'success' => true,
            'data' => [
                'branches' => $branches,
            ],
        ]);
    }

    /**
     * Create a new branch.
     */
    public function store(StoreBranchRequest $request): JsonResponse
    {
        $branch = $this->branchService->create(
            auth()->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Branch created successfully.',
            'data' => [
                'branch' => $branch,
            ],
        ], 201);
    }

    /**
     * Update an existing branch.
     */
    public function update(
        UpdateBranchRequest $request,
        Branch $branch
    ): JsonResponse {
        $branch = $this->branchService->update(
            auth()->user(),
            $branch,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Branch updated successfully.',
            'data' => [
                'branch' => $branch,
            ],
        ]);
    }

    /**
     * Soft delete a branch.
     */
    public function destroy(Branch $branch): JsonResponse
    {
        $this->branchService->delete(
            auth()->user(),
            $branch
        );

        return response()->json([
            'success' => true,
            'message' => 'Branch deleted successfully.',
        ]);
    }

    /**
     * Toggle a branch's active status.
     */
    public function toggleStatus(Branch $branch): JsonResponse
    {
        $branch = $this->branchService->toggleStatus(
            auth()->user(),
            $branch
        );

        return response()->json([
            'success' => true,
            'message' => 'Branch status updated successfully.',
            'data' => [
                'branch' => $branch,
            ],
        ]);
    }
}