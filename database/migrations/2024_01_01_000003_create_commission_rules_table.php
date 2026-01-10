<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('sales-commission.tables.commission_rules', 'commission_rules'), function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('plan_id')
                ->constrained(config('sales-commission.tables.commission_plans', 'commission_plans'))
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // percentage, fixed, tiered, bonus_threshold, custom
            $table->decimal('value', 15, 4)->nullable();
            $table->json('conditions')->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['plan_id', 'is_active', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('sales-commission.tables.commission_rules', 'commission_rules'));
    }
};
