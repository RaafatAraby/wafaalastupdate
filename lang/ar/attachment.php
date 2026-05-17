<?php

return [
    'navigation' => [
        'label' => 'المرفقات',
    ],

    'models' => [
        'singular' => 'مرفق',
        'plural' => 'المرفقات',
    ],

    'form' => [
        'sections' => [
            'main' => 'بيانات المرفق',
        ],
        'fields' => [
            'country' => 'الدولة',
            'organization' => 'الجهة',
            'project' => 'المشروع',
            'financial_transaction' => 'الحركة المالية',
            'category' => 'الفئة',
            'original_name' => 'اسم الملف',
            'files' => 'الملفات',
            'beneficiaries_count' => 'عدد المستفيدين',
        ],
    ],

    'table' => [
        'category_labels' => [
            'documentation' => 'توثيق',
            'financial' => 'مالي',
            'final_report' => 'تقرير ختامي',
            'beneficiaries_sheet' => 'كشف المستفيدين',
            'offer' => 'عرض سعر',
            'other' => 'أخرى',
        ],
        'approval' => [
            'pending' => 'بانتظار المراجعة',
            'approved' => 'معتمد',
            'rejected' => 'مرفوض',
        ],
    ],

    'validation' => [
        'files_required' => 'يرجى رفع ملف واحد على الأقل.',
    ],
];
