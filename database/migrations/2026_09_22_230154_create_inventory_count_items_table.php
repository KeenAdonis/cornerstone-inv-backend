<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_count_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_count_id')
                ->constrained('inventory_counts')
                ->cascadeOnDelete();

            $table->foreignId('inventory_id')
                ->constrained('inventories')
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->decimal('system_quantity', 12, 2);

            $table->decimal('counted_quantity', 12, 2);

            $table->decimal('variance', 12, 2);

            $table->timestamps();

            $table->unique(
                ['inventory_count_id', 'inventory_id'],
                'inventory_count_items_count_inventory_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_count_items');
    }
};