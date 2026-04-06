<?php

namespace App\Filament\Resources\Projects\Tables;

use App\Services\ActivityLogger;
use Filament\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
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

                TextColumn::make('country.name_ar')
                    ->label('الدولة')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('organization.name')
                    ->label('الجهة')
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
                    ->money('USD')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('country_id')
                    ->label('الدولة')
                    ->relationship('country', 'name_ar')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('organization_id')
                    ->label('الجهة')
                    ->relationship('organization', 'name')
                    ->searchable()
                    ->preload(),

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
            ])
            ->recordActions([
                Actions\Action::make('workspace')
                    ->label('الصفحة الداخلية')
                    ->icon('heroicon-o-folder-open')
                    ->color('primary')
                    ->url(fn ($record) => url('/admin/project-workspace?project_id=' . $record->id)),

                Actions\EditAction::make()->label('تعديل'),

                Actions\Action::make('financial')
                    ->label('الحوالات')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
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
                    ->visible(fn (Model $record): bool => ! self::isArchivedRecord($record))
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
                    ->visible(fn (Model $record): bool => self::isArchivedRecord($record))
                    ->action(function (Model $record): void {
                        if (Schema::hasColumn('projects', 'is_archived')) {
                            $record->update(['is_archived' => false]);
                        } elseif (Schema::hasColumn('projects', 'archived_at')) {
                            $record->update(['archived_at' => null]);
                        }

                        ActivityLogger::log('project.restored', 'استعادة المشروع من الأرشيف', $record);
                    }),
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
