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
        Schema::table('inventories', function (Blueprint $table) {
            $table->decimal('par_level', 12, 2)
                ->default(0)
                ->after('quantity');

            $table->decimal('reorder_level', 12, 2)
                ->default(0)
                ->after('par_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn([
                'par_level',
                'reorder_level',
            ]);
        });
    }
};