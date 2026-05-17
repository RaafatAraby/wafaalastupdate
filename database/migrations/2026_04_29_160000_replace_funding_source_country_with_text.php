<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Replaces funding_source_country_id (FK to countries) with a free-form
 * funding_source_country string (ISO 3166-1 alpha-2). The dropdown on
 * the form now shows the full world country list, which is too broad to
 * keep in sync with the local countries table.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table): void {
            if (! Schema::hasColumn('financial_transactions', 'funding_source_country')) {
                $table->string('funding_source_country', 2)->nullable()->after('transaction_type');
            }
        });

        // Drop the old FK/index/column if it exists (it was added in a
        // previous migration in the same PR).
        if (Schema::hasColumn('financial_transactions', 'funding_source_country_id')) {
            Schema::table('financial_transactions', function (Blueprint $table): void {
                try {
                    $table->dropIndex(['funding_source_country_id']);
                } catch (\Throwable $e) {
                    // index may not exist — ignore
                }
                $table->dropColumn('funding_source_country_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('financial_transactions', 'funding_source_country')) {
            Schema::table('financial_transactions', function (Blueprint $table): void {
                $table->dropColumn('funding_source_country');
            });
        }

        if (! Schema::hasColumn('financial_transactions', 'funding_source_country_id')) {
            Schema::table('financial_transactions', function (Blueprint $table): void {
                $table->unsignedBigInteger('funding_source_country_id')->nullable()->after('transaction_type');
                $table->index('funding_source_country_id');
            });
        }
    }
};
