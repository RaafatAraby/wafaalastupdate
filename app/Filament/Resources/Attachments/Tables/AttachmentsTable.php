<?php

namespace App\Filament\Resources\Attachments\Tables;

use Filament\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AttachmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('project.project_number')
                    ->label('رقم المشروع')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('project.title')
                    ->label('المشروع')
                    ->placeholder('-')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('category')
                    ->label('التصنيف')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state ?: '-')
                    ->sortable(),

                TextColumn::make('original_name')
                    ->label('اسم الملف')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('mime_type')
                    ->label('النوع')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('file_size')
                    ->label('الحجم')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return '-';
                        }

                        $size = (float) $state;

                        if ($size >= 1048576) {
                            return number_format($size / 1048576, 2) . ' MB';
                        }

                        if ($size >= 1024) {
                            return number_format($size / 1024, 2) . ' KB';
                        }

                        return number_format($size) . ' B';
                    }),

                TextColumn::make('uploadedBy.name')
                    ->label('رفع بواسطة')
                    ->placeholder('-')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('تاريخ الرفع')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('project_id')
                    ->label('المشروع')
                    ->relationship('project', 'project_number')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('category')
                    ->label('التصنيف')
                    ->options([
                        'documentation' => 'توثيق',
                        'financial' => 'مالي',
                        'final_report' => 'تقرير ختامي',
                        'offer' => 'عرض سعر',
                        'other' => 'أخرى',
                    ]),
            ])
            ->recordActions([
                Actions\Action::make('download')
                    ->label('فتح')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->url(fn ($record) => asset('storage/' . $record->file_path), shouldOpenInNewTab: true),

                Actions\EditAction::make()->label('تعديل'),

                Actions\DeleteAction::make()
                    ->label('حذف المرفق')
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد حذف المرفق')
                    ->modalDescription('سيتم حذف المرفق نهائيًا بعد التأكيد.')
                    ->modalSubmitActionLabel('نعم، حذف')
                    ->modalCancelActionLabel('إلغاء'),
            ])
            ->toolbarActions([
                Actions\CreateAction::make()->label('إضافة مرفق'),
            ])
            ->bulkActions([]);
    }
}
