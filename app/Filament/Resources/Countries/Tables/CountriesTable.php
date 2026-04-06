<?php

namespace App\Filament\Resources\Countries\Tables;

use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CountriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')->label('الدولة')->searchable(),
                Tables\Columns\TextColumn::make('name_en')->label('English')->toggleable(),
                Tables\Columns\TextColumn::make('iso2')->label('ISO')->badge(),
                Tables\Columns\IconColumn::make('is_active')->label('فعالة')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->label('الترتيب')->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_active')->label('الحالة')->options([
                    '1' => 'فعالة',
                    '0' => 'غير فعالة',
                ]),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
