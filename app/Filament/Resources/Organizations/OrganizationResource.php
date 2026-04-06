<?php

namespace App\Filament\Resources\Organizations;

use App\Filament\Resources\Organizations\Pages\CreateOrganization;
use App\Filament\Resources\Organizations\Pages\EditOrganization;
use App\Filament\Resources\Organizations\Pages\ListOrganizations;
use App\Filament\Resources\Organizations\Schemas\OrganizationForm;
use App\Filament\Resources\Organizations\Tables\OrganizationsTable;
use App\Models\Organization;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'الجهات';
    protected static ?string $modelLabel = 'جهة';
    protected static ?string $pluralModelLabel = 'الجهات';
protected static string|\UnitEnum|null $navigationGroup = 'إدارة التشغيل';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return OrganizationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrganizationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrganizations::route('/'),
            'create' => CreateOrganization::route('/create'),
            'edit' => EditOrganization::route('/{record}/edit'),
        ];
    }
}
