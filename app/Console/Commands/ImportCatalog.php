<?php

namespace App\Console\Commands;

use App\Models\SyncRun;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Memuat katalog CSV (hasil pipeline career-guidance-riasec) ke MySQL.
 * Aman diulang: memakai upsert per potongan 500 baris.
 */
class ImportCatalog extends Command
{
    protected $signature = 'catalog:import {--path= : Folder CSV (default config arahdiri.catalog_path)} {--only= : Tabel tertentu, dipisah koma}';

    protected $description = 'Impor katalog KBJI, prodi, kampus, dan SMK dari CSV ke database';

    private string $dir;

    /** Baris yang sudah disunting admin (edited_at terisi) tidak ditimpa impor ulang. tabel => [kolom kunci, nilai terkunci] */
    private array $locked = [];

    public function handle(): int
    {
        $this->dir = rtrim($this->option('path') ?: config('arahdiri.catalog_path'), '/\\');
        $only = array_filter(explode(',', (string) $this->option('only')));
        $run = SyncRun::create(['kind' => 'catalog', 'started_at' => now()]);
        $this->loadLocks();
        $total = 0;

        $steps = [
            'kbji_jobs' => fn () => $this->kbjiJobs(),
            'kbji_onet_links' => fn () => $this->simple('kbji_onet_links.csv', 'kbji_onet_links', ['kbji_code', 'onet_code'], ['relation', 'status']),
            'campuses' => fn () => $this->campuses(),
            'program_families' => fn () => $this->families(),
            'program_family_jobs' => fn () => $this->familyJobs(),
            'program_offerings' => fn () => $this->offerings(),
            'smk_programs' => fn () => $this->smkPrograms(),
            'smk_concentrations' => fn () => $this->smkConcentrations(),
            'smk_program_jobs' => fn () => $this->simple('smk_program_jobs.csv', 'smk_program_jobs', ['smk_program_code', 'kbji_code'], ['rank']),
        ];

        try {
            foreach ($steps as $name => $step) {
                if ($only && ! in_array($name, $only, true)) {
                    continue;
                }
                $n = $step();
                $total += $n;
                $this->line(sprintf('  %-22s %6d baris', $name, $n));
            }
        } catch (\Throwable $e) {
            $run->update(['status' => 'failed', 'finished_at' => now(), 'rows_in' => $total, 'log' => mb_substr($e->getMessage(), 0, 2000)]);
            $this->error(mb_substr($e->getMessage(), 0, 600));

            return self::FAILURE;
        }

        $run->update(['status' => 'ok', 'finished_at' => now(), 'rows_in' => $total, 'rows_matched' => $total]);
        $this->info("Selesai: {$total} baris.");

        return self::SUCCESS;
    }

    /** @return \Generator<array<string, string>> */
    private function rows(string $file): \Generator
    {
        $path = $this->dir.DIRECTORY_SEPARATOR.$file;
        if (! is_file($path)) {
            throw new RuntimeException("CSV tidak ditemukan: {$path}");
        }
        $h = fopen($path, 'r');
        $head = fgetcsv($h, escape: '');
        $head[0] = preg_replace('/^\xEF\xBB\xBF/', '', $head[0]); // buang BOM
        while (($r = fgetcsv($h, escape: '')) !== false) {
            if ($r === [null] || count($r) !== count($head)) {
                continue;
            }
            yield array_combine($head, $r);
        }
        fclose($h);
    }

    private function loadLocks(): void
    {
        $edited = fn (string $t, string $col) => array_flip(DB::table($t)->whereNotNull('edited_at')->pluck($col)->map(fn ($v) => (string) $v)->all());
        $families = $edited('program_families', 'id');
        $this->locked = [
            'kbji_jobs' => ['code', $edited('kbji_jobs', 'code')],
            'program_families' => ['key', $edited('program_families', 'key')],
            'program_family_jobs' => ['program_family_id', $families], // relasi karier prodi yang disunting juga dikunci
            'smk_programs' => ['code', $edited('smk_programs', 'code')],
        ];
    }

    private function upsert(string $table, iterable $rows, array $unique, array $update): int
    {
        $n = 0;
        $buf = [];
        [$lockCol, $lockKeys] = $this->locked[$table] ?? [null, []];
        foreach ($rows as $row) {
            if ($lockCol && isset($lockKeys[(string) $row[$lockCol]])) {
                continue;
            }
            $buf[] = $row;
            if (count($buf) === 500) {
                DB::table($table)->upsert($buf, $unique, $update);
                $n += 500;
                $buf = [];
            }
        }
        if ($buf) {
            DB::table($table)->upsert($buf, $unique, $update);
            $n += count($buf);
        }

        return $n;
    }

    private function simple(string $file, string $table, array $unique, array $update): int
    {
        $rows = (function () use ($file, $unique, $update) {
            foreach ($this->rows($file) as $r) {
                yield array_intersect_key($r, array_flip([...$unique, ...$update]));
            }
        })();

        return $this->upsert($table, $rows, $unique, $update);
    }

    private static function num(?string $v): ?float
    {
        return $v === null || $v === '' ? null : (float) $v;
    }

    private static function int(?string $v): ?int
    {
        return $v === null || $v === '' ? null : (int) $v;
    }

    private static function str(?string $v): ?string
    {
        $v = trim((string) $v);

        return $v === '' ? null : $v;
    }

    private function riasec(array $r): array
    {
        $out = [];
        foreach (['r', 'i', 'a', 's', 'e', 'c'] as $d) {
            $out['riasec_'.$d] = self::num($r['riasec_'.$d] ?? null);
        }
        $out['holland_code'] = self::str($r['holland_code'] ?? null);

        return $out;
    }

    private const RIASEC_COLS = ['riasec_r', 'riasec_i', 'riasec_a', 'riasec_s', 'riasec_e', 'riasec_c', 'holland_code'];

    private function kbjiJobs(): int
    {
        $now = now();
        $rows = (function () use ($now) {
            foreach ($this->rows('kbji_jobs.csv') as $r) {
                yield [
                    'code' => $r['code'], 'title' => $r['title'], 'official_title' => $r['official_title'],
                    'subgroup_code' => $r['subgroup_code'], 'subgroup_title' => self::str($r['subgroup_title']),
                    'major_group' => $r['major_group'], 'description' => self::str($r['description']),
                    'print_page' => self::int($r['print_page']), 'pdf_page' => self::int($r['pdf_page']),
                    ...$this->riasec($r),
                    'mapping_status' => self::str($r['mapping_status']) ?? 'unmapped',
                    'job_zone_min' => self::int($r['job_zone_min']), 'job_zone_max' => self::int($r['job_zone_max']),
                    'created_at' => $now, 'updated_at' => $now,
                ];
            }
        })();

        return $this->upsert('kbji_jobs', $rows, ['code'], ['title', 'official_title', 'subgroup_code', 'subgroup_title', 'major_group', 'description', 'print_page', 'pdf_page', ...self::RIASEC_COLS, 'mapping_status', 'job_zone_min', 'job_zone_max', 'updated_at']);
    }

    private function campuses(): int
    {
        $now = now();
        $rows = (function () use ($now) {
            foreach ($this->rows('campuses.csv') as $r) {
                yield [
                    'pt_code' => str_pad($r['pt_code'], 6, '0', STR_PAD_LEFT), 'name' => $r['name'],
                    'group' => self::str($r['group']), 'ownership' => self::str($r['ownership']), 'form' => self::str($r['form']),
                    'supervisor' => self::str($r['supervisor']), 'province' => self::str($r['province']), 'city' => self::str($r['city']),
                    'website' => self::str($r['website']), 'is_state' => (int) $r['is_state'], 'created_at' => $now, 'updated_at' => $now,
                ];
            }
        })();

        return $this->upsert('campuses', $rows, ['pt_code'], ['name', 'group', 'ownership', 'form', 'supervisor', 'province', 'city', 'website', 'is_state', 'updated_at']);
    }

    private function families(): int
    {
        $now = now();
        $rows = (function () use ($now) {
            foreach ($this->rows('program_families.csv') as $r) {
                yield [
                    'key' => $r['key'], 'name' => $r['name'], 'level' => $r['level'], 'field' => self::str($r['field']),
                    'is_teacher_education' => (int) $r['is_teacher_education'], ...$this->riasec($r),
                    'mapping_method' => self::str($r['mapping_method']), 'created_at' => $now, 'updated_at' => $now,
                ];
            }
        })();

        return $this->upsert('program_families', $rows, ['key'], ['name', 'level', 'field', 'is_teacher_education', ...self::RIASEC_COLS, 'mapping_method', 'updated_at']);
    }

    private function familyIds(): array
    {
        return DB::table('program_families')->pluck('id', 'key')->all();
    }

    private function familyJobs(): int
    {
        $ids = $this->familyIds();
        $rows = (function () use ($ids) {
            foreach ($this->rows('program_family_jobs.csv') as $r) {
                if (isset($ids[$r['family_key']])) {
                    yield ['program_family_id' => $ids[$r['family_key']], 'kbji_code' => $r['kbji_code'], 'rank' => (int) $r['rank']];
                }
            }
        })();

        return $this->upsert('program_family_jobs', $rows, ['program_family_id', 'kbji_code'], ['rank']);
    }

    private function offerings(): int
    {
        $fam = $this->familyIds();
        $campus = DB::table('campuses')->pluck('id', 'pt_code')->all();
        $now = now();
        $rows = (function () use ($fam, $campus, $now) {
            foreach ($this->rows('program_offerings.csv') as $r) {
                $pt = str_pad($r['pt_code'], 6, '0', STR_PAD_LEFT);
                if (! isset($campus[$pt])) {
                    continue;
                }
                yield [
                    'campus_id' => $campus[$pt], 'program_family_id' => $fam[$r['family_key']] ?? null,
                    'program_code' => $r['program_code'], 'original_name' => $r['original_name'],
                    'level' => $r['level'], 'field' => self::str($r['field']), 'created_at' => $now, 'updated_at' => $now,
                ];
            }
        })();

        return $this->upsert('program_offerings', $rows, ['campus_id', 'program_code', 'level'], ['program_family_id', 'original_name', 'field', 'updated_at']);
    }

    private function smkPrograms(): int
    {
        $now = now();
        $rows = (function () use ($now) {
            foreach ($this->rows('smk_programs.csv') as $r) {
                yield [
                    'code' => $r['code'], 'name' => $r['name'], 'field_code' => $r['field_code'], 'field_name' => $r['field_name'],
                    ...$this->riasec($r), 'mapping_status' => self::str($r['mapping_status']) ?? 'draft_ai',
                    'created_at' => $now, 'updated_at' => $now,
                ];
            }
        })();

        return $this->upsert('smk_programs', $rows, ['code'], ['name', 'field_code', 'field_name', ...self::RIASEC_COLS, 'mapping_status', 'updated_at']);
    }

    private function smkConcentrations(): int
    {
        $rows = (function () {
            foreach ($this->rows('smk_concentrations.csv') as $r) {
                yield ['code' => $r['code'], 'smk_program_code' => $r['smk_program_code'], 'name' => $r['name'], 'duration_years' => (int) $r['duration'] ?: 3];
            }
        })();

        return $this->upsert('smk_concentrations', $rows, ['code'], ['smk_program_code', 'name', 'duration_years']);
    }
}
