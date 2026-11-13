<?php

namespace App\Support;

/**
 * Target pengembangan diri per jenjang dan profil minat (port getPersonalizedDevTargets v14).
 */
final class DevTargets
{
    /** @param list<string> $rank huruf besar, urutan dimensi dari tertinggi @param array<string,float> $norm */
    public static function for(string $level, array $rank, array $norm): array
    {
        $rank = $rank ?: ['I', 'A', 'S', 'E', 'C', 'R'];
        $top3 = array_slice($rank, 0, 3);
        $t1 = $top3[0];
        $low2 = array_slice($rank, -2);
        $s = $norm['S'] ?? 0.5;
        $e = $norm['E'] ?? 0.5;
        $j = match ($level) { 'smp' => 'smp', 'mhs_umb' => 'mhs', default => 'sma' };
        $t = [];
        $add = function ($id, $cat, $text, $hint) use (&$t) {
            $t[] = ['id' => $id, 'cat' => $cat, 'text' => $text, 'hint' => $hint];
        };

        $acad = [
            'I' => ['akd-riset-analisis', 'Perdalam riset, analisis data mandiri, dan literatur ilmiah di bidang minat', 'Sesuai potensi minat Investigatif'],
            'A' => ['akd-eksplorasi-kreatif', 'Eksplorasi wawasan teori estetika visual, studi kasus desain, dan referensi karya baru', 'Sesuai potensi minat Artistik'],
            'R' => ['akd-teknis-terapan', 'Tingkatkan pemahaman teknis terapan, eksperimen praktikum, dan problem solving sains', 'Sesuai potensi minat Realistis'],
            'E' => ['akd-bisnis-strategi', 'Pelajari dasar analisis ekonomi-bisnis, strategi pertumbuhan pasar, dan inovasi industri', 'Sesuai potensi minat Enterprising'],
            'S' => ['akd-sosial-humaniora', 'Perdalam pemahaman ilmu psikologi sosial, komunikasi publik, dan dinamika interaksi manusia', 'Sesuai potensi minat Sosial'],
            'C' => ['akd-struktur-data', 'Tingkatkan ketelitian analisis sistematis, pengolahan spreadsheet data, dan standar operasional', 'Sesuai potensi minat Konvensional'],
        ][$t1];
        $add($acad[0], 'akademik', $acad[1], $acad[2]);
        match ($j) {
            'mhs' => $add('akd-jurnal-inti', 'akademik', 'Eksplorasi mata kuliah peminatan inti dan ikuti seminar/webinar bidang keahlian', 'Target akademik jenjang Mahasiswa'),
            'sma' => $add('akd-mapel-kuliah', 'akademik', 'Pertajam penguasaan mata pelajaran kunci yang disyaratkan oleh program studi impian', 'Target persiapan studi SMA/SMK'),
            default => $add('akd-fondasi-dasar', 'akademik', 'Perkuat fondasi matematika, bahasa, dan sains dasar untuk persiapan pemilihan jurusan', 'Target persiapan studi SMP'),
        };

        if ($s < 0.65 || in_array('S', $low2, true) || ($e < 0.65 && in_array('E', $low2, true))) {
            $add('sft-latihan-komunikasi', 'softskill', 'Melatih kemampuan komunikasi aktif, public speaking, dan presentasi gagasan', 'Target penguatan kemampuan komunikasi');
        } else {
            $add('sft-kolaborasi-tim', 'softskill', 'Meningkatkan kerja sama tim, empati aktif mendengarkan, dan koordinasi kelompok', 'Target penguatan kolaborasi tim');
        }
        match (true) {
            in_array('E', $top3, true) => $add('sft-kepemimpinan', 'softskill', 'Mengembangkan kepemimpinan persuasif, delegasi tugas, dan inisiatif mengambil keputusan', 'Sesuai kecenderungan Enterprising'),
            in_array('I', $top3, true) || in_array('R', $top3, true) => $add('sft-problem-solving', 'softskill', 'Meningkatkan kemampuan problem solving analitis melalui pembedahan solusi terstruktur', 'Sesuai potensi analitis & pemecahan masalah'),
            in_array('A', $top3, true) => $add('sft-kreativitas', 'softskill', 'Melatih kemampuan berpikir kreatif, ideasi orisinal, dan adaptabilitas terhadap umpan balik', 'Sesuai kecenderungan Artistik'),
            default => $add('sft-manajemen-waktu', 'softskill', 'Meningkatkan kedisiplinan manajemen waktu, prioritisasi jadwal, dan ketelitian kerja', 'Sesuai potensi manajemen keteraturan'),
        };

        match (true) {
            in_array('A', $top3, true) => $add('kar-portofolio-kreatif', 'karier', 'Membangun pengalaman dan portofolio karya kreatif berbasis proyek nyata', 'Rekomendasi kecenderungan Artistik'),
            in_array('E', $top3, true) => $add('kar-kewirausahaan', 'karier', 'Merancang inisiatif proyek rintisan / kewirausahaan mandiri atau kegiatan organisasi', 'Rekomendasi kecenderungan Enterprising'),
            in_array('I', $top3, true) => $add('kar-pelatihan-sertifikasi', 'karier', 'Mengikuti pelatihan atau sertifikasi kompetensi riset, tools data, dan teknologi', 'Rekomendasi minat Investigatif'),
            default => $add('kar-pelatihan-keahlian', 'karier', 'Mengikuti pelatihan keterampilan teknis dan sertifikasi keahlian terapan', 'Rekomendasi kesiapan karier'),
        };
        match ($j) {
            'mhs' => $add('kar-cv-magang', 'karier', 'Mempersiapkan CV profesional, portofolio digital, dan rencana lamaran magang industri', 'Persiapan karier Mahasiswa'),
            'sma' => $add('kar-eksplorasi-bidang', 'karier', 'Mengenali bidang pekerjaan yang diminati serta berdiskusi dengan mentor atau praktisi', 'Eksplorasi karier SMA/SMK'),
            default => $add('kar-eksplorasi-dunia-kerja', 'karier', 'Mengenali bidang pekerjaan yang diminati dan memahami prospek pendidikan lanjutannya', 'Pengenalan karier SMP'),
        };

        return $t;
    }
}
