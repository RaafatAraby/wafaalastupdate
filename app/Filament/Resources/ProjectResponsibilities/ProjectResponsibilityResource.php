<?php

namespace App\Filament\Resources\ProjectResponsibilities;

use App\Filament\Resources\ProjectResponsibilities\Pages\CreateProjectResponsibility;
use App\Filament\Resources\ProjectResponsibilities\Pages\EditProjectResponsibility;
use App\Filament\Resources\ProjectResponsibilities\Pages\ListProjectResponsibilities;
use App\Models\ProjectResponsibility;
use BackedEnum;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ProjectResponsibilityResource extends Resource
{
    protected static ?string $model = ProjectResponsibility::class;
    protected static bool $shouldRegisterNavigation = false;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';
    protected static string|UnitEnum|null $navigationGroup = 'إدارة التشغيل';
    protected static ?string $navigationLabel = 'مسؤوليات المشاريع';
    protected static ?string $modelLabel = 'مسؤولية مشروع';
    protected static ?string $pluralModelLabel = 'مسؤوليات المشاريع';
    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('ربط المسؤوليات بالمشروع')
                ->schema([
                    Select::make('project_id')
                        ->label('المشروع')
                        ->relationship('project', 'project_number')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('readiness_user_id')
                        ->label('مسؤول الجاهزية')
                        ->relationship('readinessUser', 'name')
                        ->searchable()
                        ->preload(),

                    Select::make('execution_user_id')
                        ->label('مسؤول التنفيذ')
                        ->relationship('executionUser', 'name')
                        ->searchable()
                        ->preload(),

                    Select::make('documentation_user_id')
                        ->label('مسؤول التوثيق')
                        ->relationship('documentationUser', 'name')
                        ->searchable()
                        ->preload(),

                    Select::make('finance_user_id')
                        ->label('المسؤول المالي')
                        ->relationship('financeUser', 'name')
                        ->searchable()
                        ->preload(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('project.project_number')
                    ->label('رقم المشروع')
                    ->searchable(),

                TextColumn::make('project.title')
                    ->label('اسم المشروع')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('readinessUser.name')
                    ->label('الجاهزية')
                    ->placeholder('-'),

                TextColumn::make('executionUser.name')
                    ->label('التنفيذ')
                    ->placeholder('-'),

                TextColumn::make('documentationUser.name')
                    ->label('التوثيق')
                    ->placeholder('-'),

                TextColumn::make('financeUser.name')
                    ->label('المالية')
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'project_number')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Actions\EditAction::make()->label('تعديل'),
            ])
            ->toolbarActions([
                Actions\CreateAction::make()->label('إضافة مسؤوليات'),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjectResponsibilities::route('/'),
            'create' => CreateProjectResponsibility::route('/create'),
            'edit' => EditProjectResponsibility::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return false;
    }
}
