<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class ProjectStatsOverview extends BaseWidget
{
    protected ?string $heading = 'إحصائيات المشاريع';

    protected function getStats(): array
    {
        $stateColumn = Schema::hasColumn('projects', 'status') ? 'status' : 'state';
        $base = $this->projectsBaseQuery();

        return [
            Stat::make('إجمالي المشاريع', number_format((clone $base)->count()))
                ->color('primary')
                ->icon('heroicon-m-rectangle-stack'),

            Stat::make('قيد التنفيذ', number_format((clone $base)->where($stateColumn, 'in_execution')->count()))
                ->color('warning')
                ->icon('heroicon-m-play'),

            Stat::make('متأخرة', number_format((clone $base)->where($stateColumn, 'delayed')->count()))
                ->color('danger')
                ->icon('heroicon-m-exclamation-triangle'),

            Stat::make('مكتملة/مغلقة', number_format((clone $base)->whereIn($stateColumn, ['completed', 'closed'])->count()))
                ->color('success')
                ->icon('heroicon-m-check-circle'),
        ];
    }

    protected function projectsBaseQuery(): Builder
    {
        $query = Project::query();

        if (Schema::hasColumn('projects', 'is_archived')) {
            $query->where('is_archived', false);
        } elseif (Schema::hasColumn('projects', 'archived_at')) {
            $query->whereNull('archived_at');
        }

        return $query;
    }
}
