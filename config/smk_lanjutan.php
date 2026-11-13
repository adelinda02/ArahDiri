<?php

/*
| Program keahlian SMK → rumpun prodi lanjutan (jalur SMP per profesi, rec-v3).
|
| Data jabatan lulusan SMK (smk_program_jobs) kebanyakan berupa jabatan teknisi, sehingga tidak selalu
| bertemu jabatan profesi lulusan kuliah (mis. DPIB → juru gambar, bukan arsitek). Peta ini menjembatani:
| program SMK cocok untuk prodi yang namanya cocok dengan pola di sini.
| Kode = Spektrum Keahlian SMK (Kepmendikbudristek 244/M/2024). Disusun dari nama dan bidang keahlian.
| Draf ArahDiri: minta guru BK / guru SMK menelaah sebelum rilis publik.
*/

return [
    '1.1' => '/teknik sipil|bangunan|gedung|konstruksi/iu',                                   // Teknik Perawatan Gedung
    '1.2' => '/teknik sipil|konstruksi|jalan|jembatan|bangunan|infrastruktur/iu',             // Konstruksi dan Perawatan Bangunan Sipil
    '1.3' => '/teknik sipil|konstruksi|arsitektur|perumahan|bangunan/iu',                     // Teknik Konstruksi dan Perumahan
    '1.4' => '/arsitektur|teknik sipil|perencanaan wilayah|interior|bangunan/iu',             // Desain Pemodelan dan Informasi Bangunan
    '1.5' => '/desain produk|interior|furnitur|mebel|kayu|kriya/iu',                          // Teknik Furnitur
    '2.1' => '/teknik mesin|manufaktur|mekatronika|perancangan mesin|teknik industri/iu',     // Teknik Mesin
    '2.2' => '/otomotif|teknik mesin|alat berat/iu',                                          // Teknik Otomotif
    '2.3' => '/teknik mesin|metalurgi|material|manufaktur|fabrikasi|pengelasan/iu',           // Teknik Pengelasan dan Fabrikasi Logam
    '2.4' => '/logistik|rantai pasok|transportasi/iu',                                        // Teknik Logistik
    '2.5' => '/elektronika|teknik elektro|instrumentasi|otomasi|mekatronika|robotika/iu',     // Teknik Elektronika
    '2.6' => '/penerbangan|dirgantara|aeronautika|pesawat/iu',                                // Teknik Pesawat Udara
    '2.7' => '/perkapalan|kapal|teknik kelautan/iu',                                          // Teknik Konstruksi Kapal
    '2.8' => '/kimia|analis|farmasi|bioteknologi|bio teknologi|mikrobiologi/iu',                                                      // Kimia Analisis
    '2.9' => '/teknik kimia|kimia|teknologi proses/iu',                                       // Teknik Kimia Industri
    '2.10' => '/tekstil|garmen/iu',                                                           // Teknik Tekstil
    '3.1' => '/teknik elektro|listrik|energi/iu',                                             // Teknik Ketenagalistrikan
    '3.2' => '/energi|teknik elektro|teknik fisika/iu',                                       // Teknik Energi Terbarukan
    '3.3' => '/geodesi|geomatika|geografi|penginderaan jauh|survei|geospasial/iu',            // Teknik Geospasial
    '3.4' => '/geologi|pertambangan|tambang/iu',                                              // Teknik Geologi Pertambangan
    '3.5' => '/perminyakan|minyak|migas|teknik kimia/iu',                                     // Teknik Perminyakan
    '4.1' => '/informatika|ilmu komputer|sistem informasi|perangkat lunak|teknologi informasi|gim|game|sains data/iu', // PPLG
    '4.2' => '/teknik komputer|jaringan|telekomunikasi|informatika|teknologi informasi/iu',   // TJKT
    '5.1' => '/keperawatan|kebidanan|kesehatan|gizi|fisioterapi/iu',                          // Layanan Kesehatan
    '5.2' => '/laboratorium medi|analis kesehatan|teknologi laboratorium|analis medis|bioteknologi|mikrobiologi/iu',    // Teknik Laboratorium Medik
    '5.3' => '/farmasi/iu',                                                                   // Teknologi Farmasi
    '5.4' => '/pekerjaan sosial|kesejahteraan sosial|rehabilitasi sosial|pemberdayaan|pembangunan sosial/iu', // Pekerjaan Sosial
    '6.1' => '/agroteknologi|agronomi|agribisnis|(?<!hasil )(?<!industri )(?<!teknologi )pertanian|hortikultura|perkebunan|proteksi tanaman/iu',       // Agribisnis Tanaman
    '6.2' => '/peternakan|ternak|veteriner/iu',                                               // Agribisnis Ternak
    '6.3' => '/perikanan|akuakultur|budidaya perairan/iu',                                    // Agribisnis Perikanan
    '6.4' => '/agribisnis|(?<!hasil )(?<!industri )(?<!teknologi )pertanian|agroteknologi/iu',                                        // Usaha Pertanian Terpadu
    '6.5' => '/pangan|hasil pertanian|industri pertanian/iu',                                 // Agriteknologi Pengolahan Hasil Pertanian
    '6.6' => '/kehutanan|hutan|konservasi/iu',                                                // Kehutanan
    '7.1' => '/permesinan perikanan|teknika|mesin kapal|perikanan tangkap/iu',                // Teknika Kapal Penangkap Ikan
    '7.2' => '/nautika|perikanan tangkap|pemanfaatan sumber ?daya perikanan/iu',              // Nautika Kapal Penangkap Ikan
    '7.3' => '/teknika|permesinan kapal|mesin kapal|sistem perkapalan/iu',                    // Teknika Kapal Niaga
    '7.4' => '/nautika|pelayaran|transportasi laut/iu',                                       // Nautika Kapal Niaga
    '8.1' => '/pemasaran|bisnis digital|manajemen|administrasi bisnis|kewirausahaan/iu',      // Pemasaran
    '8.2' => '/administrasi perkantoran|administrasi bisnis|manajemen|sekretari|administrasi publik/iu', // MPLB
    '8.3' => '/akuntansi|keuangan|perbankan|perpajakan/iu',                                   // Akuntansi dan Keuangan Lembaga
    '9.1' => '/pariwisata|destinasi|perjalanan|wisata/iu',                                    // Usaha Layanan Pariwisata
    '9.2' => '/perhotelan|hospitality|hotel/iu',                                              // Perhotelan
    '9.3' => '/boga|kuliner|gastronomi|pangan/iu',                                            // Kuliner
    '9.4' => '/kecantikan|tata rias|kosmetik|spa/iu',                                         // Kecantikan dan Spa
    '10.1' => '/seni rupa|seni murni|lukis|patung/iu',                                        // Seni Rupa
    '10.2' => '/desain komunikasi visual|dkv|desain grafis|multimedia|fotografi/iu',                    // Desain Komunikasi Visual
    '10.3' => '/kriya|desain produk/iu',                                                      // Desain dan Produksi Kriya
    '10.4' => '/tari|musik|teater|karawitan|pertunjukan|pedalangan|seni drama/iu',            // Seni Pertunjukan
    '10.5' => '/film|televisi|broadcasting|penyiaran|jurnalistik|sinemat|fotografi|audio/iu',                         // Broadcasting dan Perfilman
    '10.6' => '/animasi|film|desain komunikasi visual/iu',                                    // Animasi
    '10.7' => '/busana|mode|fashion|tekstil/iu',                                              // Busana
];
