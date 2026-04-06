<?php

namespace App\Filament\Pages;

use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\FinancialTransaction;
use App\Models\Project;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use UnitEnum;

class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'لوحة التحكم';
    protected static ?string $title = 'لوحة التحكم';
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.dashboard';

    public array $summary = [];
    public array $stateDistribution = [];
    public array $documentationDistribution = [];
    public array $financeMonthly = [];
    public array $latestProjects = [];
    public array $recentActivity = [];
    public array $topOrganizations = [];

    public function mount(): void
    {
        $this->summary = $this->buildSummary();
        $this->stateDistribution = $this->buildStateDistribution();
        $this->documentationDistribution = $this->buildDocumentationDistribution();
        $this->financeMonthly = $this->buildFinanceMonthly();
        $this->latestProjects = $this->buildLatestProjects();
        $this->recentActivity = $this->buildRecentActivity();
        $this->topOrganizations = $this->buildTopOrganizations();
    }

    protected function buildSummary(): array
    {
        $projects = $this->baseProjectsQuery();
        $stateColumn = $this->stateColumn();

        $incoming = (float) FinancialTransaction::query()
            ->where('transaction_type', 'incoming')
            ->sum('amount');

        $outgoing = (float) FinancialTransaction::query()
            ->where('transaction_type', 'outgoing')
            ->sum('amount');

        return [
            'total_projects' => (clone $projects)->count(),
            'active_projects' => (clone $projects)->whereNotIn($stateColumn, ['completed', 'closed'])->count(),
            'delayed_projects' => (clone $projects)->where($stateColumn, 'delayed')->count(),
            'undocumented_projects' => (clone $projects)->where('documentation_status', '!=', 'complete')->count(),
            'attachments_count' => Attachment::query()->count(),
            'transactions_count' => FinancialTransaction::query()->count(),
            'incoming_total' => $incoming,
            'outgoing_total' => $outgoing,
            'balance' => $incoming - $outgoing,
        ];
    }

    protected function buildStateDistribution(): array
    {
        $stateColumn = $this->stateColumn();

        $raw = $this->baseProjectsQuery()
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

        return collect($states)->map(fn (string $state) => [
            'label' => $this->stateLabel($state),
            'value' => (int) ($raw[$state] ?? 0),
        ])->all();
    }

    protected function buildDocumentationDistribution(): array
    {
        $raw = $this->baseProjectsQuery()
            ->select('documentation_status', DB::raw('COUNT(*) as total'))
            ->groupBy('documentation_status')
            ->pluck('total', 'documentation_status')
            ->all();

        return [
            ['label' => 'غير موثق', 'value' => (int) ($raw['not_started'] ?? 0)],
            ['label' => 'جزئي', 'value' => (int) ($raw['partial'] ?? 0)],
            ['label' => 'مكتمل', 'value' => (int) ($raw['complete'] ?? 0)],
        ];
    }

    protected function buildFinanceMonthly(): array
    {
        if (! Schema::hasColumn('financial_transactions', 'transaction_date')) {
            return [];
        }

        return FinancialTransaction::query()
            ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') as month")
            ->selectRaw("SUM(CASE WHEN transaction_type = 'incoming' THEN amount ELSE 0 END) as incoming_total")
            ->selectRaw("SUM(CASE WHEN transaction_type = 'outgoing' THEN amount ELSE 0 END) as outgoing_total")
            ->groupBy('month')
            ->orderBy('month')
            ->limit(6)
            ->get()
            ->map(fn ($row) => [
                'month' => (string) $row->month,
                'incoming_total' => (float) $row->incoming_total,
                'outgoing_total' => (float) $row->outgoing_total,
            ])
            ->all();
    }

    protected function buildLatestProjects(): array
    {
        $titleColumn = $this->titleColumn();
        $stateColumn = $this->stateColumn();

        return $this->baseProjectsQuery()
            ->with(['country', 'organization'])
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'project_number' => $project->project_number,
                'title' => (string) ($project->{$titleColumn} ?? '-'),
                'country' => $project->country->name_ar ?? '-',
                'organization' => $project->organization->name ?? '-',
                'state' => $this->stateLabel((string) ($project->{$stateColumn} ?? '')),
            ])
            ->all();
    }

    protected function buildRecentActivity(): array
    {
        return ActivityLog::query()
            ->with(['project', 'causer'])
            ->latest('id')
            ->limit(5)
            ->get()
            ->map(fn ($log) => [
                'event' => $this->eventLabel((string) $log->event),
                'description' => $log->description,
                'project_id' => $log->project?->id,
                'project_number' => $log->project->project_number ?? '-',
                'causer' => $log->causer->name ?? 'النظام',
                'created_at' => optional($log->created_at)->format('Y-m-d H:i') ?? '-',
            ])
            ->all();
    }

    protected function buildTopOrganizations(): array
    {
        return DB::table('projects')
            ->join('organizations', 'organizations.id', '=', 'projects.organization_id')
            ->when(
                Schema::hasColumn('projects', 'is_archived'),
                fn ($query) => $query->where('projects.is_archived', false)
            )
            ->when(
                ! Schema::hasColumn('projects', 'is_archived') && Schema::hasColumn('projects', 'archived_at'),
                fn ($query) => $query->whereNull('projects.archived_at')
            )
            ->select('organizations.name', DB::raw('COUNT(projects.id) as total'))
            ->groupBy('organizations.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($row) => [
                'name' => (string) $row->name,
                'total' => (int) $row->total,
            ])
            ->all();
    }

    protected function baseProjectsQuery(): Builder
    {
        $query = Project::query();

        if (Schema::hasColumn('projects', 'is_archived')) {
            $query->where('is_archived', false);
        } elseif (Schema::hasColumn('projects', 'archived_at')) {
            $query->whereNull('archived_at');
        }

        return $query;
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
        ][$state] ?? ($state ?: '-');
    }

    protected function eventLabel(string $event): string
    {
        return [
            'project.created' => 'إنشاء مشروع',
            'project.updated' => 'تحديث مشروع',
            'project.archived' => 'أرشفة مشروع',
            'project.restored' => 'استعادة مشروع',
            'financial.created' => 'إضافة حركة مالية',
            'financial.updated' => 'تعديل حركة مالية',
            'financial.deleted' => 'حذف حركة مالية',
            'attachment.created' => 'إضافة مرفق',
            'attachment.deleted' => 'حذف مرفق',
        ][$event] ?? $event;
    }
}
