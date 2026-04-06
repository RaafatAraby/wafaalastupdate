<?php

namespace App\Filament\Resources\Organizations\Pages;

use App\Filament\Resources\Organizations\OrganizationResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditOrganization extends EditRecord
{
    protected static string $resource = OrganizationResource::class;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}
