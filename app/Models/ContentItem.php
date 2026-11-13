<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Teks yang bisa diubah admin (judul hero, panduan, referensi). */
class ContentItem extends Model
{
    protected $fillable = ['kind', 'slug', 'title', 'body', 'status', 'version', 'meta', 'position'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    /** @return array<string, string> slug => body untuk satu jenis konten aktif */
    public static function bag(string $kind): array
    {
        return static::query()->where('kind', $kind)->where('status', 'active')->pluck('body', 'slug')->all();
    }

    /** Item aktif satu jenis, urut posisi lalu judul (untuk halaman publik). */
    public static function listOf(string $kind): array
    {
        return static::query()->where('kind', $kind)->where('status', 'active')->orderBy('position')->orderBy('title')->get()
            ->map(fn (self $i) => ['id' => $i->id, 'slug' => $i->slug, 'title' => $i->title, 'body' => $i->body, 'meta' => $i->meta ?? []])->all();
    }
}
