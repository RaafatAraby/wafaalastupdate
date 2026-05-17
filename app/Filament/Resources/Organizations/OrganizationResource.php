<?php

namespace App\Filament\Resources\Organizations;

use App\Enums\Role;
use App\Filament\Resources\Organizations\Pages\CreateOrganization;
use App\Filament\Resources\Organizations\Pages\EditOrganization;
use App\Filament\Resources\Organizations\Pages\ListOrganizations;
use App\Filament\Resources\Organizations\Schemas\OrganizationForm;
use App\Filament\Resources\Organizations\Tables\OrganizationsTable;
use App\Models\Organization;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'الجهات الممولة';
    protected static ?string $modelLabel = 'جهة ممولة';
    protected static ?string $pluralModelLabel = 'الجهات الممولة';
    protected static string|\UnitEnum|null $navigationGroup = 'إدارة التشغيل';

    protected static ?int $navigationSort = 3;

    /**
     * صفحة إدارة الجهات الممولة مقصورة على مدير النظام ومشرف النظام
     * (مجلس الإدارة) فقط. باقي الأدوار يظهر لها اسم الجهة في تفاصيل
     * المشروع/التقرير، لكن لا يمكنها فتح صفحة الإدارة هذه أو تعديلها.
     */
    private static function isAllowed(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        return $user->hasAnyRole([
            Role::SystemAdmin->value,
            Role::BoardSupervisor->value,
        ]);
    }

    public static function canAccess(): bool
    {
        return self::isAllowed();
    }

    public static function canViewAny(): bool
    {
        return self::isAllowed();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::isAllowed();
    }

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
