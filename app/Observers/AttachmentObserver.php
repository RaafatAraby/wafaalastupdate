<?php

namespace App\Observers;

use App\Models\Attachment;
use App\Services\ActivityLogger;
use App\Services\AlertNotifier;
use App\Services\InternalNotifier;
use Illuminate\Support\Facades\DB;

class AttachmentObserver
{
    public function created(Attachment $attachment): void
    {
        $projectId = $attachment->project_id;

        if (! $projectId && $attachment->financial_transaction_id) {
            $projectId = DB::table('financial_transactions')
                ->where('id', $attachment->financial_transaction_id)
                ->value('project_id');
        }

        ActivityLogger::log('attachment.created', 'إضافة مرفق جديد', $attachment, [
            'project_id' => $projectId,
            'file' => $attachment->original_name,
            'category' => $attachment->category,
        ]);

        if ($projectId) {
            AlertNotifier::storeAndSend(
                (int) $projectId,
                'attachment_created',
                'تم رفع مرفق',
                'تم رفع ملف: ' . ($attachment->original_name ?? 'مرفق'),
                'info'
            );
        }

        InternalNotifier::dispatch('attachment.created', $attachment, [
            'file' => $attachment->original_name,
        ]);
    }

    public function updated(Attachment $attachment): void
    {
        $changes = collect($attachment->getChanges())->except('updated_at')->all();

        if (empty($changes)) {
            return;
        }

        ActivityLogger::log('attachment.updated', 'تعديل مرفق', $attachment, [
            'project_id' => $attachment->project_id,
            'file' => $attachment->original_name,
            'changes' => array_keys($changes),
        ]);

        // Fire notification only for non-approval edits. Approve/reject
        // flows fire their own dedicated events (attachment.approved /
        // attachment.rejected) and we don't want to double-notify.
        $nonApprovalChanges = array_diff_key($changes, array_flip(['approval_status', 'approved_by', 'approved_at', 'reviewed_by', 'review_note']));
        if (!empty($nonApprovalChanges)) {
            InternalNotifier::dispatch('attachment.updated', $attachment, [
                'file' => $attachment->original_name,
                'changes' => array_keys($nonApprovalChanges),
            ]);
        }
    }

    public function deleted(Attachment $attachment): void
    {
        ActivityLogger::log('attachment.deleted', 'حذف مرفق', $attachment, [
            'project_id' => $attachment->project_id,
            'file' => $attachment->original_name,
        ]);
    }
}
