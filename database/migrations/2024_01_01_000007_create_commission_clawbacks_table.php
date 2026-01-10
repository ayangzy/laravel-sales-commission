<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('sales-commission.tables.commission_clawbacks', 'commission_clawbacks'), function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('earning_id')
                ->constrained(config('sales-commission.tables.commission_earnings', 'commission_earnings'))
                ->cascadeOnDelete();
            $table->string('reason'); // refund, chargeback, cancellation, manual
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['earning_id', 'reason']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('sales-commission.tables.commission_clawbacks', 'commission_clawbacks'));
    }
};
