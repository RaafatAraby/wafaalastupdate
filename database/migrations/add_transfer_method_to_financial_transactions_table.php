<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('financial_transactions', 'transfer_method')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->string('transfer_method', 50)->nullable()->after('transaction_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('financial_transactions', 'transfer_method')) {
            Schema::table('financial_transactions', function (Blueprint $table) {
                $table->dropColumn('transfer_method');
            });
        }
    }
};
