<?php

return [
    'navigation' => [
        'label' => 'المستخدمون',
    ],
    'models' => [
        'singular' => 'مستخدم',
        'plural' => 'المستخدمون',
    ],
    'form' => [
        'sections' => [
            'main' => 'بيانات المستخدم',
        ],
        'fields' => [
            'name' => 'الاسم',
            'email' => 'البريد الإلكتروني',
            'password' => 'كلمة المرور',
            'roles' => 'الأدوار',
            'permissions' => 'صلاحيات إضافية',
            'countries' => 'الدول المسموح بها',
            'is_active' => 'نشط',
        ],
    ],
    'table' => [
        'columns' => [
            'name' => 'الاسم',
            'email' => 'البريد الإلكتروني',
            'roles' => 'الأدوار',
            'countries' => 'الدول',
            'is_active' => 'الحالة',
        ],
    ],
];
