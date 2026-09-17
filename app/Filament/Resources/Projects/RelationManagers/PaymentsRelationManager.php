<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Models\ProjectPayment;
use App\Services\ExchangeRateService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

/**
 * Per-project payment schedule. Each row triggers a due-date notification
 * via the `payments:check-due` console command. Amounts entered in any
 * supported currency are converted to USD using the live exchange rate.
 */
class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'الدفعات المالية المجدولة';

    protected static ?string $modelLabel = 'دفعة';

    protected static ?string $pluralModelLabel = 'الدفعات';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->required(),

                Select::make('currency')
                    ->label('العملة')
                    ->options(app(ExchangeRateService::class)->currencyOptions())
                    ->default('USD')
                    ->native(false)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                        $original = (float) ($get('original_amount') ?? 0);
                        $rate = app(ExchangeRateService::class)->rateToUsd((string) ($state ?: 'USD'));
                        $set('exchange_rate', $rate);
                        $set('amount', round($original * $rate, 2));
                    }),

                TextInput::make('original_amount')
                    ->label('المبلغ بالعملة الأصلية')
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0)
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                        $rate = (float) ($get('exchange_rate') ?? app(ExchangeRateService::class)->rateToUsd((string) ($get('currency') ?: 'USD')));
                        $set('exchange_rate', $rate);
                        $set('amount', round(((float) $state) * $rate, 2));
                    }),

                TextInput::make('percentage')
                    ->label('نسبة الدفعة (%)')
                    ->helperText('اختياري — نسبة هذه الدفعة من إجمالي قيمة المشروع.')
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%'),

                TextInput::make('exchange_rate')
                    ->label('سعر الصرف (مقابل الدولار)')
                    ->numeric()
                    ->step(0.000001)
                    ->minValue(0)
                    ->default(1)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                        $original = (float) ($get('original_amount') ?? 0);
                        $rate = (float) ($state ?: 1);
                        $set('amount', round($original * $rate, 2));
                    }),

                TextInput::make('amount')
                    ->label('المبلغ بالدولار (USD)')
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0)
                    ->required()
                    ->readOnly()
                    ->dehydrated(),

                Select::make('status')
                    ->label('الحالة')
                    ->options([
                        ProjectPayment::STATUS_PENDING => 'قيد الانتظار',
                        ProjectPayment::STATUS_NOTIFIED => 'تم إرسال التذكير',
                        ProjectPayment::STATUS_PAID => 'تم الدفع',
                        ProjectPayment::STATUS_CANCELED => 'ملغاة',
                    ])
                    ->default(ProjectPayment::STATUS_PENDING)
                    ->required()
                    ->native(false),

                Textarea::make('description')
                    ->label('الوصف')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->defaultSort('due_date', 'asc')
            ->columns([
                TextColumn::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('original_amount')
                    ->label('المبلغ')
                    ->formatStateUsing(function ($state, ProjectPayment $record) {
                        if ($state === null) {
                            return number_format((float) $record->amount, 2, '.', ',').' USD';
                        }

                        return number_format((float) $state, 2, '.', ',').' '.($record->currency ?: 'USD');
                    }),

                TextColumn::make('amount')
                    ->label('المعادل بالدولار')
                    ->formatStateUsing(fn ($state) => $state === null ? '-' : 'USD '.number_format((float) $state, 2, '.', ',')),

                TextColumn::make('percentage')
                    ->label('النسبة')
                    ->formatStateUsing(fn ($state) => $state === null ? '-' : number_format((float) $state, 2, '.', '').'%')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        ProjectPayment::STATUS_PENDING => 'قيد الانتظار',
                        ProjectPayment::STATUS_NOTIFIED => 'تم إرسال التذكير',
                        ProjectPayment::STATUS_PAID => 'تم الدفع',
                        ProjectPayment::STATUS_CANCELED => 'ملغاة',
                        default => $state ?? '-',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        ProjectPayment::STATUS_PAID => 'success',
                        ProjectPayment::STATUS_NOTIFIED => 'warning',
                        ProjectPayment::STATUS_CANCELED => 'gray',
                        default => 'info',
                    }),

                TextColumn::make('description')
                    ->label('الوصف')
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('notified_at')
                    ->label('آخر إشعار')
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('إضافة دفعة')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['created_by'] = Auth::id();

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make()->label('تعديل'),
                Action::make('markPaid')
                    ->label('تأشير كمدفوعة')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ProjectPayment $record): bool => $record->isOutstanding())
                    ->action(function (ProjectPayment $record): void {
                        $record->forceFill([
                            'status' => ProjectPayment::STATUS_PAID,
                            'paid_at' => now(),
                        ])->save();
                        Notification::make()->success()->title('تم تأشير الدفعة كمدفوعة')->send();
                    }),
                DeleteAction::make()->label('حذف'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()->label('حذف المحدد'),
            ]);
    }
}
