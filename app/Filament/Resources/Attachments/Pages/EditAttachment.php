<?php

namespace App\Filament\Resources\Attachments\Pages;

use App\Filament\Resources\Attachments\AttachmentResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class EditAttachment extends EditRecord
{
    protected static string $resource = AttachmentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (blank($data['project_id'] ?? null)) {
            $data['project_id'] = $this->record->project_id ?: (request()->filled('project_id') ? (int) request()->query('project_id') : null);
        }

        if (Schema::hasColumn('attachments', 'uploaded_by') && blank($data['uploaded_by'] ?? null)) {
            $data['uploaded_by'] = $this->record->uploaded_by ?: Auth::id();
        }

        return $data;
    }
}
