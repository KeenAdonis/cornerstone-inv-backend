<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'purchase_order_id',
        'movement_type',
        'quantity',
        'from_warehouse_id',
        'from_branch_id',
        'to_warehouse_id',
        'to_branch_id',
        'moved_at',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'moved_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(
            PurchaseOrder::class
        );
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(
            Warehouse::class,
            'from_warehouse_id'
        );
    }

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(
            Branch::class,
            'from_branch_id'
        );
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(
            Warehouse::class,
            'to_warehouse_id'
        );
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(
            Branch::class,
            'to_branch_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }
}