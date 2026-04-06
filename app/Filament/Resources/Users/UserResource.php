<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static string|UnitEnum|null $navigationGroup = 'إدارة النظام';
    protected static ?string $navigationLabel = 'المستخدمون';
    protected static ?string $modelLabel = 'مستخدم';
    protected static ?string $pluralModelLabel = 'المستخدمون';
    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات المستخدم')
                ->schema([
                    TextInput::make('name')
                        ->label('الاسم')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('email')
                        ->label('البريد الإلكتروني')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),

                    Select::make('department_id')
                        ->label('القسم')
                        ->relationship('department', 'name')
                        ->searchable()
                        ->preload(),

                    Select::make('role')
                        ->label('الدور')
                        ->required()
                        ->options([
                            'super_admin' => 'مدير عام',
                            'project_manager' => 'مدير مشاريع',
                            'readiness_officer' => 'مسؤول الجاهزية',
                            'execution_officer' => 'مسؤول التنفيذ',
                            'documentation_officer' => 'مسؤول التوثيق',
                            'finance_officer' => 'المسؤول المالي',
                            'viewer' => 'عرض فقط',
                        ]),

                    TextInput::make('password')
                        ->label('كلمة المرور')
                        ->password()
                        ->revealable()
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn ($state) => filled($state))
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null),

                    Toggle::make('is_active')
                        ->label('نشط')
                        ->default(true),
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
                    ->label('الاسم')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),

                TextColumn::make('department.name')
                    ->label('القسم')
                    ->placeholder('-'),

                TextColumn::make('role')
                    ->label('الدور')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'super_admin' => 'مدير عام',
                        'project_manager' => 'مدير مشاريع',
                        'readiness_officer' => 'مسؤول الجاهزية',
                        'execution_officer' => 'مسؤول التنفيذ',
                        'documentation_officer' => 'مسؤول التوثيق',
                        'finance_officer' => 'المسؤول المالي',
                        'viewer' => 'عرض فقط',
                        default => $state,
                    }),

                IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->recordActions([
                Actions\EditAction::make()->label('تعديل'),
            ])
            ->toolbarActions([
                Actions\CreateAction::make()->label('إضافة مستخدم'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

  
}
