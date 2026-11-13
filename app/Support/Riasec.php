<?php

namespace App\Support;

/**
 * Konstanta dan rumus RIASEC ArahDiri (sama dengan v14).
 *
 * - Skor tes: jumlah jawaban per dimensi dibagi skor maksimum dimensi (R,I,A,S,E = 40; C = 50).
 * - Kode Holland: 3 dimensi tertinggi, seri dipecah dengan urutan R-I-A-S-E-C.
 * - Kecocokan: 0,5 x kosinus + 0,5 x (tumpang tindih 3 teratas berbobot 3,2,1) / 14.
 * - Profil katalog memakai skala O*NET 1-7, dinormalkan (v-1)/6.
 */
final class Riasec
{
    public const ORDER = ['r', 'i', 'a', 's', 'e', 'c'];

    /** @return array<string, string> */
    public static function decimalCasts(): array
    {
        $out = [];
        foreach (self::ORDER as $d) {
            $out['riasec_'.$d] = 'float';
        }

        return $out;
    }

    /**
     * Urutan dimensi dari tertinggi, sama dengan v14:
     * nilai ternormalisasi turun, lalu skor mentah turun, lalu urutan R-I-A-S-E-C.
     *
     * @param array<string, float> $norm
     * @param array<string, int> $raw
     * @return list<string> huruf kecil
     */
    public static function rank(array $norm, array $raw = []): array
    {
        $dims = self::ORDER;
        usort($dims, fn ($a, $b) => (($norm[$b] ?? 0) <=> ($norm[$a] ?? 0))
            ?: (($raw[$b] ?? 0) <=> ($raw[$a] ?? 0))
            ?: (array_search($a, self::ORDER) <=> array_search($b, self::ORDER)));

        return $dims;
    }

    /** @param array<string, float> $v @return string 3 huruf besar */
    public static function hollandCode(array $v, array $raw = []): string
    {
        return strtoupper(implode('', array_slice(self::rank($v, $raw), 0, 3)));
    }

    /** Skala O*NET 1-7 ke 0-1. */
    public static function normalizeOnet(?float $v): float
    {
        return $v === null ? 0.0 : max(0.0, min(1.0, ($v - 1) / 6));
    }

    /**
     * @param array<string, float> $user 0-1 per dimensi
     * @param array<string, float> $target 0-1 per dimensi
     */
    public static function match(array $user, array $target): float
    {
        $dot = $a = $b = 0.0;
        foreach (self::ORDER as $d) {
            $dot += ($user[$d] ?? 0) * ($target[$d] ?? 0);
            $a += ($user[$d] ?? 0) ** 2;
            $b += ($target[$d] ?? 0) ** 2;
        }
        if ($a <= 0 || $b <= 0) {
            return 0.0;
        }
        $rank = function (array $v): array {
            $dims = self::ORDER;
            usort($dims, fn ($x, $y) => (($v[$y] ?? 0) <=> ($v[$x] ?? 0)) ?: (array_search($x, self::ORDER) <=> array_search($y, self::ORDER)));

            return $dims;
        };
        $ru = $rank($user);
        $rt = $rank($target);
        $overlap = 0;
        foreach (self::ORDER as $d) {
            $iu = array_search($d, $ru);
            $it = array_search($d, $rt);
            $overlap += ($iu < 3 ? 3 - $iu : 0) * ($it < 3 ? 3 - $it : 0);
        }

        return 0.5 * $dot / sqrt($a * $b) + 0.5 * $overlap / 14;
    }
}
