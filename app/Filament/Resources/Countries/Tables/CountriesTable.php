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
                Tables\Columns\TextColumn::make('name_ar')
                    ->label(__('country.form.fields.name_ar'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('name_en')
                    ->label(__('country.form.fields.name_en'))
                    ->toggleable(),

                Tables\Columns\TextColumn::make('iso2')
                    ->label(__('country.form.fields.iso2'))
                    ->badge(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('country.form.fields.is_active'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label(__('country.form.fields.sort_order'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('الحالة')
                    ->options([
                        '1' => 'فعالة',
                        '0' => 'غير فعالة',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
