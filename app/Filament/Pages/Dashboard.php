<?php

namespace App\Filament\Pages;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\FinancialTransaction;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectAutoDelay;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
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

    /**
     * When true, the dashboard renders a simple welcome message instead of
     * the full statistics panel. Triggered for every role except
     * system_admin and board_supervisor.
     */
    public bool $welcomeOnly = false;

    public function mount(): void
    {
        // Piggy-back on dashboard page-load to lazily evaluate overdue
        // projects — throttled internally to once per 6 hours.
        ProjectAutoDelay::kick();

        $user = Auth::user();
        // أدوار ترى لوحة التحكم الإحصائية الكاملة:
        //  - system_admin و board_supervisor: بيانات عالمية (كل الدول).
        //  - enhancer_finance_central: نفس الويدجتس لكن مصفّاة بدولة/دول
        //    المستخدم (عبر allowedCountryIds()).
        $this->welcomeOnly = ! ($user && $user->hasAnyRole([
            Role::SystemAdmin->value,
            Role::BoardSupervisor->value,
            Role::EnhancerFinanceCentral->value,
        ]));

        if ($this->welcomeOnly) {
            return;
        }

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

        $incoming = (float) $this->scopedTransactionsQuery()
            ->where('transaction_type', 'incoming')
            ->sum('amount');

        $outgoing = (float) $this->scopedTransactionsQuery()
            ->where('transaction_type', 'outgoing')
            ->sum('amount');

        return [
            'total_projects' => (clone $projects)->count(),
            'active_projects' => (clone $projects)->whereNotIn($stateColumn, ['completed', 'closed'])->count(),
            'delayed_projects' => (clone $projects)->where($stateColumn, 'delayed')->count(),
            'undocumented_projects' => (clone $projects)->where('documentation_status', '!=', 'complete')->count(),
            'attachments_count' => $this->scopedAttachmentsQuery()->count(),
            'transactions_count' => $this->scopedTransactionsQuery()->count(),
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

        return $this->scopedTransactionsQuery()
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
        $countryIds = $this->scopedCountryIds();

        return ActivityLog::query()
            ->with(['project', 'causer'])
            ->when(
                $countryIds !== null,
                fn ($q) => $q->whereHas('project', fn ($p) => $p->whereIn('country_id', $countryIds ?: [0]))
            )
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
        $countryIds = $this->scopedCountryIds();

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
            ->when(
                $countryIds !== null,
                fn ($query) => $query->whereIn('projects.country_id', $countryIds ?: [0])
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

        $countryIds = $this->scopedCountryIds();
        if ($countryIds !== null) {
            $query->whereIn('country_id', $countryIds ?: [0]);
        }

        return $query;
    }

    /**
     * جدول الحركات المالية مصفّى بالدول المسموحة للمستخدم الحالي. يعود
     * بالبيانات العالمية للأدوار التي تملك hasGlobalDataScope().
     */
    protected function scopedTransactionsQuery(): Builder
    {
        $query = FinancialTransaction::query();
        $countryIds = $this->scopedCountryIds();

        if ($countryIds !== null) {
            $query->whereHas('project', fn (Builder $q) => $q->whereIn('country_id', $countryIds ?: [0]));
        }

        return $query;
    }

    /**
     * جدول المرفقات مصفّى بالدول المسموحة للمستخدم الحالي.
     */
    protected function scopedAttachmentsQuery(): Builder
    {
        $query = Attachment::query();
        $countryIds = $this->scopedCountryIds();

        if ($countryIds !== null) {
            $query->whereHas('project', fn (Builder $q) => $q->whereIn('country_id', $countryIds ?: [0]));
        }

        return $query;
    }

    /**
     * تعود بـ null إذا كان الدور غير مقيّد بدولة (system_admin أو
     * board_supervisor — يرى الكل). وإلا تعود بمصفوفة معرفات الدول
     * المسموحة. مصفوفة فارغة = المستخدم لا دولة له فيرى 0 سجلات.
     *
     * @return array<int, int>|null
     */
    protected function scopedCountryIds(): ?array
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            return null;
        }

        if ($user->hasGlobalDataScope()) {
            return null;
        }

        return $user->allowedCountryIds();
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
        if ($event === '') {
            return '-';
        }

        $key = 'activity_log.events.' . $event;
        $translated = __($key);

        return is_string($translated) && $translated !== $key ? $translated : $event;
    }
}
