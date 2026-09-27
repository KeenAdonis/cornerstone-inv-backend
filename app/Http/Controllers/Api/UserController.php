<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;

use App\Models\User;

use App\Services\UserService;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private UserService $userService
    ) {
    }

    /**
     * Get all system users.
     */
    public function index(): JsonResponse
    {
        $users = $this->userService->getUsers();

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $users,
            ],
        ]);
    }

    /**
     * Create a new system user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => [
                'user' => $user->load([
                    'branch',
                    'warehouse',
                    'assignedBranches',
                    'assignedWarehouses',
                ]),
            ],
        ], 201);
    }

    /**
     * Update an existing system user.
     */
    public function update(
        UpdateUserRequest $request,
        User $user
    ): JsonResponse {
        $user = $this->userService->update(
            $request->user(),
            $user,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => [
                'user' => $user->load([
                    'branch',
                    'warehouse',
                    'assignedBranches',
                    'assignedWarehouses',
                ]),
            ],
        ]);
    }

    /**
     * Toggle a system user's active status.
     */
    public function toggleStatus(
        Request $request,
        User $user
    ): JsonResponse {
        $user = $this->userService->toggleStatus(
            $request->user(),
            $user
        );

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully.',
            'data' => [
                'user' => $user->load([
                    'branch',
                    'warehouse',
                ]),
            ],
        ]);
    }

    /**
     * Soft delete a system user.
     */
    public function destroy(
        Request $request,
        User $user
    ): JsonResponse {
        try {
            $this->userService->delete(
                $request->user(),
                $user
            );

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.',
            ]);
        } catch (\RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 403);
        }
    }
}