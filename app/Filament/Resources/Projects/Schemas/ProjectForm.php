<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Models\Country;
use App\Models\Organization;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
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
        ]);
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
