<?php

namespace App\Services\Banpt;

use App\Support\AccreditationRating;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Port logika tool banpt-prodi-api: mengambil JSON direktori prodi & institusi BAN-PT
 * (termasuk SK LAM) dan menormalkan barisnya.
 */
class BanptDirectory
{
    public const PRODI_PAGE = 'https://www.banpt.or.id/direktori/prodi/pencarian_prodi.php';
    public const PRODI_DATA = 'https://www.banpt.or.id/direktori/model/dir_prodi/get_hasil_pencariannew.php';
    public const INSTITUSI_PAGE = 'https://www.banpt.or.id/direktori/institusi/pencarian_institusi.php';
    public const INSTITUSI_DATA = 'https://www.banpt.or.id/direktori/model/dir_aipt/get_data_institusi.php';

    public const PRODI_FIELDS = ['nama_kampus', 'program_studi', 'jenjang', 'wilayah', 'nomor_sk', 'tahun_sk', 'peringkat_akreditasi', 'tanggal_kedaluwarsa', 'status_kedaluwarsa'];
    public const INSTITUSI_FIELDS = ['nama_kampus', 'peringkat_akreditasi', 'nomor_sk', 'tahun_sk', 'wilayah', 'tanggal_kedaluwarsa', 'status_kedaluwarsa'];

    private const MAX_BYTES = 30 * 1024 * 1024;

    /** Unduh JSON mentah dari BAN-PT. */
    public function download(string $kind): string
    {
        [$url, $page] = $kind === 'prodi' ? [self::PRODI_DATA, self::PRODI_PAGE] : [self::INSTITUSI_DATA, self::INSTITUSI_PAGE];

        $res = Http::withHeaders([
            'Accept' => 'application/json, text/plain, */*',
            'Referer' => $page,
            'User-Agent' => 'ArahDiri/2 banpt:sync (sinkron mingguan, alat bimbingan karier)',
        ])
            // Server BAN-PT kadang lambat menerima koneksi (>10 detik, batas bawaan Laravel)
            // dan butuh ±30 detik untuk mengirim data. Beri waktu cukup dan coba ulang beberapa kali.
            ->connectTimeout(60)->timeout(300)
            ->retry(3, fn (int $attempt) => $attempt * 15000, throw: false)
            ->get($url);

        if (! $res->successful()) {
            throw new RuntimeException("BAN-PT {$kind}: HTTP {$res->status()}");
        }
        $body = $res->body();
        if (strlen($body) > self::MAX_BYTES) {
            throw new RuntimeException("BAN-PT {$kind}: respons melebihi batas aman");
        }

        return $body;
    }

    /** @return list<array<string, ?string>> */
    public function parse(string $json, string $kind): array
    {
        $fields = $kind === 'prodi' ? self::PRODI_FIELDS : self::INSTITUSI_FIELDS;
        $decoded = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', $json), true);
        $rows = $decoded['data'] ?? null;
        if (! is_array($rows)) {
            throw new RuntimeException("Respons BAN-PT {$kind} bukan JSON yang dikenali");
        }

        $out = [];
        foreach ($rows as $row) {
            // Format asli: daftar nilai berurutan. Format tool lokal: objek bernama.
            if (is_array($row) && array_is_list($row) && count($row) === count($fields)) {
                $row = array_combine($fields, $row);
            }
            if (! is_array($row) || ! isset($row['nama_kampus'])) {
                continue;
            }
            $out[] = array_map(fn ($v) => ($v = trim((string) $v)) === '' ? null : $v, array_intersect_key($row, array_flip($fields)));
        }
        if ($rows && ! $out) {
            throw new RuntimeException("Tidak ada baris BAN-PT {$kind} dengan format yang dikenali");
        }

        return $out;
    }

    /** Singkatan baku agar "Politeknik Kesehatan Kementerian Kesehatan X" = "Poltekkes Kemenkes X", dst. */
    private const SYNONYMS = [
        '/\\bkementerian kesehatan\\b/u' => 'kemenkes',
        '/\\bpoliteknik kesehatan\\b/u' => 'poltekkes',
        '/\\bsekolah tinggi ilmu kesehatan\\b/u' => 'stikes',
        '/\\bsekolah tinggi ilmu ekonomi\\b/u' => 'stie',
        '/\\bsekolah tinggi manajemen informatika dan komputer\\b/u' => 'stmik',
        '/\\bsekolah tinggi keguruan dan ilmu pendidikan\\b/u' => 'stkip',
        '/\\binstitut keguruan dan ilmu pendidikan\\b/u' => 'ikip',
        '/\\bsekolah tinggi agama islam negeri\\b/u' => 'stain',
        '/\\bsekolah tinggi agama islam\\b/u' => 'stai',
        '/\\bsekolah tinggi ilmu administrasi\\b/u' => 'stia',
        '/\\binstitut agama islam negeri\\b/u' => 'iain',
        '/\\buniversitas islam negeri\\b/u' => 'uin',
        '/\\bpersatuan guru republik indonesia\\b/u' => 'pgri',
        // Ejaan prodi yang sering berbeda antara BAN-PT dan PDDikti.
        '/\\bsyari ah\\b/u' => 'syariah',
        '/\\bakutansi\\b/u' => 'akuntansi',
        '/\\bal (?=qur)/u' => '',
        '/\\bqur an\\b/u' => 'quran',
    ];

    public static function normName(?string $v, bool $stripParens = false): string
    {
        $v = html_entity_decode((string) $v, ENT_QUOTES | ENT_HTML5, 'UTF-8'); // "Asy&#039;ari" → "Asy'ari"
        if ($stripParens) {
            $v = preg_replace('/\\([^)]*\\)/u', ' ', $v);
        }
        $v = mb_strtolower($v);
        $v = trim(preg_replace('/\\s+/', ' ', preg_replace('/[^\\p{L}\\p{N}]+/u', ' ', $v)));

        return preg_replace(array_keys(self::SYNONYMS), array_values(self::SYNONYMS), $v);
    }

    /**
     * Pilih satu kandidat yang ejaannya hampir sama (jarak Levenshtein kecil). Hanya untuk nama yang cukup panjang
     * dan hanya bila hasil terbaiknya tunggal, supaya salah ketik ("AIi"/"Ali", "Akutansi") cocok tanpa menebak.
     *
     * @param  iterable<string>  $candidates
     */
    public static function closest(string $name, iterable $candidates, int $maxDistance = 2, int $minLength = 12): ?string
    {
        if (mb_strlen($name) < $minLength) {
            return null;
        }
        $best = null;
        $bestD = $maxDistance + 1;
        $tie = false;
        foreach ($candidates as $c) {
            if (abs(strlen($c) - strlen($name)) > $maxDistance) {
                continue;
            }
            $d = levenshtein($name, $c);
            if ($d < $bestD) {
                [$best, $bestD, $tie] = [$c, $d, false];
            } elseif ($d === $bestD) {
                $tie = true;
            }
        }

        return $best !== null && ! $tie && $bestD <= $maxDistance ? $best : null;
    }

    /**
     * Dua nama kampus dianggap salah ketik kecil dari nama yang sama: sama persis tanpa spasi ("Adi Karya"/"Adikarya"),
     * atau jumlah kata sama dan setiap kata yang berbeda cukup panjang (≥5 huruf) dengan selisih 1 huruf,
     * atau 2 huruf bila katanya ≥8 huruf dan 3 huruf awalnya sama. Menolak "Teknologi"/"Teologi", "Ipmi"/"Ppm".
     */
    public static function sameNameTypo(string $a, string $b, int $minToken = 5): bool
    {
        if (str_replace(' ', '', $a) === str_replace(' ', '', $b)) {
            return true;
        }
        $ta = explode(' ', $a);
        $tb = explode(' ', $b);
        if (count($ta) !== count($tb)) {
            return false;
        }
        // Nama panjang (≥35 huruf) dengan satu kata pendek salah 1 huruf, mis. "Syekh AIi Hasan ..." ↔ "Syekh Ali Hasan ...".
        $diff = array_keys(array_filter($ta, fn ($w, $i) => $w !== $tb[$i], ARRAY_FILTER_USE_BOTH));
        if (count($diff) === 1 && mb_strlen($a) >= 35 && levenshtein($ta[$diff[0]], $tb[$diff[0]]) === 1) {
            return true;
        }
        foreach ($ta as $i => $w) {
            $v = $tb[$i];
            if ($w === $v) {
                continue;
            }
            if (mb_strlen($w) < $minToken || mb_strlen($v) < $minToken) {
                return false;
            }
            $d = levenshtein($w, $v);
            if (! ($d <= 1 || ($d === 2 && mb_strlen($w) >= 8 && mb_strlen($v) >= 8 && substr($w, 0, 3) === substr($v, 0, 3)))) {
                return false;
            }
        }

        return true;
    }

    /** Nama kampus BAN-PT: direktori LAM-PTKes menulis "UNIVERSITAS X, KOTA" (huruf besar + kota). */
    public static function normCampus(?string $v): string
    {
        $v = html_entity_decode((string) $v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (str_contains($v, ',') && mb_strtoupper($v) === $v) {
            $v = substr($v, 0, strrpos($v, ','));
        }

        return self::normName($v);
    }

    /** D-III → D3, D-IV → D4, Sp-1 tetap, dst. Selaras dengan level katalog PDDikti. */
    public static function normLevel(?string $v): string
    {
        $k = strtoupper(str_replace([' ', '.'], '', (string) $v));
        return match ($k) {
            'D-I', 'DI', 'D1' => 'D1',
            'D-II', 'DII', 'D2' => 'D2',
            'D-III', 'DIII', 'D3' => 'D3',
            'D-IV', 'DIV', 'D4', 'STR', 'SARJANATERAPAN' => 'D4',
            'S1' => 'S1', 'S2' => 'S2', 'S3' => 'S3',
            'S2TERAPAN', 'S2-TERAPAN' => 'S2 Terapan',
            'S3TERAPAN', 'S3-TERAPAN' => 'S3 Terapan',
            'PROFESI' => 'Profesi',
            'SP-1', 'SP1' => 'Sp-1',
            'SP-2', 'SP2' => 'Sp-2',
            default => trim((string) $v),
        };
    }

    public static function body(?string $sk): ?string
    {
        $sk = (string) $sk;
        if (stripos($sk, 'BAN-PT') !== false) {
            return 'BAN-PT';
        }
        if (preg_match('/LAM[\s-]?(INFOKOM|Teknik|DIK|EMBA|SAMA|PTKes|SPAK)/i', $sk, $m)) {
            return 'LAM-'.strtoupper($m[1]);
        }
        if (str_contains($sk, '/DE/')) {
            return 'LAM-EMBA';
        }

        return null;
    }

    public static function expiry(?string $v): ?CarbonImmutable
    {
        // BAN-PT kadang mengisi tanggal kosong sebagai 0000-00-00; anggap tidak diketahui.
        if (! $v || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m) || (int) $m[1] < 1990 || ! checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }

        return CarbonImmutable::createFromFormat('!Y-m-d', $v);
    }

    /** Baris berlaku: peringkat bukan "Tidak Terakreditasi", tanggal belum lewat, status bukan kedaluwarsa. */
    public static function isActive(array $row, CarbonImmutable $today): bool
    {
        $status = mb_strtolower((string) ($row['status_kedaluwarsa'] ?? ''));
        if (str_starts_with($status, 'sudah') || str_starts_with($status, 'kedaluwarsa') || str_starts_with($status, 'tidak berlaku')) {
            return false;
        }
        if (AccreditationRating::score($row['peringkat_akreditasi'] ?? null) === 0) {
            return false;
        }
        $exp = self::expiry($row['tanggal_kedaluwarsa'] ?? null);
        if ($exp) {
            return $exp->gte($today);
        }
        // Tanggal kosong: percaya status "masih berlaku", atau SK yang terbit < 5 tahun lalu.
        $year = (int) ($row['tahun_sk'] ?? 0);

        return str_contains($status, 'berlaku') || ($year > 0 && $year + 5 >= (int) $today->format('Y'));
    }

    /** Kunci pembanding: baris yang berlaku dulu, lalu peringkat, lalu masa berlaku. */
    public static function rankKey(array $row, CarbonImmutable $today): array
    {
        return [
            self::isActive($row, $today) ? 1 : 0,
            AccreditationRating::score($row['peringkat_akreditasi'] ?? null),
            (string) ($row['tanggal_kedaluwarsa'] ?? ''),
        ];
    }
}
