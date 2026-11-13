<?php

namespace Tests\Feature;

use App\Models\TestSession;
use App\Models\User;
use App\Services\RiasecScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Stage4Test extends TestCase
{
    use RefreshDatabase;

    private function finishedTest(User $u): TestSession
    {
        $answers = [];
        foreach (config('riasec.items') as $no => [$d]) {
            $answers[$no] = ['R' => 2, 'I' => 5, 'A' => 4, 'S' => 3, 'E' => 2, 'C' => 1][$d];
        }
        $this->actingAs($u)->post('/tes/mulai');
        $s = $u->testSessions()->latest('started_at')->first();
        $this->post("/tes/{$s->id}/selesai", ['answers' => $answers]);

        return $s->fresh();
    }

    private function user(array $attr = []): User
    {
        $u = User::factory()->create($attr);
        $u->profile()->create();

        return $u;
    }

    public function test_history_lists_and_deletes_only_own_sessions(): void
    {
        $u = $this->user();
        $s = $this->finishedTest($u);
        $this->get('/riwayat')->assertOk()->assertInertia(fn ($p) => $p->component('Riwayat')->has('items', 1)->where('items.0.code', 'IAS'));

        $other = $this->user();
        $this->flushSession();
        $this->actingAs($other)->delete("/riwayat/{$s->id}")->assertNotFound();

        $this->flushSession();
        $this->actingAs($u)->delete("/riwayat/{$s->id}")->assertRedirect();
        $this->assertDatabaseMissing('test_sessions', ['id' => $s->id]);
        $this->assertDatabaseCount('test_results', 0);
    }

    public function test_print_view_is_private(): void
    {
        $u = $this->user();
        $s = $this->finishedTest($u);
        $this->get("/hasil/{$s->id}/cetak")->assertOk()->assertSee('Laporan Hasil Tes Minat RIASEC')->assertSee('IAS');
        $this->flushSession();
        $this->actingAs($this->user())->get("/hasil/{$s->id}/cetak")->assertNotFound();
    }

    public function test_favorites_toggle(): void
    {
        $u = $this->user();
        DB::table('kbji_jobs')->insert(['code' => '2511.01', 'title' => 'Analis Sistem', 'official_title' => 'Analis Sistem', 'subgroup_code' => '2511', 'major_group' => '2', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($u)->postJson('/favorit', ['kind' => 'career', 'target_id' => '2511.01'])->assertJson(['favorite' => true, 'count' => 1]);
        $this->get('/favorit')->assertInertia(fn ($p) => $p->has('careers', 1));
        $this->postJson('/favorit', ['kind' => 'career', 'target_id' => '2511.01'])->assertJson(['favorite' => false]);
        $this->postJson('/favorit', ['kind' => 'career', 'target_id' => '9999.99'])->assertNotFound();
    }

    public function test_development_targets_and_custom_target(): void
    {
        $u = $this->user();
        $this->finishedTest($u);
        $this->get('/langkah')->assertInertia(fn ($p) => $p->component('Langkah')->has('targets', 6)->where('targets.0.id', 'akd-riset-analisis'));

        $this->post('/langkah/akd-riset-analisis/centang');
        $this->assertDatabaseHas('development_checks', ['user_id' => $u->id, 'target_key' => 'akd-riset-analisis', 'done' => true]);
        $this->post('/langkah', ['title' => 'Ikut lomba karya tulis', 'category' => 'akademik']);
        $this->get('/langkah')->assertInertia(fn ($p) => $p->has('targets', 7));
    }

    public function test_profile_update_password_and_delete(): void
    {
        $u = $this->user(['username' => 'budi', 'email' => 'budi@contoh.id']);
        $this->actingAs($u)->put('/profil', ['name' => 'Budi S', 'email' => 'budi@contoh.id', 'phone' => '081234567890', 'level' => 'sma_smk', 'school' => 'SMA 1', 'bio' => '', 'avatar' => 'avatar3'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('student_profiles', ['user_id' => $u->id, 'avatar' => 'avatar3', 'school_name' => 'SMA 1']);

        $this->put('/profil/kata-sandi', ['current_password' => 'salah-sekali-x', 'password' => 'baru-rahasia-123', 'password_confirmation' => 'baru-rahasia-123'])->assertSessionHasErrors('current_password');
        $this->put('/profil/kata-sandi', ['current_password' => 'rahasia-panjang-123', 'password' => 'baru-rahasia-123', 'password_confirmation' => 'baru-rahasia-123'])->assertSessionHasNoErrors();

        $this->finishedTest($u->fresh());
        $this->flushSession();
        $this->actingAs($u->fresh())->delete('/profil', ['password' => 'baru-rahasia-123'])->assertRedirect('/');
        $this->assertDatabaseMissing('users', ['id' => $u->id]);
        $this->assertDatabaseCount('test_results', 0);
        $this->assertGuest();
    }

    public function test_public_catalogs_render(): void
    {
        $now = now();
        $c = DB::table('campuses')->insertGetId(['pt_code' => '001001', 'name' => 'Universitas A', 'province' => 'Prov. Jawa Barat', 'created_at' => $now, 'updated_at' => $now]);
        $f = DB::table('program_families')->insertGetId(['key' => 'psikologi|S1', 'name' => 'Psikologi', 'level' => 'S1', 'field' => 'Sosial', 'created_at' => $now, 'updated_at' => $now]);
        DB::table('program_offerings')->insert(['campus_id' => $c, 'program_family_id' => $f, 'program_code' => '73201', 'original_name' => 'Psikologi', 'level' => 'S1', 'created_at' => $now, 'updated_at' => $now]);

        $this->get('/jurusan?q=psiko')->assertOk()->assertInertia(fn ($p) => $p->component('Katalog/Jurusan')->has('items', 1)->where('detail', null));
        $this->get("/jurusan/{$f}")->assertInertia(fn ($p) => $p->where('detail.name', 'Psikologi')->has('detail.campuses', 1));
        $this->get('/kampus/001001')->assertInertia(fn ($p) => $p->where('detail.name', 'Universitas A'));
        $this->get('/karier')->assertOk();
        $this->get('/program-smk')->assertOk();
        $this->get('/referensi')->assertOk();
        $this->get('/privasi')->assertOk();
    }

    public function test_admin_area_requires_admin_and_export_is_anonymous(): void
    {
        $u = $this->user(['username' => 'siswa', 'email' => 'siswa@contoh.id']);
        $this->finishedTest($u);
        $this->get('/admin')->assertForbidden();

        $admin = $this->user();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->flushSession();
        $this->actingAs($admin)->get('/admin')->assertOk()->assertInertia(fn ($p) => $p->component('Admin/Dashboard')->where('stats.completed', 1));
        $csv = $this->get('/admin/hasil/ekspor')->assertOk()->streamedContent();
        $this->assertStringContainsString('kode_holland', $csv);
        $this->assertStringContainsString('IAS', $csv);
        $this->assertStringNotContainsString('siswa@contoh.id', $csv);
        $this->assertStringNotContainsString('jawaban_1', $csv);
        // Jawaban mentah hanya untuk owner.
        $this->assertStringNotContainsString('jawaban_1', $this->get('/admin/hasil/ekspor?jawaban=1')->streamedContent());
        $admin->forceFill(['role' => 'owner'])->save();
        $this->assertStringContainsString('jawaban_1', $this->actingAs($admin->fresh())->get('/admin/hasil/ekspor?jawaban=1')->streamedContent());
        $this->assertDatabaseHas('audit_logs', ['action' => 'results_exported']);

        foreach (['/admin/hasil', '/admin/akreditasi', '/admin/katalog', '/admin/konten', '/admin/akun', '/admin/audit'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_admin_can_pair_unmatched_campus_and_edit_content(): void
    {
        $admin = $this->user();
        $admin->forceFill(['role' => 'admin'])->save();
        DB::table('campuses')->insert(['pt_code' => '201040', 'name' => 'Universitas Islam Negeri Sunan Kudus', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('accreditation_unmatched')->insert(['source' => 'institusi', 'campus_name' => 'IAIN Kudus Lama', 'raw' => '{}', 'fetched_at' => now(), 'resolved' => false, 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($admin)->post('/admin/akreditasi/pasangan', ['banpt_name' => 'IAIN Kudus Lama', 'pt_code' => '201040'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('banpt_aliases', ['name_key' => 'iain kudus lama']);
        $this->assertDatabaseHas('accreditation_unmatched', ['campus_name' => 'IAIN Kudus Lama', 'resolved' => true]);

        $this->post('/admin/konten', ['kind' => 'copy', 'slug' => 'hero-title', 'version' => 0, 'title' => 'Judul halaman awal', 'body' => 'Judul baru.', 'status' => 'active'])->assertSessionHasNoErrors();
        $this->get('/')->assertInertia(fn ($p) => $p->where('copy.hero-title', 'Judul baru.'));
    }
}
