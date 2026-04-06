<?php

namespace App\Filament\Resources\Organizations\Pages;

use App\Filament\Resources\Organizations\OrganizationResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateOrganization extends CreateRecord
{
    protected static string $resource = OrganizationResource::class;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }
}
