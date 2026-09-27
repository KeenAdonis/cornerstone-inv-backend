<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustmentItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_adjustment_id',
        'product_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function stockAdjustment(): BelongsTo
    {
        return $this->belongsTo(
            StockAdjustment::class
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class
        );
    }
}