<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();
        $data['funder_organization_id'] = $data['organization_id'] ?? null;

        return $data;
    }
}
