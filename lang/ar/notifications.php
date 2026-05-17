<?php

return [
    'project' => [
        'readiness_decided' => [
            'title' => 'تحديث جاهزية مشروع',
            'body' => 'تم تحديث حالة الجاهزية للمشروع :project',
        ],
        'execution_updated' => [
            'title' => 'تحديث تنفيذ مشروع',
            'body' => 'تم تحديث حالة التنفيذ للمشروع :project إلى :state',
        ],
        'final_report_drafted' => [
            'title' => 'تم إعداد التقرير النهائي',
            'body' => 'جاهز للاعتماد التقرير النهائي للمشروع :project',
        ],
        'final_report_approved' => [
            'title' => 'اعتماد التقرير النهائي',
            'body' => 'تم اعتماد التقرير النهائي للمشروع :project',
        ],
        'rolled_back' => [
            'title' => 'إرجاع مشروع',
            'body' => 'تم إرجاع المشروع :project إلى مرحلة سابقة',
        ],
        'closed' => [
            'title' => 'إغلاق مشروع',
            'body' => 'تم إغلاق المشروع :project',
        ],
        'auto_delayed' => [
            'title' => 'تحويل تلقائي لمشروع متأخر',
            'body' => 'تم تحويل المشروع :project إلى حالة متأخر — تجاوز تاريخ الانتهاء المتوقع :expected_end_date',
        ],
    ],
    'attachment' => [
        'created' => [
            'title' => 'إضافة مرفق جديد',
            'body' => 'تم رفع مرفق جديد على المشروع :project',
        ],
        'updated' => [
            'title' => 'تعديل مرفق',
            'body' => 'تم تعديل مرفق على المشروع :project',
        ],
        'approved' => [
            'title' => 'اعتماد مرفق',
            'body' => 'تم اعتماد مرفق على المشروع :project',
        ],
        'rejected' => [
            'title' => 'رفض مرفق',
            'body' => 'تم رفض مرفق على المشروع :project',
        ],
    ],
    'financial' => [
        'created' => [
            'title' => 'إضافة حركة مالية',
            'body' => 'تمت إضافة حركة مالية على المشروع :project',
        ],
        'updated' => [
            'title' => 'تعديل حركة مالية',
            'body' => 'تم تعديل حركة مالية على المشروع :project',
        ],
        'approved' => [
            'title' => 'اعتماد حركة مالية',
            'body' => 'تم اعتماد حركة مالية على المشروع :project',
        ],
        'rejected' => [
            'title' => 'رفض حركة مالية',
            'body' => 'تم رفض حركة مالية على المشروع :project',
        ],
    ],
];
