<?php

namespace App\Filament\Resources\Departments;

use App\Filament\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Resources\Departments\Pages\EditDepartment;
use App\Filament\Resources\Departments\Pages\ListDepartments;
use App\Models\Department;
use BackedEnum;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class DepartmentResource extends Resource
{
    
    protected static ?string $model = Department::class;
    protected static bool $shouldRegisterNavigation = false;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office';
    protected static string|UnitEnum|null $navigationGroup = 'إدارة النظام';
    protected static ?string $navigationLabel = 'الأقسام';
    protected static ?string $modelLabel = 'قسم';
    protected static ?string $pluralModelLabel = 'الأقسام';
    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات القسم')
                ->schema([
                    TextInput::make('name')
                        ->label('اسم القسم')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('code')
                        ->label('رمز القسم')
                        ->maxLength(50),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label('اسم القسم')
                    ->searchable(),

                TextColumn::make('code')
                    ->label('الرمز')
                    ->searchable(),

                TextColumn::make('users_count')
                    ->label('عدد المستخدمين')
                    ->counts('users'),
            ])
            ->recordActions([
                Actions\EditAction::make()->label('تعديل'),
            ])
            ->toolbarActions([
                Actions\CreateAction::make()->label('إضافة قسم'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDepartments::route('/'),
            'create' => CreateDepartment::route('/create'),
            'edit' => EditDepartment::route('/{record}/edit'),
        ];
    }

   
}
