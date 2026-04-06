<?php

namespace App\Filament\Resources\FinancialTransactions\Tables;

use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FinancialTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('transaction_date', 'desc')
            ->columns([
                TextColumn::make('project.project_number')
                    ->label('رقم المشروع')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('project.title')
                    ->label('المشروع')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('transaction_type')
                    ->label('نوع الحركة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'incoming' => 'واردة',
                        'outgoing' => 'صادرة',
                        default => $state ?? '-',
                    })
                    ->color(fn (?string $state) => match ($state) {
                        'incoming' => 'success',
                        'outgoing' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('المبلغ')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('sender_name')
                    ->label('اسم المرسل')
                    ->placeholder('-')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('receiver_name')
                    ->label('اسم المستقبل')
                    ->placeholder('-')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('transfer_method')
                    ->label('طريقة التحويل')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        'bank' => 'بنكي',
                        'western_union' => 'ويسترن يونيون',
                        'paypal' => 'باي بال',
                        'cash_hand' => 'تسليم يد',
                        'other' => 'أخرى',
                        default => $state ?? '-',
                    })
                    ->color('info'),

                TextColumn::make('reference_no')
                    ->label('المرجع')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('transaction_date')
                    ->label('تاريخ الحركة')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('createdBy.name')
                    ->label('أضيفت بواسطة')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'project_number')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('transaction_type')
                    ->label('نوع الحركة')
                    ->options([
                        'incoming' => 'واردة',
                        'outgoing' => 'صادرة',
                    ]),

                SelectFilter::make('transfer_method')
                    ->label('طريقة التحويل')
                    ->options([
                        'bank' => 'بنكي',
                        'western_union' => 'ويسترن يونيون',
                        'paypal' => 'باي بال',
                        'cash_hand' => 'تسليم يد',
                        'other' => 'أخرى',
                    ]),
            ])
            ->recordActions([
                Actions\EditAction::make()->label('تعديل'),
            ])
            ->toolbarActions([
                Actions\CreateAction::make()->label('إضافة حركة مالية'),
            ])
            ->bulkActions([]);
    }
}
