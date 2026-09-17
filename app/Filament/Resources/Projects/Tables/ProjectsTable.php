<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Models\Project;
use App\Services\ActivityLogger;
use App\Services\InternalNotifier;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
      return $table
            ->recordUrl(fn (Model $record): string => url('/admin/project-workspace?project_id=' . $record->id))
            
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('project_number')
                    ->label('رقم المشروع')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('اسم المشروع')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('description')
                    ->label('وصف المشروع')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('country.name_ar')
                    ->label('الدولة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('organization.name')
                    ->label('الجهة الممولة')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('state')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'new' => 'جديد',
                        'pending_readiness' => 'بانتظار الجاهزية',
                        'ready_for_execution' => 'جاهز للتنفيذ',
                        'in_execution' => 'قيد التنفيذ',
                        'pending_documentation' => 'بانتظار التوثيق',
                        'delayed' => 'متأخر',
                        'completed' => 'مكتمل',
                        'closed' => 'مغلق',
                        default => $state ?? '-',
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'new' => 'gray',
                        'pending_readiness' => 'warning',
                        'ready_for_execution' => 'info',
                        'in_execution' => 'primary',
                        'pending_documentation' => 'warning',
                        'delayed' => 'danger',
                        'completed' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('documentation_status')
                    ->label('التوثيق')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'not_started' => 'غير موثق',
                        'partial' => 'جزئي',
                        'complete' => 'مكتمل',
                        default => $state ?? '-',
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'not_started' => 'danger',
                        'partial' => 'warning',
                        'complete' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('financial_status')
                    ->label('المالية')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'unfunded' => 'غير ممول',
                        'partially_funded' => 'تمويل جزئي',
                        'funded' => 'ممول',
                        'partially_spent' => 'صرف جزئي',
                        'settled' => 'مسوى',
                        default => $state ?? '-',
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'unfunded' => 'gray',
                        'partially_funded' => 'warning',
                        'funded' => 'success',
                        'partially_spent' => 'info',
                        'settled' => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('approved_amount')
                    ->label('المبلغ')
                    ->formatStateUsing(fn ($state) => $state === null ? '-' : 'USD ' . number_format((float) $state, 2, '.', ','))
                    ->sortable(),
            ])
            ->filters([
                // 1. فلتر الدولة
                SelectFilter::make('country_id')
                    ->label('الدولة')
                    ->relationship('country', 'name_ar')
                    ->searchable()
                    ->preload(),

                // 2. فلتر الجهة
                SelectFilter::make('organization_id')
                    ->label('الجهة الممولة')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload(),

                // 3. فلاتر الحالة
                SelectFilter::make('state')
                    ->label('حالة المشروع')
                    ->options([
                        'new' => 'جديد',
                        'pending_readiness' => 'بانتظار الجاهزية',
                        'ready_for_execution' => 'جاهز للتنفيذ',
                        'in_execution' => 'قيد التنفيذ',
                        'pending_documentation' => 'بانتظار التوثيق',
                        'delayed' => 'متأخر',
                        'completed' => 'مكتمل',
                        'closed' => 'مغلق',
                    ]),

                SelectFilter::make('documentation_status')
                    ->label('حالة التوثيق')
                    ->options([
                        'not_started' => 'غير موثق',
                        'partial' => 'جزئي',
                        'complete' => 'مكتمل',
                    ]),

                SelectFilter::make('financial_status')
                    ->label('الحالة المالية')
                    ->options([
                        'unfunded' => 'غير ممول',
                        'partially_funded' => 'تمويل جزئي',
                        'funded' => 'ممول',
                        'partially_spent' => 'صرف جزئي',
                        'settled' => 'مسوى',
                    ]),

                // 4. فلتر الأرشيف (يظهر المشاريع المؤرشفة أو النشطة أو الكل)
                TernaryFilter::make('is_archived')
                    ->label('الأرشيف')
                    ->placeholder('الكل')
                    ->trueLabel('المشاريع المؤرشفة فقط')
                    ->falseLabel('المشاريع النشطة فقط')
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_archived', true),
                        false: fn (Builder $query) => $query->where('is_archived', false)->orWhereNull('is_archived'),
                        blank: fn (Builder $query) => $query,
                    ),

                // 5. فلتر نطاق الميزانية (من - إلى)
                Filter::make('approved_amount')
                    ->form([
                        TextInput::make('amount_from')
                            ->label('الميزانية (من)')
                            ->numeric()
                            ->prefix('$'),
                        TextInput::make('amount_to')
                            ->label('الميزانية (إلى)')
                            ->numeric()
                            ->prefix('$'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'],
                                fn (Builder $query, $amount): Builder => $query->where('approved_amount', '>=', $amount),
                            )
                            ->when(
                                $data['amount_to'],
                                fn (Builder $query, $amount): Builder => $query->where('approved_amount', '<=', $amount),
                            );
                    }),

                // 6. فلتر نطاق تاريخ البداية أو الإنشاء (كمثال: created_at)
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label('تاريخ الإضافة (من)'),
                        DatePicker::make('created_until')->label('تاريخ الإضافة (إلى)'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            // ─── إعدادات عرض الفلاتر في الواجهة ───
            ->filtersLayout(FiltersLayout::AboveContentCollapsible) // عرض الفلاتر أعلى الجدول مع إمكانية طيها
            ->filtersFormColumns(4) // تقسيم الفلاتر إلى 4 أعمدة لترتيبها بشكل أفقي جميل
            ->deferFilters() // تأجيل تنفيذ الفلترة حتى يضغط المستخدم على "تطبيق" (يقلل الضغط على السيرفر)
            
            ->recordActions([
                // ─── الإجراء الأساسي الظاهر مباشرة (زر واحد فقط لتجنّب أي scroll) ─
                Actions\Action::make('workspace')
                    ->label('فتح')
                    ->icon('heroicon-o-folder-open')
                    ->color('primary')
                    ->button()
                    ->url(fn ($record) => url('/admin/project-workspace?project_id=' . $record->id)),

                // ─── باقي الإجراءات ضمن قائمة منسدلة (إجراءات) ───────────────
                Actions\ActionGroup::make([
                Actions\EditAction::make()
                    ->label('تعديل')
                    ->authorize(fn (Model $record): bool => Auth::user()?->can('update', $record) ?? false),

                // ─── وصول سريع لإضافة/تحديث رابط التوثيق ──────────────────────
                // متاح أيضاً لمعزز المشروع (الدولة + المركزي) + المنفذ + الإدارة
                // — حتى لو ما عندهم صلاحية تعديل المشروع، لأنه إجراء محدود
                // النطاق على حقلين فقط (روابط ألبوم الصور والفيديو).
                Actions\Action::make('add_documentation_url_table')
                    ->label('رابط التوثيق')
                    ->icon('heroicon-o-link')
                    ->color('warning')
                    ->visible(function (Project $record): bool {
                        $user = Auth::user();
                        if (! $user) {
                            return false;
                        }
                        if ($user->hasAnyRole([
                            'system_admin',
                            'project_executor',
                            'enhancer_entry_country',
                            'enhancer_finance_central',
                        ])) {
                            // For country-scoped roles, also enforce country boundary.
                            return $user->hasCountryAccess($record->country_id);
                        }
                        return false;
                    })
                    ->modalHeading('إضافة/تحديث روابط التوثيق')
                    ->fillForm(fn (Project $record): array => [
                        'photo_album_url' => $record->photo_album_url,
                        'video_album_url' => $record->video_album_url,
                    ])
                    ->form([
                        TextInput::make('photo_album_url')
                            ->label('رابط ألبوم الصور')
                            ->url()
                            ->prefixIcon('heroicon-o-photo')
                            ->maxLength(2048)
                            ->placeholder('https://...'),
                        TextInput::make('video_album_url')
                            ->label('رابط إنتاج الفيديو')
                            ->url()
                            ->prefixIcon('heroicon-o-video-camera')
                            ->maxLength(2048)
                            ->placeholder('https://...'),
                    ])
                    ->action(function (Project $record, array $data): void {
                        $record->update([
                            'photo_album_url' => $data['photo_album_url'] ?? null,
                            'video_album_url' => $data['video_album_url'] ?? null,
                            'updated_by' => Auth::id(),
                        ]);
                        ActivityLogger::log(
                            'project.documentation_url_updated',
                            'تحديث رابط التوثيق',
                            $record
                        );
                        Notification::make()->success()->title('تم حفظ رابط التوثيق')->send();
                    }),

                // ─── Quick edit: نوع التوثيق ──────────────────────────────
                // يُتاح لمدخل معززات الدولة + منفذ المشروع + الإدارة + المركزي،
                // حتى لو ما عندهم صلاحية تعديل سجل المشروع بالكامل، لأن هذا
                // إجراء محدود على حقل واحد فقط.
                Actions\Action::make('set_documentation_type')
                    ->label('نوع التوثيق')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->visible(function (Project $record): bool {
                        if (! Schema::hasColumn('projects', 'documentation_type')) {
                            return false;
                        }
                        $user = Auth::user();
                        if (! $user) {
                            return false;
                        }
                        if ($user->hasAnyRole([
                            'system_admin',
                            'project_executor',
                            'enhancer_entry_country',
                            'enhancer_finance_central',
                        ])) {
                            return $user->hasCountryAccess($record->country_id);
                        }
                        return false;
                    })
                    ->modalHeading('تحديث نوع التوثيق')
                    ->fillForm(fn (Project $record): array => [
                        'documentation_type' => $record->documentation_type,
                    ])
                    ->form([
                        Select::make('documentation_type')
                            ->label('نوع التوثيق')
                            ->options([
                                'one_time' => __('project.form.options.documentation_types.one_time'),
                                'continuous' => __('project.form.options.documentation_types.continuous'),
                                'periodic' => __('project.form.options.documentation_types.periodic'),
                            ])
                            ->native(false)
                            ->required(),
                    ])
                    ->action(function (Project $record, array $data): void {
                        $record->update([
                            'documentation_type' => $data['documentation_type'],
                            'updated_by' => Auth::id(),
                        ]);
                        ActivityLogger::log(
                            'project.documentation_type_updated',
                            'تحديث نوع التوثيق',
                            $record
                        );
                        Notification::make()->success()->title('تم حفظ نوع التوثيق')->send();
                    }),

                // ─── Workflow: approve readiness ─────────────────────────
                Actions\Action::make('approveReadiness')
                    ->label('اعتماد الجاهزية')
                    ->icon('heroicon-o-check-badge')
                    ->color('info')
                    ->visible(fn (Project $record): bool => Auth::user()?->can('approveReadiness', $record) ?? false)
                    ->form([
                        Select::make('decision')
                            ->label('القرار')
                            ->options([
                                'ready_for_execution' => 'تم الاستلام وجاهز للتنفيذ',
                                'rejected' => 'رفض الجاهزية',
                            ])
                            ->required()
                            ->default('ready_for_execution'),
                        Textarea::make('readiness_notes')
                            ->label('ملاحظات الجاهزية')
                            ->rows(3),
                    ])
                    ->action(function (Project $record, array $data): void {
                        $previous = $record->state;
                        $decision = $data['decision'];

                        // Rejection: keep the project in pending_readiness so
                        // the creator can fix and resubmit — NEVER promote to
                        // 'delayed' (delayed is reserved for execution-phase
                        // overruns).
                        $newState = $decision === 'rejected'
                            ? 'pending_readiness'
                            : 'ready_for_execution';

                        $record->stateChangeNote = $data['readiness_notes'] ?? null;
                        $record->update([
                            'state' => $newState,
                            'readiness_notes' => $data['readiness_notes'] ?? $record->readiness_notes,
                            'updated_by' => Auth::id(),
                        ]);

                        // Rejection keeps the project in pending_readiness; if
                        // the project was already in that state, Eloquent sees
                        // no state change and the Observer-driven history
                        // insert is skipped. Log manually ONLY in that case —
                        // otherwise (e.g. coming from 'new') the observer
                        // already inserts the row and a second manual insert
                        // would duplicate it.
                        if ($decision === 'rejected' && $previous === $newState) {
                            DB::table('project_state_histories')->insert([
                                'project_id' => $record->id,
                                'from_state' => $previous,
                                'to_state' => $newState,
                                'changed_by' => Auth::id() ?? $record->updated_by ?? $record->created_by,
                                'notes' => filled($data['readiness_notes'] ?? null)
                                    ? (string) $data['readiness_notes']
                                    : 'رفض الجاهزية',
                                'created_at' => now(),
                            ]);
                        }

                        $event = $decision === 'rejected'
                            ? 'project.readiness_rejected'
                            : 'project.readiness_approved';
                        $description = $decision === 'rejected'
                            ? 'رفض الجاهزية'
                            : 'اعتماد الجاهزية';

                        ActivityLogger::log($event, $description, $record, [
                            'from' => $previous, 'to' => $newState, 'notes' => $data['readiness_notes'] ?? null,
                        ]);
                        InternalNotifier::notifyRoles(
                            ['system_admin', 'project_executor', 'board_supervisor'],
                            'notifications.project.readiness_decided.title',
                            'notifications.project.readiness_decided.body',
                            ['project' => $record->project_number]
                        );
                        Notification::make()->success()
                            ->title($decision === 'rejected' ? 'تم رفض الجاهزية' : 'تم اعتماد الجاهزية')
                            ->send();
                    }),

                // ─── Workflow: execution update ─────────────────────────
                Actions\Action::make('updateExecution')
                    ->label('تحديث التنفيذ')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('primary')
                    ->visible(fn (Project $record): bool => Auth::user()?->can('updateExecution', $record) ?? false)
                    ->form([
                        Select::make('state')
                            ->label('حالة التنفيذ')
                            ->options([
                                'in_execution' => 'قيد التنفيذ',
                                'completed' => 'تم التنفيذ',
                                'delayed' => 'متأخر',
                                'pending_documentation' => 'بانتظار التوثيق',
                            ])
                            ->required(),
                        Textarea::make('execution_notes')->label('ملاحظات')->rows(3),
                    ])
                    ->action(function (Project $record, array $data): void {
                        $previous = $record->state;
                        $record->stateChangeNote = $data['execution_notes'] ?? null;
                        $record->update([
                            'state' => $data['state'],
                            'execution_notes' => $data['execution_notes'] ?? $record->execution_notes,
                            'updated_by' => Auth::id(),
                        ]);
                        ActivityLogger::log('project.execution_updated', 'تحديث حالة التنفيذ', $record, [
                            'from' => $previous, 'to' => $data['state'], 'notes' => $data['execution_notes'] ?? null,
                        ]);
                        InternalNotifier::notifyRoles(
                            ['system_admin', 'board_supervisor', 'final_report_preparer'],
                            'notifications.project.execution_updated.title',
                            'notifications.project.execution_updated.body',
                            ['project' => $record->project_number, 'state' => $data['state']]
                        );
                        Notification::make()->success()->title('تم تحديث حالة التنفيذ')->send();
                    }),

                // ─── Workflow: prepare final report ─────────────────────
                Actions\Action::make('prepareFinalReport')
                    ->label('إعداد التقرير النهائي')
                    ->icon('heroicon-o-document-text')
                    ->color('warning')
                    ->visible(fn (Project $record): bool => Auth::user()?->can('prepareFinalReport', $record) ?? false)
                    ->form([
                        Textarea::make('final_report')
                            ->label('التقرير النهائي')
                            ->rows(8)
                            ->required(),
                    ])
                    ->fillForm(fn (Project $record): array => ['final_report' => $record->final_report])
                    ->action(function (Project $record, array $data): void {
                        $record->update([
                            'final_report' => $data['final_report'],
                            'updated_by' => Auth::id(),
                        ]);
                        ActivityLogger::log('project.final_report_drafted', 'تحديث التقرير النهائي', $record);
                        InternalNotifier::notifyRoles(
                            ['board_supervisor'],
                            'notifications.project.final_report_drafted.title',
                            'notifications.project.final_report_drafted.body',
                            ['project' => $record->project_number]
                        );
                        Notification::make()->success()->title('تم حفظ التقرير النهائي')->send();
                    }),

                // ─── Workflow: approve final report ─────────────────────
                Actions\Action::make('approveFinalReport')
                    ->label('اعتماد التقرير النهائي')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Project $record): bool => Auth::user()?->can('approveFinalReport', $record) ?? false)
                    ->action(function (Project $record): void {
                        $record->update([
                            'final_report_approved' => true,
                            'final_report_approved_by' => Auth::id(),
                            'final_report_approved_at' => now(),
                            'updated_by' => Auth::id(),
                        ]);
                        ActivityLogger::log('project.final_report_approved', 'اعتماد التقرير النهائي', $record);
                        // dispatch() applies country scoping for enhancer_finance_central.
                        InternalNotifier::dispatch('project.final_report_approved', $record);
                        Notification::make()->success()->title('تم اعتماد التقرير النهائي')->send();
                    }),

                // ─── Workflow: rollback (board supervisor) ──────────────
                Actions\Action::make('rollback')
                    ->label('إرجاع لمرحلة سابقة')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (Project $record): bool => Auth::user()?->can('rollback', $record) ?? false)
                    ->form([
                        Select::make('to_state')
                            ->label('إلى الحالة')
                            ->options([
                                'new' => 'جديد',
                                'pending_readiness' => 'بانتظار الجاهزية',
                                'ready_for_execution' => 'جاهز للتنفيذ',
                                'in_execution' => 'قيد التنفيذ',
                                'pending_documentation' => 'بانتظار التوثيق',
                            ])
                            ->required(),
                        Textarea::make('reason')->label('سبب الإرجاع')->rows(3)->required(),
                    ])
                    ->action(function (Project $record, array $data): void {
                        $previous = $record->state;
                        $record->stateChangeNote = $data['reason'] ?? null;
                        // ProjectObserver records the state-history row automatically.
                        $record->update([
                            'state' => $data['to_state'],
                            'updated_by' => Auth::id(),
                        ]);
                        ActivityLogger::log('project.rolled_back', 'إرجاع المشروع لمرحلة سابقة', $record, [
                            'from' => $previous, 'to' => $data['to_state'], 'reason' => $data['reason'],
                        ]);
                        InternalNotifier::notifyRoles(
                            ['system_admin', 'project_creator', 'project_executor'],
                            'notifications.project.rolled_back.title',
                            'notifications.project.rolled_back.body',
                            ['project' => $record->project_number]
                        );
                        Notification::make()->warning()->title('تم إرجاع المشروع')->send();
                    }),

                // ─── Workflow: close project ────────────────────────────
                Actions\Action::make('close')
                    ->label('إغلاق المشروع')
                    ->icon('heroicon-o-lock-closed')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('يتم الإغلاق فقط بعد اعتماد التقرير النهائي وانعدام الرصيد المالي.')
                    ->visible(fn (Project $record): bool => Auth::user()?->can('close', $record) ?? false)
                    ->action(function (Project $record): void {
                        $record->update(['state' => 'closed', 'updated_by' => Auth::id()]);
                        ActivityLogger::log('project.closed', 'إغلاق المشروع', $record);
                        // dispatch() applies country scoping for enhancer_finance_central.
                        InternalNotifier::dispatch('project.closed', $record);
                        Notification::make()->success()->title('تم إغلاق المشروع')->send();
                    }),

                Actions\Action::make('financial')
                    ->label('الحوالات')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn (): bool => \App\Filament\Resources\FinancialTransactions\FinancialTransactionResource::canAccess())
                    ->url(fn ($record) => url('/admin/financial-transactions?tableFilters[project_id][value]=' . $record->id)),

                Actions\Action::make('attachments')
                    ->label('المرفقات')
                    ->icon('heroicon-o-paper-clip')
                    ->color('info')
                    ->url(fn ($record) => url('/admin/attachments?tableFilters[project_id][value]=' . $record->id)),

                Actions\Action::make('activity')
                    ->label('السجل')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->url(fn ($record) => url('/admin/activity-log/activity-logs?tableFilters[project_id][value]=' . $record->id)),

                Actions\Action::make('states')
                    ->label('الحالات')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('warning')
                    ->url(fn ($record) => url('/admin/project-state-histories?tableFilters[project_id][value]=' . $record->id)),

                Actions\Action::make('archive')
                    ->label('أرشفة')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('أرشفة المشروع')
                    ->modalDescription('سيتم نقل المشروع إلى الأرشيف ويمكن استعادته لاحقًا.')
                    ->modalSubmitActionLabel('نعم، أرشف')
                    ->visible(fn (Model $record): bool => ! self::isArchivedRecord($record) && (Auth::user()?->hasRole('system_admin') ?? false))
                    ->action(function (Model $record): void {
                        if (Schema::hasColumn('projects', 'is_archived')) {
                            $record->update(['is_archived' => true]);
                        } elseif (Schema::hasColumn('projects', 'archived_at')) {
                            $record->update(['archived_at' => now()]);
                        }

                        ActivityLogger::log('project.archived', 'أرشفة المشروع', $record);
                    }),

                Actions\Action::make('restore')
                    ->label('استعادة')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('استعادة المشروع')
                    ->modalDescription('سيتم إعادة المشروع من الأرشيف إلى القائمة النشطة.')
                    ->modalSubmitActionLabel('نعم، استعد')
                    ->visible(fn (Model $record): bool => self::isArchivedRecord($record) && (Auth::user()?->hasRole('system_admin') ?? false))
                    ->action(function (Model $record): void {
                        if (Schema::hasColumn('projects', 'is_archived')) {
                            $record->update(['is_archived' => false]);
                        } elseif (Schema::hasColumn('projects', 'archived_at')) {
                            $record->update(['archived_at' => null]);
                        }

                        ActivityLogger::log('project.restored', 'استعادة المشروع من الأرشيف', $record);
                    }),

                // Hard delete is only for مدير النظام (Gate::before grants
                // system_admin any ability; ProjectPolicy::delete returns false for
                // everyone else, so the action is auto-hidden for them).
                Actions\DeleteAction::make()
                    ->label('حذف نهائي')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->modalHeading('حذف المشروع نهائياً')
                    ->modalDescription('سيتم حذف المشروع وجميع بياناته نهائياً. هذه العملية لا يمكن التراجع عنها. للأرشفة استخدم زر "أرشفة" بدلاً من ذلك.')
                    ->modalSubmitActionLabel('نعم، احذف نهائياً')
                    ->before(fn (Project $record) => ActivityLogger::log('project.deleted', 'حذف مشروع', $record)),
                ])
                    ->label('إجراءات')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                Actions\CreateAction::make()->label('إضافة مشروع'),
            ])
            ->bulkActions([]);
    }

    protected static function isArchivedRecord(Model $record): bool
    {
        if (Schema::hasColumn('projects', 'is_archived')) {
            return (bool) $record->getAttribute('is_archived');
        }

        if (Schema::hasColumn('projects', 'archived_at')) {
            return filled($record->getAttribute('archived_at'));
        }

        return false;
    }
}