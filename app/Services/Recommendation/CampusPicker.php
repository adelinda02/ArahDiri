<?php

namespace App\Services\Recommendation;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Memilih 1 kampus untuk 1 kelompok prodi, sesuai spesifikasi bagian 6:
 * prodi & kampus berakreditasi aktif → akreditasi prodi → akreditasi kampus → PTN/PTAN dulu
 * → masa berlaku lebih panjang → nama kampus. Kampus yang sudah dipakai dilewati.
 */
class CampusPicker
{
    /** Kepemilikan yang dihitung sebagai PTN/PTAN. */
    public const STATE_OWNERSHIP = ['Negeri', 'PTA (Keagamaan)'];

    private ?bool $hasData = null;

    public function hasAccreditationData(): bool
    {
        return $this->hasData ??= DB::table('accreditations')->where('rating_score', '>', 0)->exists();
    }

    /** @param list<int> $excludeCampusIds */
    public function pick(int $familyId, array $excludeCampusIds = []): ?array
    {
        return $this->candidates($familyId, $excludeCampusIds)[0] ?? null;
    }

    /** Daftar kampus memenuhi syarat untuk satu kelompok prodi (dipakai pick()). */
    public function candidates(int $familyId, array $excludeCampusIds = [], int $limit = 1): array
    {
        return $this->eligible()
            ->where('po.program_family_id', $familyId)
            ->when($excludeCampusIds, fn ($q) => $q->whereNotIn('c.id', $excludeCampusIds))
            ->limit($limit)->get()->map(fn ($r) => (array) $r)->all();
    }

    /**
     * Kampus yang membuka satu kelompok prodi menurut katalog PDDikti, tanpa syarat akreditasi.
     * Dipakai bila data akreditasi BAN-PT belum ada: PTN/PTKN dulu, lalu nama kampus.
     */
    public function catalogCampuses(int $familyId, array $excludeCampusIds = [], int $limit = 4, ?int $onlyCampusId = null): array
    {
        return $this->eligible(false)
            ->reorder()
            ->where('po.program_family_id', $familyId)
            ->when($excludeCampusIds, fn ($q) => $q->whereNotIn('c.id', $excludeCampusIds))
            ->when($onlyCampusId, fn ($q) => $q->where('c.id', $onlyCampusId))
            ->orderByRaw('case when c.ownership in (?, ?) then 0 else 1 end', self::STATE_OWNERSHIP)
            ->orderByDesc('pa.rating_score')
            ->orderBy('c.name')
            ->limit($limit)->get()->map(fn ($r) => (array) $r)->all();
    }

    /** Ringkasan satu kampus untuk disimpan di rekomendasi (daftar kampus alternatif). */
    public static function brief(array $row): array
    {
        return [
            'campus_id' => (int) $row['campus_id'],
            'offering_id' => (int) $row['offering_id'],
            'name' => $row['campus_name'],
            'pt_code' => $row['pt_code'] ?? null,
            'city' => $row['city'] ?? null,
            'province' => $row['province'] ?? null,
            'ownership' => $row['ownership'] ?? null,
            'website' => $row['website'] ?? null,
            'program_rating' => ($row['p_score'] ?? 0) > 0 ? $row['p_rating'] : null,
            'campus_rating' => ($row['c_score'] ?? 0) > 0 ? $row['c_rating'] : null,
            'program_expires' => ! empty($row['p_expires']) ? substr((string) $row['p_expires'], 0, 10) : null,
        ];
    }

    /** @return array<int, true> id kelompok prodi yang punya minimal satu kampus memenuhi syarat */
    public function eligibleFamilyIds(): array
    {
        return array_fill_keys($this->eligible()->reorder()->select('po.program_family_id')->distinct()->pluck('program_family_id')->filter()->all(), true);
    }

    /** Status akreditasi satu prodi tertentu (mis. prodi UMB), atau null bila tidak berlaku. */
    public function forOffering(int $offeringId): ?array
    {
        $r = $this->eligible(false)->where('po.id', $offeringId)->first();

        return $r ? (array) $r : null;
    }

    private function eligible(bool $requireActive = true)
    {
        $today = CarbonImmutable::today()->toDateString();
        $q = DB::table('program_offerings as po')
            ->join('campuses as c', 'c.id', '=', 'po.campus_id')
            ->leftJoin('accreditations as pa', fn ($j) => $j->on('pa.subject_id', '=', 'po.id')->where('pa.subject_type', '=', 'program_offering'))
            ->leftJoin('accreditations as ca', fn ($j) => $j->on('ca.subject_id', '=', 'c.id')->where('ca.subject_type', '=', 'campus'))
            ->where('po.is_active', true)->where('c.is_active', true)
            ->select([
                'po.id as offering_id', 'po.original_name', 'po.level', 'c.id as campus_id', 'c.name as campus_name', 'c.pt_code',
                'c.city', 'c.province', 'c.ownership', 'c.group', 'c.website',
                'pa.rating_raw as p_rating', 'pa.rating_score as p_score', 'pa.decree_no as p_decree', 'pa.decree_year as p_year', 'pa.expires_at as p_expires', 'pa.body as p_body', 'pa.fetched_at',
                'ca.rating_raw as c_rating', 'ca.rating_score as c_score', 'ca.decree_no as c_decree', 'ca.decree_year as c_year', 'ca.expires_at as c_expires', 'ca.body as c_body',
            ]);

        if ($requireActive) {
            $q->where('pa.rating_score', '>', 0)->where('ca.rating_score', '>', 0)
                ->where(fn ($w) => $w->whereNull('pa.expires_at')->orWhere('pa.expires_at', '>=', $today))
                ->where(fn ($w) => $w->whereNull('ca.expires_at')->orWhere('ca.expires_at', '>=', $today));
        }

        return $q->orderByDesc('pa.rating_score')
            ->orderByDesc('ca.rating_score')
            ->orderByRaw('case when c.ownership in (?, ?) then 0 else 1 end', self::STATE_OWNERSHIP)
            ->orderByDesc('pa.expires_at')
            ->orderBy('c.name');
    }

    /** Salinan akreditasi untuk disimpan bersama rekomendasi (tidak berubah walau data disinkron ulang). */
    public static function snapshot(array $row, string $prefix): ?array
    {
        if (empty($row[$prefix.'_rating'])) {
            return null;
        }
        $exp = $row[$prefix.'_expires'] ?? null;

        return [
            'rating' => $row[$prefix.'_rating'],
            'score' => (int) $row[$prefix.'_score'],
            'decree_no' => $row[$prefix.'_decree'] ?? null,
            'decree_year' => $row[$prefix.'_year'] ?? null,
            'expires_at' => $exp ? substr((string) $exp, 0, 10) : null,
            'body' => $row[$prefix.'_body'] ?? null,
            // Habis sebelum sinkron mingguan berikutnya.
            'expiring_soon' => $exp && CarbonImmutable::parse($exp)->lt(CarbonImmutable::today()->addDays(8)),
        ];
    }
}
