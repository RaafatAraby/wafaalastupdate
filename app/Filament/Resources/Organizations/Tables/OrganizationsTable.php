<?php

namespace App\Filament\Resources\Organizations\Tables;

use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrganizationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->columns([
                Tables\Columns\TextColumn::make('organization_code')
                    ->label('رمز الجهة')
                    ->searchable()
                    ->badge(),

                Tables\Columns\TextColumn::make('name')
                    ->label('الجهة')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('entity_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'institution' ? 'مؤسسة - IVD' : 'فرد - IVD-D'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشطة')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('entity_type')->label('نوع الجهة')->options([
                    'institution' => 'مؤسسة',
                    'individual' => 'فرد',
                ]),
                SelectFilter::make('is_active')->label('الحالة')->options([
                    '1' => 'نشطة',
                    '0' => 'غير نشطة',
                ]),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
