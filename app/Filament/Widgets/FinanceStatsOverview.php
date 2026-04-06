<?php

namespace App\Filament\Widgets;

use App\Models\FinancialTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceStatsOverview extends BaseWidget
{
    protected ?string $heading = 'ملخص الحوالات المالية';

    protected function getStats(): array
    {
        $incoming = (float) FinancialTransaction::query()
            ->where('transaction_type', 'incoming')
            ->sum('amount');

        $outgoing = (float) FinancialTransaction::query()
            ->where('transaction_type', 'outgoing')
            ->sum('amount');

        $balance = $incoming - $outgoing;

        return [
            Stat::make('الوارد', number_format($incoming, 2))
                ->color('success')
                ->icon('heroicon-m-arrow-down-circle'),

            Stat::make('الصادر', number_format($outgoing, 2))
                ->color('danger')
                ->icon('heroicon-m-arrow-up-circle'),

            Stat::make('الرصيد', number_format($balance, 2))
                ->color($balance >= 0 ? 'primary' : 'danger')
                ->icon('heroicon-m-banknotes'),
        ];
    }
}
