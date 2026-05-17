<?php

namespace App\Filament\Resources\FinancialTransactions\Pages;

use App\Filament\Resources\FinancialTransactions\FinancialTransactionResource;
use App\Support\NumericNormalizer;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditFinancialTransaction extends EditRecord
{
    protected static string $resource = FinancialTransactionResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Approval-related fields can only be changed by the workflow
        // actions; ignore any value coming from the form.
        unset(
            $data['approval_status'],
            $data['approved_by'],
            $data['approved_at'],
            $data['reviewed_by'],
        );

        NumericNormalizer::apply($data, 'amount');

        $record->update($data);

        return $record->fresh();
    }
}
