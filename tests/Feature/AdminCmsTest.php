<?php

namespace Tests\Feature;

use App\Models\TestSession;
use App\Models\User;
use App\Support\Settings;
use App\Support\SmkPaths;
use App\Support\SupportSubjects;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Admin lanjutan: simulator, aturan, laporan siswa, penyuntingan katalog, dan konten. */
class AdminCmsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'admin'): User
    {
        $u = User::factory()->create();
        $u->profile()->create();
        $u->forceFill(['role' => $role])->save();

        return $u;
    }

    private function seedCatalog(): void
    {
        $now = now();
        $p = ['riasec_r' => 2, 'riasec_i' => 7, 'riasec_a' => 5, 'riasec_s' => 3, 'riasec_e' => 2, 'riasec_c' => 4, 'holland_code' => 'IAC'];
        DB::table('kbji_jobs')->insert(['code' => '2511.01', 'title' => 'Analis Sistem', 'official_title' => 'Analis Sistem', 'subgroup_code' => '2511', 'major_group' => '2', 'created_at' => $now, 'updated_at' => $now] + $p);
        $c = DB::table('campuses')->insertGetId(['pt_code' => '001001', 'name' => 'Universitas A', 'created_at' => $now, 'updated_at' => $now]);
        $f = DB::table('program_families')->insertGetId(['key' => 'teknik informatika|S1', 'name' => 'Teknik Informatika', 'level' => 'S1', 'created_at' => $now, 'updated_at' => $now] + $p);
        DB::table('program_offerings')->insert(['campus_id' => $c, 'program_family_id' => $f, 'program_code' => '55201', 'original_name' => 'Teknik Informatika', 'level' => 'S1', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('program_family_jobs')->insert(['program_family_id' => $f, 'kbji_code' => '2511.01', 'rank' => 1]);
        DB::table('smk_programs')->insert(['code' => '4.1', 'name' => 'Pengembangan Perangkat Lunak dan Gim', 'field_code' => '4', 'field_name' => 'Teknologi Informasi', 'created_at' => $now, 'updated_at' => $now] + $p);
    }

    public function test_simulator_runs_engine_without_leaving_data(): void
    {
        $this->seedCatalog();
        $admin = $this->admin();
        $before = [DB::table('users')->count(), DB::table('test_sessions')->count(), DB::table('recommendations')->count()];

        $this->actingAs($admin)->get('/admin/simulator?jenjang=smp&r=20&i=90&a=60&s=30&e=20&c=50&jalankan=1')->assertOk()
            ->assertInertia(fn ($p) => $p->component('Admin/Simulator')->where('code', 'IAC')
                ->where('result.career_paths.0.title', 'Analis Sistem')
                ->where('result.career_paths.0.sma.code', 'ipa')
                ->where('result.career_paths.0.smk.0.code', '4.1'));

        $this->assertSame($before, [DB::table('users')->count(), DB::table('test_sessions')->count(), DB::table('recommendations')->count()]);
        $this->get('/admin/simulator')->assertInertia(fn ($p) => $p->where('result', null));
    }

    public function test_rules_can_be_changed_and_are_audited(): void
    {
        $admin = $this->admin();
        $this->assertTrue(Settings::get('recommend.exclude_religious'));
        $this->actingAs($admin)->post('/admin/aturan', ['key' => 'recommend.exclude_religious', 'value' => false])->assertSessionHasNoErrors();
        Settings::flush();
        $this->assertFalse(Settings::get('recommend.exclude_religious'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'rule_changed', 'subject_id' => 'recommend.exclude_religious']);
        $this->post('/admin/aturan', ['key' => 'tidak.ada', 'value' => true])->assertSessionHasErrors('key');
    }

    public function test_student_report_reaches_admin_inbox(): void
    {
        $student = User::factory()->create();
        $student->profile()->create();
        $s = TestSession::create(['user_id' => $student->id, 'level_at_test' => 'sma_smk', 'status' => 'completed', 'instrument_version' => 'x', 'current_item' => 50, 'started_at' => now(), 'completed_at' => now()]);
        $s->result()->create(['score_r' => 1, 'score_i' => 1, 'score_a' => 1, 'score_s' => 1, 'score_e' => 1, 'score_c' => 1, 'norm_r' => .2, 'norm_i' => .9, 'norm_a' => .5, 'norm_s' => .4, 'norm_e' => .1, 'norm_c' => .3, 'holland_code' => 'IAS', 'scoring_version' => 'x']);

        $this->actingAs($student)->post("/hasil/{$s->id}/laporan", ['category' => 'tidak_nyambung', 'message' => 'Aneh'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('result_reports', ['test_session_id' => $s->id, 'category' => 'tidak_nyambung', 'status' => 'baru']);

        $other = User::factory()->create();
        $this->flushSession();
        $this->actingAs($other)->post("/hasil/{$s->id}/laporan", ['category' => 'lainnya'])->assertNotFound();

        $admin = $this->admin();
        $this->flushSession();
        $this->actingAs($admin)->get('/admin/laporan')->assertInertia(fn ($p) => $p->component('Admin/Laporan')->has('items.data', 1)
            ->where('items.data.0.code', 'IAS')->where('items.data.0.simulate.i', 90)->missing('items.data.0.user'));
        $id = DB::table('result_reports')->value('id');
        $this->put("/admin/laporan/{$id}", ['status' => 'selesai', 'admin_note' => 'Diperbaiki'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('result_reports', ['id' => $id, 'status' => 'selesai', 'resolved_by' => $admin->id]);
    }

    public function test_catalog_editor_updates_locks_and_detects_conflicts(): void
    {
        $this->seedCatalog();
        $admin = $this->admin();
        $this->actingAs($admin);

        $data = $this->getJson('/admin/katalog/karier/2511.01')->assertOk()->json();
        $riasec = ['r' => 1, 'i' => 3, 'a' => 7, 's' => 2, 'e' => 5, 'c' => 1];
        $this->put('/admin/katalog/karier/2511.01', ['stamp' => $data['stamp'], 'title' => 'Analis Sistem Informasi', 'description' => 'Uraian', 'is_active' => true, 'riasec' => $riasec])->assertSessionHasNoErrors();
        $job = DB::table('kbji_jobs')->where('code', '2511.01')->first();
        $this->assertSame('Analis Sistem Informasi', $job->title);
        $this->assertSame('AEI', $job->holland_code);
        $this->assertNotNull($job->edited_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'catalog_edited', 'subject_type' => 'kbji_jobs', 'subject_id' => '2511.01']);

        // Stempel lama → ditolak (admin lain sudah menyimpan).
        $this->put('/admin/katalog/karier/2511.01', ['stamp' => $data['stamp'], 'title' => 'X', 'riasec' => $riasec])->assertSessionHasErrors('stamp');

        $this->post('/admin/katalog/karier/2511.01/lepas')->assertSessionHasNoErrors();
        $this->assertNull(DB::table('kbji_jobs')->where('code', '2511.01')->value('edited_at'));

        // Prodi: override mapel pendukung + relasi karier.
        $fid = DB::table('program_families')->value('id');
        $pd = $this->getJson("/admin/katalog/prodi/{$fid}")->json();
        $this->assertSame(['Matematika Tingkat Lanjut'], $pd['support_auto']);
        $this->put("/admin/katalog/prodi/{$fid}", ['stamp' => $pd['stamp'], 'description' => 'Belajar perangkat lunak.', 'is_recommendable' => true,
            'support_override' => ['Informatika', 'Fisika'], 'careers' => ['2511.01'], 'riasec' => $riasec])->assertSessionHasNoErrors();
        $fam = DB::table('program_families')->find($fid);
        $this->assertSame(['Informatika', 'Fisika'], SupportSubjects::forProgram($fam->name, $fam->field, SupportSubjects::overrideOf($fam)));
        $this->get("/jurusan/{$fid}")->assertInertia(fn ($p) => $p->where('detail.description', 'Belajar perangkat lunak.'));

        // SMK: kata kunci + pengecualian menggantikan peta bawaan.
        $sd = $this->getJson('/admin/katalog/smk/4.1')->json();
        $this->assertTrue($sd['using_default']);
        $this->assertContains('Teknik Informatika (S1)', $sd['preview']);
        $this->put('/admin/katalog/smk/4.1', ['stamp' => $sd['stamp'], 'next_keywords' => "informatika\nkomputer", 'next_exclude' => 'teknik informatika', 'riasec' => $riasec])->assertSessionHasNoErrors();
        $smk = DB::table('smk_programs')->where('code', '4.1')->first();
        $this->assertFalse(SmkPaths::matches($smk, 'Teknik Informatika'));
        $this->assertTrue(SmkPaths::matches($smk, 'Ilmu Komputer'));

        // Arah SMA tersimpan di basis data dan bisa disunting.
        $ma = $this->getJson('/admin/katalog/sma/ipa')->json();
        $this->put('/admin/katalog/sma/ipa', ['stamp' => $ma['stamp'], 'name' => 'IPA', 'subjects' => ['Fisika', 'Kimia'], 'riasec' => $riasec])->assertSessionHasNoErrors();
        $this->assertSame(['Fisika', 'Kimia'], \App\Support\SmaTracks::all()['ipa']['subjects']);
    }

    public function test_content_is_versioned_and_shown_on_public_pages(): void
    {
        $this->seedCatalog();
        $admin = $this->admin();
        $this->actingAs($admin);

        $this->post('/admin/konten', ['kind' => 'skill', 'version' => 0, 'title' => 'Berpikir sistematis', 'body' => 'Memecah masalah.', 'status' => 'active', 'meta' => ['kbji' => ['2511.01']]])->assertSessionHasNoErrors();
        $this->post('/admin/konten', ['kind' => 'activity', 'version' => 0, 'title' => 'Ikut klub robotik', 'status' => 'active', 'meta' => ['category' => 'softskill']])->assertSessionHasNoErrors();
        $this->post('/admin/konten', ['kind' => 'reference', 'version' => 0, 'title' => 'O*NET', 'status' => 'active', 'meta' => ['url' => 'https://www.onetonline.org']])->assertSessionHasNoErrors();
        $this->post('/admin/konten', ['kind' => 'reference', 'version' => 0, 'title' => 'Buruk', 'status' => 'active', 'meta' => ['url' => 'javascript:alert(1)']])->assertSessionHasErrors('meta.url');
        $this->post('/admin/konten', ['kind' => 'copy', 'version' => 0, 'title' => 'Tentang data', 'body' => 'Penjelasan tambahan.', 'status' => 'active'])->assertSessionHasNoErrors();

        $skill = DB::table('content_items')->where('kind', 'skill')->first();
        $this->post('/admin/konten', ['kind' => 'skill', 'id' => $skill->id, 'version' => 0, 'title' => 'Basi', 'status' => 'active'])->assertSessionHasErrors('version');

        $this->get('/karier/2511.01')->assertInertia(fn ($p) => $p->where('detail.skills.0.title', 'Berpikir sistematis'));
        $this->get('/referensi')->assertInertia(fn ($p) => $p->where('content.references.0.meta.url', 'https://www.onetonline.org')
            ->where('content.skills.0.title', 'Berpikir sistematis')->where('content.info.0.title', 'Tentang data'));
        $this->get('/langkah')->assertInertia(fn ($p) => $p->where('activities.0.title', 'Ikut klub robotik'));

        // Skill juga bisa ditautkan dari editor karier.
        $data = $this->getJson('/admin/katalog/karier/2511.01')->json();
        $this->assertSame([$skill->id], $data['skills']);

        $this->delete("/admin/konten/{$skill->id}")->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('content_items', ['id' => $skill->id]);
    }

    public function test_catalog_import_keeps_admin_edits(): void
    {
        $this->seedCatalog();
        DB::table('kbji_jobs')->insert(['code' => '2411.01', 'title' => 'Akuntan', 'official_title' => 'Akuntan', 'subgroup_code' => '2411', 'major_group' => '2', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('kbji_jobs')->where('code', '2511.01')->update(['title' => 'Judul admin', 'edited_at' => now()]);

        $dir = sys_get_temp_dir().'/arahdiri-import-'.uniqid();
        mkdir($dir);
        $head = 'code,title,official_title,subgroup_code,subgroup_title,major_group,description,print_page,pdf_page,riasec_r,riasec_i,riasec_a,riasec_s,riasec_e,riasec_c,holland_code,mapping_status,job_zone_min,job_zone_max';
        file_put_contents($dir.'/kbji_jobs.csv', $head."\n2511.01,Judul sumber,Analis,2511,,2,,,,1,2,3,4,5,6,ECS,draft_ai,,\n2411.01,Akuntan Baru,Akuntan,2411,,2,,,,1,2,3,4,5,6,ECS,draft_ai,,\n");

        $this->artisan('catalog:import', ['--path' => $dir, '--only' => 'kbji_jobs'])->assertSuccessful();
        $this->assertSame('Judul admin', DB::table('kbji_jobs')->where('code', '2511.01')->value('title')); // dikunci
        $this->assertSame('Akuntan Baru', DB::table('kbji_jobs')->where('code', '2411.01')->value('title'));   // tidak disunting → diperbarui
        array_map('unlink', glob($dir.'/*'));
        rmdir($dir);
    }

    public function test_admin_pages_require_admin(): void
    {
        $u = User::factory()->create();
        $u->profile()->create();
        foreach (['/admin/simulator', '/admin/aturan', '/admin/laporan', '/admin/konten', '/admin/katalog/karier/2511.01'] as $url) {
            $this->actingAs($u)->get($url)->assertForbidden();
        }
        $admin = $this->admin();
        $this->flushSession();
        foreach (['/admin/simulator', '/admin/aturan', '/admin/laporan', '/admin/konten?jenis=skill', '/admin/katalog?jenis=smk', '/admin/katalog?jenis=sma'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }
}
