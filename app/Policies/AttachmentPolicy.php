<?php

namespace App\Policies;

use App\Enums\Permission as Perm;
use App\Enums\Role;
use App\Models\Attachment;
use App\Models\User;

/**
 * Authorization rules for project attachments (مرفقات / معززات).
 *
 *  - مدخل معززات المشروع (country): can add and edit own-country attachments
 *    only while approval_status != 'approved'.
 *  - منفذ المشروع (country): may add documentation/photo attachments to
 *    his country's projects (also bounded by approval_status).
 *  - مدخل ومعتمد المعززات والحوالات (central): may add for HQ + approve all,
 *    EXCEPT cannot approve attachments he himself created (4-eyes principle).
 *  - معد التقرير النهائي: read-only، يرى جميع المرفقات (للاطلاع الكامل قبل
 *    إعداد التقرير) ولكن لا يمكنه التعديل.
 *  - مشرف النظام: read-only لجميع المرفقات.
 *  - Hard-delete forbidden for everyone.
 */
class AttachmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Perm::AttachmentsView->value);
    }

    public function view(User $user, Attachment $attachment): bool
    {
        if (! $user->can(Perm::AttachmentsView->value)) {
            return false;
        }

        // مشرف النظام + معد التقرير النهائي: رؤية كاملة للتفاصيل بدون قيد الدولة
        // أو حالة الاعتماد.
        if ($user->hasAnyRole([
            Role::BoardSupervisor->value,
            Role::FinalReportPreparer->value,
        ])) {
            return true;
        }

        $countryId = $attachment->project?->country_id;

        return $user->hasCountryAccess($countryId);
    }

    public function create(User $user): bool
    {
        return $user->can(Perm::AttachmentsCreate->value);
    }

    public function update(User $user, Attachment $attachment): bool
    {
        if (! $user->can(Perm::AttachmentsUpdate->value)) {
            return false;
        }

        // No edits after approval.
        if ($attachment->approval_status === 'approved') {
            return false;
        }

        if (! $user->hasCountryAccess($attachment->project?->country_id)) {
            return false;
        }

        // مدخل معززات المشروع: only own country (covered by hasCountryAccess) AND only own uploads
        // are not strictly required by spec — they may edit any attachment within their country
        // before approval. Keep that behaviour but block once approved (above).
        return true;
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        return false;
    }

    public function restore(User $user, Attachment $attachment): bool
    {
        return false;
    }

    public function forceDelete(User $user, Attachment $attachment): bool
    {
        return false;
    }

    /**
     * Approve an attachment. Central enhancer-finance role only, and never
     * on attachments he himself uploaded (4-eyes / segregation of duties).
     */
    public function approve(User $user, Attachment $attachment): bool
    {
        if (! $user->can(Perm::AttachmentsApprove->value)) {
            return false;
        }

        if ($attachment->approval_status === 'approved') {
            return false;
        }

        // Country scoping: enhancer_finance_central is country-scoped, so
        // approval is restricted to attachments on projects in the user's
        // allowed countries. Global-scope roles bypass via hasCountryAccess().
        if (! $user->hasCountryAccess($attachment->project?->country_id)) {
            return false;
        }

        return (int) $attachment->uploaded_by !== (int) $user->id;
    }

    public function reject(User $user, Attachment $attachment): bool
    {
        // Same gate as approve.
        return $this->approve($user, $attachment);
    }
}
