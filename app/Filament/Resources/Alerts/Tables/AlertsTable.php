<?php

namespace App\Filament\Resources\Alerts\Tables;

use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AlertsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('العنوان')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('type')->label('النوع')->badge(),
                Tables\Columns\TextColumn::make('severity')->label('الأهمية')->badge(),
                Tables\Columns\IconColumn::make('is_read')->label('مقروء')->boolean(),
                Tables\Columns\TextColumn::make('project.project_number')->label('المشروع')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('الوقت')->since(),
            ])
            ->filters([
                SelectFilter::make('severity')->label('الأهمية')->options([
                    'info' => 'معلومة',
                    'success' => 'نجاح',
                    'warning' => 'تحذير',
                    'danger' => 'خطر',
                ]),
                SelectFilter::make('is_read')->label('الحالة')->options([
                    '0' => 'غير مقروء',
                    '1' => 'مقروء',
                ]),
            ])
            ->recordActions([
                EditAction::make()->label('تعديل'),
            ]);
    }
}
