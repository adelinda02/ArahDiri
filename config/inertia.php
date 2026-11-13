<?php

// Hanya menimpa lokasi halaman: proyek ini memakai resources/js/Pages (huruf P besar, seperti v14 → v2).
// Kunci lain diambil dari konfigurasi bawaan inertia-laravel.
return [
    'pages' => [
        'ensure_pages_exist' => false,
        'paths' => [resource_path('js/Pages')],
        'extensions' => ['vue', 'js'],
    ],

    'testing' => [
        'ensure_pages_exist' => true,
    ],
];
