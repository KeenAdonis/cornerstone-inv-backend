<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->restrictOnDelete();

            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->restrictOnDelete();

            $table->string('action', 100);

            $table->string('module', 100);

            $table->text('description');

            $table->nullableMorphs('subject');

            $table->json('old_values')
                ->nullable();

            $table->json('new_values')
                ->nullable();

            $table->ipAddress('ip_address')
                ->nullable();

            $table->text('user_agent')
                ->nullable();

            $table->timestamps();

            $table->index([
                'branch_id',
                'created_at',
            ]);

            $table->index([
                'warehouse_id',
                'created_at',
            ]);

            $table->index([
                'module',
                'created_at',
            ]);

            $table->index([
                'action',
                'created_at',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};