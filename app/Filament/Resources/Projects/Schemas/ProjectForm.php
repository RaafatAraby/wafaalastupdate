<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Models\Country;
use App\Models\Organization;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('البيانات الأساسية')
                ->icon('heroicon-m-identification')
                ->schema([
                    Grid::make(2)->schema([
                        Placeholder::make('project_number_preview')
                            ->label('رقم المشروع')
                            ->content('يتم توليده تلقائيًا بعد الحفظ حسب نوع الجهة (IVD / IVD-D).'),

                        TextInput::make('title')
                            ->label('اسم المشروع')
                            ->required()
                            ->maxLength(255),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('country_id')
                            ->label('الدولة')
                            ->options(fn () => Country::query()
                                ->where('is_active', true)
                                ->orderBy('sort_order')
                                ->pluck('name_ar', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('organization_id')
                            ->label('الجهة')
                            ->options(fn () => Organization::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn ($o) => [
                                    $o->id => ($o->organization_code ? $o->organization_code . ' - ' : '') . $o->name
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('approved_amount')
                            ->label('المبلغ المعتمد')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Select::make('state')
                            ->label('الحالة')
                            ->options([
                                'new' => 'جديد',
                                'pending_readiness' => 'بانتظار الجاهزية',
                                'ready_for_execution' => 'جاهز للتنفيذ',
                                'in_execution' => 'قيد التنفيذ',
                                'pending_documentation' => 'بانتظار التوثيق',
                                'delayed' => 'متأخر',
                                'completed' => 'مكتمل',
                                'closed' => 'مغلق',
                            ])
                            ->default('new')
                            ->required(),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('documentation_status')
                            ->label('حالة التوثيق')
                            ->options([
                                'not_started' => 'غير مبدوء',
                                'partial' => 'جزئي',
                                'complete' => 'مكتمل',
                            ])
                            ->default('not_started')
                            ->required(),

                        Select::make('financial_status')
                            ->label('الحالة المالية')
                            ->options([
                                'unfunded' => 'غير ممول',
                                'partially_funded' => 'تمويل جزئي',
                                'funded' => 'ممّول',
                                'partially_spent' => 'صرف جزئي',
                                'settled' => 'مسوّى',
                            ])
                            ->default('unfunded')
                            ->required(),
                    ]),

                    Grid::make(2)->schema([
                        DatePicker::make('start_date')->label('تاريخ البداية'),
                        DatePicker::make('expected_end_date')->label('تاريخ النهاية المتوقع'),
                    ]),

                    Grid::make(2)->schema([
                        DatePicker::make('actual_end_date')->label('تاريخ النهاية الفعلي'),
                        TextInput::make('photo_album_url')->label('رابط ألبوم الصور')->url()->maxLength(2048),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('video_album_url')->label('رابط ألبوم الفيديو')->url()->maxLength(2048),
                        Textarea::make('description')->label('الوصف')->rows(3),
                    ]),
                ]),

            Section::make('الملاحظات')
                ->icon('heroicon-m-document-text')
                ->schema([
                    Grid::make(2)->schema([
                        Textarea::make('readiness_notes')->label('ملاحظات الجاهزية')->rows(4),
                        Textarea::make('execution_notes')->label('ملاحظات التنفيذ')->rows(4),
                    ]),
                    Grid::make(2)->schema([
                        Textarea::make('documentation_notes')->label('ملاحظات التوثيق')->rows(4),
                        Textarea::make('final_report')->label('التقرير الختامي')->rows(4),
                    ]),
                ]),
        ]);
    }
}
