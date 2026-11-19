// AI Pathway: kesimpulan ringkas yang disusun otomatis dari skor RIASEC dan rekomendasi tersimpan.
// Tidak memakai layanan AI luar; semua kalimat dibangun dari data hasil tes (deterministik, bisa diaudit).
import { DIM, becauseText } from './riasec';
import { fitLabel, neutralFit, pct, zoneText } from './format';

const list = (xs, sep = ', ', last = ' dan ') => {
    const a = xs.filter(Boolean);
    return a.length <= 1 ? a.join('') : a.slice(0, -1).join(sep) + last + a[a.length - 1];
};

/** Kalimat profil: tiga minat terkuat beserta persentasenya. */
function profileLine(code, top3, norm) {
    const parts = top3.map((t) => `${DIM[t].name} (${pct(norm[t])}%)`);
    return `Profil minatmu ${code}: paling kuat di ${list(parts)}. Artinya kamu cenderung ${list(top3.map((t) => DIM[t].alias.toLowerCase()))}.`;
}

const careerNames = (careers, n = 2) => (careers ?? []).slice(0, n).map((c) => c.title);
const because = (p) => (p.because?.length ? `minat ${becauseText(p.because)}-mu sejalan dengan prodi ini` : 'profil minat prodi ini paling dekat dengan profilmu');

// ------------------------------------------------------------------ SMA / SMK
function forSchool(recs, ctx) {
    const [p1, ...rest] = recs.programs;
    if (!p1) return null;
    const f = fitLabel(p1.score);
    const campus = p1.campus?.name;
    const subjects = p1.subjects ?? [];
    const isArt = /seni|desain|film|musik|tari|kriya|animasi/i.test(p1.name + ' ' + (p1.field ?? ''));
    return {
        headline: `Jalur utamamu: ${p1.name}`,
        summary: `${profileLine(ctx.code, ctx.top3, ctx.norm)} Dari ${recs.programs.length} prodi yang direkomendasikan, ${p1.name} (${p1.level}) paling selaras (${f.label.toLowerCase()}, ${f.pct}%) karena ${because(p1)}.`,
        main: {
            label: 'Pilihan utama',
            title: `${p1.name} (${p1.level})`,
            fit: f,
            points: [
                campus ? `Kampus pilihan: ${campus}${p1.campus.program_accreditation?.rating ? `, akreditasi prodi ${p1.campus.program_accreditation.rating}` : ''}.` : 'Kampus pilihan akan muncul setelah data akreditasi tersedia.',
                careerNames(p1.careers).length ? `Peluang karier: ${list(careerNames(p1.careers, 3))}.` : null,
                subjects.length ? `Mapel pendukung saat seleksi: ${list(subjects)}.` : null,
            ].filter(Boolean),
        },
        alternatives: rest.map((p) => ({
            title: `${p.name} (${p.level})`,
            fit: fitLabel(p.score),
            text: careerNames(p.careers).length ? `Mengarah ke ${list(careerNames(p.careers))}.` : `Bidang ${p.field ?? 'lain'}.`,
        })),
        steps: [
            { when: '30 hari ke depan', items: [
                `Baca kurikulum ${p1.name}${campus ? ` di situs resmi ${campus}` : ''} dan bandingkan dengan 2 kampus lain di tab Kampus.`,
                subjects.length ? `Perkuat nilai ${list(subjects)}: mapel ini dilihat saat seleksi.` : 'Perkuat nilai rapor mapel yang paling dekat dengan prodi ini.',
                careerNames(p1.careers, 1).length ? `Cari tahu keseharian seorang ${careerNames(p1.careers, 1)[0]} (video, wawancara, atau kunjungan).` : null,
            ].filter(Boolean) },
            { when: 'Semester ini', items: [
                'Tentukan jalur masuk yang akan dicoba: SNBP, SNBT, atau seleksi mandiri, lalu catat jadwalnya.',
                isArt ? 'Mulai kumpulkan karya untuk portofolio; banyak prodi seni dan desain memintanya.' : 'Ikut kegiatan atau lomba yang terkait bidang ini untuk menguji minatmu.',
                'Bandingkan biaya kuliah, akreditasi, dan lokasi tiap kampus.',
            ] },
            { when: 'Sebelum mendaftar', items: [
                'Diskusikan pilihanmu dengan guru BK dan orang tua, bawa hasil ini sebagai bahan.',
                rest.length ? `Siapkan cadangan: ${list(rest.map((p) => p.name), ', ', ' atau ')}.` : null,
            ].filter(Boolean) },
        ],
        notes: [
            p1.campus?.program_accreditation?.expiring_soon ? `Akreditasi ${p1.name} di ${campus} segera berakhir; cek perpanjangannya di BAN-PT.` : null,
            recs.snbp?.smk_note ? `Untuk lulusan SMK: ${recs.snbp.smk_note}` : null,
        ].filter(Boolean),
    };
}

// ------------------------------------------------------------------ SMP
function forSmp(recs, ctx) {
    const c = recs.career_paths?.[0];
    if (!c) return null;
    const f = fitLabel(c.score);
    const smk = c.smk?.map((k) => k.name) ?? [];
    const kuliah = c.programs.map((p) => p.name);
    return {
        headline: `Cita-cita yang paling cocok: ${c.title}`,
        summary: `${profileLine(ctx.code, ctx.top3, ctx.norm)} Profesi ${c.title} paling selaras dengan minatmu (${f.label.toLowerCase()}, ${f.pct}%)${c.because?.length ? ` karena sama-sama menuntut minat ${becauseText(c.because)}` : ''}.`,
        main: {
            label: 'Jalur yang bersambung',
            title: c.title,
            fit: f,
            points: [
                `SMA arah ${c.sma.name}${smk.length ? ` atau SMK ${list(smk, ', ', ' / ')}` : ''}.`,
                `Lalu kuliah ${list(kuliah, ', ', ' atau ')}.`,
                zoneText(c.zone_min, c.zone_max) ? `Pendidikan yang dibutuhkan: ${zoneText(c.zone_min, c.zone_max).replace(/^U/, 'u')}.` : null,
            ].filter(Boolean),
        },
        alternatives: recs.career_paths.slice(1).map((p) => ({
            title: p.title,
            fit: fitLabel(p.score),
            text: `SMA arah ${p.sma.name}${p.smk?.length ? ` atau SMK ${p.smk[0].name}` : ''}, lalu ${p.programs.map((x) => x.name).join(' / ')}.`,
        })),
        steps: [
            { when: '30 hari ke depan', items: [
                `Cari tahu pekerjaan ${c.title}: apa yang dikerjakan sehari-hari dan di mana bekerjanya.`,
                `Perhatikan mapel ${list(c.sma.subjects.slice(0, 3))}; mapel ini dipakai di arah ${c.sma.name}.`,
            ] },
            { when: 'Semester ini', items: [
                'Ikut ekstrakurikuler atau kegiatan yang dekat dengan profesi ini.',
                'Ceritakan cita-citamu ke orang tua dan guru BK, lalu dengarkan pendapat mereka.',
            ] },
            { when: 'Sebelum lulus SMP', items: [
                smk.length ? `Bandingkan SMA arah ${c.sma.name} dengan SMK ${smk[0]}: lihat praktik, fasilitas, dan peluang lanjut kuliah.` : `Pilih SMA yang membuka arah ${c.sma.name}.`,
            ] },
        ],
        notes: [],
    };
}

// ------------------------------------------------------------------ Mahasiswa
// YA (merasa salah jurusan): karier dulu → jurusan yang sejalan. TIDAK: profesi dari jurusan sekarang.
function forStudent(recs, ctx) {
    const cur = recs.current;
    const careers = recs.careers ?? [];
    const c1 = careers[0];
    if (!cur || !c1) return null;
    const f = fitLabel(c1.score);
    const ya = !!cur.feels_mismatch;
    const progs = (c) => (c.programs ?? []).map((p) => `${p.name} (${p.level})`);

    if (ya) {
        return {
            headline: `Karier yang paling cocok: ${c1.title}`,
            summary: `${profileLine(ctx.code, ctx.top3, ctx.norm)} Karena kamu merasa salah jurusan di ${cur.name}, kami mulai dari karier yang paling cocok dengan minatmu, lalu mencari jurusan yang mengarah ke sana.`,
            main: {
                label: 'Karier utama',
                title: c1.title,
                fit: f,
                points: [
                    c1.because?.length ? `Cocok karena sama-sama menuntut minat ${becauseText(c1.because)}.` : null,
                    progs(c1).length ? `Jurusan yang sejalan: ${list(progs(c1), ', ', ' atau ')}.` : null,
                    zoneText(c1.zone_min, c1.zone_max) ? `Pendidikan: ${zoneText(c1.zone_min, c1.zone_max).replace(/^U/, 'u')}.` : null,
                ].filter(Boolean),
            },
            alternatives: careers.slice(1).map((c) => ({
                title: c.title,
                fit: fitLabel(c.score),
                text: progs(c).length ? `Lewat jurusan ${list(progs(c), ', ', ' atau ')}.` : 'Jurusan belum tercatat.',
            })),
            alternativesTitle: 'Karier lain yang juga cocok',
            steps: [
                { when: 'Bulan ini', items: [
                    `Cari tahu keseharian ${c1.title}: tugasnya, tempat kerjanya, dan keahlian yang dibutuhkan.`,
                    progs(c1).length ? `Bandingkan kurikulum ${list(progs(c1), ', ', ' dan ')} dengan ${cur.name}.` : null,
                ].filter(Boolean) },
                { when: 'Semester ini', items: [
                    'Diskusikan dengan dosen pembimbing akademik: cukup diarahkan lewat mata kuliah pilihan, atau perlu pindah jurusan?',
                    'Coba kegiatan, kursus, atau proyek kecil di bidang karier itu untuk menguji minatmu.',
                ] },
                { when: 'Kalau mantap pindah', items: [
                    'Tanyakan prosedur ke bagian akademik kampusmu atau bagian penerimaan kampus tujuan.',
                    'Pastikan syarat IPK, batas semester, biaya, dan konversi SKS yang bisa diakui.',
                ] },
            ],
            notes: cur.semester >= 5 ? [`Kamu sudah di semester ${cur.semester}: makin lanjut semesternya, makin penting menghitung SKS yang bisa diakui sebelum pindah.`] : [],
        };
    }

    return {
        headline: `Profesi yang cocok untukmu: ${c1.title}`,
        summary: `${profileLine(ctx.code, ctx.top3, ctx.norm)} Kamu sudah nyaman di ${cur.name}, jadi kami pilihkan profesi dari jurusan ini yang paling sesuai dengan minatmu.`,
        main: {
            label: `Profesi utama dari ${cur.name}`,
            title: c1.title,
            fit: neutralFit(c1.score),
            points: [
                c1.because?.length ? `Cocok karena sama-sama menuntut minat ${becauseText(c1.because)}.` : null,
                zoneText(c1.zone_min, c1.zone_max) ? `Pendidikan: ${zoneText(c1.zone_min, c1.zone_max).replace(/^U/, 'u')}.` : null,
            ].filter(Boolean),
        },
        alternatives: careers.slice(1).map((c) => ({ title: c.title, fit: neutralFit(c.score), text: `Juga bisa ditempuh dari ${cur.name}.` })),
        alternativesTitle: 'Profesi lain dari jurusanmu',
        steps: [
            { when: 'Semester ini', items: [
                `Pilih mata kuliah peminatan yang paling dekat dengan pekerjaan ${c1.title}.`,
                'Cari dosen, organisasi, atau komunitas yang bergerak di bidang ini.',
            ] },
            { when: 'Tahun ini', items: [
                `Rencanakan magang atau proyek nyata sebagai ${c1.title} atau posisi sejenis.`,
                'Mulai susun portofolio dan CV sejak dini.',
            ] },
            { when: 'Sebelum lulus', items: ['Ambil sertifikasi atau pelatihan yang diakui di bidang ini.', 'Bangun jaringan dengan alumni yang sudah bekerja di profesi tersebut.'] },
        ],
        notes: [],
    };
}

/** @returns {null|{headline, summary, main, alternatives, steps, notes}} */
export function buildPathway(recs, ctx) {
    if (!recs) return null;
    if (recs.level === 'smp') return forSmp(recs, ctx);
    if (recs.level === 'mhs') return forStudent(recs, ctx);
    return forSchool(recs, ctx);
}
