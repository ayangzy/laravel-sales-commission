<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('sales-commission.tables.payouts', 'payouts'), function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('period'); // e.g., '2024-01', '2024-W05'
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->integer('total_earnings_count')->default(0);
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('payment_method')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['period', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('sales-commission.tables.payouts', 'payouts'));
    }
};
