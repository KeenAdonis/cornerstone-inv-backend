<?php

namespace App\Services;

use App\Models\PurchaseOrderItem;

use Illuminate\Support\Collection;

class DashboardAnalyticsService
{
    /**
     * Get the top products based on quantities
     * requested through completed purchase orders.
     *
     * This represents product demand based on
     * completed PO activity, not actual sales.
     */
    public function getProductDemandRanking(
        int $limit = 10
    ): Collection {
        return PurchaseOrderItem::query()
            ->selectRaw(
                'product_id, SUM(quantity) as total_quantity'
            )
            ->whereHas(
                'purchaseOrder',
                function ($query) {
                    $query->where(
                        'status',
                        'completed'
                    );
                }
            )
            ->with([
                'product:id,name,sku,unit',
            ])
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get()
            ->map(
                function (
                    PurchaseOrderItem $item
                ) {
                    return [
                        'product_id' =>
                            $item->product_id,

                        'product_name' =>
                            $item->product?->name,

                        'sku' =>
                            $item->product?->sku,

                        'unit' =>
                            $item->product?->unit,

                        'quantity' =>
                            (float) $item->total_quantity,
                    ];
                }
            )
            ->values();
    }
}