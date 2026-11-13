<?php

namespace App\Http\Controllers;

use App\Support\DevTargets;
use App\Support\Riasec;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Rencana pengembangan diri (v14 "Panduan Pengembangan Diri"). */
class DevelopmentController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $r = $user->latestResult();
        $rank = $norm = [];
        if ($r) {
            foreach (Riasec::ORDER as $d) {
                $norm[strtoupper($d)] = (float) $r->{'norm_'.$d};
            }
            $raw = array_map(fn ($d) => (int) $r->{'score_'.$d}, array_combine(Riasec::ORDER, Riasec::ORDER));
            $rank = array_map('strtoupper', Riasec::rank(array_change_key_case($norm), $raw));
        }

        $checks = $user->developmentChecks()->get()->keyBy('target_key');
        $targets = collect(DevTargets::for($user->level, $rank, $norm))
            ->map(fn ($t) => $t + ['done' => (bool) ($checks[$t['id']]->done ?? false), 'custom' => false]);
        $custom = $checks->whereNotNull('custom_title')->values()
            ->map(fn ($c) => ['id' => $c->target_key, 'cat' => $c->category ?? 'akademik', 'text' => $c->custom_title, 'hint' => 'Target buatanmu', 'done' => $c->done, 'custom' => true]);

        return Inertia::render('Langkah', [
            'code' => $r?->holland_code,
            'top3' => array_slice($rank, 0, 3),
            'targets' => $targets->concat($custom)->values(),
            'activities' => \App\Models\ContentItem::listOf('activity'),
        ]);
    }

    public function toggle(Request $request, string $key): RedirectResponse
    {
        abort_unless(preg_match('/^[a-z0-9-]{3,64}$/', $key), 404);
        $check = $request->user()->developmentChecks()->firstOrNew(['target_key' => $key]);
        $check->done = ! $check->done;
        $check->save();

        return back();
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:160'],
            'category' => ['required', Rule::in(['akademik', 'softskill', 'karier'])],
        ], [], ['title' => 'target', 'category' => 'kategori']);

        $request->user()->developmentChecks()->create([
            'target_key' => 'own-'.Str::lower(Str::random(10)),
            'custom_title' => $data['title'], 'category' => $data['category'], 'done' => false,
        ]);

        return back()->with('status', 'Target baru ditambahkan.');
    }

    public function destroy(Request $request, string $key): RedirectResponse
    {
        $request->user()->developmentChecks()->where('target_key', $key)->whereNotNull('custom_title')->delete();

        return back()->with('status', 'Target dihapus.');
    }
}
