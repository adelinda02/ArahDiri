<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCode;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Lupa kata sandi (mandiri): email → kode 6 digit lewat email → kata sandi baru.
 *
 * Keamanan:
 * - Pesan sama untuk email terdaftar maupun tidak (tidak membocorkan siapa yang punya akun).
 * - Kode disimpan ter-hash di tabel bawaan password_reset_tokens, berlaku CODE_TTL menit, sekali pakai.
 * - Salah kode MAX_ATTEMPTS kali → kode hangus. Kirim ulang paling cepat RESEND_SECONDS detik.
 * - Setelah berhasil: semua sesi & "ingat saya" di perangkat lain dihapus.
 */
class PasswordResetController extends Controller
{
    public const CODE_TTL = 10;          // menit
    public const MAX_ATTEMPTS = 5;
    public const RESEND_SECONDS = 60;
    public const VERIFIED_TTL = 15;      // menit untuk mengisi kata sandi baru setelah kode benar

    private const S_EMAIL = 'password_reset.email';
    private const S_SENT = 'password_reset.sent_at';
    private const S_VERIFIED = 'password_reset.verified_at';

    /** Langkah 1: isi email. */
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'step' => 'email',
            'email' => $request->session()->get(self::S_EMAIL, ''),
        ]);
    }

    /** Kirim (atau kirim ulang) kode ke email. */
    public function send(Request $request): RedirectResponse
    {
        // "Kirim ulang" dari langkah 2 tidak mengirim email lagi; pakai email yang tersimpan di sesi.
        if ($request->boolean('resend') && $request->session()->has(self::S_EMAIL)) {
            $request->merge(['email' => $request->session()->get(self::S_EMAIL)]);
        }
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate(['email' => ['required', 'string', 'email', 'max:254']], [], ['email' => 'email']);
        $email = $data['email'];

        $user = User::query()->where('email', $email)->where('is_active', true)->first();
        $cooldownKey = 'password-reset-send:'.$email;

        if ($user && ! RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            $code = (string) random_int(100000, 999999);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $email],
                ['token' => Hash::make($code), 'created_at' => now()],
            );

            try {
                Mail::to($user->email)->send(new PasswordResetCode($user->name ?: $user->username, $code, self::CODE_TTL));
            } catch (Throwable $e) {
                report($e);
                DB::table('password_reset_tokens')->where('email', $email)->delete();

                throw ValidationException::withMessages([
                    'email' => 'Kode belum bisa dikirim saat ini. Coba lagi beberapa saat lagi atau hubungi admin ArahDiri.',
                ]);
            }

            RateLimiter::hit($cooldownKey, self::RESEND_SECONDS);
            RateLimiter::clear('password-reset-verify:'.$email);
            AuditLog::record('password_reset_requested', $user->id);
        }

        $request->session()->put(self::S_EMAIL, $email);
        $request->session()->put(self::S_SENT, now()->getTimestamp());
        $request->session()->forget(self::S_VERIFIED);

        return redirect()->route('lupa-sandi.kode')
            ->with('status', 'Jika email terdaftar, kode 6 digit sudah dikirim. Cek juga folder spam.');
    }

    /** Langkah 2: isi kode. */
    public function code(Request $request): Response|RedirectResponse
    {
        $email = $request->session()->get(self::S_EMAIL);
        if (! $email) {
            return redirect()->route('lupa-sandi');
        }
        $sentAt = (int) $request->session()->get(self::S_SENT, 0);

        return Inertia::render('Auth/ForgotPassword', [
            'step' => 'code',
            'email' => self::mask($email),
            'resend_in' => max(0, self::RESEND_SECONDS - (now()->getTimestamp() - $sentAt)),
            'ttl' => self::CODE_TTL,
        ]);
    }

    /** Cek kode. */
    public function verify(Request $request): RedirectResponse
    {
        $email = $request->session()->get(self::S_EMAIL);
        if (! $email) {
            return redirect()->route('lupa-sandi');
        }

        $request->merge(['code' => preg_replace('/\D+/', '', (string) $request->input('code'))]);
        $request->validate(['code' => ['required', 'digits:6']], ['code.digits' => 'Kode terdiri dari 6 angka.'], ['code' => 'kode']);

        $key = 'password-reset-verify:'.$email;
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            throw ValidationException::withMessages(['code' => 'Terlalu banyak percobaan. Minta kode baru.']);
        }

        $row = DB::table('password_reset_tokens')->where('email', $email)->first();
        $fresh = $row && $row->created_at && now()->subMinutes(self::CODE_TTL)->lessThan($row->created_at);

        if (! $fresh || ! Hash::check($request->input('code'), $row->token)) {
            RateLimiter::hit($key, self::CODE_TTL * 60);
            $left = self::MAX_ATTEMPTS - RateLimiter::attempts($key);

            throw ValidationException::withMessages([
                'code' => $left > 0 ? "Kode salah atau sudah kedaluwarsa. Sisa percobaan: {$left}." : 'Terlalu banyak percobaan. Minta kode baru.',
            ]);
        }

        // Kode sekali pakai.
        DB::table('password_reset_tokens')->where('email', $email)->delete();
        RateLimiter::clear($key);
        $request->session()->put(self::S_VERIFIED, now()->getTimestamp());
        $request->session()->regenerate();

        return redirect()->route('lupa-sandi.baru');
    }

    /** Langkah 3: kata sandi baru. */
    public function edit(Request $request): Response|RedirectResponse
    {
        if (! $this->verifiedEmail($request)) {
            return redirect()->route('lupa-sandi')->with('error', 'Masukkan email dan kode verifikasi dulu.');
        }

        return Inertia::render('Auth/ForgotPassword', ['step' => 'password', 'email' => self::mask($request->session()->get(self::S_EMAIL))]);
    }

    /** Simpan kata sandi baru. */
    public function update(Request $request): RedirectResponse
    {
        $email = $this->verifiedEmail($request);
        if (! $email) {
            return redirect()->route('lupa-sandi')->with('error', 'Sesi reset kata sandi sudah habis. Ulangi dari awal.');
        }

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [], ['password' => 'kata sandi baru']);

        $user = User::query()->where('email', $email)->where('is_active', true)->first();
        if (! $user) {
            $request->session()->forget('password_reset');

            return redirect()->route('lupa-sandi')->with('error', 'Akun tidak ditemukan atau tidak aktif. Hubungi admin ArahDiri.');
        }

        $user->forceFill([
            'password' => $data['password'],
            'must_change_password' => false,
            'remember_token' => Str::random(60),
        ])->save();
        DB::table('sessions')->where('user_id', $user->id)->delete(); // keluar dari semua perangkat
        AuditLog::record('password_reset', $user->id);

        $request->session()->forget('password_reset');
        $request->session()->regenerate();

        return redirect()->route('login')->with('status', 'Kata sandi baru tersimpan. Silakan masuk.');
    }

    private function verifiedEmail(Request $request): ?string
    {
        $email = $request->session()->get(self::S_EMAIL);
        $at = (int) $request->session()->get(self::S_VERIFIED, 0);

        return $email && $at && now()->getTimestamp() - $at <= self::VERIFIED_TTL * 60 ? $email : null;
    }

    /** linda@gmail.com → l***a@gmail.com */
    public static function mask(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $shown = mb_strlen($name) <= 2 ? mb_substr($name, 0, 1).'*' : mb_substr($name, 0, 1).str_repeat('*', min(5, mb_strlen($name) - 2)).mb_substr($name, -1);

        return $shown.'@'.$domain;
    }
}
