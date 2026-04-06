<?php

namespace App\Observers;

use App\Models\FinancialTransaction;
use App\Services\ActivityLogger;
use App\Services\AlertNotifier;

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
    }

    public function updated(FinancialTransaction $transaction): void
    {
        $changes = collect($transaction->getChanges())->except('updated_at')->all();

        if (!empty($changes)) {
            ActivityLogger::log('financial.updated', 'تعديل حركة مالية', $transaction, [
                'project_id' => $transaction->project_id,
                'changes' => $changes,
            ]);
        }
    }

    public function deleted(FinancialTransaction $transaction): void
    {
        ActivityLogger::log('financial.deleted', 'حذف حركة مالية', $transaction, [
            'project_id' => $transaction->project_id,
        ]);
    }
}
