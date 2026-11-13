<?php

namespace App\Console\Commands;

use App\Models\SyncRun;
use App\Services\Banpt\BanptDirectory;
use App\Support\AccreditationRating;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Sinkron akreditasi prodi & kampus dari Direktori BAN-PT (termasuk SK LAM).
 *
 *   php artisan banpt:sync            ambil dari BAN-PT lalu simpan
 *   php artisan banpt:sync --export   hanya unduh JSON mentah ke storage/app/private/banpt (mis. di laptop)
 *   php artisan banpt:sync --import   pakai JSON yang sudah ada di folder itu (mis. di hosting)
 *
 * robots.txt BAN-PT melarang akses otomatis ke /direktori/. Jalankan paling sering seminggu sekali,
 * dan minta izin/data resmi BAN-PT sebelum rilis publik.
 */
class BanptSync extends Command
{
    protected $signature = 'banpt:sync {--export : Hanya unduh JSON mentah} {--import : Pakai JSON mentah yang sudah diunduh}
        {--prodi-file= : Path JSON prodi} {--institusi-file= : Path JSON institusi}';

    protected $description = 'Sinkron akreditasi prodi dan kampus dari Direktori BAN-PT';

    private const DIR = 'banpt';

    public function handle(BanptDirectory $dir): int
    {
        // Direktori prodi ±37 ribu baris; beri ruang memori bila hosting mengizinkan.
        if ($this->memoryLimitBytes() < 512 * 1024 * 1024) {
            @ini_set('memory_limit', '512M');
        }
        $run = SyncRun::create(['kind' => 'banpt', 'started_at' => now()]);
        $log = [];

        try {
            $raw = [];
            foreach (['prodi', 'institusi'] as $kind) {
                $file = $this->option($kind.'-file');
                if ($file) {
                    $raw[$kind] = file_get_contents($file) ?: throw new \RuntimeException("Tidak bisa membaca {$file}");
                } elseif ($this->option('import')) {
                    $raw[$kind] = Storage::disk('local')->get(self::DIR."/{$kind}.json") ?? throw new \RuntimeException("Belum ada ".self::DIR."/{$kind}.json; jalankan --export dulu");
                } else {
                    $this->line("Mengunduh direktori {$kind} BAN-PT…");
                    $raw[$kind] = $dir->download($kind);
                    Storage::disk('local')->put(self::DIR."/{$kind}.json", $raw[$kind]);
                }
            }

            if ($this->option('export')) {
                $run->update(['status' => 'ok', 'finished_at' => now(), 'log' => 'Ekspor JSON mentah saja.']);
                $this->info('JSON tersimpan di storage/app/private/'.self::DIR.'. Unggah lalu jalankan banpt:sync --import.');

                return self::SUCCESS;
            }

            $prodi = $dir->parse($raw['prodi'], 'prodi');
            $inst = $dir->parse($raw['institusi'], 'institusi');
            unset($raw);

            $stats = DB::transaction(fn () => $this->store($prodi, $inst, now()));
            $log[] = sprintf('Prodi: %d baris, %d cocok, %d tidak cocok.', count($prodi), $stats['prodi_matched'], $stats['prodi_unmatched']);
            $log[] = sprintf('Institusi: %d baris, %d cocok, %d tidak cocok.', count($inst), $stats['inst_matched'], $stats['inst_unmatched']);

            $run->update([
                'status' => 'ok', 'finished_at' => now(),
                'rows_in' => count($prodi) + count($inst),
                'rows_matched' => $stats['prodi_matched'] + $stats['inst_matched'],
                'log' => implode("\n", $log),
            ]);
            foreach ($log as $l) {
                $this->info($l);
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $run->update(['status' => 'failed', 'finished_at' => now(), 'log' => mb_substr($e->getMessage(), 0, 2000)]);
            $this->error(mb_substr($e->getMessage(), 0, 600));

            return self::FAILURE;
        }
    }

    /** @return array<string, int> */
    private function store(array $prodi, array $inst, \DateTimeInterface $fetchedAt): array
    {
        $today = CarbonImmutable::today();

        // Indeks kampus: nama ternormal → id (nama ganda dianggap ambigu).
        $campusByName = $campusPlace = [];
        foreach (DB::table('campuses')->get(['id', 'name', 'city', 'province']) as $c) {
            $campusByName[BanptDirectory::normName($c->name)][] = $c->id;
            $campusPlace[$c->id] = BanptDirectory::normName($c->city.' '.$c->province);
        }
        $names = array_keys($campusByName);
        // Kampus yang berganti nama: nama lama di BAN-PT → kode PT di katalog.
        $byCode = DB::table('campuses')->pluck('id', 'pt_code');
        $cache = [];
        foreach (config('banpt_aliases', []) as $alias => $code) {
            if (isset($byCode[$code])) {
                $cache[BanptDirectory::normCampus($alias)] = $byCode[$code];
            }
        }
        // Pasangan yang dibuat admin di panel "Akreditasi".
        foreach (DB::table('banpt_aliases')->get(['name_key', 'campus_id']) as $a) {
            $cache[$a->name_key] = $a->campus_id;
        }
        $campusId = function (?string $name) use (&$cache, $campusByName, $names, $campusPlace) {
            $k = BanptDirectory::normCampus($name);
            if (array_key_exists($k, $cache)) {
                return $cache[$k];
            }
            $ids = $campusByName[$k] ?? [];
            if (! $ids && $k !== '') {
                // Nama BAN-PT lebih pendek, mis. "Universitas Trunojoyo" → "Universitas Trunojoyo Madura" (harus unik).
                $hits = array_values(array_filter($names, fn ($n) => str_starts_with($n, $k.' ')));
                $ids = count($hits) === 1 ? $campusByName[$hits[0]] : [];
            }
            if (! $ids && $k !== '') {
                // Nama BAN-PT lebih panjang (tambahan kota/kabupaten), mis. "Universitas Sawerigading Makassar"
                // → "Universitas Sawerigading". Nama katalog minimal 2 kata dan sisanya paling banyak 2 kata.
                $hits = array_values(array_filter($names, fn ($n) => substr_count($n, ' ') >= 1 && str_starts_with($k, $n.' ')
                    && substr_count(substr($k, strlen($n) + 1), ' ') <= 1));
                usort($hits, fn ($a, $b) => strlen($b) <=> strlen($a));
                if ($hits && (count($hits) === 1 || strlen($hits[0]) > strlen($hits[1])) && count($campusByName[$hits[0]]) === 1) {
                    // Tambahan nama harus sesuai kota/provinsi kampus di katalog, supaya "STT Nasional Jambi"
                    // tidak tertukar dengan "STT Nasional" (Yogyakarta).
                    $id = $campusByName[$hits[0]][0];
                    $extra = substr($k, strlen($hits[0]) + 1);
                    $place = ' '.str_replace(' ', '', $campusPlace[$id] ?? '').' ';
                    if ($extra !== '' && str_contains($place, str_replace(' ', '', $extra))) {
                        $ids = [$id];
                    }
                }
            }
            if (! $ids && $k !== '' && substr_count($k, ' ') >= 1) {
                // Nama BAN-PT lebih pendek dan ada salah ketik, mis. "Universitas Trunojoyo" → "Universitas Trunodjoyo Madura".
                $n = substr_count($k, ' ') + 1;
                $hits = array_values(array_filter($names, fn ($c) => substr_count($c, ' ') + 1 > $n
                    && BanptDirectory::sameNameTypo($k, implode(' ', array_slice(explode(' ', $c), 0, $n)), 8)));
                $ids = count($hits) === 1 && count($campusByName[$hits[0]]) === 1 ? $campusByName[$hits[0]] : [];
            }
            if (! $ids && $k !== '') {
                // Salah ketik kecil, mis. "Syekh AIi Hasan" (huruf I besar) → "Syekh Ali Hasan".
                $first = strtok($k, ' ');
                $near = BanptDirectory::closest($k, array_filter($names, fn ($n) => str_starts_with($n, $first.' ')), 2, 20);
                $ids = $near && BanptDirectory::sameNameTypo($k, $near) ? $campusByName[$near] : [];
            }

            return $cache[$k] = count($ids) === 1 ? $ids[0] : null;
        };

        // Indeks prodi per kampus: campus_id|jenjang|nama ternormal → id offering (juga versi tanpa isi kurung).
        $offering = $offeringLoose = $offeringsByCampus = [];
        foreach (DB::table('program_offerings')->select(['id', 'campus_id', 'level', 'original_name'])->orderBy('id')->lazy(2000) as $o) {
            $offering[$o->campus_id.'|'.$o->level.'|'.BanptDirectory::normName($o->original_name)] = $o->id;
            $loose = BanptDirectory::normName($o->original_name, true);
            $offeringLoose[$o->campus_id.'|'.$o->level.'|'.$loose] ??= $o->id;
            $offeringsByCampus[$o->campus_id.'|'.$o->level][$loose] ??= $o->id;
        }
        // Cadangan prodi di kampus & jenjang yang sama: salah ketik kecil, atau beda awalan "Ilmu"
        // (mis. "Ilmu Hukum" ↔ "Hukum", "Teknik Informatika" ↔ "Informatika"). Hanya bila kandidatnya tunggal.
        $offeringFuzzy = function (int $cid, string $level, string $program) use ($offeringsByCampus): ?int {
            $pool = $offeringsByCampus[$cid.'|'.$level] ?? [];
            if (! $pool) {
                return null;
            }
            $name = BanptDirectory::normName($program, true);
            // "Ilmu Hukum" ↔ "Hukum" aman; "Teknik Kimia" ≠ "Kimia", jadi "teknik" hanya untuk Informatika.
            $strip = fn ($n) => preg_replace(['/^(ilmu|program studi|prodi) /u', '/^teknik informatika$/u'], ['', 'informatika'], $n);
            $hits = array_keys(array_filter($pool, fn ($id, $n) => $strip($n) === $strip($name), ARRAY_FILTER_USE_BOTH));
            if (count($hits) === 1) {
                return $pool[$hits[0]];
            }
            $near = BanptDirectory::closest($name, array_keys($pool), 2, 10);

            return $near ? $pool[$near] : null;
        };

        DB::table('accreditation_unmatched')->where('resolved', false)->delete();

        $best = ['campus' => [], 'program_offering' => []];
        $unmatched = [];
        $stats = ['prodi_matched' => 0, 'prodi_unmatched' => 0, 'inst_matched' => 0, 'inst_unmatched' => 0];

        foreach ($inst as $row) {
            $id = $campusId($row['nama_kampus']);
            if (! $id) {
                $stats['inst_unmatched']++;
                if (BanptDirectory::isActive($row, $today)) {
                    $unmatched[] = ['source' => 'institusi', 'campus_name' => $row['nama_kampus'], 'program_name' => null, 'level' => null, 'raw' => json_encode($row)];
                }
                continue;
            }
            $stats['inst_matched']++;
            $this->keepBest($best['campus'], $id, $row, $today);
        }

        foreach ($prodi as $row) {
            $level = BanptDirectory::normLevel($row['jenjang']);
            $cid = $campusId($row['nama_kampus']);
            $oid = $cid ? ($offering[$cid.'|'.$level.'|'.BanptDirectory::normName($row['program_studi'])]
                ?? $offeringLoose[$cid.'|'.$level.'|'.BanptDirectory::normName($row['program_studi'], true)]
                ?? $offeringFuzzy($cid, $level, $row['program_studi'])) : null;
            if (! $oid) {
                $stats['prodi_unmatched']++;
                // Hanya jenjang yang direkomendasikan yang dicatat untuk diperiksa admin.
                if (in_array($level, ['D3', 'D4', 'S1'], true) && BanptDirectory::isActive($row, $today)) {
                    $unmatched[] = ['source' => 'prodi', 'campus_name' => $row['nama_kampus'], 'program_name' => $row['program_studi'], 'level' => $level, 'raw' => json_encode($row)];
                }
                continue;
            }
            $stats['prodi_matched']++;
            $this->keepBest($best['program_offering'], $oid, $row, $today);
        }

        $now = now();
        $records = [];
        foreach ($best as $type => $rows) {
            foreach ($rows as $id => $r) {
                $records[] = [
                    'subject_type' => $type, 'subject_id' => $id,
                    'rating_raw' => mb_substr((string) $r['peringkat_akreditasi'], 0, 40),
                    'rating_score' => BanptDirectory::isActive($r, $today) ? AccreditationRating::score($r['peringkat_akreditasi']) : 0,
                    'decree_no' => mb_substr((string) $r['nomor_sk'], 0, 120) ?: null,
                    'decree_year' => is_numeric($r['tahun_sk']) ? (int) $r['tahun_sk'] : null,
                    'expires_at' => BanptDirectory::expiry($r['tanggal_kedaluwarsa'])?->toDateString(),
                    'body' => BanptDirectory::body($r['nomor_sk']),
                    'fetched_at' => $fetchedAt, 'created_at' => $now, 'updated_at' => $now,
                ];
            }
        }
        foreach (array_chunk($records, 500) as $chunk) {
            DB::table('accreditations')->upsert($chunk, ['subject_type', 'subject_id'], ['rating_raw', 'rating_score', 'decree_no', 'decree_year', 'expires_at', 'body', 'fetched_at', 'updated_at']);
        }
        // Subjek yang tidak muncul lagi di direktori dihapus.
        DB::table('accreditations')->where('fetched_at', '<', $fetchedAt)->delete();

        foreach (array_chunk($unmatched, 500) as $chunk) {
            DB::table('accreditation_unmatched')->insert(array_map(fn ($u) => $u + ['fetched_at' => $fetchedAt, 'resolved' => false, 'created_at' => $now, 'updated_at' => $now], $chunk));
        }

        return $stats;
    }

    private function memoryLimitBytes(): int
    {
        $v = trim((string) ini_get('memory_limit'));
        if ($v === '-1') {
            return PHP_INT_MAX;
        }
        $n = (int) $v;

        return match (strtolower(substr($v, -1))) {
            'g' => $n * 1024 ** 3, 'm' => $n * 1024 ** 2, 'k' => $n * 1024, default => $n,
        };
    }

    /** Satu baris terbaik per subjek: yang berlaku, peringkat tertinggi, berlaku paling lama. */
    private function keepBest(array &$bucket, int $id, array $row, CarbonImmutable $today): void
    {
        if (! isset($bucket[$id]) || BanptDirectory::rankKey($row, $today) > BanptDirectory::rankKey($bucket[$id], $today)) {
            $bucket[$id] = $row;
        }
    }
}
