<?php

namespace App\Models;

use App\Models\Branch;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\PurchaseOrderDeliveryAttachment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_number',
        'branch_id',
        'warehouse_id',
        'created_by',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'requested_at',
        'notes',
        'delivery_type',
        'tracking_number',
        'ship_out_date',
        'date_of_arrival',
        'delivery_photo_path',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'requested_at' => 'datetime',
        'ship_out_date' => 'date:Y-m-d',
        'date_of_arrival' => 'date:Y-m-d',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            PurchaseOrderItem::class
        );
    }

    public function deliveryAttachments(): HasMany
    {
        return $this->hasMany(
            PurchaseOrderDeliveryAttachment::class
        );
    }
}