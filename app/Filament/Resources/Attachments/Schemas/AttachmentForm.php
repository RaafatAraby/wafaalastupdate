<?php

namespace App\Filament\Resources\Attachments\Schemas;

use App\Models\Country;
use App\Models\Organization;
use App\Models\Project;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class AttachmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('attachment.form.sections.main'))
                ->schema([
                    Select::make('country_id')
                        ->label(__('attachment.form.fields.country'))
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
                        ->afterStateUpdated(function (callable $set, $state, callable $get): void {
                            $currentProjectId = $get('project_id');
                            $currentProject = $currentProjectId ? Project::query()->find($currentProjectId) : null;

                            if ($currentProject && (string) $currentProject->country_id === (string) $state) {
                                return;
                            }

                            $set('project_id', null);
                        }),

                    Select::make('organization_id')
                        ->label(__('attachment.form.fields.organization'))
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
                        ->afterStateUpdated(function (callable $set, $state, callable $get): void {
                            $currentProjectId = $get('project_id');
                            $currentProject = $currentProjectId ? Project::query()->find($currentProjectId) : null;

                            if ($currentProject && (string) $currentProject->organization_id === (string) $state) {
                                return;
                            }

                            $set('project_id', null);
                        }),

                    Select::make('project_id')
                        ->label(__('attachment.form.fields.project'))
                        ->options(function (callable $get, $record): array {
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

                    Select::make('category')
                        ->label(__('attachment.form.fields.category'))
                        ->options(self::categoryOptions())
                        ->required()
                        ->native(false),

                    TextInput::make('original_name')
                        ->label(__('attachment.form.fields.original_name'))
                        ->maxLength(255),

                    FileUpload::make('file_path')
                        ->label(__('attachment.form.fields.files'))
                        ->multiple()
                        ->required()
                        ->disk('public')
                        ->directory('attachments')
                        ->preserveFilenames(false)
                        // أيقونات تنزيل + فتح بجانب كل ملف داخل الفورم/المودال
                        // ليتمكن ذوو المشاهدة فقط (system_admin / board_supervisor /
                        // final_report_preparer) من تنزيل الملفات من نافذة التفاصيل.
                        ->downloadable()
                        ->openable(),

                    self::beneficiariesCountField(),

                    Hidden::make('uploaded_by'),
                ])
                ->columns(2),
        ]);
    }

    private static function beneficiariesCountField(): ?TextInput
    {
        if (! SchemaFacade::hasColumn('attachments', 'beneficiaries_count')) {
            return null;
        }

        return TextInput::make('beneficiaries_count')
            ->label(__('attachment.form.fields.beneficiaries_count'))
            ->numeric()
            ->visible(fn (callable $get) => $get('category') === 'beneficiaries_sheet');
    }

    private static function categoryOptions(): array
    {
        return [
            'documentation' => (string) __('attachment.table.category_labels.documentation'),
            'financial' => (string) __('attachment.table.category_labels.financial'),
            'final_report' => (string) __('attachment.table.category_labels.final_report'),
            'beneficiaries_sheet' => (string) __('attachment.table.category_labels.beneficiaries_sheet'),
            'offer' => (string) __('attachment.table.category_labels.offer'),
            'other' => (string) __('attachment.table.category_labels.other'),
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

    private static function countryOptions(): array
    {
        $label = self::countryLabelColumn();

        return Country::query()
            ->orderBy($label)
            ->get()
            ->mapWithKeys(function ($country) use ($label) {
                $value = trim((string) data_get($country, $label, ''));

                return $value !== ''
                    ? [$country->getKey() => $value]
                    : [];
            })
            ->toArray();
    }

    private static function organizationOptions(): array
    {
        $label = self::organizationLabelColumn();

        return Organization::query()
            ->orderBy($label)
            ->get()
            ->mapWithKeys(function ($organization) use ($label) {
                $value = trim((string) data_get($organization, $label, ''));

                return $value !== ''
                    ? [$organization->getKey() => $value]
                    : [];
            })
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
            ->mapWithKeys(function (Project $project) use ($titleColumn) {
                $label = trim(($project->project_number ? $project->project_number . ' - ' : '') . ((string) ($project->{$titleColumn} ?? '')));

                return $label !== ''
                    ? [$project->getKey() => $label]
                    : [];
            })
            ->toArray();
    }

    private static function projectOptionsByExplicitProject(int $projectId): array
    {
        $titleColumn = self::projectTitleColumn();

        return Project::query()
            ->whereKey($projectId)
            ->get()
            ->mapWithKeys(function (Project $project) use ($titleColumn) {
                $label = trim(($project->project_number ? $project->project_number . ' - ' : '') . ((string) ($project->{$titleColumn} ?? '')));

                return $label !== ''
                    ? [$project->getKey() => $label]
                    : [];
            })
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
