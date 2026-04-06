<?php

namespace App\Filament\Resources\FinancialTransactions\Pages;

use App\Filament\Resources\FinancialTransactions\FinancialTransactionResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Storage;

class EditFinancialTransaction extends EditRecord
{
    protected static string $resource = FinancialTransactionResource::class;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('removeAttachment')
                ->label('حذف المرفق')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(fn (): bool => filled($this->record?->attachment_path))
                ->requiresConfirmation()
                ->modalHeading('تأكيد حذف المرفق')
                ->modalDescription('سيتم حذف المرفق نهائياً. هل تريد المتابعة؟')
                ->modalSubmitActionLabel('نعم، حذف')
                ->modalCancelActionLabel('إلغاء')
                ->action(fn () => $this->removeAttachmentFile()),

            DeleteAction::make()->label('حذف الحركة'),
        ];
    }

    protected function removeAttachmentFile(): void
    {
        $path = $this->record->attachment_path;

        if (filled($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $this->record->update([
            'attachment_path' => null,
        ]);

        Notification::make()
            ->title('تم حذف المرفق بنجاح')
            ->success()
            ->send();
    }
}
