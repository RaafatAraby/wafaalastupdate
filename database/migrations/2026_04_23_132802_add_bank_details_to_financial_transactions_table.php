<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            // إضافة عمود bank_name فقط إذا لم يكن موجوداً
            if (!Schema::hasColumn('financial_transactions', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('transfer_method');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('financial_transactions', 'bank_name')) {
                $table->dropColumn('bank_name');
            }
        });
    }
};