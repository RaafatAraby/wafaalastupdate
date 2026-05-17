<?php

namespace App\Enums;

/**
 * Canonical role slugs used as Spatie role names.
 *
 * Display names live in lang/ar/system.php. Never use the Arabic display
 * name as a role identifier in code.
 */
enum Role: string
{
    case SystemAdmin            = 'system_admin';
    case ProjectCreator         = 'project_creator';
    case ReadinessApprover      = 'readiness_approver';
    case ProjectExecutor        = 'project_executor';
    case EnhancerEntryCountry   = 'enhancer_entry_country';
    case EnhancerFinanceCentral = 'enhancer_finance_central';
    case FinalReportPreparer    = 'final_report_preparer';
    case BoardSupervisor        = 'board_supervisor';

    /** Roles whose data access is restricted to the user's assigned countries. */
    public static function countryScoped(): array
    {
        return [
            self::ReadinessApprover->value,
            self::ProjectExecutor->value,
            self::EnhancerEntryCountry->value,
            self::EnhancerFinanceCentral->value,
        ];
    }

    /** All role slugs as a flat array (handy for whereIn). */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
