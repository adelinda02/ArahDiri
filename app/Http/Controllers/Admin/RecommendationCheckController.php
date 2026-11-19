<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Campus;
use App\Models\ProgramOffering;
use App\Models\ResultReport;
use App\Models\TestResult;
use App\Models\TestSession;
use App\Models\User;
use App\Services\Recommendation\RecommendationEngine;
use App\Services\Recommendation\RecommendationPresenter;
use App\Support\Riasec;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pengecekan kualitas rekomendasi: simulator (profil RIASEC → hasil, tanpa menyimpan apa pun),
 * aturan rekomendasi yang bisa diubah, dan kotak masuk laporan "hasil tidak sesuai" dari siswa.
 */
class RecommendationCheckController extends Controller
{
    // ------------------------------------------------------------------ simulator

    public function simulator(Request $request, RecommendationEngine $engine, RecommendationPresenter $presenter): Response
    {
        $levels = config('arahdiri.levels');
        $input = [
            'jenjang' => array_key_exists($request->query('jenjang'), $levels) ? $request->query('jenjang') : User::LEVEL_SMA_SMK,
            'umb' => $request->integer('umb') ?: null,
            'salah' => $request->boolean('salah'),
        ];
        foreach (Riasec::ORDER as $d) {
            $input[$d] = max(0, min(100, $request->integer($d, 50)));
        }
        $run = $request->boolean('jalankan');
        $umbOptions = $this->umbPrograms();
        if ($input['jenjang'] === User::LEVEL_MHS && ! $umbOptions->contains('id', $input['umb'])) {
            $input['umb'] = $umbOptions->first()?->id;
        }

        $result = null;
        $ms = null;
        if ($run) {
            $t = microtime(true);
            $result = $this->simulate($engine, $presenter, $input);
            $ms = (int) round((microtime(true) - $t) * 1000);
        }
        $norm = collect(Riasec::ORDER)->mapWithKeys(fn ($d) => [$d => $input[$d] / 100])->all();

        return Inertia::render('Admin/Simulator', [
            'input' => $input,
            'levels' => $levels,
            'umbOptions' => $umbOptions->map(fn ($p) => ['id' => $p->id, 'label' => $p->level.' · '.$p->original_name])->values(),
            'code' => strtoupper(implode('', array_slice(Riasec::rank($norm), 0, 3))),
            'result' => $result,
            'ms' => $ms,
            'report' => $request->integer('laporan') ? ResultReport::find($request->integer('laporan'))?->only(['id', 'category', 'message']) : null,
        ]);
    }

    /** Jalankan mesin rekomendasi pada pengguna dan sesi sementara di dalam transaksi yang selalu dibatalkan. */
    private function simulate(RecommendationEngine $engine, RecommendationPresenter $presenter, array $in): array
    {
        DB::beginTransaction();
        try {
            $tag = 'sim'.Str::lower(Str::random(10));
            $user = User::forceCreate(['name' => 'Simulasi', 'username' => $tag, 'email' => $tag.'@simulasi.invalid', 'phone' => '0'.random_int(1000000000, 9999999999),
                'password' => Str::random(40), 'level' => $in['jenjang'], 'role' => 'user']);
            $user->profile()->create(['current_program_offering_id' => $in['jenjang'] === User::LEVEL_MHS ? $in['umb'] : null, 'feels_mismatch' => $in['salah']]);
            $session = TestSession::create(['user_id' => $user->id, 'level_at_test' => $in['jenjang'], 'status' => 'completed',
                'instrument_version' => 'simulasi', 'current_item' => 50, 'started_at' => now(), 'completed_at' => now()]);
            $norm = collect(Riasec::ORDER)->mapWithKeys(fn ($d) => ['norm_'.$d => $in[$d] / 100])->all();
            $result = TestResult::create(['test_session_id' => $session->id, 'holland_code' => 'XXX', 'scoring_version' => 'simulasi']
                + collect(Riasec::ORDER)->mapWithKeys(fn ($d) => ['score_'.$d => (int) round($in[$d] / 100 * 40)])->all() + $norm);
            $engine->generate($session, $result);

            return $presenter->present($session->fresh());
        } finally {
            DB::rollBack(); // simulasi tidak pernah meninggalkan jejak di basis data
        }
    }

    private function umbPrograms()
    {
        $campus = Campus::query()->umb()->first();

        return $campus ? ProgramOffering::query()->where('campus_id', $campus->id)->where('is_active', true)
            ->whereIn('level', ['D3', 'S1'])->orderBy('level')->orderBy('original_name')->get(['id', 'level', 'original_name']) : collect();
    }

    // ------------------------------------------------------------------ aturan

    public function rules(): Response
    {
        return Inertia::render('Admin/Aturan', [
            'rules' => collect(Settings::RULES)->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'value' => (bool) Settings::get($key), 'default' => (bool) Settings::defaults()[$key]])->values(),
            // Pola regex → daftar kata yang mudah dibaca (mis. "hadi(s|ts)" → "hadis", "budd?ha" → "buddha").
            'religiousWords' => collect(explode('|', preg_replace('/\(([^|)]+)\|[^)]*\)/', '$1', trim(config('arahdiri.recommend.religious_pattern'), '/i'))))
                ->map(fn ($w) => str_replace(['?', '\\', '(', ')'], '', $w))->filter()->values(),
            'hiddenCounts' => [
                'prodi' => DB::table('program_families')->where('is_recommendable', false)->count(),
                'karier' => DB::table('kbji_jobs')->where('is_active', false)->count(),
                'kampus' => DB::table('campuses')->where('is_active', false)->count(),
            ],
            'engineVersion' => RecommendationEngine::VERSION,
        ]);
    }

    public function saveRules(Request $request): RedirectResponse
    {
        $data = $request->validate(['key' => ['required', Rule::in(array_keys(Settings::RULES))], 'value' => ['required', 'boolean']]);
        $before = (bool) Settings::get($data['key']);
        Settings::set($data['key'], (bool) $data['value'], $request->user()->id);
        AuditLog::record('rule_changed', $request->user()->id, 'setting', $data['key'], ['before' => $before, 'after' => (bool) $data['value']]);

        return back()->with('status', 'Aturan tersimpan. Berlaku untuk tes yang selesai mulai sekarang.');
    }

    // ------------------------------------------------------------------ laporan siswa

    public function reports(Request $request): Response
    {
        $status = in_array($request->query('status'), ['baru', 'ditinjau', 'selesai', 'semua'], true) ? $request->query('status') : 'baru';

        $items = ResultReport::query()->with(['session.result'])
            ->when($status !== 'semua', fn ($q) => $q->where('status', $status))
            ->latest('id')->paginate(20)->withQueryString()
            ->through(fn (ResultReport $r) => [
                'id' => $r->id, 'at' => $r->created_at, 'category' => $r->category, 'category_label' => ResultReport::CATEGORIES[$r->category] ?? $r->category,
                'message' => $r->message, 'status' => $r->status, 'admin_note' => $r->admin_note,
                'level' => $r->session?->level_at_test, 'code' => $r->session?->result?->holland_code,
                // Ringkasan rekomendasi yang dilihat siswa (tanpa identitas: pengguna tidak ditampilkan).
                'recs' => $r->session ? $this->recSummary($r->session) : [],
                'simulate' => $r->session?->result ? array_merge(
                    collect(Riasec::ORDER)->mapWithKeys(fn ($d) => [$d => (int) round((float) $r->session->result->{'norm_'.$d} * 100)])->all(),
                    ['jenjang' => $r->session->level_at_test, 'jalankan' => 1, 'laporan' => $r->id]) : null,
            ]);

        return Inertia::render('Admin/Laporan', [
            'items' => $items,
            'status' => $status,
            'counts' => ResultReport::query()->selectRaw('status, count(*) n')->groupBy('status')->pluck('n', 'status'),
        ]);
    }

    private function recSummary(TestSession $s): array
    {
        return DB::table('recommendations')->where('test_session_id', $s->id)->whereIn('kind', ['career_path', 'program', 'career', 'mhs_eval', 'mhs_switch'])
            ->orderBy('kind')->orderBy('rank')->get(['kind', 'rank', 'score', 'reason'])
            ->map(function ($r) {
                $reason = json_decode($r->reason, true) ?? [];

                return ['kind' => $r->kind, 'rank' => $r->rank, 'score' => (float) $r->score, 'name' => $reason['title'] ?? $reason['name'] ?? '-'];
            })->all();
    }

    public function updateReport(Request $request, ResultReport $report): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['baru', 'ditinjau', 'selesai'])], 'admin_note' => ['nullable', 'string', 'max:2000']]);
        $report->fill($data + ($data['status'] === 'selesai' ? ['resolved_by' => $request->user()->id, 'resolved_at' => now()] : ['resolved_by' => null, 'resolved_at' => null]))->save();
        AuditLog::record('report_updated', $request->user()->id, 'result_report', (string) $report->id, ['status' => $data['status']]);

        return back()->with('status', 'Laporan diperbarui.');
    }
}
