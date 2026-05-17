<?php

namespace App\Policies;

use App\Enums\Permission as Perm;
use App\Enums\Role;
use App\Models\FinancialTransaction;
use App\Models\User;

/**
 * Authorization rules for financial transactions (حوالات).
 *
 *  - مدخل ومعتمد المعززات والحوالات (central): creates/edits/approves.
 *  - مدخل معززات المشروع (country): NO financial access — attachments only.
 *  - معد التقرير النهائي: read-only، يرى جميع الحركات (مُعتمدة وغير مُعتمدة)
 *    لكن لا يمكنه التعديل.
 *  - مشرف النظام: read-only لجميع الحركات.
 *  - 4-eyes: cannot approve own entries.
 *  - No hard-delete.
 *  - No edits after approval.
 */
class FinancialTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Perm::FinancialView->value);
    }

    public function view(User $user, FinancialTransaction $transaction): bool
    {
        if (! $user->can(Perm::FinancialView->value)) {
            return false;
        }

        // مشرف النظام + معد التقرير النهائي: رؤية كاملة للتفاصيل بدون قيد.
        if ($user->hasAnyRole([
            Role::BoardSupervisor->value,
            Role::FinalReportPreparer->value,
        ])) {
            return true;
        }

        // Country scoping for other roles.
        return $user->hasCountryAccess($transaction->project?->country_id);
    }

    public function create(User $user): bool
    {
        return $user->can(Perm::FinancialCreate->value);
    }

    public function update(User $user, FinancialTransaction $transaction): bool
    {
        if (! $user->can(Perm::FinancialUpdate->value)) {
            return false;
        }

        if ($transaction->approval_status === 'approved') {
            return false;
        }

        // Country scoping: country-scoped roles (EnhancerEntryCountry,
        // EnhancerFinanceCentral) may only edit transactions belonging to
        // projects in their bound countries (user_countries). Global-scope
        // roles bypass this check via hasCountryAccess() returning true.
        return $user->hasCountryAccess($transaction->project?->country_id);
    }

    public function delete(User $user, FinancialTransaction $transaction): bool
    {
        return false;
    }

    public function restore(User $user, FinancialTransaction $transaction): bool
    {
        return false;
    }

    public function forceDelete(User $user, FinancialTransaction $transaction): bool
    {
        return false;
    }

    /**
     * Approve a transaction. Central role only; not own entries (4-eyes).
     */
    public function approve(User $user, FinancialTransaction $transaction): bool
    {
        if (! $user->can(Perm::FinancialApprove->value)) {
            return false;
        }

        if ($transaction->approval_status === 'approved') {
            return false;
        }

        // Country scoping: enhancer_finance_central is now country-scoped, so
        // approval is restricted to transactions on projects in the user's
        // allowed countries. Global-scope roles bypass via hasCountryAccess().
        if (! $user->hasCountryAccess($transaction->project?->country_id)) {
            return false;
        }

        return (int) $transaction->created_by !== (int) $user->id;
    }

    public function reject(User $user, FinancialTransaction $transaction): bool
    {
        return $this->approve($user, $transaction);
    }
}
