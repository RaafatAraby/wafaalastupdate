<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use Closure;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class ListProjects extends ListRecords
{
    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('project.actions.create')),
        ];
    }

    public function getTabs(): array
    {
        $tabClass = $this->resolveTabClass();
        if (! $tabClass) {
            return [];
        }

        $state = $this->stateColumn();

        return array_filter([
            'all' => $this->makeTab(__('project.tabs.all'), null, $this->baseQuery()->count()),
            'new' => $this->makeTab(
                __('project.form.options.states.new'),
                fn (Builder $q) => $q->where($state, 'new'),
                $this->baseQuery()->where($state, 'new')->count()
            ),
            'pending_readiness' => $this->makeTab(
                __('project.form.options.states.pending_readiness'),
                fn (Builder $q) => $q->where($state, 'pending_readiness'),
                $this->baseQuery()->where($state, 'pending_readiness')->count()
            ),
            'ready_for_execution' => $this->makeTab(
                __('project.form.options.states.ready_for_execution'),
                fn (Builder $q) => $q->where($state, 'ready_for_execution'),
                $this->baseQuery()->where($state, 'ready_for_execution')->count()
            ),
            'in_execution' => $this->makeTab(
                __('project.form.options.states.in_execution'),
                fn (Builder $q) => $q->where($state, 'in_execution'),
                $this->baseQuery()->where($state, 'in_execution')->count()
            ),
            'delayed' => $this->makeTab(
                __('project.form.options.states.delayed'),
                fn (Builder $q) => $q->where($state, 'delayed'),
                $this->baseQuery()->where($state, 'delayed')->count()
            ),
            'undocumented' => $this->makeTab(
                __('project.tabs.undocumented'),
                fn (Builder $q) => $q->where('documentation_status', '!=', 'complete'),
                $this->baseQuery()->where('documentation_status', '!=', 'complete')->count()
            ),
            'completed' => $this->makeTab(
                __('project.form.options.states.completed'),
                fn (Builder $q) => $q->where($state, 'completed'),
                $this->baseQuery()->where($state, 'completed')->count()
            ),
        ]);
    }

    protected function baseQuery(): Builder
    {
        return static::getResource()::getEloquentQuery();
    }

    protected function stateColumn(): string
    {
        return Schema::hasColumn('projects', 'status') ? 'status' : 'state';
    }

    protected function resolveTabClass(): ?string
    {
        foreach ([
            \Filament\Resources\Components\Tab::class,
            'Filament\\Resources\\Pages\\ListRecords\\Tab',
        ] as $class) {
            if (class_exists($class)) {
                return $class;
            }
        }

        return null;
    }

    protected function makeTab(string $label, ?Closure $query = null, ?int $badge = null): mixed
    {
        $tabClass = $this->resolveTabClass();
        if (! $tabClass) {
            return null;
        }

        $tab = $tabClass::make($label);

        if ($query && method_exists($tab, 'modifyQueryUsing')) {
            $tab = $tab->modifyQueryUsing($query);
        }

        if ($badge !== null && method_exists($tab, 'badge')) {
            $tab = $tab->badge($badge);
        }

        return $tab;
    }
}
