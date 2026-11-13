<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\TestAnswer;
use App\Models\TestSession;
use App\Services\Recommendation\RecommendationEngine;
use App\Services\RiasecScorer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Alur tes v14: panduan → 50 pernyataan (maju otomatis setelah menjawab) → hasil.
 * Beda dengan v14: setiap jawaban langsung tersimpan di server, jadi tes bisa dilanjutkan.
 */
class TestController extends Controller
{
    public function index(Request $request): Response
    {
        $session = $this->openSession($request);

        return Inertia::render('Tes', [
            'instrument' => [
                'version' => config('riasec.version'),
                'lead' => config('riasec.lead'),
                'scale' => array_values(config('riasec.scale')),
                // Hanya teks; dimensi tiap butir tidak dikirim ke peramban.
                'items' => array_values(array_map(fn ($i) => $i[1], config('riasec.items'))),
            ],
            'session' => $session ? [
                'id' => $session->id,
                'answers' => $session->answers()->pluck('value', 'item_no'),
                'current_item' => $session->current_item,
            ] : null,
            'startNow' => (bool) $request->session()->get('test_started'),
        ]);
    }

    /** "Mulai Tes" / "Ulangi Tes": sesi berjalan ditinggalkan, sesi baru dibuat. */
    public function start(Request $request): RedirectResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user) {
            $user->testSessions()->where('status', 'in_progress')->update(['status' => 'abandoned']);
            $user->testSessions()->create([
                'level_at_test' => $user->level,
                'status' => 'in_progress',
                'instrument_version' => config('riasec.version'),
                'current_item' => 1,
                'started_at' => now(),
            ]);
        });

        return redirect()->route('tes')->with('test_started', true);
    }

    /** Simpan satu jawaban (dipanggil di latar belakang setiap kali pengguna memilih). */
    public function answer(Request $request, TestSession $testSession): JsonResponse
    {
        $this->authorizeOpen($request, $testSession);
        $n = count(config('riasec.items'));
        $data = $request->validate([
            'item_no' => ['required', 'integer', 'between:1,'.$n],
            'value' => ['required', 'integer', 'between:1,5'],
        ]);

        TestAnswer::query()->upsert(
            [['test_session_id' => $testSession->id, 'item_no' => $data['item_no'], 'value' => $data['value'], 'created_at' => now(), 'updated_at' => now()]],
            ['test_session_id', 'item_no'],
            ['value', 'updated_at'],
        );
        $testSession->update(['current_item' => min($data['item_no'] + 1, $n)]);

        return response()->json(['ok' => true]);
    }

    /**
     * Selesaikan tes. Peramban mengirim ulang semua jawaban sebagai cadangan
     * kalau ada penyimpanan latar belakang yang gagal.
     */
    public function finish(Request $request, TestSession $testSession, RiasecScorer $scorer, RecommendationEngine $engine): RedirectResponse
    {
        $this->authorizeOpen($request, $testSession);
        $n = count(config('riasec.items'));
        $data = $request->validate([
            'answers' => ['required', 'array', 'size:'.$n],
            'answers.*' => ['required', 'integer', 'between:1,5'],
        ]);

        $answers = [];
        foreach ($data['answers'] as $no => $v) {
            $no = (int) $no;
            abort_unless($no >= 1 && $no <= $n, 422, 'Nomor butir tidak sah.');
            $answers[$no] = (int) $v;
        }
        $score = $scorer->score($answers);

        DB::transaction(function () use ($testSession, $answers, $score, $n) {
            $now = now();
            TestAnswer::query()->upsert(
                collect($answers)->map(fn ($v, $no) => ['test_session_id' => $testSession->id, 'item_no' => $no, 'value' => $v, 'created_at' => $now, 'updated_at' => $now])->values()->all(),
                ['test_session_id', 'item_no'],
                ['value', 'updated_at'],
            );

            $row = ['scoring_version' => config('riasec.scoring_version'), 'holland_code' => $score['code']];
            foreach ($score['raw'] as $d => $v) {
                $row['score_'.$d] = $v;
                $row['norm_'.$d] = $score['norm'][$d];
            }
            $testSession->result()->updateOrCreate([], $row);
            $testSession->update(['status' => 'completed', 'completed_at' => $now, 'current_item' => $n]);

        });

        // Rekomendasi per jenjang. Bila gagal, hasil tes tetap tersimpan dan rekomendasi dibuat ulang saat halaman hasil dibuka.
        try {
            $engine->generate($testSession, $testSession->result()->first());
        } catch (\Throwable $e) {
            report($e);
        }

        AuditLog::record('test_completed', $request->user()->id, 'test_session', $testSession->id, ['code' => $score['code']]);

        return redirect()->route('hasil')->with('status', 'Tes selesai! Hasil berhasil disimpan ke Riwayat.');
    }

    private function openSession(Request $request): ?TestSession
    {
        return $request->user()->testSessions()
            ->where('status', 'in_progress')
            ->where('instrument_version', config('riasec.version'))
            ->latest('started_at')
            ->first();
    }

    private function authorizeOpen(Request $request, TestSession $s): void
    {
        abort_unless($s->user_id === $request->user()->id, 404);
        abort_unless($s->status === 'in_progress', 409, 'Sesi tes ini sudah selesai atau ditinggalkan.');
    }
}
