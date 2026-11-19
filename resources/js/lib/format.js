// Format tampilan hasil rekomendasi.
export const KIND_ICON = {
    majors: '<path d="m2 8 10-5 10 5-10 5-10-5ZM6 10v7c4 3 8 3 12 0v-7M22 8v8"/>',
    careers: '<rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V4h8v3M3 12c6 4 12 4 18 0M10 14h4v3h-4z"/>',
    campuses: '<path d="m3 8 9-5 9 5H3ZM4 21h16M5 18h14M6 10v8M12 10v8M18 10v8"/>',
};

export const pct = (x) => Math.round((x ?? 0) * 100);

export function tanggal(iso) {
    if (!iso) return '';
    const d = new Date(iso.length === 10 ? iso + 'T00:00:00' : iso);
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
}

/** "Unggul · SK 038/SK/LAM-INFOKOM/… (LAM-INFOKOM) · berlaku s.d. 7 April 2028" */
export function accLine(a) {
    if (!a) return 'Belum ada data akreditasi';
    const parts = [a.rating];
    if (a.decree_no) parts.push('SK ' + a.decree_no + (a.body ? ` (${a.body})` : ''));
    if (a.expires_at) parts.push('berlaku s.d. ' + tanggal(a.expires_at));
    return parts.join(' · ');
}

export function campusPlace(c) {
    return [c?.city, c?.province].filter(Boolean).join(', ');
}

export function ownershipLabel(c) {
    if (!c?.ownership) return 'Perguruan Tinggi';
    if (c.ownership === 'Negeri') return 'PTN';
    if (c.ownership === 'PTA (Keagamaan)') return 'PTKN (Keagamaan Negeri)';
    if (c.ownership === 'Negeri (Kedinasan)') return 'Kedinasan';
    return 'PTS';
}

/** Persentase keselarasan → label awam dan warna. */
export function fitLabel(score) {
    const p = pct(score);
    if (p >= 90) return { pct: p, label: 'Sangat cocok', tone: 'high' };
    if (p >= 75) return { pct: p, label: 'Cocok', tone: 'good' };
    if (p >= 60) return { pct: p, label: 'Cukup cocok', tone: 'mid' };
    return { pct: p, label: 'Kurang cocok', tone: 'low' };
}

// Tingkat persiapan pekerjaan (Job Zone O*NET 1–5) dalam istilah pendidikan Indonesia.
const ZONE = {
    1: 'Bisa dimulai tanpa pendidikan khusus',
    2: 'Umumnya lulusan SMA/SMK',
    3: 'Umumnya lulusan D3 atau pelatihan vokasi',
    4: 'Umumnya lulusan S1/D4',
    5: 'Umumnya butuh S2/S3 atau pendidikan profesi',
};
export function zoneText(min, max) {
    if (!min && !max) return null;
    return ZONE[max || min] ?? null;
}

/** Bentuk singkat kampus: "PTN · Kota Bandung". */
export function campusShort(c) {
    return [ownershipLabel(c), c?.city].filter(Boolean).join(' · ');
}

export const siteUrl = (w) => (w ? (w.startsWith('http') ? w : 'https://' + w) : null);

/** Lencana netral (tanpa penilaian cocok/kurang cocok), mis. untuk profesi dari jurusan yang sudah dipilih pengguna. */
export const neutralFit = (score) => ({ pct: pct(score), label: 'Kecocokan minat', tone: 'good' });
