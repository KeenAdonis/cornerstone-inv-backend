<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }

    /**
     * Authenticate a user using a secure session.
     */
    public function login(
        Request $request
    ): JsonResponse {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials)) {
            $this->activityLogService->logUnauthenticated(
                action: 'login_failed',
                module: 'authentication',
                description:
                'Failed login attempt for email ' .
                $credentials['email'] .
                '.',
            );

            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.',
            ], 401);
        }

        $request->session()->regenerate();

        $user = $request->user();

        $this->activityLogService->log(
            user: $user,
            action: 'logged_in',
            module: 'authentication',
            description:
            'User ' .
            $user->name .
            ' logged in.',
        );

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => $user->load([
                    'assignedBranches',
                    'assignedWarehouses',
                ]),
            ],
        ]);
    }

    /**
     * Log out the authenticated user and invalidate
     * the current session.
     */
    public function logout(
        Request $request
    ): JsonResponse {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}