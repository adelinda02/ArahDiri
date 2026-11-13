<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use App\Models\Campus;
use App\Models\ProgramOffering;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Tangkap lazy loading dan atribut yang hilang selama pengembangan.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Nama pendek di kolom subject_type tabel accreditations.
        Relation::enforceMorphMap([
            'campus' => Campus::class,
            'program_offering' => ProgramOffering::class,
        ]);

        // Dibaca di sini (bukan bootstrap/app.php) karena .env baru dimuat setelah middleware disusun.
        if ($proxies = config('arahdiri.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        // Aturan kata sandi sama dengan v14: minimal 10 karakter.
        Password::defaults(fn () => Password::min(10)->max(128));

        RateLimiter::for('login', function (Request $request) {
            $id = mb_strtolower((string) $request->input('identifier'));

            return [
                Limit::perMinute(5)->by($id.'|'.$request->ip()),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });
        RateLimiter::for('register', fn (Request $request) => Limit::perHour(10)->by($request->ip()));
        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(90)->by($request->ip()));
    }
}
