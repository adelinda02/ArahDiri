<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ContentItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Konten yang bisa disunting admin (setara menu konten admin v14):
 * teks penjelasan, bidang, skill, aktivitas eksplorasi, dan sumber referensi.
 * Penyimpanan memakai nomor versi supaya dua admin tidak saling menimpa.
 */
class ContentController extends Controller
{
    public const KINDS = [
        'copy' => ['Teks & penjelasan', 'Teks halaman awal dan penjelasan tambahan yang tampil di halaman Referensi.'],
        'scope' => ['Bidang', 'Bidang/rumpun minat yang dijelaskan di halaman Referensi.'],
        'skill' => ['Skill', 'Skill yang perlu dikembangkan. Bisa ditautkan ke karier; tampil di detail karier dan halaman Referensi.'],
        'activity' => ['Aktivitas eksplorasi', 'Ide kegiatan yang bisa siswa jadikan target di halaman Langkah.'],
        'reference' => ['Sumber referensi', 'Sumber data dan bacaan yang dicantumkan di halaman Referensi.'],
    ];

    /** Teks halaman awal (slug tetap) => [judul, nilai bawaan]. */
    public const COPY = [
        'hero-title' => ['Judul halaman awal', 'Masa depanmu. Dengan caramu.'],
        'hero-description' => ['Deskripsi halaman awal', 'Kenali minatmu, jelajahi pilihan jurusan dan karier, lalu tentukan langkah berikutnya. Satu per satu, bersama ArahDiri.'],
    ];

    public const ACTIVITY_CATEGORIES = ['akademik' => 'Akademik', 'softskill' => 'Soft skill', 'karier' => 'Karier'];

    public function index(Request $request): Response
    {
        $kind = array_key_exists($request->query('jenis'), self::KINDS) ? $request->query('jenis') : 'copy';
        $items = ContentItem::query()->where('kind', $kind)->orderBy('position')->orderBy('title')->get();

        if ($kind === 'copy') {
            // Teks halaman awal selalu tampil di daftar, meski belum pernah disimpan.
            foreach (array_reverse(self::COPY, true) as $slug => [$title, $default]) {
                if (! $items->firstWhere('slug', $slug)) {
                    $items->prepend(new ContentItem(['kind' => 'copy', 'slug' => $slug, 'title' => $title, 'body' => null, 'status' => 'active', 'version' => 0]));
                }
            }
        }

        $kbji = DB::table('kbji_jobs')->whereIn('code', $items->flatMap(fn ($i) => $i->meta['kbji'] ?? [])->unique())->pluck('title', 'code');

        return Inertia::render('Admin/Konten', [
            'kind' => $kind,
            'kinds' => collect(self::KINDS)->map(fn ($k, $key) => ['key' => $key, 'label' => $k[0], 'desc' => $k[1],
                'count' => ContentItem::query()->where('kind', $key)->count()])->values(),
            'items' => $items->map(fn (ContentItem $i) => [
                'id' => $i->id, 'slug' => $i->slug, 'title' => $i->title, 'body' => $i->body, 'status' => $i->status, 'version' => (int) $i->version,
                'position' => (int) $i->position, 'meta' => $i->meta ?? (object) [],
                'fixed' => $kind === 'copy' && isset(self::COPY[$i->slug]), 'default' => self::COPY[$i->slug][1] ?? null,
                'careers' => collect($i->meta['kbji'] ?? [])->map(fn ($c) => ['id' => $c, 'label' => $c.' · '.($kbji[$c] ?? '?')])->values(),
            ])->values(),
            'activityCategories' => self::ACTIVITY_CATEGORIES,
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $kind = $request->validate(['kind' => ['required', Rule::in(array_keys(self::KINDS))]])['kind'];
        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'slug' => ['nullable', 'string', 'max:80'],
            'version' => ['required', 'integer', 'min:0'],
            'title' => ['required', 'string', 'max:255'],
            'body' => [$kind === 'copy' && ! isset(self::COPY[$request->input('slug')]) ? 'required' : 'nullable', 'string', 'max:6000'],
            'status' => ['required', Rule::in(['active', 'hidden'])],
            'position' => ['nullable', 'integer', 'between:0,999'],
            'meta.url' => ['nullable', 'url:http,https', 'max:1000'],
            'meta.category' => ['nullable', Rule::in(array_keys(self::ACTIVITY_CATEGORIES))],
            'meta.kbji' => ['nullable', 'array', 'max:50'],
            'meta.kbji.*' => ['string', 'exists:kbji_jobs,code'],
        ], [], ['title' => 'judul', 'body' => 'isi', 'meta.url' => 'URL']);
        $data += ['id' => null, 'slug' => null, 'body' => null, 'position' => 0, 'meta' => []];

        $item = $data['id'] ? ContentItem::query()->where('kind', $kind)->findOrFail($data['id'])
            : (isset(self::COPY[$data['slug'] ?? '']) ? ContentItem::firstOrNew(['kind' => 'copy', 'slug' => $data['slug']]) : new ContentItem(['kind' => $kind]));
        if ((int) ($item->version ?? 0) !== (int) $data['version']) {
            return back()->withErrors(['version' => 'Konten ini sudah diubah admin lain. Muat ulang halaman sebelum menyimpan.']);
        }
        if (! $item->exists && ! isset(self::COPY[$data['slug'] ?? ''])) {
            $item->slug = ($kind === 'copy' ? 'info-' : $kind.'-').substr(bin2hex(random_bytes(4)), 0, 8);
        }

        $meta = array_filter([
            'url' => $kind === 'reference' ? ($data['meta']['url'] ?? null) : null,
            'category' => $kind === 'activity' ? ($data['meta']['category'] ?? 'akademik') : null,
            'kbji' => $kind === 'skill' ? array_values(array_unique($data['meta']['kbji'] ?? [])) : null,
        ], fn ($v) => $v !== null && $v !== []);

        $before = $item->only(['title', 'body', 'status']);
        $item->fill([
            'title' => $data['title'], 'body' => $data['body'] ?? null, 'status' => $data['status'],
            'position' => $data['position'] ?? 0, 'version' => (int) $item->version + 1,
        ]);
        $item->meta = $meta ?: null;
        $item->save();
        AuditLog::record($item->wasRecentlyCreated ? 'content_created' : 'content_updated', $request->user()->id, 'content_item', $item->kind.':'.$item->slug,
            ['before' => array_diff_assoc($before, $item->only(['title', 'body', 'status'])), 'after' => array_diff_assoc($item->only(['title', 'body', 'status']), $before)]);

        return back()->with('status', 'Konten tersimpan.');
    }

    public function destroy(Request $request, ContentItem $item): RedirectResponse
    {
        abort_if($item->kind === 'copy' && isset(self::COPY[$item->slug]), 403);
        $item->delete();
        AuditLog::record('content_deleted', $request->user()->id, 'content_item', $item->kind.':'.$item->slug, ['title' => $item->title]);

        return back()->with('status', 'Konten dihapus.');
    }
}
