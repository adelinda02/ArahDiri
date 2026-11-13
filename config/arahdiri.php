<?php

return [
    // Folder CSV katalog hasil pipeline career-guidance-riasec.
    'catalog_path' => env('ARAHDIRI_CATALOG_PATH') ?: database_path('data'),

    // Proxy tepercaya (mis. ngrok: *). Kosongkan bila situs diakses langsung.
    'trusted_proxies' => env('TRUSTED_PROXIES'),

    // Kode PT Universitas Mercu Buana (Jakarta). UMB Yogyakarta (051019) kampus lain.
    'umb_pt_code' => env('ARAHDIRI_UMB_PT_CODE', '031019'),

    // Versi instrumen dan mesin skor, disimpan di setiap hasil tes.
    'instrument_version' => 'riasec-50-v1',
    'scoring_version' => 'riasec-sum-norm-v1',

    // Jenjang pengguna yang dikenal sistem.
    'levels' => [
        'smp' => 'SMP',
        'sma_smk' => 'SMA / SMK',
        'mhs_umb' => 'Mahasiswa UMB',
    ],

    'recommend' => [
        // Prodi khusus agama tertentu (mis. Pendidikan Agama Islam, Teologi, Ekonomi Syariah)
        // tidak dimunculkan otomatis karena ArahDiri tidak menanyakan agama pengguna.
        // Prodi ini tetap ada di katalog. Ubah ke false untuk menyertakannya.
        'exclude_religious' => env('ARAHDIRI_EXCLUDE_RELIGIOUS', true),
        'religious_pattern' => '/islam|syari|tarbiy|dakwah|ushuluddin|qur|hadi(s|ts)|tafsir|aqidah|akidah|teologi|kristen|katolik|hindu|budd?ha|kh?onghucu|pastoral|kateket|misiolog|kependetaan|agama|zakat|wakaf|haji|falak|muamalah|ahwal|tasawuf|madz?hab|ulama|imam|spiritual|rohani|biblika|ustadz|penghulu|rohaniwan|pendeta|biarawa|madrasah|ibtidaiy|raudhatul|pesantren|dharma|gereja|siyasah|jinayah|ministri/i',
    ],
];
