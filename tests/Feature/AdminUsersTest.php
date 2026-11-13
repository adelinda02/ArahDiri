<?php

namespace Tests\Feature;

use App\Models\TestSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Pengguna & peran editor, ringkasan kesehatan data, saringan data hasil/aktivitas, akreditasi kedaluwarsa. */
class AdminUsersTest extends TestCase
{
    use RefreshDatabase;

    private function make(string $role = 'user', array $attr = []): User
    {
        $u = User::factory()->create($attr);
        $u->profile()->create();
        if ($role !== 'user') {
            $u->forceFill(['role' => $role])->save();
        }

        return $u;
    }

    private function completed(User $u, string $code, string $level = 'sma_smk'): TestSession
    {
        $s = TestSession::create(['user_id' => $u->id, 'level_at_test' => $level, 'status' => 'completed', 'instrument_version' => 'x', 'current_item' => 50, 'started_at' => now(), 'completed_at' => now()]);
        $s->result()->create(['score_r' => 1, 'score_i' => 1, 'score_a' => 1, 'score_s' => 1, 'score_e' => 1, 'score_c' => 1, 'norm_r' => .1, 'norm_i' => .2, 'norm_a' => .3, 'norm_s' => .4, 'norm_e' => .5, 'norm_c' => .6, 'holland_code' => $code, 'scoring_version' => 'x']);

        return $s;
    }

    public function test_editor_sees_content_tools_but_not_data_or_accounts(): void
    {
        $editor = $this->make('editor', ['username' => 'gurubk']);
        $this->post('/masuk', ['identifier' => 'gurubk', 'password' => 'rahasia-panjang-123'])->assertRedirect('/admin');

        foreach (['/admin', '/admin/simulator', '/admin/laporan', '/admin/katalog', '/admin/konten'] as $url) {
            $this->get($url)->assertOk();
        }
        foreach (['/admin/hasil', '/admin/hasil/ekspor', '/admin/akun', '/admin/pengguna', '/admin/aturan', '/admin/akreditasi', '/admin/audit'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->post('/admin/katalog/impor')->assertForbidden();
        $this->get('/admin')->assertInertia(fn ($p) => $p->where('auth.user.role', 'editor')->where('auth.user.is_admin', true));
    }

    public function test_owner_grants_and_switches_editor_role(): void
    {
        $owner = $this->make('owner');
        $this->make('user', ['username' => 'calon']);
        $this->actingAs($owner)->post('/admin/akun', ['identifier' => 'calon', 'role' => 'editor'])->assertSessionHasNoErrors();
        $u = User::where('username', 'calon')->first();
        $this->assertSame('editor', $u->role);
        $this->post("/admin/akun/{$u->id}", ['action' => 'jadi_admin']);
        $this->assertSame('admin', $u->fresh()->role);

        $admin = $this->make('admin');
        $this->flushSession();
        $this->actingAs($admin)->post('/admin/akun', ['identifier' => 'siapa', 'role' => 'editor'])->assertForbidden(); // hanya owner
    }

    public function test_user_list_is_minimal_and_admin_can_deactivate(): void
    {
        $admin = $this->make('admin');
        $student = $this->make('user', ['username' => 'budi', 'email' => 'budi.santoso@contoh.id']);
        $this->completed($student, 'IAS');

        $this->actingAs($admin)->get('/admin/pengguna?q=budi.santoso@contoh.id')->assertInertia(fn ($p) => $p->component('Admin/Pengguna')
            ->has('items.data', 1)->where('items.data.0.username', 'budi')->where('items.data.0.email', 'b******@contoh.id')->where('items.data.0.tests', 1)
            ->missing('items.data.0.phone'));
        $this->get('/admin/pengguna')->assertInertia(fn ($p) => $p->has('items.data', 1)); // admin tidak ikut terdaftar

        $this->post("/admin/pengguna/{$student->id}/status")->assertSessionHasNoErrors();
        $this->assertFalse((bool) $student->fresh()->is_active);
        $this->post("/admin/pengguna/{$admin->id}/status")->assertForbidden();

        $this->flushSession();
        $this->app['auth']->forgetGuards();
        $this->post('/masuk', ['identifier' => 'budi', 'password' => 'rahasia-panjang-123'])->assertSessionHasErrors();
        $this->assertDatabaseHas('audit_logs', ['action' => 'user_deactivated', 'subject_id' => (string) $student->id]);
    }

    public function test_temporary_password_must_be_changed_on_login(): void
    {
        $admin = $this->make('admin');
        $student = $this->make('user', ['username' => 'sari']);

        $resp = $this->actingAs($admin)->post("/admin/pengguna/{$student->id}/sandi-sementara");
        $temp = session('temp_password');
        $this->assertSame('sari', $temp['username']);
        $this->assertMatchesRegularExpression('/^arah-[a-z2-9]{4}-[a-z2-9]{4}$/', $temp['password']);
        $this->assertStringNotContainsString($temp['password'], (string) DB::table('audit_logs')->where('action', 'user_temp_password')->value('meta'));

        $this->flushSession();
        $this->app['auth']->forgetGuards();
        $this->post('/masuk', ['identifier' => 'sari', 'password' => 'rahasia-panjang-123'])->assertSessionHasErrors(); // sandi lama tidak berlaku
        $this->post('/masuk', ['identifier' => 'sari', 'password' => $temp['password']])->assertRedirect('/profil');
        $this->get('/beranda')->assertRedirect('/profil');
        $this->get('/profil')->assertOk()->assertInertia(fn ($p) => $p->where('auth.user.must_change_password', true));

        $this->put('/profil/kata-sandi', ['current_password' => $temp['password'], 'password' => 'sandi-baru-sari-123', 'password_confirmation' => 'sandi-baru-sari-123'])->assertSessionHasNoErrors();
        $this->get('/beranda')->assertOk();

        $this->post("/admin/pengguna/{$admin->id}/sandi-sementara")->assertForbidden(); // sari bukan admin
    }

    public function test_dashboard_health_results_and_audit_filters(): void
    {
        $admin = $this->make('admin');
        $a = $this->make();
        $this->completed($a, 'IAS');
        $this->completed($this->make(), 'ECS', 'smp');

        $this->actingAs($admin)->get('/admin')->assertInertia(fn ($p) => $p->has('health', 5)->where('health.0.key', 'banpt')->where('health.0.level', 'bad')
            ->where('completed_by_level.0.n', 1)->where('completed_by_level.1.n', 1));

        $this->get('/admin/hasil?kode=i')->assertInertia(fn ($p) => $p->where('total', 1)->where('items.data.0.code', 'IAS'));
        $this->get('/admin/hasil?kode=EC&jenjang=smp')->assertInertia(fn ($p) => $p->where('total', 1));
        $this->assertStringNotContainsString('IAS', $this->get('/admin/hasil/ekspor?kode=E')->streamedContent());

        $this->post("/admin/pengguna/{$a->id}/status");
        $this->get('/admin/audit?aksi=user_deactivated')->assertInertia(fn ($p) => $p->has('items.data', 1)->where('actors.0.id', $admin->id));
        $this->get('/admin/audit?aksi=results_exported')->assertInertia(fn ($p) => $p->has('items.data', 1));
    }

    public function test_expired_accreditations_are_listed(): void
    {
        $admin = $this->make('admin');
        $now = now();
        $c = DB::table('campuses')->insertGetId(['pt_code' => '001001', 'name' => 'Universitas A', 'created_at' => $now, 'updated_at' => $now]);
        $f = DB::table('program_families')->insertGetId(['key' => 'gizi|S1', 'name' => 'Gizi', 'level' => 'S1', 'created_at' => $now, 'updated_at' => $now]);
        $o = DB::table('program_offerings')->insertGetId(['campus_id' => $c, 'program_family_id' => $f, 'program_code' => '1', 'original_name' => 'Gizi', 'level' => 'S1', 'created_at' => $now, 'updated_at' => $now]);
        $acc = fn ($type, $id, $exp) => DB::table('accreditations')->insert(['subject_type' => $type, 'subject_id' => $id, 'rating_raw' => 'Baik', 'rating_score' => 4, 'expires_at' => $exp, 'fetched_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        $acc('program_offering', $o, now()->subDays(10)->toDateString());
        $acc('campus', $c, now()->addDays(20)->toDateString());

        $this->actingAs($admin)->get('/admin/akreditasi?tampil=kedaluwarsa')->assertInertia(fn ($p) => $p->where('tab', 'kedaluwarsa')
            ->has('expiry.data', 1)->where('expiry.data.0.program', 'Gizi')->where('expiry.data.0.campus', 'Universitas A')->where('expiryCounts.30', 1));
        $this->get('/admin/akreditasi?tampil=kedaluwarsa&habis=30&jenis=kampus')->assertInertia(fn ($p) => $p->has('expiry.data', 1)->where('expiry.data.0.subject_type', 'campus'));
    }
}
