<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('delivery_type')
                ->nullable()
                ->after('status');

            $table->date('ship_out_date')
                ->nullable()
                ->after('delivery_type');

            $table->date('date_of_arrival')
                ->nullable()
                ->after('ship_out_date');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_type',
                'ship_out_date',
                'date_of_arrival',
            ]);
        });
    }
};