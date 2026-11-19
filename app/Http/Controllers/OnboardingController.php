<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ProgramOffering;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Mahasiswa (kampus mana pun) memilih kampus lalu prodi D3/D4/S1 yang sedang dijalani. */
class OnboardingController extends Controller
{
    /** Jenjang prodi yang bisa dievaluasi (sama dengan jenjang rekomendasi). */
    public const LEVELS = ['S1', 'D4', 'D3'];

    public function edit(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        if (! $user->isCollegeStudent()) {
            return redirect()->route('beranda');
        }

        $current = $user->profile?->currentProgram()->with('campus')->first();

        return Inertia::render('Onboarding/CurrentProgram', [
            'current' => $current ? [
                'program_offering_id' => $current->id,
                'program' => ['id' => $current->id, 'name' => $current->original_name, 'level' => $current->level],
                'campus' => ['id' => $current->campus_id, 'name' => $current->campus?->name, 'city' => $current->campus?->city],
                'semester' => $user->profile->current_semester,
            ] : null,
            'feelsMismatch' => $user->profile?->feels_mismatch,
        ]);
    }

    /** Cari kampus aktif berdasarkan nama atau kota (untuk pemilih kampus). */
    public function campuses(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q'));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';

        return response()->json(DB::table('campuses as c')->where('c.is_active', true)
            ->where(fn ($w) => $w->where('c.name', 'like', $like)->orWhere('c.city', 'like', $like))
            ->whereExists(fn ($e) => $e->from('program_offerings as o')->whereColumn('o.campus_id', 'c.id')
                ->where('o.is_active', true)->whereIn('o.level', self::LEVELS))
            ->orderByRaw('case when c.name like ? then 0 else 1 end', [str_replace(['%', '_'], ['\%', '\_'], $q).'%'])
            ->orderBy('c.name')->limit(20)
            ->get(['c.id', 'c.name', 'c.city', 'c.province', 'c.ownership']));
    }

    /** Prodi D3/D4/S1 aktif di satu kampus. */
    public function programs(int $campus): JsonResponse
    {
        return response()->json(ProgramOffering::query()->where('campus_id', $campus)->where('is_active', true)
            ->whereIn('level', self::LEVELS)->orderBy('original_name')->orderBy('level')
            ->get(['id', 'original_name as name', 'level', 'field']));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isCollegeStudent(), 403);

        $data = $request->validate([
            'program_offering_id' => ['required', 'integer', Rule::exists('program_offerings', 'id')
                ->where(fn ($q) => $q->where('is_active', true)->whereIn('level', self::LEVELS))],
            'semester' => ['required', 'integer', 'between:1,14'],
            'self_declared' => ['accepted'],
            'feels_mismatch' => ['required', 'boolean'],
        ], [
            'program_offering_id.required' => 'Pilih kampus dan prodimu.',
            'program_offering_id.exists' => 'Prodi harus prodi D3, D4, atau S1 yang aktif di katalog PDDikti.',
            'self_declared.accepted' => 'Centang pernyataan bahwa kamu mahasiswa prodi ini.',
            'feels_mismatch.required' => 'Jawab dulu: apakah kamu merasa salah jurusan?',
        ], ['semester' => 'semester']);

        $profile = $user->profile()->updateOrCreate([], [
            'current_program_offering_id' => $data['program_offering_id'],
            'current_semester' => $data['semester'],
            'current_self_declared' => true,
            'feels_mismatch' => (bool) $data['feels_mismatch'],
        ]);
        // Perbarui relasi yang sudah dimuat agar pengecekan onboarding berikutnya memakai data baru.
        $user->setRelation('profile', $profile);
        AuditLog::record('current_program_declared', $user->id, 'program_offering', (string) $data['program_offering_id']);

        return redirect()->route('beranda')->with('status', 'Prodi tersimpan.');
    }
}
