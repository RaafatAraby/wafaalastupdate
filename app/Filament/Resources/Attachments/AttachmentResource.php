<?php

namespace App\Filament\Resources\Attachments;

use App\Filament\Resources\Attachments\Pages;
use App\Filament\Resources\Attachments\Schemas\AttachmentForm;
use App\Filament\Resources\Attachments\Tables\AttachmentsTable;
use App\Enums\Role;
use App\Models\Attachment;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Authorization is delegated to App\Policies\AttachmentPolicy.
 */
class AttachmentResource extends Resource
{
    protected static ?string $model = Attachment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-paper-clip';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('attachment.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('attachment.models.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('attachment.models.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return AttachmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttachmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttachments::route('/'),
            'create' => Pages\CreateAttachment::route('/create'),
            'edit' => Pages\EditAttachment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        // مشرف النظام + معد التقرير النهائي: رؤية كاملة لجميع
        // المرفقات دون تصفية (توافقاً مع AttachmentPolicy::view).
        if ($user && $user->hasAnyRole([
            Role::BoardSupervisor->value,
            Role::FinalReportPreparer->value,
        ])) {
            return parent::getEloquentQuery();
        }

        return parent::getEloquentQuery()->visibleTo($user);
    }
}
