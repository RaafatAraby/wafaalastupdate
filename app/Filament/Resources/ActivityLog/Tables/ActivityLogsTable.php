<?php

namespace App\Filament\Resources\ActivityLog\Tables;

use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('activity_log.fields.date'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('project.project_number')
                    ->label(__('activity_log.fields.project_number'))
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('project.title')
                    ->label(__('activity_log.fields.project'))
                    ->searchable()
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('event')
                    ->label(__('activity_log.fields.event'))
                    ->badge()
                    ->color('primary')
                    // هذه الدالة الآن ستعمل بشكل سحري بفضل المصفوفة المتداخلة
                    ->formatStateUsing(fn (?string $state) => $state ? __('activity_log.events.' . $state) : '-')
                    ->searchable(),

                TextColumn::make('description')
                    ->label(__('activity_log.fields.description'))
                    ->wrap()
                    ->searchable(),

                TextColumn::make('causer.name')
                    ->label(__('activity_log.fields.causer'))
                    ->searchable()
                    ->placeholder(__('activity_log.fields.system')),

                TextColumn::make('subject_type')
                    ->label(__('activity_log.fields.subject_type'))
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'App\\Models\\Project' => __('activity_log.subject_types.project'),
                        'App\\Models\\FinancialTransaction' => __('activity_log.subject_types.financial_transaction'),
                        'App\\Models\\Attachment' => __('activity_log.subject_types.attachment'),
                        default => $state ?: '-',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label(__('activity_log.fields.event'))
                    // ربط الفلتر مباشرة مع الكلمات المترجمة الجديدة
                    ->options([
                        'project.created' => __('activity_log.events.project.created'),
                        'project.updated' => __('activity_log.events.project.updated'),
                        'financial.created' => __('activity_log.events.financial.created'),
                        'financial.updated' => __('activity_log.events.financial.updated'),
                        'financial.deleted' => __('activity_log.events.financial.deleted'),
                        'attachment.created' => __('activity_log.events.attachment.created'),
                        'attachment.deleted' => __('activity_log.events.attachment.deleted'),
                    ]),

                SelectFilter::make('project_id')
                    ->label(__('activity_log.fields.project'))
                    ->relationship('project', 'project_number'),

                SelectFilter::make('causer_id')
                    ->label(__('activity_log.fields.user'))
                    ->relationship('causer', 'name'),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->bulkActions([]);
    }
}