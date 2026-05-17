<?php

namespace App\Enums;

/**
 * Canonical permission keys.
 *
 * Naming convention: <domain>.<action>[.<scope>].
 * All permissions live on the `web` guard.
 */
enum Permission: string
{
    // Users / settings
    case UsersManage    = 'users.manage';
    case SettingsManage = 'settings.manage';

    // Projects
    case ProjectsView             = 'projects.view';
    case ProjectsCreate           = 'projects.create';
    case ProjectsUpdate           = 'projects.update';
    case ProjectsReadinessApprove = 'projects.readiness.approve';
    case ProjectsExecutionUpdate  = 'projects.execution.update';
    case ProjectsRollback         = 'projects.rollback';
    case ProjectsClose            = 'projects.close';

    // Attachments (مرفقات / معززات)
    case AttachmentsView    = 'attachments.view';
    case AttachmentsCreate  = 'attachments.create';
    case AttachmentsUpdate  = 'attachments.update';
    case AttachmentsApprove = 'attachments.approve';

    // Financial transactions (حوالات)
    case FinancialView    = 'financial.view';
    case FinancialCreate  = 'financial.create';
    case FinancialUpdate  = 'financial.update';
    case FinancialApprove = 'financial.approve';

    // Final report
    case FinalReportPrepare = 'final_report.prepare';
    case FinalReportApprove = 'final_report.approve';

    // Supervision
    case SupervisionNote = 'supervision.note';

    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
