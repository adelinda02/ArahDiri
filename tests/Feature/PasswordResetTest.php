<?php

namespace Tests\Feature;

use App\Mail\PasswordResetCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /** Minta kode untuk email, lalu kembalikan kode yang terkirim lewat email (Mail::fake). */
    private function requestCode(string $email): ?string
    {
        $this->post('/lupa-sandi', ['email' => $email])->assertRedirect('/lupa-sandi/kode');
        $code = null;
        Mail::assertSent(PasswordResetCode::class, function (PasswordResetCode $m) use (&$code) {
            $code = $m->code;

            return true;
        });

        return $code;
    }

    public function test_pages_open_for_guests_and_link_exists(): void
    {
        $this->get('/lupa-sandi')->assertOk()->assertInertia(fn ($p) => $p->component('Auth/ForgotPassword')->where('step', 'email'));
        // Tanpa email di sesi, langkah 2 dan 3 kembali ke langkah 1.
        $this->get('/lupa-sandi/kode')->assertRedirect('/lupa-sandi');
        $this->get('/lupa-sandi/baru')->assertRedirect('/lupa-sandi');
    }

    public function test_full_reset_flow_changes_password_and_logs_out_other_devices(): void
    {
        Mail::fake();
        $u = User::factory()->create(['email' => 'linda@contoh.id', 'must_change_password' => true]);
        DB::table('sessions')->insert(['id' => 'sesi-lama', 'user_id' => $u->id, 'payload' => 'x', 'last_activity' => time()]);

        $code = $this->requestCode('  LINDA@contoh.id ');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $row = DB::table('password_reset_tokens')->where('email', 'linda@contoh.id')->first();
        $this->assertNotEquals($code, $row->token, 'kode harus disimpan ter-hash');
        $this->assertTrue(Hash::check($code, $row->token));

        $this->get('/lupa-sandi/kode')->assertOk()->assertInertia(fn ($p) => $p->where('step', 'code')->where('email', 'l***a@contoh.id'));
        $this->post('/lupa-sandi/kode', ['code' => $code])->assertRedirect('/lupa-sandi/baru');
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'linda@contoh.id']); // sekali pakai

        $this->get('/lupa-sandi/baru')->assertOk()->assertInertia(fn ($p) => $p->where('step', 'password'));
        $this->post('/lupa-sandi/baru', ['password' => 'sandi-baru-aman-1', 'password_confirmation' => 'beda'])->assertSessionHasErrors('password');
        $this->post('/lupa-sandi/baru', ['password' => 'sandi-baru-aman-1', 'password_confirmation' => 'sandi-baru-aman-1'])->assertRedirect('/masuk');

        $u->refresh();
        $this->assertTrue(Hash::check('sandi-baru-aman-1', $u->password));
        $this->assertFalse($u->mustChangePassword());
        $this->assertDatabaseMissing('sessions', ['user_id' => $u->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'password_reset', 'actor_id' => $u->id]);

        // Tidak bisa dipakai ulang tanpa verifikasi baru.
        $this->get('/lupa-sandi/baru')->assertRedirect('/lupa-sandi');

        $this->post('/masuk', ['identifier' => 'linda@contoh.id', 'password' => 'sandi-baru-aman-1'])->assertRedirect('/beranda');
        $this->assertAuthenticatedAs($u);
    }

    public function test_unknown_email_gets_same_response_without_email(): void
    {
        Mail::fake();
        $this->post('/lupa-sandi', ['email' => 'tidak.ada@contoh.id'])
            ->assertRedirect('/lupa-sandi/kode')
            ->assertSessionHas('status');
        Mail::assertNothingSent();
        $this->post('/lupa-sandi/kode', ['code' => '123456'])->assertSessionHasErrors('code');
    }

    public function test_inactive_account_does_not_receive_code(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'nonaktif@contoh.id', 'is_active' => false]);
        $this->post('/lupa-sandi', ['email' => 'nonaktif@contoh.id'])->assertRedirect('/lupa-sandi/kode');
        Mail::assertNothingSent();
    }

    public function test_wrong_code_is_limited_and_then_burns_the_code(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'budi@contoh.id']);
        $code = $this->requestCode('budi@contoh.id');
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 5; $i++) {
            $this->post('/lupa-sandi/kode', ['code' => $wrong])->assertSessionHasErrors('code');
        }
        // Setelah 5 kali salah, kode yang benar pun ditolak dan kodenya dihapus.
        $this->post('/lupa-sandi/kode', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'budi@contoh.id']);
        $this->get('/lupa-sandi/baru')->assertRedirect('/lupa-sandi');
    }

    public function test_code_expires_after_ten_minutes(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'sari@contoh.id']);
        $code = $this->requestCode('sari@contoh.id');

        $this->travel(11)->minutes();
        $this->post('/lupa-sandi/kode', ['code' => $code])->assertSessionHasErrors('code');
    }

    public function test_resend_has_cooldown(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'andi@contoh.id']);
        $this->post('/lupa-sandi', ['email' => 'andi@contoh.id']);
        $this->post('/lupa-sandi', ['email' => 'andi@contoh.id']);
        Mail::assertSentCount(1);

        // "Kirim ulang" dari halaman kode tidak mengirim email; email diambil dari sesi.
        $this->travel(61)->seconds();
        $this->post('/lupa-sandi', ['resend' => true])->assertRedirect('/lupa-sandi/kode');
        Mail::assertSentCount(2);

        // Langkah 1 dengan email kosong tetap ditolak (tidak diam-diam memakai email di sesi).
        $this->post('/lupa-sandi', ['email' => ''])->assertSessionHasErrors('email');
    }

    public function test_logged_in_user_cannot_open_reset_pages(): void
    {
        $this->actingAs(User::factory()->create())->get('/lupa-sandi')->assertRedirect('/beranda');
    }
}
