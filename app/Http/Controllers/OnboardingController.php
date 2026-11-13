<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Campus;
use App\Models\ProgramOffering;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Mahasiswa UMB memilih prodi D3/S1 UMB yang sedang dijalani. */
class OnboardingController extends Controller
{
    public function edit(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        if (! $user->isUmbStudent()) {
            return redirect()->route('beranda');
        }

        return Inertia::render('Onboarding/UmbProgram', [
            'programs' => $this->umbPrograms()->map(fn (ProgramOffering $p) => [
                'id' => $p->id, 'name' => $p->original_name, 'level' => $p->level, 'field' => $p->field,
            ])->values(),
            'current' => $user->profile ? [
                'program_offering_id' => $user->profile->umb_program_offering_id,
                'semester' => $user->profile->umb_semester,
            ] : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isUmbStudent(), 403);

        $data = $request->validate([
            'program_offering_id' => ['required', 'integer', Rule::in($this->umbPrograms()->pluck('id')->all())],
            'semester' => ['required', 'integer', 'between:1,14'],
            'self_declared' => ['accepted'],
        ], [
            'program_offering_id.required' => 'Pilih prodi UMB-mu.',
            'program_offering_id.in' => 'Prodi harus prodi D3 atau S1 Universitas Mercu Buana.',
            'self_declared.accepted' => 'Centang pernyataan bahwa kamu mahasiswa prodi ini.',
        ], ['semester' => 'semester']);

        $profile = $user->profile()->updateOrCreate([], [
            'umb_program_offering_id' => $data['program_offering_id'],
            'umb_semester' => $data['semester'],
            'umb_self_declared' => true,
        ]);
        // Perbarui relasi yang sudah dimuat agar pengecekan onboarding berikutnya memakai data baru.
        $user->setRelation('profile', $profile);
        AuditLog::record('umb_program_declared', $user->id, 'program_offering', (string) $data['program_offering_id']);

        return redirect()->route('beranda')->with('status', 'Prodi UMB tersimpan.');
    }

    /** Prodi D3/S1 aktif di UMB Jakarta (bukan UMB Yogyakarta). */
    private function umbPrograms()
    {
        $campus = Campus::query()->umb()->first();

        return $campus
            ? ProgramOffering::query()->where('campus_id', $campus->id)->where('is_active', true)
                ->whereIn('level', ['D3', 'S1'])->orderBy('level')->orderBy('original_name')->get()
            : collect();
    }
}
