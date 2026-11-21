<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pengguna (siswa/mahasiswa) untuk admin: data minimal saja. Jawaban, skor, dan rekomendasi
 * per orang tidak ditampilkan di sini. Email dan HP disamarkan; pencarian boleh memakai nilai lengkap
 * supaya admin bisa menemukan akun yang meminta bantuan (mis. lupa kata sandi).
 */
class UserAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q'));
        $status = in_array($request->query('status'), ['aktif', 'nonaktif'], true) ? $request->query('status') : null;
        $level = array_key_exists((string) $request->query('jenjang'), config('arahdiri.levels')) ? $request->query('jenjang') : null;

        $items = User::query()->where('role', 'user')
            ->when($q, fn ($w) => $w->where(fn ($x) => $x->where('username', 'like', mb_strtolower($q).'%')->orWhere('name', 'like', '%'.$q.'%')
                ->orWhere('email', mb_strtolower($q))->orWhere('phone', User::normalizePhone($q))))
            ->when($status, fn ($w) => $w->where('is_active', $status === 'aktif'))
            ->when($level, fn ($w) => $w->where('level', $level))
            ->withCount(['testSessions as tests' => fn ($t) => $t->where('status', 'completed')])
            ->withMax(['testSessions as last_test' => fn ($t) => $t->where('status', 'completed')], 'completed_at')
            ->latest('id')->paginate(30)->withQueryString()
            ->through(fn (User $u) => [
                'id' => $u->id, 'name' => $u->name, 'username' => $u->username,
                'email' => self::mask($u->email), 'level' => config('arahdiri.levels.'.$u->level), 'level_key' => $u->level,
                'joined' => $u->created_at, 'tests' => (int) $u->tests, 'last_test' => $u->last_test,
                'active' => (bool) $u->is_active, 'temp' => $u->mustChangePassword(),
            ]);

        return Inertia::render('Admin/Pengguna', [
            'items' => $items,
            'filters' => ['q' => $q, 'status' => $status, 'jenjang' => $level],
            'levels' => config('arahdiri.levels'),
            'totals' => [
                'all' => User::query()->where('role', 'user')->count(),
                'inactive' => User::query()->where('role', 'user')->where('is_active', false)->count(),
            ],
        ]);
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'user', 403); // akun admin/editor dikelola di menu Akun admin
        $user->forceFill(['is_active' => ! $user->is_active])->save();
        if (! $user->is_active) {
            DB::table('sessions')->where('user_id', $user->id)->delete(); // langsung keluar dari semua perangkat
        }
        AuditLog::record($user->is_active ? 'user_activated' : 'user_deactivated', $request->user()->id, 'user', (string) $user->id);

        return back()->with('status', $user->is_active ? "Akun {$user->username} diaktifkan lagi." : "Akun {$user->username} dinonaktifkan dan dikeluarkan dari semua perangkat.");
    }

    /** Buat kata sandi sementara (ditampilkan sekali ke admin); pengguna wajib menggantinya saat masuk. */
    public function temporaryPassword(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'user', 403);
        // Tanpa huruf/angka yang mirip (0/O, 1/l) agar mudah didiktekan.
        $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';
        $plain = 'arah-'.collect(range(1, 4))->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])->implode('')
            .'-'.collect(range(1, 4))->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])->implode('');

        $user->forceFill(['password' => $plain, 'must_change_password' => true, 'remember_token' => Str::random(60)])->save();
        DB::table('sessions')->where('user_id', $user->id)->delete();
        AuditLog::record('user_temp_password', $request->user()->id, 'user', (string) $user->id); // kata sandinya tidak dicatat

        return back()->with('temp_password', ['username' => $user->username, 'password' => $plain]);
    }

    /**
     * Ubah jenjang atas permintaan pemilik akun (jenjang dikunci di Edit Profil untuk pengguna biasa).
     * Hasil tes lama tetap tersimpan dengan jenjang saat tes; rekomendasi jenjang baru muncul setelah tes ulang.
     */
    public function level(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'user', 403);
        $data = $request->validate(['level' => ['required', Rule::in(array_keys(config('arahdiri.levels')))]], [], ['level' => 'jenjang']);

        if ($user->level === $data['level']) {
            return back()->with('status', "Jenjang {$user->username} tidak berubah.");
        }
        $from = $user->level;
        $user->forceFill(['level' => $data['level']])->save();
        AuditLog::record('user_level_changed', $request->user()->id, 'user', (string) $user->id, ['dari' => $from, 'ke' => $data['level']]);

        return back()->with('status', "Jenjang {$user->username} diubah ke ".config('arahdiri.levels.'.$data['level']).'. Hasil lama tetap tersimpan; ia perlu tes ulang untuk rekomendasi jenjang baru.');
    }

    /** a***@gmail.com — cukup untuk mengenali, tidak membuka alamat lengkap. */
    public static function mask(?string $email): string
    {
        if (! $email || ! str_contains($email, '@')) {
            return '-';
        }
        [$local, $domain] = explode('@', $email, 2);

        return mb_substr($local, 0, 1).str_repeat('*', max(2, min(6, mb_strlen($local) - 1))).'@'.$domain;
    }
}
