<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\TestResult;
use App\Models\TestSession;
use App\Services\Recommendation\CampusPicker;
use App\Services\Recommendation\RecommendationEngine;
use App\Services\Recommendation\RecommendationPresenter;
use App\Support\Riasec;
use App\Support\Settings;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ResultController extends Controller
{
    /** /hasil = hasil tes terakhir; /hasil/{sesi} = hasil tertentu (untuk Riwayat). */
    public function __construct(
        private RecommendationEngine $engine,
        private RecommendationPresenter $presenter,
        private CampusPicker $campuses,
    ) {}

    public function show(Request $request, ?TestSession $testSession = null): Response
    {
        $user = $request->user();

        if ($testSession) {
            abort_unless($testSession->user_id === $user->id && $testSession->status === 'completed', 404);
            $result = $testSession->result;
        } else {
            $result = $user->latestResult();
        }

        if (! $result) {
            $open = $user->testSessions()->where('status', 'in_progress')->withCount('answers')->latest('started_at')->first();

            return Inertia::render('Hasil', [
                'result' => null,
                'answered' => $open?->answers_count ?? 0,
                'total' => count(config('riasec.items')),
            ]);
        }

        $result->loadMissing('session');
        $session = $result->session;
        $this->ensureRecommendations($session, $result);

        $umbProgram = null;
        if ($session->level_at_test === 'mhs_umb') {
            $o = $user->profile?->umbProgram;
            $umbProgram = $o ? ['name' => $o->original_name, 'level' => $o->level] : null;
        }

        return Inertia::render('Hasil', [
            'result' => $this->present($result),
            'recs' => $this->presenter->present($session),
            'umbProgram' => $umbProgram,
            'favorites' => FavoriteController::mapFor($user),
            'total' => count(config('riasec.items')),
            'reportCategories' => \App\Models\ResultReport::CATEGORIES,
        ]);
    }

    /** Siswa melaporkan hasil yang terasa tidak sesuai; masuk ke kotak laporan admin. */
    public function report(Request $request, TestSession $testSession): \Illuminate\Http\RedirectResponse
    {
        abort_unless($testSession->user_id === $request->user()->id && $testSession->status === 'completed', 404);
        $data = $request->validate([
            'category' => ['required', \Illuminate\Validation\Rule::in(array_keys(\App\Models\ResultReport::CATEGORIES))],
            'message' => ['nullable', 'string', 'max:1000'],
        ], [], ['category' => 'jenis masalah', 'message' => 'penjelasan']);
        if (\App\Models\ResultReport::query()->where('test_session_id', $testSession->id)->where('status', 'baru')->count() >= 3) {
            return back()->with('status', 'Laporanmu untuk hasil ini sudah kami terima. Terima kasih!');
        }
        \App\Models\ResultReport::create($data + ['test_session_id' => $testSession->id, 'user_id' => $request->user()->id]);

        return back()->with('status', 'Terima kasih! Laporanmu akan ditinjau tim ArahDiri.');
    }

    /** Versi cetak / PDF hasil (dicetak lewat dialog "Simpan sebagai PDF" peramban). */
    public function print(Request $request, TestSession $testSession)
    {
        abort_unless($testSession->user_id === $request->user()->id && $testSession->status === 'completed', 404);
        $result = $testSession->result;
        abort_unless($result, 404);
        $this->ensureRecommendations($testSession, $result);
        AuditLog::record('result_printed', $request->user()->id, 'test_session', $testSession->id);

        return view('cetak', [
            'user' => $request->user(),
            'result' => $this->present($result),
            'recs' => $this->presenter->present($testSession),
            'levelLabel' => config('arahdiri.levels.'.$testSession->level_at_test),
            'completedAt' => $testSession->completed_at,
        ]);
    }

    /**
     * Hasil lama (sebelum tahap 3) atau yang dibuat saat data akreditasi belum ada
     * dibuatkan rekomendasinya sekarang.
     */
    private function ensureRecommendations(TestSession $session, TestResult $result): void
    {
        $recs = $session->recommendations()->with('campusPick')->get();
        $pending = $recs->where('kind', 'program')->contains(fn ($r) => $r->campusPick?->status === 'pending_accreditation');
        // Hasil lama yang masih memuat prodi/profesi khusus agama (dibuat sebelum daftar kata diperbarui).
        // Hanya jenis yang memang disaring mesin, agar tidak dibuat ulang terus-menerus.
        $religious = Settings::get('recommend.exclude_religious') && $recs->whereIn('kind', ['program', 'career_path'])->contains(function ($r) {
            $names = array_merge([$r->reason['name'] ?? '', $r->reason['title'] ?? ''], array_column($r->reason['programs'] ?? [], 'name'));

            return (bool) preg_grep(config('arahdiri.recommend.religious_pattern'), array_filter($names));
        });

        if ($recs->isEmpty() || $religious || ($pending && $this->campuses->hasAccreditationData())) {
            try {
                $this->engine->generate($session, $result);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    private function present(TestResult $r): array
    {
        $raw = $norm = [];
        foreach (Riasec::ORDER as $d) {
            $raw[strtoupper($d)] = (int) $r->{'score_'.$d};
            $norm[strtoupper($d)] = (float) $r->{'norm_'.$d};
        }
        $rank = array_map('strtoupper', Riasec::rank(array_change_key_case($norm), array_change_key_case($raw)));

        return [
            'session_id' => $r->test_session_id,
            'code' => $r->holland_code,
            'raw' => $raw,
            'norm' => $norm,
            'rank' => $rank,
            'max' => config('riasec.max'),
            'level' => $r->session->level_at_test,
            'completed_at' => $r->session->completed_at?->toIso8601String(),
        ];
    }
}
