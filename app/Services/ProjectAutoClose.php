<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Auto-complete a project when its closure conditions are met.
 *
 * Rules (per business spec):
 *  - INDIVIDUAL organization (entity_type = 'individual'):
 *      • photo_album_url OR video_album_url is filled (رابط التوثيق)
 *      • approved-balance == 0 (incoming - outgoing)
 *
 *  - INSTITUTIONAL organization (entity_type = 'institution'):
 *      • final_report_approved = true (اعتماد التقرير النهائي)
 *      • approved-balance == 0
 *
 * On match: state is set to 'completed' (مكتمل), a state-history row is
 * recorded, and a notification is fired.
 *
 * The check is idempotent (already-completed/closed projects are skipped)
 * and never reverts a project to an earlier state.
 */
class ProjectAutoClose
{
    /**
     * Evaluate a project's closure conditions and close it if met.
     * Safe to call repeatedly from observers.
     */
    public static function evaluate(?Project $project): void
    {
        if (! $project) {
            return;
        }

        // Skip if already completed/closed (terminal states).
        if (in_array((string) $project->state, ['completed', 'closed'], true)) {
            return;
        }

        // Only auto-complete projects that have reached a late-stage workflow
        // state. Early states (new, pending_readiness, ready_for_execution)
        // must never be silently completed even if the financial balance
        // happens to be zero — the project hasn't gone through execution yet.
        $closableStates = [
            'in_execution',
            'pending_documentation',
            'delayed',
        ];
        if (! in_array((string) $project->state, $closableStates, true)) {
            return;
        }

        $project->loadMissing('organization');

        $entityType = optional($project->organization)->entity_type;
        if (! $entityType) {
            return;
        }

        // Compute approved-balance: incoming - outgoing (approved only).
        $balance = static::computeApprovedBalance($project->id);

        // Balance must be exactly zero (after a small float tolerance).
        if (abs($balance) > 0.0001) {
            return;
        }

        // Don't complete while there are still un-approved (pending/rejected
        // /NULL-status) financial transactions on the project — even if the
        // *approved* balance is zero, those un-approved entries represent
        // unsettled intent and the project shouldn't snap completed under
        // them. Note: SQL `<> 'approved'` evaluates to NULL (not TRUE) for
        // NULL rows, so we OR-add a whereNull guard to also catch them.
        $hasUnsettled = DB::table('financial_transactions')
            ->where('project_id', $project->id)
            ->where(function ($q): void {
                $q->where('approval_status', '!=', 'approved')
                  ->orWhereNull('approval_status');
            })
            ->exists();
        if ($hasUnsettled) {
            return;
        }

        $shouldClose = false;

        if ($entityType === 'individual') {
            $hasDocLink = filled($project->photo_album_url)
                || filled($project->video_album_url);

            $shouldClose = $hasDocLink;
        } elseif ($entityType === 'institution') {
            $shouldClose = (bool) $project->final_report_approved;
        }

        if (! $shouldClose) {
            return;
        }

        // Capture the previous state BEFORE saveQuietly() — Laravel calls
        // syncOriginal() during save which would otherwise overwrite the
        // original attribute snapshot and make from_state == to_state.
        $previousState = (string) $project->state;

        $project->state = 'completed';
        if (Auth::id()) {
            $project->updated_by = Auth::id();
        }
        $project->saveQuietly(); // avoid recursive observer re-evaluation

        // Manually log + notify since we used saveQuietly.
        ActivityLogger::log(
            'project.auto_completed',
            'اكتمال تلقائي للمشروع وفق الشروط',
            $project,
            ['entity_type' => $entityType, 'from' => $previousState]
        );

        // Insert a state-history row so the audit trail is complete.
        DB::table('project_state_histories')->insert([
            'project_id' => $project->id,
            'from_state' => $previousState,
            'to_state' => 'completed',
            'changed_by' => Auth::id() ?? $project->updated_by ?? $project->created_by,
            'notes' => $entityType === 'individual'
                ? 'اكتمال تلقائي: تم إضافة رابط التوثيق + الرصيد المالي = 0'
                : 'اكتمال تلقائي: التقرير النهائي معتمد + الرصيد المالي = 0',
            'created_at' => now(),
        ]);

        InternalNotifier::dispatch('project.completed', $project, [
            'reason' => 'auto',
            'entity_type' => $entityType,
        ]);
    }

    /**
     * Sum of approved incoming - approved outgoing for a project.
     */
    public static function computeApprovedBalance(int $projectId): float
    {
        $sums = DB::table('financial_transactions')
            ->select('transaction_type', DB::raw('COALESCE(SUM(amount), 0) AS total'))
            ->where('project_id', $projectId)
            ->where('approval_status', 'approved')
            ->groupBy('transaction_type')
            ->pluck('total', 'transaction_type');

        $incoming = (float) ($sums['incoming'] ?? 0);
        $outgoing = (float) ($sums['outgoing'] ?? 0);

        return $incoming - $outgoing;
    }
}
