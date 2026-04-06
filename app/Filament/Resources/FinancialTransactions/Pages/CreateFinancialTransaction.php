<?php

namespace App\Filament\Resources\FinancialTransactions\Pages;

use App\Filament\Resources\FinancialTransactions\FinancialTransactionResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateFinancialTransaction extends CreateRecord
{
    protected static string $resource = FinancialTransactionResource::class;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
