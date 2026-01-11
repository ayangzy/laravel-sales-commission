<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('sales-commission.tables.commission_earnings', 'commission_earnings'), function (Blueprint $table) {
            $table->ulid('id')->primary();
            
            // Polymorphic relationship to agent (User, SalesRep, etc.)
            $table->string('agent_type');
            $table->ulid('agent_id');
            
            // Polymorphic relationship to commissionable (Order, Sale, etc.)
            $table->string('commissionable_type');
            $table->ulid('commissionable_id');
            
            // Plan and tier references
            $table->foreignUlid('plan_id')
                ->nullable()
                ->constrained(config('sales-commission.tables.commission_plans', 'commission_plans'))
                ->nullOnDelete();
            $table->foreignUlid('tier_id')
                ->nullable()
                ->constrained(config('sales-commission.tables.commission_tiers', 'commission_tiers'))
                ->nullOnDelete();
            $table->foreignUlid('rule_id')
                ->nullable()
                ->constrained(config('sales-commission.tables.commission_rules', 'commission_rules'))
                ->nullOnDelete();
            
            // Amounts
            $table->decimal('base_amount', 15, 2);
            $table->decimal('commission_amount', 15, 2);
            $table->decimal('rate', 8, 4)->nullable(); // Commission rate applied
            $table->string('rate_type')->nullable(); // percentage, fixed
            $table->string('currency')->nullable(); // Currency code (ISO 4217) - optional, system uses config currency
            
            // Status and lifecycle
            $table->string('status')->default('pending');
            $table->string('period')->nullable()->index(); // YYYY-MM format for period tracking
            $table->timestamp('earned_at');
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            
            // Payout reference
            $table->foreignUlid('payout_id')
                ->nullable()
                ->constrained(config('sales-commission.tables.payouts', 'payouts'))
                ->nullOnDelete();
            
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['agent_type', 'agent_id']);
            $table->index(['commissionable_type', 'commissionable_id']);
            $table->index(['status', 'earned_at']);
            $table->index(['payout_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('sales-commission.tables.commission_earnings', 'commission_earnings'));
    }
};
