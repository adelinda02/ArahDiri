<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Favorit: prodi (id kelompok prodi), karier (kode KBJI), kampus (kode PT). */
class FavoriteController extends Controller
{
    public const KINDS = ['program', 'career', 'campus'];

    public function index(Request $request): Response
    {
        $fav = $request->user()->favorites()->latest()->get()->groupBy('kind');
        $ids = fn ($k) => $fav->get($k, collect())->pluck('target_id');

        $programs = DB::table('program_families')->whereIn('id', $ids('program'))
            ->get(['id', 'name', 'level', 'field', 'holland_code'])
            ->map(fn ($p) => ['id' => (string) $p->id, 'name' => $p->name, 'sub' => trim($p->level.' · '.($p->field ?? ''), ' ·'), 'code' => $p->holland_code, 'url' => '/jurusan/'.$p->id]);
        $careers = DB::table('kbji_jobs')->whereIn('code', $ids('career'))
            ->get(['code', 'title', 'subgroup_title', 'holland_code'])
            ->map(fn ($c) => ['id' => $c->code, 'name' => $c->title, 'sub' => 'KBJI '.$c->code.' · '.($c->subgroup_title ?? ''), 'code' => $c->holland_code, 'url' => '/karier/'.$c->code]);
        $campuses = DB::table('campuses')->whereIn('pt_code', $ids('campus'))
            ->get(['pt_code', 'name', 'city', 'province'])
            ->map(fn ($c) => ['id' => $c->pt_code, 'name' => $c->name, 'sub' => trim(($c->city ?? '').', '.($c->province ?? ''), ', '), 'code' => null, 'url' => '/kampus/'.$c->pt_code]);

        return Inertia::render('Favorit', ['programs' => $programs->values(), 'careers' => $careers->values(), 'campuses' => $campuses->values()]);
    }

    /** Tambah/hapus favorit. Dipanggil dengan fetch (JSON) atau formulir Inertia. */
    public function toggle(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(self::KINDS)],
            'target_id' => ['required', 'string', 'max:64'],
        ]);
        $exists = match ($data['kind']) {
            'program' => DB::table('program_families')->where('id', $data['target_id'])->exists(),
            'career' => DB::table('kbji_jobs')->where('code', $data['target_id'])->exists(),
            'campus' => DB::table('campuses')->where('pt_code', $data['target_id'])->exists(),
        };
        abort_unless($exists, 404);

        $user = $request->user();
        $fav = $user->favorites()->where($data)->first();
        $fav ? $fav->delete() : $user->favorites()->create($data);
        $on = ! $fav;

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['favorite' => $on, 'count' => $user->favorites()->count()]);
        }

        return back()->with('status', $on ? 'Disimpan ke favorit.' : 'Dihapus dari favorit.');
    }

    /** @return array<string, list<string>> favorit pengguna per jenis, untuk menandai tombol hati */
    public static function mapFor(?\App\Models\User $user): array
    {
        if (! $user) {
            return [];
        }

        return Favorite::query()->where('user_id', $user->id)->get(['kind', 'target_id'])
            ->groupBy('kind')->map(fn ($g) => $g->pluck('target_id')->all())->all();
    }
}
