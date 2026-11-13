<?php

namespace App\Services\Recommendation;

use App\Models\Recommendation;
use App\Models\TestSession;
use Illuminate\Support\Facades\DB;

/**
 * Menyusun rekomendasi tersimpan menjadi data halaman hasil.
 * Tidak menghitung ulang apa pun: semua dari salinan yang disimpan saat tes selesai.
 */
class RecommendationPresenter
{
    public function __construct(private CampusPicker $campuses) {}

    public function present(TestSession $session): array
    {
        $recs = $session->recommendations()->with('campusPick')->get()->groupBy('kind');
        $campusIds = $recs->collapse()->pluck('campusPick.campus_id')->filter()->unique();
        $campuses = DB::table('campuses')->whereIn('id', $campusIds)->get()->keyBy('id');
        $fetched = $recs->collapse()->pluck('campusPick.accreditation_fetched_at')->filter()->max();

        $out = [
            'level' => $session->level_at_test,
            'accreditation_date' => $fetched?->toDateString(),
            'programs' => $this->programs($recs->get('program'), $campuses),
            'sma_tracks' => $this->simple($recs->get('sma_track')),
            'smk_programs' => $this->simple($recs->get('smk_program')),
            'careers' => $this->simple($recs->get('career')),
            // SMP rec-v3+: jalur per profesi (profesi → SMA/SMK → prodi).
            'career_paths' => $this->simple($recs->get('career_path')),
            'umb_eval' => ($e = $recs->get('umb_eval')?->first()) ? ['score' => (float) $e->score] + $e->reason : null,
            'umb_programs' => $this->programs($recs->get('umb_program'), $campuses),
            'umb_s1_path' => $this->programs($recs->get('umb_s1_path'), $campuses),
            'umb_promos' => $this->promos($session),
            // Dasar mapel pendukung SNBP dan catatan lulusan SMK (Diktum KETIGA).
            'snbp' => ['source' => config('mapel_pendukung.source_short'), 'smk_note' => config('mapel_pendukung.smk_note')],
        ];

        return $out;
    }

    private function simple($rows): array
    {
        return collect($rows)->map(fn (Recommendation $r) => ['rank' => $r->rank, 'id' => $r->target_id, 'score' => (float) $r->score] + ($r->reason ?? []))->values()->all();
    }

    private function programs($rows, $campuses): array
    {
        return collect($rows)->map(function (Recommendation $r) use ($campuses) {
            $pick = $r->campusPick;
            $c = $pick?->campus_id ? $campuses->get($pick->campus_id) : null;

            return ['rank' => $r->rank, 'id' => $r->target_id, 'score' => (float) $r->score] + ($r->reason ?? []) + [
                'campus' => [
                    'status' => $pick?->status ?? 'pending_accreditation',
                    'name' => $c?->name,
                    'pt_code' => $c?->pt_code,
                    'city' => $c?->city,
                    'province' => $c?->province,
                    'ownership' => $c?->ownership,
                    'website' => $c?->website,
                    'is_state' => $c && in_array($c->ownership, CampusPicker::STATE_OWNERSHIP, true),
                    'program_accreditation' => $pick?->program_accreditation,
                    'campus_accreditation' => $pick?->campus_accreditation,
                ],
            ];
        })->values()->all();
    }

    private function promos(TestSession $session): array
    {
        return DB::table('umb_promotions as p')
            ->join('recommendations as r', 'r.id', '=', 'p.recommendation_id')
            ->join('program_offerings as o', 'o.id', '=', 'p.program_offering_id')
            ->leftJoin('kbji_jobs as j', 'j.code', '=', 'p.via_kbji_code')
            ->where('p.test_session_id', $session->id)->orderBy('r.rank')
            ->get(['p.match_type', 'p.via_kbji_code', 'j.title as via_title', 'r.rank as for_rank', 'r.reason as for_reason', 'o.id as offering_id', 'o.original_name', 'o.level'])
            ->map(function ($p) {
                $acc = $this->campuses->forOffering($p->offering_id);

                return [
                    'for_rank' => $p->for_rank,
                    'for_name' => json_decode($p->for_reason, true)['name'] ?? null,
                    'type' => $p->match_type,
                    'via' => $p->via_title,
                    'name' => $p->original_name,
                    'level' => $p->level,
                    'accreditation' => $acc ? CampusPicker::snapshot($acc, 'p') : null,
                ];
            })->all();
    }
}
