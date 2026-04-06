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
    protected static string|UnitEnum|null $navigationGroup = 'التحليلات والتقارير';
    protected static ?string $navigationLabel = 'سجل العمليات';
    protected static ?string $modelLabel = 'عملية';
    protected static ?string $pluralModelLabel = 'سجل العمليات';
    protected static ?int $navigationSort = 81;

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

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
