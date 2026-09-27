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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->foreignId('purchase_order_id')
                ->nullable()
                ->constrained('purchase_orders')
                ->restrictOnDelete();

            $table->enum('movement_type', [
                'stock_in',
                'stock_out',
                'transfer',
                'adjustment',
            ]);

            $table->decimal('quantity', 12, 2);

            $table->foreignId('from_warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->restrictOnDelete();

            $table->foreignId('from_branch_id')
                ->nullable()
                ->constrained('branches')
                ->restrictOnDelete();

            $table->foreignId('to_warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->restrictOnDelete();

            $table->foreignId('to_branch_id')
                ->nullable()
                ->constrained('branches')
                ->restrictOnDelete();

            $table->dateTime('moved_at');

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};