<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('sales-commission.tables.commission_tiers', 'commission_tiers'), function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('plan_id')
                ->constrained(config('sales-commission.tables.commission_plans', 'commission_plans'))
                ->cascadeOnDelete();
            $table->string('name');
            $table->decimal('min_threshold', 15, 2)->default(0);
            $table->decimal('max_threshold', 15, 2)->nullable();
            $table->decimal('rate', 8, 4);
            $table->string('rate_type')->default('percentage'); // percentage, fixed
            $table->decimal('bonus_amount', 15, 2)->nullable();
            $table->text('description')->nullable();
            $table->string('color')->nullable(); // For UI display
            $table->timestamps();

            $table->index(['plan_id', 'min_threshold', 'max_threshold']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('sales-commission.tables.commission_tiers', 'commission_tiers'));
    }
};
