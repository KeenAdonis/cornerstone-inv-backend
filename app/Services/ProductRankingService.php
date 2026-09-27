<?php

namespace App\Services;

use App\Models\PurchaseOrderItem;
use Illuminate\Support\Collection;

class ProductRankingService
{
    /**
     * Get product demand ranking based on
     * purchase order quantities.
     *
     * Only approved purchase orders and later
     * workflow statuses are included.
     */
    public function getRanking(
        array $filters = []
    ): Collection {
        $query = PurchaseOrderItem::query()
            ->selectRaw(
                'product_id, SUM(quantity) as total_quantity'
            )
            ->with([
                'product:id,name,sku,unit',
            ])
            ->whereHas(
                'purchaseOrder',
                function ($purchaseOrderQuery) use ($filters) {
                    $purchaseOrderQuery->whereIn(
                        'status',
                        [
                            'approved',
                            'preparing',
                            'out_for_delivery',
                            'delivered',
                            'completed',
                        ]
                    );

                    if (
                        !empty(
                            $filters['branch_id']
                        )
                    ) {
                        $purchaseOrderQuery->where(
                            'branch_id',
                            $filters['branch_id']
                        );
                    }

                    if (
                        !empty(
                            $filters['warehouse_id']
                        )
                    ) {
                        $purchaseOrderQuery->where(
                            'warehouse_id',
                            $filters['warehouse_id']
                        );
                    }

                    if (
                        !empty(
                            $filters['date_from']
                        )
                    ) {
                        $purchaseOrderQuery->whereDate(
                            'requested_at',
                            '>=',
                            $filters['date_from']
                        );
                    }

                    if (
                        !empty(
                            $filters['date_to']
                        )
                    ) {
                        $purchaseOrderQuery->whereDate(
                            'requested_at',
                            '<=',
                            $filters['date_to']
                        );
                    }
                }
            )
            ->groupBy('product_id')
            ->orderByDesc('total_quantity');

        return $query
            ->get()
            ->values()
            ->map(
                function (
                    PurchaseOrderItem $item,
                    int $index
                ) {
                    return [
                        'rank' =>
                            $index + 1,

                        'product_id' =>
                            $item->product_id,

                        'product_name' =>
                            $item->product?->name,

                        'sku' =>
                            $item->product?->sku,

                        'unit' =>
                            $item->product?->unit,

                        'total_quantity' =>
                            (float) $item->total_quantity,
                    ];
                }
            );
    }
}