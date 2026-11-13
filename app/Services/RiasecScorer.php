<?php

namespace App\Services;

use App\Support\Riasec;
use InvalidArgumentException;

/**
 * Menghitung skor tes RIASEC 50 butir (versi riasec-sum-norm-v1, sama dengan v14):
 * jumlah jawaban (1-5) per dimensi, dibagi skor maksimum dimensi.
 */
class RiasecScorer
{
    /**
     * @param array<int, int> $answers item_no => nilai 1-5, harus lengkap
     * @return array{raw: array<string,int>, norm: array<string,float>, rank: list<string>, code: string}
     */
    public function score(array $answers): array
    {
        $items = config('riasec.items');
        $max = config('riasec.max');

        $raw = array_fill_keys(Riasec::ORDER, 0);
        foreach ($items as $no => [$dim]) {
            $v = $answers[$no] ?? null;
            if (! is_int($v) || $v < 1 || $v > 5) {
                throw new InvalidArgumentException("Jawaban butir {$no} belum ada atau tidak sah.");
            }
            $raw[strtolower($dim)] += $v;
        }

        $norm = [];
        foreach (Riasec::ORDER as $d) {
            $norm[$d] = round($raw[$d] / $max[strtoupper($d)], 4);
        }
        $rank = Riasec::rank($norm, $raw);

        return [
            'raw' => $raw,
            'norm' => $norm,
            'rank' => $rank,
            'code' => strtoupper(implode('', array_slice($rank, 0, 3))),
        ];
    }
}
