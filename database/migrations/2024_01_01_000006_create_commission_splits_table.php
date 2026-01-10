<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('sales-commission.tables.commission_splits', 'commission_splits'), function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('earning_id')
                ->constrained(config('sales-commission.tables.commission_earnings', 'commission_earnings'))
                ->cascadeOnDelete();
            
            // Polymorphic relationship to receiving agent
            $table->string('agent_type');
            $table->ulid('agent_id');
            
            $table->decimal('split_percentage', 5, 2);
            $table->decimal('split_amount', 15, 2);
            $table->string('role')->nullable(); // primary, support, manager, referrer
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['agent_type', 'agent_id']);
            $table->index('earning_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('sales-commission.tables.commission_splits', 'commission_splits'));
    }
};
