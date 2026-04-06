<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('organizations', 'organization_code')) {
            return;
        }

        Schema::table('organizations', function (Blueprint $table) {
            $table->string('organization_code', 50)->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('organizations', 'organization_code')) {
            return;
        }

        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('organization_code');
        });
    }
};
