<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_order_id')
                ->constrained('purchase_orders')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->decimal('quantity', 12, 2);

            $table->timestamps();

            $table->unique(
                ['purchase_order_id', 'product_id'],
                'purchase_order_items_order_product_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};