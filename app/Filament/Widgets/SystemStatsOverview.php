<?php

namespace App\Filament\Widgets;

use App\Models\FinancialTransaction;
use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $incoming = (float) FinancialTransaction::where('transaction_type', 'incoming')->sum('amount');
        $outgoing = (float) FinancialTransaction::where('transaction_type', 'outgoing')->sum('amount');

        return [
            Stat::make('إجمالي المشاريع', (string) Project::count()),
            Stat::make('المشاريع المتأخرة', (string) Project::where('state', 'delayed')->count())->color('danger'),
            Stat::make('إجمالي الوارد', number_format($incoming, 2)),
            Stat::make('إجمالي الصادر', number_format($outgoing, 2)),
        ];
    }
}
