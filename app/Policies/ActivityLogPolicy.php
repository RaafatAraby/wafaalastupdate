<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\User;

/**
 * Activity log: read-only audit trail.
 *
 * Visible to:
 *  - system_admin (via Gate::before — bypass)
 *  - board_supervisor (مشرف النظام / مجلس الإدارة)
 *  - final_report_preparer (معد التقرير النهائي)
 *
 * Everyone else: deny.
 */
class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            Role::BoardSupervisor->value,
            Role::FinalReportPreparer->value,
        ]);
    }

    public function view(User $user, ActivityLog $log): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ActivityLog $log): bool
    {
        return false;
    }

    public function delete(User $user, ActivityLog $log): bool
    {
        return false;
    }
}
