<?php

use App\Enums\Permission;
use App\Enums\Role;

/**
 * Canonical role → permissions matrix.
 *
 * The seeder in database/seeders/RolesAndPermissionsSeeder.php reads this
 * file as the single source of truth. To grant or revoke a permission,
 * edit this file and re-run the seeder; do not edit the seeder directly.
 *
 * Use ['*'] to grant every permission to a role (super-admin).
 */
return [

    // Every permission known to the system. Permissions not listed here
    // will not exist in the database.
    'permissions' => Permission::values(),

    'roles' => [

        // مدير النظام: full access. Combined with Gate::before for short-circuit.
        Role::SystemAdmin->value => ['*'],

        // منشئ المشروع: only create/edit projects (until readiness decision).
        Role::ProjectCreator->value => [
            Permission::ProjectsView->value,
            Permission::ProjectsCreate->value,
            Permission::ProjectsUpdate->value,
        ],

        // معتمد الجاهزية (حسب الدولة): may approve readiness state on his country's projects.
        Role::ReadinessApprover->value => [
            Permission::ProjectsView->value,
            Permission::ProjectsReadinessApprove->value,
        ],

        // منفذ المشروع (حسب الدولة): execution + documentation updates and attachments.
        Role::ProjectExecutor->value => [
            Permission::ProjectsView->value,
            Permission::ProjectsExecutionUpdate->value,
            Permission::AttachmentsView->value,
            Permission::AttachmentsCreate->value,
            Permission::AttachmentsUpdate->value,
        ],

        // مدخل معززات المشروع (حسب الدولة):
        // مرفقات + رابط التوثيق على المشروع (بدون صلاحيات الحركات المالية).
        // ملاحظة: لا نعطي ProjectsExecutionUpdate لأنها تفتح إجراءات تحويل
        // حالة المشروع (completed/delayed/in_execution/...) في
        // ProjectsTable::updateExecution والتي لا يجوز لهذا الدور تنفيذها.
        // إجراءات "رابط التوثيق" و "نوع التوثيق" السريعة تعتمد على فحص
        // الأدوار المباشر في ->visible() ولا تتطلب هذه الصلاحية.
        Role::EnhancerEntryCountry->value => [
            Permission::ProjectsView->value,
            Permission::AttachmentsView->value,
            Permission::AttachmentsCreate->value,
            Permission::AttachmentsUpdate->value,
        ],

        // مدخل ومعتمد المعززات والحوالات (مقيّد بدولة المستخدم):
        // مشاهدة + إضافة مرفقات + اعتماد مرفقات. صلاحيات الحركات المالية
        // (financial.view/create/approve) لم تعد ممنوحة افتراضياً للدور
        // طبقاً لقرار إخفاء صفحة الحركات المالية عنه. لو احتاج مستخدم
        // فردي رؤية أو إنشاء حركة، يُمنح الصلاحية مباشرة من شاشة
        // "صلاحيات إضافية" (givePermissionTo على المستخدم) — وهذا يفعّل
        // البوابة في FinancialTransactionResource::isAllowed عبر
        // hasDirectPermission.
        Role::EnhancerFinanceCentral->value => [
            Permission::ProjectsView->value,
            Permission::AttachmentsView->value,
            Permission::AttachmentsCreate->value,
            Permission::AttachmentsApprove->value,
        ],

        // مُعدّ التقرير النهائي: read-only access + writes the final_report field.
        Role::FinalReportPreparer->value => [
            Permission::ProjectsView->value,
            Permission::AttachmentsView->value,
            Permission::FinancialView->value,
            Permission::FinalReportPrepare->value,
        ],

        // مشرف النظام (مجلس الإدارة): read-only oversight + final-report approval + rollback.
        Role::BoardSupervisor->value => [
            Permission::ProjectsView->value,
            Permission::AttachmentsView->value,
            Permission::FinancialView->value,
            Permission::ProjectsRollback->value,
            Permission::FinalReportApprove->value,
            Permission::SupervisionNote->value,
        ],
    ],
];
