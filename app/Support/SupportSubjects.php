<?php

namespace App\Support;

/**
 * Mapel pendukung prodi (config/mapel_pendukung.php) dan arah SMA yang menyiapkannya.
 */
class SupportSubjects
{
    /** Pilihan mapel yang dikenal (untuk penyuntingan admin). @return list<string> */
    public static function options(): array
    {
        return array_keys(config('mapel_pendukung.tracks'));
    }

    /**
     * @param  list<string>|null  $override  mapel yang ditetapkan admin untuk prodi ini (mengalahkan aturan pola nama)
     * @return list<string> mapel pendukung; [] bila tidak diketahui
     */
    public static function forProgram(string $name, ?string $field = null, ?array $override = null): array
    {
        $cfg = config('mapel_pendukung');
        if ($override) {
            // Nama mapel lama yang tersimpan di suntingan admin diterjemahkan ke nama 102/M/2025.
            return array_values(array_unique(array_map(fn ($m) => $cfg['aliases'][$m] ?? $m, $override)));
        }
        $name = trim($name);
        $stripped = preg_replace($cfg['education_prefix'], '', $name);
        $education = $stripped !== $name;

        foreach (array_unique([$stripped, $name]) as $candidate) {
            foreach ($cfg['rules'] as [$pattern, $subjects]) {
                if (preg_match($pattern, $candidate)) {
                    // Kelompok Pendidikan (no. 32): paling banyak 1 mapel yang relevan dengan bidang yang diajarkan.
                    return $education ? array_slice($subjects, 0, $cfg['education_max'] ?? 1) : $subjects;
                }
            }
        }

        $fallback = $cfg['by_field'][$field] ?? [];

        return $education ? array_slice($fallback, 0, $cfg['education_max'] ?? 1) : $fallback;
    }

    /** Override admin dari kolom program_families.support_subjects (JSON). */
    public static function overrideOf(object $family): ?array
    {
        $v = $family->support_subjects ?? null;

        $list = $v ? (is_array($v) ? $v : json_decode($v, true)) : null;
        $aliases = config('mapel_pendukung.aliases', []);

        return $list ? array_values(array_unique(array_map(fn ($m) => $aliases[$m] ?? $m, $list))) : null;
    }

    /**
     * Arah SMA yang cocok untuk satu prodi.
     *
     * @return array<string, array{strength: int, subjects: list<string>}> kode arah → kekuatan (2 = mapel khas arah itu,
     *                                                                        1 = hanya mapel umum) dan mapel yang menyambungkan
     */
    public static function tracksFor(string $name, ?string $field = null, ?array $override = null): array
    {
        $subjects = self::forProgram($name, $field, $override);
        $map = config('mapel_pendukung.tracks');
        $out = [];
        $general = [];
        foreach ($subjects as $m) {
            $tracks = $map[$m] ?? [];
            if (! $tracks) {
                $general[] = $m;
            }
            foreach ($tracks as $t) {
                $out[$t]['strength'] = 2;
                $out[$t]['subjects'][] = $m;
            }
        }
        // Mapel umum (Matematika wajib, Pancasila, PJOK) ada di semua arah, tapi lebih lemah.
        if ($general) {
            foreach (array_keys(SmaTracks::all()) as $t) {
                $out[$t] ??= ['strength' => 1, 'subjects' => $general];
            }
        }

        return $out;
    }
}
