<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The UI exposes a `documentation_type` dropdown (one_time / continuous /
 * periodic) but the original create_projects_table migration never added
 * the matching column. Eloquent silently dropped the value on save, so
 * the ProjectObserver's auto-sync of documentation_status never had a
 * type to match on.
 */
return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('projects')) {
            return;
        }

        if (! Schema::hasColumn('projects', 'documentation_type')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->enum('documentation_type', ['one_time', 'continuous', 'periodic'])
                    ->nullable()
                    ->after('documentation_status');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('projects')) {
            return;
        }

        if (Schema::hasColumn('projects', 'documentation_type')) {
            Schema::table('projects', function (Blueprint $table): void {
                $table->dropColumn('documentation_type');
            });
        }
    }
};
