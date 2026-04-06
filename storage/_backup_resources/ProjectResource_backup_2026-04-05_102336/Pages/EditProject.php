<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected ?string $oldState = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->oldState = $this->record->state;
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->oldState !== $this->record->state) {
            $this->record->stateHistories()->create([
                'from_state' => $this->oldState,
                'to_state' => $this->record->state,
                'changed_by' => auth()->id(),
                'notes' => 'تم تغيير حالة المشروع',
            ]);
        }
    }
}
