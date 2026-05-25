<?php

/**
 * Main Master Configuration
 *
 * @version 1.0.0
 *
 * @license MIT
 */
return [
    'app' => [
        'profile' => [
            'name' => 'Laporan Kinerja Harian',
            'short_name' => 'LKH',
            'description' => 'Main Master is a automatic CRUD generator for Laravel 10',
            'keywords' => 'lkh, Laporan Kinerja Harian',
            'author' => '@arwahyupradana', // Your name or company
            'version' => '1.0.1', // major.minor.patch
            'laravel' => 'KPU KOTA DUMAI', // Laravel version
        ],
        'root' => [
            'backend' => 'App/Http/Controllers/Backend', // path to backend controller
            'frontend' => 'App/Http/Controllers/Frontend', // path to frontend controller
            'model' => 'App/Models', // path to model
            'view' => 'views/backend', // path to backend view
        ],
        'url' => [
            'backend' => 'admin', // url for backend
            'frontend' => 'web', // url for frontend
        ],
        'view' => [
            'backend' => 'backend', // path to backend view
            'frontend' => 'frontend', // path to frontend view
        ],
        'web' => [
            'template' => 'eduadmin', // template for frontend view (default: eduadmin)
            'icon' => '',
            'logo_light' => '/images/lkh.png',
            'logo_dark' => '/images/lkh.png',
            'favicon' => '/images/favicon.png',
            'background' => '/images/auth-bg/bg-1.jpg',
        ],
        'level' => [
            'read', 'create', 'update', 'delete', // level of access for user role and permission module
        ],
        'jabatan' => [
            '- Pilih Jabatan -',
            'Sekretaris',
            'Kepala Subbagian',
            'Staf',
        ],
        'subbagian' => [
            '- Pilih Subbagian -',
            'Keuangan, Umum & Logistik',
            'Teknis,Penyelenggara Pemilu dan Hukum',
            'Perencanaan, Data dan Informasi',
            'Partisipasi Hubungan Masyarakat dan Sumber Daya Manusia',
        ],
    ],
    'content' => [
        'announcement' => [
            'status' => [
                'sangat_penting' => 'Sangat Penting',
                'penting' => 'Penting',
                'biasa' => 'Biasa',
            ],
            'color' => [
                'sangat_penting' => 'danger',
                'penting' => 'warning',
                'biasa' => 'info',
            ],
        ],
    ],
];
