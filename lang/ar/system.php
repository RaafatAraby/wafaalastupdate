<?php

/*
 * Display names for system roles and permissions. Keys are the canonical
 * slugs defined in App\Enums\Role and App\Enums\Permission.
 */
return [

    'roles' => [
        'system_admin'             => 'مدير النظام',
        'project_creator'          => 'منشئ المشروع',
        'readiness_approver'       => 'معتمد الجاهزية',
        'project_executor'         => 'منفذ المشروع',
        'enhancer_entry_country'   => 'مدخل معززات المشروع حسب الدولة',
        'enhancer_finance_central' => 'معتمد المعززات والحوالات مركزي',
        'final_report_preparer'    => 'معد التقرير النهائي',
        'board_supervisor'         => 'مشرف المجلس',
    ],

    'permissions' => [
        // Users / settings
        'users.manage'                 => 'إدارة المستخدمين',
        'settings.manage'              => 'إدارة الإعدادات',

        // Projects
        'projects.view'                => 'عرض المشاريع',
        'projects.create'              => 'إنشاء مشاريع',
        'projects.update'              => 'تعديل المشاريع',
        'projects.readiness.approve'   => 'اعتماد جاهزية المشاريع',
        'projects.execution.update'    => 'تحديث تنفيذ المشاريع',
        'projects.rollback'            => 'التراجع عن حالة المشروع',
        'projects.close'               => 'إغلاق المشاريع',

        // Attachments
        'attachments.view'             => 'عرض المرفقات',
        'attachments.create'           => 'إضافة مرفقات',
        'attachments.update'           => 'تعديل المرفقات',
        'attachments.approve'          => 'اعتماد المرفقات',

        // Financial transactions
        'financial.view'               => 'عرض الحركات المالية',
        'financial.create'             => 'إضافة حركات مالية',
        'financial.update'             => 'تعديل الحركات المالية',
        'financial.approve'            => 'اعتماد الحركات المالية',

        // Final report
        'final_report.prepare'         => 'إعداد التقرير النهائي',
        'final_report.approve'         => 'اعتماد التقرير النهائي',

        // Supervision
        'supervision.note'             => 'إضافة ملاحظات إشرافية',
    ],

];
