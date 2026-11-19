<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mahasiswa wajib memilih kampus dan prodi yang sedang dijalani sebelum memakai fitur lain.
 */
class EnsureOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->needsProgramOnboarding()) {
            return redirect()->route('onboarding');
        }

        return $next($request);
    }
}
