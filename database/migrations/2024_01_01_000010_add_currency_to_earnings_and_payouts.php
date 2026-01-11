<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $earningsTable = config('sales-commission.tables.commission_earnings', 'commission_earnings');
        $payoutsTable = config('sales-commission.tables.payouts', 'payouts');

        // Add currency to commission_earnings
        Schema::table($earningsTable, function (Blueprint $table) {
            $table->string('currency', 3)->default('USD')->after('rate_type');
        });

        // Add currency to payouts
        Schema::table($payoutsTable, function (Blueprint $table) {
            $table->string('currency', 3)->default('USD')->after('total_earnings_count');
        });

        // Backfill existing records with default currency
        $defaultCurrency = config('sales-commission.currency', 'USD');
        
        DB::table($earningsTable)->whereNull('currency')->orWhere('currency', '')->update([
            'currency' => $defaultCurrency,
        ]);
        
        DB::table($payoutsTable)->whereNull('currency')->orWhere('currency', '')->update([
            'currency' => $defaultCurrency,
        ]);
    }

    public function down(): void
    {
        $earningsTable = config('sales-commission.tables.commission_earnings', 'commission_earnings');
        $payoutsTable = config('sales-commission.tables.payouts', 'payouts');

        Schema::table($earningsTable, function (Blueprint $table) {
            $table->dropColumn('currency');
        });

        Schema::table($payoutsTable, function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
