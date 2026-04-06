<?php

namespace App\Filament\Resources\Attachments\Schemas;

use App\Models\FinancialTransaction;
use App\Models\Project;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttachmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات المرفق')->schema([
                Grid::make(2)->schema([
                    Select::make('project_id')
                        ->label('المشروع')
                        ->options(fn () => Project::query()->orderByDesc('id')->pluck('project_number', 'id')->all())
                        ->searchable()
                        ->preload(),

                    Select::make('financial_transaction_id')
                        ->label('الحركة المالية')
                        ->options(fn () => FinancialTransaction::query()->orderByDesc('id')->pluck('reference_no', 'id')->all())
                        ->searchable()
                        ->preload(),
                ]),

                Grid::make(2)->schema([
                    Select::make('category')
                        ->label('الفئة')
                        ->options([
                            'documentation' => 'توثيق',
                            'final_report' => 'تقرير ختامي',
                            'payment_receipt' => 'سند/إيصال',
                            'other' => 'أخرى',
                        ])
                        ->native(true)
                        ->required(),

                    TextInput::make('original_name')
                        ->label('اسم الملف')
                        ->required()
                        ->maxLength(255),
                ]),

                FileUpload::make('file_path')
                    ->label('الملف')
                    ->disk('public')
                    ->directory('attachments')
                    ->visibility('public')
                    ->downloadable()
                    ->openable()
                    ->required(),
            ]),
        ]);
    }
}
