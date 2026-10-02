<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Http\Requests\PurchaseOrder\DeliverPurchaseOrderRequest;
use App\Http\Requests\PurchaseOrder\ReleasePurchaseOrderRequest;
use App\Http\Requests\PurchaseOrder\ReviewPurchaseOrderRequest;
use App\Http\Requests\PurchaseOrder\StorePurchaseOrderRequest;
use App\Http\Requests\PurchaseOrder\BulkApprovePurchaseOrdersRequest;
use App\Http\Requests\PurchaseOrder\BulkRejectPurchaseOrdersRequest;

use App\Models\PurchaseOrder;

use App\Services\PurchaseOrderService;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function __construct(
        private PurchaseOrderService $purchaseOrderService
    ) {
    }

    public function index(
        Request $request
    ): JsonResponse {
        $purchaseOrders =
            $this->purchaseOrderService
                ->getPurchaseOrdersForUser(
                    $request->user(),
                    $request->only([
                        'branch_id',
                        'warehouse_id',
                    ])
                );

        return response()->json([
            'success' => true,
            'data' => [
                'purchase_orders' =>
                    $purchaseOrders,
            ],
        ]);
    }

    public function store(
        StorePurchaseOrderRequest $request
    ): JsonResponse {
        $purchaseOrder =
            $this->purchaseOrderService->create(
                $request->user(),
                $request->validated()
            );

        return response()->json([
            'success' => true,
            'message' =>
                'Purchase order created successfully.',
            'data' => [
                'purchase_order' =>
                    $purchaseOrder,
            ],
        ], 201);
    }

    public function review(
        ReviewPurchaseOrderRequest $request,
        PurchaseOrder $purchaseOrder
    ): JsonResponse {
        $updatedPurchaseOrder =
            $this->purchaseOrderService->review(
                $request->user(),
                $purchaseOrder,
                $request->validated()
            );

        return response()->json([
            'success' => true,
            'message' =>
                'Purchase order reviewed successfully.',
            'data' => [
                'purchase_order' =>
                    $updatedPurchaseOrder,
            ],
        ]);
    }

    public function bulkApprove(
        BulkApprovePurchaseOrdersRequest $request
    ): JsonResponse {
        $purchaseOrders =
            $this->purchaseOrderService->bulkApprove(
                $request->user(),
                $request->validated('purchase_order_ids')
            );

        return response()->json([
            'success' => true,
            'message' =>
                'Purchase orders approved successfully.',
            'data' => [
                'purchase_orders' =>
                    $purchaseOrders,
            ],
        ]);
    }

    public function bulkReject(
        BulkRejectPurchaseOrdersRequest $request
    ): JsonResponse {
        $purchaseOrders =
            $this->purchaseOrderService->bulkReject(
                $request->user(),
                $request->validated('purchase_order_ids'),
                $request->validated('rejection_reason')
            );

        return response()->json([
            'success' => true,
            'message' =>
                'Purchase orders rejected successfully.',
            'data' => [
                'purchase_orders' =>
                    $purchaseOrders,
            ],
        ]);
    }

    public function process(
        Request $request,
        PurchaseOrder $purchaseOrder
    ): JsonResponse {
        $updatedPurchaseOrder =
            $this->purchaseOrderService->process(
                $request->user(),
                $purchaseOrder
            );

        return response()->json([
            'success' => true,
            'message' =>
                'Purchase order is now being prepared.',
            'data' => [
                'purchase_order' =>
                    $updatedPurchaseOrder,
            ],
        ]);
    }

    public function release(
        ReleasePurchaseOrderRequest $request,
        PurchaseOrder $purchaseOrder
    ): JsonResponse {
        $updatedPurchaseOrder =
            $this->purchaseOrderService->release(
                $request->user(),
                $purchaseOrder,
                $request->validated()
            );

        return response()->json([
            'success' => true,
            'message' =>
                'Purchase order is now out for delivery.',
            'data' => [
                'purchase_order' =>
                    $updatedPurchaseOrder,
            ],
        ]);
    }

    public function deliver(
        DeliverPurchaseOrderRequest $request,
        PurchaseOrder $purchaseOrder
    ): JsonResponse {
        $updatedPurchaseOrder =
            $this->purchaseOrderService->deliver(
                $request->user(),
                $purchaseOrder,
                $request->validated()
            );

        return response()->json([
            'success' => true,
            'message' =>
                'Purchase order marked as delivered successfully.',
            'data' => [
                'purchase_order' =>
                    $updatedPurchaseOrder,
            ],
        ]);
    }

    public function complete(
        Request $request,
        PurchaseOrder $purchaseOrder
    ): JsonResponse {
        $updatedPurchaseOrder =
            $this->purchaseOrderService->complete(
                $request->user(),
                $purchaseOrder
            );

        return response()->json([
            'success' => true,
            'message' =>
                'Purchase order completed successfully.',
            'data' => [
                'purchase_order' =>
                    $updatedPurchaseOrder,
            ],
        ]);
    }
}