<?php

/*
| Arah SMA untuk jenjang SMP (draf ArahDiri, belum resmi).
| Penjurusan SMA direncanakan kembali tahun 2027 dan aturannya masih disusun Kemendikdasmen,
| jadi label ditulis "arah IPA/IPS/Bahasa + mapel pilihan" dan disesuaikan setelah aturan terbit.
| Profil RIASEC skala 0-1 disusun dari karakter mata pelajaran tiap arah:
|  IPA    = Investigative kuat, Realistic dan Conventional pendukung
|  IPS    = Enterprising dan Social kuat, Conventional pendukung
|  Bahasa = Artistic dan Social kuat, Investigative pendukung
*/

return [
    'ipa' => [
        'name' => 'IPA',
        'subjects' => ['Matematika Tingkat Lanjut', 'Fisika', 'Kimia', 'Biologi', 'Informatika'],
        'profile' => ['r' => 0.65, 'i' => 1.0, 'a' => 0.25, 's' => 0.35, 'e' => 0.25, 'c' => 0.55],
    ],
    'ips' => [
        'name' => 'IPS',
        'subjects' => ['Ekonomi', 'Sosiologi', 'Geografi', 'Sejarah Tingkat Lanjut', 'Antropologi'],
        'profile' => ['r' => 0.2, 'i' => 0.45, 'a' => 0.3, 's' => 0.8, 'e' => 1.0, 'c' => 0.7],
    ],
    'bahasa' => [
        'name' => 'Bahasa',
        'subjects' => ['Bahasa Indonesia Tingkat Lanjut', 'Bahasa Inggris Tingkat Lanjut', 'Bahasa Asing Lain', 'Antropologi', 'Seni Budaya'],
        'profile' => ['r' => 0.1, 'i' => 0.5, 'a' => 1.0, 's' => 0.75, 'e' => 0.4, 'c' => 0.3],
    ],

    // Mapel pendukung per tipe minat (sama dengan MAPEL v14).
    'support' => [
        'R' => ['Fisika', 'Matematika', 'Informatika'],
        'I' => ['Matematika', 'Fisika, Kimia, atau Biologi', 'Informatika'],
        'A' => ['Seni Budaya', 'Bahasa Indonesia', 'Bahasa Inggris'],
        'S' => ['Biologi', 'Sosiologi', 'Bahasa Indonesia'],
        'E' => ['Ekonomi', 'Bahasa Inggris', 'Sosiologi'],
        'C' => ['Matematika', 'Ekonomi', 'Informatika'],
    ],
];
