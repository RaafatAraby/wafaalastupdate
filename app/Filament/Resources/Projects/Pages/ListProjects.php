<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('إضافة مشروع'),
        ];
    }

    public function getTabs(): array
    {
        $tab = $this->resolveTabClass();
        $stateColumn = Schema::hasColumn('projects', 'status') ? 'status' : 'state';
        $base = fn (): Builder => $this->baseQuery();

        return [
            'all' => $tab::make('الكل')
                ->badge($base()->count()),

            'new' => $tab::make('جديد')
                ->modifyQueryUsing(fn (Builder $query) => $query->where($stateColumn, 'new'))
                ->badge($base()->where($stateColumn, 'new')->count()),

            'pending_readiness' => $tab::make('بانتظار الجاهزية')
                ->modifyQueryUsing(fn (Builder $query) => $query->where($stateColumn, 'pending_readiness'))
                ->badge($base()->where($stateColumn, 'pending_readiness')->count()),

            'ready_for_execution' => $tab::make('جاهز للتنفيذ')
                ->modifyQueryUsing(fn (Builder $query) => $query->where($stateColumn, 'ready_for_execution'))
                ->badge($base()->where($stateColumn, 'ready_for_execution')->count()),

            'in_execution' => $tab::make('قيد التنفيذ')
                ->modifyQueryUsing(fn (Builder $query) => $query->where($stateColumn, 'in_execution'))
                ->badge($base()->where($stateColumn, 'in_execution')->count()),

            'delayed' => $tab::make('متأخرة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where($stateColumn, 'delayed'))
                ->badge($base()->where($stateColumn, 'delayed')->count()),

            'undocumented' => $tab::make('غير موثقة')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('documentation_status', '!=', 'complete'))
                ->badge($base()->where('documentation_status', '!=', 'complete')->count()),

            'completed' => $tab::make('مكتملة')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn($stateColumn, ['completed', 'closed']))
                ->badge($base()->whereIn($stateColumn, ['completed', 'closed'])->count()),
        ];
    }

    protected function baseQuery(): Builder
    {
        $query = Project::query();

        if (Schema::hasColumn('projects', 'is_archived')) {
            $query->where('is_archived', false);
        } elseif (Schema::hasColumn('projects', 'archived_at')) {
            $query->whereNull('archived_at');
        }

        return $query;
    }

    protected function resolveTabClass(): string
    {
        foreach ([
            \Filament\Schemas\Components\Tabs\Tab::class,
            \Filament\Resources\Components\Tab::class,
            \Filament\Resources\Pages\ListRecords\Tab::class,
        ] as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        throw new RuntimeException('Filament Tab class not found for this version.');
    }
}
