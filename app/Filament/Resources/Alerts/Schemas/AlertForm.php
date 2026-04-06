<?php

namespace App\Filament\Resources\Alerts\Schemas;

use App\Models\Project;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AlertForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات الإشعار')->schema([
                Grid::make(2)->schema([
                    Select::make('project_id')
                        ->label('المشروع')
                        ->options(fn () => Project::query()->orderByDesc('id')->pluck('project_number', 'id')->all())
                        ->searchable()
                        ->preload(),

                    Select::make('type')
                        ->label('نوع الإشعار')
                        ->options([
                            'created' => 'إنشاء مشروع',
                            'delayed' => 'تأخير',
                            'documentation_missing' => 'نقص توثيق',
                            'financial' => 'حركة مالية',
                            'closed' => 'إغلاق',
                            'general' => 'عام',
                        ])
                        ->native(true)
                        ->required(),
                ]),

                Grid::make(2)->schema([
                    TextInput::make('title')->label('العنوان')->required()->maxLength(255),
                    Select::make('severity')->label('الأهمية')->options([
                        'info' => 'معلومة',
                        'success' => 'نجاح',
                        'warning' => 'تحذير',
                        'danger' => 'خطر',
                    ])->native(true)->default('info')->required(),
                ]),

                Textarea::make('body')->label('الوصف')->rows(4),

                Grid::make(2)->schema([
                    Toggle::make('is_read')->label('تمت القراءة')->default(false),
                    DateTimePicker::make('sent_at')->label('تاريخ الإرسال'),
                ]),
            ]),
        ]);
    }
}
