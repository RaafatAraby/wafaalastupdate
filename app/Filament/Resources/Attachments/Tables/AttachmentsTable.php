<?php

namespace App\Filament\Resources\Attachments\Tables;

use App\Models\Attachment;
use App\Models\Country;
use App\Models\Organization;
use App\Services\ActivityLogger;
use App\Services\InternalNotifier;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class AttachmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                if (request()->filled('project_id')) {
                    $query->where('project_id', (int) request()->query('project_id'));
                }

                return $query;
            })
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
                    ->formatStateUsing(fn (?string $state) => self::categoryOptions()[$state ?? 'other'] ?? '-'),

                TextColumn::make('original_name')
                    ->label('اسم الملف')
                    ->formatStateUsing(function ($state, $record) {
                        $names = self::normalizeNames($state);

                        if (count($names) > 0) {
                            return implode(' | ', $names);
                        }

                        $paths = self::extractPaths($record->file_path ?? null);

                        return count($paths) > 0 ? ('ملفات مرفوعة (' . count($paths) . ')') : '-';
                    })
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('تاريخ الرفع')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('country_id')
                    ->label('الدولة')
                    ->options(self::countryOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, $value) => $query->whereHas('project', fn (Builder $projectQuery) => $projectQuery->where('country_id', $value))
                        );
                    }),

                SelectFilter::make('organization_id')
                    ->label('الجهة الممولة')
                    ->options(self::organizationOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['value'] ?? null,
                            fn (Builder $query, $value) => $query->whereHas('project', fn (Builder $projectQuery) => $projectQuery->where('organization_id', $value))
                        );
                    }),

SelectFilter::make('project_id')
    ->label('المشروع')
    ->options(
        \App\Models\Project::query()
            ->orderBy('project_number')
            ->get()
            ->mapWithKeys(fn ($project) => [
                $project->getKey() => trim(($project->project_number ? $project->project_number . ' - ' : '') . ($project->title ?? $project->name ?? $project->project_name ?? ''))
            ])
            ->toArray()
    )
    ->searchable()
    ->preload(),


                SelectFilter::make('category')
                    ->label('التصنيف')
                    ->options(self::categoryOptions()),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')->label('تاريخ الرفع (من)'),
                        DatePicker::make('created_until')->label('تاريخ الرفع (إلى)'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(5)
            ->deferFilters()
            ->recordActions([
                Actions\Action::make('download')
                    ->label('فتح أول ملف')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->visible(fn ($record) => count(self::extractPaths($record->file_path ?? null)) > 0)
                    ->url(function ($record) {
                        $paths = self::extractPaths($record->file_path ?? null);

                        return asset('storage/' . $paths[0]);
                    }, shouldOpenInNewTab: true),

                // Read-only details modal: visible to anyone who can view
                // the record. Useful for roles that have view + create
                // permissions but cannot edit (e.g. enhancer_finance_central).
                Actions\ViewAction::make()
                    ->label('تفاصيل')
                    ->modalHeading('عرض مرفق')
                    ->modalWidth('4xl')
                    ->slideOver(false)
                    ->authorize(fn (Attachment $record): bool => Auth::user()?->can('view', $record) ?? false),

                Actions\EditAction::make()
                    ->label('تعديل')
                    ->modalWidth('4xl')
                    ->slideOver(false)
                    ->authorize(fn (Attachment $record): bool => Auth::user()?->can('update', $record) ?? false),

                Actions\Action::make('approve')
                    ->label('اعتماد')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Attachment $record): bool => ($record->approval_status !== 'approved')
                        && (Auth::user()?->can('approve', $record) ?? false))
                    ->action(function (Attachment $record): void {
                        $record->forceFill([
                            'approval_status' => 'approved',
                            'reviewed_by' => Auth::id(),
                            'approved_at' => now(),
                        ])->saveQuietly();
                        ActivityLogger::log('attachment.approved', 'اعتماد مرفق', $record);
                        // Config-driven dispatch — recipients live in
                        // config/notifications-recipients.php so the role
                        // list stays in one place. Filename is intentionally
                        // not passed: the WhatsApp / email body shows
                        // project + category + date instead.
                        InternalNotifier::dispatch('attachment.approved', $record);
                        Notification::make()->success()->title('تم اعتماد المرفق')->send();
                    }),

                Actions\Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Attachment $record): bool => ($record->approval_status !== 'approved')
                        && (Auth::user()?->can('reject', $record) ?? false))
                    ->form([
                        Textarea::make('review_note')->label('سبب الرفض')->required()->rows(3),
                    ])
                    ->action(function (Attachment $record, array $data): void {
                        // Once approved, an attachment can never be rejected —
                        // not even by system_admin. Defensive guard because
                        // Gate::before grants system_admin every ability.
                        if ($record->approval_status === 'approved') {
                            Notification::make()->danger()
                                ->title('لا يمكن رفض مرفق مُعتمد')
                                ->send();
                            return;
                        }

                        $record->forceFill([
                            'approval_status' => 'rejected',
                            'reviewed_by' => Auth::id(),
                            'review_note' => $data['review_note'],
                            'approved_at' => null,
                        ])->saveQuietly();
                        ActivityLogger::log('attachment.rejected', 'رفض مرفق', $record, ['reason' => $data['review_note']]);
                        InternalNotifier::dispatch('attachment.rejected', $record, [
                            // `notes` is the key InternalNotifier maps into
                            // the rendered body — `reason` is silently
                            // dropped and would lose the rejection reason.
                            'notes' => $data['review_note'],
                        ]);
                        Notification::make()->warning()->title('تم رفض المرفق')->send();
                    }),

                // Hard delete is only for مدير النظام (Gate::before grants system_admin
                // any ability; AttachmentPolicy::delete returns false for everyone else).
                Actions\DeleteAction::make()
                    ->label('حذف')
                    ->requiresConfirmation()
                    ->modalHeading('حذف المرفق')
                    ->modalDescription('سيتم حذف المرفق نهائياً. هذه العملية لا يمكن التراجع عنها.')
                    ->modalSubmitActionLabel('نعم، حذف'),
                    // حذف: التسجيل في سجل العمليات يتم تلقائياً عبر AttachmentObserver::deleted()
                    // مع context أوسع (project_id + file)، لتجنب تكرار السطور على نفس الحدث.
            ])
            ->toolbarActions([
                Actions\CreateAction::make()->label('إضافة مرفق'),
            ])
            ->bulkActions([]);
    }

    private static function categoryOptions(): array
    {
        return [
            'documentation' => 'توثيق',
            'financial' => 'مالي',
            'final_report' => 'تقرير ختامي',
            'beneficiaries_sheet' => 'كشف المستفيدين',
            'offer' => 'عرض سعر',
            'other' => 'أخرى',
        ];
    }

    private static function countryOptions(): array
    {
        $label = self::countryLabelColumn();

        return Country::query()
            ->orderBy($label)
            ->pluck($label, 'id')
            ->toArray();
    }

    private static function organizationOptions(): array
    {
        $label = self::organizationLabelColumn();

        return Organization::query()
            ->orderBy($label)
            ->pluck($label, 'id')
            ->toArray();
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

    private static function organizationLabelColumn(): string
    {
        foreach (['name', 'title', 'organization_name'] as $column) {
            if (SchemaFacade::hasColumn('organizations', $column)) {
                return $column;
            }
        }

        return 'id';
    }

    private static function extractPaths(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value));
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return array_values(array_filter($decoded));
            }

            return $value !== '' ? [$value] : [];
        }

        return [];
    }

    private static function normalizeNames(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value));
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return array_values(array_filter($decoded));
            }

            return $value !== '' ? [$value] : [];
        }

        return [];
    }
}
