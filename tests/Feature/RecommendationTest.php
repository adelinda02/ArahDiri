<?php

namespace Tests\Feature;

use App\Models\Recommendation;
use App\Models\TestSession;
use App\Models\User;
use App\Services\Recommendation\RecommendationEngine;
use App\Services\RiasecScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecommendationTest extends TestCase
{
    use RefreshDatabase;

    private int $umbId;

    protected function setUp(): void
    {
        parent::setUp();
        $now = now();

        // Profil skala O*NET 1-7. Pengguna di tes ini ber-kode IAS.
        $ias = ['riasec_r' => 2, 'riasec_i' => 7, 'riasec_a' => 6, 'riasec_s' => 5, 'riasec_e' => 2, 'riasec_c' => 1, 'holland_code' => 'IAS'];
        $iac = ['riasec_r' => 2, 'riasec_i' => 7, 'riasec_a' => 5.5, 'riasec_s' => 3, 'riasec_e' => 2, 'riasec_c' => 4, 'holland_code' => 'IAC'];
        $cer = ['riasec_r' => 3, 'riasec_i' => 2, 'riasec_a' => 1, 'riasec_s' => 3, 'riasec_e' => 5, 'riasec_c' => 7, 'holland_code' => 'CER'];

        foreach ([['2634.01', 'Psikolog Klinis'], ['2632.01', 'Ahli Antropologi'], ['2511.01', 'Analis Sistem'], ['2411.01', 'Akuntan'], ['2166.01', 'Desainer Grafis']] as [$code, $title]) {
            DB::table('kbji_jobs')->insert(['code' => $code, 'title' => $title, 'official_title' => $title, 'subgroup_code' => substr($code, 0, 4), 'major_group' => '2', 'mapping_status' => 'draft_ai', 'created_at' => $now, 'updated_at' => $now] + $ias);
        }

        $fam = fn (string $name, string $level, array $p) => DB::table('program_families')->insertGetId(['key' => strtolower($name).'|'.$level, 'name' => $name, 'level' => $level, 'mapping_method' => 'aturan_spesifik', 'created_at' => $now, 'updated_at' => $now] + $p);
        $psi = $fam('Psikologi', 'S1', $ias);
        $psi2 = $fam('Psikologi Terapan', 'S1', $ias);       // karier utama sama dengan Psikologi → harus dilewati
        $antro = $fam('Antropologi', 'S1', $ias);
        $ti = $fam('Teknik Informatika', 'S1', $iac);
        $si = $fam('Sistem Informasi', 'S1', $iac);
        $aku = $fam('Akuntansi', 'S1', $cer);
        $agama = $fam('Pendidikan Agama Islam', 'S1', $ias); // khusus agama → tidak direkomendasikan otomatis
        $dkv = $fam('Desain Komunikasi Visual', 'S1', $ias);

        $job = fn ($f, $code, $rank) => DB::table('program_family_jobs')->insert(['program_family_id' => $f, 'kbji_code' => $code, 'rank' => $rank]);
        $job($psi, '2634.01', 1); $job($psi2, '2634.01', 1); $job($antro, '2632.01', 1); $job($ti, '2511.01', 1);
        $job($si, '2511.01', 1); $job($aku, '2411.01', 1); $job($agama, '2632.01', 1); $job($dkv, '2166.01', 1); $job($si, '2166.01', 2);

        $campus = fn (string $code, string $name, string $own) => DB::table('campuses')->insertGetId(['pt_code' => $code, 'name' => $name, 'ownership' => $own, 'is_state' => $own === 'Negeri', 'created_at' => $now, 'updated_at' => $now]);
        $ptnA = $campus('001001', 'Universitas Negeri A', 'Negeri');
        $ptsB = $campus('002002', 'Universitas Swasta B', 'Swasta');
        $ptsC = $campus('003003', 'Universitas Swasta C', 'Swasta');
        $this->umbId = $campus('031019', 'Universitas Mercu Buana', 'Swasta');

        $off = fn ($c, $f, $name, $level = 'S1') => DB::table('program_offerings')->insertGetId(['campus_id' => $c, 'program_family_id' => $f, 'program_code' => (string) random_int(10000, 99999), 'original_name' => $name, 'level' => $level, 'created_at' => $now, 'updated_at' => $now]);
        $acc = fn (string $type, int $id, string $rating, int $score, string $exp = '2030-01-01') => DB::table('accreditations')->insert(['subject_type' => $type, 'subject_id' => $id, 'rating_raw' => $rating, 'rating_score' => $score, 'expires_at' => $exp, 'fetched_at' => $now, 'created_at' => $now, 'updated_at' => $now]);

        foreach ([$ptnA => ['Baik Sekali', 5], $ptsB => ['Unggul', 6], $ptsC => ['Baik Sekali', 5], $this->umbId => ['Unggul', 6]] as $id => [$r, $s]) {
            $acc('campus', $id, $r, $s);
        }
        // Psikologi: prodi Unggul di PTN A dan PTS C (kampus sama-sama Baik Sekali) → PTN A menang karena PTN.
        $acc('program_offering', $off($ptnA, $psi, 'Psikologi'), 'Unggul', 6);
        $acc('program_offering', $off($ptsC, $psi, 'Psikologi'), 'Unggul', 6);
        // PTS B prodi Baik Sekali → kalah dari prodi Unggul walau kampusnya Unggul.
        $acc('program_offering', $off($ptsB, $psi, 'Psikologi'), 'Baik Sekali', 5);
        $acc('program_offering', $off($ptsB, $psi2, 'Psikologi Terapan'), 'Unggul', 6);
        // Antropologi hanya di PTN A (sudah dipakai Psikologi) dan kedaluwarsa di PTS C → tidak ada kampus → dilewati.
        $acc('program_offering', $off($ptnA, $antro, 'Antropologi'), 'Unggul', 6);
        $acc('program_offering', $off($ptsC, $antro, 'Antropologi'), 'Unggul', 6, '2020-01-01');
        $acc('program_offering', $off($ptsB, $ti, 'Teknik Informatika'), 'Unggul', 6);
        $acc('program_offering', $off($ptsC, $dkv, 'Desain Komunikasi Visual'), 'Baik', 4);
        $acc('program_offering', $off($ptsB, $agama, 'Pendidikan Agama Islam'), 'Unggul', 6);
        $acc('program_offering', $off($ptsC, $aku, 'Akuntansi'), 'Unggul', 6);

        // UMB: Psikologi (rumpun sama), Sistem Informasi (karier sama dengan Teknik Informatika), Akuntansi.
        $acc('program_offering', $off($this->umbId, $psi, 'Psikologi'), 'Baik Sekali', 5);
        $acc('program_offering', $off($this->umbId, $si, 'Sistem Informasi'), 'Baik Sekali', 5);
        $acc('program_offering', $off($this->umbId, $aku, 'Akuntansi'), 'Unggul', 6);
    }

    private function run_(string $level, ?callable $before = null): TestSession
    {
        $u = User::factory()->create(['level' => $level]);
        $u->profile()->create();
        $before && $before($u);
        $v = ['R' => 2, 'I' => 5, 'A' => 4, 'S' => 3, 'E' => 2, 'C' => 1];
        $answers = [];
        foreach (config('riasec.items') as $no => [$d]) {
            $answers[$no] = $v[$d];
        }
        $sc = app(RiasecScorer::class)->score($answers);
        $s = $u->testSessions()->create(['level_at_test' => $level, 'status' => 'completed', 'instrument_version' => 'riasec-50-v1', 'started_at' => now(), 'completed_at' => now()]);
        $row = ['scoring_version' => 'x', 'holland_code' => $sc['code']];
        foreach ($sc['raw'] as $d => $x) {
            $row['score_'.$d] = $x;
            $row['norm_'.$d] = $sc['norm'][$d];
        }
        app(RecommendationEngine::class)->generate($s, $s->result()->create($row));

        return $s;
    }

    private function programs(TestSession $s, string $kind = 'program')
    {
        return Recommendation::with('campusPick.campus')->where('test_session_id', $s->id)->where('kind', $kind)->orderBy('rank')->get();
    }

    public function test_sma_smk_gets_three_programs_with_distinct_careers_and_campuses(): void
    {
        $recs = $this->programs($this->run_('sma_smk'));

        // Psikologi Terapan (karier utama sama), Antropologi (tanpa kampus berlaku), Pendidikan Agama Islam (khusus agama)
        // dan Teknik Informatika (seri dengan Sistem Informasi, karier utama sama, kalah urutan nama) tidak muncul.
        $this->assertSame(['Psikologi', 'Desain Komunikasi Visual', 'Sistem Informasi'], $recs->pluck('reason.name')->all());
        // Psikologi: prodi Unggul di PTN A & PTS C → PTN A (PTN didahulukan); PTS B prodinya hanya Baik Sekali.
        $this->assertSame('Universitas Negeri A', $recs[0]->campusPick->campus->name);
        $this->assertSame('Unggul', $recs[0]->campusPick->program_accreditation['rating']);
        $this->assertSame('Universitas Swasta C', $recs[1]->campusPick->campus->name);
        $this->assertSame('Universitas Mercu Buana', $recs[2]->campusPick->campus->name);
        $this->assertCount(3, $recs->pluck('campusPick.campus_id')->unique());
    }

    public function test_umb_promotion_for_sma_smk_only(): void
    {
        $s = $this->run_('sma_smk');
        $promos = DB::table('umb_promotions as p')->join('program_offerings as o', 'o.id', '=', 'p.program_offering_id')
            ->where('test_session_id', $s->id)->orderBy('p.id')->get(['p.match_type', 'o.original_name', 'p.via_kbji_code']);

        $this->assertSame(['same_family', 'Psikologi'], [$promos[0]->match_type, $promos[0]->original_name]);
        // DKV → Sistem Informasi UMB lewat jabatan Desainer Grafis (beda rumpun, karier sama).
        $this->assertSame(['related_career', 'Sistem Informasi', '2166.01'], [$promos[1]->match_type, $promos[1]->original_name, $promos[1]->via_kbji_code]);
        $this->assertCount(2, $promos);

        $smp = $this->run_('smp');
        $this->assertSame(0, DB::table('umb_promotions')->where('test_session_id', $smp->id)->count());
    }

    public function test_smp_gets_three_career_paths_worked_backwards(): void
    {
        $now = now();
        foreach ([['4.1', 'Pengembangan Perangkat Lunak dan Gim', 'Teknologi Informasi'], ['10.2', 'Desain Komunikasi Visual', 'Seni dan Ekonomi Kreatif']] as [$code, $name, $field]) {
            DB::table('smk_programs')->insert(['code' => $code, 'name' => $name, 'field_code' => strtok($code, '.'), 'field_name' => $field, 'riasec_r' => 3, 'riasec_i' => 5, 'riasec_a' => 6, 'riasec_s' => 3, 'riasec_e' => 2, 'riasec_c' => 3, 'holland_code' => 'AIR', 'created_at' => $now, 'updated_at' => $now]);
        }
        DB::table('smk_program_jobs')->insert(['smk_program_code' => '10.2', 'kbji_code' => '2166.01', 'rank' => 1]);

        $s = $this->run_('smp');
        $paths = $this->programs($s, 'career_path');

        // Profil semua jabatan sama → seri dipecah jumlah prodi terkait lalu judul. Ahli Antropologi sekelompok (263)
        // dengan Psikolog Klinis; Akuntan hanya punya 1 prodi.
        $this->assertSame(['Analis Sistem', 'Desainer Grafis', 'Psikolog Klinis'], $paths->pluck('reason.title')->all());
        $this->assertSame(0, $this->programs($s, 'sma_track')->count()); // diganti seluruhnya

        [$analis, $desainer, $psikolog] = $paths->map->reason->all();
        // Analis Sistem → 2 prodi (karier utama sama boleh, karena memang menuju profesi ini) → SMA IPA → SMK PPLG lewat peta prodi.
        $this->assertEqualsCanonicalizing(['Teknik Informatika', 'Sistem Informasi'], array_column($analis['programs'], 'name'));
        $this->assertSame('ipa', $analis['sma']['code']);
        $this->assertSame(['Matematika Tingkat Lanjut'], $analis['sma']['via']);
        $this->assertSame([['4.1', 'program']], array_map(fn ($k) => [$k['code'], $k['via']], $analis['smk']));
        // Desainer Grafis → SMK DKV lewat jabatan yang sama (didahulukan).
        $this->assertSame(['10.2', 'career'], [$desainer['smk'][0]['code'], $desainer['smk'][0]['via']]);
        $this->assertSame('Desain Komunikasi Visual', $desainer['programs'][0]['name']);
        // Psikolog → Psikologi (Sosiologi) → SMA IPS; tidak ada SMK yang linear.
        $this->assertSame('Psikologi', $psikolog['programs'][0]['name']);
        $this->assertSame('ips', $psikolog['sma']['code']);
        $this->assertSame([], $psikolog['smk']);
        $this->assertSame(['I', 'A', 'S'], $psikolog['because']);
        $this->assertNull($paths[0]->campusPick);
        $this->assertSame('rec-v4', $paths[0]->engine_version);

        // Halaman hasil dan PDF membawa dasar hukum mapel pendukung dan catatan SMK (Diktum KETIGA).
        $this->actingAs($s->user)->get('/hasil')->assertInertia(fn ($p) => $p->where('recs.snbp.source', 'Kepmendikdasmen 102/M/2025')
            ->where('recs.snbp.smk_note', fn ($n) => str_contains($n, 'Dasar-dasar Program Keahlian') && str_contains($n, 'Diktum KETIGA')));
        $this->get("/hasil/{$s->id}/cetak")->assertOk()->assertSee('Kepmendikdasmen 102/M/2025')->assertSee('Projek Kreatif dan Kewirausahaan')->assertDontSee('345/M/2022');
    }

    public function test_umb_student_gets_evaluation_among_umb_programs_only(): void
    {
        $s = $this->run_('mhs_umb', function (User $u) {
            $aku = DB::table('program_offerings')->where('campus_id', $this->umbId)->where('original_name', 'Akuntansi')->value('id');
            $u->profile->update(['umb_program_offering_id' => $aku, 'umb_self_declared' => true]);
        });

        $eval = $this->programs($s, 'umb_eval')->first();
        $this->assertSame(['position' => 3, 'total' => 3], ['position' => $eval->reason['position'], 'total' => $eval->reason['total']]);
        $top = $this->programs($s, 'umb_program');
        $this->assertSame(['Psikologi', 'Sistem Informasi', 'Akuntansi'], $top->pluck('reason.name')->all());
        $this->assertTrue($top->every(fn ($r) => $r->campusPick->campus_id === $this->umbId));
        $this->assertSame(1, $this->programs($s, 'career')->count()); // karier Akuntansi
    }

    public function test_without_accreditation_data_results_are_still_saved(): void
    {
        DB::table('accreditations')->delete();
        $recs = $this->programs($this->run_('sma_smk'));

        $this->assertCount(3, $recs);
        $this->assertTrue($recs->every(fn ($r) => $r->campusPick->status === 'pending_accreditation' && $r->campusPick->campus_id === null));
    }

    public function test_result_page_renders_recommendations(): void
    {
        $s = $this->run_('sma_smk');
        $this->actingAs($s->user)->get('/hasil')->assertOk()
            ->assertInertia(fn ($page) => $page->component('Hasil')->has('recs.programs', 3)->has('recs.umb_promos', 2));
    }
}
