<?php

namespace App\Observers;

use App\Models\Attachment;
use App\Services\ActivityLogger;
use App\Services\AlertNotifier;
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
    }

    public function deleted(Attachment $attachment): void
    {
        ActivityLogger::log('attachment.deleted', 'حذف مرفق', $attachment, [
            'project_id' => $attachment->project_id,
            'file' => $attachment->original_name,
        ]);
    }
}
