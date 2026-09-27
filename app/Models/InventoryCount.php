<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryCount extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'counted_by',
        'counted_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'counted_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function countedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'counted_by'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            InventoryCountItem::class
        );
    }
}