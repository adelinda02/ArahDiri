<?php

namespace App\Support;

/**
 * Urutan peringkat akreditasi untuk ranking ArahDiri (aturan aplikasi, bukan skor resmi BAN-PT).
 * 0 = Tidak Terakreditasi, tidak pernah direkomendasikan.
 */
final class AccreditationRating
{
    private const MAP = [
        'unggul' => 6, 'terakreditasi unggul' => 6, 'a' => 6,
        'baik sekali' => 5, 'b' => 5,
        'baik' => 4, 'c' => 4,
        'terakreditasi' => 3,
        'terakreditasi pertama' => 2,
        'terakreditasi sementara' => 1,
        'tidak terakreditasi' => 0,
    ];

    public static function score(?string $raw): int
    {
        $k = mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $raw)));

        return self::MAP[$k] ?? 0;
    }
}
