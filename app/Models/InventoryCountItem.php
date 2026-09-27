<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryCountItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_count_id',
        'inventory_id',
        'product_id',
        'system_quantity',
        'counted_quantity',
        'variance',
    ];

    protected $casts = [
        'system_quantity' => 'decimal:2',
        'counted_quantity' => 'decimal:2',
        'variance' => 'decimal:2',
    ];

    public function inventoryCount(): BelongsTo
    {
        return $this->belongsTo(
            InventoryCount::class
        );
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(
            Inventory::class
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class
        );
    }
}