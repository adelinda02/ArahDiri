// Format tampilan hasil rekomendasi.
export const KIND_ICON = {
    majors: '<path d="m2 8 10-5 10 5-10 5-10-5ZM6 10v7c4 3 8 3 12 0v-7M22 8v8"/>',
    careers: '<rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V4h8v3M3 12c6 4 12 4 18 0M10 14h4v3h-4z"/>',
    campuses: '<path d="m3 8 9-5 9 5H3ZM4 21h16M5 18h14M6 10v8M12 10v8M18 10v8"/>',
};

// Ikon garis per bidang jurusan (kolom "field" katalog). Hanya untuk tampilan.
export const FIELD_ICON = {
    Teknik: '<rect x="5" y="5" width="14" height="14" rx="2"/><rect x="9" y="9" width="6" height="6" rx="1"/><path d="M9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3"/>',
    Sosial: '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20v-1.5A5.5 5.5 0 0 1 8 13h2a5.5 5.5 0 0 1 5.5 5.5V20"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18 13.3a5.5 5.5 0 0 1 3.5 5.2V20"/>',
    Pertanian: '<path d="M12 21v-9"/><path d="M12 12C12 8 9 5 4 5c0 4 3 7 8 7Z"/><path d="M12 14c0-3.5 2.5-6 7-6 0 3.5-2.5 6-7 6Z"/>',
    Kesehatan: '<path d="M6 3v6a4 4 0 0 0 8 0V3"/><path d="M10 13v2a5 5 0 0 0 10 0v-2"/><circle cx="20" cy="11" r="2"/><path d="M5 3h2M13 3h2"/>',
    Pendidikan: '<path d="M2 5h6a4 4 0 0 1 4 4v11a3 3 0 0 0-3-3H2z"/><path d="M22 5h-6a4 4 0 0 0-4 4v11a3 3 0 0 1 3-3h7z"/>',
    Ekonomi: '<path d="M3 20h18"/><rect x="5" y="12" width="3" height="6" rx=".8"/><rect x="10.5" y="8" width="3" height="10" rx=".8"/><rect x="16" y="4" width="3" height="14" rx=".8"/>',
    Agama: '<path d="M4 19.5V5a2 2 0 0 1 2-2h14v16H6a2 2 0 0 0-2 2.5Z"/><path d="M6 21h14M12 7v6M9 10h6"/>',
    Humaniora: '<path d="M4 5h8M8 3v2M5.5 5c.8 3 2.8 5.5 6 7"/><path d="M10.5 5c-.8 3.5-3 6.5-6.5 8"/><path d="m13 21 4-10 4 10M14.5 17.5h5"/>',
    MIPA: '<path d="M9 3h6M10 3v6L4.5 18.5A1.7 1.7 0 0 0 6 21h12a1.7 1.7 0 0 0 1.5-2.5L14 9V3"/><path d="M7 15h10"/>',
    Seni: '<path d="M12 3a9 9 0 1 0 0 18c1 0 1.7-.8 1.7-1.7 0-.5-.2-.9-.5-1.2-.3-.3-.5-.7-.5-1.2 0-.9.8-1.7 1.7-1.7H16a5 5 0 0 0 5-5C21 6.5 17 3 12 3Z"/><circle cx="7.5" cy="10.5" r="1"/><circle cx="10.5" cy="7" r="1"/><circle cx="15" cy="7.5" r="1"/>',
};
export const fieldIcon = (field) => FIELD_ICON[field] ?? KIND_ICON.majors;

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
