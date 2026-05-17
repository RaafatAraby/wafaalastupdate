<?php

/*
 * Arabic translations for the activity log resource and the project
 * state-history table. Keys are referenced via __('activity_log.<group>.<key>').
 */
return [

    'models' => [
        'singular' => 'سجل عملية',
        'plural'   => 'سجل العمليات',
    ],

    'navigation' => [
        'group' => 'السجلات',
        'label' => 'سجل العمليات',
    ],

    'fields' => [
        'date'           => 'التاريخ',
        'project_number' => 'رقم المشروع',
        'project'        => 'المشروع',
        'event'          => 'العملية',
        'description'    => 'التفاصيل',
        'causer'         => 'بواسطة',
        'user'           => 'المستخدم',
        'system'         => 'النظام',
        'subject_type'   => 'نوع الكائن',
    ],

    'subject_types' => [
        'project'               => 'مشروع',
        'financial_transaction' => 'حركة مالية',
        'attachment'            => 'مرفق',
    ],

    // The translator resolves dotted keys like 'activity_log.events.project.created'
    // by looking up nested arrays. Keep the structure: events.<group>.<verb>.
    'events' => [
        ''        => '—',
        'project' => [
            'created'                => 'إنشاء مشروع',
            'updated'                => 'تعديل مشروع',
            'execution_updated'      => 'تحديث التنفيذ',
            'readiness_approved'     => 'اعتماد الجاهزية',
            'readiness_rejected'     => 'رفض الجاهزية',
            'auto_delayed'           => 'تحويل تلقائي لمتأخر',
            'final_report_drafted'   => 'صياغة التقرير النهائي',
            'final_report_approved'  => 'اعتماد التقرير النهائي',
            'rolled_back'            => 'تراجع عن الحالة',
            'auto_completed'         => 'اكتمال تلقائي للمشروع',
            'auto_closed'            => 'إغلاق تلقائي للمشروع',
            'completed'              => 'اكتمال المشروع',
            'closed'                 => 'إغلاق المشروع',
            'archived'               => 'أرشفة المشروع',
            'restored'               => 'استعادة المشروع',
            'deleted'                => 'حذف المشروع',
            'documentation_url_updated' => 'تحديث رابط التوثيق',
            'documentation_type_updated' => 'تحديث نوع التوثيق',
            'readiness_rejected'     => 'رفض الجاهزية',
            'auto_delayed'           => 'تحويل تلقائي لمتأخر',
        ],
        'attachment' => [
            'created'  => 'إضافة مرفق',
            'approved' => 'اعتماد مرفق',
            'rejected' => 'رفض مرفق',
            'deleted'  => 'حذف مرفق',
        ],
        'financial' => [
            'created'  => 'إضافة حركة مالية',
            'updated'  => 'تعديل حركة مالية',
            'approved' => 'اعتماد حركة مالية',
            'rejected' => 'رفض حركة مالية',
            'deleted'  => 'حذف حركة مالية',
        ],
    ],

];
