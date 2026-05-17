<?php

/**
 * Recipient matrix per business event.
 *
 * Each event maps to a list of role slugs that should be notified. For
 * country-scoped roles, the dispatcher will only target users whose
 * `user_countries` row matches the subject project's country_id.
 *
 * The originating user (auth()->id()) is always excluded, so users are
 * never notified about their own actions.
 *
 * Add or remove roles per event without touching application code.
 */
return [
    'events' => [
        // ─── Project lifecycle ──────────────────────────────────────────
        'project.created' => [
            'system_admin',
            'board_supervisor',
            'readiness_approver',          // country-scoped → filtered to project's country
            'enhancer_finance_central',    // country-scoped
        ],
        'project.updated' => [
            'system_admin',
            'board_supervisor',
            'enhancer_finance_central',    // country-scoped
        ],
        'project.readiness_approved' => [
            'system_admin',
            'board_supervisor',
            'project_creator',
            'project_executor',            // country-scoped
            'enhancer_finance_central',    // country-scoped
        ],
        'project.readiness_rejected' => [
            'system_admin',
            'board_supervisor',
            'project_creator',
            'enhancer_finance_central',    // country-scoped
        ],
        'project.execution_updated' => [
            'system_admin',
            'board_supervisor',
            'project_creator',
            'enhancer_finance_central',    // country-scoped
        ],
        'project.final_report_drafted' => [
            'system_admin',
            'board_supervisor',
            'enhancer_finance_central',    // country-scoped
        ],
        'project.final_report_approved' => [
            'system_admin',
            'project_creator',
            'final_report_preparer',
            'enhancer_finance_central', // country-scoped → filtered to project's country
        ],
        'project.rolled_back' => [
            'system_admin',
            'project_creator',
            'project_executor',            // country-scoped
            'final_report_preparer',
            'enhancer_finance_central',    // country-scoped
        ],
        'project.closed' => [
            'system_admin',
            'board_supervisor',
            'project_creator',
            'final_report_preparer',
            'enhancer_finance_central', // country-scoped → filtered to project's country
        ],
        'project.completed' => [
            'system_admin',
            'board_supervisor',
            'project_creator',
            'final_report_preparer',
            'enhancer_finance_central',    // country-scoped
        ],
        'project.archived' => [
            'system_admin',
            'enhancer_finance_central',    // country-scoped
        ],
        // Auto-transition to 'delayed' when expected_end_date is past.
        // project_executor + enhancer_entry_country are country-scoped and
        // will be filtered to the project's country by recipientsFor().
        'project.auto_delayed' => [
            'system_admin',
            'project_executor',
            'enhancer_entry_country',
            'enhancer_finance_central',    // country-scoped
        ],

        // ─── Attachments ────────────────────────────────────────────────
        'attachment.created' => [
            'system_admin',
            'enhancer_finance_central',    // central approver needs to know (country-scoped)
        ],
        'attachment.updated' => [
            'system_admin',
            'enhancer_finance_central',    // country-scoped
        ],
        'attachment.approved' => [
            'system_admin',
            'board_supervisor',
            'enhancer_entry_country',      // original uploader's role; country-scoped
            'enhancer_finance_central',    // country-scoped
        ],
        'attachment.rejected' => [
            'system_admin',
            'enhancer_entry_country',
            'enhancer_finance_central',    // country-scoped
        ],

        // ─── Financial transactions ─────────────────────────────────────
        // ملاحظة: enhancer_finance_central لم يعد يُشعَر بأحداث الحركات
        // المالية بعد PR #11 لأن صفحة الحركات المالية مخفية عنه (لا
        // يستطيع فتح الإشعار). الإشعارات تذهب فقط لمن يستطيع رؤية
        // السجل.
        'financial.created' => [
            'system_admin',
            'board_supervisor',
        ],
        'financial.updated' => [
            'system_admin',
            'board_supervisor',
        ],
        'financial.approved' => [
            'system_admin',
            'board_supervisor',
            'project_creator',
        ],
        'financial.rejected' => [
            'system_admin',
            'board_supervisor',
            'project_creator',
        ],
    ],

    /**
     * Roles whose visibility is constrained to specific countries. When
     * dispatching, the notifier will only target users with a country
     * binding matching the subject's country_id.
     */
    'country_scoped_roles' => [
        'readiness_approver',
        'project_executor',
        'enhancer_entry_country',
        'enhancer_finance_central',
    ],
];
