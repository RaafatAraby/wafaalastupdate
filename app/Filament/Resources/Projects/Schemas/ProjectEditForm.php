<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Models\Country;
use App\Models\Organization;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Illuminate\Support\HtmlString;

class ProjectEditForm
{
    // ─────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────

    protected static function options(string $key): array
    {
        $value = trans($key);

        return is_array($value) ? $value : [];
    }

    /**
     * Render the most recent state-history rows for a project as Arabic
     * HTML so user-supplied notes are visible in the edit page (otherwise
     * those notes are buried in the state-history resource).
     */
    protected static function renderRecentStateHistory(?Model $project): HtmlString
    {
        if (! $project || ! $project->getKey()) {
            return new HtmlString('<p style="color:#6b7280;">لا يوجد سجل بعد.</p>');
        }

        $rows = DB::table('project_state_histories')
            ->leftJoin('users', 'users.id', '=', 'project_state_histories.changed_by')
            ->where('project_id', $project->getKey())
            ->orderByDesc('project_state_histories.id')
            ->limit(8)
            ->get([
                'project_state_histories.from_state',
                'project_state_histories.to_state',
                'project_state_histories.notes',
                'project_state_histories.created_at',
                'users.name as user_name',
            ]);

        if ($rows->isEmpty()) {
            return new HtmlString('<p style="color:#6b7280;">لا يوجد سجل بعد.</p>');
        }

        $stateLabels = [
            'new' => 'جديد',
            'pending_readiness' => 'بانتظار الجاهزية',
            'ready_for_execution' => 'جاهز للتنفيذ',
            'in_execution' => 'قيد التنفيذ',
            'pending_documentation' => 'بانتظار التوثيق',
            'delayed' => 'متأخر',
            'completed' => 'مكتمل',
            'closed' => 'مغلق',
        ];

        $items = [];
        foreach ($rows as $row) {
            $from = $stateLabels[$row->from_state ?? ''] ?? ($row->from_state ?? '-');
            $to = $stateLabels[$row->to_state ?? ''] ?? ($row->to_state ?? '-');
            $when = $row->created_at ? date('Y-m-d H:i', strtotime((string) $row->created_at)) : '';
            $by = $row->user_name ?? 'النظام';
            $note = trim((string) ($row->notes ?? ''));
            $noteHtml = $note !== ''
                ? '<div style="margin-top:6px;padding:8px 10px;background:#f3f4f6;border-radius:8px;color:#111827;white-space:pre-wrap;">'.e($note).'</div>'
                : '';

            $items[] = '<li style="padding:10px 0;border-bottom:1px solid #e5e7eb;">'
                .'<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">'
                .'<span style="background:#e5e7eb;border-radius:6px;padding:2px 8px;font-size:.8rem;">'.e($from).'</span>'
                .'<span style="color:#6b7280;">←</span>'
                .'<span style="background:#dcfce7;color:#065f46;border-radius:6px;padding:2px 8px;font-size:.8rem;">'.e($to).'</span>'
                .'<span style="color:#6b7280;font-size:.8rem;">'.e($by).' • '.e($when).'</span>'
                .'</div>'
                .$noteHtml
                .'</li>';
        }

        $html = '<ul style="list-style:none;padding:0;margin:0;">'.implode('', $items).'</ul>';

        return new HtmlString($html);
    }

    /**
     * Render a compact summary of the project's payment schedule as Arabic
     * HTML so the user can see the upcoming/late instalments without
     * leaving the Edit page (the editable table is the relation manager
     * underneath this form).
     */
    protected static function renderPaymentsSummary(?Model $project): HtmlString
    {
        if (! $project || ! $project->getKey()) {
            return new HtmlString('<p style="color:#6b7280;">لا توجد دفعات مجدولة بعد. أضف الدفعات من القسم أدناه.</p>');
        }

        $rows = DB::table('project_payments')
            ->where('project_id', $project->getKey())
            ->orderBy('due_date')
            ->get([
                'due_date',
                'amount',
                'currency',
                'original_amount',
                'percentage',
                'status',
                'description',
            ]);

        if ($rows->isEmpty()) {
            return new HtmlString('<p style="color:#6b7280;">لا توجد دفعات مجدولة بعد. أضف الدفعات من القسم أدناه.</p>');
        }

        $statusLabels = [
            'pending' => ['قيد الانتظار', '#1f2937', '#dbeafe'],
            'notified' => ['تم إرسال التذكير', '#92400e', '#fef3c7'],
            'paid' => ['تم الدفع', '#065f46', '#dcfce7'],
            'canceled' => ['ملغاة', '#374151', '#e5e7eb'],
        ];

        $today = now()->startOfDay();
        $items = [];
        $totalUsd = 0.0;
        $totalPct = 0.0;

        foreach ($rows as $row) {
            $due = $row->due_date ? date('Y-m-d', strtotime((string) $row->due_date)) : '-';
            $isOverdue = $row->due_date && strtotime((string) $row->due_date) < $today->timestamp
                && ! in_array($row->status, ['paid', 'canceled'], true);
            $totalUsd += (float) $row->amount;
            $totalPct += (float) ($row->percentage ?? 0);

            $original = $row->original_amount !== null
                ? number_format((float) $row->original_amount, 2, '.', ',').' '.($row->currency ?: 'USD')
                : number_format((float) $row->amount, 2, '.', ',').' USD';
            $usdEquiv = $row->currency && $row->currency !== 'USD'
                ? ' (≈ '.number_format((float) $row->amount, 2, '.', ',').' USD)'
                : '';
            $pct = $row->percentage !== null
                ? ' • '.number_format((float) $row->percentage, 2, '.', '').'%'
                : '';

            [$statusText, $statusFg, $statusBg] = $statusLabels[$row->status] ?? [$row->status, '#1f2937', '#e5e7eb'];
            if ($isOverdue) {
                [$statusText, $statusFg, $statusBg] = ['متأخرة', '#991b1b', '#fee2e2'];
            }

            $note = trim((string) ($row->description ?? ''));
            $noteHtml = $note !== ''
                ? '<div style="margin-top:6px;color:#374151;font-size:.85rem;">'.e($note).'</div>'
                : '';

            $items[] = '<li style="padding:10px 0;border-bottom:1px solid #e5e7eb;">'
                .'<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">'
                .'<span style="background:#f3f4f6;border-radius:6px;padding:2px 8px;font-size:.8rem;">'.e($due).'</span>'
                .'<span style="font-weight:600;color:#111827;">'.e($original).'</span>'
                .'<span style="color:#6b7280;font-size:.85rem;">'.e($usdEquiv.$pct).'</span>'
                .'<span style="margin-inline-start:auto;background:'.$statusBg.';color:'.$statusFg.';border-radius:9999px;padding:2px 10px;font-size:.75rem;">'.e($statusText).'</span>'
                .'</div>'
                .$noteHtml
                .'</li>';
        }

        $totalsHtml = '<div style="margin-top:10px;display:flex;gap:16px;flex-wrap:wrap;color:#111827;">'
            .'<span><strong>إجمالي الدفعات (USD):</strong> '.number_format($totalUsd, 2, '.', ',').'</span>'
            .($totalPct > 0 ? '<span><strong>مجموع النسب:</strong> '.number_format($totalPct, 2, '.', '').'%</span>' : '')
            .'</div>';

        $html = '<ul style="list-style:none;padding:0;margin:0;">'.implode('', $items).'</ul>'.$totalsHtml;

        return new HtmlString($html);
    }

    /**
     * Responsive column counts. Mobile = 1 column, tablet = 2, desktop = 3.
     */
    protected static function responsiveColumns(int $desktop = 3): array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'lg' => 2,
            'xl' => $desktop,
            '2xl' => $desktop,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  Schema
    // ─────────────────────────────────────────────────────────────

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('project_edit_tabs')
                ->columnSpanFull()
                ->tabs([

                    // ══════════════════════════════════════════════════════
                    //  Tab 1 – Basic Information
                    // ══════════════════════════════════════════════════════
                    Tab::make(__('project.edit.tabs.basic'))
                        ->icon('heroicon-o-identification')
                        ->schema([

                            // ───────── Section A: Identification ─────────
                            Section::make('تعريف المشروع')
                                ->icon('heroicon-o-hashtag')
                                ->columns(self::responsiveColumns(6))
                                ->schema([
                                    TextInput::make('project_number')
                                        ->label(__('project.form.fields.project_number'))
                                        ->prefixIcon('heroicon-o-hashtag')
                                        ->disabled()
                                        ->dehydrated(false)
                                        ->columnSpan(['default' => 1, 'md' => 1, 'xl' => 2]),

                                    TextInput::make('title')
                                        ->label(__('project.form.fields.title'))
                                        ->prefixIcon('heroicon-o-pencil-square')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(['default' => 1, 'md' => 1, 'xl' => 3]),

                                    TextInput::make('beneficiaries_count')
                                        ->label(__('project.form.fields.beneficiaries_count'))
                                        ->prefixIcon('heroicon-o-users')
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0)
                                        ->dehydrated(
                                            fn (): bool => SchemaFacade::hasColumn('projects', 'beneficiaries_count')
                                        )
                                        ->columnSpan(['default' => 1, 'md' => 2, 'xl' => 1]),
                                ]),

                            // ───────── Section B: Location & Organisation ─────────
                            Section::make('الموقع والجهة الممولة')
                                ->icon('heroicon-o-map-pin')
                                ->columns(self::responsiveColumns(2))
                                ->schema([
                                    Select::make('country_id')
                                        ->label(__('project.form.fields.country'))
                                        ->prefixIcon('heroicon-o-globe-alt')
                                        ->options(fn () => Country::query()
                                            ->where('is_active', true)
                                            ->orderBy('sort_order')
                                            ->pluck('name_ar', 'id')
                                            ->all())
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->native(false),

                                    Select::make('organization_id')
                                        ->label(__('project.form.fields.organization'))
                                        ->prefixIcon('heroicon-o-building-office-2')
                                        ->options(fn () => Organization::query()
                                            ->where('is_active', true)
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(fn ($org) => [
                                                $org->id => ($org->organization_code
                                                    ? $org->organization_code.' - '
                                                    : '').$org->name,
                                            ])
                                            ->all())
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->native(false),
                                ]),

                            // ───────── Section C: Status & Financial ─────────
                            Section::make('الحالة والوضع المالي')
                                ->icon('heroicon-o-currency-dollar')
                                ->columns(self::responsiveColumns(3))
                                ->schema([
                                    TextInput::make('approved_amount')
                                        ->label(__('project.form.fields.approved_amount'))
                                        ->prefixIcon('heroicon-o-banknotes')
                                        ->numeric()
                                        ->step(0.01)
                                        ->minValue(0)
                                        ->required()
                                        ->prefix('USD')
                                        ->extraInputAttributes(['lang' => 'en', 'dir' => 'ltr', 'inputmode' => 'decimal']),

                                    Select::make('state')
                                        ->label(__('project.form.fields.state'))
                                        ->prefixIcon('heroicon-o-signal')
                                        ->options(fn () => self::options('project.form.options.states'))
                                        ->required()
                                        ->native(false),

                                    Select::make('financial_status')
                                        ->label(__('project.form.fields.financial_status'))
                                        ->prefixIcon('heroicon-o-currency-dollar')
                                        ->options(fn () => self::options('project.form.options.financial_statuses'))
                                        ->required()
                                        ->native(false),
                                ]),

                            // ───────── Section D: Documentation Settings ─────────
                            Section::make('إعدادات التوثيق')
                                ->icon('heroicon-o-clipboard-document-check')
                                ->columns(self::responsiveColumns(2))
                                ->schema([
                                    Select::make('documentation_type')
                                        ->label(__('project.form.fields.documentation_type'))
                                        ->prefixIcon('heroicon-o-document-duplicate')
                                        ->options(fn () => self::options('project.form.options.documentation_types'))
                                        ->default('one_time')
                                        ->native(false)
                                        ->dehydrated(
                                            fn (): bool => SchemaFacade::hasColumn('projects', 'documentation_type')
                                        ),

                                    Select::make('documentation_status')
                                        ->label(__('project.form.fields.documentation_status'))
                                        ->prefixIcon('heroicon-o-clipboard-document-check')
                                        ->options(fn () => self::options('project.form.options.documentation_statuses'))
                                        ->required()
                                        ->native(false),
                                ]),

                            // ───────── Section E: Timeline ─────────
                            Section::make('الجدول الزمني')
                                ->icon('heroicon-o-calendar-days')
                                ->columns(self::responsiveColumns(3))
                                ->schema([
                                    DatePicker::make('start_date')
                                        ->label(__('project.form.fields.start_date'))
                                        ->prefixIcon('heroicon-o-calendar-days')
                                        ->required()
                                        ->native(false),

                                    DatePicker::make('expected_end_date')
                                        ->label(__('project.form.fields.expected_end_date'))
                                        ->prefixIcon('heroicon-o-calendar')
                                        ->required()
                                        ->native(false),

                                    DatePicker::make('actual_end_date')
                                        ->label(__('project.form.fields.actual_end_date'))
                                        ->prefixIcon('heroicon-o-check-circle')
                                        ->native(false),
                                ]),

                            // ───────── Section F: Description ─────────
                            Section::make('وصف المشروع')
                                ->icon('heroicon-o-document-text')
                                ->collapsible()
                                ->schema([
                                    Textarea::make('description')
                                        ->label(__('project.form.fields.description'))
                                        ->rows(4)
                                        ->columnSpanFull(),
                                ]),

                            // ───────── Section G: Payments summary ─────────
                            Section::make('الدفعات المستحقة للمشروع')
                                ->icon('heroicon-o-banknotes')
                                ->description('ملخص جدول الدفعات — للإضافة/التعديل استخدم قسم "الدفعات المالية المجدولة" في أسفل الصفحة.')
                                ->collapsible()
                                ->schema([
                                    Placeholder::make('payments_summary')
                                        ->label('')
                                        ->columnSpanFull()
                                        ->content(fn (?Model $record): HtmlString => self::renderPaymentsSummary($record)),
                                ]),
                        ]),

                    // ══════════════════════════════════════════════════════
                    //  Tab 2 – Documentation & Reports
                    // ══════════════════════════════════════════════════════
                    Tab::make(__('project.edit.tabs.documentation'))
                        ->icon('heroicon-o-document-text')
                        ->schema([

                            // ───────── Notes ─────────
                            Section::make('الملاحظات والتقارير')
                                ->icon('heroicon-o-pencil-square')
                                ->columns(self::responsiveColumns(2))
                                ->schema([
                                    Textarea::make('readiness_notes')
                                        ->label(__('project.form.fields.readiness_notes'))
                                        ->rows(4),

                                    Textarea::make('execution_notes')
                                        ->label(__('project.form.fields.execution_notes'))
                                        ->rows(4),

                                    Textarea::make('documentation_notes')
                                        ->label(__('project.form.fields.documentation_notes'))
                                        ->rows(4),

                                    Textarea::make('final_report')
                                        ->label(__('project.form.fields.final_report'))
                                        ->rows(4),
                                ]),

                            // ───────── Recent state-history notes ─────────
                            Section::make('سجل تغييرات الحالة والملاحظات')
                                ->icon('heroicon-o-clock')
                                ->description('آخر التغييرات على حالة المشروع مع ملاحظات المستخدمين')
                                ->collapsible()
                                ->schema([
                                    Placeholder::make('state_history_recent')
                                        ->label('')
                                        ->columnSpanFull()
                                        ->content(fn (?Model $record): HtmlString => self::renderRecentStateHistory($record)),
                                ]),

                            // ───────── Media Links ─────────
                            Section::make('روابط الوسائط')
                                ->icon('heroicon-o-photo')
                                ->columns(self::responsiveColumns(2))
                                ->schema([
                                    TextInput::make('photo_album_url')
                                        ->label(__('project.form.fields.documentation_url'))
                                        ->prefixIcon('heroicon-o-photo')
                                        ->url()
                                        ->maxLength(2048)
                                        ->placeholder('https://...'),

                                    TextInput::make('video_album_url')
                                        ->label(__('project.form.fields.documentation_video_url'))
                                        ->prefixIcon('heroicon-o-video-camera')
                                        ->url()
                                        ->maxLength(2048)
                                        ->placeholder('https://...'),
                                ]),
                        ]),
                ]),
        ]);
    }
}
