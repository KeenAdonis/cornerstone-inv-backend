<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_in_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stock_in_id')
                ->constrained('stock_ins')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->decimal('quantity', 12, 2);

            $table->timestamps();

            $table->unique(
                ['stock_in_id', 'product_id'],
                'stock_in_items_stock_in_product_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_in_items');
    }
};