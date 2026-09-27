<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\StockIn;
use App\Models\StockMovement;
use App\Models\User;

use App\Services\ActivityLogService;

use Illuminate\Database\Eloquent\Collection;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Str;

use Illuminate\Validation\ValidationException;

class StockInService
{
    public function __construct(
        private ActivityLogService $activityLogService
    ) {
    }
    /**
     * Get stock-in transactions accessible to the user.
     */
    public function getStockInsForUser(User $user): Collection
    {
        $query = StockIn::query()
            ->with([
                'warehouse',
                'creator',
                'items.product',
            ])
            ->latest();

        if ($user->role === 'warehouse_coordinator') {
            if (!$user->warehouse_id) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'The user is not assigned to a warehouse.',
                ]);
            }

            return $query
                ->where('warehouse_id', $user->warehouse_id)
                ->get();
        }

        if ($user->role === 'admin') {
            return $query->get();
        }

        throw ValidationException::withMessages([
            'user' => 'You are not authorized to view stock-in transactions.',
        ]);
    }

    /**
     * Create a stock-in transaction and update warehouse inventory.
     *
     * @param  array{
     *     received_at: string,
     *     notes?: string|null,
     *     items: array<int, array{
     *         product_id: int,
     *         quantity: numeric
     *     }>
     * }  $data
     */
    public function create(User $user, array $data): StockIn
    {
        if ($user->role !== 'warehouse_coordinator') {
            throw ValidationException::withMessages([
                'user' => 'Only warehouse coordinators can create stock-in transactions.',
            ]);
        }

        if (!$user->warehouse_id) {
            throw ValidationException::withMessages([
                'warehouse_id' => 'The user is not assigned to a warehouse.',
            ]);
        }

        return DB::transaction(function () use ($user, $data) {
            $stockIn = StockIn::create([
                'warehouse_id' => $user->warehouse_id,
                'reference_number' => $this->generateReferenceNumber(),
                'received_at' => $data['received_at'],
                'created_by' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $stockIn->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);

                $inventory = Inventory::query()
                    ->where('product_id', $item['product_id'])
                    ->where('warehouse_id', $user->warehouse_id)
                    ->lockForUpdate()
                    ->first();

                if ($inventory) {
                    $inventory->increment(
                        'quantity',
                        $item['quantity']
                    );
                } else {
                    Inventory::create([
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $user->warehouse_id,
                        'branch_id' => null,
                        'quantity' => $item['quantity'],
                    ]);
                }

                StockMovement::create([
                    'product_id' => $item['product_id'],
                    'purchase_order_id' => null,
                    'movement_type' => 'stock_in',
                    'quantity' => $item['quantity'],
                    'from_warehouse_id' => null,
                    'from_branch_id' => null,
                    'to_warehouse_id' => $user->warehouse_id,
                    'to_branch_id' => null,
                    'moved_at' => $data['received_at'],
                    'created_by' => $user->id,
                    'notes' => $data['notes'] ?? null,
                ]);
            }

            $stockIn->load([
                'warehouse',
                'creator',
                'items.product',
            ]);

            $this->activityLogService->log(
                user: $user,
                action: 'created',
                module: 'stock_in',
                description:
                'Created stock-in transaction ' .
                $stockIn->reference_number .
                '.',
                warehouse:
                $stockIn->warehouse,
                subject:
                $stockIn,
                newValues: [
                    'received_at' =>
                        $stockIn->received_at,
                    'items' =>
                        $stockIn->items
                            ->map(
                                fn($item) => [
                                    'product_id' =>
                                        $item->product_id,
                                    'quantity' =>
                                        $item->quantity,
                                ]
                            )
                            ->values()
                            ->all(),
                ],
            );

            return $stockIn;
        });
    }

    private function generateReferenceNumber(): string
    {
        do {
            $referenceNumber =
                'SI-' .
                now()->format('YmdHis') .
                '-' .
                Str::upper(Str::random(4));
        } while (
            StockIn::query()
                ->where(
                    'reference_number',
                    $referenceNumber
                )
                ->exists()
        );

        return $referenceNumber;
    }
}