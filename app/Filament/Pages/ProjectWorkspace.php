<?php

namespace App\Filament\Pages;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\Attachment;
use App\Models\Project;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use UnitEnum;

class ProjectWorkspace extends Page
{
    protected static bool $shouldRegisterNavigation = false;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder-open';

    protected string $view = 'filament.pages.project-workspace';

    public ?Project $project = null;
    public array $financeSummary = [
        'incoming' => 0,
        'outgoing' => 0,
        'balance' => 0,
    ];
    public array $projectAttachments = [];

    /**
     * Whether the current user can see the financial block on this page
     * (summary + attachments that belong to financial transactions).
     */
    public bool $canSeeFinancials = false;

    public function mount(): void
    {
        $projectId = (int) request()->query('project_id');

        abort_unless($projectId > 0, 404);

        // الملخص المالي وكل تفاصيل الحركات المالية المتعلقة بالمشروع
        // مرئية لـ: مدير النظام، مشرف المجلس، معد التقرير النهائي،
        // أو لأي مستخدم مُنحت له صلاحية مباشرة `financial.view` من
        // شاشة "صلاحيات إضافية" (تجاوز يدوي لقاعدة الإخفاء).
        $user = Auth::user();
        $this->canSeeFinancials = $user !== null && (
            $user->hasAnyRole([
                Role::SystemAdmin->value,
                Role::BoardSupervisor->value,
                Role::FinalReportPreparer->value,
            ])
            || $user->hasDirectPermission(Permission::FinancialView->value)
        );

        $relations = [
            'country',
            'organization',
            'attachments',
            'activityLogs.causer',
            'stateHistories.user',
        ];
        if ($this->canSeeFinancials) {
            $relations[] = 'financialTransactions';
        }

        $this->project = Project::query()
            ->with($relations)
            ->findOrFail($projectId);

        if ($this->canSeeFinancials) {
            $incoming = (float) $this->project->financialTransactions
                ->where('transaction_type', 'incoming')
                ->sum('amount');

            $outgoing = (float) $this->project->financialTransactions
                ->where('transaction_type', 'outgoing')
                ->sum('amount');

            $this->financeSummary = [
                'incoming' => $incoming,
                'outgoing' => $outgoing,
                'balance' => $incoming - $outgoing,
            ];

            $transactionIds = $this->project->financialTransactions->pluck('id')->filter()->values();
        } else {
            $transactionIds = collect();
        }

        $this->projectAttachments = Attachment::query()
            ->with(['financialTransaction', 'uploadedBy'])
            ->where(function ($query) use ($transactionIds) {
                $query->where('project_id', $this->project->id);

                if ($transactionIds->isNotEmpty()) {
                    $query->orWhereIn('financial_transaction_id', $transactionIds);
                }
            })
            ->latest('id')
            ->get()
            ->map(function (Attachment $attachment): array {
                return [
                    'id' => $attachment->id,
                    'original_name' => $attachment->original_name,
                    'category' => $attachment->category,
                    'files_count' => $attachment->files_count,
                    'created_at' => optional($attachment->created_at)?->format('Y-m-d H:i') ?? '-',
                    'transaction_ref' => $attachment->financialTransaction?->reference_no ?: null,
                    'transaction_type' => $attachment->financialTransaction?->transaction_type ?: null,
                    'uploaded_by' => $attachment->uploadedBy?->name ?: 'النظام',
                ];
            })
            ->all();
    }

    public function getHeading(): string
    {
        if (! $this->project) {
            return 'صفحة المشروع';
        }

        return 'المشروع: ' . $this->projectTitle();
    }

    public function projectTitle(): string
    {
        if (! $this->project) {
            return '-';
        }

        if (Schema::hasColumn('projects', 'title')) {
            return (string) ($this->project->title ?? '-');
        }

        if (Schema::hasColumn('projects', 'name')) {
            return (string) ($this->project->name ?? '-');
        }

        return (string) ($this->project->project_name ?? '-');
    }

    public function projectState(): string
    {
        if (! $this->project) {
            return '-';
        }

        $state = Schema::hasColumn('projects', 'status')
            ? (string) ($this->project->status ?? '')
            : (string) ($this->project->state ?? '');

        return match ($state) {
            'new' => 'جديد',
            'pending_readiness' => 'بانتظار الجاهزية',
            'ready_for_execution' => 'جاهز للتنفيذ',
            'in_execution' => 'قيد التنفيذ',
            'pending_documentation' => 'بانتظار التوثيق',
            'delayed' => 'متأخر',
            'completed' => 'مكتمل',
            'closed' => 'مغلق',
            default => $state ?: '-',
        };
    }

    public function documentationStatus(): string
    {
        $status = (string) ($this->project->documentation_status ?? '');

        return match ($status) {
            'not_started' => 'غير موثق',
            'partial' => 'جزئي',
            'complete' => 'مكتمل',
            default => $status ?: '-',
        };
    }

    public function financialStatus(): string
    {
        $status = (string) ($this->project->financial_status ?? '');

        return match ($status) {
            'unfunded' => 'غير ممول',
            'partially_funded' => 'تمويل جزئي',
            'funded' => 'ممول',
            'partially_spent' => 'صرف جزئي',
            'settled' => 'مسوى',
            default => $status ?: '-',
        };
    }
}
