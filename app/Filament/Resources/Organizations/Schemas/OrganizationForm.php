<?php

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('البيانات الأساسية')
                ->icon('heroicon-m-building-office-2')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('organization_code')
                            ->label('رمز الجهة')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->placeholder('ORG-001')
                            ->dehydrateStateUsing(fn (?string $state) => $state ? strtoupper(trim($state)) : null),

                        TextInput::make('name')
                            ->label('اسم الجهة')
                            ->required()
                            ->maxLength(255),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('entity_type')
                            ->label('نوع الجهة')
                            ->options([
                                'institution' => 'مؤسسة',
                                'individual' => 'فرد',
                            ])
                            ->native(true)
                            ->required(),

                        TextInput::make('contact_name')
                            ->label('اسم مسؤول التواصل')
                            ->maxLength(255),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('phone')
                            ->label('الهاتف')
                            ->tel()
                            ->maxLength(50),

                        TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->maxLength(255),
                    ]),
                ]),

            Section::make('الحالة')
                ->icon('heroicon-m-check-badge')
                ->schema([
                    Toggle::make('is_active')
                        ->label('نشطة / غير نشطة')
                        ->default(true)
                        ->inline(false),
                ]),

            Section::make('التفاصيل والملاحظات')
                ->icon('heroicon-m-document-text')
                ->schema([
                    Textarea::make('address')
                        ->label('التفاصيل / الملاحظات')
                        ->rows(5)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
