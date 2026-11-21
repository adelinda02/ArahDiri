<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Admin mengubah jenjang pengguna (jenjang dikunci di Edit Profil untuk pengguna biasa). */
class AdminUserLevelTest extends TestCase
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

    public function test_admin_changes_user_level_and_it_is_audited(): void
    {
        $admin = $this->make('admin');
        $user = $this->make('user', ['level' => 'smp']);

        $this->actingAs($admin)->post("/admin/pengguna/{$user->id}/jenjang", ['level' => 'sma_smk'])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('sma_smk', $user->fresh()->level);
        $this->assertTrue(AuditLog::where('action', 'user_level_changed')->where('subject_id', (string) $user->id)->exists());
    }

    public function test_invalid_level_is_rejected(): void
    {
        $admin = $this->make('admin');
        $user = $this->make('user', ['level' => 'smp']);

        $this->actingAs($admin)->post("/admin/pengguna/{$user->id}/jenjang", ['level' => 'bukan-jenjang'])->assertSessionHasErrors('level');
        $this->assertSame('smp', $user->fresh()->level);
    }

    // Satu tes per peran: berganti actingAs dalam satu tes membuat sesi dianggap milik akun lain
    // (authenticateSessions) sehingga yang diuji jadi redirect ke halaman masuk, bukan aturan aksesnya.
    public function test_editor_cannot_change_level(): void
    {
        $user = $this->make('user', ['level' => 'smp']);

        $this->actingAs($this->make('editor'))->post("/admin/pengguna/{$user->id}/jenjang", ['level' => 'sma_smk'])->assertForbidden();
        $this->assertSame('smp', $user->fresh()->level);
    }

    public function test_regular_user_cannot_change_level(): void
    {
        $user = $this->make('user', ['level' => 'smp']);

        $this->actingAs($this->make('user'))->post("/admin/pengguna/{$user->id}/jenjang", ['level' => 'sma_smk'])->assertForbidden();
        $this->assertSame('smp', $user->fresh()->level);
    }

    public function test_admin_accounts_are_not_changed_here(): void
    {
        $admin = $this->make('admin');
        $other = $this->make('editor', ['level' => 'smp']);

        $this->actingAs($admin)->post("/admin/pengguna/{$other->id}/jenjang", ['level' => 'sma_smk'])->assertForbidden();
    }
}
