<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mahasiswa UMB wajib memilih prodi UMB-nya dulu sebelum memakai fitur lain.
 */
class EnsureOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->needsUmbOnboarding()) {
            return redirect()->route('onboarding');
        }

        return $next($request);
    }
}
