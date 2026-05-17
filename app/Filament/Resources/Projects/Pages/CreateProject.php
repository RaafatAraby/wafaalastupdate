<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Projects\Schemas\ProjectForm;
use App\Models\Attachment;
use App\Support\NumericNormalizer;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    /**
     * Files captured from the create form for post-create attachment
     * persistence. Stored as upload paths on the `public` disk.
     *
     * @var array<int, string>
     */
    protected array $pendingProjectFiles = [];

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function form(Schema $schema): Schema
    {
        return ProjectForm::configure($schema);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // The `project_files` upload is `dehydrated(false)`, so it never
        // reaches $data — we read it directly from the live form state
        // before the model is persisted.
        $files = array_values(array_filter(Arr::wrap($this->data['project_files'] ?? [])));
        $this->pendingProjectFiles = array_map(fn ($f) => (string) $f, $files);

        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();
        $data['funder_organization_id'] = $data['organization_id'] ?? null;

        NumericNormalizer::apply($data, 'approved_amount');
        NumericNormalizer::apply($data, 'beneficiaries_count');

        unset($data['project_files']);

        return $data;
    }

    /**
     * Persist any uploaded project files as a single Attachment row linked
     * to the newly-created project. The Attachment model casts `file_path`
     * to an array, so all uploads share one row (the project owner sees
     * one logical "documentation upload" with N files inside, instead of
     * N separate rows).
     */
    protected function afterCreate(): void
    {
        if (empty($this->pendingProjectFiles) || ! $this->record) {
            return;
        }

        $count = count($this->pendingProjectFiles);
        $originalName = $count === 1
            ? basename($this->pendingProjectFiles[0])
            : "ملفات المشروع ({$count})";

        $payload = [
            'project_id' => $this->record->id,
            'category' => 'documentation',
            'original_name' => $originalName,
            'file_path' => $this->pendingProjectFiles,
        ];

        if (SchemaFacade::hasColumn('attachments', 'uploaded_by')) {
            $payload['uploaded_by'] = Auth::id();
        }

        Attachment::query()->create($payload);

        $this->pendingProjectFiles = [];
    }
}
