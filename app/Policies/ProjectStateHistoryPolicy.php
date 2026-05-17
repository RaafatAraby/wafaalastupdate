<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\ProjectStateHistory;
use App\Models\User;

/**
 * State-history rows are read-only and contain sensitive transitions.
 * Restricted to:
 *  - system_admin (via Gate::before)
 *  - board_supervisor (مشرف النظام)
 *  - final_report_preparer (معد التقرير النهائي)
 */
class ProjectStateHistoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            Role::BoardSupervisor->value,
            Role::FinalReportPreparer->value,
        ]);
    }

    public function view(User $user, ProjectStateHistory $history): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        // BoardSupervisor + FinalReportPreparer have global access (no country scope).
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ProjectStateHistory $history): bool
    {
        return false;
    }

    public function delete(User $user, ProjectStateHistory $history): bool
    {
        return false;
    }
}
