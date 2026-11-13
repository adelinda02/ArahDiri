<script setup>
// Tab "Jurusan" hasil (gaya renderHasilJurusan v14): chip pilihan + kartu detail + akordeon.
import { computed, ref, watch } from 'vue';
import { pct, accLine, campusPlace, ownershipLabel } from '../../lib/format';
import { becauseText } from '../../lib/riasec';
import Go from '../Go.vue';

const props = defineProps({ recs: { type: Object, required: true }, start: { type: Number, default: 0 } });

const items = computed(() => {
    const r = props.recs;
    if (r.level === 'smp' && r.career_paths?.length) {
        return r.career_paths.map((c) => ({ type: 'path', label: 'Profesi: ' + c.title, data: c }));
    }
    if (r.level === 'smp') {
        return [
            ...(r.sma_tracks.length ? [{ type: 'track', label: 'Arah SMA', data: r.sma_tracks }] : []),
            ...r.smk_programs.map((s) => ({ type: 'smk', label: 'SMK: ' + s.name, data: s })),
            ...r.programs.map((p) => ({ type: 'program', label: p.name, data: p })),
        ];
    }
    if (r.level === 'mhs_umb') {
        return [
            ...r.umb_programs.map((p) => ({ type: 'program', label: p.name + ' (' + p.level + ')', data: p })),
            ...r.umb_s1_path.map((p) => ({ type: 'program', label: 'Lanjutan S1: ' + p.name, data: p, path: true })),
        ];
    }
    return r.programs.map((p) => ({ type: 'program', label: p.name, data: p }));
});
const active = ref(Math.min(props.start, Math.max(items.value.length - 1, 0)));
watch(() => props.start, (v) => (active.value = v));
const cur = computed(() => items.value[active.value]);
const row = ref(null);
const scroll = (dir) => row.value?.scrollBy({ left: (dir < 0 ? -1 : 1) * Math.max(180, row.value.clientWidth * 0.65), behavior: 'smooth' });
const isUmb = computed(() => props.recs.level === 'mhs_umb');
const isSmp = computed(() => props.recs.level === 'smp');
const SMK_VIA = { career: 'Lulusannya bekerja di bidang profesi ini', program: 'Sejalan dengan prodi lanjutannya' };
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

        <!-- Jalur per profesi (SMP rec-v3) -->
        <template v-if="cur.type === 'path'">
            <div class="hasil-mockup-card rec-hero">
                <div class="rec-hero-band"><span class="rec-pill">Profesi #{{ cur.data.rank }}</span><span class="rec-pill">KBJI {{ cur.data.id }}</span></div>
                <div class="hasil-card-header" style="padding:18px 20px 16px 20px">
                    <h2 class="hasil-card-title rec-title">{{ cur.data.title }}</h2>
                    <div class="hasil-meta-row rec-meta">
                        <div class="hasil-meta-item"><span>Keselarasan: <b>{{ pct(cur.data.score) }}%</b></span></div>
                        <div class="hasil-meta-item"><span>Kode minat profesi: <b>{{ cur.data.holland }}</b></span></div>
                        <div v-if="cur.data.because.length" class="hasil-meta-item"><span>Cocok karena: <b>{{ becauseText(cur.data.because) }}</b></span></div>
                    </div>
                </div>
            </div>
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
                            <b>{{ p.name }}</b> ({{ p.level }}) · keselarasan {{ pct(p.score) }}%<br>
                            <span><template v-if="p.subjects.length">Mapel pendukung SNBP<template v-if="recs.snbp?.source"> ({{ recs.snbp.source }})</template>: {{ p.subjects.join(' dan/atau ') }} · </template>dibuka di {{ p.offerings_count }} kampus · <Go :href="'/jurusan/' + p.id">lihat kampus →</Go></span>
                        </li>
                    </ul>
                    <p style="margin-top:10px">Kampus belum perlu dipilih sekarang; daftar kampus dan akreditasinya ada di katalog jurusan.</p>
                </div>
            </details>
            <details v-if="cur.data.description" class="acc-card rec-acc">
                <summary><span class="acc-title-wrap"><span class="acc-title">Tentang profesi ini</span></span><span class="acc-chevron">›</span></summary>
                <div class="acc-body"><p>{{ cur.data.description }}</p><p style="margin-top:8px"><Go :href="'/karier/' + cur.data.id">Lihat di katalog karier →</Go></p></div>
            </details>
        </template>

        <!-- Arah SMA -->
        <template v-else-if="cur.type === 'track'">
            <div class="hasil-mockup-card rec-hero">
                <div class="rec-hero-band"><span class="rec-pill">Arah SMA</span><span class="rec-pill">Draf, menunggu aturan penjurusan 2027</span></div>
                <div class="hasil-card-header" style="padding:18px 20px 16px 20px">
                    <h2 class="hasil-card-title rec-title">Arah {{ cur.data[0].name }}</h2>
                    <div class="hasil-meta-row rec-meta">
                        <div v-for="t in cur.data" :key="t.id" class="hasil-meta-item"><span>{{ t.name }}: <b>{{ pct(t.score) }}%</b></span></div>
                    </div>
                </div>
            </div>
            <details v-for="(t, i) in cur.data" :key="t.id" class="acc-card rec-acc" :open="i === 0">
                <summary><span class="acc-title-wrap"><span class="acc-title">#{{ t.rank }} Arah {{ t.name }} · keselarasan {{ pct(t.score) }}%</span></span><span class="acc-chevron">›</span></summary>
                <div class="acc-body">
                    <p>Mata pelajaran pilihan yang mendukung: <b>{{ t.subjects.join(', ') }}</b>.</p>
                    <p v-if="i === 0" style="margin-top:8px">Mapel pendukung dari minatmu: {{ t.support_subjects.join(', ') }}.</p>
                    <template v-if="t.next_programs?.length">
                        <p style="margin-top:12px"><b>Bisa lanjut kuliah ke:</b></p>
                        <ul class="klist rec-next">
                            <li v-for="n in t.next_programs" :key="n.id">
                                <b>{{ n.name }}</b> ({{ n.level }}) · keselarasan {{ pct(n.score) }}%<br>
                                <span>Mapel pendukung SNBP: {{ n.subjects.join(', ') }}<template v-if="n.because.length"> · cocok karena minat {{ becauseText(n.because) }}</template></span>
                            </li>
                        </ul>
                    </template>
                </div>
            </details>
        </template>

        <!-- Program keahlian SMK -->
        <template v-else-if="cur.type === 'smk'">
            <div class="hasil-mockup-card rec-hero">
                <div class="rec-hero-band"><span class="rec-pill">{{ cur.data.field }}</span><span class="rec-pill">Program SMK #{{ cur.data.rank }}</span></div>
                <div class="hasil-card-header" style="padding:18px 20px 16px 20px">
                    <h2 class="hasil-card-title rec-title">{{ cur.data.name }}</h2>
                    <div class="hasil-meta-row rec-meta">
                        <div class="hasil-meta-item"><span>Keselarasan: <b>{{ pct(cur.data.score) }}%</b></span></div>
                        <div class="hasil-meta-item"><span>Kode minat program: <b>{{ cur.data.holland }}</b></span></div>
                        <div class="hasil-meta-item"><span>Sumber: <b>Kepmendikbudristek 244/M/2024</b></span></div>
                    </div>
                </div>
            </div>
            <details class="acc-card rec-acc" open>
                <summary><span class="acc-title-wrap"><span class="acc-title">Konsentrasi keahlian</span></span><span class="acc-chevron">›</span></summary>
                <div class="acc-body"><ul class="klist"><li v-for="k in cur.data.concentrations" :key="k"><b>{{ k }}</b></li></ul></div>
            </details>
            <details class="acc-card rec-acc">
                <summary><span class="acc-title-wrap"><span class="acc-title">Contoh jabatan lulusan (KBJI 2026)</span></span><span class="acc-chevron">›</span></summary>
                <div class="acc-body"><ul class="klist"><li v-for="c in cur.data.careers" :key="c.code"><b>{{ c.title }}</b><br><span>KBJI {{ c.code }}</span></li></ul></div>
            </details>
            <details v-if="cur.data.next_programs?.length" class="acc-card rec-acc" open>
                <summary><span class="acc-title-wrap"><span class="acc-title">Bisa lanjut kuliah ke</span></span><span class="acc-chevron">›</span></summary>
                <div class="acc-body">
                    <p style="margin:0 0 10px">Prodi yang mengarah ke pekerjaan yang sama dengan lulusan program ini:</p>
                    <ul class="klist rec-next">
                        <li v-for="n in cur.data.next_programs" :key="n.id">
                            <b>{{ n.name }}</b> ({{ n.level }}) · keselarasan {{ pct(n.score) }}%<br>
                            <span>Pekerjaan yang sama: {{ n.careers.join(', ') }}<template v-if="n.because.length"> · cocok karena minat {{ becauseText(n.because) }}</template></span>
                        </li>
                    </ul>
                </div>
            </details>
        </template>

        <!-- Prodi (+ kampus) -->
        <template v-else>
            <div class="hasil-mockup-card rec-hero">
                <div class="rec-hero-band">
                    <span v-if="cur.data.field" class="rec-pill">{{ cur.data.field }}</span>
                    <span class="rec-pill">{{ cur.data.is_current ? 'Prodimu sekarang' : cur.path ? 'Jalur lanjutan S1' : 'Rekomendasi #' + cur.data.rank }}</span>
                </div>
                <div class="hasil-card-header" style="padding:18px 20px 16px 20px">
                    <h2 class="hasil-card-title rec-title">{{ cur.data.name }}</h2>
                    <div class="hasil-meta-row rec-meta">
                        <div class="hasil-meta-item"><span>Jenjang: <b>{{ cur.data.level }}</b></span></div>
                        <div class="hasil-meta-item"><span>Keselarasan: <b>{{ pct(cur.data.score) }}%</b></span></div>
                        <div class="hasil-meta-item"><span>Kode minat prodi: <b>{{ cur.data.holland || '-' }}</b></span></div>
                        <div v-if="cur.data.offerings_count" class="hasil-meta-item"><span>Dibuka di: <b>{{ cur.data.offerings_count }} kampus</b></span></div>
                    </div>
                </div>
            </div>
            <details class="acc-card rec-acc" open>
                <summary><span class="acc-title-wrap"><span class="acc-title">Peluang Karier &amp; Profesi Lulusan</span></span><span class="acc-chevron">›</span></summary>
                <div class="acc-body">
                    <p style="margin:0 0 10px">Jabatan KBJI 2026 yang terhubung dengan prodi ini:</p>
                    <ul class="klist"><li v-for="c in cur.data.careers" :key="c.code"><b>{{ c.title }}</b><br><span>KBJI {{ c.code }}</span></li></ul>
                </div>
            </details>
            <details v-if="isSmp && cur.data.from_tracks" class="acc-card rec-acc" open>
                <summary><span class="acc-title-wrap"><span class="acc-title">Jalur menuju prodi ini</span></span><span class="acc-chevron">›</span></summary>
                <div class="acc-body">
                    <p v-if="cur.data.because?.length">Cocok karena minat <b>{{ becauseText(cur.data.because) }}</b> (kode prodi {{ cur.data.holland }}).</p>
                    <p v-if="cur.data.support_subjects?.length" style="margin-top:8px">Mapel pendukung saat seleksi SNBP: <b>{{ cur.data.support_subjects.join(', ') }}</b>.</p>
                    <ul class="klist rec-next" style="margin-top:10px">
                        <li v-for="f in cur.data.from_tracks.filter((x) => x.strong)" :key="f.track"><b>SMA arah {{ f.track }}</b><br><span>lewat mapel {{ f.subjects.join(', ') }}</span></li>
                        <li v-if="!cur.data.from_tracks.some((x) => x.strong) && cur.data.from_tracks.length"><b>SMA arah mana pun</b><br><span>mapel pendukungnya ada di semua arah</span></li>
                        <li v-for="k in cur.data.from_smk" :key="k"><b>SMK {{ k }}</b><br><span>mengarah ke pekerjaan yang sama</span></li>
                    </ul>
                    <p style="margin-top:10px">Kampus belum perlu dipilih sekarang. Daftar kampus dan akreditasinya ada di <a :href="'/jurusan/' + cur.data.id">katalog jurusan</a>.</p>
                </div>
            </details>
            <details v-if="!isSmp || !cur.data.from_tracks" class="acc-card rec-acc" open>
                <summary><span class="acc-title-wrap"><span class="acc-title">{{ isUmb ? 'Akreditasi di UMB' : 'Kampus Pilihan' }}</span></span><span class="acc-chevron">›</span></summary>
                <div class="acc-body">
                    <template v-if="cur.data.campus.name">
                        <p><b>{{ cur.data.campus.name }}</b> · {{ ownershipLabel(cur.data.campus) }}<template v-if="campusPlace(cur.data.campus)"> · {{ campusPlace(cur.data.campus) }}</template></p>
                        <p style="margin-top:8px">Akreditasi prodi: {{ accLine(cur.data.campus.program_accreditation) }}</p>
                        <p>Akreditasi kampus: {{ accLine(cur.data.campus.campus_accreditation) }}</p>
                        <p v-if="cur.data.campus.program_accreditation?.expiring_soon" class="rec-warn">Akreditasi prodi segera berakhir; cek perpanjangannya di situs BAN-PT.</p>
                        <p v-if="cur.data.campus.website" style="margin-top:8px"><a :href="(cur.data.campus.website.startsWith('http') ? '' : 'https://') + cur.data.campus.website" target="_blank" rel="noopener">Situs resmi kampus ↗</a></p>
                    </template>
                    <p v-else>Kampus dipilih setelah data akreditasi Direktori BAN-PT disinkron.</p>
                </div>
            </details>
        </template>
    </template>
</template>
