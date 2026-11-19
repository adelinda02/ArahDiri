@php
    $dim = ['R' => 'Realistis', 'I' => 'Investigatif', 'A' => 'Artistik', 'S' => 'Sosial', 'E' => 'Enterprising', 'C' => 'Konvensional'];
    $acc = function (?array $a) {
        if (! $a) return 'Belum ada data akreditasi';
        $p = [$a['rating']];
        if (! empty($a['decree_no'])) $p[] = 'SK '.$a['decree_no'].(! empty($a['body']) ? ' ('.$a['body'].')' : '');
        if (! empty($a['expires_at'])) $p[] = 'berlaku s.d. '.\Carbon\Carbon::parse($a['expires_at'])->translatedFormat('j F Y');
        return implode(' · ', $p);
    };
    $pct = fn ($x) => round(($x ?? 0) * 100);
    $top3 = array_slice($result['rank'], 0, 3);
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Hasil Tes ArahDiri {{ $result['code'] }} — {{ $user->name }}</title>
    <link rel="icon" href="/brand-logo.png">
    <style>
        @page { size: A4; margin: 16mm 14mm; }
        * { box-sizing: border-box; }
        body { font-family: "Plus Jakarta Sans", "Segoe UI", Arial, sans-serif; color: #0f2144; margin: 0; background: #eef3fb; font-size: 11.5pt; line-height: 1.5; }
        .sheet { max-width: 210mm; margin: 0 auto; background: #fff; padding: 18mm 16mm; }
        header { display: flex; align-items: center; gap: 12px; border-bottom: 2px solid #204692; padding-bottom: 10px; margin-bottom: 16px; }
        header img { width: 44px; height: 44px; border-radius: 50%; }
        header b { font-size: 17pt; display: block; }
        header small { color: #2654af; letter-spacing: .08em; font-size: 8pt; }
        h1 { font-size: 18pt; margin: 0 0 4px; }
        h2 { font-size: 13pt; margin: 22px 0 8px; color: #142c5d; border-left: 4px solid #295abb; padding-left: 8px; }
        h3 { font-size: 11.5pt; margin: 0 0 4px; }
        .meta { color: #2654af; font-size: 10pt; margin: 0 0 4px; }
        .code { font-family: "IBM Plex Mono", Consolas, monospace; font-size: 20pt; font-weight: 700; color: #204692; }
        table { width: 100%; border-collapse: collapse; font-size: 10.5pt; }
        td, th { padding: 5px 6px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: top; }
        th { color: #2654af; font-size: 9pt; text-transform: uppercase; letter-spacing: .05em; }
        * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bar { height: 8px; background: #e5ecf7; border-radius: 6px; overflow: hidden; }
        .bar i { display: block; height: 100%; background: #204692; }
        .card { border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 12px; margin-bottom: 8px; break-inside: avoid; }
        .card small, .note { color: #475569; font-size: 9.5pt; }
        .pill { display: inline-block; background: #e5ecf7; color: #204692; border-radius: 99px; padding: 1px 8px; font-size: 9pt; font-weight: 700; margin-right: 4px; }
        footer { margin-top: 22px; border-top: 1px solid #cbd5e1; padding-top: 8px; font-size: 8.5pt; color: #475569; }
        .actions { max-width: 210mm; margin: 12px auto; display: flex; gap: 8px; justify-content: flex-end; }
        .actions button, .actions a { font: inherit; padding: 8px 16px; border-radius: 10px; border: 1px solid #204692; background: #204692; color: #fff; cursor: pointer; text-decoration: none; }
        .actions a { background: #fff; color: #204692; }
        @media print { body { background: #fff; } .sheet { padding: 0; max-width: none; } .actions { display: none; } }
    </style>
</head>
<body>
<div class="actions">
    <a href="/hasil/{{ $result['session_id'] }}">Kembali ke hasil</a>
    <button type="button" onclick="window.print()">Simpan sebagai PDF / Cetak</button>
</div>
<main class="sheet">
    <header>
        <img src="/brand-logo.png" alt="">
        <div><b>ArahDiri</b><small>PANDUAN PENJURUSAN &amp; KARIER</small></div>
    </header>

    <h1>Laporan Hasil Tes Minat RIASEC</h1>
    <p class="meta">{{ $user->name }} · Jenjang {{ $levelLabel }} · Selesai {{ $completedAt?->timezone('Asia/Jakarta')->translatedFormat('j F Y, H.i') }} WIB</p>

    <h2>Profil minat</h2>
    <p>Kode minat: <span class="code">{{ $result['code'] }}</span> ({{ implode(', ', array_map(fn ($t) => $dim[$t], $top3)) }})</p>
    <table>
        <tr><th>Dimensi</th><th style="width:45%">Skor</th><th>Persen</th><th>Poin</th></tr>
        @foreach (['R', 'I', 'A', 'S', 'E', 'C'] as $t)
            <tr>
                <td>{{ $dim[$t] }} ({{ $t }}) @if (in_array($t, $top3)) <span class="pill">#{{ array_search($t, $top3) + 1 }}</span> @endif</td>
                <td><div class="bar"><i style="width:{{ $pct($result['norm'][$t]) }}%"></i></div></td>
                <td>{{ $pct($result['norm'][$t]) }}%</td>
                <td>{{ $result['raw'][$t] }}/{{ $result['max'][$t] }}</td>
            </tr>
        @endforeach
    </table>

    @if (! empty($recs['career_paths']))
        <h2>3 profesi &amp; jalur pendidikannya</h2>
        @foreach ($recs['career_paths'] as $c)
            <div class="card">
                <h3>#{{ $c['rank'] }} {{ $c['title'] }} · keselarasan {{ $pct($c['score']) }}%</h3>
                <small>KBJI {{ $c['id'] }} · kode minat {{ $c['holland'] }}@if ($c['because']) · cocok karena minat {{ implode(' + ', array_map(fn ($d) => $dim[strtoupper($d)] ?? $d, $c['because'])) }}@endif</small><br>
                <small><b>1. SMA arah {{ $c['sma']['name'] }}</b>: {{ implode(', ', $c['sma']['subjects']) }}@if (! $c['sma']['any'] && $c['sma']['via']) (utamakan {{ implode(', ', $c['sma']['via']) }})@endif</small><br>
                <small><b>1. atau SMK</b>: {{ $c['smk'] ? implode('; ', array_map(fn ($k) => $k['name'].' ('.$k['field'].')', $c['smk'])) : 'belum ada program yang linear; SMA arah '.$c['sma']['name'].' lebih disarankan' }}</small><br>
                <small><b>2. Kuliah</b>: {{ implode('; ', array_map(fn ($p) => $p['name'].' ('.$p['level'].', '.$p['offerings_count'].' kampus'.($p['subjects'] ? ', mapel pendukung '.implode(' dan/atau ', $p['subjects']) : '').')', $c['programs'])) }}</small>
            </div>
        @endforeach
        <p class="note">Mapel pendukung prodi mengikuti {{ $recs['snbp']['source'] ?? 'Kepmendikdasmen 102/M/2025' }}. {{ $recs['snbp']['smk_note'] ?? '' }} Penjurusan SMA direncanakan kembali tahun 2027; arah ini disesuaikan setelah aturannya terbit. Kampus dan akreditasinya bisa dilihat di katalog jurusan ArahDiri.</p>
    @endif

    @if ($recs['level'] === 'smp' && empty($recs['career_paths']))
        <h2>Arah SMA</h2>
        @foreach ($recs['sma_tracks'] as $t)
            <div class="card"><h3>#{{ $t['rank'] }} Arah {{ $t['name'] }} · keselarasan {{ $pct($t['score']) }}%</h3><small>Mapel pilihan: {{ implode(', ', $t['subjects']) }}</small>
                @if (! empty($t['next_programs']))
                    <br><small><b>Lanjut kuliah:</b> {{ implode('; ', array_map(fn ($n) => $n['name'].' ('.$n['level'].', mapel '.implode('/', $n['subjects']).')', $t['next_programs'])) }}</small>
                @endif
            </div>
        @endforeach
        <p class="note">Penjurusan SMA direncanakan kembali tahun 2027; arah ini disesuaikan setelah aturannya terbit. Prodi lanjutan dipilih dari mapel pendukung prodi pada SNBP.</p>
        <h2>Program keahlian SMK</h2>
        @foreach ($recs['smk_programs'] as $s)
            <div class="card"><h3>#{{ $s['rank'] }} {{ $s['name'] }} · keselarasan {{ $pct($s['score']) }}%</h3>
                <small>{{ $s['field'] }}. Konsentrasi: {{ implode(', ', $s['concentrations']) }}. Contoh jabatan: {{ implode(', ', array_column($s['careers'], 'title')) }}.</small>
                @if (! empty($s['next_programs']))
                    <br><small><b>Lanjut kuliah:</b> {{ implode('; ', array_map(fn ($n) => $n['name'].' ('.$n['level'].')', $s['next_programs'])) }}</small>
                @endif
            </div>
        @endforeach
    @endif

    @if (in_array($recs['level'], ['smp', 'sma_smk']) && count($recs['programs']))
        <h2>{{ $recs['level'] === 'smp' ? 'Gambaran tujuan kuliah nanti' : '3 rekomendasi prodi & kampus' }}</h2>
        @foreach ($recs['programs'] as $p)
            <div class="card">
                <h3>#{{ $p['rank'] }} {{ $p['name'] }} ({{ $p['level'] }}) · keselarasan {{ $pct($p['score']) }}%</h3>
                <small>Contoh karier (KBJI 2026): {{ implode(', ', array_column($p['careers'], 'title')) ?: '-' }}</small><br>
                @if (isset($p['from_tracks']))
                    @php $strong = array_column(array_filter($p['from_tracks'], fn ($f) => $f['strong']), 'track'); @endphp
                    <small>Cocok karena minat {{ implode(' + ', array_map(fn ($d) => $dim[strtoupper($d)] ?? $d, $p['because'])) ?: '-' }}.
                    Jalur: {{ $strong ? 'SMA arah '.implode(' / ', $strong) : 'SMA arah mana pun' }}{{ $p['from_smk'] ? ' atau SMK '.implode(' / ', $p['from_smk']) : '' }}.
                    @if ($p['support_subjects']) Mapel pendukung SNBP: {{ implode(', ', $p['support_subjects']) }}. @endif</small>
                @elseif ($p['campus']['name'])
                    <small><b>{{ $p['campus']['name'] }}</b>{{ $p['campus']['city'] ? ' · '.$p['campus']['city'] : '' }}<br>
                    Akreditasi prodi: {{ $acc($p['campus']['program_accreditation']) }}<br>
                    Akreditasi kampus: {{ $acc($p['campus']['campus_accreditation']) }}</small>
                @else
                    <small>Belum ada kampus yang memenuhi syarat.</small>
                @endif
            </div>
        @endforeach
    @endif

    @if ($recs['level'] === 'sma_smk' && count($recs['umb_promos']))
        <h2>Informasi dari Universitas Mercu Buana</h2>
        @foreach ($recs['umb_promos'] as $u)
            <div class="card"><h3>{{ $u['type'] === 'same_family' ? 'Prodi yang sama tersedia di UMB' : 'Prodi terkait di UMB' }}: {{ $u['name'] }} ({{ $u['level'] }})</h3>
                <small>Selaras dengan rekomendasi #{{ $u['for_rank'] }} {{ $u['for_name'] }}{{ $u['via'] ? ' lewat jabatan '.$u['via'] : '' }}. {{ $acc($u['accreditation']) }}</small></div>
        @endforeach
        <p class="note">Informasi ini tidak mengubah urutan rekomendasi di atas.</p>
    @endif

    @if ($recs['level'] === 'mhs' && $recs['current'])
        @php $e = $recs['current']; $ya = ! empty($e['feels_mismatch']); @endphp
        <h2>Jurusanmu sekarang</h2>
        <p><b>{{ $e['name'] }} ({{ $e['level'] }})</b> di {{ $e['campus']['name'] ?? '-' }}. Merasa salah jurusan: <b>{{ $ya ? 'Iya' : 'Tidak' }}</b>.</p>
        @if (count($recs['careers']))
            <h2>{{ $ya ? 'Karier yang paling cocok dan jurusan yang sejalan' : 'Profesi dari jurusanmu yang paling cocok' }}</h2>
            @foreach ($recs['careers'] as $c)
                <div class="card"><h3>#{{ $c['rank'] }} {{ $c['title'] }} · {{ $pct($c['score']) }}%</h3>
                    <small>{{ $c['description'] ?? '' }}</small>
                    @if ($ya && count($c['programs'] ?? []))
                        <br><small><b>Jurusan yang sejalan:</b> {{ implode('; ', array_map(fn ($p) => $p['name'].' ('.$p['level'].', dibuka di '.($p['offerings_count'] ?? 0).' kampus)', $c['programs'])) }}</small>
                    @endif
                </div>
            @endforeach
            @if ($ya)
                <p class="note">Diskusikan pilihan ini dengan dosen pembimbing akademik. Kalau mantap pindah, tanyakan prosedur, syarat IPK, batas semester, biaya, dan konversi SKS ke bagian akademik atau kampus tujuan.</p>
            @endif
        @endif
    @endif

    <footer>
        Sumber data: instrumen RIASEC 50 pernyataan ArahDiri; profil minat O*NET 31.0; Klasifikasi Baku Jabatan Indonesia (KBJI) 2026, BPS–Kemnaker;
        katalog perguruan tinggi &amp; prodi PDDikti; Spektrum Keahlian SMK (Kepmendikbudristek 244/M/2024); mapel pendukung prodi SNBP (Kepmendikdasmen 102/M/2025).
        @if ($recs['accreditation_date']) Akreditasi menurut Direktori BAN-PT per {{ \Carbon\Carbon::parse($recs['accreditation_date'])->translatedFormat('j F Y') }}. @endif
        Persentase adalah keselarasan profil minat, bukan peluang diterima. ArahDiri adalah alat bantu awal, bukan pengganti guru BK, konselor, atau orang tua.
    </footer>
</main>
<script>
    // Buka dialog cetak otomatis; pilih "Simpan sebagai PDF" untuk menyimpan.
    window.addEventListener('load', () => setTimeout(() => window.print(), 400));
</script>
</body>
</html>
