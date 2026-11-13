<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Pengguna yang masuk dengan kata sandi sementara dari admin harus menggantinya dulu. */
class EnsurePasswordChanged
{
    private const ALLOWED = ['profil', 'profil.password', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user?->mustChangePassword() && ! $request->routeIs(...self::ALLOWED)) {
            return redirect()->route('profil')->with('error', 'Kamu masuk dengan kata sandi sementara. Ganti kata sandi dulu di Pengaturan Akun.');
        }

        return $next($request);
    }
}
