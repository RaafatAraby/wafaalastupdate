<?php

namespace App\Filament\Resources\FinancialTransactions\Tables;

use App\Models\Country;
use App\Models\FinancialTransaction;
use App\Models\Organization;
use App\Models\Project;
use App\Services\ActivityLogger;
use App\Services\InternalNotifier;
use App\Services\ProjectAutoClose;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class FinancialTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                if (request()->filled('project_id')) {
                    $query->where('project_id', (int) request()->query('project_id'));
                }

                return $query;
            })
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('project.project_number')
                    ->label(__('financial_transaction.table.columns.project_number'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('project.title')
                    ->label(__('financial_transaction.table.columns.project'))
                    ->searchable()
                    ->wrap(),

                TextColumn::make('transaction_type')
                    ->label(__('financial_transaction.table.columns.transaction_type'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => __('financial_transaction.form.options.transaction_types.' . ($state ?? 'incoming'))),

                TextColumn::make('amount')
                    ->label(__('financial_transaction.table.columns.amount'))
                    ->formatStateUsing(fn ($state) => $state === null ? '-' : 'USD ' . number_format((float) $state, 2, '.', ','))
                    ->sortable(),

                TextColumn::make('approval_status')
                    ->label(__('financial_transaction.table.columns.approval_status'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => __('financial_transaction.table.approval.' . ($state ?? 'pending'))),

                TextColumn::make('transaction_date')
                    ->label(__('financial_transaction.table.columns.transaction_date'))
                    ->date('Y-m-d')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('country_id')
                    ->label(__('financial_transaction.form.fields.country'))
                    ->options(self::countryOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, $value) => $query->whereHas('project', fn (Builder $projectQuery) => $projectQuery->where('country_id', $value))
                        );
                    }),

                SelectFilter::make('organization_id')
                    ->label(__('financial_transaction.form.fields.organization'))
                    ->options(self::organizationOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, $value) => $query->whereHas('project', fn (Builder $projectQuery) => $projectQuery->where('organization_id', $value))
                        );
                    }),

           SelectFilter::make('project_id')
    ->label(__('financial_transaction.form.fields.project'))
    ->options(
        \App\Models\Project::query()
            ->orderBy('project_number')
            ->get()
            ->mapWithKeys(fn ($project) => [
                $project->getKey() => trim(($project->project_number ? $project->project_number . ' - ' : '') . ($project->title ?? $project->name ?? $project->project_name ?? ''))
            ])
            ->toArray()
    )
    ->searchable()
    ->preload(),


SelectFilter::make('bank_name')
    ->label(__('financial_transaction.form.fields.bank_name'))
    ->options(
        \App\Models\FinancialTransaction::query()
            ->whereNotNull('bank_name')
            ->where('bank_name', '!=', '')
            ->distinct()
            ->orderBy('bank_name')
            ->pluck('bank_name', 'bank_name')
            ->toArray()
    )
    ->searchable()
    ->preload(),


                SelectFilter::make('transaction_type')
                    ->label(__('financial_transaction.form.fields.transaction_type'))
                    ->options([
                        'incoming' => __('financial_transaction.form.options.transaction_types.incoming'),
                        'outgoing' => __('financial_transaction.form.options.transaction_types.outgoing'),
                    ]),

                SelectFilter::make('approval_status')
                    ->label(__('financial_transaction.form.fields.approval_status'))
                    ->options([
                        'pending' => __('financial_transaction.table.approval.pending'),
                        'approved' => __('financial_transaction.table.approval.approved'),
                        'rejected' => __('financial_transaction.table.approval.rejected'),
                        'returned' => __('financial_transaction.table.approval.returned'),
                    ]),

                SelectFilter::make('bank_name')
                    ->label(__('financial_transaction.form.fields.bank_name'))
                    ->options(self::bankOptions())
                    ->visible(fn () => SchemaFacade::hasColumn('financial_transactions', 'bank_name')),

                Filter::make('transaction_date')
                    ->form([
                        DatePicker::make('from')->label('من تاريخ'),
                        DatePicker::make('until')->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(5)
            ->deferFilters()
            ->recordActions([
                // Read-only details modal: visible to anyone who can view
                // the record. Roles with view+create only (no update)
                // — e.g. enhancer_finance_central — use this to inspect
                // existing transactions without an edit form.
                Actions\ViewAction::make()
                    ->label('تفاصيل')
                    ->modalHeading('عرض حركة مالية')
                    ->modalWidth('4xl')
                    ->slideOver(false)
                    ->authorize(fn (FinancialTransaction $record): bool => Auth::user()?->can('view', $record) ?? false),

                Actions\EditAction::make()
                    ->label(__('financial_transaction.table.actions.edit'))
                    ->modalWidth('4xl')
                    ->slideOver(false)
                    ->authorize(fn (FinancialTransaction $record): bool => Auth::user()?->can('update', $record) ?? false),

                Actions\Action::make('approve')
                    ->label('اعتماد')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (FinancialTransaction $record): bool => ($record->approval_status !== 'approved')
                        && (Auth::user()?->can('approve', $record) ?? false))
                    ->action(function (FinancialTransaction $record): void {
                        $record->forceFill([
                            'approval_status' => 'approved',
                            'reviewed_by' => Auth::id(),
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                        ])->saveQuietly();
                        ActivityLogger::log('financial.approved', 'اعتماد حركة مالية', $record);
                        // Config-driven dispatch — reads recipients from
                        // config/notifications-recipients.php so the role list
                        // stays in one place instead of being hard-coded here.
                        InternalNotifier::dispatch('financial.approved', $record, [
                            'ref' => $record->reference_no ?? $record->id,
                        ]);
                        // Manual auto-close trigger — saveQuietly() bypasses the
                        // FinancialTransactionObserver, so the approved-balance
                        // recomputation must be invoked explicitly here.
                        ProjectAutoClose::evaluate(
                            Project::with('organization')->find($record->project_id)
                        );
                        Notification::make()->success()->title('تم اعتماد الحوالة')->send();
                    }),

                Actions\Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (FinancialTransaction $record): bool => ($record->approval_status !== 'approved')
                        && (Auth::user()?->can('reject', $record) ?? false))
                    ->form([
                        Textarea::make('review_note')->label('سبب الرفض')->required()->rows(3),
                    ])
                    ->action(function (FinancialTransaction $record, array $data): void {
                        // Once approved, a transaction can never be rejected —
                        // not even by system_admin. Enforced in action body
                        // because Gate::before lets system_admin bypass the
                        // policy-level check otherwise.
                        if ($record->approval_status === 'approved') {
                            Notification::make()->danger()
                                ->title('لا يمكن رفض حركة مُعتمدة')
                                ->send();
                            return;
                        }

                        $record->forceFill([
                            'approval_status' => 'rejected',
                            'reviewed_by' => Auth::id(),
                            'review_note' => $data['review_note'],
                            'approved_at' => null,
                            'approved_by' => null,
                        ])->saveQuietly();
                        ActivityLogger::log('financial.rejected', 'رفض حركة مالية', $record, ['reason' => $data['review_note']]);
                        // Notify rejection through the same config-driven pipe
                        // (system_admin + enhancer_finance_central per config).
                        InternalNotifier::dispatch('financial.rejected', $record, [
                            'ref' => $record->reference_no ?? $record->id,
                            // InternalNotifier maps `notes` into the rendered
                            // body — `reason` would be silently dropped.
                            'notes' => $data['review_note'],
                        ]);
                        // Re-evaluate auto-close: a rejection un-approves the
                        // transaction, which can change the approved balance.
                        ProjectAutoClose::evaluate(
                            Project::with('organization')->find($record->project_id)
                        );
                        Notification::make()->warning()->title('تم رفض الحوالة')->send();
                    }),

                // Hard delete is only for مدير النظام (granted via Gate::before;
                // FinancialTransactionPolicy::delete returns false for everyone else).
                Actions\DeleteAction::make()
                    ->label('حذف')
                    ->requiresConfirmation()
                    ->modalHeading('حذف الحوالة')
                    ->modalDescription('سيتم حذف الحوالة نهائياً. هذه العملية لا يمكن التراجع عنها.')
                    ->modalSubmitActionLabel('نعم، حذف'),
                    // حذف: التسجيل في سجل العمليات يتم تلقائياً عبر FinancialTransactionObserver::deleted()
                    // لتجنب تكرار سطور ActivityLog على نفس الحدث.
            ]);
    }

    private static function bankOptions(): array
    {
        return [
            'بنك فلسطين' => 'بنك فلسطين',
            'البنك الإسلامي الفلسطيني' => 'البنك الإسلامي الفلسطيني',
            'البنك الوطني' => 'البنك الوطني',
            'بنك القدس' => 'بنك القدس',
            'بنك الإسكان' => 'بنك الإسكان',
            'بنك القاهرة عمان' => 'بنك القاهرة عمان',
            'بنك الأردن' => 'بنك الأردن',
            'أخرى' => 'أخرى',
        ];
    }

    private static function countryOptions(): array
    {
        $label = self::countryLabelColumn();

        return Country::query()
            ->orderBy($label)
            ->pluck($label, 'id')
            ->toArray();
    }

    private static function organizationOptions(): array
    {
        $label = self::organizationLabelColumn();

        return Organization::query()
            ->orderBy($label)
            ->pluck($label, 'id')
            ->toArray();
    }

    private static function countryLabelColumn(): string
    {
        foreach (['name', 'title', 'country_name', 'name_ar', 'ar_name'] as $column) {
            if (SchemaFacade::hasColumn('countries', $column)) {
                return $column;
            }
        }

        return 'id';
    }

    private static function organizationLabelColumn(): string
    {
        foreach (['name', 'title', 'organization_name'] as $column) {
            if (SchemaFacade::hasColumn('organizations', $column)) {
                return $column;
            }
        }

        return 'id';
    }
}
