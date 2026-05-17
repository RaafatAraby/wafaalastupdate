<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
use Illuminate\Support\Facades\Schema as SchemaFacade;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 6;

    public static function getNavigationLabel(): string
    {
        return __('users.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('users.models.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('users.models.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('users.form.sections.main'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('users.form.fields.name'))
                        ->required()
                        ->maxLength(255),

                    TextInput::make('email')
                        ->label(__('users.form.fields.email'))
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    TextInput::make('whatsapp_number')
                        ->label('رقم واتساب')
                        ->helperText('بالصيغة الدولية بدون + ولا 00. مثال: 966512345678')
                        ->tel()
                        ->maxLength(32)
                        ->rule('regex:/^[0-9]{8,15}$/')
                        ->dehydrateStateUsing(fn ($state) => $state ? preg_replace('/\D+/', '', $state) : null),

                    TextInput::make('password')
                        ->label(__('users.form.fields.password'))
                        ->password()
                        ->revealable()
                        ->dehydrated(fn ($state) => filled($state))
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                        ->required(fn (string $operation) => $operation === 'create')
                        ->minLength(6),

                    Select::make('roles')
                        ->label(__('users.form.fields.roles'))
                        ->relationship(
                            name: 'roles',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) => $query->orderBy('name')
                        )
                        ->getOptionLabelFromRecordUsing(function ($record): string {
                            $name = (string) data_get($record, 'name', '');
                            // Fetch the full map once — avoids Laravel's
                            // dot-notation key interpretation which breaks
                            // slugs containing dots (e.g. "attachments.approve").
                            $map = (array) trans('system.roles', [], 'ar');
                            return is_string($map[$name] ?? null) ? $map[$name] : $name;
                        })
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('permissions')
                        ->label(__('users.form.fields.permissions'))
                        ->relationship(
                            name: 'permissions',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) => $query->orderBy('name')
                        )
                        ->getOptionLabelFromRecordUsing(function ($record): string {
                            $name = (string) data_get($record, 'name', '');
                            $map = (array) trans('system.permissions', [], 'ar');
                            return is_string($map[$name] ?? null) ? $map[$name] : $name;
                        })
                        ->multiple()
                        ->searchable()
                        ->preload(),

                    Select::make('countries')
                        ->label(__('users.form.fields.countries'))
                        ->relationship('countries', self::countryLabelColumn())
                        ->multiple()
                        ->searchable()
                        ->preload(),

                    Toggle::make('is_active')
                        ->label(__('users.form.fields.is_active'))
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('users.table.columns.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label(__('users.table.columns.email'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->label(__('users.table.columns.roles'))
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        $name = (string) $state;
                        $translated = __('system.roles.' . $name, [], 'ar');
                        return $translated !== 'system.roles.' . $name ? $translated : $name;
                    }),

                TextColumn::make('countries.' . self::countryLabelColumn())
                    ->label(__('users.table.columns.countries'))
                    ->badge(),

                IconColumn::make('is_active')
                    ->label(__('users.table.columns.is_active'))
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
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

    private static function countryLabelColumn(): string
    {
        foreach (['name', 'title', 'country_name', 'name_ar', 'ar_name'] as $column) {
            if (SchemaFacade::hasColumn('countries', $column)) {
                return $column;
            }
        }

        return 'id';
    }
}
