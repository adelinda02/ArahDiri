<?php

namespace App\Http\Controllers;

use App\Services\Recommendation\CampusPicker;
use App\Support\Riasec;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Katalog publik v14 (vJurusan/vKarier/vKampus): daftar + saringan + halaman, detail di jendela (dialog).
 * /jurusan/{id}, /karier/{kode}, /kampus/{kodePT} membuka katalog dengan detail terbuka (bisa dibagikan).
 */
class CatalogController extends Controller
{
    private const PER_PAGE = 24;

    private const MAJOR_GROUPS = [
        '0' => 'TNI & Polri', '1' => 'Manajer', '2' => 'Profesional', '3' => 'Teknisi & Asisten Profesional',
        '4' => 'Tenaga Tata Usaha', '5' => 'Tenaga Usaha Jasa & Penjualan', '6' => 'Pekerja Pertanian, Kehutanan & Perikanan',
        '7' => 'Pekerja Pengolahan & Kerajinan', '8' => 'Operator Mesin & Perakit', '9' => 'Pekerja Kasar',
    ];

    // ------------------------------------------------------------------ jurusan

    public function programs(Request $request, ?int $id = null): Response
    {
        $f = $this->filters($request, ['q', 'bidang', 'jenjang']);
        if (! $id && $request->filled('prodi')) {
            $id = DB::table('program_families')->where('key', $request->query('prodi'))->value('id');
        }

        $q = DB::table('program_families as f')
            ->when($f['q'], fn ($w, $v) => $w->where('f.name', 'like', $this->like($v)))
            ->when($f['bidang'], fn ($w, $v) => $w->where('f.field', $v))
            ->when($f['jenjang'], fn ($w, $v) => $w->where('f.level', $v))
            ->select('f.id', 'f.name', 'f.level', 'f.field', 'f.holland_code')
            ->selectSub(fn ($s) => $s->from('program_offerings as o')->whereColumn('o.program_family_id', 'f.id')->where('o.is_active', true)->selectRaw('count(*)'), 'campus_count')
            ->orderBy('f.name')->orderBy('f.level');

        return $this->render('Katalog/Jurusan', $q, $f, $request, [
            'fields' => DB::table('program_families')->whereNotNull('field')->distinct()->orderBy('field')->pluck('field'),
            'levels' => DB::table('program_families')->distinct()->orderBy('level')->pluck('level'),
            'detail' => fn () => $id ? $this->programDetail($id) : null,
        ]);
    }

    private function programDetail(int $id): ?array
    {
        $f = DB::table('program_families')->find($id);
        if (! $f) {
            return null;
        }
        $offers = DB::table('program_offerings as o')->join('campuses as c', 'c.id', '=', 'o.campus_id')
            ->leftJoin('accreditations as a', fn ($j) => $j->on('a.subject_id', '=', 'o.id')->where('a.subject_type', '=', 'program_offering'))
            ->where('o.program_family_id', $id)->where('o.is_active', true)
            ->orderByDesc('a.rating_score')->orderByRaw('case when c.ownership in (?, ?) then 0 else 1 end', CampusPicker::STATE_OWNERSHIP)->orderBy('c.name')
            ->limit(120)->get(['c.pt_code', 'c.name', 'c.province', 'o.original_name', 'a.rating_raw', 'a.rating_score', 'a.expires_at']);

        return [
            'kind' => 'program', 'id' => (string) $f->id, 'name' => $f->name,
            'meta' => trim($f->level.' · '.($f->field ?? '').($f->holland_code ? ' · Kode minat '.$f->holland_code : ''), ' ·'),
            'description' => $f->description ?? null,
            'riasec' => $this->vector($f),
            'careers' => DB::table('program_family_jobs as l')->join('kbji_jobs as j', 'j.code', '=', 'l.kbji_code')
                ->where('l.program_family_id', $id)->orderBy('l.rank')->get(['j.code', 'j.title'])
                ->map(fn ($j) => ['id' => $j->code, 'title' => $j->title, 'sub' => 'KBJI '.$j->code])->all(),
            'campuses' => $offers->map(fn ($o) => [
                'id' => $o->pt_code, 'title' => $o->name,
                'sub' => trim(($o->province ?? '').' · Akreditasi prodi: '.($o->rating_score > 0 ? $o->rating_raw : ($o->rating_raw ? $o->rating_raw.' (tidak berlaku)' : 'belum ada data')), ' ·'),
            ])->all(),
            'campus_total' => DB::table('program_offerings')->where('program_family_id', $id)->where('is_active', true)->count(),
        ];
    }

    // ------------------------------------------------------------------ karier

    public function careers(Request $request, ?string $code = null): Response
    {
        $f = $this->filters($request, ['q', 'golongan', 'minat']);
        $code ??= $request->query('kbji');

        $q = DB::table('kbji_jobs as j')->where('j.is_active', true)
            ->when($f['q'], fn ($w, $v) => $w->where(fn ($x) => $x->where('j.title', 'like', $this->like($v))->orWhere('j.code', 'like', $v.'%')))
            ->when($f['golongan'] !== null && $f['golongan'] !== '', fn ($w) => $w->where('j.major_group', $f['golongan']))
            ->when($f['minat'], fn ($w, $v) => $w->where('j.holland_code', 'like', strtoupper(substr($v, 0, 1)).'%'))
            ->orderByRaw('j.holland_code is null')->orderBy('j.code')
            ->select('j.code as id', 'j.title as name', 'j.subgroup_title', 'j.holland_code');

        return $this->render('Katalog/Karier', $q, $f, $request, [
            'groups' => self::MAJOR_GROUPS,
            'detail' => fn () => $code ? $this->careerDetail($code) : null,
        ]);
    }

    private function careerDetail(string $code): ?array
    {
        $j = DB::table('kbji_jobs')->where('code', $code)->first();
        if (! $j) {
            return null;
        }

        return [
            'kind' => 'career', 'id' => $j->code, 'name' => $j->title,
            'meta' => 'KBJI '.$j->code.' · '.($j->subgroup_title ?? '').($j->holland_code ? ' · Kode minat '.$j->holland_code : ''),
            'description' => $j->description,
            'official' => $j->official_title !== $j->title ? $j->official_title : null,
            'status' => match ($j->mapping_status) {
                'document_supported' => 'Profil minat dari padanan resmi KBJI–ISCO–O*NET.',
                'draft_ai' => 'Profil minat dari padanan draf ArahDiri, belum ditelaah peneliti.',
                default => 'Belum ada profil minat untuk jabatan ini.',
            },
            'riasec' => $this->vector($j),
            'programs' => DB::table('program_family_jobs as l')->join('program_families as f', 'f.id', '=', 'l.program_family_id')
                ->where('l.kbji_code', $code)->whereIn('f.level', ['S1', 'D4', 'D3', 'D2', 'D1'])->orderBy('f.name')->limit(60)
                ->get(['f.id', 'f.name', 'f.level'])->map(fn ($p) => ['id' => (string) $p->id, 'title' => $p->name, 'sub' => $p->level])->all(),
            'smk' => DB::table('smk_program_jobs as l')->join('smk_programs as s', 's.code', '=', 'l.smk_program_code')
                ->where('l.kbji_code', $code)->get(['s.code', 's.name'])->map(fn ($s) => ['id' => $s->code, 'title' => $s->name, 'sub' => 'Program keahlian SMK'])->all(),
            'page' => $j->print_page ? 'KBJI 2026 Vol. 1, hlm. '.$j->print_page : null,
            // Skill yang ditautkan admin ke karier ini (menu Konten → Skill).
            'skills' => collect(\App\Models\ContentItem::listOf('skill'))->filter(fn ($s) => in_array($j->code, $s['meta']['kbji'] ?? [], true))
                ->map(fn ($s) => ['title' => $s['title'], 'body' => $s['body']])->values()->all(),
        ];
    }

    // ------------------------------------------------------------------ kampus

    public function campuses(Request $request, ?string $pt = null): Response
    {
        $f = $this->filters($request, ['q', 'provinsi', 'status']);
        $pt ??= $request->query('pt');

        $q = DB::table('campuses as c')->where('c.is_active', true)
            ->leftJoin('accreditations as a', fn ($j) => $j->on('a.subject_id', '=', 'c.id')->where('a.subject_type', '=', 'campus'))
            ->when($f['q'], fn ($w, $v) => $w->where(fn ($x) => $x->where('c.name', 'like', $this->like($v))->orWhere('c.pt_code', $v)))
            ->when($f['provinsi'], fn ($w, $v) => $w->where('c.province', $v))
            ->when($f['status'], fn ($w, $v) => $w->where('c.ownership', $v))
            ->orderByDesc('a.rating_score')->orderBy('c.name')
            ->select('c.pt_code as id', 'c.name', 'c.city', 'c.province', 'c.ownership', 'a.rating_raw', 'a.rating_score');

        return $this->render('Katalog/Kampus', $q, $f, $request, [
            'provinces' => DB::table('campuses')->whereNotNull('province')->distinct()->orderBy('province')->pluck('province'),
            'statuses' => DB::table('campuses')->whereNotNull('ownership')->distinct()->orderBy('ownership')->pluck('ownership'),
            'detail' => fn () => $pt ? $this->campusDetail($pt) : null,
            'accreditation_date' => DB::table('sync_runs')->where('kind', 'banpt')->where('status', 'ok')->max('finished_at'),
        ]);
    }

    private function campusDetail(string $pt): ?array
    {
        $c = DB::table('campuses')->where('pt_code', $pt)->first();
        if (! $c) {
            return null;
        }
        $acc = DB::table('accreditations')->where('subject_type', 'campus')->where('subject_id', $c->id)->first();
        $progs = DB::table('program_offerings as o')
            ->leftJoin('accreditations as a', fn ($j) => $j->on('a.subject_id', '=', 'o.id')->where('a.subject_type', '=', 'program_offering'))
            ->where('o.campus_id', $c->id)->where('o.is_active', true)->orderBy('o.level')->orderBy('o.original_name')
            ->get(['o.program_family_id', 'o.original_name', 'o.level', 'a.rating_raw', 'a.rating_score', 'a.expires_at']);

        return [
            'kind' => 'campus', 'id' => $c->pt_code, 'name' => $c->name,
            'meta' => trim(implode(' · ', array_filter([$c->ownership, $c->form, $c->city, $c->province])), ' ·'),
            'website' => $c->website,
            'accreditation' => $acc ? ['rating' => $acc->rating_raw, 'active' => $acc->rating_score > 0, 'decree_no' => $acc->decree_no, 'expires_at' => $acc->expires_at] : null,
            'programs' => $progs->map(fn ($p) => [
                'id' => $p->program_family_id ? (string) $p->program_family_id : null,
                'title' => $p->original_name.' ('.$p->level.')',
                'sub' => 'Akreditasi: '.($p->rating_score > 0 ? $p->rating_raw.($p->expires_at ? ' · berlaku s.d. '.substr($p->expires_at, 0, 10) : '') : ($p->rating_raw ? $p->rating_raw.' (tidak berlaku)' : 'belum ada data')),
            ])->all(),
        ];
    }

    // ------------------------------------------------------------------ program SMK

    public function smk(): Response
    {
        $jobs = DB::table('smk_program_jobs as l')->join('kbji_jobs as j', 'j.code', '=', 'l.kbji_code')->orderBy('l.rank')
            ->get(['l.smk_program_code', 'j.code', 'j.title'])->groupBy('smk_program_code');
        $conc = DB::table('smk_concentrations')->orderBy('code')->get()->groupBy('smk_program_code');

        $fields = DB::table('smk_programs')->get()
            ->sortBy(fn ($p) => array_map('intval', explode('.', $p->code)))
            ->groupBy('field_name')
            ->map(fn ($g, $name) => ['name' => $name, 'programs' => $g->map(fn ($p) => [
                'code' => $p->code, 'name' => $p->name, 'holland' => $p->holland_code,
                'concentrations' => ($conc[$p->code] ?? collect())->map(fn ($c) => $c->name.($c->duration_years == 4 ? ' (4 tahun)' : ''))->all(),
                'careers' => ($jobs[$p->code] ?? collect())->map(fn ($j) => ['id' => $j->code, 'title' => $j->title])->all(),
            ])->values()])->values();

        return Inertia::render('Katalog/Smk', ['fields' => $fields]);
    }

    // ------------------------------------------------------------------ bantu

    private function render(string $page, $query, array $filters, Request $request, array $extra): Response
    {
        $paginator = $query->paginate(self::PER_PAGE)->withQueryString();

        return Inertia::render($page, [
            'items' => $paginator->items(),
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'pages' => $paginator->lastPage(),
            'filters' => $filters,
            'favorites' => FavoriteController::mapFor($request->user()),
        ] + $extra);
    }

    private function filters(Request $request, array $keys): array
    {
        $out = [];
        foreach ($keys as $k) {
            $v = trim((string) $request->query($k, ''));
            $out[$k] = $v === '' ? null : mb_substr($v, 0, 80);
        }

        return $out;
    }

    private function like(string $v): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $v).'%';
    }

    private function vector(object $row): ?array
    {
        if (! isset($row->riasec_r) || $row->riasec_r === null) {
            return null;
        }
        $v = [];
        foreach (Riasec::ORDER as $d) {
            $v[strtoupper($d)] = round(Riasec::normalizeOnet((float) $row->{'riasec_'.$d}), 3);
        }

        return $v;
    }
}
