<?php

namespace App\Filament\Resources\Countries\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('country.form.sections.main'))
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name_ar')
                            ->label(__('country.form.fields.name_ar'))
                            ->required()
                            ->maxLength(150),

                        TextInput::make('name_en')
                            ->label(__('country.form.fields.name_en'))
                            ->required()
                            ->maxLength(150),
                    ]),

                    Grid::make(3)->schema([
                        TextInput::make('iso2')
                            ->label(__('country.form.fields.iso2'))
                            ->required()
                            ->maxLength(2),

                        TextInput::make('sort_order')
                            ->label(__('country.form.fields.sort_order'))
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Toggle::make('is_active')
                            ->label(__('country.form.fields.is_active'))
                            ->default(true),
                    ]),
                ]),
        ]);
    }
}
