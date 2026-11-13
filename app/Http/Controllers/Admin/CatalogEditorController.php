<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\Riasec;
use App\Support\SmaTracks;
use App\Support\SmkPaths;
use App\Support\SupportSubjects;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Penyuntingan katalog oleh admin: karier (KBJI), prodi (rumpun), program SMK, dan arah SMA.
 * - Cek bentrok: formulir membawa sidik isi baris; bila berbeda, simpan ditolak (admin lain sudah mengubah).
 * - Baris yang disunting diberi edited_at sehingga tidak ditimpa `catalog:import`; bisa dilepas lagi.
 * - Setiap simpan dicatat di audit_logs beserta nilai sebelum/sesudah.
 */
class CatalogEditorController extends Controller
{
    private const TABLES = [
        'karier' => ['kbji_jobs', 'code'],
        'prodi' => ['program_families', 'id'],
        'smk' => ['smk_programs', 'code'],
        'sma' => ['sma_tracks', 'code'],
    ];

    public function show(string $jenis, string $id): JsonResponse
    {
        [$table, $key] = $this->table($jenis);
        $row = DB::table($table)->where($key, $id)->first();
        abort_unless($row, 404);

        $data = [
            'jenis' => $jenis, 'id' => $id, 'stamp' => $this->stamp($row),
            'edited' => (bool) ($row->edited_at ?? false),
            'riasec' => collect(Riasec::ORDER)->mapWithKeys(fn ($d) => [$d => $row->{'riasec_'.$d} !== null ? (float) $row->{'riasec_'.$d} : null])->all(),
            'holland' => $row->holland_code,
            'history' => DB::table('audit_logs as l')->leftJoin('users as u', 'u.id', '=', 'l.actor_id')
                ->where('l.subject_type', $table)->where('l.subject_id', $id)->latest('l.id')->limit(8)
                ->get(['l.action', 'l.created_at', 'u.username', 'l.meta'])->map(fn ($h) => ['action' => $h->action, 'at' => $h->created_at, 'by' => $h->username, 'fields' => array_keys(json_decode($h->meta ?? '{}', true)['after'] ?? [])]),
        ];

        return response()->json($data + match ($jenis) {
            'karier' => $this->careerData($row),
            'prodi' => $this->programData($row),
            'smk' => $this->smkData($row),
            'sma' => ['name' => $row->name, 'subjects' => json_decode($row->subjects, true) ?: []],
        });
    }

    private function careerData(object $j): array
    {
        return [
            'title' => $j->title, 'description' => $j->description, 'is_active' => (bool) $j->is_active,
            'info' => ['Kode KBJI' => $j->code, 'Judul resmi' => $j->official_title, 'Subgolongan' => trim($j->subgroup_code.' '.($j->subgroup_title ?? '')), 'Status pemetaan' => $j->mapping_status],
            'programs' => DB::table('program_family_jobs as l')->join('program_families as f', 'f.id', '=', 'l.program_family_id')
                ->where('l.kbji_code', $j->code)->orderBy('l.rank')->limit(30)->get(['f.id', 'f.name', 'f.level', 'l.rank'])->map(fn ($p) => ['id' => $p->id, 'label' => "{$p->name} ({$p->level}) · peringkat {$p->rank}"]),
            'smk' => DB::table('smk_program_jobs as l')->join('smk_programs as p', 'p.code', '=', 'l.smk_program_code')
                ->where('l.kbji_code', $j->code)->get(['p.code', 'p.name'])->map(fn ($p) => $p->code.' '.$p->name),
            'skills' => DB::table('content_items')->where('kind', 'skill')->get(['id', 'title', 'meta'])
                ->filter(fn ($s) => in_array($j->code, json_decode($s->meta ?? '{}', true)['kbji'] ?? [], true))->pluck('id')->values(),
            'skillOptions' => DB::table('content_items')->where('kind', 'skill')->orderBy('title')->get(['id', 'title'])->map(fn ($s) => ['id' => $s->id, 'label' => $s->title]),
        ];
    }

    private function programData(object $f): array
    {
        return [
            'name' => $f->name, 'description' => $f->description, 'is_recommendable' => (bool) $f->is_recommendable,
            'info' => ['Jenjang' => $f->level, 'Rumpun' => $f->field ?? '-', 'Metode pemetaan' => $f->mapping_method ?? '-',
                'Dibuka di' => DB::table('program_offerings')->where('program_family_id', $f->id)->where('is_active', true)->count().' kampus'],
            'support_override' => SupportSubjects::overrideOf($f),
            'support_auto' => SupportSubjects::forProgram($f->name, $f->field),
            'subjectOptions' => SupportSubjects::options(),
            'tracks' => collect(SupportSubjects::tracksFor($f->name, $f->field, SupportSubjects::overrideOf($f)))
                ->map(fn ($t, $code) => (SmaTracks::all()[$code]['name'] ?? $code).($t['strength'] === 2 ? '' : ' (umum)'))->values(),
            'careers' => DB::table('program_family_jobs as l')->join('kbji_jobs as j', 'j.code', '=', 'l.kbji_code')
                ->where('l.program_family_id', $f->id)->orderBy('l.rank')->get(['j.code', 'j.title'])->map(fn ($c) => ['id' => $c->code, 'label' => "{$c->code} · {$c->title}"]),
        ];
    }

    private function smkData(object $p): array
    {
        [$defKeep, $defDrop] = SmkPaths::defaultKeywords($p->code);

        return [
            'name' => $p->name,
            'info' => ['Kode' => $p->code, 'Bidang keahlian' => $p->field_name,
                'Konsentrasi' => DB::table('smk_concentrations')->where('smk_program_code', $p->code)->orderBy('code')->pluck('name')->implode(', ') ?: '-'],
            'next_keywords' => $p->next_keywords ?? $defKeep,
            'next_exclude' => $p->next_exclude ?? $defDrop,
            'using_default' => ! $p->next_keywords,
            'preview' => $this->smkPreview($p),
            'jobs' => DB::table('smk_program_jobs as l')->join('kbji_jobs as j', 'j.code', '=', 'l.kbji_code')
                ->where('l.smk_program_code', $p->code)->orderBy('l.rank')->get(['j.code', 'j.title'])->map(fn ($j) => "{$j->code} · {$j->title}"),
        ];
    }

    /** Prodi S1/D4/D3 yang saat ini dianggap lanjutan program SMK ini (untuk memeriksa kata kunci). */
    private function smkPreview(object $p): array
    {
        return DB::table('program_families')->whereIn('level', ['S1', 'D4', 'D3'])->where('is_recommendable', true)->orderBy('name')->get(['name', 'level'])
            ->filter(fn ($f) => SmkPaths::matches($p, $f->name))->map(fn ($f) => "{$f->name} ({$f->level})")->unique()->take(40)->values()->all();
    }

    // ------------------------------------------------------------------ simpan

    public function update(Request $request, string $jenis, string $id): RedirectResponse
    {
        [$table, $key] = $this->table($jenis);
        $row = DB::table($table)->where($key, $id)->first();
        abort_unless($row, 404);
        if ((string) $request->input('stamp') !== $this->stamp($row)) {
            return back()->withErrors(['stamp' => 'Data ini sudah diubah admin lain sejak kamu membukanya. Tutup lalu buka lagi sebelum menyimpan.']);
        }

        $riasecRules = collect(Riasec::ORDER)->mapWithKeys(fn ($d) => ["riasec.$d" => ['required', 'numeric', 'between:1,7']])->all();
        $data = $request->validate(match ($jenis) {
            'karier' => ['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:5000'], 'is_active' => ['boolean'],
                'skills' => ['array'], 'skills.*' => ['integer']] + $riasecRules,
            'prodi' => ['description' => ['nullable', 'string', 'max:5000'], 'is_recommendable' => ['boolean'],
                'support_override' => ['nullable', 'array'], 'support_override.*' => [Rule::in(SupportSubjects::options())],
                'careers' => ['array', 'max:20'], 'careers.*' => ['string', 'exists:kbji_jobs,code']] + $riasecRules,
            'smk' => ['next_keywords' => ['nullable', 'string', 'max:2000'], 'next_exclude' => ['nullable', 'string', 'max:2000']] + $riasecRules,
            'sma' => ['name' => ['required', 'string', 'max:40'], 'subjects' => ['required', 'array', 'min:1', 'max:10'], 'subjects.*' => ['string', 'max:80']] + $riasecRules,
        }, [], ['title' => 'judul', 'description' => 'deskripsi', 'riasec.*' => 'nilai RIASEC', 'next_keywords' => 'kata kunci']);

        $update = [];
        foreach (Riasec::ORDER as $d) {
            $update['riasec_'.$d] = round((float) $data['riasec'][$d], 2);
        }
        $update['holland_code'] = $this->holland($update);
        $update += match ($jenis) {
            'karier' => ['title' => $data['title'], 'description' => $data['description'] ?? null, 'is_active' => (bool) ($data['is_active'] ?? true)],
            'prodi' => ['description' => $data['description'] ?? null, 'is_recommendable' => (bool) ($data['is_recommendable'] ?? true),
                'support_subjects' => ! empty($data['support_override']) ? json_encode(array_values($data['support_override'])) : null],
            'smk' => ['next_keywords' => trim((string) ($data['next_keywords'] ?? '')) ?: null, 'next_exclude' => trim((string) ($data['next_exclude'] ?? '')) ?: null],
            'sma' => ['name' => $data['name'], 'subjects' => json_encode(array_values(array_filter(array_map('trim', $data['subjects']))))],
        };
        if ($jenis !== 'sma') {
            $update['edited_at'] = now();
        }
        $update['updated_at'] = now();

        $before = collect((array) $row)->only(array_keys($update))->except(['updated_at', 'edited_at']);
        $after = collect($update)->except(['updated_at', 'edited_at']);
        $changed = $after->filter(fn ($v, $k) => (string) $v !== (string) ($before[$k] ?? ''));

        DB::transaction(function () use ($table, $key, $id, $update, $jenis, $data) {
            DB::table($table)->where($key, $id)->update($update);
            if ($jenis === 'prodi') {
                DB::table('program_family_jobs')->where('program_family_id', $id)->delete();
                foreach (array_values(array_unique($data['careers'] ?? [])) as $i => $code) {
                    DB::table('program_family_jobs')->insert(['program_family_id' => $id, 'kbji_code' => $code, 'rank' => $i + 1]);
                }
            }
            if ($jenis === 'karier') {
                $this->syncSkills($id, array_map('intval', $data['skills'] ?? []));
            }
        });
        SmaTracks::flush();
        AuditLog::record('catalog_edited', $request->user()->id, $table, $id, [
            'before' => $before->only($changed->keys())->all(), 'after' => $changed->all(),
        ]);

        return back()->with('status', 'Perubahan tersimpan. Berlaku untuk tes yang selesai mulai sekarang.');
    }

    /** Lepas kunci suntingan: impor ulang katalog berikutnya boleh menimpa baris ini lagi. */
    public function release(Request $request, string $jenis, string $id): RedirectResponse
    {
        [$table, $key] = $this->table($jenis);
        abort_if($jenis === 'sma', 404);
        DB::table($table)->where($key, $id)->update(['edited_at' => null]);
        AuditLog::record('catalog_released', $request->user()->id, $table, $id);

        return back()->with('status', 'Kunci suntingan dilepas. Impor ulang berikutnya akan memakai data sumber.');
    }

    public function searchCareers(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q'));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        return response()->json(DB::table('kbji_jobs')->where(fn ($w) => $w->where('title', 'like', '%'.$q.'%')->orWhere('code', 'like', $q.'%'))
            ->orderBy('code')->limit(15)->get(['code', 'title'])->map(fn ($j) => ['id' => $j->code, 'label' => "{$j->code} · {$j->title}"]));
    }

    private function syncSkills(string $code, array $skillIds): void
    {
        foreach (DB::table('content_items')->where('kind', 'skill')->get(['id', 'meta']) as $s) {
            $meta = json_decode($s->meta ?? '{}', true) ?: [];
            $list = $meta['kbji'] ?? [];
            $has = in_array($code, $list, true);
            $want = in_array((int) $s->id, $skillIds, true);
            if ($has !== $want) {
                $meta['kbji'] = $want ? [...$list, $code] : array_values(array_diff($list, [$code]));
                DB::table('content_items')->where('id', $s->id)->update(['meta' => json_encode($meta), 'updated_at' => now()]);
            }
        }
    }

    private function holland(array $row): string
    {
        $v = collect(Riasec::ORDER)->mapWithKeys(fn ($d) => [$d => (float) $row['riasec_'.$d]])->all();

        return strtoupper(implode('', array_slice(Riasec::rank($v), 0, 3)));
    }

    /** Sidik isi baris: berubah setiap kali ada yang menyimpan, walau dalam detik yang sama. */
    private function stamp(object $row): string
    {
        return sha1(json_encode((array) $row));
    }

    private function table(string $jenis): array
    {
        abort_unless(isset(self::TABLES[$jenis]), 404);

        return self::TABLES[$jenis];
    }
}
