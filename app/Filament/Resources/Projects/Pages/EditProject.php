<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Projects\Schemas\ProjectEditForm;
use App\Support\NumericNormalizer;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use App\Services\ActivityLogger;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function form(Schema $schema): Schema
    {
        return ProjectEditForm::configure($schema);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();
        $data['funder_organization_id'] = $data['organization_id'] ?? null;

        NumericNormalizer::apply($data, 'approved_amount');
        NumericNormalizer::apply($data, 'beneficiaries_count');

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('add_attachment')
                ->label('إضافة مرفق')
                ->icon('heroicon-o-paper-clip')
                ->color('info')
                ->visible(fn () => auth()->user()?->can('attachments.create') ?? false)
                ->url(fn () => url('/admin/attachments/create?project_id=' . $this->record->id)),

            Actions\Action::make('add_financial_transaction')
                ->label('إضافة حركة مالية')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn () => \App\Filament\Resources\FinancialTransactions\FinancialTransactionResource::canAccess()
                    && (auth()->user()?->can('financial.create') ?? false))
                ->url(fn () => url('/admin/financial-transactions/create?project_id=' . $this->record->id)),

            // ─── وصول سريع لإضافة/تحديث رابط التوثيق ──────────────────────
            Actions\Action::make('add_documentation_url')
                ->label('رابط التوثيق')
                ->icon('heroicon-o-link')
                ->color('warning')
                ->visible(fn () => auth()->user()?->can('update', $this->record) ?? false)
                ->modalHeading('إضافة/تحديث روابط التوثيق')
                ->fillForm(fn (): array => [
                    'photo_album_url' => $this->record->photo_album_url,
                    'video_album_url' => $this->record->video_album_url,
                ])
                ->form([
                    TextInput::make('photo_album_url')
                        ->label('رابط ألبوم الصور')
                        ->url()
                        ->prefixIcon('heroicon-o-photo')
                        ->maxLength(2048)
                        ->placeholder('https://...'),
                    TextInput::make('video_album_url')
                        ->label('رابط إنتاج الفيديو')
                        ->url()
                        ->prefixIcon('heroicon-o-video-camera')
                        ->maxLength(2048)
                        ->placeholder('https://...'),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'photo_album_url' => $data['photo_album_url'] ?? null,
                        'video_album_url' => $data['video_album_url'] ?? null,
                        'updated_by' => auth()->id(),
                    ]);
                    ActivityLogger::log(
                        'project.documentation_url_updated',
                        'تحديث رابط التوثيق',
                        $this->record
                    );
                    Notification::make()->success()->title('تم حفظ رابط التوثيق')->send();
                    $this->refreshFormData(['photo_album_url', 'video_album_url']);
                }),

            Actions\ActionGroup::make([
                Actions\Action::make('view_attachments')
                    ->label('عرض المرفقات')
                    ->icon('heroicon-o-folder')
                    ->url(fn () => url('/admin/attachments?project_id=' . $this->record->id)),

                Actions\Action::make('view_transactions')
                    ->label('عرض الحركات المالية')
                    ->icon('heroicon-o-list-bullet')
                    ->visible(fn () => \App\Filament\Resources\FinancialTransactions\FinancialTransactionResource::canAccess())
                    ->url(fn () => url('/admin/financial-transactions?project_id=' . $this->record->id)),
            ])
                ->label('عرض')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->button(),

            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
