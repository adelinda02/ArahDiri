<?php

namespace App\Http\Controllers;

use App\Models\ContentItem;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/** Halaman informasi: referensi & metode, kebijakan privasi. */
class InfoController extends Controller
{
    public function references(): Response
    {
        $last = fn (string $kind) => DB::table('sync_runs')->where('kind', $kind)->where('status', 'ok')->max('finished_at');

        return Inertia::render('Referensi', [
            'stats' => [
                'jobs' => DB::table('kbji_jobs')->count(),
                'jobs_riasec' => DB::table('kbji_jobs')->whereNotNull('holland_code')->count(),
                'campuses' => DB::table('campuses')->count(),
                'offerings' => DB::table('program_offerings')->count(),
                'families' => DB::table('program_families')->count(),
                'smk' => DB::table('smk_programs')->count(),
                'accredited' => DB::table('accreditations')->where('rating_score', '>', 0)->count(),
            ],
            'dates' => ['catalog' => $last('catalog'), 'banpt' => $last('banpt')],
            // Konten yang dikelola admin (menu Konten).
            'content' => [
                'info' => array_values(array_filter(ContentItem::listOf('copy'), fn ($i) => str_starts_with($i['slug'], 'info-'))),
                'scopes' => ContentItem::listOf('scope'),
                'skills' => ContentItem::listOf('skill'),
                'references' => ContentItem::listOf('reference'),
            ],
        ]);
    }

    public function privacy(): Response
    {
        return Inertia::render('Privasi');
    }
}
