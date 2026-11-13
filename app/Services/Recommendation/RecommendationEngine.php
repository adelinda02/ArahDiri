<?php

namespace App\Services\Recommendation;

use App\Models\Campus;
use App\Models\Recommendation;
use App\Models\TestResult;
use App\Models\TestSession;
use App\Models\User;
use App\Support\Riasec;
use App\Support\Settings;
use App\Support\SmaTracks;
use App\Support\SmkPaths;
use App\Support\SupportSubjects;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Mesin rekomendasi ArahDiri v2 (spesifikasi bagian 3–6).
 * Semua jenjang memakai Riasec::match(); yang berbeda kandidat dan cara memilih kampus.
 * Hasil disimpan per sesi tes sebagai salinan (snapshot), termasuk akreditasi saat itu.
 */
class RecommendationEngine
{
    public const VERSION = 'rec-v4';

    /** Jenjang prodi yang direkomendasikan untuk siswa sekolah, urutan juga dipakai pemecah seri. */
    private const SCHOOL_LEVELS = ['S1', 'D4', 'D3'];

    public function __construct(private CampusPicker $campuses) {}

    public function generate(TestSession $session, TestResult $result): void
    {
        $user = $this->userVector($result);

        DB::transaction(function () use ($session, $user) {
            $session->recommendations()->delete(); // cascade ke recommendation_campuses & umb_promotions
            DB::table('umb_promotions')->where('test_session_id', $session->id)->delete();

            match ($session->level_at_test) {
                User::LEVEL_SMP => $this->forSmp($session, $user),
                User::LEVEL_MHS_UMB => $this->forUmbStudent($session, $user),
                default => $this->forSmaSmk($session, $user),
            };
        });
    }

    // ------------------------------------------------------------------ jenjang

    /**
     * SMP (rec-v3): jalur per profesi, dihitung mundur dari cita-cita.
     *  1) 3 profesi KBJI paling cocok dengan minat (kelompok jabatan berbeda, harus punya prodi lanjutan);
     *  2) tiap profesi → 1–2 prodi S1/D4 yang mengarah ke profesi itu (program_family_jobs);
     *  3) prodi → arah SMA lewat mapel pendukung SNBP (config/mapel_pendukung.php);
     *  4) profesi/prodi → program SMK: jabatan KBJI sama, lalu peta config/smk_lanjutan.php, lalu minat.
     * Tidak memilih kampus: kampus baru relevan di jenjang SMA/SMK.
     */
    private function forSmp(TestSession $s, array $u): void
    {
        $userTop = array_slice(array_map('strtoupper', Riasec::rank($u)), 0, 3);
        $families = $this->rankFamilies($u, ['S1', 'D4'])->keyBy(fn ($r) => $r['f']->id);
        if ($families->isEmpty()) {
            return;
        }
        $links = DB::table('program_family_jobs')->whereIn('program_family_id', $families->keys())->get()->groupBy('kbji_code');

        $careers = DB::table('kbji_jobs')->whereNotNull('holland_code')->whereIn('code', $links->keys())
            ->when(Settings::get('recommend.exclude_lecturers'), fn ($q) => $q->where('code', 'not like', '2310%')) // dosen: bukan cita-cita bagi siswa SMP
            ->when(Settings::get('recommend.require_active_careers'), fn ($q) => $q->where('is_active', true))
            ->get()
            // Profesi khusus agama tertentu tidak direkomendasikan (sama dengan prodi).
            ->reject(fn ($j) => Settings::get('recommend.exclude_religious') && preg_match(config('arahdiri.recommend.religious_pattern'), $j->title))
            // "Lainnya / ytdl" = kategori sisa KBJI, tidak jelas sebagai cita-cita.
            ->reject(fn ($j) => preg_match('/\bytdl\b|lainnya/i', $j->title))
            ->map(fn ($j) => ['j' => $j, 'score' => round(Riasec::match($u, $this->onetVector($j)), 6)])
            ->sort(fn ($a, $b) => [round($b['score'], 2), $links[$b['j']->code]->count(), $a['j']->title] <=> [round($a['score'], 2), $links[$a['j']->code]->count(), $b['j']->title])
            ->values();

        $tracks = collect(SmaTracks::all())->map(fn ($t, $code) => ['code' => $code, 'score' => Riasec::match($u, $t['profile'])] + $t);
        $smkAll = DB::table('smk_programs')->get()->keyBy('code');
        $smkJobs = DB::table('smk_program_jobs')->get()->groupBy('kbji_code');

        $usedGroups = $usedPrograms = [];
        $rank = 0;
        foreach ($careers as $c) {
            if ($rank === 3) {
                break;
            }
            $j = $c['j'];
            $group = substr($j->code, 0, 3); // kelompok jabatan kecil KBJI → profesi dari bidang berbeda
            if (in_array($group, $usedGroups, true)) {
                continue;
            }

            // 2) Prodi: peringkat keterkaitan di katalog dulu, lalu kecocokan minat, lalu yang banyak dibuka.
            $progRows = $links[$j->code]->map(fn ($l) => $families[$l->program_family_id] + ['link_rank' => (int) $l->rank])
                ->sort(fn ($a, $b) => [$a['link_rank'], -round($a['score'], 2), (int) ($b['f']->level === 'S1'), -(int) $a['f']->offerings_count]
                    <=> [$b['link_rank'], -round($b['score'], 2), (int) ($a['f']->level === 'S1'), -(int) $b['f']->offerings_count])
                ->values();
            $programs = $this->pickDistinct($progRows, 2, false); // semua prodi ini memang menuju profesi yang sama
            if ($programs->isEmpty() || in_array($programs[0]['f']->id, $usedPrograms, true)) {
                continue; // profesi berbeda tapi prodinya sama persis → cari profesi lain
            }

            // 3) Arah SMA dari mapel pendukung prodi.
            $fits = [];
            foreach ($programs as $p) {
                foreach (SupportSubjects::tracksFor($p['f']->name, $p['f']->field, SupportSubjects::overrideOf($p['f'])) as $code => $fit) {
                    $fits[$code]['strength'] = max($fits[$code]['strength'] ?? 0, $fit['strength']);
                    $fits[$code]['count'] = ($fits[$code]['count'] ?? 0) + ($fit['strength'] === 2 ? 1 : 0);
                    $fits[$code]['subjects'] = array_values(array_unique(array_merge($fits[$code]['subjects'] ?? [], $fit['subjects'])));
                }
            }
            $strong = collect($fits)->filter(fn ($f) => $f['strength'] === 2);
            // Arah dengan mapel khas terbanyak; tanpa mapel khas (mis. hanya Matematika umum) → arah yang paling cocok minat.
            $pool = $strong->isNotEmpty() ? $strong->keys() : $tracks->keys();
            $smaCode = $pool->sort(fn ($a, $b) => [$fits[$b]['count'] ?? 0, $tracks[$b]['score']] <=> [$fits[$a]['count'] ?? 0, $tracks[$a]['score']])->first();
            $sma = $tracks[$smaCode];

            // 4) Program SMK.
            $direct = collect($smkJobs[$j->code] ?? [])->pluck('smk_program_code');
            $names = $programs->map(fn ($p) => $p['f']->name);
            $smk = $smkAll->map(function ($p) use ($direct, $names, $u) {
                $byCareer = $direct->contains($p->code);
                $byProgram = $names->contains(fn ($n) => SmkPaths::matches($p, $n));
                if (! $byCareer && ! $byProgram) {
                    return null;
                }

                // Cocok lewat jabatan dan lewat prodi sekaligus → paling kuat; selebihnya setara, dipecah minat.
                return ['p' => $p, 'via' => $byCareer ? 'career' : 'program', 'strength' => (int) $byCareer + (int) $byProgram,
                    'score' => $p->holland_code ? Riasec::match($u, $this->onetVector($p)) : 0];
            })->filter()
                ->sort(fn ($a, $b) => [$b['strength'], round($b['score'], 2), $a['p']->code] <=> [$a['strength'], round($a['score'], 2), $b['p']->code])
                ->take(2)->values();
            // Tanpa program SMK yang linear, smk kosong: tampilan menyarankan jalur SMA (lebih jujur daripada menebak).

            $rank++;
            $this->save($s, 'career_path', $rank, 'kbji_job', $j->code, $c['score'], [
                'title' => $j->title, 'holland' => $j->holland_code, 'description' => $this->short($j->description),
                'because' => $this->because($userTop, $j->holland_code),
                'programs' => $programs->map(fn ($p) => [
                    'id' => (string) $p['f']->id, 'name' => $p['f']->name, 'level' => $p['f']->level, 'field' => $p['f']->field,
                    'score' => round($p['score'], 4), 'offerings_count' => (int) $p['f']->offerings_count,
                    'subjects' => SupportSubjects::forProgram($p['f']->name, $p['f']->field, SupportSubjects::overrideOf($p['f'])),
                ])->all(),
                'sma' => ['code' => $smaCode, 'name' => $sma['name'], 'subjects' => $sma['subjects'],
                    'via' => $fits[$smaCode]['subjects'] ?? [], 'any' => $strong->isEmpty()],
                'smk' => $smk->map(fn ($x) => [
                    'code' => $x['p']->code, 'name' => $x['p']->name, 'field' => $x['p']->field_name, 'via' => $x['via'],
                    'concentrations' => DB::table('smk_concentrations')->where('smk_program_code', $x['p']->code)->orderBy('code')->pluck('name')->all(),
                ])->all(),
            ]);
            $usedGroups[] = $group;
            $usedPrograms[] = $programs[0]['f']->id;
        }
    }
    /** Dimensi minat yang sama antara 3 teratas pengguna dan kode Holland prodi, urut sesuai pengguna. */
    private function because(array $userTop, ?string $holland): array
    {
        $codes = str_split(strtoupper((string) $holland));

        return array_values(array_intersect($userTop, array_slice($codes, 0, 3)));
    }

    /** Ambil n baris teratas dengan nama berbeda (dan karier utama berbeda bila $distinctJob). */
    private function pickDistinct(Collection $rows, int $n, bool $distinctJob = true): Collection
    {
        $mainJob = $this->mainJobs();
        $usedJobs = $usedNames = [];
        $out = collect();
        foreach ($rows as $r) {
            if ($out->count() === $n) {
                break;
            }
            $job = $mainJob[$r['f']->id] ?? null;
            $name = mb_strtolower($r['f']->name);
            if (in_array($name, $usedNames, true) || ($distinctJob && $job && in_array($job, $usedJobs, true))) {
                continue;
            }
            $out->push($r);
            $usedNames[] = $name;
            $job && $usedJobs[] = $job;
        }

        return $out;
    }

    private function forSmaSmk(TestSession $s, array $u): void
    {
        $picked = $this->schoolPrograms($s, $u);
        $this->umbPromotions($s, $picked);
    }

    private function forUmbStudent(TestSession $s, array $u): void
    {
        $profile = DB::table('student_profiles')->where('user_id', $s->user_id)->first();
        $current = $profile?->umb_program_offering_id
            ? DB::table('program_offerings')->where('id', $profile->umb_program_offering_id)->first()
            : null;
        if (! $current) {
            return; // belum memilih prodi; halaman hasil meminta melengkapi profil
        }

        $ranked = $this->rankUmbOfferings($u, $current->level);
        $pos = $ranked->search(fn ($r) => $r['offering']->id === $current->id);
        $currentRow = $pos === false ? null : $ranked[$pos];
        $family = $current->program_family_id ? DB::table('program_families')->find($current->program_family_id) : null;

        // Evaluasi prodi sekarang.
        $this->save($s, 'umb_eval', 1, 'program_offering', (string) $current->id, $currentRow['score'] ?? 0, [
            'name' => $current->original_name, 'level' => $current->level,
            'position' => $pos === false ? null : $pos + 1, 'total' => $ranked->count(),
            'program_holland' => $family?->holland_code, 'in_top3' => $pos !== false && $pos < 3,
        ]);

        // Tiga prodi UMB terbaik di jenjang yang sama.
        foreach ($ranked->take(3)->values() as $i => $r) {
            $this->saveUmbProgram($s, 'umb_program', $i + 1, $r, $current->id);
        }

        // D3 UMB hanya sedikit: tampilkan juga jalur lanjutan S1.
        if ($current->level === 'D3') {
            foreach ($this->rankUmbOfferings($u, 'S1')->take(3)->values() as $i => $r) {
                $this->saveUmbProgram($s, 'umb_s1_path', $i + 1, $r, $current->id);
            }
        }

        // Bertahan sambil belok arah: karier dari prodi sekarang yang paling cocok.
        if ($family) {
            $jobs = DB::table('program_family_jobs as fj')->join('kbji_jobs as j', 'j.code', '=', 'fj.kbji_code')
                ->where('fj.program_family_id', $family->id)->whereNotNull('j.holland_code')
                ->get(['j.*'])
                ->map(fn ($j) => [$j, Riasec::match($u, $this->onetVector($j))])
                ->sortByDesc(fn ($x) => $x[1])->take(3)->values();
            foreach ($jobs as $i => [$j, $score]) {
                $this->save($s, 'career', $i + 1, 'kbji_job', $j->code, $score, [
                    'title' => $j->title, 'holland' => $j->holland_code, 'description' => $this->short($j->description),
                ]);
            }
        }
    }

    // ------------------------------------------------------------------ prodi + kampus

    /**
     * 3 prodi S1/D4/D3 dengan karier utama berbeda, masing-masing 1 kampus berbeda.
     * Tanpa data akreditasi, prodi tetap disimpan dengan status kampus "pending_accreditation".
     *
     * @return list<array{rec: Recommendation, family: object, jobs: list<string>}>
     */
    private function schoolPrograms(TestSession $s, array $u): array
    {
        $ranked = $this->rankFamilies($u, self::SCHOOL_LEVELS);
        $mainJob = $this->mainJobs();

        $hasData = $this->campuses->hasAccreditationData();
        $eligible = $hasData ? $this->campuses->eligibleFamilyIds() : null;
        $usedJobs = $usedCampuses = $usedNames = [];
        $picked = [];

        foreach ($ranked as $row) {
            if (count($picked) === 3) {
                break;
            }
            $f = $row['f'];
            if ($eligible !== null && ! isset($eligible[$f->id])) {
                continue; // tidak ada kampus berakreditasi aktif untuk prodi ini
            }
            $job = $mainJob[$f->id] ?? null;
            $nameKey = mb_strtolower($f->name);
            if (($job && in_array($job, $usedJobs, true)) || in_array($nameKey, $usedNames, true)) {
                continue; // jalur karier utama harus berbeda
            }

            $campus = $hasData ? $this->campuses->pick($f->id, $usedCampuses) : null;
            if ($hasData && ! $campus) {
                continue; // tidak ada kampus memenuhi syarat → prodi berikutnya
            }

            $jobs = $this->jobsFor('program_family_jobs', 'program_family_id', $f->id, 4);
            $rec = $this->save($s, 'program', count($picked) + 1, 'program_family', (string) $f->id, $row['score'], [
                'name' => $f->name, 'level' => $f->level, 'field' => $f->field, 'holland' => $f->holland_code,
                'offerings_count' => (int) $f->offerings_count, 'careers' => $jobs,
            ]);
            $this->saveCampus($rec, $campus);

            $job && $usedJobs[] = $job;
            $campus && $usedCampuses[] = $campus['campus_id'];
            $usedNames[] = $nameKey;
            $picked[] = ['rec' => $rec, 'family' => $f, 'jobs' => array_column($jobs, 'code')];
        }

        return $picked;
    }

    /**
     * Prodi yang boleh direkomendasikan pada jenjang tertentu, diurutkan dari paling cocok.
     * Urutan: skor (dibulatkan ke persen yang ditampilkan) turun → pemetaan spesifik dulu → S1, D4, D3
     * → jumlah kampus terbanyak → nama. Pembulatan mencegah prodi langka menang hanya karena selisih 0,0x%.
     */
    private function rankFamilies(array $u, array $levels): Collection
    {
        $families = DB::table('program_families as f')
            ->whereIn('f.level', $levels)->where('f.is_recommendable', true)->whereNotNull('f.holland_code')
            ->whereExists(fn ($q) => $q->from('program_offerings as o')->whereColumn('o.program_family_id', 'f.id')->where('o.is_active', true))
            ->select('f.*')
            ->selectSub(fn ($q) => $q->from('program_offerings as o')->whereColumn('o.program_family_id', 'f.id')->where('o.is_active', true)->selectRaw('count(*)'), 'offerings_count')
            ->get()
            ->reject(fn ($f) => Settings::get('recommend.exclude_religious') && preg_match(config('arahdiri.recommend.religious_pattern'), $f->name));

        $levelOrder = array_flip(self::SCHOOL_LEVELS);
        $key = fn ($r) => [round($r['score'], 2), $r['f']->mapping_method === 'aturan_spesifik' ? 1 : 0, -($levelOrder[$r['f']->level] ?? 9), (int) $r['f']->offerings_count];

        return $families->map(fn ($f) => ['f' => $f, 'score' => round(Riasec::match($u, $this->onetVector($f)), 6)])
            ->sort(fn ($a, $b) => ($key($b) <=> $key($a)) ?: strcmp($a['f']->name, $b['f']->name))
            ->values();
    }

    private ?Collection $mainJobCache = null;

    private function mainJobs(): Collection
    {
        return $this->mainJobCache ??= DB::table('program_family_jobs')->where('rank', 1)->pluck('kbji_code', 'program_family_id');
    }

    // ------------------------------------------------------------------ UMB

    /** Section promosi UMB (SMA/SMK saja). Tidak mengubah urutan rekomendasi. */
    private function umbPromotions(TestSession $s, array $picked): void
    {
        $umb = Campus::query()->umb()->first();
        if (! $umb || ! $picked) {
            return;
        }
        $hasData = $this->campuses->hasAccreditationData();

        $offers = DB::table('program_offerings')->where('campus_id', $umb->id)->where('is_active', true)
            ->whereIn('level', self::SCHOOL_LEVELS)->whereNotNull('program_family_id')->get()
            ->filter(fn ($o) => ! $hasData || (($a = $this->campuses->forOffering($o->id)) && $a['p_score'] > 0 && (! $a['p_expires'] || $a['p_expires'] >= now()->toDateString())));
        if ($offers->isEmpty()) {
            return;
        }

        $jobsOf = DB::table('program_family_jobs')->whereIn('program_family_id', $offers->pluck('program_family_id')->unique())
            ->orderBy('rank')->get()->groupBy('program_family_id')->map(fn ($g) => $g->pluck('kbji_code')->all());

        $used = [];
        foreach ($picked as $p) {
            $f = $p['family'];
            $sameLevel = $offers->where('level', $f->level)->reject(fn ($o) => in_array($o->id, $used, true));
            $recMain = $p['jobs'][0] ?? null;

            // Satu rumpun: kelompok prodi sama, atau karier utama sama (mis. Informatika ↔ Teknik Informatika).
            $same = $sameLevel->first(fn ($o) => $o->program_family_id === $f->id
                || ($recMain && ($jobsOf[$o->program_family_id][0] ?? null) === $recMain));
            if ($same) {
                $this->savePromo($s, $p['rec'], $same->id, 'same_family', null);
                $used[] = $same->id;
                continue;
            }

            // Beda rumpun, tetapi mengarah ke jabatan KBJI yang sama.
            foreach ($p['jobs'] as $code) {
                $rel = $sameLevel->first(fn ($o) => in_array($code, $jobsOf[$o->program_family_id] ?? [], true));
                if ($rel) {
                    $this->savePromo($s, $p['rec'], $rel->id, 'related_career', $code);
                    $used[] = $rel->id;
                    break;
                }
            }
        }
    }

    /** Prodi UMB aktif pada satu jenjang, diurutkan dari kecocokan tertinggi. */
    private function rankUmbOfferings(array $u, string $level): Collection
    {
        $umb = Campus::query()->umb()->first();
        if (! $umb) {
            return collect();
        }
        $hasData = $this->campuses->hasAccreditationData();
        $today = now()->toDateString();

        return DB::table('program_offerings as o')->join('program_families as f', 'f.id', '=', 'o.program_family_id')
            ->where('o.campus_id', $umb->id)->where('o.level', $level)->where('o.is_active', true)->whereNotNull('f.holland_code')
            ->get(['o.*', 'f.riasec_r', 'f.riasec_i', 'f.riasec_a', 'f.riasec_s', 'f.riasec_e', 'f.riasec_c', 'f.holland_code', 'f.id as family_id'])
            ->map(function ($o) use ($u, $hasData, $today) {
                $acc = $this->campuses->forOffering($o->id);
                $active = $acc && $acc['p_score'] > 0 && (! $acc['p_expires'] || $acc['p_expires'] >= $today);

                return ['offering' => $o, 'score' => round(Riasec::match($u, $this->onetVector($o)), 6), 'acc' => $acc, 'active' => $active];
            })
            // Hanya prodi aktif menurut BAN-PT; tanpa data akreditasi, semua prodi katalog dipakai.
            ->filter(fn ($r) => ! $hasData || $r['active'])
            ->sort(fn ($a, $b) => [$b['score'], $a['offering']->original_name] <=> [$a['score'], $b['offering']->original_name])
            ->values();
    }

    private function saveUmbProgram(TestSession $s, string $kind, int $rank, array $r, int $currentId): void
    {
        $o = $r['offering'];
        $rec = $this->save($s, $kind, $rank, 'program_offering', (string) $o->id, $r['score'], [
            'name' => $o->original_name, 'level' => $o->level, 'holland' => $o->holland_code,
            'is_current' => $o->id === $currentId,
            'careers' => $this->jobsFor('program_family_jobs', 'program_family_id', $o->family_id, 3),
        ]);
        $this->saveCampus($rec, $r['acc'] ?: ['offering_id' => $o->id, 'campus_id' => $o->campus_id], ! $r['acc']);
    }

    // ------------------------------------------------------------------ penyimpanan

    private function save(TestSession $s, string $kind, int $rank, string $type, string $id, float $score, array $reason): Recommendation
    {
        return $s->recommendations()->create([
            'kind' => $kind, 'rank' => $rank, 'target_type' => $type, 'target_id' => $id,
            'score' => round($score, 4), 'reason' => $reason, 'engine_version' => self::VERSION,
        ]);
    }

    private function saveCampus(Recommendation $rec, ?array $row, bool $noAccreditation = false): void
    {
        $rec->campusPick()->create([
            'campus_id' => $row['campus_id'] ?? null,
            'program_offering_id' => $row['offering_id'] ?? null,
            'program_accreditation' => $row ? CampusPicker::snapshot($row, 'p') : null,
            'campus_accreditation' => $row ? CampusPicker::snapshot($row, 'c') : null,
            'accreditation_fetched_at' => $row['fetched_at'] ?? null,
            'status' => ($row && ! $noAccreditation && ! empty($row['p_rating'])) ? 'ready' : 'pending_accreditation',
        ]);
    }

    private function savePromo(TestSession $s, Recommendation $rec, int $offeringId, string $type, ?string $via): void
    {
        DB::table('umb_promotions')->insert([
            'test_session_id' => $s->id, 'recommendation_id' => $rec->id, 'program_offering_id' => $offeringId,
            'match_type' => $type, 'via_kbji_code' => $via, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // ------------------------------------------------------------------ bantu

    /** @return array<string, float> profil pengguna 0–1 */
    private function userVector(TestResult $r): array
    {
        $v = [];
        foreach (Riasec::ORDER as $d) {
            $v[$d] = (float) $r->{'norm_'.$d};
        }

        return $v;
    }

    /** Profil katalog skala O*NET 1–7 → 0–1. */
    private function onetVector(object $row): array
    {
        $v = [];
        foreach (Riasec::ORDER as $d) {
            $v[$d] = Riasec::normalizeOnet(isset($row->{'riasec_'.$d}) ? (float) $row->{'riasec_'.$d} : null);
        }

        return $v;
    }

    /** @return list<array{code: string, title: string}> */
    private function jobsFor(string $table, string $col, $id, int $limit): array
    {
        return DB::table($table.' as l')->join('kbji_jobs as j', 'j.code', '=', 'l.kbji_code')
            ->where('l.'.$col, $id)->when(Settings::get('recommend.require_active_careers'), fn ($q) => $q->where('j.is_active', true))
            ->orderBy('l.rank')->limit($limit)
            ->get(['j.code', 'j.title'])->map(fn ($j) => ['code' => $j->code, 'title' => $j->title])->all();
    }

    private function short(?string $text, int $max = 260): ?string
    {
        if (! $text) {
            return null;
        }
        $text = trim(preg_replace('/\s+/', ' ', $text));

        return mb_strlen($text) > $max ? rtrim(mb_substr($text, 0, $max - 1)).'…' : $text;
    }
}
