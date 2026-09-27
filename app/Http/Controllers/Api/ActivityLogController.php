<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $logs = $this->activityLogService->getLogsForUser(
            $request->user(),
            $request->only([
                'user_id',
                'branch_id',
                'warehouse_id',
                'module',
                'action',
                'date_from',
                'date_to',
            ])
        );

        return response()->json([
            'success' => true,
            'data' => [
                'activity_logs' => $logs,
            ],
        ]);
    }
}