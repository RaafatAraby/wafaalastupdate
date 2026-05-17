<?php

namespace App\Observers;

use App\Models\FinancialTransaction;
use App\Services\ActivityLogger;
use App\Services\AlertNotifier;
use App\Services\InternalNotifier;
use App\Services\ProjectAutoClose;
use App\Models\Project;

class FinancialTransactionObserver
{
    public function created(FinancialTransaction $transaction): void
    {
        ActivityLogger::log(
            'financial.created',
            'إضافة حركة مالية جديدة',
            $transaction,
            [
                'project_id' => $transaction->project_id,
                'type' => $transaction->transaction_type,
                'amount' => $transaction->amount,
            ]
        );

        AlertNotifier::storeAndSend(
            $transaction->project_id,
            'financial_transaction_created',
            'حركة مالية جديدة',
            'تمت إضافة حركة مالية بقيمة ' . number_format((float) $transaction->amount, 2),
            'info'
        );

        InternalNotifier::dispatch('financial.created', $transaction, [
            'amount' => $transaction->amount,
        ]);

        // تقييم الإغلاق التلقائي (تغيير الرصيد قد يجعله = 0).
        ProjectAutoClose::evaluate(Project::with('organization')->find($transaction->project_id));
    }

    public function updated(FinancialTransaction $transaction): void
    {
        $changes = collect($transaction->getChanges())->except('updated_at')->all();

        if (!empty($changes)) {
            ActivityLogger::log('financial.updated', 'تعديل حركة مالية', $transaction, [
                'project_id' => $transaction->project_id,
                'changes' => $changes,
            ]);

            // Fire notification only for non-approval edits. Approve/reject
            // flows fire their own dedicated events (financial.approved /
            // financial.rejected) and we don't want to double-notify.
            $nonApprovalChanges = array_diff_key($changes, array_flip(['approval_status', 'approved_by', 'approved_at', 'reviewed_by', 'review_note']));
            if (!empty($nonApprovalChanges)) {
                InternalNotifier::dispatch('financial.updated', $transaction, [
                    'amount' => $transaction->amount,
                    'changes' => array_keys($nonApprovalChanges),
                ]);
            }
        }

        // تقييم الإغلاق التلقائي عند الاعتماد/تغيير المبلغ/النوع.
        if (array_intersect(['amount', 'transaction_type', 'approval_status'], array_keys($changes))) {
            ProjectAutoClose::evaluate(Project::with('organization')->find($transaction->project_id));
        }
    }

    public function deleted(FinancialTransaction $transaction): void
    {
        ActivityLogger::log('financial.deleted', 'حذف حركة مالية', $transaction, [
            'project_id' => $transaction->project_id,
        ]);

        ProjectAutoClose::evaluate(Project::with('organization')->find($transaction->project_id));
    }
}
