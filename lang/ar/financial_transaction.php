<?php

return [

    'models' => [
        'singular' => 'حركة مالية',
        'plural'   => 'الحركات المالية',
    ],

    'navigation' => [
        'group' => 'المالية',
        'label' => 'الحركات المالية',
    ],

    'form' => [
        'sections' => [
            'main' => 'بيانات الحركة المالية',
        ],

        'fields' => [
            'transaction_type'         => 'نوع الحركة',
            'funding_source_country'   => 'دولة الممول',
            'country'                  => 'الدولة',
            'organization'             => 'الجهة الممولة',
            'project'                  => 'المشروع',
            'reference_no'             => 'الرقم المرجعي',
            'sender_name'              => 'اسم المحوّل',
            'receiver_name'            => 'اسم المستلم',
            'transfer_method'          => 'طريقة التحويل',
            'bank_name'                => 'اسم البنك',
            'category'                 => 'التصنيف',
            'amount'                   => 'المبلغ بالدولار (USD)',
            'currency'                 => 'العملة',
            'original_amount'          => 'المبلغ بالعملة الأصلية',
            'exchange_rate'            => 'سعر الصرف (مقابل الدولار)',
            'transaction_date'         => 'تاريخ الحركة',
            'notes'                    => 'ملاحظات',
            'approval_notes'           => 'ملاحظات الاعتماد',
            'approval_status'          => 'حالة الاعتماد',
            'attachment'               => 'ملف مرفق',
        ],

        'help' => [
            'exchange_rate'  => 'يُحدَّث تلقائياً عند تغيير العملة. يمكن تعديله يدوياً عند الحاجة.',
            'amount_usd'     => 'يُحتسب تلقائياً من المبلغ الأصلي × سعر الصرف.',
        ],

        'options' => [
            'transaction_types' => [
                'incoming' => 'واردة',
                'outgoing' => 'صادرة',
            ],
            'transfer_methods' => [
                'bank_transfer' => 'تحويل بنكي',
                'cash'          => 'نقدي',
                'hawala'        => 'حوالة',
                'wallet'        => 'محفظة إلكترونية',
                'other'         => 'أخرى',
            ],
            'categories' => [
                'project_support' => 'دعم مشاريع',
                'operational'     => 'تشغيلي',
                'administrative'  => 'إداري',
                'transfer'        => 'تحويل',
                'other'           => 'أخرى',
            ],
        ],
    ],

    'table' => [
        'columns' => [
            'project_number'    => 'رقم المشروع',
            'project'           => 'المشروع',
            'transaction_type'  => 'نوع الحركة',
            'amount'            => 'المبلغ',
            'approval_status'   => 'حالة الاعتماد',
            'transaction_date'  => 'التاريخ',
        ],

        'approval' => [
            'pending'  => 'بانتظار الاعتماد',
            'approved' => 'معتمدة',
            'rejected' => 'مرفوضة',
            'returned' => 'مُعادة',
        ],

        'actions' => [
            'edit' => 'تعديل',
        ],
    ],

];
