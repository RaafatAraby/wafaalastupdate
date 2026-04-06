<?php

namespace App\Filament\Resources\FinancialTransactions\Schemas;

use App\Models\Project;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FinancialTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات الحركة المالية')
                ->icon('heroicon-m-banknotes')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('project_id')
                            ->label('المشروع')
                            ->options(fn () => Project::query()
                                ->orderByDesc('id')
                                ->get()
                                ->mapWithKeys(fn ($p) => [$p->id => $p->project_number . ' - ' . $p->title])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('transaction_type')
                            ->label('نوع الحركة')
                            ->options([
                                'incoming' => 'وارد',
                                'outgoing' => 'صادر',
                            ])
                            ->native(true)
                            ->required(),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('transfer_method')
                            ->label('طريقة التحويل')
                            ->options([
                                'bank' => 'بنكي',
                                'western_union' => 'ويسترن يونيون',
                                'paypal' => 'باي بال',
                                'cash_hand' => 'تسليم يد',
                                'other' => 'أخرى',
                            ])
                            ->native(true)
                            ->required(),

                        DatePicker::make('transaction_date')
                            ->label('تاريخ الحركة')
                            ->default(now())
                            ->required(),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('amount')
                            ->label('المبلغ')
                            ->numeric()
                            ->required()
                            ->prefixIcon('heroicon-o-banknotes'),

                        TextInput::make('reference_no')
                            ->label('رقم المرجع')
                            ->maxLength(100)
                            ->prefixIcon('heroicon-o-hashtag'),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('sender_name')
                            ->label('اسم المرسل')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-user'),

                        TextInput::make('receiver_name')
                            ->label('اسم المستقبل')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-user-group'),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('approved_by')
                            ->label('اعتماد بواسطة')
                            ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload(),

                        FileUpload::make('attachment_path')
                            ->label('المرفق')
                            ->disk('public')
                            ->directory('financial-transactions')
                            ->visibility('public')
                            ->downloadable()
                            ->openable()
                            ->deletable(false)
                            ->helperText('لحذف المرفق استخدم زر "حذف المرفق" أعلى الصفحة مع تأكيد.'),
                    ]),

                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
