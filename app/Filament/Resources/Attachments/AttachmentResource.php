<?php

namespace App\Filament\Resources\Attachments;

use App\Filament\Resources\Attachments\Pages\CreateAttachment;
use App\Filament\Resources\Attachments\Pages\EditAttachment;
use App\Filament\Resources\Attachments\Pages\ListAttachments;
use App\Filament\Resources\Attachments\Schemas\AttachmentForm;
use App\Filament\Resources\Attachments\Tables\AttachmentsTable;
use App\Models\Attachment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class AttachmentResource extends Resource
{
    protected static ?string $model = Attachment::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paper-clip';
    protected static ?string $navigationLabel = 'المرفقات';
    protected static ?string $modelLabel = 'مرفق';
    protected static ?string $pluralModelLabel = 'المرفقات';
    protected static ?int $navigationSort = 12;

    public static function form(Schema $schema): Schema
    {
        return AttachmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttachmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttachments::route('/'),
            'create' => CreateAttachment::route('/create'),
            'edit' => EditAttachment::route('/{record}/edit'),
        ];
    }
}
