<?php

namespace App\Filament\Resources\ProjectStateHistories;

use App\Filament\Resources\ProjectStateHistories\Pages\ListProjectStateHistories;
use App\Models\ProjectStateHistory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ProjectStateHistoryResource extends Resource
{
    protected static ?string $model = ProjectStateHistory::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static string|UnitEnum|null $navigationGroup = 'إدارة التشغيل';
    protected static ?string $navigationLabel = 'سجل الحالات';
    protected static ?string $modelLabel = 'حركة حالة';
    protected static ?string $pluralModelLabel = 'سجل الحالات';
    protected static ?int $navigationSort = 31;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('project.project_number')
                    ->label('رقم المشروع')
                    ->searchable(),

                TextColumn::make('project.title')
                    ->label('المشروع')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('from_state')
                    ->label('من حالة')
                    ->badge()
                    ->placeholder('-'),

                TextColumn::make('to_state')
                    ->label('إلى حالة')
                    ->badge()
                    ->color('success'),

                TextColumn::make('user.name')
                    ->label('بواسطة')
                    ->placeholder('-'),

                TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->wrap()
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'project_number')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjectStateHistories::route('/'),
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
