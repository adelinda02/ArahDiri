<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\KbjiJob;
use App\Models\ProgramFamily;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Pencarian header: saran cepat (JSON) dan halaman hasil lengkap. */
class SearchController extends Controller
{
    public function suggest(Request $request): JsonResponse
    {
        return response()->json($this->search((string) $request->query('q'), 4));
    }

    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q'));

        return Inertia::render('Cari', ['q' => $q, 'results' => $this->search($q, 30)]);
    }

    private function search(string $q, int $limit): array
    {
        $q = trim(mb_substr($q, 0, 80));
        if (mb_strlen($q) < 2) {
            return ['programs' => [], 'careers' => [], 'campuses' => []];
        }
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q).'%';

        $programs = ProgramFamily::query()->where('is_recommendable', true)->where('name', 'like', $like)
            ->withCount('offerings')->orderByDesc('offerings_count')->limit($limit)->get()
            ->map(fn ($m) => [
                'key' => $m->key, 'name' => $m->name.' ('.$m->level.')',
                'sub' => $m->offerings_count.' kampus · minat '.($m->holland_code ?? '-'),
                'tag' => $m->field ?? 'Prodi', 'url' => '/jurusan?prodi='.urlencode($m->key),
            ]);

        $careers = KbjiJob::query()->where('is_active', true)
            ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('subgroup_title', 'like', $like))
            ->orderByRaw('holland_code is null')->orderBy('title')->limit($limit)->get()
            ->map(fn ($c) => [
                'code' => $c->code, 'name' => $c->title,
                'sub' => 'KBJI '.$c->code.' · '.($c->subgroup_title ?? ''),
                'url' => '/karier?kbji='.urlencode($c->code),
            ]);

        $campuses = Campus::query()->where('is_active', true)->where('name', 'like', $like)
            ->orderByDesc('is_state')->orderBy('name')->limit($limit)->get()
            ->map(fn ($c) => [
                'code' => $c->pt_code, 'name' => $c->name,
                'sub' => trim(($c->city ?? '').' · '.($c->province ?? ''), ' ·'),
                'tag' => $c->group ?? 'PT', 'url' => '/kampus?pt='.$c->pt_code,
            ]);

        return ['programs' => $programs->values(), 'careers' => $careers->values(), 'campuses' => $campuses->values()];
    }
}
