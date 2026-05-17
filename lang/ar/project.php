<?php

/*
 * Arabic translations for the projects module.
 *
 * Keys are referenced via __('project.<group>.<key>') from blade and PHP.
 */
return [

    // Filament resource model labels.
    'models' => [
        'project'              => 'مشروع',
        'project_plural'       => 'المشاريع',
        'state_history'        => 'سجل حالة',
        'state_history_plural' => 'سجل الحالات',
    ],

    // Sidebar / navigation labels.
    'navigation' => [
        'projects'      => 'المشاريع',
        'operations'    => 'العمليات',
        'state_history' => 'سجل الحالات',
    ],

    // Resource-level actions.
    'actions' => [
        'create' => 'إضافة مشروع',
    ],

    // Tab keys used in the edit form.
    'edit' => [
        'tabs' => [
            'basic'         => 'البيانات الأساسية',
            'documentation' => 'التوثيق والتقارير',
        ],
    ],

    // Common short field labels used in lists / placeholders.
    'fields' => [
        'project'        => 'المشروع',
        'project_number' => 'رقم المشروع',
        'date'           => 'التاريخ',
        'by_user'        => 'بواسطة',
        'from_state'     => 'الحالة السابقة',
        'to_state'       => 'الحالة الجديدة',
        'notes'          => 'الملاحظات',
    ],

    // Workflow tabs above the projects list.
    'tabs' => [
        'all'          => 'الكل',
        'undocumented' => 'غير موثق',
    ],

    // Form-level translations used by ProjectForm / ProjectEditForm.
    'form' => [
        'sections' => [
            'basic_info' => 'البيانات الأساسية',
        ],
        'fields' => [
            'project_number'         => 'رقم المشروع',
            'title'                  => 'اسم المشروع',
            'beneficiaries_count'    => 'عدد المستفيدين',
            'country'                => 'الدولة',
            'organization'           => 'الجهة الممولة',
            'approved_amount'        => 'المبلغ المعتمد',
            'state'                  => 'حالة المشروع',
            'financial_status'       => 'الحالة المالية',
            'documentation_type'     => 'نوع التوثيق',
            'documentation_status'   => 'حالة التوثيق',
            'documentation_url'      => 'رابط ألبوم الصور',
            'documentation_video_url'=> 'رابط إنتاج الفيديو',
            'project_files'          => 'ملفات المشروع',
            'project_files_help'     => 'حقل اختياري — يمكنك رفع ملفات تخص المشروع، وستظهر ضمن مرفقات المشروع.',
            'start_date'             => 'تاريخ البداية',
            'expected_end_date'      => 'الانتهاء المتوقع',
            'actual_end_date'        => 'الانتهاء الفعلي',
            'description'            => 'وصف المشروع',
            'readiness_notes'        => 'ملاحظات الجاهزية',
            'execution_notes'        => 'ملاحظات التنفيذ',
            'documentation_notes'    => 'ملاحظات التوثيق',
            'final_report'           => 'التقرير النهائي',
        ],
        'options' => [
            'states' => [
                ''                      => 'اختر',
                'new'                   => 'جديد',
                'pending_readiness'     => 'بانتظار الجاهزية',
                'ready_for_execution'   => 'جاهز للتنفيذ',
                'in_execution'          => 'قيد التنفيذ',
                'pending_documentation' => 'بانتظار التوثيق',
                'delayed'               => 'متأخر',
                'completed'             => 'مكتمل',
                'closed'                => 'مغلق',
            ],
            'documentation_types' => [
                'one_time'    => 'مرة واحدة',
                'continuous'  => 'مستمر',
                'periodic'    => 'دوري',
            ],
            'documentation_statuses' => [
                'not_started' => 'غير موثق',
                'partial'     => 'جزئي',
                'complete'    => 'مكتمل',
            ],
            'financial_statuses' => [
                'unfunded'         => 'غير ممول',
                'partially_funded' => 'تمويل جزئي',
                'funded'           => 'ممول',
                'partially_spent'  => 'صرف جزئي',
                'settled'          => 'مسوى',
            ],
        ],
    ],

    // Used by the workspace blade view __('project.states.<key>').
    'states' => [
        ''                       => '—',
        'new'                    => 'جديد',
        'pending_readiness'      => 'بانتظار الجاهزية',
        'ready_for_execution'    => 'جاهز للتنفيذ',
        'in_execution'           => 'قيد التنفيذ',
        'pending_documentation'  => 'بانتظار التوثيق',
        'delayed'                => 'متأخر',
        'completed'              => 'مكتمل',
        'closed'                 => 'مغلق',
        'rejected'               => 'مرفوض',
        'cancelled'              => 'ملغي',
        'on_hold'                => 'موقوف',
    ],

    'documentation_status' => [
        'not_started' => 'غير موثق',
        'partial'     => 'جزئي',
        'complete'    => 'مكتمل',
    ],

    'financial_status' => [
        'unfunded'          => 'غير ممول',
        'partially_funded'  => 'تمويل جزئي',
        'funded'            => 'ممول',
        'partially_spent'   => 'صرف جزئي',
        'settled'           => 'مسوى',
    ],

];
