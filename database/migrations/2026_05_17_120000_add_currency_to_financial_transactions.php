<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add multi-currency support to financial transactions.
 *
 * Existing column `amount` keeps storing the USD value because USD is the
 * system's canonical currency for reporting / balance calculations. The
 * new columns record the value as originally entered by the user (e.g.
 * TRY, EUR) together with the exchange rate that was used at entry time,
 * so the form can always show both numbers without re-fetching rates.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('financial_transactions', 'currency')) {
                $table->string('currency', 3)->default('USD')->after('amount');
            }

            if (! Schema::hasColumn('financial_transactions', 'original_amount')) {
                $table->decimal('original_amount', 14, 2)->nullable()->after('currency');
            }

            if (! Schema::hasColumn('financial_transactions', 'exchange_rate')) {
                $table->decimal('exchange_rate', 14, 6)->nullable()->after('original_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            foreach (['exchange_rate', 'original_amount', 'currency'] as $column) {
                if (Schema::hasColumn('financial_transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
