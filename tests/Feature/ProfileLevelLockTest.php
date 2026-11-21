<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Jenjang pendidikan dipilih saat daftar; pengguna biasa tidak bisa mengubahnya lewat Edit Profil. */
class ProfileLevelLockTest extends TestCase
{
    use RefreshDatabase;

    private function make(string $role = 'user'): User
    {
        $u = User::factory()->create(['level' => User::LEVEL_SMA_SMK]);
        $u->profile()->create();
        if ($role !== 'user') {
            $u->forceFill(['role' => $role])->save();
        }

        return $u;
    }

    private function payload(User $u, string $level): array
    {
        return ['name' => 'Nama Baru', 'email' => $u->email, 'phone' => $u->phone, 'level' => $level, 'school' => 'SMA 1', 'bio' => '', 'avatar' => 'avatar1'];
    }

    public function test_regular_user_cannot_change_level_but_other_fields_save(): void
    {
        $u = $this->make();
        $this->actingAs($u)->put('/profil', $this->payload($u, User::LEVEL_MHS))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $u->refresh();
        $this->assertSame(User::LEVEL_SMA_SMK, $u->level);
        $this->assertSame('Nama Baru', $u->name);
    }

    public function test_admin_can_still_change_level(): void
    {
        $admin = $this->make('admin');
        $this->actingAs($admin)->put('/profil', $this->payload($admin, User::LEVEL_SMP))->assertSessionHasNoErrors();
        $this->assertSame(User::LEVEL_SMP, $admin->fresh()->level);
    }
}
