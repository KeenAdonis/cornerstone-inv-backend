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
        Schema::table(
            'stock_adjustments',
            function (Blueprint $table) {
                $table->dropForeign([
                    'warehouse_id',
                ]);

                $table->foreignId(
                    'branch_id'
                )
                    ->nullable()
                    ->after('warehouse_id')
                    ->constrained('branches')
                    ->restrictOnDelete();

                $table->foreignId(
                    'warehouse_id'
                )
                    ->nullable()
                    ->change();

                $table->foreign(
                    'warehouse_id'
                )
                    ->references('id')
                    ->on('warehouses')
                    ->restrictOnDelete();
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(
            'stock_adjustments',
            function (Blueprint $table) {
                $table->dropForeign([
                    'warehouse_id',
                ]);

                $table->dropForeign([
                    'branch_id',
                ]);

                $table->dropColumn(
                    'branch_id'
                );

                $table->foreignId(
                    'warehouse_id'
                )
                    ->nullable(false)
                    ->change();

                $table->foreign(
                    'warehouse_id'
                )
                    ->references('id')
                    ->on('warehouses')
                    ->restrictOnDelete();
            }
        );
    }
};