<?php

/*
| Mata pelajaran pendukung prodi untuk menyambungkan arah SMA → prodi kuliah (jalur SMP).
|
| Sumber: Lampiran Keputusan Menteri Pendidikan Dasar dan Menengah Nomor 102/M/2025 tentang Mata
| Pelajaran Pendukung Program Studi dalam Seleksi Nasional Berdasarkan Prestasi (ditetapkan 29 Juli 2025;
| mencabut Kepmendikbudristek 345/M/2022). Kolom yang dipakai: Kurikulum Merdeka.
| 59 kelompok prodi di lampiran dipetakan ke nama prodi katalog lewat pola nama (aturan pertama yang
| cocok dipakai); nomor kelompok lampiran ditulis di komentar tiap aturan. "dan/atau" ditulis sebagai
| daftar mapel. Prodi kependidikan (no. 32) memakai paling banyak 1 mapel yang relevan dengan bidangnya.
*/

return [
    'source' => 'Kepmendikdasmen Nomor 102/M/2025 tentang Mata Pelajaran Pendukung Program Studi dalam SNBP (29 Juli 2025)',
    'source_short' => 'Kepmendikdasmen 102/M/2025',

    // Diktum KETIGA: komponen tambahan SNBP bagi lulusan SMK/MAK.
    'smk_note' => 'Bagi lulusan SMK, nilai mapel Dasar-dasar Program Keahlian dan Projek Kreatif dan Kewirausahaan ikut menjadi komponen penilaian SNBP untuk mendukung prodi yang dipilih (Kepmendikdasmen 102/M/2025, Diktum KETIGA).',

    // Mapel → arah SMA (config/sma_tracks.php). Mapel umum (wajib di semua arah) → [].
    'tracks' => [
        'Matematika Tingkat Lanjut' => ['ipa'],
        'Fisika' => ['ipa'],
        'Kimia' => ['ipa'],
        'Biologi' => ['ipa'],
        'Informatika' => ['ipa'],
        'Ekonomi' => ['ips'],
        'Sosiologi' => ['ips'],
        'Geografi' => ['ips'],
        'Sejarah' => ['ips'],
        'Antropologi' => ['ips', 'bahasa'],
        'Bahasa Indonesia Tingkat Lanjut' => ['bahasa'],
        'Bahasa Inggris Tingkat Lanjut' => ['bahasa'],
        'Bahasa Asing' => ['bahasa'],
        'Seni Budaya' => ['bahasa'],
        'Matematika' => [],
        'Bahasa Inggris' => [],
        'Pendidikan Pancasila' => [],
        'PJOK' => [],
    ],

    // Nama mapel lama (sebelum 102/M/2025) yang mungkin masih tersimpan di suntingan admin.
    'aliases' => [
        'Bahasa Indonesia' => 'Bahasa Indonesia Tingkat Lanjut',
    ],

    // Awalan prodi kependidikan; sisanya dicocokkan ulang ke aturan di bawah. "Pendidikan Dokter/Profesi" bukan kependidikan.
    'education_prefix' => '/^(pendidikan|tadris|pend\.)\s+(?!(dokter|profesi)\b)/iu',
    'education_max' => 1,

    // [pola nama (regex, tanpa membedakan huruf besar), mapel pendukung]  — nomor = kelompok prodi lampiran
    'rules' => [
        ['/kedokteran|dokter|veteriner/iu', ['Biologi', 'Kimia']],                                           // 36, 37, 38
        ['/farmasi|gizi|nutrisi/iu', ['Biologi', 'Kimia']],                                                  // 39, 40
        ['/pangan|hasil pertanian|hasil peternakan|hasil perikanan/iu', ['Kimia', 'Biologi']],              // 56
        ['/informatika (medis|kesehatan)/iu', ['Biologi', 'Matematika Tingkat Lanjut']],                    // 54
        ['/bioinformatika|bioteknologi|bio teknologi|biokewirausahaan/iu', ['Biologi', 'Matematika']],      // 52
        ['/olahraga|keolahragaan|kepelatihan|jasmani|penjas/iu', ['PJOK', 'Biologi']],                       // 49
        ['/keperawatan|kebidanan|kesehatan|epidemiologi|fisioterapi|radiolog|laboratorium medi|rekam medi|okupasi|terapi wicara|gigi/iu', ['Biologi']], // 41-44
        ['/sains data|data sains|statistika|aktuaria|matematika|logika/iu', ['Matematika Tingkat Lanjut']],  // 18, 19, 57
        ['/perencanaan wilayah|planologi|tata kota/iu', ['Ekonomi', 'Matematika']],                         // 24
        ['/geografi|penginderaan jauh/iu', ['Geografi', 'Matematika']],                                      // 53
        ['/arsitektur/iu', ['Matematika', 'Fisika']],                                                        // 23
        ['/desain|dkv|interior/iu', ['Seni Budaya', 'Matematika']],                                          // 25
        ['/informatika|komputer|sistem informasi|perangkat lunak|siber|keamanan informasi|teknologi informasi|ilmu informasi|kecerdasan buatan|gim|sains informasi|perpustakaan|kearsipan|informasi/iu', ['Matematika Tingkat Lanjut']], // 17, 45
        ['/kebumian|geofisika|meteorologi|klimatologi|astronomi/iu', ['Fisika', 'Matematika Tingkat Lanjut']], // 11, 16
        ['/teknik kimia|rekayasa kimia/iu', ['Kimia', 'Matematika Tingkat Lanjut']],                        // 33
        ['/teknik|rekayasa|teknologi|mekatronika|elektro|geodesi|geologi|pertambangan|perminyakan|metalurgi|otomotif|telekomunikasi|jaringan|elektronika|instrumentasi|otomasi|robotika|listrik|mesin/iu', ['Matematika Tingkat Lanjut', 'Fisika']], // 33
        ['/kelautan|oseanografi/iu', ['Biologi']],                                                           // 12
        ['/fisika/iu', ['Fisika']],                                                                          // 14, 15
        ['/kimia/iu', ['Kimia']],                                                                            // 10
        ['/biologi|biosains|perairan|sumber ?daya air|mikrobiologi|konservasi|kehutanan|hutan|lingkungan|peternakan|perikanan|akuakultur|budidaya|pertanian|agro|agronomi|agribisnis|tanah|perkebunan|hortikultura|kopi|ipa\b/iu', ['Biologi']], // 13, 20-22, 34, 35, 55, 58
        ['/transportasi|penerbangan|pelayaran|nautika|logistik maritim/iu', ['Matematika Tingkat Lanjut']], // 51
        ['/psikologi/iu', ['Sosiologi', 'Matematika']],                                                      // 9
        ['/hukum/iu', ['Sosiologi', 'Pendidikan Pancasila']],                                                // 46
        ['/pertahanan|bela negara|pancasila|kewarganegaraan|ppkn/iu', ['Pendidikan Pancasila']],             // 8
        ['/pariwisata|perhotelan|hospitali|perjalanan wisata|usaha perjalanan/iu', ['Ekonomi', 'Bahasa Inggris Tingkat Lanjut']], // 50
        ['/akuntansi|manajemen|bisnis|logistik|keuangan|perbankan|pemasaran|ritel|kewirausahaan|perpajakan|asuransi|ips\b/iu', ['Ekonomi']], // 26-30
        ['/ekonomi/iu', ['Ekonomi', 'Matematika']],                                                          // 7
        ['/sejarah|arkeologi/iu', ['Sejarah']],                                                              // 2
        ['/antropologi|humanitas/iu', ['Antropologi', 'Sosiologi']],                                          // 59
        ['/linguistik/iu', ['Bahasa Indonesia Tingkat Lanjut', 'Bahasa Inggris']],                          // 3
        ['/inggris/iu', ['Bahasa Inggris Tingkat Lanjut']],                                                  // 4 (bahasa asing relevan)
        ['/jepang|jerman|perancis|prancis|arab|mandarin|cina|tionghoa|korea|belanda|rusia|asing/iu', ['Bahasa Asing', 'Bahasa Indonesia Tingkat Lanjut']], // 4
        ['/sastra|bahasa|tarjamah|penerjemah|indonesia|jawa|sunda|bali|daerah/iu', ['Bahasa Indonesia Tingkat Lanjut']], // 3, 4
        ['/seni|musik|tari|teater|film|animasi|kriya|karawitan|pedalangan|fotografi|televisi|pertunjukan|etnomusikologi|sinemat|perfilman|broadcasting/iu', ['Seni Budaya']], // 1
        ['/komunikasi|jurnalistik|hubungan masyarakat|penyiaran/iu', ['Sosiologi', 'Antropologi']],          // 31
        ['/filsafat|sosial|sosiologi|politik|pemerintahan|administrasi|hubungan internasional|kriminologi|militer|kebijakan|publik|konseling|bimbingan/iu', ['Sosiologi']], // 5, 6, 47, 48
    ],

    // Jika tidak ada pola yang cocok: berdasarkan rumpun (kolom field) prodi.
    'by_field' => [
        'MIPA' => ['Matematika Tingkat Lanjut'],
        'Teknik' => ['Matematika Tingkat Lanjut', 'Fisika'],
        'Kesehatan' => ['Biologi'],
        'Pertanian' => ['Biologi'],
        'Ekonomi' => ['Ekonomi', 'Matematika'],
        'Sosial' => ['Sosiologi'],
        'Humaniora' => ['Bahasa Indonesia Tingkat Lanjut'],
        'Seni' => ['Seni Budaya'],
    ],
];
