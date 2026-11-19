<script setup>
// Tab "Jurusan": chip pilihan + detail lengkap (alasan, peluang karier, kampus pilihan, langkah).
import { computed, ref, watch } from 'vue';
import { fitLabel, neutralFit, accLine, campusPlace, campusShort, ownershipLabel, siteUrl, zoneText, tanggal } from '../../lib/format';
import { DIM, becauseText } from '../../lib/riasec';
import Go from '../Go.vue';

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

const hollandDims = (h) => (h ? h.split('').slice(0, 3).map((t) => DIM[t]).filter(Boolean) : []);
const fit = (s) => fitLabel(s);

// Mahasiswa: jurusan sekarang dan jawaban "merasa salah jurusan?".
const now = computed(() => props.recs.current);
const mFit = (s) => (now.value?.feels_mismatch ? fitLabel(s) : neutralFit(s));
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
            <div class="hasil-mockup-card rec-hero">
                <div class="rec-hero-band">
                    <span v-if="cur.data.field" class="rec-pill">{{ cur.data.field }}</span>
                    <span class="rec-pill">Pilihan #{{ cur.data.rank }}</span>
                </div>
                <div class="hasil-card-header" style="padding:18px 20px 16px 20px">
                    <h2 class="hasil-card-title rec-title">{{ cur.data.name }}</h2>
                    <span class="fit-badge" :class="'fit-' + fit(cur.data.score).tone">{{ fit(cur.data.score).label }} · {{ fit(cur.data.score).pct }}%</span>
                    <div class="hasil-meta-row rec-meta">
                        <div class="hasil-meta-item"><span>Jenjang: <b>{{ cur.data.level }}</b></span></div>
                        <div class="hasil-meta-item"><span>Kode minat prodi: <b>{{ cur.data.holland || '-' }}</b></span></div>
                        <div v-if="cur.data.offerings_count" class="hasil-meta-item"><span>Dibuka di: <b>{{ cur.data.offerings_count }} kampus</b></span></div>
                    </div>
                </div>
            </div>

            <details class="acc-card rec-acc" open>
                <summary><span class="acc-title-wrap"><span class="acc-title">Kenapa cocok untukmu</span></span><span class="acc-chevron">›</span></summary>
                <div class="acc-body">
                    <p v-if="cur.data.because?.length">Minat <b>{{ becauseText(cur.data.because) }}</b>-mu sama dengan minat utama prodi ini.</p>
                    <p v-else>Profil minat prodi ini paling dekat dengan profil minatmu secara keseluruhan.</p>
                    <ul v-if="hollandDims(cur.data.holland).length" class="rx-dim-list">
                        <li v-for="d in hollandDims(cur.data.holland)" :key="d.name"><b>{{ d.name }}</b> · {{ d.alias }}<br><span>{{ d.desc }}</span></li>
                    </ul>
                    <p v-if="cur.data.subjects?.length" style="margin-top:10px">Mapel pendukung saat seleksi SNBP<template v-if="recs.snbp?.source"> ({{ recs.snbp.source }})</template>: <b>{{ cur.data.subjects.join(', ') }}</b>.</p>
                </div>
            </details>

            <details class="acc-card rec-acc" open>
                <summary><span class="acc-title-wrap"><span class="acc-title">Peluang karier lulusan</span></span><span class="acc-chevron">›</span></summary>
                <div class="acc-body">
                    <ul v-if="cur.data.careers?.length" class="rx-career-list">
                        <li v-for="c in cur.data.careers" :key="c.code">
                            <div class="rx-career-top"><b>{{ c.title }}</b><span v-if="c.score != null" class="fit-badge" :class="'fit-' + fit(c.score).tone" :title="'Kecocokan dengan minatmu'">{{ fit(c.score).pct }}%</span></div>
                            <p v-if="c.description">{{ c.description }}</p>
                            <small>KBJI {{ c.code }}<template v-if="zoneText(c.zone_min, c.zone_max)"> · {{ zoneText(c.zone_min, c.zone_max) }}</template> · <Go :href="'/karier/' + c.code">katalog karier →</Go></small>
                        </li>
                    </ul>
                    <p v-else>Belum ada jabatan KBJI yang terhubung dengan prodi ini.</p>
                </div>
            </details>

            <details class="acc-card rec-acc" open>
                <summary><span class="acc-title-wrap"><span class="acc-title">Kampus pilihan</span></span><span class="acc-chevron">›</span></summary>
                <div class="acc-body">
                    <div v-if="cur.data.campus.name" class="rx-campus-main">
                        <p><b>{{ cur.data.campus.name }}</b> · {{ ownershipLabel(cur.data.campus) }}<template v-if="campusPlace(cur.data.campus)"> · {{ campusPlace(cur.data.campus) }}</template></p>
                        <p>Akreditasi prodi: {{ accLine(cur.data.campus.program_accreditation) }}</p>
                        <p>Akreditasi kampus: {{ accLine(cur.data.campus.campus_accreditation) }}</p>
                        <p v-if="cur.data.campus.status !== 'ready'" class="rec-warn-soft">Akreditasi belum diverifikasi dari Direktori BAN-PT.</p>
                        <p v-if="cur.data.campus.program_accreditation?.expiring_soon" class="rec-warn">Akreditasi prodi segera berakhir; cek perpanjangannya di situs BAN-PT.</p>
                        <p v-if="cur.data.campus.website"><a :href="siteUrl(cur.data.campus.website)" target="_blank" rel="noopener">Situs resmi kampus ↗</a></p>
                    </div>
                    <p v-else>Belum ada kampus yang memenuhi syarat untuk prodi ini.</p>
                    <template v-if="cur.data.alt_campuses?.length">
                        <p class="rx-steps-title">Kampus lain yang juga membuka prodi ini</p>
                        <ul class="rx-alt-campus">
                            <li v-for="c in cur.data.alt_campuses" :key="c.campus_id">
                                <div><b>{{ c.name }}</b><br><small>{{ campusShort(c) }}<template v-if="c.program_expires"> · berlaku s.d. {{ tanggal(c.program_expires) }}</template></small></div>
                                <span class="rx-acc-chip">{{ c.program_rating ? 'Prodi ' + c.program_rating : 'Belum diverifikasi' }}</span>
                            </li>
                        </ul>
                    </template>
                    <p style="margin-top:10px"><Go :href="'/jurusan/' + cur.data.id">Lihat semua {{ cur.data.offerings_count || '' }} kampus yang membuka prodi ini →</Go></p>
                </div>
            </details>

        </template>
    </template>
</template>
