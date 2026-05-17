<?php

namespace App\Filament\Resources\FinancialTransactions\Pages;

use App\Filament\Resources\FinancialTransactions\FinancialTransactionResource;
use App\Models\FinancialTransaction;
use App\Support\NumericNormalizer;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class CreateFinancialTransaction extends CreateRecord
{
    protected static string $resource = FinancialTransactionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        if (blank($data['project_id'] ?? null) && request()->filled('project_id')) {
            $data['project_id'] = (int) request()->query('project_id');
        }

        if (SchemaFacade::hasColumn('financial_transactions', 'created_by') && blank($data['created_by'] ?? null)) {
            $data['created_by'] = Auth::id();
        }

        // New transactions always start as `pending`; approval is via
        // the dedicated workflow action (4-eyes principle).
        $data['approval_status'] = 'pending';
        unset($data['approved_by'], $data['approved_at'], $data['reviewed_by']);

        NumericNormalizer::apply($data, 'amount');

        return FinancialTransaction::query()->create($data);
    }
}
