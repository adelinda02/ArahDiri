<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Arah SMA (IPA/IPS/Bahasa). Sumber utama tabel sma_tracks (bisa disunting admin),
 * cadangan config/sma_tracks.php. Profil dikembalikan dalam skala 0–1.
 *
 * @return array<string, array{name: string, subjects: list<string>, profile: array<string, float>}>
 */
class SmaTracks
{
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        try {
            $rows = DB::table('sma_tracks')->orderByRaw("case code when 'ipa' then 1 when 'ips' then 2 when 'bahasa' then 3 else 4 end")->get();
        } catch (\Throwable) {
            $rows = collect(); // basis data belum siap (mis. uji unit) → config
        }
        if ($rows->isEmpty()) {
            return self::$cache = collect(config('sma_tracks'))->except('support')->all();
        }

        return self::$cache = $rows->mapWithKeys(fn ($r) => [$r->code => [
            'name' => $r->name,
            'subjects' => json_decode($r->subjects, true) ?: [],
            'profile' => collect(Riasec::ORDER)->mapWithKeys(fn ($d) => [$d => Riasec::normalizeOnet($r->{'riasec_'.$d} !== null ? (float) $r->{'riasec_'.$d} : null)])->all(),
        ]])->all();
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
