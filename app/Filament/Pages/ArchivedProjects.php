<?php

namespace App\Filament\Pages;

use App\Enums\Role;
use App\Models\Project;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use UnitEnum;

class ArchivedProjects extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';
    protected static string|UnitEnum|null $navigationGroup = 'التحليلات والتقارير';
    protected static ?string $navigationLabel = 'أرشيف المشاريع';
    protected static ?int $navigationSort = 82;

    /**
     * Hidden from enhancer_entry_country.
     */
    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user instanceof User
            && ! $user->hasRole(Role::EnhancerEntryCountry->value);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canAccess();
    }

    protected string $view = 'filament.pages.archived-projects';

    public array $projects = [];

    public function mount(): void
    {
        $query = Project::query();
        $titleColumn = $this->titleColumn();
        $stateColumn = Schema::hasColumn('projects', 'status') ? 'status' : 'state';

        if (Schema::hasColumn('projects', 'is_archived')) {
            $query->where('is_archived', true);
        } elseif (Schema::hasColumn('projects', 'archived_at')) {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereIn($stateColumn, ['closed']);
        }

        $this->projects = $query
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn ($project) => [
                'project_number' => $project->project_number,
                'title' => $project->{$titleColumn},
                'state' => $project->{$stateColumn},
                'archived_at' => optional($project->archived_at ?? $project->updated_at)->format('Y-m-d H:i'),
            ])->all();
    }

    protected function titleColumn(): string
    {
        if (Schema::hasColumn('projects', 'title')) {
            return 'title';
        }

        if (Schema::hasColumn('projects', 'name')) {
            return 'name';
        }

        return 'project_name';
    }
}
