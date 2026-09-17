<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the optional `percentage` column to `project_payments` so the
 * user can describe each instalment as a share of the total approved
 * project amount (e.g. 50% first instalment, 50% second instalment).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('project_payments', 'percentage')) {
                $table->decimal('percentage', 5, 2)->nullable()->after('exchange_rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_payments', function (Blueprint $table) {
            if (Schema::hasColumn('project_payments', 'percentage')) {
                $table->dropColumn('percentage');
            }
        });
    }
};
