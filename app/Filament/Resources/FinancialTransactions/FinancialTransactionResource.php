<?php

namespace App\Filament\Resources\FinancialTransactions;

use App\Enums\Permission;
use App\Enums\Role;
use App\Filament\Resources\FinancialTransactions\Pages;
use App\Filament\Resources\FinancialTransactions\Schemas\FinancialTransactionForm;
use App\Filament\Resources\FinancialTransactions\Tables\FinancialTransactionsTable;
use App\Models\FinancialTransaction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Authorization is delegated to App\Policies\FinancialTransactionPolicy
 * for record-level operations. Page access (showing the resource in
 * navigation, opening list/create/edit URLs) is gated below by role:
 * مدير النظام، مشرف المجلس، معد التقرير النهائي فقط.
 */
class FinancialTransactionResource extends Resource
{
    protected static ?string $model = FinancialTransaction::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 4;

    /**
     * صفحة الحركات المالية مرئية لـ:
     *  1. الأدوار الثلاثة المسؤولة افتراضياً (مدير النظام، مشرف المجلس،
     *     معد التقرير النهائي).
     *  2. أي مستخدم مُنحت له صلاحية مباشرة `financial.view` من شاشة
     *     "صلاحيات إضافية" حتى لو كان دوره الأساسي مخفياً عنه. يعتمد
     *     `hasDirectPermission` (وليس `can`) لتجنّب الالتفاف عبر
     *     الصلاحيات الموروثة من الدور (role-based).
     */
    private static function isAllowed(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        if ($user->hasAnyRole([
            Role::SystemAdmin->value,
            Role::BoardSupervisor->value,
            Role::FinalReportPreparer->value,
        ])) {
            return true;
        }
        return $user->hasDirectPermission(Permission::FinancialView->value);
    }

    public static function canAccess(): bool
    {
        return self::isAllowed();
    }

    public static function canViewAny(): bool
    {
        return self::isAllowed();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::isAllowed();
    }

    public static function getNavigationLabel(): string
    {
        return __('financial_transaction.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('financial_transaction.models.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('financial_transaction.models.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return FinancialTransactionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FinancialTransactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinancialTransactions::route('/'),
            'create' => Pages\CreateFinancialTransaction::route('/create'),
            'edit' => Pages\EditFinancialTransaction::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        // مشرف النظام + معد التقرير النهائي: رؤية كاملة لجميع
        // الحركات دون تصفية (توافقاً مع FinancialTransactionPolicy::view).
        if ($user && $user->hasAnyRole([
            Role::BoardSupervisor->value,
            Role::FinalReportPreparer->value,
        ])) {
            return parent::getEloquentQuery();
        }

        return parent::getEloquentQuery()->visibleTo($user);
    }
}
