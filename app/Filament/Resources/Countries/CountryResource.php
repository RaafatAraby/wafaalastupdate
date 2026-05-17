<?php

namespace App\Filament\Resources\Countries;

use App\Enums\Role;
use App\Filament\Resources\Countries\Pages\CreateCountry;
use App\Filament\Resources\Countries\Pages\EditCountry;
use App\Filament\Resources\Countries\Pages\ListCountries;
use App\Filament\Resources\Countries\Schemas\CountryForm;
use App\Filament\Resources\Countries\Tables\CountriesTable;
use App\Models\Country;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class CountryResource extends Resource
{
    protected static ?string $model = Country::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'الدول';
    protected static ?string $modelLabel = 'دولة';
    protected static ?string $pluralModelLabel = 'الدول';
    protected static ?int $navigationSort = 5;

    /**
     * صفحة إدارة الدول مقصورة على: مدير النظام، مشرف المجلس،
     * معد التقرير النهائي. باقي الأدوار يظهر لها اسم الدولة في
     * تفاصيل المشروع/التقرير، لكن لا يمكنها فتح هذه الصفحة.
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
            Role::FinalReportPreparer->value,
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
        return CountryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CountriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCountries::route('/'),
            'create' => CreateCountry::route('/create'),
            'edit' => EditCountry::route('/{record}/edit'),
        ];
    }

}
