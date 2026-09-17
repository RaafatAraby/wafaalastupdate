<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Models\Country;
use App\Models\Organization;
use App\Models\ProjectPayment;
use App\Services\ExchangeRateService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('project.form.sections.basic_info'))
                ->schema([
                    TextInput::make('project_number')
                        ->label(__('project.form.fields.project_number'))
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('title')
                        ->label(__('project.form.fields.title'))
                        ->required()
                        ->maxLength(255),

                    Select::make('country_id')
                        ->label(__('project.form.fields.country'))
                        ->options(self::countryOptions())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->native(false),

                    Select::make('organization_id')
                        ->label(__('project.form.fields.organization'))
                        ->options(self::organizationOptions())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->native(false),

                    TextInput::make('approved_amount')
                        ->label(__('project.form.fields.approved_amount'))
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0)
                        ->required()
                        ->prefix('USD')
                        ->extraInputAttributes(['lang' => 'en', 'dir' => 'ltr', 'inputmode' => 'decimal']),

                    Select::make('state')
                        ->label(__('project.form.fields.state'))
                        ->options([
                            'new' => __('project.form.options.states.new'),
                            'pending_readiness' => __('project.form.options.states.pending_readiness'),
                            'ready_for_execution' => __('project.form.options.states.ready_for_execution'),
                            'in_execution' => __('project.form.options.states.in_execution'),
                            'pending_documentation' => __('project.form.options.states.pending_documentation'),
                            'delayed' => __('project.form.options.states.delayed'),
                            'completed' => __('project.form.options.states.completed'),
                            'closed' => __('project.form.options.states.closed'),
                        ])
                        ->default('pending_readiness')
                        ->required()
                        ->native(false),

                    Select::make('documentation_type')
                        ->label(__('project.form.fields.documentation_type'))
                        ->options(self::optionsArray('project.form.options.documentation_types'))
                        ->default('one_time')
                        ->required()
                        ->native(false)
                        ->helperText('يحدد تحويل حالة التوثيق تلقائياً عند إضافة الرابط: مرة واحدة → مكتمل، دوري/مستمر → جزئي.')
                        ->dehydrated(
                            fn (): bool => SchemaFacade::hasColumn('projects', 'documentation_type')
                        ),

                    Select::make('documentation_status')
                        ->label(__('project.form.fields.documentation_status'))
                        ->options([
                            'not_started' => __('project.form.options.documentation_statuses.not_started'),
                            'partial' => __('project.form.options.documentation_statuses.partial'),
                            'complete' => __('project.form.options.documentation_statuses.complete'),
                        ])
                        ->default('not_started')
                        ->required()
                        ->native(false),

                    Select::make('financial_status')
                        ->label(__('project.form.fields.financial_status'))
                        ->options([
                            'unfunded' => __('project.form.options.financial_statuses.unfunded'),
                            'partially_funded' => __('project.form.options.financial_statuses.partially_funded'),
                            'funded' => __('project.form.options.financial_statuses.funded'),
                            'partially_spent' => __('project.form.options.financial_statuses.partially_spent'),
                            'settled' => __('project.form.options.financial_statuses.settled'),
                        ])
                        ->default('unfunded')
                        ->required()
                        ->native(false),

                    DatePicker::make('start_date')
                        ->label(__('project.form.fields.start_date'))
                        ->required(),

                    DatePicker::make('expected_end_date')
                        ->label(__('project.form.fields.expected_end_date'))
                        ->required(),

                    DatePicker::make('actual_end_date')
                        ->label(__('project.form.fields.actual_end_date')),

                    TextInput::make('beneficiaries_count')
                        ->label(__('project.form.fields.beneficiaries_count'))
                        ->numeric(),

                    TextInput::make('documentation_url')
                        ->label(__('project.form.fields.documentation_url'))
                        ->url()
                        ->maxLength(500),

                    TextInput::make('documentation_video_url')
                        ->label(__('project.form.fields.documentation_video_url'))
                        ->url()
                        ->maxLength(500),

                    FileUpload::make('project_files')
                        ->label(__('project.form.fields.project_files'))
                        ->helperText(__('project.form.fields.project_files_help'))
                        ->multiple()
                        ->disk('public')
                        ->directory('attachments')
                        ->preserveFilenames(false)
                        ->dehydrated(false)
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label(__('project.form.fields.description'))
                        ->rows(4)
                        ->columnSpanFull(),

                    Textarea::make('readiness_notes')
                        ->label(__('project.form.fields.readiness_notes'))
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('execution_notes')
                        ->label(__('project.form.fields.execution_notes'))
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('documentation_notes')
                        ->label(__('project.form.fields.documentation_notes'))
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('final_report')
                        ->label(__('project.form.fields.final_report'))
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('الدفعات المستحقة')
                ->description('جدولة دفعات المشروع — يُرسل النظام تذكيراً تلقائياً عند حلول تاريخ كل دفعة.')
                ->icon('heroicon-o-banknotes')
                ->collapsible()
                ->schema([
                    Repeater::make('payments_schedule')
                        ->label(false)
                        ->dehydrated(false)
                        ->addActionLabel('إضافة دفعة جديدة')
                        ->reorderableWithButtons()
                        ->collapsed()
                        ->itemLabel(function (array $state): ?string {
                            $date = $state['due_date'] ?? null;
                            $amount = $state['original_amount'] ?? null;
                            $currency = $state['currency'] ?? 'USD';
                            $pct = $state['percentage'] ?? null;
                            $parts = [];
                            if ($date) {
                                $parts[] = $date;
                            }
                            if ($amount !== null && $amount !== '') {
                                $parts[] = number_format((float) $amount, 2, '.', ',').' '.$currency;
                            }
                            if ($pct !== null && $pct !== '') {
                                $parts[] = '('.$pct.'%)';
                            }

                            return $parts === [] ? null : implode(' • ', $parts);
                        })
                        ->defaultItems(0)
                        ->columns(2)
                        ->schema([
                            DatePicker::make('due_date')
                                ->label('تاريخ الدفعة')
                                ->required(),

                            Select::make('currency')
                                ->label('العملة')
                                ->options(app(ExchangeRateService::class)->currencyOptions())
                                ->default('USD')
                                ->native(false)
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                    $original = (float) ($get('original_amount') ?? 0);
                                    $rate = app(ExchangeRateService::class)->rateToUsd((string) ($state ?: 'USD'));
                                    $set('exchange_rate', $rate);
                                    $set('amount', round($original * $rate, 2));
                                }),

                            TextInput::make('original_amount')
                                ->label('قيمة الدفعة')
                                ->numeric()
                                ->step(0.01)
                                ->minValue(0)
                                ->required()
                                ->live(onBlur: true)
                                ->extraInputAttributes(['lang' => 'en', 'dir' => 'ltr', 'inputmode' => 'decimal'])
                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                    $rate = (float) ($get('exchange_rate') ?? app(ExchangeRateService::class)->rateToUsd((string) ($get('currency') ?: 'USD')));
                                    $set('exchange_rate', $rate);
                                    $set('amount', round(((float) $state) * $rate, 2));
                                }),

                            TextInput::make('percentage')
                                ->label('نسبة الدفعة (%)')
                                ->helperText('اختياري — نسبة هذه الدفعة من إجمالي قيمة المشروع.')
                                ->numeric()
                                ->step(0.01)
                                ->minValue(0)
                                ->maxValue(100)
                                ->suffix('%')
                                ->extraInputAttributes(['lang' => 'en', 'dir' => 'ltr', 'inputmode' => 'decimal']),

                            TextInput::make('exchange_rate')
                                ->label('سعر الصرف مقابل الدولار')
                                ->numeric()
                                ->step(0.000001)
                                ->minValue(0)
                                ->default(1)
                                ->live(onBlur: true)
                                ->extraInputAttributes(['lang' => 'en', 'dir' => 'ltr', 'inputmode' => 'decimal'])
                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                    $original = (float) ($get('original_amount') ?? 0);
                                    $rate = (float) ($state ?: 1);
                                    $set('amount', round($original * $rate, 2));
                                }),

                            TextInput::make('amount')
                                ->label('المعادل بالدولار (USD)')
                                ->numeric()
                                ->step(0.01)
                                ->minValue(0)
                                ->readOnly()
                                ->extraInputAttributes(['lang' => 'en', 'dir' => 'ltr', 'inputmode' => 'decimal']),

                            Textarea::make('description')
                                ->label('وصف الدفعة')
                                ->rows(2)
                                ->columnSpanFull(),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    /**
     * Normalise repeater rows into ProjectPayment-compatible attribute
     * arrays. Used by CreateProject::afterCreate() and other callers
     * that need to persist payment schedules captured from forms.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public static function normalisePaymentRows(array $rows): array
    {
        $rates = app(ExchangeRateService::class);
        $normalised = [];

        foreach ($rows as $row) {
            $dueDate = $row['due_date'] ?? null;
            $original = isset($row['original_amount']) && $row['original_amount'] !== ''
                ? (float) $row['original_amount']
                : null;

            if (! $dueDate || $original === null) {
                continue;
            }

            $currency = strtoupper((string) ($row['currency'] ?? 'USD'));
            $rate = isset($row['exchange_rate']) && $row['exchange_rate'] !== ''
                ? (float) $row['exchange_rate']
                : $rates->rateToUsd($currency);
            $amount = isset($row['amount']) && $row['amount'] !== ''
                ? (float) $row['amount']
                : round($original * $rate, 2);
            $percentage = isset($row['percentage']) && $row['percentage'] !== ''
                ? (float) $row['percentage']
                : null;

            $normalised[] = [
                'due_date' => $dueDate,
                'currency' => $currency,
                'original_amount' => $original,
                'exchange_rate' => $rate,
                'amount' => $amount,
                'percentage' => $percentage,
                'description' => isset($row['description']) ? (string) $row['description'] : null,
                'status' => ProjectPayment::STATUS_PENDING,
            ];
        }

        return $normalised;
    }

    private static function optionsArray(string $key): array
    {
        $value = trans($key);

        return is_array($value) ? $value : [];
    }

    private static function countryOptions(): array
    {
        $label = self::countryLabelColumn();

        return Country::query()
            ->orderBy($label)
            ->pluck($label, 'id')
            ->toArray();
    }

    private static function organizationOptions(): array
    {
        $label = self::organizationLabelColumn();

        return Organization::query()
            ->orderBy($label)
            ->pluck($label, 'id')
            ->toArray();
    }

    private static function countryLabelColumn(): string
    {
        foreach (['name', 'title', 'country_name', 'name_ar', 'ar_name'] as $column) {
            if (SchemaFacade::hasColumn('countries', $column)) {
                return $column;
            }
        }

        return 'id';
    }

    private static function organizationLabelColumn(): string
    {
        foreach (['name', 'title', 'organization_name'] as $column) {
            if (SchemaFacade::hasColumn('organizations', $column)) {
                return $column;
            }
        }

        return 'id';
    }
}
