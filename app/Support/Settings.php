<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Pengaturan aplikasi yang bisa diubah admin (tabel app_settings), dengan nilai bawaan dari config.
 * Dibaca sekali per permintaan.
 */
class Settings
{
    /** kunci => [label, bawaan] — hanya kunci di sini yang boleh disimpan. */
    public const RULES = [
        'recommend.exclude_religious' => 'Sembunyikan prodi dan profesi khusus agama tertentu',
        'recommend.exclude_lecturers' => 'Jangan jadikan profesi dosen sebagai cita-cita siswa SMP',
        'recommend.require_active_careers' => 'Hanya pakai karier yang berstatus aktif di katalog',
    ];

    private static ?array $cache = null;

    public static function defaults(): array
    {
        return [
            'recommend.exclude_religious' => (bool) config('arahdiri.recommend.exclude_religious', true),
            'recommend.exclude_lecturers' => true,
            'recommend.require_active_careers' => true,
        ];
    }

    public static function get(string $key): mixed
    {
        self::$cache ??= DB::table('app_settings')->pluck('value', 'key')->map(fn ($v) => json_decode($v, true))->all();

        return array_key_exists($key, self::$cache) ? self::$cache[$key] : (self::defaults()[$key] ?? null);
    }

    public static function set(string $key, mixed $value, ?int $userId): void
    {
        DB::table('app_settings')->updateOrInsert(['key' => $key], ['value' => json_encode($value), 'updated_by' => $userId, 'updated_at' => now(), 'created_at' => now()]);
        self::flush();
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
