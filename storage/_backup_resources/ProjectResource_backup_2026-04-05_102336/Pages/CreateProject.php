<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->stateHistories()->create([
            'from_state' => null,
            'to_state' => $this->record->state,
            'changed_by' => auth()->id(),
            'notes' => 'تم إنشاء المشروع',
        ]);
    }
}
