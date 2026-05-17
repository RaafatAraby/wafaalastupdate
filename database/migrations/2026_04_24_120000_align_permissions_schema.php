<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Permissions overhaul:
     *  - Snapshot the legacy plain-text `users.role` values into a backup
     *    table (`users_legacy_role_backup`) before dropping the column, so no
     *    data is lost and the deployer can reassign manually if needed.
     *  - Drop the legacy plain-text `users.role` column (Spatie role tables
     *    are now the single source of truth).
     *  - Add `final_report_*` columns to `projects` so close/approve workflows
     *    can be enforced at the database level (existing ProjectPolicy::close
     *    already reads `final_report_approved` defensively via
     *    Schema::hasColumn).
     */
    public function up(): void
    {
        // 1) Snapshot legacy users.role values so they're never lost.
        if (Schema::hasColumn('users', 'role')) {
            if (! Schema::hasTable('users_legacy_role_backup')) {
                Schema::create('users_legacy_role_backup', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                    $table->string('legacy_role', 100)->nullable();
                    $table->timestamp('snapshotted_at')->useCurrent();
                });
            }

            $rows = DB::table('users')
                ->select('id', 'role')
                ->whereNotNull('role')
                ->get();

            foreach ($rows as $row) {
                DB::table('users_legacy_role_backup')->updateOrInsert(
                    ['user_id' => $row->id],
                    [
                        'legacy_role' => $row->role,
                        'snapshotted_at' => now(),
                    ]
                );
            }

            // 2) Drop the legacy column.
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }

        // 3) Add project-level columns required by the new approval workflow.
        Schema::table('projects', function (Blueprint $table) {
            if (! Schema::hasColumn('projects', 'final_report_approved')) {
                $table->boolean('final_report_approved')->default(false)->after('final_report');
            }
            if (! Schema::hasColumn('projects', 'final_report_approved_by')) {
                $table->foreignId('final_report_approved_by')
                    ->nullable()
                    ->after('final_report_approved')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('projects', 'final_report_approved_at')) {
                $table->timestamp('final_report_approved_at')->nullable()->after('final_report_approved_by');
            }
            if (! Schema::hasColumn('projects', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('archived_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'final_report_approved_by')) {
                $table->dropConstrainedForeignId('final_report_approved_by');
            }
            foreach (['final_report_approved', 'final_report_approved_at', 'is_archived'] as $column) {
                if (Schema::hasColumn('projects', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 50)->default('viewer')->after('department_id');
            }
        });

        // Restore legacy role values from the backup if it exists.
        if (Schema::hasColumn('users', 'role') && Schema::hasTable('users_legacy_role_backup')) {
            $rows = DB::table('users_legacy_role_backup')->get();
            foreach ($rows as $row) {
                if ($row->legacy_role !== null) {
                    DB::table('users')
                        ->where('id', $row->user_id)
                        ->update(['role' => $row->legacy_role]);
                }
            }
        }

        Schema::dropIfExists('users_legacy_role_backup');
    }
};
