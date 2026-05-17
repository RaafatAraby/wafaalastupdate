<?php

namespace App\Filament\Resources\ActivityLog;

use App\Filament\Resources\ActivityLog\Pages\ListActivityLogs;
use App\Filament\Resources\ActivityLog\Tables\ActivityLogsTable;
use App\Models\ActivityLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clock';
    protected static ?int $navigationSort = 81;

    // ─── دوال الترجمة ───

    public static function getNavigationGroup(): ?string
    {
        return __('activity_log.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('activity_log.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('activity_log.models.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('activity_log.models.plural');
    }

    // ──────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return ActivityLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
        ];
    }

}