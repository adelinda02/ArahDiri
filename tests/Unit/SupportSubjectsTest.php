<?php

namespace Tests\Unit;

use App\Support\SupportSubjects;
use Tests\TestCase;

/** Mapel pendukung prodi SNBP sesuai Lampiran Kepmendikdasmen 102/M/2025 (nomor = kelompok prodi lampiran). */
class SupportSubjectsTest extends TestCase
{
    private function assertSubjects(array $expected, string $program, ?string $field = null): void
    {
        $this->assertSame($expected, SupportSubjects::forProgram($program, $field), "Mapel pendukung untuk prodi {$program}");
    }

    public function test_program_names_map_to_snbp_subjects_and_tracks(): void
    {
        $this->assertSubjects(['Sosiologi', 'Matematika'], 'Psikologi');                    // 9
        $this->assertSubjects(['Matematika Tingkat Lanjut', 'Fisika'], 'Teknik Sipil');    // 33
        $this->assertSubjects(['Matematika Tingkat Lanjut', 'Fisika'], 'Sistem Telekomunikasi');
        $this->assertSubjects(['Matematika Tingkat Lanjut'], 'Pendidikan Matematika');
        $this->assertSubjects(['Biologi'], 'Manajemen Sumber Daya Perairan');
        $this->assertSubjects(['Ekonomi', 'Matematika'], 'Nama Baru', 'Ekonomi');

        $psi = SupportSubjects::tracksFor('Psikologi');
        $this->assertSame(2, $psi['ips']['strength']);
        $this->assertSame(1, $psi['ipa']['strength']); // Matematika umum ada di semua arah
        $this->assertSame([], SupportSubjects::tracksFor('Nama Tidak Dikenal'));
    }

    /** Kelompok yang berbeda dari peta lama (345/M/2022) dan sudah dibetulkan. */
    public function test_groups_corrected_for_102_m_2025(): void
    {
        // 11 Ilmu Kebumian: Fisika dan/atau Matematika Tingkat Lanjut (dulu: Biologi)
        $this->assertSubjects(['Fisika', 'Matematika Tingkat Lanjut'], 'Ilmu Kebumian');
        $this->assertSubjects(['Fisika', 'Matematika Tingkat Lanjut'], 'Meteorologi');
        $this->assertSubjects(['Fisika', 'Matematika Tingkat Lanjut'], 'Geofisika');
        $this->assertSubjects(['Fisika', 'Matematika Tingkat Lanjut'], 'Astronomi');       // 16

        // 12 Ilmu Kelautan: Biologi (dulu: Fisika + Matematika Tingkat Lanjut); Teknik Kelautan tetap kelompok Teknik
        $this->assertSubjects(['Biologi'], 'Ilmu Kelautan');
        $this->assertSubjects(['Biologi'], 'Oseanografi');
        $this->assertSubjects(['Matematika Tingkat Lanjut', 'Fisika'], 'Teknik Kelautan');

        // 8 Pertahanan: Pendidikan Pancasila (dulu: Sosiologi); 47 Ilmu Militer tetap Sosiologi
        $this->assertSubjects(['Pendidikan Pancasila'], 'Manajemen Pertahanan');
        $this->assertSubjects(['Pendidikan Pancasila'], 'Ilmu Pertahanan');
        $this->assertSubjects(['Sosiologi'], 'Ilmu Militer');

        // 23 Arsitektur: Matematika dan/atau Fisika (bukan Matematika Tingkat Lanjut)
        $this->assertSubjects(['Matematika', 'Fisika'], 'Arsitektur');
        $this->assertSubjects(['Matematika', 'Fisika'], 'Arsitektur Lanskap');

        // 52 Bioteknologi, Biokewirausahaan, Bioinformatika: Biologi dan/atau Matematika
        $this->assertSubjects(['Biologi', 'Matematika'], 'Bioteknologi');
        $this->assertSubjects(['Biologi', 'Matematika'], 'Bioinformatika');
        $this->assertSubjects(['Biologi', 'Matematika'], 'Biokewirausahaan');
        // 54 Informatika Medis: Biologi dan/atau Matematika Tingkat Lanjut
        $this->assertSubjects(['Biologi', 'Matematika Tingkat Lanjut'], 'Informatika Medis');

        // 3 Linguistik dan 4 Sastra: Bahasa Indonesia Tingkat Lanjut (nama mapel Kurikulum Merdeka)
        $this->assertSubjects(['Bahasa Indonesia Tingkat Lanjut', 'Bahasa Inggris'], 'Linguistik');
        $this->assertSubjects(['Bahasa Indonesia Tingkat Lanjut'], 'Sastra Indonesia');
        $this->assertSubjects(['Bahasa Inggris Tingkat Lanjut'], 'Sastra Inggris');
        $this->assertSubjects(['Bahasa Asing', 'Bahasa Indonesia Tingkat Lanjut'], 'Sastra Jepang');

        // 50 Pariwisata: Ekonomi dan/atau Bahasa Inggris Tingkat Lanjut
        $this->assertSubjects(['Ekonomi', 'Bahasa Inggris Tingkat Lanjut'], 'Pariwisata');
        $this->assertSubjects(['Ekonomi', 'Bahasa Inggris Tingkat Lanjut'], 'Manajemen Perhotelan');

        // 31 Komunikasi: Sosiologi dan/atau Antropologi; 59 Studi Humanitas: Antropologi dan/atau Sosiologi
        $this->assertSubjects(['Sosiologi', 'Antropologi'], 'Ilmu Komunikasi');
        $this->assertSubjects(['Antropologi', 'Sosiologi'], 'Studi Humanitas');

        // 7 Ekonomi: Ekonomi dan/atau Matematika; 26-30 Akuntansi/Manajemen/Bisnis: Ekonomi
        $this->assertSubjects(['Ekonomi', 'Matematika'], 'Ekonomi Pembangunan');
        $this->assertSubjects(['Ekonomi'], 'Akuntansi');
        $this->assertSubjects(['Ekonomi'], 'Administrasi Bisnis');

        // 58 Sains Perkopian: Biologi (dulu tidak dikenali)
        $this->assertSubjects(['Biologi'], 'Sains Perkopian');
    }

    /** Kelompok yang tidak berubah tetap benar. */
    public function test_unchanged_groups_still_match(): void
    {
        $this->assertSubjects(['Biologi', 'Kimia'], 'Kedokteran');               // 36
        $this->assertSubjects(['Biologi', 'Kimia'], 'Pendidikan Dokter');        // bukan kependidikan
        $this->assertSubjects(['Biologi', 'Kimia'], 'Farmasi');                  // 39
        $this->assertSubjects(['Kimia', 'Biologi'], 'Teknologi Pangan');         // 56
        $this->assertSubjects(['Biologi'], 'Keperawatan');                       // 43
        $this->assertSubjects(['PJOK', 'Biologi'], 'Ilmu Keolahragaan');         // 49
        $this->assertSubjects(['Matematika Tingkat Lanjut'], 'Informatika');      // 17
        $this->assertSubjects(['Matematika Tingkat Lanjut'], 'Sains Data');       // 57
        $this->assertSubjects(['Sosiologi', 'Pendidikan Pancasila'], 'Ilmu Hukum'); // 46
        $this->assertSubjects(['Geografi', 'Matematika'], 'Geografi');           // 53
        $this->assertSubjects(['Seni Budaya', 'Matematika'], 'Desain Komunikasi Visual'); // 25
        $this->assertSubjects(['Seni Budaya'], 'Seni Musik');                     // 1
        $this->assertSubjects(['Sejarah'], 'Ilmu Sejarah');                       // 2
        $this->assertSubjects(['Kimia', 'Matematika Tingkat Lanjut'], 'Teknik Kimia'); // 33
        $this->assertSubjects(['Fisika'], 'Fisika');                              // 15
    }

    /** 32 Pendidikan: paling banyak 1 mapel yang relevan dengan prodi kependidikannya. */
    public function test_education_programs_use_at_most_one_subject(): void
    {
        $this->assertSubjects(['Bahasa Inggris Tingkat Lanjut'], 'Pendidikan Bahasa Inggris');
        $this->assertSubjects(['Ekonomi'], 'Pendidikan Ekonomi');
        $this->assertSubjects(['Biologi'], 'Pendidikan Biologi');
        $this->assertSubjects(['PJOK'], 'Pendidikan Jasmani, Kesehatan, dan Rekreasi');
        $this->assertSubjects(['Bahasa Asing'], 'Pendidikan Bahasa Arab');
        $this->assertSubjects(['Pendidikan Pancasila'], 'Pendidikan Pancasila dan Kewarganegaraan');
        $this->assertSubjects(['Matematika Tingkat Lanjut'], 'Pendidikan Teknik Mesin');
    }

    /** Suntingan admin yang masih memakai nama mapel lama diterjemahkan ke nama 102/M/2025. */
    public function test_old_subject_names_in_admin_overrides_are_translated(): void
    {
        $this->assertSame(['Bahasa Indonesia Tingkat Lanjut', 'Bahasa Inggris'], SupportSubjects::forProgram('Apa Saja', null, ['Bahasa Indonesia', 'Bahasa Inggris']));
        $this->assertSame(['Bahasa Indonesia Tingkat Lanjut'], SupportSubjects::overrideOf((object) ['support_subjects' => json_encode(['Bahasa Indonesia'])]));
        foreach (SupportSubjects::options() as $m) {
            $this->assertArrayNotHasKey($m, config('mapel_pendukung.aliases')); // nama lama tidak lagi ditawarkan
        }
        // Semua mapel di aturan dikenal oleh peta arah SMA.
        foreach (config('mapel_pendukung.rules') as [, $subjects]) {
            foreach ($subjects as $m) {
                $this->assertArrayHasKey($m, config('mapel_pendukung.tracks'), "Mapel {$m} tidak punya arah SMA");
            }
        }
    }
}
