<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /*
         * Step 1:
         * Temporarily allow both the old and new
         * status values so existing records remain valid.
         */
        DB::statement("
            ALTER TABLE purchase_orders
            MODIFY COLUMN status ENUM(
                'pending',
                'approved',
                'rejected',
                'processing',
                'preparing',
                'released',
                'out_for_delivery',
                'delivered',
                'completed',
                'cancelled'
            ) NOT NULL DEFAULT 'pending'
        ");

        /*
         * Step 2:
         * Convert existing records to the new statuses.
         */
        DB::table('purchase_orders')
            ->where('status', 'processing')
            ->update([
                'status' => 'preparing',
            ]);

        DB::table('purchase_orders')
            ->where('status', 'released')
            ->update([
                'status' => 'out_for_delivery',
            ]);

        /*
         * Step 3:
         * Remove the old status values now that
         * no records should be using them.
         */
        DB::statement("
            ALTER TABLE purchase_orders
            MODIFY COLUMN status ENUM(
                'pending',
                'approved',
                'rejected',
                'preparing',
                'out_for_delivery',
                'delivered',
                'completed',
                'cancelled'
            ) NOT NULL DEFAULT 'pending'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /*
         * Step 1:
         * Temporarily allow both old and new statuses.
         */
        DB::statement("
            ALTER TABLE purchase_orders
            MODIFY COLUMN status ENUM(
                'pending',
                'approved',
                'rejected',
                'processing',
                'preparing',
                'released',
                'out_for_delivery',
                'delivered',
                'completed',
                'cancelled'
            ) NOT NULL DEFAULT 'pending'
        ");

        /*
         * Step 2:
         * Convert new statuses back to the old statuses.
         */
        DB::table('purchase_orders')
            ->where('status', 'preparing')
            ->update([
                'status' => 'processing',
            ]);

        DB::table('purchase_orders')
            ->where('status', 'out_for_delivery')
            ->update([
                'status' => 'released',
            ]);

        DB::table('purchase_orders')
            ->where('status', 'delivered')
            ->update([
                'status' => 'released',
            ]);

        /*
         * Step 3:
         * Restore the original enum.
         */
        DB::statement("
            ALTER TABLE purchase_orders
            MODIFY COLUMN status ENUM(
                'pending',
                'approved',
                'rejected',
                'processing',
                'released',
                'completed',
                'cancelled'
            ) NOT NULL DEFAULT 'pending'
        ");
    }
};