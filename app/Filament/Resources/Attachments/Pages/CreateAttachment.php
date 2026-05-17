<?php

namespace App\Filament\Resources\Attachments\Pages;

use App\Filament\Resources\Attachments\AttachmentResource;
use App\Models\Attachment;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CreateAttachment extends CreateRecord
{
    protected static string $resource = AttachmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $files = array_values(array_filter(Arr::wrap($data['file_path'] ?? [])));

        if (empty($files)) {
            throw ValidationException::withMessages([
                'file_path' => __('attachment.validation.files_required'),
            ]);
        }

        $projectId = $data['project_id'] ?? null;

        if (blank($projectId) && request()->filled('project_id')) {
            $projectId = (int) request()->query('project_id');
        }

        $firstFileName = basename((string) $files[0]);
        $originalName = trim((string) ($data['original_name'] ?? ''));

        if ($originalName === '') {
            $originalName = $firstFileName;
        }

        $payload = [
            'project_id' => $projectId,
            'financial_transaction_id' => $data['financial_transaction_id'] ?? null,
            'category' => $data['category'] ?? 'other',
            'original_name' => $originalName,
            'file_path' => $files,
        ];

        if (Schema::hasColumn('attachments', 'uploaded_by')) {
            $payload['uploaded_by'] = Auth::id();
        }

        if (Schema::hasColumn('attachments', 'beneficiaries_count')) {
            $payload['beneficiaries_count'] = $data['beneficiaries_count'] ?? null;
        }

        return Attachment::query()->create($payload);
    }
}
