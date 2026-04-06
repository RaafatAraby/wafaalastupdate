<?php

namespace App\Filament\Pages;

use App\Models\FinancialTransaction;
use App\Models\Organization;
use App\Models\Project;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use UnitEnum;

class Reports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string|UnitEnum|null $navigationGroup = 'التحليلات والتقارير';
    protected static ?string $navigationLabel = 'التقارير';
    protected static ?int $navigationSort = 80;

    protected string $view = 'filament.pages.reports';

    public array $summary = [];
    public array $projectsByState = [];
    public array $monthlyFinance = [];
    public array $lateProjects = [];
    public array $filters = [];
    public array $countries = [];
    public array $organizations = [];

    public function mount(Request $request): void
    {
        $this->filters = [
            'country_id' => $request->query('country_id'),
            'organization_id' => $request->query('organization_id'),
            'state' => $request->query('state'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ];

        $this->countries = DB::table('countries')->orderBy('name_ar')->pluck('name_ar', 'id')->toArray();
        $this->organizations = Organization::query()->orderBy('name')->pluck('name', 'id')->toArray();

        $this->summary = $this->buildSummary();
        $this->projectsByState = $this->buildProjectsByState();
        $this->monthlyFinance = $this->buildMonthlyFinance();
        $this->lateProjects = $this->buildLateProjects();
    }

    protected function filteredProjectsQuery(): Builder
    {
        $query = Project::query();

        if (Schema::hasColumn('projects', 'is_archived')) {
            $query->where('is_archived', false);
        } elseif (Schema::hasColumn('projects', 'archived_at')) {
            $query->whereNull('archived_at');
        }

        $stateColumn = $this->stateColumn();

        if (! empty($this->filters['country_id'])) {
            $query->where('country_id', $this->filters['country_id']);
        }

        if (! empty($this->filters['organization_id'])) {
            $query->where('organization_id', $this->filters['organization_id']);
        }

        if (! empty($this->filters['state'])) {
            $query->where($stateColumn, $this->filters['state']);
        }

        if (! empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }

        if (! empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        return $query;
    }

    protected function buildSummary(): array
    {
        $stateColumn = $this->stateColumn();
        $projects = $this->filteredProjectsQuery();
        $projectIds = (clone $projects)->pluck('id');

        $incoming = (float) FinancialTransaction::query()
            ->whereIn('project_id', $projectIds)
            ->where('transaction_type', 'incoming')
            ->sum('amount');

        $outgoing = (float) FinancialTransaction::query()
            ->whereIn('project_id', $projectIds)
            ->where('transaction_type', 'outgoing')
            ->sum('amount');

        return [
            'total_projects' => (int) (clone $projects)->count(),
            'delayed_projects' => (int) (clone $projects)->where($stateColumn, 'delayed')->count(),
            'completed_projects' => (int) (clone $projects)->whereIn($stateColumn, ['completed', 'closed'])->count(),
            'incoming_total' => $incoming,
            'outgoing_total' => $outgoing,
            'balance' => $incoming - $outgoing,
        ];
    }

    protected function buildProjectsByState(): array
    {
        $stateColumn = $this->stateColumn();

        $rows = $this->filteredProjectsQuery()
            ->select($stateColumn, DB::raw('COUNT(*) as total'))
            ->groupBy($stateColumn)
            ->pluck('total', $stateColumn)
            ->all();

        $states = [
            'new',
            'pending_readiness',
            'ready_for_execution',
            'in_execution',
            'pending_documentation',
            'delayed',
            'completed',
            'closed',
        ];

        $output = [];
        foreach ($states as $state) {
            $output[] = [
                'state' => $this->stateLabel($state),
                'total' => (int) ($rows[$state] ?? 0),
            ];
        }

        return $output;
    }

    protected function buildMonthlyFinance(): array
    {
        $projectIds = $this->filteredProjectsQuery()->pluck('id');

        return FinancialTransaction::query()
            ->whereIn('project_id', $projectIds)
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as month")
            ->selectRaw("SUM(CASE WHEN transaction_type = 'incoming' THEN amount ELSE 0 END) as incoming_total")
            ->selectRaw("SUM(CASE WHEN transaction_type = 'outgoing' THEN amount ELSE 0 END) as outgoing_total")
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(6)
            ->get()
            ->map(fn ($row): array => [
                'month' => (string) $row->month,
                'incoming_total' => (float) $row->incoming_total,
                'outgoing_total' => (float) $row->outgoing_total,
                'balance' => (float) $row->incoming_total - (float) $row->outgoing_total,
            ])
            ->values()
            ->all();
    }

    protected function buildLateProjects(): array
    {
        $stateColumn = $this->stateColumn();
        $titleColumn = $this->titleColumn();

        return $this->filteredProjectsQuery()
            ->whereNotNull('expected_end_date')
            ->whereDate('expected_end_date', '<', now()->toDateString())
            ->whereNotIn($stateColumn, ['completed', 'closed'])
            ->orderBy('expected_end_date')
            ->limit(10)
            ->get()
            ->map(fn (Project $project): array => [
                'project_number' => (string) ($project->project_number ?? $project->id),
                'title' => (string) ($project->{$titleColumn} ?? '—'),
                'state' => $this->stateLabel((string) $project->{$stateColumn}),
                'expected_end_date' => optional($project->expected_end_date)?->format('Y-m-d') ?? '—',
            ])
            ->all();
    }

    protected function stateColumn(): string
    {
        return Schema::hasColumn('projects', 'status') ? 'status' : 'state';
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

    protected function stateLabel(string $state): string
    {
        return [
            'new' => 'جديد',
            'pending_readiness' => 'بانتظار الجاهزية',
            'ready_for_execution' => 'جاهز للتنفيذ',
            'in_execution' => 'قيد التنفيذ',
            'pending_documentation' => 'بانتظار التوثيق',
            'delayed' => 'متأخر',
            'completed' => 'مكتمل',
            'closed' => 'مغلق',
        ][$state] ?? $state;
    }
}
