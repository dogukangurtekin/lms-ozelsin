<?php

return [
    'types' => [
        'system_message' => [
            'label' => 'Sistem mesajlari',
            'description' => 'Zorunlu sistem duyurulari ve yonetsel bilgilendirmeler.',
            'roles' => ['admin', 'teacher', 'student', 'parent'],
            'locked' => true,
            'default' => true,
        ],
        'assignment_created' => [
            'label' => 'Odev bildirimleri',
            'description' => 'Yeni odev ve odev yayimlama bildirimleri.',
            'roles' => ['admin', 'teacher', 'student', 'parent'],
            'locked' => false,
            'default' => true,
        ],
        'meeting_created' => [
            'label' => 'Gorusme bildirimleri',
            'description' => 'Yeni gorusme planlari ve gorusme bilgilendirmeleri.',
            'roles' => ['admin', 'teacher', 'student', 'parent'],
            'locked' => false,
            'default' => true,
        ],
        'attendance_reminder' => [
            'label' => 'Yoklama hatirlatmalari',
            'description' => 'Alinmayan yoklamalar icin otomatik hatirlatmalar.',
            'roles' => ['admin', 'teacher'],
            'locked' => true,
            'default' => true,
        ],
        'parent_report_ready' => [
            'label' => 'Veli rapor bildirimleri',
            'description' => 'Hazirlanan veli raporu ve paylasim bildirimleri.',
            'roles' => ['parent'],
            'locked' => false,
            'default' => true,
        ],
    ],
];
