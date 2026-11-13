<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\TestSession;
use App\Support\Riasec;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Riwayat tes: semua hasil tersimpan apa adanya (snapshot). */
class HistoryController extends Controller
{
    public function index(Request $request): Response
    {
        $sessions = $request->user()->testSessions()->where('status', 'completed')
            ->with('result')->latest('completed_at')->get();

        return Inertia::render('Riwayat', [
            'items' => $sessions->filter->result->map(function (TestSession $s) {
                $r = $s->result;
                $raw = $norm = [];
                foreach (Riasec::ORDER as $d) {
                    $raw[strtoupper($d)] = (int) $r->{'score_'.$d};
                    $norm[strtoupper($d)] = (float) $r->{'norm_'.$d};
                }

                return [
                    'id' => $s->id, 'code' => $r->holland_code, 'raw' => $raw, 'norm' => $norm,
                    'level' => config('arahdiri.levels.'.$s->level_at_test, $s->level_at_test),
                    'completed_at' => $s->completed_at?->toIso8601String(),
                ];
            })->values(),
            'max' => config('riasec.max'),
        ]);
    }

    public function destroy(Request $request, TestSession $testSession): RedirectResponse
    {
        abort_unless($testSession->user_id === $request->user()->id, 404);
        $testSession->delete();
        AuditLog::record('history_deleted', $request->user()->id, 'test_session', $testSession->id);

        return back()->with('status', 'Riwayat sesi tes dihapus.');
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        $n = $request->user()->testSessions()->delete();
        AuditLog::record('history_cleared', $request->user()->id, meta: ['count' => $n]);

        return back()->with('status', 'Seluruh riwayat tes dihapus.');
    }
}
