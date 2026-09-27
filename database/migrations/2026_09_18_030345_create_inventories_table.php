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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->restrictOnDelete();

            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->restrictOnDelete();

            $table->decimal('quantity', 12, 2)->default(0);

            $table->timestamps();

            $table->unique(
                ['product_id', 'warehouse_id'],
                'inventories_product_warehouse_unique'
            );

            $table->unique(
                ['product_id', 'branch_id'],
                'inventories_product_branch_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};