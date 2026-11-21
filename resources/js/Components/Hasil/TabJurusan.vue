<script setup>
// Tab "Jurusan": chip pilihan + detail lengkap (alasan, peluang karier, kampus pilihan, langkah).
import { computed, ref, watch } from 'vue';
import { fitLabel, neutralFit, accLine, campusPlace, campusShort, ownershipLabel, siteUrl, zoneText, tanggal, KIND_ICON } from '../../lib/format';
import { DIM, ICON as DIM_ICON, becauseText } from '../../lib/riasec';
import Go from '../Go.vue';
import LineIcon from './LineIcon.vue';

const props = defineProps({ recs: { type: Object, required: true }, start: { type: Number, default: 0 } });

const items = computed(() => {
    const r = props.recs;
    if (r.level === 'smp') return (r.career_paths ?? []).map((c) => ({ type: 'path', label: 'Profesi: ' + c.title, data: c }));
    if (r.level === 'mhs') {
        return (r.careers ?? []).map((c) => ({ type: 'mhsCareer', label: (r.current?.feels_mismatch ? 'Karier: ' : 'Profesi: ') + c.title, data: c }));
    }
    return r.programs.map((p) => ({ type: 'program', label: p.name, data: p }));
});
const active = ref(Math.min(props.start, Math.max(items.value.length - 1, 0)));
watch(() => props.start, (v) => (active.value = v));
const cur = computed(() => items.value[active.value]);
const row = ref(null);
const scroll = (dir) => row.value?.scrollBy({ left: (dir < 0 ? -1 : 1) * Math.max(180, row.value.clientWidth * 0.65), behavior: 'smooth' });
const SMK_VIA = { career: 'Lulusannya bekerja di bidang profesi ini', program: 'Sejalan dengan prodi lanjutannya' };

const fit = (s) => fitLabel(s);

// Mahasiswa: jurusan sekarang dan jawaban "merasa salah jurusan?".
const now = computed(() => props.recs.current);
const mFit = (s) => (now.value?.feels_mismatch ? fitLabel(s) : neutralFit(s));

// Detail prodi (hanya tampilan): dimensi RIASEC beserta ikonnya, dan ikon garis untuk tiap bagian.
const hollandDimsIcon = (h) => (h ? h.split('').slice(0, 3).filter((t) => DIM[t]).map((t) => ({ key: t, ...DIM[t], icon: DIM_ICON[t] })) : []);
const UI_ICON = {
    target: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.2"/><path d="m12 12 7-7"/><path d="M16 5h3v3"/>',
    field: '<path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/>',
    rank: '<path d="m3 8 4 3 5-6 5 6 4-3-2 11H5L3 8Z"/>',
    level: '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/>',
    code: '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8Z"/><circle cx="8" cy="8" r="1.5"/>',
    info: '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
    pin: '<path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12Z"/><circle cx="12" cy="9" r="2.5"/>',
    badge: '<path d="M12 3 14.5 5l3.2-.2.8 3.1 2.6 1.9-1.2 3 1.2 3-2.6 1.9-.8 3.1-3.2-.2L12 23l-2.5-2.4-3.2.2-.8-3.1L2.9 16l1.2-3-1.2-3 2.6-1.9.8-3.1 3.2.2Z" transform="scale(.9) translate(1.3 0)"/><path d="m9 12 2 2 4-4"/>',
    check: '<circle cx="12" cy="12" r="9"/><path d="m8.5 12 2.5 2.5 4.5-5"/>',
    chevron: '<path d="m6 15 6-6 6 6"/>',
};
</script>

<template>
    <div v-if="!items.length" class="panel" style="margin-top:16px"><p class="lead">Rekomendasi belum tersedia untuk hasil ini.</p></div>
    <template v-else>
        <div class="hasil-chips-wrapper">
            <button type="button" class="chips-nav-btn chips-nav-prev" title="Geser ke pilihan sebelumnya" aria-label="Geser ke kiri" @click="scroll(-1)"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6" /></svg></button>
            <div ref="row" class="hasil-chips-row">
                <button v-for="(it, i) in items" :key="i" type="button" class="hasil-chip" :class="{ active: i === active }" @click="active = i">{{ it.label }}</button>
            </div>
            <button type="button" class="chips-nav-btn chips-nav-next" title="Geser ke pilihan berikutnya" aria-label="Geser ke kanan" @click="scroll(1)"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6" /></svg></button>
        </div>

        <!-- ================= Mahasiswa: satu karier (YA → jurusan sejalan, TIDAK → profesi dari jurusan sekarang) ================= -->
        <template v-if="cur.type === 'mhsCareer'">
            <div class="hasil-mockup-card rec-hero">
                <div class="rec-hero-band">
                    <span class="rec-pill">{{ now?.feels_mismatch ? 'Karier' : 'Profesi' }} #{{ cur.data.rank }}</span>
                    <span class="rec-pill">KBJI {{ cur.data.id }}</span>
                    <span v-if="!now?.feels_mismatch" class="rec-pill">Dari jurusan {{ now?.name }}</span>
                </div>
                <div class="hasil-card-header" style="padding:18px 20px 16px 20px">
                    <h2 class="hasil-card-title rec-title">{{ cur.data.title }}</h2>
                    <span class="fit-badge" :class="'fit-' + mFit(cur.data.score).tone">{{ mFit(cur.data.score).label }} · {{ mFit(cur.data.score).pct }}%</span>
                    <div class="hasil-meta-row rec-meta">
                        <div class="hasil-meta-item"><span>Kode minat profesi: <b>{{ cur.data.holland || '-' }}</b></span></div>
                        <div v-if="cur.data.because?.length" class="hasil-meta-item"><span>Cocok karena: <b>{{ becauseText(cur.data.because) }}</b></span></div>
                        <div v-if="zoneText(cur.data.zone_min, cur.data.zone_max)" class="hasil-meta-item"><span>Pendidikan: <b>{{ zoneText(cur.data.zone_min, cur.data.zone_max) }}</b></span></div>
                    </div>
                </div>
            </div>

            <details v-if="cur.data.description" class="acc-card rec-acc" open>
                <summary><span class="acc-title-wrap"><span class="acc-title">Apa yang dikerjakan {{ cur.data.title }}?</span></span><span class="acc-chevron">›</span></summary>
                <div class="acc-body"><p>{{ cur.data.description }}</p><p style="margin-top:8px"><Go :href="'/karier/' + cur.data.id">Lihat di katalog karier →</Go></p></div>
            </details>

            <!-- YA: jurusan yang sejalan dengan karier ini, tanpa kampus -->
            <details v-if="now?.feels_mismatch" class="acc-card rec-acc" open>
                <summary><span class="acc-title-wrap"><span class="acc-title">Jurusan yang sejalan dengan karier ini</span></span><span class="acc-chevron">›</span></summary>
                <div class="acc-body">
                    <ul class="rx-career-list">
                        <li v-for="p in cur.data.programs" :key="p.id">
                            <div class="rx-career-top"><b>{{ p.name }} ({{ p.level }})</b><span v-if="p.score != null" class="fit-badge" :class="'fit-' + fit(p.score).tone">{{ fit(p.score).label }} · {{ fit(p.score).pct }}%</span></div>
                            <p>
                                <template v-if="p.because?.length">Cocok dengan minat {{ becauseText(p.because) }}-mu. </template>
                                <template v-if="p.field">Bidang {{ p.field }}. </template>
                                Dibuka di {{ p.offerings_count }} kampus di Indonesia.
                            </p>
                            <small><template v-if="p.subjects?.length">Mapel pendukung: {{ p.subjects.join(', ') }} · </template><Go :href="'/jurusan/' + p.id">lihat di katalog jurusan →</Go></small>
                        </li>
                    </ul>
                </div>
            </details>

            <details class="acc-card rec-acc" open>
                <summary><span class="acc-title-wrap"><span class="acc-title">Langkah selanjutnya</span></span><span class="acc-chevron">›</span></summary>
                <div class="acc-body">
                    <ol v-if="now?.feels_mismatch" class="rx-steps">
                        <li>Pelajari keseharian seorang {{ cur.data.title }}: tugasnya, tempat kerjanya, dan keahlian yang dibutuhkan.</li>
                        <li>Bandingkan kurikulum {{ cur.data.programs.map((p) => p.name).join(' dan ') }} dengan jurusanmu sekarang ({{ now?.name }}): mata kuliah mana yang sama, mana yang baru.</li>
                        <li>Diskusikan dengan dosen pembimbing akademik: apakah bisa diarahkan lewat mata kuliah pilihan, atau perlu pindah jurusan.</li>
                        <li>Kalau mantap pindah, tanyakan prosedurnya ke bagian akademik kampusmu atau bagian penerimaan kampus tujuan (syarat IPK, batas semester, biaya, dan konversi SKS).</li>
                    </ol>
                    <ol v-else class="rx-steps">
                        <li>Pilih mata kuliah peminatan di {{ now?.name }} yang paling dekat dengan pekerjaan {{ cur.data.title }}.</li>
                        <li>Cari magang, proyek, atau organisasi di bidang ini sebelum tingkat akhir.</li>
                        <li>Tambah sertifikasi atau keahlian pendukung, lalu catat targetmu di menu <Go href="/langkah">Langkah</Go>.</li>
                    </ol>
                </div>
            </details>
        </template>

        <!-- ================= SMP: jalur per profesi ================= -->
        <template v-else-if="cur.type === 'path'">
            <div class="hasil-mockup-card rec-hero">
                <div class="rec-hero-band"><span class="rec-pill">Profesi #{{ cur.data.rank }}</span><span class="rec-pill">KBJI {{ cur.data.id }}</span></div>
                <div class="hasil-card-header" style="padding:18px 20px 16px 20px">
                    <h2 class="hasil-card-title rec-title">{{ cur.data.title }}</h2>
                    <span class="fit-badge" :class="'fit-' + fit(cur.data.score).tone">{{ fit(cur.data.score).label }} · {{ fit(cur.data.score).pct }}%</span>
                    <div class="hasil-meta-row rec-meta">
                        <div class="hasil-meta-item"><span>Kode minat profesi: <b>{{ cur.data.holland }}</b></span></div>
                        <div v-if="cur.data.because.length" class="hasil-meta-item"><span>Cocok karena: <b>{{ becauseText(cur.data.because) }}</b></span></div>
                        <div v-if="zoneText(cur.data.zone_min, cur.data.zone_max)" class="hasil-meta-item"><span>Pendidikan: <b>{{ zoneText(cur.data.zone_min, cur.data.zone_max) }}</b></span></div>
                    </div>
                </div>
            </div>
            <details v-if="cur.data.description" class="acc-card rec-acc" open>
                <summary><span class="acc-title-wrap"><span class="acc-title">Apa yang dikerjakan {{ cur.data.title }}?</span></span><span class="acc-chevron">›</span></summary>
                <div class="acc-body"><p>{{ cur.data.description }}</p><p style="margin-top:8px"><Go :href="'/karier/' + cur.data.id">Lihat di katalog karier →</Go></p></div>
            </details>
            <details class="acc-card rec-acc" open>
                <summary><span class="acc-title-wrap"><span class="acc-title">Langkah 1 · SMA arah {{ cur.data.sma.name }}</span></span><span class="acc-chevron">›</span></summary>
                <div class="acc-body">
                    <p>Mata pelajaran pilihan arah {{ cur.data.sma.name }}: <b>{{ cur.data.sma.subjects.join(', ') }}</b>.</p>
                    <p v-if="!cur.data.sma.any && cur.data.sma.via.length" style="margin-top:8px">Yang paling penting untuk prodinya nanti: <b>{{ cur.data.sma.via.join(', ') }}</b> (mapel pendukung SNBP).</p>
                    <p v-else style="margin-top:8px">Prodinya tidak mensyaratkan mapel khas arah tertentu; arah {{ cur.data.sma.name }} dipilih karena paling selaras dengan minatmu.</p>
                    <p style="margin-top:8px" class="rec-warn-soft">Penjurusan SMA direncanakan kembali tahun 2027; arah ini disesuaikan setelah aturannya terbit.</p>
                </div>
            </details>
            <details class="acc-card rec-acc" open>
                <summary><span class="acc-title-wrap"><span class="acc-title">Langkah 1 (pilihan lain) · SMK</span></span><span class="acc-chevron">›</span></summary>
                <div class="acc-body">
                    <ul v-if="cur.data.smk.length" class="klist rec-next">
                        <li v-for="k in cur.data.smk" :key="k.code">
                            <b>{{ k.name }}</b> · {{ k.field }}<br>
                            <span>{{ SMK_VIA[k.via] }}<template v-if="k.concentrations.length">. Konsentrasi: {{ k.concentrations.join(', ') }}</template></span>
                        </li>
                    </ul>
                    <p v-else>Belum ada program SMK yang langsung menyiapkan ke profesi ini. Jalur <b>SMA arah {{ cur.data.sma.name }}</b> lebih disarankan.</p>
                    <p v-if="cur.data.smk.length && recs.snbp?.smk_note" style="margin-top:10px" class="rec-warn-soft">Kabar baik: {{ recs.snbp.smk_note }}</p>
                </div>
            </details>
            <details class="acc-card rec-acc" open>
                <summary><span class="acc-title-wrap"><span class="acc-title">Langkah 2 · Kuliah</span></span><span class="acc-chevron">›</span></summary>
                <div class="acc-body">
                    <ul class="klist rec-next">
                        <li v-for="p in cur.data.programs" :key="p.id">
                            <b>{{ p.name }}</b> ({{ p.level }}) · {{ fit(p.score).label.toLowerCase() }} {{ fit(p.score).pct }}%<br>
                            <span><template v-if="p.subjects.length">Mapel pendukung SNBP<template v-if="recs.snbp?.source"> ({{ recs.snbp.source }})</template>: {{ p.subjects.join(' dan/atau ') }} · </template>dibuka di {{ p.offerings_count }} kampus · <Go :href="'/jurusan/' + p.id">lihat kampus →</Go></span>
                        </li>
                    </ul>
                    <p style="margin-top:10px">Kampus belum perlu dipilih sekarang; daftar kampus dan akreditasinya ada di katalog jurusan.</p>
                </div>
            </details>
        </template>

        <!-- ================= Prodi (SMA/SMK, pindah jurusan, kampus sendiri) ================= -->
        <template v-else>
            <!-- Ringkasan utama: nama, kecocokan, jenjang, kode minat, jumlah kampus -->
            <section class="rd-hero">
                <div class="rd-hero-main">
                    <div class="rd-pills">
                        <span v-if="cur.data.field" class="rd-pill"><LineIcon :paths="UI_ICON.field" />{{ cur.data.field }}</span>
                        <span class="rd-pill"><LineIcon :paths="UI_ICON.rank" />Pilihan #{{ cur.data.rank }}</span>
                    </div>
                    <div class="rd-title-row">
                        <span class="rd-title-icon"><LineIcon :paths="KIND_ICON.majors" /></span>
                        <div class="rd-title-text">
                            <h2>{{ cur.data.name }}</h2>
                            <span class="fit-badge rd-fit" :class="'fit-' + fit(cur.data.score).tone"><LineIcon :paths="UI_ICON.check" />{{ fit(cur.data.score).label }} · {{ fit(cur.data.score).pct }}%</span>
                        </div>
                    </div>
                </div>
                <dl class="rd-stats">
                    <div class="rd-stat">
                        <span class="rd-stat-icon"><LineIcon :paths="UI_ICON.level" /></span>
                        <div><dt>Jenjang</dt><dd>{{ cur.data.level }}</dd></div>
                    </div>
                    <div class="rd-stat">
                        <span class="rd-stat-icon"><LineIcon :paths="UI_ICON.code" /></span>
                        <div><dt>Kode minat prodi</dt><dd>{{ cur.data.holland || '-' }}</dd></div>
                    </div>
                    <div v-if="cur.data.offerings_count" class="rd-stat">
                        <span class="rd-stat-icon"><LineIcon :paths="KIND_ICON.campuses" /></span>
                        <div><dt>Dibuka di</dt><dd>{{ cur.data.offerings_count }} kampus</dd></div>
                    </div>
                </dl>
            </section>

            <!-- Kenapa cocok -->
            <details class="rd-acc" open>
                <summary>
                    <span class="rd-acc-title"><LineIcon :paths="UI_ICON.target" />Kenapa cocok untukmu</span>
                    <span class="rd-acc-chevron"><LineIcon :paths="UI_ICON.chevron" /></span>
                </summary>
                <div class="rd-acc-body">
                    <p v-if="cur.data.because?.length" class="rd-lead">Minat <b>{{ becauseText(cur.data.because) }}</b>-mu sama dengan minat utama prodi ini.</p>
                    <p v-else class="rd-lead">Profil minat prodi ini paling dekat dengan profil minatmu secara keseluruhan.</p>
                    <ul v-if="hollandDimsIcon(cur.data.holland).length" class="rd-dims">
                        <li v-for="d in hollandDimsIcon(cur.data.holland)" :key="d.key">
                            <span class="rd-dim-icon"><LineIcon :paths="d.icon" /></span>
                            <div class="rd-dim-text"><b>{{ d.name }}</b> · <span class="rd-dim-alias">{{ d.alias }}</span><p>{{ d.desc }}</p></div>
                        </li>
                    </ul>
                    <p v-if="cur.data.subjects?.length" class="rd-note">
                        <LineIcon :paths="UI_ICON.info" />
                        <span class="rd-note-text">Mapel pendukung saat seleksi SNBP<template v-if="recs.snbp?.source"> ({{ recs.snbp.source }})</template>: <span v-for="s in cur.data.subjects" :key="s" class="rd-chip">{{ s }}</span></span>
                    </p>
                </div>
            </details>

            <!-- Peluang karier -->
            <details class="rd-acc" open>
                <summary>
                    <span class="rd-acc-title"><LineIcon :paths="KIND_ICON.careers" />Peluang karier lulusan</span>
                    <span class="rd-acc-chevron"><LineIcon :paths="UI_ICON.chevron" /></span>
                </summary>
                <div class="rd-acc-body">
                    <ul v-if="cur.data.careers?.length" class="rd-careers">
                        <li v-for="c in cur.data.careers" :key="c.code" class="rd-career">
                            <span class="rd-career-icon"><LineIcon :paths="KIND_ICON.careers" /></span>
                            <div class="rd-career-main">
                                <b>{{ c.title }}</b>
                                <p v-if="c.description">{{ c.description }}</p>
                            </div>
                            <div class="rd-career-meta">
                                <span>KBJI {{ c.code }}</span>
                                <small v-if="zoneText(c.zone_min, c.zone_max)">{{ zoneText(c.zone_min, c.zone_max) }}</small>
                                <Go :href="'/karier/' + c.code" class="rd-link">Katalog karier →</Go>
                            </div>
                            <span v-if="c.score != null" class="fit-badge rd-career-fit" :class="'fit-' + fit(c.score).tone" title="Kecocokan dengan minatmu">{{ fit(c.score).pct }}%</span>
                        </li>
                    </ul>
                    <p v-else class="rd-lead">Belum ada jabatan KBJI yang terhubung dengan prodi ini.</p>
                </div>
            </details>

            <!-- Kampus pilihan -->
            <details class="rd-acc" open>
                <summary>
                    <span class="rd-acc-title"><LineIcon :paths="KIND_ICON.campuses" />Kampus pilihan</span>
                    <span class="rd-acc-chevron"><LineIcon :paths="UI_ICON.chevron" /></span>
                </summary>
                <div class="rd-acc-body">
                    <div v-if="cur.data.campus.name" class="rd-campus">
                        <div class="rd-campus-id">
                            <span class="rd-title-icon rd-campus-icon"><LineIcon :paths="KIND_ICON.majors" /></span>
                            <div>
                                <h3>{{ cur.data.campus.name }}</h3>
                                <p class="rd-campus-place"><LineIcon :paths="UI_ICON.pin" />{{ ownershipLabel(cur.data.campus) }}<template v-if="campusPlace(cur.data.campus)"> · {{ campusPlace(cur.data.campus) }}</template></p>
                                <p v-if="cur.data.campus.website" class="rd-campus-site"><a :href="siteUrl(cur.data.campus.website)" target="_blank" rel="noopener">Situs resmi kampus ↗</a></p>
                            </div>
                        </div>
                        <div class="rd-campus-acc">
                            <p><LineIcon :paths="UI_ICON.badge" /><span><b>Akreditasi prodi:</b> {{ accLine(cur.data.campus.program_accreditation) }}</span></p>
                            <p><LineIcon :paths="UI_ICON.badge" /><span><b>Akreditasi kampus:</b> {{ accLine(cur.data.campus.campus_accreditation) }}</span></p>
                            <p v-if="cur.data.campus.status !== 'ready'" class="rec-warn-soft">Akreditasi belum diverifikasi dari Direktori BAN-PT.</p>
                            <p v-if="cur.data.campus.program_accreditation?.expiring_soon" class="rec-warn">Akreditasi prodi segera berakhir; cek perpanjangannya di situs BAN-PT.</p>
                        </div>
                    </div>
                    <p v-else class="rd-lead">Belum ada kampus yang memenuhi syarat untuk prodi ini.</p>
                    <template v-if="cur.data.alt_campuses?.length">
                        <p class="rx-steps-title">Kampus lain yang juga membuka prodi ini</p>
                        <ul class="rx-alt-campus">
                            <li v-for="c in cur.data.alt_campuses" :key="c.campus_id">
                                <div><b>{{ c.name }}</b><br><small>{{ campusShort(c) }}<template v-if="c.program_expires"> · berlaku s.d. {{ tanggal(c.program_expires) }}</template></small></div>
                                <span class="rx-acc-chip">{{ c.program_rating ? 'Prodi ' + c.program_rating : 'Belum diverifikasi' }}</span>
                            </li>
                        </ul>
                    </template>
                    <Go :href="'/jurusan/' + cur.data.id" class="rd-all-btn">Lihat semua {{ cur.data.offerings_count || '' }} kampus yang membuka prodi ini <span aria-hidden="true">→</span></Go>
                </div>
            </details>

        </template>
    </template>
</template>
