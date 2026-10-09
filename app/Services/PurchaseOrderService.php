<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\PurchaseOrderDeliveryAttachment;

use Illuminate\Database\Eloquent\Collection;

use Illuminate\Http\UploadedFile;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Str;

use Illuminate\Validation\ValidationException;

use App\Services\ActivityLogService;

class PurchaseOrderService
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }
    public function getPurchaseOrdersForUser(
        User $user,
        array $filters = []
    ): Collection {
        $query = PurchaseOrder::query()
            ->with([
                'branch',
                'warehouse',
                'creator',
                'approver',
                'items.product',
                'deliveryAttachments',
            ])
            ->latest();

        if ($user->role === 'branch_coordinator') {
            $branchId =
                $filters['branch_id'] ?? null;

            if ($branchId !== null) {
                $this->authorizeBranchAssignment(
                    $user,
                    (int) $branchId
                );
            } else {
                $branchId =
                    $this->getDefaultBranchId(
                        $user
                    );
            }

            return $this->appendDeliveryPhotoUrls(
                $query
                    ->where(
                        'branch_id',
                        $branchId
                    )
                    ->get()
            );
        }

        if ($user->role === 'admin') {
            $this->applyAdminFilters(
                $query,
                $filters
            );

            return $this->appendDeliveryPhotoUrls(
                $query->get()
            );
        }

        if ($user->role === 'warehouse_coordinator') {
            $warehouseId =
                $filters['warehouse_id'] ?? null;

            if ($warehouseId !== null) {
                $this->authorizeWarehouseAssignment(
                    $user,
                    (int) $warehouseId
                );
            } else {
                $warehouseId =
                    $this->getDefaultWarehouseId(
                        $user
                    );
            }

            return $this->appendDeliveryPhotoUrls(
                $query
                    ->where(
                        'warehouse_id',
                        $warehouseId
                    )
                    ->whereIn('status', [
                        'approved',
                        'preparing',
                        'out_for_delivery',
                        'delivered',
                        'completed',
                    ])
                    ->get()
            );
        }

        throw ValidationException::withMessages([
            'user' =>
                'You are not authorized to view purchase orders.',
        ]);
    }

    public function create(
        User $user,
        array $data
    ): PurchaseOrder {
        if (
            $user->role !==
            'branch_coordinator'
        ) {
            throw ValidationException::withMessages([
                'user' =>
                    'Only branch coordinators can create purchase orders.',
            ]);
        }

        $branchId =
            (int) $data['branch_id'];

        $this->authorizeBranchAssignment(
            $user,
            $branchId
        );

        $warehouse = Warehouse::query()
            ->where(
                'id',
                $data['warehouse_id']
            )
            ->where(
                'status',
                'active'
            )
            ->first();

        if (!$warehouse) {
            throw ValidationException::withMessages([
                'warehouse_id' =>
                    'The selected warehouse is not active or does not exist.',
            ]);
        }

        return DB::transaction(
            function () use ($user, $data, $branchId) {
                $purchaseOrder =
                    PurchaseOrder::create([
                        'reference_number' =>
                            $this->generateReferenceNumber(),

                        'branch_id' =>
                            $branchId,

                        'warehouse_id' =>
                            $data['warehouse_id'],

                        'created_by' =>
                            $user->id,

                        'status' =>
                            'pending',

                        'requested_at' =>
                            now(),

                        'notes' =>
                            $data['notes'] ?? null,
                    ]);

                foreach (
                    $data['items']
                    as $item
                ) {
                    $purchaseOrder
                        ->items()
                        ->create([
                            'product_id' =>
                                $item['product_id'],

                            'quantity' =>
                                $item['quantity'],
                        ]);
                }

                $purchaseOrder->load([
                    'branch',
                    'warehouse',
                    'creator',
                    'approver',
                    'items.product',
                ]);

                $this->activityLogService->log(
                    user: $user,
                    action: 'created',
                    module: 'purchase_order',
                    description:
                    'Created purchase order ' .
                    $purchaseOrder->reference_number . '.',
                    branch: $purchaseOrder->branch,
                    warehouse: $purchaseOrder->warehouse,
                    subject: $purchaseOrder,
                    newValues: [
                        'status' => 'pending',
                    ],
                );

                return $this->appendDeliveryPhotoUrl(
                    $purchaseOrder
                );
            }
        );
    }


    public function review(
        User $user,
        PurchaseOrder $purchaseOrder,
        array $data
    ): PurchaseOrder {
        if ($user->role !== 'admin') {
            throw ValidationException::withMessages([
                'user' =>
                    'Only administrators can review purchase orders.',
            ]);
        }

        return DB::transaction(function () use ($user, $purchaseOrder, $data) {
            $purchaseOrder = PurchaseOrder::query()
                ->with([
                    'branch',
                    'warehouse',
                ])
                ->lockForUpdate()
                ->findOrFail($purchaseOrder->id);

            if ($purchaseOrder->status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' =>
                        'Only pending purchase orders can be reviewed.',
                ]);
            }

            if ($data['action'] === 'approve') {
                $purchaseOrder->update([
                    'status' => 'approved',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'rejection_reason' => null,
                ]);
            } elseif ($data['action'] === 'reject') {
                $purchaseOrder->update([
                    'status' => 'rejected',
                    'approved_by' => null,
                    'approved_at' => null,
                    'rejection_reason' =>
                        $data['rejection_reason'] ?? null,
                ]);
            } else {
                throw ValidationException::withMessages([
                    'action' => 'Invalid review action.',
                ]);
            }

            $purchaseOrder->load([
                'branch',
                'warehouse',
            ]);

            $newStatus = $data['action'] === 'approve'
                ? 'approved'
                : 'rejected';

            $this->activityLogService->log(
                user: $user,
                action: $data['action'] === 'approve'
                ? 'approved'
                : 'rejected',
                module: 'purchase_order',
                description: $data['action'] === 'approve'
                ? 'Approved purchase order ' .
                $purchaseOrder->reference_number . '.'
                : 'Rejected purchase order ' .
                $purchaseOrder->reference_number . '.',
                branch: $purchaseOrder->branch,
                warehouse: $purchaseOrder->warehouse,
                subject: $purchaseOrder,
                oldValues: [
                    'status' => 'pending',
                ],
                newValues: [
                    'status' => $newStatus,
                ],
            );

            return $this->appendDeliveryPhotoUrl(
                $purchaseOrder->fresh([
                    'branch',
                    'warehouse',
                    'creator',
                    'approver',
                    'items.product',
                ])
            );
        });
    }


    public function bulkApprove(
        User $user,
        array $purchaseOrderIds
    ): Collection {
        if ($user->role !== 'admin') {
            throw ValidationException::withMessages([
                'user' =>
                    'Only administrators can approve purchase orders.',
            ]);
        }

        return DB::transaction(function () use ($user, $purchaseOrderIds) {
            $purchaseOrders = PurchaseOrder::query()
                ->with([
                    'branch',
                    'warehouse',
                    'creator',
                    'approver',
                    'items.product',
                ])
                ->whereIn('id', $purchaseOrderIds)
                ->lockForUpdate()
                ->get();

            foreach ($purchaseOrders as $purchaseOrder) {
                if ($purchaseOrder->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'status' =>
                            "Purchase order {$purchaseOrder->reference_number} is no longer pending.",
                    ]);
                }
            }

            foreach ($purchaseOrders as $purchaseOrder) {
                $purchaseOrder->update([
                    'status' => 'approved',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'rejection_reason' => null,
                ]);

                $this->activityLogService->log(
                    user: $user,
                    action: 'approved',
                    module: 'purchase_order',
                    description:
                    'Approved purchase order ' .
                    $purchaseOrder->reference_number . '.',
                    branch: $purchaseOrder->branch,
                    warehouse: $purchaseOrder->warehouse,
                    subject: $purchaseOrder,
                    oldValues: [
                        'status' => 'pending',
                    ],
                    newValues: [
                        'status' => 'approved',
                    ],
                );
            }

            return $purchaseOrders->map(
                function (PurchaseOrder $purchaseOrder) {
                    return $this->appendDeliveryPhotoUrl(
                        $purchaseOrder->fresh([
                            'branch',
                            'warehouse',
                            'creator',
                            'approver',
                            'items.product',
                        ])
                    );
                }
            );
        });
    }

    public function bulkReject(
        User $user,
        array $purchaseOrderIds,
        string $rejectionReason
    ): Collection {
        if ($user->role !== 'admin') {
            throw ValidationException::withMessages([
                'user' =>
                    'Only administrators can reject purchase orders.',
            ]);
        }

        return DB::transaction(function () use ($user, $purchaseOrderIds, $rejectionReason) {
            $purchaseOrders = PurchaseOrder::query()
                ->with([
                    'branch',
                    'warehouse',
                    'creator',
                    'approver',
                    'items.product',
                ])
                ->whereIn('id', $purchaseOrderIds)
                ->lockForUpdate()
                ->get();

            foreach ($purchaseOrders as $purchaseOrder) {
                if ($purchaseOrder->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'status' =>
                            "Purchase order {$purchaseOrder->reference_number} is no longer pending.",
                    ]);
                }
            }

            foreach ($purchaseOrders as $purchaseOrder) {
                $purchaseOrder->update([
                    'status' => 'rejected',
                    'approved_by' => null,
                    'approved_at' => null,
                    'rejection_reason' => $rejectionReason,
                ]);

                $this->activityLogService->log(
                    user: $user,
                    action: 'rejected',
                    module: 'purchase_order',
                    description:
                    'Rejected purchase order ' .
                    $purchaseOrder->reference_number . '.',
                    branch: $purchaseOrder->branch,
                    warehouse: $purchaseOrder->warehouse,
                    subject: $purchaseOrder,
                    oldValues: [
                        'status' => 'pending',
                    ],
                    newValues: [
                        'status' => 'rejected',
                        'rejection_reason' => $rejectionReason,
                    ],
                );
            }

            return $purchaseOrders->map(
                function (PurchaseOrder $purchaseOrder) {
                    return $this->appendDeliveryPhotoUrl(
                        $purchaseOrder->fresh([
                            'branch',
                            'warehouse',
                            'creator',
                            'approver',
                            'items.product',
                        ])
                    );
                }
            );
        });
    }


    public function process(
        User $user,
        PurchaseOrder $purchaseOrder
    ): PurchaseOrder {
        if ($user->role !== 'warehouse_coordinator') {
            throw ValidationException::withMessages([
                'user' =>
                    'Only warehouse coordinators can prepare purchase orders.',
            ]);
        }

        return DB::transaction(function () use ($user, $purchaseOrder) {
            $purchaseOrder = PurchaseOrder::query()
                ->with([
                    'branch',
                    'warehouse',
                ])
                ->lockForUpdate()
                ->findOrFail($purchaseOrder->id);

            $this->authorizeWarehouseAssignment(
                $user,
                $purchaseOrder->warehouse_id
            );

            if ($purchaseOrder->status !== 'approved') {
                throw ValidationException::withMessages([
                    'status' =>
                        'Only approved purchase orders can be prepared.',
                ]);
            }

            $purchaseOrder->update([
                'status' => 'preparing',
            ]);

            $this->activityLogService->log(
                user: $user,
                action: 'prepared',
                module: 'purchase_order',
                description:
                'Started preparing purchase order ' .
                $purchaseOrder->reference_number . '.',
                branch: $purchaseOrder->branch,
                warehouse: $purchaseOrder->warehouse,
                subject: $purchaseOrder,
                oldValues: [
                    'status' => 'approved',
                ],
                newValues: [
                    'status' => 'preparing',
                ],
            );

            return $this->appendDeliveryPhotoUrl(
                $purchaseOrder->fresh([
                    'branch',
                    'warehouse',
                    'creator',
                    'approver',
                    'items.product',
                ])
            );
        });
    }


    public function release(
        User $user,
        PurchaseOrder $purchaseOrder,
        array $data
    ): PurchaseOrder {
        if (
            $user->role !==
            'warehouse_coordinator'
        ) {
            throw ValidationException::withMessages([
                'user' =>
                    'Only warehouse coordinators can release purchase orders.',
            ]);
        }

        $this->authorizeWarehouseAssignment(
            $user,
            $purchaseOrder->warehouse_id
        );

        if (
            $purchaseOrder->status !==
            'preparing'
        ) {
            throw ValidationException::withMessages([
                'status' =>
                    'Only purchase orders in preparing status can be released.',
            ]);
        }

        $deliveryType =
            $data['delivery_type'];

        $shipOutDate =
            $data['ship_out_date'];

        $trackingNumber =
            $deliveryType === 'in_house'
            ? null
            : ($data['tracking_number'] ?? null);

        return DB::transaction(
            function () use ($user, $purchaseOrder, $deliveryType, $shipOutDate, $trackingNumber) {
                $purchaseOrder =
                    PurchaseOrder::query()
                        ->with([
                            'branch',
                            'warehouse',
                            'creator',
                            'approver',
                            'items.product',
                        ])
                        ->lockForUpdate()
                        ->findOrFail(
                            $purchaseOrder->id
                        );

                if (
                    $purchaseOrder->status !==
                    'preparing'
                ) {
                    throw ValidationException::withMessages([
                        'status' =>
                            'Only purchase orders in preparing status can be released.',
                    ]);
                }

                foreach (
                    $purchaseOrder->items
                    as $item
                ) {
                    $quantity =
                        (float) $item->quantity;

                    $warehouseInventory =
                        Inventory::query()
                            ->where(
                                'product_id',
                                $item->product_id
                            )
                            ->where(
                                'warehouse_id',
                                $purchaseOrder->warehouse_id
                            )
                            ->lockForUpdate()
                            ->first();

                    if (
                        !$warehouseInventory
                    ) {
                        throw ValidationException::withMessages([
                            'inventory' =>
                                "No warehouse inventory record exists for product {$item->product_id}.",
                        ]);
                    }

                    $availableQuantity =
                        (float) $warehouseInventory->quantity;

                    if (
                        $availableQuantity <
                        $quantity
                    ) {
                        throw ValidationException::withMessages([
                            'inventory' =>
                                "Insufficient stock for product {$item->product_id}. Available: {$availableQuantity}, requested: {$quantity}.",
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Deduct Warehouse Inventory
                    |--------------------------------------------------------------------------
                    */

                    $warehouseInventory->update([
                        'quantity' =>
                            $availableQuantity -
                            $quantity,
                    ]);
                }

                $purchaseOrder->update([
                    'status' =>
                        'out_for_delivery',

                    'delivery_type' =>
                        $deliveryType,

                    'tracking_number' =>
                        $trackingNumber,

                    'ship_out_date' =>
                        $shipOutDate,
                ]);

                $this->activityLogService->log(
                    user: $user,
                    action: 'released',
                    module: 'purchase_order',
                    description:
                    'Released purchase order ' .
                    $purchaseOrder->reference_number .
                    ' for delivery.',
                    branch:
                    $purchaseOrder->branch,
                    warehouse:
                    $purchaseOrder->warehouse,
                    subject:
                    $purchaseOrder,
                    oldValues: [
                        'status' =>
                            'preparing',
                    ],
                    newValues: [
                        'status' =>
                            'out_for_delivery',

                        'delivery_type' =>
                            $deliveryType,

                        'tracking_number' =>
                            $trackingNumber,

                        'ship_out_date' =>
                            $shipOutDate,
                    ],
                );

                return $this->appendDeliveryPhotoUrl(
                    $purchaseOrder->fresh([
                        'branch',
                        'warehouse',
                        'creator',
                        'approver',
                        'items.product',
                    ])
                );
            }
        );
    }

    private function appendDeliveryAttachmentUrls(
        PurchaseOrder $purchaseOrder
    ): PurchaseOrder {
        $purchaseOrder->deliveryAttachments
            ->each(
                function (PurchaseOrderDeliveryAttachment $attachment) {
                    $attachment->setAttribute(
                        'file_url',
                        Storage::disk('public')->url(
                            $attachment->file_path
                        )
                    );
                }
            );

        return $purchaseOrder;
    }

    public function deliver(
        User $user,
        PurchaseOrder $purchaseOrder,
        array $data
    ): PurchaseOrder {
        if (
            $user->role !==
            'branch_coordinator'
        ) {
            throw ValidationException::withMessages([
                'user' =>
                    'Only branch coordinators can mark purchase orders as delivered.',
            ]);
        }

        $this->authorizeBranchAssignment(
            $user,
            $purchaseOrder->branch_id
        );

        if (
            $purchaseOrder->status !==
            'out_for_delivery'
        ) {
            throw ValidationException::withMessages([
                'status' =>
                    'Only purchase orders that are out for delivery can be marked as delivered.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Delivery Proof Attachments
        |--------------------------------------------------------------------------
        |
        | New requests use delivery_photos[].
        | delivery_photo is kept temporarily for backward compatibility
        | with the existing single-file implementation.
        |
        */

        $deliveryPhotos = [];

        if (
            isset($data['delivery_photos']) &&
            is_array($data['delivery_photos'])
        ) {
            $deliveryPhotos =
                $data['delivery_photos'];
        } elseif (
            isset($data['delivery_photo']) &&
            $data['delivery_photo'] instanceof UploadedFile
        ) {
            $deliveryPhotos = [
                $data['delivery_photo'],
            ];
        }

        if (
            count($deliveryPhotos) === 0
        ) {
            throw ValidationException::withMessages([
                'delivery_photos' =>
                    'At least one delivery proof attachment is required.',
            ]);
        }

        $storedDeliveryPaths = [];

        foreach (
            $deliveryPhotos as $deliveryPhoto
        ) {
            /** @var UploadedFile $deliveryPhoto */

            $storedDeliveryPaths[] = [
                'file' => $deliveryPhoto,

                'path' =>
                    $deliveryPhoto->store(
                        'purchase-orders/delivery-photos',
                        'public'
                    ),
            ];
        }

        $dateOfArrival =
            $data['date_of_arrival'] ?? null;

        try {
            return DB::transaction(
                function () use ($user, $purchaseOrder, $storedDeliveryPaths, $dateOfArrival) {
                    $purchaseOrder =
                        PurchaseOrder::query()
                            ->with([
                                'items.product',
                            ])
                            ->lockForUpdate()
                            ->findOrFail(
                                $purchaseOrder->id
                            );

                    if (
                        $purchaseOrder->status !==
                        'out_for_delivery'
                    ) {
                        throw ValidationException::withMessages([
                            'status' =>
                                'Only purchase orders that are out for delivery can be marked as delivered.',
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Update Branch Inventory
                    |--------------------------------------------------------------------------
                    |
                    | Branch inventory is updated only after the branch
                    | confirms receipt through proof of delivery.
                    |
                    */

                    foreach (
                        $purchaseOrder->items
                        as $item
                    ) {
                        $quantity =
                            (float) $item->quantity;

                        $branchInventory =
                            Inventory::query()
                                ->where(
                                    'product_id',
                                    $item->product_id
                                )
                                ->where(
                                    'branch_id',
                                    $purchaseOrder->branch_id
                                )
                                ->lockForUpdate()
                                ->first();

                        if (
                            $branchInventory
                        ) {
                            $branchInventory->update([
                                'quantity' =>
                                    (float) $branchInventory->quantity +
                                    $quantity,
                            ]);
                        } else {
                            Inventory::create([
                                'product_id' =>
                                    $item->product_id,

                                'warehouse_id' =>
                                    null,

                                'branch_id' =>
                                    $purchaseOrder->branch_id,

                                'quantity' =>
                                    $quantity,

                                'par_level' =>
                                    0,

                                'reorder_level' =>
                                    0,
                            ]);
                        }

                        StockMovement::create([
                            'product_id' =>
                                $item->product_id,

                            'purchase_order_id' =>
                                $purchaseOrder->id,

                            'movement_type' =>
                                'transfer',

                            'quantity' =>
                                $quantity,

                            'from_warehouse_id' =>
                                $purchaseOrder->warehouse_id,

                            'from_branch_id' =>
                                null,

                            'to_warehouse_id' =>
                                null,

                            'to_branch_id' =>
                                $purchaseOrder->branch_id,

                            'moved_at' =>
                                now(),

                            'created_by' =>
                                $user->id,

                            'notes' =>
                                'Delivered through purchase order ' .
                                $purchaseOrder->reference_number,
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Update Purchase Order
                    |--------------------------------------------------------------------------
                    */

                    $firstDeliveryPhotoPath =
                        $storedDeliveryPaths[0]['path'];

                    $purchaseOrder->update([
                        'status' =>
                            'delivered',

                        /*
                         * Keep the legacy field populated with
                         * the first attachment for backward compatibility.
                         */
                        'delivery_photo_path' =>
                            $firstDeliveryPhotoPath,

                        'date_of_arrival' =>
                            $dateOfArrival,
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Save Delivery Proof Attachments
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $storedDeliveryPaths as $storedDelivery
                    ) {
                        /** @var UploadedFile $deliveryPhoto */
                        $deliveryPhoto =
                            $storedDelivery['file'];

                        PurchaseOrderDeliveryAttachment::create([
                            'purchase_order_id' =>
                                $purchaseOrder->id,

                            'file_name' =>
                                $deliveryPhoto->getClientOriginalName(),

                            'file_path' =>
                                $storedDelivery['path'],

                            'mime_type' =>
                                $deliveryPhoto->getClientMimeType(),

                            'file_size' =>
                                $deliveryPhoto->getSize(),
                        ]);
                    }

                    $purchaseOrder->load([
                        'branch',
                        'warehouse',
                    ]);

                    $this->activityLogService->log(
                        user: $user,
                        action: 'delivered',
                        module: 'purchase_order',
                        description:
                        'Confirmed delivery of purchase order ' .
                        $purchaseOrder->reference_number . '.',
                        branch:
                        $purchaseOrder->branch,
                        warehouse:
                        $purchaseOrder->warehouse,
                        subject:
                        $purchaseOrder,
                        oldValues: [
                            'status' =>
                                'out_for_delivery',
                        ],
                        newValues: [
                            'status' =>
                                'delivered',

                            'date_of_arrival' =>
                                $dateOfArrival,
                        ],
                    );

                    $purchaseOrder =
                        $purchaseOrder->fresh([
                            'branch',
                            'warehouse',
                            'creator',
                            'approver',
                            'items.product',
                            'deliveryAttachments',
                        ]);

                    $this->appendDeliveryPhotoUrl(
                        $purchaseOrder
                    );

                    $this->appendDeliveryAttachmentUrls(
                        $purchaseOrder
                    );

                    return $purchaseOrder;
                }
            );
        } catch (\Throwable $exception) {
            /*
            |--------------------------------------------------------------------------
            | Cleanup Uploaded Files
            |--------------------------------------------------------------------------
            |
            | If the database transaction fails, remove all files
            | that were already stored.
            |
            */

            foreach (
                $storedDeliveryPaths as $storedDelivery
            ) {
                Storage::disk('public')
                    ->delete(
                        $storedDelivery['path']
                    );
            }

            throw $exception;
        }
    }

    public function complete(
        User $user,
        PurchaseOrder $purchaseOrder
    ): PurchaseOrder {
        if (
            $user->role !==
            'warehouse_coordinator'
        ) {
            throw ValidationException::withMessages([
                'user' =>
                    'Only warehouse coordinators can complete purchase orders.',
            ]);
        }

        $this->authorizeWarehouseAssignment(
            $user,
            $purchaseOrder->warehouse_id
        );

        if (
            $purchaseOrder->status !==
            'delivered'
        ) {
            throw ValidationException::withMessages([
                'status' =>
                    'Only delivered purchase orders can be completed.',
            ]);
        }

        return DB::transaction(
            function () use ($user, $purchaseOrder) {
                $purchaseOrder =
                    PurchaseOrder::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $purchaseOrder->id
                        );

                if (
                    $purchaseOrder->status !==
                    'delivered'
                ) {
                    throw ValidationException::withMessages([
                        'status' =>
                            'Only delivered purchase orders can be completed.',
                    ]);
                }

                $purchaseOrder->update([
                    'status' =>
                        'completed',
                ]);

                $purchaseOrder->load([
                    'branch',
                    'warehouse',
                ]);

                $this->activityLogService->log(
                    user: $user,
                    action: 'completed',
                    module: 'purchase_order',
                    description:
                    'Completed purchase order ' .
                    $purchaseOrder->reference_number .
                    '.',
                    branch:
                    $purchaseOrder->branch,
                    warehouse:
                    $purchaseOrder->warehouse,
                    subject:
                    $purchaseOrder,
                    oldValues: [
                        'status' => 'delivered',
                    ],
                    newValues: [
                        'status' => 'completed',
                    ],
                );

                return $this->appendDeliveryPhotoUrl(
                    $purchaseOrder->fresh([
                        'branch',
                        'warehouse',
                        'creator',
                        'approver',
                        'items.product',
                    ])
                );
            }
        );
    }

    private function applyAdminFilters(
        $query,
        array $filters
    ): void {
        if (!empty($filters['branch_id'])) {
            $query->where(
                'branch_id',
                $filters['branch_id']
            );
        }

        if (!empty($filters['warehouse_id'])) {
            $query->where(
                'warehouse_id',
                $filters['warehouse_id']
            );
        }
    }

    private function authorizeBranchAssignment(
        User $user,
        int $branchId
    ): void {
        if (
            $this->userHasBranchAssignment(
                $user,
                $branchId
            )
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'branch_id' =>
                'You are not authorized to access this branch.',
        ]);
    }

    private function authorizeWarehouseAssignment(
        User $user,
        int $warehouseId
    ): void {
        if (
            $this->userHasWarehouseAssignment(
                $user,
                $warehouseId
            )
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'warehouse_id' =>
                'You are not authorized to access this warehouse.',
        ]);
    }

    private function userHasBranchAssignment(
        User $user,
        int $branchId
    ): bool {
        if (
            $user->assignedBranches()
                ->whereKey($branchId)
                ->exists()
        ) {
            return true;
        }

        return $user->branch_id === $branchId;
    }

    private function userHasWarehouseAssignment(
        User $user,
        int $warehouseId
    ): bool {
        if (
            $user->assignedWarehouses()
                ->whereKey($warehouseId)
                ->exists()
        ) {
            return true;
        }

        return $user->warehouse_id ===
            $warehouseId;
    }

    private function getDefaultBranchId(
        User $user
    ): int {
        if (
            $user->branch_id !== null &&
            $this->userHasBranchAssignment(
                $user,
                $user->branch_id
            )
        ) {
            return (int) $user->branch_id;
        }

        $branchId =
            $user->assignedBranches()
                ->value('branches.id');

        if ($branchId !== null) {
            return (int) $branchId;
        }

        throw ValidationException::withMessages([
            'branch_id' =>
                'No branch assignment is available for this user.',
        ]);
    }

    private function getDefaultWarehouseId(
        User $user
    ): int {
        if (
            $user->warehouse_id !== null &&
            $this->userHasWarehouseAssignment(
                $user,
                $user->warehouse_id
            )
        ) {
            return (int) $user->warehouse_id;
        }

        $warehouseId =
            $user->assignedWarehouses()
                ->value('warehouses.id');

        if ($warehouseId !== null) {
            return (int) $warehouseId;
        }

        throw ValidationException::withMessages([
            'warehouse_id' =>
                'No warehouse assignment is available for this user.',
        ]);
    }

    private function appendDeliveryPhotoUrl(
        PurchaseOrder $purchaseOrder
    ): PurchaseOrder {
        $purchaseOrder->setAttribute(
            'delivery_photo_url',
            $purchaseOrder->delivery_photo_path
            ? Storage::disk('public')->url(
                $purchaseOrder->delivery_photo_path
            )
            : null
        );

        return $purchaseOrder;
    }

    private function appendDeliveryPhotoUrls(
        Collection $purchaseOrders
    ): Collection {
        return $purchaseOrders->each(
            function (PurchaseOrder $purchaseOrder) {
                $this->appendDeliveryPhotoUrl(
                    $purchaseOrder
                );

                $this->appendDeliveryAttachmentUrls(
                    $purchaseOrder
                );
            }
        );
    }

    private function generateReferenceNumber(): string
    {
        do {
            $referenceNumber =
                'PO-' .
                now()->format(
                    'YmdHis'
                ) .
                '-' .
                Str::upper(
                    Str::random(4)
                );
        } while (
            PurchaseOrder::query()
                ->where(
                    'reference_number',
                    $referenceNumber
                )
                ->exists()
        );

        return $referenceNumber;
    }

    public function delete(
        User $user,
        PurchaseOrder $purchaseOrder
    ): void {
        if ($user->role !== 'admin') {
            throw ValidationException::withMessages([
                'user' =>
                    'Only administrators can delete purchase orders.',
            ]);
        }

        DB::transaction(function () use ($user, $purchaseOrder) {
            $purchaseOrder = PurchaseOrder::query()
                ->with([
                    'branch',
                    'warehouse',
                ])
                ->lockForUpdate()
                ->findOrFail($purchaseOrder->id);

            $allowedStatuses = [
                'approved',
                'preparing',
                'rejected',
                'cancelled',
            ];

            if (
                !in_array(
                    $purchaseOrder->status,
                    $allowedStatuses,
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'status' =>
                        'This purchase order cannot be deleted because it has entered delivery or has already affected inventory.',
                ]);
            }

            $this->activityLogService->log(
                user: $user,
                action: 'deleted',
                module: 'purchase_order',
                description:
                'Deleted purchase order ' .
                $purchaseOrder->reference_number . '.',
                branch: $purchaseOrder->branch,
                warehouse: $purchaseOrder->warehouse,
                subject: $purchaseOrder,
                oldValues: [
                    'reference_number' =>
                        $purchaseOrder->reference_number,
                    'status' =>
                        $purchaseOrder->status,
                ],
            );

            $purchaseOrder->delete();
        });
    }
}

