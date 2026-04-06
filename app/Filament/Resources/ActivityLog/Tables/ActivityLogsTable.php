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
                    ->label('التاريخ')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('project.project_number')
                    ->label('رقم المشروع')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('project.title')
                    ->label('المشروع')
                    ->searchable()
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('event')
                    ->label('الحدث')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('description')
                    ->label('الوصف')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('causer.name')
                    ->label('بواسطة')
                    ->searchable()
                    ->placeholder('النظام'),

                TextColumn::make('subject_type')
                    ->label('النوع')
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'App\\Models\\Project' => 'مشروع',
                        'App\\Models\\FinancialTransaction' => 'حركة مالية',
                        'App\\Models\\Attachment' => 'مرفق',
                        default => $state ?: '-',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label('الحدث')
                    ->options([
                        'project.created' => 'إنشاء مشروع',
                        'project.updated' => 'تحديث مشروع',
                        'financial.created' => 'إضافة حركة مالية',
                        'financial.updated' => 'تعديل حركة مالية',
                        'financial.deleted' => 'حذف حركة مالية',
                        'attachment.created' => 'إضافة مرفق',
                        'attachment.deleted' => 'حذف مرفق',
                    ]),

                SelectFilter::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'project_number'),

                SelectFilter::make('causer_id')
                    ->label('المستخدم')
                    ->relationship('causer', 'name'),
                    
                    SelectFilter::make('project_id')
    ->label('المشروع')
    ->relationship('project', 'project_number'),

            ])
            ->recordActions([])
            ->toolbarActions([])
            ->bulkActions([]);
    }
}
