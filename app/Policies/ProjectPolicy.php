<?php

namespace App\Policies;

use App\Enums\Permission as Perm;
use App\Enums\Role;
use App\Models\Project;
use App\Models\User;

/**
 * Authorization rules for the Project model.
 *
 * Notes:
 *  - Gate::before grants مدير النظام everything; rules below apply to all
 *    other roles.
 *  - Country scoping is enforced inside view/update/etc. via
 *    `User::hasCountryAccess()` so a user can never reach a project from
 *    another country (even via direct URL).
 *  - Hard-delete is forbidden for everyone (return false). Archive uses
 *    a separate ability and a flag column.
 */
class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Perm::ProjectsView->value);
    }

    public function view(User $user, Project $project): bool
    {
        if (! $user->can(Perm::ProjectsView->value)) {
            return false;
        }

        // Final-report preparer must only see projects whose final report is being prepared
        // (any state) — but no special record-level filtering beyond approved attachments,
        // which is enforced at the AttachmentPolicy level.

        return $user->hasCountryAccess($project->country_id);
    }

    public function create(User $user): bool
    {
        return $user->can(Perm::ProjectsCreate->value);
    }

    /**
     * General-purpose update gate for the *project record itself*.
     *
     * - منشئ المشروع: only before any readiness decision is made
     *   (state in {new, pending_readiness}).
     * - معتمد الجاهزية: handled via `approveReadiness` ability (state field only).
     * - منفذ المشروع: handled via `updateExecution` ability (state field only).
     * - مُعدّ التقرير النهائي: handled via `prepareFinalReport`.
     * - مشرف النظام: handled via `rollback` (and final report approval).
     * - مدخل/معتمد المعززات: cannot edit project records — only their own attachments.
     */
    public function update(User $user, Project $project): bool
    {
        if (! $user->hasCountryAccess($project->country_id)) {
            return false;
        }

        // Project creator: edit only while still in initial state.
        if ($user->hasRole(Role::ProjectCreator->value)
            && $user->can(Perm::ProjectsUpdate->value)
            && in_array($project->state, ['new', 'pending_readiness'], true)
        ) {
            return true;
        }

        return false;
    }

    /**
     * No one can hard-delete a project. Archive instead.
     * (Gate::before short-circuits true for system_admin — overridden via denial here.)
     */
    public function delete(User $user, Project $project): bool
    {
        return false;
    }

    public function restore(User $user, Project $project): bool
    {
        return false;
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }

    // ─── Workflow abilities ─────────────────────────────────────────────

    public function approveReadiness(User $user, Project $project): bool
    {
        return $user->can(Perm::ProjectsReadinessApprove->value)
            && $user->hasCountryAccess($project->country_id)
            && in_array($project->state, ['new', 'pending_readiness'], true);
    }

    public function updateExecution(User $user, Project $project): bool
    {
        return $user->can(Perm::ProjectsExecutionUpdate->value)
            && $user->hasCountryAccess($project->country_id)
            && in_array($project->state, ['ready_for_execution', 'in_execution', 'pending_documentation', 'delayed'], true);
    }

    public function prepareFinalReport(User $user, Project $project): bool
    {
        return $user->can(Perm::FinalReportPrepare->value)
            && in_array($project->state, ['pending_documentation', 'completed'], true)
            && ! $project->final_report_approved;
    }

    public function approveFinalReport(User $user, Project $project): bool
    {
        return $user->can(Perm::FinalReportApprove->value)
            && filled($project->final_report)
            && ! $project->final_report_approved;
    }

    public function rollback(User $user, Project $project): bool
    {
        return $user->can(Perm::ProjectsRollback->value)
            && $project->state !== 'closed';
    }

    /**
     * A project may be closed only when:
     *   1. Final report has been approved by مشرف النظام, AND
     *   2. The project's approved-balance is zero.
     */
    public function close(User $user, Project $project): bool
    {
        if (! $user->can(Perm::ProjectsClose->value)) {
            return false;
        }

        if ($project->state === 'closed') {
            return false;
        }

        return (bool) $project->final_report_approved && abs($project->balance()) < 0.01;
    }

    public function archive(User $user, Project $project): bool
    {
        // Only system admin (granted via Gate::before) — never anyone else.
        return false;
    }
}
