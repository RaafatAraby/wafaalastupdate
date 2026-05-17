<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('financial_transactions', 'funding_source_country_id')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('funding_source_country_id')->nullable()->after('transaction_type');
                $table->index('funding_source_country_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('financial_transactions', 'funding_source_country_id')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->dropIndex(['funding_source_country_id']);
                $table->dropColumn('funding_source_country_id');
            });
        }
    }
};
