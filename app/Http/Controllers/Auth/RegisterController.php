<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register', ['levels' => config('arahdiri.levels')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'username' => mb_strtolower(trim((string) $request->input('username'))),
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            'phone' => User::normalizePhone($request->input('phone')),
        ]);

        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[a-z0-9._]+$/', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:254', 'unique:users,email'],
            'phone' => ['required', 'regex:/^08\d{8,12}$/', 'unique:users,phone'],
            'level' => ['required', Rule::in(array_keys(config('arahdiri.levels')))],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'username.regex' => 'Nama pengguna hanya boleh huruf kecil, angka, titik, dan garis bawah.',
            'phone.regex' => 'Nomor HP harus nomor Indonesia, contoh 081234567890.',
        ], [
            'username' => 'nama pengguna', 'email' => 'email', 'phone' => 'nomor HP', 'level' => 'jenjang', 'password' => 'kata sandi',
        ]);

        // Nama tampilan awal = nama pengguna; bisa diubah di halaman profil.
        $user = User::create([...$data, 'name' => $data['username']]);
        $user->profile()->create();

        AuditLog::record('register', $user->id, meta: ['level' => $user->level]);
        Auth::login($user);
        $request->session()->regenerate();

        return $user->needsProgramOnboarding()
            ? redirect()->route('onboarding')
            : redirect()->route('beranda')->with('status', 'Akun berhasil dibuat. Selamat datang!');
    }
}
