<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Data bersama untuk semua halaman.
     * 'view' = nama route, dipakai cangkang v14 untuk menandai menu aktif
     * dan menyembunyikan navigasi bawah di halaman masuk/daftar/awal.
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'level' => $user->level,
                    'level_label' => config('arahdiri.levels.'.$user->level),
                    'role' => $user->role,
                    'is_admin' => $user->canEnterAdmin(),
                    'must_change_password' => $user->mustChangePassword(),
                    'email' => $user->email,
                    'favorites_count' => fn () => $user->favorites()->count(),
                ] : null,
            ],
            // Lencana jumlah laporan siswa baru di sidebar admin (hanya dihitung di halaman admin).
            'admin' => $user?->canEnterAdmin() && $request->is('admin*')
                ? fn () => ['reports_new' => \App\Models\ResultReport::query()->where('status', 'baru')->count()] : null,
            'view' => \Illuminate\Support\Str::before($request->route()?->getName() ?? '', '.'),
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                'error' => fn () => $request->session()->get('error'),
                'temp_password' => fn () => $request->session()->get('temp_password'),
            ],
        ];
    }
}
