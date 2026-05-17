<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Auto-transition a project to `delayed` when its expected_end_date has
 * passed without the project reaching a terminal state.
 *
 * Rules:
 *  - Only projects whose state is in an "active execution" bucket are
 *    considered: {ready_for_execution, in_execution, pending_documentation}.
 *    Readiness states (new / pending_readiness) are NEVER auto-delayed — the
 *    readiness step has its own rejection flow (spec #10).
 *  - Terminal states (completed, closed, archived) and already-delayed
 *    projects are skipped.
 *  - Notifies: system_admin + project_executor + enhancer_entry_country
 *    (the country-scoped enhancer is notified via role only; scoping is
 *    handled when the notification is rendered per-user).
 *
 * Idempotent — safe to run on every request via kick(), cron, or manually.
 */
class ProjectAutoDelay
{
    private const ACTIVE_STATES = [
        'ready_for_execution',
        'in_execution',
        'pending_documentation',
    ];

    /**
     * Scan all projects and delay the overdue ones. Returns count delayed.
     */
    public static function run(): int
    {
        $today = Carbon::today();
        $count = 0;

        Project::query()
            ->whereIn('state', self::ACTIVE_STATES)
            ->whereNotNull('expected_end_date')
            ->whereDate('expected_end_date', '<', $today)
            ->cursor()
            ->each(function (Project $project) use (&$count): void {
                if (self::delay($project)) {
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Throttled wrapper for run() — invokes run() at most once per day so it
     * is safe to call on every admin request without thrashing the DB.
     */
    public static function kick(): void
    {
        Cache::remember('project_auto_delay:last_run', now()->addHours(6), function () {
            try {
                self::run();
            } catch (\Throwable $e) {
                report($e);
            }
            return now()->toIso8601String();
        });
    }

    private static function delay(Project $project): bool
    {
        $previous = $project->state;

        DB::transaction(function () use ($project): void {
            $project->stateChangeNote = 'تحويل تلقائي لمتأخر — تجاوز تاريخ الانتهاء المتوقع';
            $project->update(['state' => 'delayed']);
        });

        ActivityLogger::log('project.auto_delayed', 'تحويل تلقائي لمتأخر', $project, [
            'from' => $previous,
            'to' => 'delayed',
            'expected_end_date' => optional($project->expected_end_date)->toDateString(),
        ]);

        // Notify system_admin + project_executor + enhancer_entry_country
        // (dedicated alert, independent of the generic close/complete flows).
        InternalNotifier::notifyRoles(
            ['system_admin', 'project_executor', 'enhancer_entry_country'],
            'notifications.project.auto_delayed.title',
            'notifications.project.auto_delayed.body',
            [
                'project' => $project->project_number ?? ('#' . $project->id),
                'title' => $project->title ?? '',
                'expected_end_date' => optional($project->expected_end_date)->toDateString() ?? '',
            ]
        );

        return true;
    }
}
