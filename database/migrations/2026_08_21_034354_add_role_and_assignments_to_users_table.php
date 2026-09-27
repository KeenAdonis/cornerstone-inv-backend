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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [
                'admin',
                'branch_coordinator',
                'warehouse_coordinator',
            ])->default('branch_coordinator');

            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();

            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete();

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['warehouse_id']);

            $table->dropColumn([
                'role',
                'branch_id',
                'warehouse_id',
                'status',
            ]);
        });
    }
};
