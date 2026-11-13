<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Banpt\BanptDirectory;
use App\Support\Riasec;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Panel admin (spesifikasi bagian 9 & 10). Admin hanya melihat angka gabungan;
 * data per orang (jawaban mentah) hanya untuk owner dan selalu dicatat di log audit.
 */
class AdminController extends Controller
{
    // ------------------------------------------------------------------ ringkasan

    public function dashboard(Request $request): Response
    {
        $days = in_array((int) $request->query('hari'), [7, 30, 90, 365], true) ? (int) $request->query('hari') : 30;
        $since = now()->subDays($days);

        $users = DB::table('users')->where('role', 'user')->where('is_active', true)->select('level', DB::raw('count(*) as n'))->groupBy('level')->pluck('n', 'level');
        $sessions = DB::table('test_sessions')->where('created_at', '>=', $since);
        $completed = (clone $sessions)->where('status', 'completed');

        $codes = DB::table('test_results as r')->join('test_sessions as s', 's.id', '=', 'r.test_session_id')->where('s.completed_at', '>=', $since)
            ->select('r.holland_code', DB::raw('count(*) as n'))->groupBy('r.holland_code')->orderByDesc('n')->limit(12)->get();
        $letters = DB::table('test_results as r')->join('test_sessions as s', 's.id', '=', 'r.test_session_id')->where('s.completed_at', '>=', $since)
            ->select(DB::raw('substr(r.holland_code, 1, 1) as t'), DB::raw('count(*) as n'))->groupBy('t')->pluck('n', 't');

        $topPrograms = DB::table('recommendations as r')->join('test_sessions as s', 's.id', '=', 'r.test_session_id')
            ->join('program_families as f', 'f.id', '=', 'r.target_id')
            ->where('r.kind', 'program')->where('r.target_type', 'program_family')->where('s.completed_at', '>=', $since)
            ->select('f.name', 'f.level', DB::raw('count(*) as n'))->groupBy('f.name', 'f.level')->orderByDesc('n')->limit(10)->get();

        $smaDone = (clone $completed)->where('level_at_test', 'sma_smk')->count();
        $withPromo = DB::table('umb_promotions as p')->join('test_sessions as s', 's.id', '=', 'p.test_session_id')
            ->where('s.completed_at', '>=', $since)->distinct()->count('p.test_session_id');

        // Rekomendasi teratas lain: profesi jalur SMP dan karier SMA/SMK/UMB.
        $topJobs = fn (string $kind) => DB::table('recommendations as r')->join('test_sessions as s', 's.id', '=', 'r.test_session_id')
            ->join('kbji_jobs as j', 'j.code', '=', 'r.target_id')
            ->where('r.kind', $kind)->where('s.completed_at', '>=', $since)
            ->select('j.title as name', 'j.code as level', DB::raw('count(*) as n'))->groupBy('j.title', 'j.code')->orderByDesc('n')->limit(10)->get();

        return Inertia::render('Admin/Dashboard', [
            'days' => $days,
            'completed_by_level' => collect(config('arahdiri.levels'))->map(fn ($l, $k) => ['label' => $l,
                'n' => (clone $completed)->where('level_at_test', $k)->count()])->values(),
            'top_career_paths' => $topJobs('career_path'),
            'top_careers' => $topJobs('career'),
            'health' => $this->dataHealth(),
            'stats' => [
                'users' => array_sum($users->all()),
                'started' => (clone $sessions)->count(),
                'completed' => $completed->count(),
                'umb_rate' => $smaDone ? round($withPromo / $smaDone * 100) : null,
            ],
            'users_by_level' => collect(config('arahdiri.levels'))->map(fn ($l, $k) => ['label' => $l, 'n' => (int) ($users[$k] ?? 0)])->values(),
            'codes' => $codes,
            'letters' => collect(Riasec::ORDER)->map(fn ($d) => ['t' => strtoupper($d), 'n' => (int) ($letters[strtoupper($d)] ?? 0)]),
            'top_programs' => $topPrograms,
            'last_sync' => DB::table('sync_runs')->where('kind', 'banpt')->latest('id')->first(),
        ]);
    }

    /** Kesehatan data: hal yang perlu ditindaklanjuti admin, masing-masing dengan tingkat ok/peringatan/masalah. */
    private function dataHealth(): array
    {
        $today = now()->toDateString();
        $lastOk = DB::table('sync_runs')->where('kind', 'banpt')->where('status', 'ok')->max('finished_at');
        $lastRun = DB::table('sync_runs')->where('kind', 'banpt')->latest('id')->first();
        $age = $lastOk ? (int) \Illuminate\Support\Carbon::parse($lastOk)->diffInDays(now()) : null;
        $expired = DB::table('accreditations')->whereNotNull('expires_at')->where('expires_at', '<', $today)->count();
        $soon = DB::table('accreditations')->whereBetween('expires_at', [$today, now()->addDays(30)->toDateString()])->count();
        $unmatched = DB::table('accreditation_unmatched')->where('resolved', false)->count();
        $unmatchedCampus = DB::table('accreditation_unmatched')->where('resolved', false)->distinct()->count('campus_name');
        $reports = DB::table('result_reports')->where('status', 'baru')->count();
        $catalog = DB::table('sync_runs')->where('kind', 'catalog')->where('status', 'ok')->max('finished_at');
        $edited = DB::table('kbji_jobs')->whereNotNull('edited_at')->count() + DB::table('program_families')->whereNotNull('edited_at')->count() + DB::table('smk_programs')->whereNotNull('edited_at')->count();

        return [
            ['key' => 'banpt', 'label' => 'Sinkron BAN-PT', 'href' => '/admin/akreditasi',
                'level' => $lastOk === null || $age > 14 || ($lastRun && $lastRun->status === 'failed') ? 'bad' : ($age > 7 ? 'warn' : 'ok'),
                'text' => $lastOk ? "Terakhir berhasil {$age} hari lalu".($lastRun && $lastRun->status === 'failed' ? '; sinkron terakhir gagal' : '') : 'Belum pernah berhasil'],
            ['key' => 'expired', 'label' => 'Akreditasi kedaluwarsa', 'href' => '/admin/akreditasi?tampil=kedaluwarsa',
                'level' => $expired ? 'warn' : 'ok', 'text' => "{$expired} sudah lewat masa berlaku · {$soon} habis dalam 30 hari"],
            ['key' => 'unmatched', 'label' => 'Nama BAN-PT belum cocok', 'href' => '/admin/akreditasi',
                'level' => $unmatched > 500 ? 'warn' : 'ok', 'text' => "{$unmatched} baris dari {$unmatchedCampus} nama kampus belum dipasangkan (kebanyakan nama lama atau kampus di luar katalog)"],
            ['key' => 'reports', 'label' => 'Laporan siswa baru', 'href' => '/admin/laporan',
                'level' => $reports ? 'warn' : 'ok', 'text' => $reports ? "{$reports} laporan menunggu ditinjau" : 'Tidak ada laporan baru'],
            ['key' => 'catalog', 'label' => 'Katalog', 'href' => '/admin/katalog?disunting=1',
                'level' => $catalog ? 'ok' : 'warn', 'text' => ($catalog ? 'Diimpor '.\Illuminate\Support\Carbon::parse($catalog)->translatedFormat('j M Y') : 'Belum ada catatan impor')." · {$edited} item disunting admin"],
        ];
    }

    // ------------------------------------------------------------------ data hasil tes

    public function results(Request $request): Response
    {
        $total = $this->resultQuery($request)->count();

        return Inertia::render('Admin/Hasil', [
            'total' => $total,
            'items' => $this->resultQuery($request)->paginate(30)->withQueryString()->through(fn ($r) => [
                'rid' => $this->rid($r->id), 'level' => config('arahdiri.levels.'.$r->level_at_test), 'code' => $r->holland_code,
                'completed_at' => $r->completed_at,
            ]),
            'filters' => $request->only(['jenjang', 'dari', 'sampai', 'kode']),
            'levels' => config('arahdiri.levels'),
            'isOwner' => $request->user()->role === 'owner',
        ]);
    }

    /** Ekspor CSV untuk penelitian: ID acak, tanpa nama/email/HP. Jawaban mentah hanya untuk owner. */
    public function export(Request $request): StreamedResponse
    {
        $withAnswers = $request->boolean('jawaban') && $request->user()->role === 'owner';
        AuditLog::record('results_exported', $request->user()->id, meta: $request->only(['jenjang', 'dari', 'sampai', 'kode']) + ['answers' => $withAnswers]);
        $query = $this->resultQuery($request);
        $n = count(config('riasec.items'));

        return response()->streamDownload(function () use ($query, $withAnswers, $n) {
            $out = fopen('php://output', 'w');
            $head = ['id_acak', 'jenjang', 'selesai', 'kode_holland'];
            foreach (Riasec::ORDER as $d) {
                $head[] = 'skor_'.$d;
            }
            foreach (Riasec::ORDER as $d) {
                $head[] = 'norm_'.$d;
            }
            array_push($head, 'rekomendasi_1', 'rekomendasi_2', 'rekomendasi_3', 'promosi_umb');
            if ($withAnswers) {
                for ($i = 1; $i <= $n; $i++) {
                    $head[] = 'jawaban_'.$i;
                }
            }
            fputcsv($out, $head, escape: '');

            $query->chunk(500, function ($rows) use ($out, $withAnswers, $n) {
                $ids = $rows->pluck('id');
                $recs = DB::table('recommendations')->whereIn('test_session_id', $ids)->whereIn('kind', ['program', 'umb_program'])->where('rank', '<=', 3)
                    ->orderBy('rank')->get(['test_session_id', 'reason'])->groupBy('test_session_id');
                $promo = DB::table('umb_promotions')->whereIn('test_session_id', $ids)->select('test_session_id', DB::raw('count(*) as n'))->groupBy('test_session_id')->pluck('n', 'test_session_id');
                $answers = $withAnswers ? DB::table('test_answers')->whereIn('test_session_id', $ids)->get()->groupBy('test_session_id') : collect();

                foreach ($rows as $r) {
                    $line = [$this->rid($r->id), $r->level_at_test, $r->completed_at, $r->holland_code];
                    foreach (Riasec::ORDER as $d) {
                        $line[] = $r->{'score_'.$d};
                    }
                    foreach (Riasec::ORDER as $d) {
                        $line[] = $r->{'norm_'.$d};
                    }
                    $names = ($recs[$r->id] ?? collect())->map(fn ($x) => json_decode($x->reason, true)['name'] ?? '')->values();
                    array_push($line, $names[0] ?? '', $names[1] ?? '', $names[2] ?? '', (int) ($promo[$r->id] ?? 0));
                    if ($withAnswers) {
                        $a = ($answers[$r->id] ?? collect())->pluck('value', 'item_no');
                        for ($i = 1; $i <= $n; $i++) {
                            $line[] = $a[$i] ?? '';
                        }
                    }
                    fputcsv($out, $line, escape: '');
                }
            });
            fclose($out);
        }, 'arahdiri-hasil-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function resultQuery(Request $request)
    {
        $from = $request->date('dari');
        $to = $request->date('sampai');
        // Kode Holland: 1 huruf = minat utama, 2–3 huruf = awalan kode (mis. "IA" → IAS, IAC, ...).
        $code = strtoupper(preg_replace('/[^RIASEC]/i', '', (string) $request->query('kode')));

        return DB::table('test_sessions as s')->join('test_results as r', 'r.test_session_id', '=', 's.id')
            ->join('users as u', 'u.id', '=', 's.user_id')->where('u.role', 'user')->where('s.status', 'completed')
            ->when($request->query('jenjang'), fn ($q, $v) => $q->where('s.level_at_test', $v))
            ->when($from, fn ($q) => $q->where('s.completed_at', '>=', $from->startOfDay()))
            ->when($to, fn ($q) => $q->where('s.completed_at', '<=', $to->endOfDay()))
            ->when($code !== '', fn ($q) => $q->where('r.holland_code', 'like', substr($code, 0, 3).'%'))
            ->orderByDesc('s.completed_at')
            ->select('s.id', 's.level_at_test', 's.completed_at', 'r.*');
    }

    /** ID acak yang stabil tetapi tidak bisa dikembalikan ke ID sesi. */
    private function rid(string $sessionId): string
    {
        return substr(hash_hmac('sha256', $sessionId, (string) config('app.key')), 0, 12);
    }

    // ------------------------------------------------------------------ akreditasi

    public function accreditation(Request $request): Response
    {
        $q = trim((string) $request->query('q'));

        // Akreditasi yang sudah lewat atau hampir habis masa berlakunya (prodi & kampus).
        $window = in_array($request->query('habis'), ['lewat', '30', '90'], true) ? $request->query('habis') : 'lewat';
        $type = in_array($request->query('jenis'), ['prodi', 'kampus'], true) ? $request->query('jenis') : null;
        $today = now()->toDateString();
        $expiry = DB::table('accreditations as a')
            ->leftJoin('program_offerings as o', fn ($j) => $j->on('o.id', '=', 'a.subject_id')->where('a.subject_type', '=', 'program_offering'))
            ->leftJoin('campuses as c', 'c.id', '=', DB::raw('coalesce(o.campus_id, a.subject_id)'))
            ->whereNotNull('a.expires_at')
            ->when($window === 'lewat', fn ($w) => $w->where('a.expires_at', '<', $today))
            ->when($window !== 'lewat', fn ($w) => $w->whereBetween('a.expires_at', [$today, now()->addDays((int) $window)->toDateString()]))
            ->when($type, fn ($w) => $w->where('a.subject_type', $type === 'prodi' ? 'program_offering' : 'campus'))
            ->when($q, fn ($w) => $w->where(fn ($x) => $x->where('c.name', 'like', "%$q%")->orWhere('o.original_name', 'like', "%$q%")))
            ->orderBy('a.expires_at', $window === 'lewat' ? 'desc' : 'asc')
            ->select('a.id', 'a.subject_type', 'a.rating_raw', 'a.expires_at', 'a.decree_no', 'a.body', 'c.name as campus', 'c.pt_code', 'o.original_name as program', 'o.level')
            ->paginate(30, ['*'], 'hal_habis')->withQueryString();

        return Inertia::render('Admin/Akreditasi', [
            'expiry' => $expiry,
            'expiryCounts' => [
                'lewat' => DB::table('accreditations')->whereNotNull('expires_at')->where('expires_at', '<', $today)->count(),
                '30' => DB::table('accreditations')->whereBetween('expires_at', [$today, now()->addDays(30)->toDateString()])->count(),
                '90' => DB::table('accreditations')->whereBetween('expires_at', [$today, now()->addDays(90)->toDateString()])->count(),
            ],
            'tab' => $request->query('tampil') === 'kedaluwarsa' ? 'kedaluwarsa' : 'cocok',
            'runs' => DB::table('sync_runs')->latest('id')->limit(15)->get(),
            'unmatched' => DB::table('accreditation_unmatched')->where('resolved', false)
                ->when($request->query('sumber'), fn ($w, $v) => $w->where('source', $v))
                ->when($q, fn ($w) => $w->where(fn ($x) => $x->where('campus_name', 'like', "%$q%")->orWhere('program_name', 'like', "%$q%")))
                ->select('campus_name', 'source', DB::raw('count(*) as n'), DB::raw('min(id) as id'))
                ->groupBy('campus_name', 'source')->orderByDesc('n')->paginate(30)->withQueryString(),
            'aliases' => DB::table('banpt_aliases as a')->join('campuses as c', 'c.id', '=', 'a.campus_id')->latest('a.id')->limit(50)->get(['a.id', 'a.banpt_name', 'c.name', 'c.pt_code']),
            'filters' => $request->only(['q', 'sumber', 'habis', 'jenis']),
            'has_raw' => Storage::disk('local')->exists('banpt/prodi.json') && Storage::disk('local')->exists('banpt/institusi.json'),
        ]);
    }

    public function campusSearch(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q'));

        return response()->json(mb_strlen($q) < 3 ? [] : DB::table('campuses')->where('name', 'like', "%$q%")->orWhere('pt_code', $q)
            ->orderBy('name')->limit(15)->get(['pt_code', 'name', 'city', 'province']));
    }

    public function pair(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'banpt_name' => ['required', 'string', 'max:255'],
            'pt_code' => ['required', 'exists:campuses,pt_code'],
        ]);
        $campusId = DB::table('campuses')->where('pt_code', $data['pt_code'])->value('id');
        DB::table('banpt_aliases')->updateOrInsert(
            ['name_key' => BanptDirectory::normCampus($data['banpt_name'])],
            ['banpt_name' => $data['banpt_name'], 'campus_id' => $campusId, 'created_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()],
        );
        DB::table('accreditation_unmatched')->where('campus_name', $data['banpt_name'])->update(['resolved' => true, 'updated_at' => now()]);
        AuditLog::record('banpt_alias_added', $request->user()->id, 'campus', $data['pt_code'], ['banpt_name' => $data['banpt_name']]);

        return back()->with('status', 'Pasangan tersimpan. Jalankan sinkron ulang agar akreditasinya masuk.');
    }

    public function unpair(Request $request, int $id): RedirectResponse
    {
        DB::table('banpt_aliases')->where('id', $id)->delete();
        AuditLog::record('banpt_alias_removed', $request->user()->id, 'banpt_alias', (string) $id);

        return back()->with('status', 'Pasangan dihapus.');
    }

    /** Sinkron ulang dari JSON BAN-PT terakhir yang tersimpan (cepat, tanpa mengunduh). */
    public function resync(Request $request): RedirectResponse
    {
        @set_time_limit(300);
        $code = Artisan::call('banpt:sync', ['--import' => true]);
        AuditLog::record('banpt_resync', $request->user()->id, meta: ['exit' => $code]);

        return back()->with($code === 0 ? 'status' : 'error', $code === 0 ? 'Sinkron ulang selesai. '.trim(Artisan::output()) : 'Sinkron gagal: '.mb_substr(trim(Artisan::output()), 0, 300));
    }

    // ------------------------------------------------------------------ katalog

    public function catalog(Request $request): Response
    {
        $type = in_array($request->query('jenis'), ['prodi', 'karier', 'kampus', 'smk', 'sma'], true) ? $request->query('jenis') : 'prodi';
        $q = trim((string) $request->query('q'));
        $like = '%'.$q.'%';
        $edited = $request->boolean('disunting');

        $items = match ($type) {
            'prodi' => DB::table('program_families')->when($q, fn ($w) => $w->where('name', 'like', $like))->when($edited, fn ($w) => $w->whereNotNull('edited_at'))->orderBy('name')
                ->select('id', 'name as title', 'level', 'field', 'holland_code', 'is_recommendable as visible', 'edited_at'),
            'karier' => DB::table('kbji_jobs')->when($q, fn ($w) => $w->where(fn ($x) => $x->where('title', 'like', $like)->orWhere('code', 'like', $q.'%')))->when($edited, fn ($w) => $w->whereNotNull('edited_at'))->orderBy('code')
                ->select('code as id', 'title', 'holland_code', 'is_active as visible', 'edited_at'),
            'kampus' => DB::table('campuses')->when($q, fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('pt_code', $q)))->orderBy('name')
                ->select('pt_code as id', 'name as title', 'province', 'is_active as visible', DB::raw('null as edited_at'), DB::raw('null as holland_code')),
            'smk' => DB::table('smk_programs')->when($q, fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', $like)->orWhere('code', 'like', $q.'%')))->when($edited, fn ($w) => $w->whereNotNull('edited_at'))->orderByRaw('length(code), code')
                ->select('code as id', 'name as title', 'field_name', 'holland_code', DB::raw('1 as visible'), 'edited_at'),
            'sma' => DB::table('sma_tracks')->orderBy('code')->select('code as id', 'name as title', 'holland_code', DB::raw('1 as visible'), DB::raw('null as edited_at')),
        };
        $page = $items->paginate(30)->withQueryString()->through(fn ($r) => [
            'id' => (string) $r->id, 'title' => $r->title, 'visible' => (bool) $r->visible, 'edited' => (bool) $r->edited_at, 'holland' => $r->holland_code,
            'sub' => match ($type) {
                'prodi' => trim($r->level.' · '.($r->field ?? ''), ' ·'),
                'karier' => 'KBJI '.$r->id,
                'kampus' => $r->province ?? '',
                'smk' => $r->id.' · '.$r->field_name,
                'sma' => 'Arah SMA (draf penjurusan 2027)',
            },
        ]);

        return Inertia::render('Admin/Katalog', [
            'type' => $type,
            'items' => $page,
            'filters' => ['q' => $q, 'jenis' => $type, 'disunting' => $edited],
        ]);
    }

    public function toggleCatalog(Request $request): RedirectResponse
    {
        $data = $request->validate(['jenis' => ['required', Rule::in(['prodi', 'karier', 'kampus'])], 'id' => ['required', 'string', 'max:64']]);
        [$table, $key, $col] = match ($data['jenis']) {
            'prodi' => ['program_families', 'id', 'is_recommendable'],
            'karier' => ['kbji_jobs', 'code', 'is_active'],
            'kampus' => ['campuses', 'pt_code', 'is_active'],
        };
        $row = DB::table($table)->where($key, $data['id'])->first();
        abort_unless($row, 404);
        DB::table($table)->where($key, $data['id'])->update([$col => ! $row->{$col}, 'updated_at' => now()]);
        AuditLog::record('catalog_toggled', $request->user()->id, $table, $data['id'], [$col => ! $row->{$col}]);

        return back()->with('status', $row->{$col} ? 'Item disembunyikan.' : 'Item ditampilkan lagi.');
    }

    public function reimport(Request $request): RedirectResponse
    {
        @set_time_limit(300);
        $code = Artisan::call('catalog:import');
        AuditLog::record('catalog_reimported', $request->user()->id, meta: ['exit' => $code]);

        return back()->with($code === 0 ? 'status' : 'error', $code === 0 ? 'Impor ulang katalog selesai.' : 'Impor gagal: '.mb_substr(trim(Artisan::output()), 0, 300));
    }

    // ------------------------------------------------------------------ akun admin & log

    public function accounts(Request $request): Response
    {
        return Inertia::render('Admin/Akun', [
            'admins' => User::query()->whereIn('role', ['editor', 'admin', 'owner'])
                ->orderByRaw("case role when 'owner' then 1 when 'admin' then 2 else 3 end")->orderBy('username')->get(['id', 'name', 'username', 'email', 'role', 'is_active']),
            'isOwner' => $request->user()->role === 'owner',
        ]);
    }

    public function promote(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'owner', 403);
        $data = $request->validate(['identifier' => ['required', 'string', 'max:254'], 'role' => ['nullable', Rule::in(['admin', 'editor'])]], [], ['identifier' => 'akun']);
        $role = $data['role'] ?? 'admin';
        $id = mb_strtolower(trim($data['identifier']));
        $u = User::query()->where('username', $id)->orWhere('email', $id)->first();
        if (! $u) {
            return back()->withErrors(['identifier' => 'Akun tidak ditemukan. Minta calon admin mendaftar dulu.']);
        }
        if ($u->role !== 'user') {
            return back()->withErrors(['identifier' => 'Akun ini sudah punya akses panel admin.']);
        }
        $u->forceFill(['role' => $role])->save();
        AuditLog::record('admin_promoted', $request->user()->id, 'user', (string) $u->id, ['role' => $role]);

        return back()->with('status', "{$u->username} sekarang ".($role === 'editor' ? 'editor konten.' : 'admin.'));
    }

    public function setAdmin(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->role === 'owner' && in_array($user->role, ['admin', 'editor'], true), 403);
        $action = $request->validate(['action' => ['required', Rule::in(['nonaktif', 'aktif', 'cabut', 'jadi_admin', 'jadi_editor'])]])['action'];
        match ($action) {
            'nonaktif' => $user->forceFill(['is_active' => false])->save(),
            'aktif' => $user->forceFill(['is_active' => true])->save(),
            'cabut' => $user->forceFill(['role' => 'user'])->save(),
            'jadi_admin' => $user->forceFill(['role' => 'admin'])->save(),
            'jadi_editor' => $user->forceFill(['role' => 'editor'])->save(),
        };
        if (in_array($action, ['nonaktif', 'cabut', 'jadi_editor'], true)) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
        AuditLog::record('admin_'.$action, $request->user()->id, 'user', (string) $user->id);

        return back()->with('status', 'Akun admin diperbarui.');
    }

    public function audit(Request $request): Response
    {
        $hidden = ['login', 'register', 'test_completed', 'result_printed', 'profile_updated', 'history_deleted', 'history_cleared'];
        $actor = $request->integer('pelaku') ?: null;
        $action = (string) $request->query('aksi');
        $from = $request->date('dari');
        $to = $request->date('sampai');

        return Inertia::render('Admin/Audit', [
            'items' => DB::table('audit_logs as l')->leftJoin('users as u', 'u.id', '=', 'l.actor_id')
                ->whereNotIn('l.action', $hidden)
                ->when($actor, fn ($w) => $w->where('l.actor_id', $actor))
                ->when($action !== '', fn ($w) => $w->where('l.action', $action))
                ->when($from, fn ($w) => $w->where('l.created_at', '>=', $from->startOfDay()))
                ->when($to, fn ($w) => $w->where('l.created_at', '<=', $to->endOfDay()))
                ->latest('l.id')->paginate(50)->withQueryString()->through(fn ($l) => [
                    'id' => $l->id, 'at' => $l->created_at, 'actor' => $l->username, 'action' => $l->action,
                    'subject' => trim(($l->subject_type ?? '').' '.($l->subject_id ?? '')), 'meta' => $l->meta,
                ]),
            // Pilihan saringan: siapa saja yang pernah tercatat dan jenis aksi yang ada.
            'actors' => DB::table('audit_logs as l')->join('users as u', 'u.id', '=', 'l.actor_id')->whereNotIn('l.action', $hidden)
                ->whereIn('u.role', ['editor', 'admin', 'owner'])->distinct()->orderBy('u.username')->get(['u.id', 'u.username']),
            'actions' => DB::table('audit_logs')->whereNotIn('action', $hidden)->distinct()->orderBy('action')->pluck('action'),
            'filters' => ['pelaku' => $actor, 'aksi' => $action, 'dari' => $request->query('dari'), 'sampai' => $request->query('sampai')],
        ]);
    }
}
