<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/** Profil & pengaturan akun (v14 "Pusat Pengaturan Akun"). */
class ProfileController extends Controller
{
    public const AVATARS = ['avatar1', 'avatar2', 'avatar3', 'avatar4', 'avatar5', 'avatar6'];

    public function show(Request $request): Response
    {
        $u = $request->user()->load('profile.umbProgram');
        $r = $u->latestResult();

        return Inertia::render('Profil', [
            'account' => [
                'name' => $u->name, 'username' => $u->username, 'email' => $u->email, 'phone' => $u->phone,
                'level' => $u->level, 'level_label' => config('arahdiri.levels.'.$u->level),
                'school' => $u->profile?->school_name, 'bio' => $u->profile?->bio,
                'avatar' => $u->profile?->avatar ?: 'avatar1',
                'umb_program' => $u->profile?->umbProgram?->original_name,
                'password_changed_at' => $u->updated_at?->toIso8601String(),
            ],
            'result' => $r ? ['code' => $r->holland_code] : null,
            'levels' => config('arahdiri.levels'),
            'avatars' => self::AVATARS,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $u = $request->user();
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email'))), 'phone' => User::normalizePhone($request->input('phone'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'email', 'max:254', Rule::unique('users')->ignore($u->id)],
            'phone' => ['required', 'regex:/^08\d{8,12}$/', Rule::unique('users')->ignore($u->id)],
            'level' => ['required', Rule::in(array_keys(config('arahdiri.levels')))],
            'school' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:160'],
            'avatar' => ['required', Rule::in(self::AVATARS)],
        ], ['phone.regex' => 'Nomor HP harus nomor Indonesia, contoh 081234567890.'],
            ['name' => 'nama', 'phone' => 'nomor HP', 'level' => 'jenjang', 'school' => 'asal sekolah', 'bio' => 'bio']);

        $levelChanged = $u->level !== $data['level'];
        $u->fill(['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'], 'level' => $data['level']])->save();
        $u->profile()->updateOrCreate([], ['school_name' => $data['school'], 'bio' => $data['bio'], 'avatar' => $data['avatar']]);
        AuditLog::record('profile_updated', $u->id, meta: $levelChanged ? ['level' => $data['level']] : []);

        if ($levelChanged && $u->fresh()->needsUmbOnboarding()) {
            return redirect()->route('onboarding');
        }

        return back()->with('status', $levelChanged ? 'Profil tersimpan. Kerjakan tes ulang untuk rekomendasi jenjang barumu.' : 'Profil tersimpan.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults(), 'different:current_password'],
        ], [], ['current_password' => 'kata sandi saat ini', 'password' => 'kata sandi baru']);

        $request->user()->forceFill(['password' => $data['password'], 'must_change_password' => false])->save();
        Auth::logoutOtherDevices($data['password']);
        AuditLog::record('password_changed', $request->user()->id);

        return back()->with('status', 'Kata sandi diperbarui. Perangkat lain otomatis keluar.');
    }

    /** Keluar dari semua perangkat lain. */
    public function logoutOthers(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']], [], ['password' => 'kata sandi']);
        Auth::logoutOtherDevices($request->input('password'));

        return back()->with('status', 'Semua perangkat lain sudah keluar.');
    }

    /** Hapus akun beserta seluruh hasil tes (spesifikasi bagian 10). */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']], [], ['password' => 'kata sandi']);
        $u = $request->user();
        abort_if($u->role === 'owner', 403, 'Akun owner tidak bisa dihapus dari sini.');

        AuditLog::record('account_deleted', null, 'user', (string) $u->id);
        Auth::logout();
        $u->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landing')->with('status', 'Akun dan seluruh hasil tes sudah dihapus.');
    }
}
