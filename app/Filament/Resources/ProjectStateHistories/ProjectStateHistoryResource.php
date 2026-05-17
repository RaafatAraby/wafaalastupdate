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
    protected static ?int $navigationSort = 31;

    // ─── دوال الترجمة للقوائم والعناوين ───

    public static function getNavigationGroup(): ?string
    {
        return __('project.navigation.operations');
    }

    public static function getNavigationLabel(): string
    {
        return __('project.navigation.state_history');
    }

    public static function getModelLabel(): string
    {
        return __('project.models.state_history');
    }

    public static function getPluralModelLabel(): string
    {
        return __('project.models.state_history_plural');
    }

    // ────────────────────────────────────────

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
                    ->label(__('project.fields.project_number'))
                    ->searchable(),

                TextColumn::make('project.title')
                    ->label(__('project.fields.project'))
                    ->searchable()
                    ->wrap(),

                TextColumn::make('from_state')
                    ->label(__('project.fields.from_state'))
                    ->badge()
                    // التعديل هنا: قراءة الترجمة من ملف اللغة
                    ->formatStateUsing(fn (?string $state) => $state ? __('project.form.options.states.' . $state) : '-')
                    // إضافة الألوان المناسبة لكل حالة
                    ->color(fn (?string $state) => match ($state) {
                        'new' => 'gray',
                        'pending_readiness' => 'warning',
                        'ready_for_execution' => 'info',
                        'in_execution' => 'primary',
                        'pending_documentation' => 'warning',
                        'delayed' => 'danger',
                        'completed' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('to_state')
                    ->label(__('project.fields.to_state'))
                    ->badge()
                    // التعديل هنا: قراءة الترجمة من ملف اللغة
                    ->formatStateUsing(fn (?string $state) => $state ? __('project.form.options.states.' . $state) : '-')
                    // إضافة الألوان المناسبة لكل حالة
                    ->color(fn (?string $state) => match ($state) {
                        'new' => 'gray',
                        'pending_readiness' => 'warning',
                        'ready_for_execution' => 'info',
                        'in_execution' => 'primary',
                        'pending_documentation' => 'warning',
                        'delayed' => 'danger',
                        'completed' => 'success',
                        'closed' => 'gray',
                        default => 'success',
                    }),

                TextColumn::make('user.name')
                    ->label(__('project.fields.by_user'))
                    ->placeholder('-'),

                TextColumn::make('notes')
                    ->label(__('project.fields.notes'))
                    ->wrap()
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label(__('project.fields.date'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('project_id')
                    ->label(__('project.fields.project'))
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

}