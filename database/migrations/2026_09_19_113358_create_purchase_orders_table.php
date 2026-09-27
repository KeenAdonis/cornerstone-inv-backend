<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();

            $table->string('reference_number')
                ->unique();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->restrictOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'processing',
                'released',
                'completed',
                'cancelled',
            ])->default('pending');

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->dateTime('approved_at')
                ->nullable();

            $table->text('rejection_reason')
                ->nullable();

            $table->dateTime('requested_at');

            $table->text('notes')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};