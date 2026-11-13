<?php

namespace App\Support;

/**
 * Peta program SMK → prodi lanjutan. Kata kunci disunting admin (smk_programs.next_keywords / next_exclude,
 * satu per baris); bila belum disunting, memakai pola draf config/smk_lanjutan.php.
 */
class SmkPaths
{
    public static function matches(object $smk, string $programName): bool
    {
        if ($smk->next_keywords ?? null) {
            $has = fn (?string $list) => collect(preg_split('/[\r\n,]+/', (string) $list))->map(fn ($k) => mb_strtolower(trim($k)))->filter()
                ->contains(fn ($k) => str_contains(mb_strtolower($programName), $k));

            return $has($smk->next_keywords) && ! $has($smk->next_exclude ?? '');
        }
        $pattern = config('smk_lanjutan')[$smk->code] ?? null; // kode "5.3" bukan notasi titik config

        return $pattern ? (bool) preg_match($pattern, $programName) : false;
    }

    /** Kata kunci bawaan (dari pola config) untuk ditampilkan di editor. @return array{0: string, 1: string} */
    public static function defaultKeywords(string $code): array
    {
        $pattern = config('smk_lanjutan')[$code] ?? '';
        $body = preg_replace('~^/|/[a-z]*$~', '', $pattern);
        $keep = $drop = [];
        foreach (explode('|', $body) as $alt) {
            // (?<!hasil )(?<!industri )pertanian → kata kunci "pertanian" + pengecualian "hasil pertanian", dst.
            if (preg_match_all('/\(\?<!([^)]+)\)/', $alt, $m)) {
                $word = preg_replace('/\(\?<![^)]+\)/', '', $alt);
                foreach ($m[1] as $prefix) {
                    $drop[] = $prefix.$word;
                }
                $alt = $word;
            }
            $keep[] = str_replace([' ?', '\\'], [' ', ''], $alt);
        }

        return [implode("\n", array_filter($keep)), implode("\n", $drop)];
    }
}
