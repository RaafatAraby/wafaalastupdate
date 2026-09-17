<?php

namespace App\Filament\Resources\FinancialTransactions\Schemas;

use App\Models\Country;
use App\Models\Organization;
use App\Models\Project;
use App\Services\ExchangeRateService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class FinancialTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        $fields = [
            Select::make('transaction_type')
                ->label(__('financial_transaction.form.fields.transaction_type'))
                ->options([
                    'incoming' => __('financial_transaction.form.options.transaction_types.incoming'),
                    'outgoing' => __('financial_transaction.form.options.transaction_types.outgoing'),
                ])
                ->required()
                ->live()
                ->native(false),

            Select::make('funding_source_country')
                ->label(__('financial_transaction.form.fields.funding_source_country'))
                ->options(self::fundingCountryOptions())
                ->searchable()
                ->preload()
                ->native(false)
                ->visible(fn (Get $get): bool => $get('transaction_type') === 'incoming'
                    && SchemaFacade::hasColumn('financial_transactions', 'funding_source_country'))
                ->required(fn (Get $get): bool => $get('transaction_type') === 'incoming'
                    && SchemaFacade::hasColumn('financial_transactions', 'funding_source_country'))
                ->dehydrated(fn (Get $get): bool => $get('transaction_type') === 'incoming'
                    && SchemaFacade::hasColumn('financial_transactions', 'funding_source_country')),

            Select::make('country_id')
                ->label(__('financial_transaction.form.fields.country'))
                ->options(self::countryOptions())
                ->searchable()
                ->preload()
                ->live()
                ->dehydrated(false)
                ->default(fn ($record) => self::resolveCountryId($record))
                ->afterStateHydrated(function (Select $component, $state, $record): void {
                    if (filled($state)) {
                        return;
                    }

                    $component->state(self::resolveCountryId($record));
                })
                ->afterStateUpdated(fn (callable $set) => $set('project_id', null)),

            Select::make('organization_id')
                ->label(__('financial_transaction.form.fields.organization'))
                ->options(self::organizationOptions())
                ->searchable()
                ->preload()
                ->live()
                ->dehydrated(false)
                ->default(fn ($record) => self::resolveOrganizationId($record))
                ->afterStateHydrated(function (Select $component, $state, $record): void {
                    if (filled($state)) {
                        return;
                    }

                    $component->state(self::resolveOrganizationId($record));
                })
                ->afterStateUpdated(fn (callable $set) => $set('project_id', null)),

            Select::make('project_id')
                ->label(__('financial_transaction.form.fields.project'))
                ->options(function (Get $get, $record): array {
                    $explicitProjectId = request()->query('project_id') ?: $record?->project_id;

                    if ($explicitProjectId) {
                        return self::projectOptionsByExplicitProject((int) $explicitProjectId);
                    }

                    return self::projectOptions(
                        $get('country_id'),
                        $get('organization_id')
                    );
                })
                ->default(fn ($record) => $record?->project_id ?: request()->query('project_id'))
                ->afterStateHydrated(function (Select $component, $state, $record): void {
                    if (filled($state)) {
                        return;
                    }

                    $component->state($record?->project_id ?: request()->query('project_id'));
                })
                ->searchable()
                ->preload()
                ->native(false)
                ->required(),

            self::columnTextInput('reference_no', 'financial_transaction.form.fields.reference_no'),
            self::columnTextInput('sender_name', 'financial_transaction.form.fields.sender_name'),
            self::columnTextInput('receiver_name', 'financial_transaction.form.fields.receiver_name'),

            // حقل طريقة التحويل (تم إزالة شرط قاعدة البيانات لضمان ظهوره)
            Select::make('transfer_method')
                ->label(__('financial_transaction.form.fields.transfer_method'))
                ->options([
                    'bank_transfer' => __('financial_transaction.form.options.transfer_methods.bank_transfer'),
                    'cash' => __('financial_transaction.form.options.transfer_methods.cash'),
                    'hawala' => __('financial_transaction.form.options.transfer_methods.hawala'),
                    'wallet' => __('financial_transaction.form.options.transfer_methods.wallet'),
                    'other' => __('financial_transaction.form.options.transfer_methods.other'),
                ])
                ->native(false)
                ->live(), // تحديث لحظي

            // حقل اسم البنك (مربوط بطريقة التحويل)
            Select::make('bank_name')
                ->label(__('financial_transaction.form.fields.bank_name'))
                ->options(self::bankOptions())
                ->searchable()
                ->preload()
                ->native(false)
                ->visible(fn (Get $get): bool => $get('transfer_method') === 'bank_transfer')
                ->required(fn (Get $get): bool => $get('transfer_method') === 'bank_transfer')
                ->dehydrated(fn (Get $get): bool => $get('transfer_method') === 'bank_transfer'),

            self::columnSelect('category', 'financial_transaction.form.fields.category', [
                'project_support' => __('financial_transaction.form.options.categories.project_support'),
                'operational' => __('financial_transaction.form.options.categories.operational'),
                'administrative' => __('financial_transaction.form.options.categories.administrative'),
                'transfer' => __('financial_transaction.form.options.categories.transfer'),
                'other' => __('financial_transaction.form.options.categories.other'),
            ]),

            Select::make('currency')
                ->label(__('financial_transaction.form.fields.currency'))
                ->options(app(ExchangeRateService::class)->currencyOptions())
                ->default('USD')
                ->required()
                ->live()
                ->native(false)
                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                    $original = (float) ($get('original_amount') ?? 0);
                    $rate = app(ExchangeRateService::class)->rateToUsd((string) ($state ?: 'USD'));
                    $set('exchange_rate', $rate);
                    $set('amount', round($original * $rate, 2));
                }),

            TextInput::make('original_amount')
                ->label(__('financial_transaction.form.fields.original_amount'))
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

            TextInput::make('exchange_rate')
                ->label(__('financial_transaction.form.fields.exchange_rate'))
                ->helperText(__('financial_transaction.form.help.exchange_rate'))
                ->numeric()
                ->step(0.000001)
                ->minValue(0)
                ->default(1)
                ->dehydrated()
                ->live(onBlur: true)
                ->extraInputAttributes(['lang' => 'en', 'dir' => 'ltr', 'inputmode' => 'decimal'])
                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                    $original = (float) ($get('original_amount') ?? 0);
                    $rate = (float) ($state ?: 1);
                    $set('amount', round($original * $rate, 2));
                }),

            TextInput::make('amount')
                ->label(__('financial_transaction.form.fields.amount'))
                ->helperText(__('financial_transaction.form.help.amount_usd'))
                ->numeric()
                ->step(0.01)
                ->minValue(0)
                ->required()
                ->readOnly()
                ->dehydrated()
                ->extraInputAttributes(['lang' => 'en', 'dir' => 'ltr', 'inputmode' => 'decimal']),

            DatePicker::make('transaction_date')
                ->label(__('financial_transaction.form.fields.transaction_date'))
                ->required(),

            // Approval status is workflow-only — set via the
            // dedicated approve/reject actions, never the form.
            Hidden::make('approved_by'),
            Hidden::make('approved_at'),

            self::columnTextarea('notes', 'financial_transaction.form.fields.notes'),
            self::columnTextarea('approval_notes', 'financial_transaction.form.fields.approval_notes'),

            SchemaFacade::hasColumn('financial_transactions', 'attachment_path')
                ? FileUpload::make('attachment_path')
                    ->label(__('financial_transaction.form.fields.attachment'))
                    ->disk('public')
                    ->directory('financial_transactions')
                    ->preserveFilenames(false)
                    ->maxSize(10 * 1024)
                    ->columnSpanFull()
                : null,
        ];

        return $schema->components([
            Section::make(__('financial_transaction.form.sections.main'))
                ->schema(array_values(array_filter($fields)))
                ->columns(2),
        ]);
    }

    private static function bankOptions(): array
    {
        return [
            'بنك فلسطين' => 'بنك فلسطين',
            'البنك الإسلامي الفلسطيني' => 'البنك الإسلامي الفلسطيني',
            'البنك الوطني الإسلامي' => 'البنك الوطني الإسلامي',
            'بنك القدس' => 'بنك القدس',
            'بنك الإسكان' => 'بنك الإسكان',
            'بنك القاهرة عمان' => 'بنك القاهرة عمان',
            'بنك الأردن' => 'بنك الأردن',
       'بنك الإستثمار' => 'بنك الإستثمار',
'İş bankası' => 'İş bankası',
'Ziraat bankası' => 'Ziraat bankası',
'Ziraat katılım' => 'Ziraat katılım',
'Vakıf katılım' => 'Vakıf katılım',
'Al-baraka' => 'Al-baraka',
'Kuveyt Turk' => 'Kuveyt Turk',
            'أخرى' => 'أخرى',
        ];
    }

    private static function resolveCountryId($record): mixed
    {
        if ($record?->project?->country_id) {
            return $record->project->country_id;
        }

        if (request()->filled('project_id')) {
            return Project::query()
                ->whereKey((int) request()->query('project_id'))
                ->value('country_id');
        }

        return request()->query('country_id');
    }

    private static function resolveOrganizationId($record): mixed
    {
        if ($record?->project?->organization_id) {
            return $record->project->organization_id;
        }

        if (request()->filled('project_id')) {
            return Project::query()
                ->whereKey((int) request()->query('project_id'))
                ->value('organization_id');
        }

        return request()->query('organization_id');
    }

    private static function columnTextInput(string $column, string $label): ?TextInput
    {
        if (! SchemaFacade::hasColumn('financial_transactions', $column)) {
            return null;
        }

        return TextInput::make($column)
            ->label(__($label))
            ->maxLength(255);
    }

    private static function columnTextarea(string $column, string $label): ?Textarea
    {
        if (! SchemaFacade::hasColumn('financial_transactions', $column)) {
            return null;
        }

        return Textarea::make($column)
            ->label(__($label))
            ->rows(4)
            ->columnSpanFull();
    }

    private static function columnSelect(string $column, string $label, array $options): ?Select
    {
        if (! SchemaFacade::hasColumn('financial_transactions', $column)) {
            return null;
        }

        return Select::make($column)
            ->label(__($label))
            ->options($options)
            ->native(false);
    }

    private static function countryOptions(): array
    {
        $label = self::countryLabelColumn();

        return Country::query()
            ->orderBy($label)
            ->pluck($label, 'id')
            ->toArray();
    }

    /**
     * Full ISO 3166-1 country list (Arabic). Not scoped to the user's assigned
     * countries because funding can originate from anywhere in the world —
     * independent of where the project runs.
     */
    private static function fundingCountryOptions(): array
    {
        $list = (array) config('world_countries', []);
        // Display is already Arabic names; sort by value.
        asort($list, SORT_NATURAL | SORT_FLAG_CASE);

        return $list;
    }

    private static function organizationOptions(): array
    {
        $label = self::organizationLabelColumn();

        return Organization::query()
            ->orderBy($label)
            ->pluck($label, 'id')
            ->toArray();
    }

    private static function projectOptions(?string $countryId = null, ?string $organizationId = null): array
    {
        $titleColumn = self::projectTitleColumn();

        return Project::query()
            ->when($countryId, fn ($query) => $query->where('country_id', $countryId))
            ->when($organizationId, fn ($query) => $query->where('organization_id', $organizationId))
            ->orderBy($titleColumn)
            ->get()
            ->mapWithKeys(fn (Project $project) => [
                $project->getKey() => trim(($project->project_number ? $project->project_number.' - ' : '').($project->{$titleColumn} ?? '')),
            ])
            ->toArray();
    }

    private static function projectOptionsByExplicitProject(int $projectId): array
    {
        $titleColumn = self::projectTitleColumn();

        return Project::query()
            ->whereKey($projectId)
            ->get()
            ->mapWithKeys(fn (Project $project) => [
                $project->getKey() => trim(($project->project_number ? $project->project_number.' - ' : '').($project->{$titleColumn} ?? '')),
            ])
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

    private static function projectTitleColumn(): string
    {
        foreach (['title', 'name', 'project_name'] as $column) {
            if (SchemaFacade::hasColumn('projects', $column)) {
                return $column;
            }
        }

        return 'id';
    }
}
