<script setup>
// Bagian rekomendasi di tab Ringkasan, per jenjang: kartu yang mudah dibaca + AI Pathway.
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Go from '../Go.vue';
import LineIcon from './LineIcon.vue';
import AiPathway from './AiPathway.vue';
import { KIND_ICON, fieldIcon, tanggal, fitLabel, neutralFit, campusShort, zoneText } from '../../lib/format';
import { becauseText } from '../../lib/riasec';

const props = defineProps({
    recs: { type: Object, required: true },
    code: { type: String, required: true },
    top3: { type: Array, required: true },
    norm: { type: Object, required: true },
    favorites: { type: Object, default: () => ({}) },
});
const emit = defineEmits(['open']);
const isFav = (id) => (props.favorites.program ?? []).includes(String(id));
const toggleFav = (id) => router.post('/favorit', { kind: 'program', target_id: String(id) }, { preserveScroll: true, preserveState: true });

const level = computed(() => props.recs.level);
const accNote = computed(() => props.recs.accreditation_date
    ? `Akreditasi menurut Direktori BAN-PT per ${tanggal(props.recs.accreditation_date)}.`
    : 'Data akreditasi BAN-PT belum disinkron; kampus diambil dari katalog PDDikti dan akreditasinya belum diverifikasi.');
const careersOf = (p, n = 2) => (p.careers ?? []).slice(0, n).map((c) => c.title).join(', ');

// SMP
const paths = computed(() => props.recs.career_paths ?? []);
const smkName = (c) => (c.smk.length ? c.smk.map((k) => k.name).join(' / ') : null);

// Mahasiswa
const cur = computed(() => props.recs.current);
const mismatch = computed(() => !!cur.value?.feels_mismatch);
// Sudah suka jurusannya → tampilkan angka kecocokan tanpa label "kurang cocok".
const careerFit = (c) => (mismatch.value ? fitLabel(c.score) : neutralFit(c.score));

// Panduan membaca hasil (hanya tampilan). Ambang sama dengan fitLabel() di lib/format.js.
const GUIDE_ICON = {
    target: '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.2"/><path d="m12 12 7-7"/><path d="M16 5h3v3"/>',
    users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    bars: '<rect x="4" y="13" width="4" height="7" rx="1.2"/><rect x="10" y="9" width="4" height="11" rx="1.2"/><rect x="16" y="4" width="4" height="16" rx="1.2"/>',
    click: '<path d="m9 9 5 12 1.8-5.2L21 14Z"/><path d="M7.2 2.2 8 5.1"/><path d="m5.1 8-2.9-.8"/><path d="M14 4.1 12 6"/><path d="m6 12-1.9 2"/>',
    info: '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
    star: '<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9Z"/>',
};
const hasUmb = computed(() => (props.recs.umb_promos?.length ?? 0) > 0);
// Prodi UMB memakai ikon bidang dari rekomendasi yang diselaraskan.
const umbIcon = (u) => fieldIcon(props.recs.programs?.[u.for_rank - 1]?.field);
const GUIDE_TIERS = [
    { label: 'Sangat cocok', range: '≥ 90%' },
    { label: 'Cocok', range: '75 – 89%' },
    { label: 'Cukup cocok', range: '60 – 74%' },
];
</script>

<template>
    <!-- Cara membaca hasil -->
    <section class="read-guide" aria-labelledby="read-guide-title">
        <header class="rg-head">
            <span class="rg-head-icon"><LineIcon :paths="GUIDE_ICON.target" /></span>
            <div>
                <h2 id="read-guide-title">Panduan Memahami Hasil Kecocokan</h2>
                <p>Berikut penjelasan untuk membaca hasil kecocokan profil minatmu dengan profil prodi atau karier.</p>
            </div>
        </header>

        <div class="rg-cards">
            <article class="rg-card">
                <span class="rg-icon"><LineIcon :paths="GUIDE_ICON.users" /></span>
                <div class="rg-body">
                    <h3><span class="rg-no">1</span>Kecocokan</h3>
                    <p>Menunjukkan seberapa mirip profil minatmu dengan profil prodi atau karier.</p>
                    <p class="rg-warn"><LineIcon :paths="GUIDE_ICON.info" /><b>Bukan peluang diterima.</b></p>
                </div>
            </article>

            <article class="rg-card rg-card--tiers">
                <span class="rg-icon"><LineIcon :paths="GUIDE_ICON.bars" /></span>
                <div class="rg-body">
                    <h3><span class="rg-no">2</span>Tingkat Kecocokan</h3>
                    <ul class="rg-tiers">
                        <li v-for="t in GUIDE_TIERS" :key="t.label"><span class="rg-tier-label">{{ t.label }}</span><span class="rg-tier-range">{{ t.range }}</span></li>
                    </ul>
                </div>
            </article>

            <article class="rg-card">
                <span class="rg-icon"><LineIcon :paths="GUIDE_ICON.click" /></span>
                <div class="rg-body">
                    <h3><span class="rg-no">3</span>Lihat Detail</h3>
                    <p>Klik <b>Lihat detail</b> untuk penjelasan lengkap, peluang karier, dan alasan kecocokannya.</p>
                </div>
            </article>
        </div>
    </section>

    <!-- ============ SMA / SMK ============ -->
    <section v-if="level === 'sma_smk'" class="star-catalog rr" id="rekomendasi-ringkas-sec">
        <header class="rr-head">
            <span class="rr-kicker">Hasil Rekomendasi Jurusan <LineIcon :paths="KIND_ICON.majors" /></span>
            <h2>Rekomendasi Jurusan Kuliah untuk Kamu</h2>
            <p>Berdasarkan hasil tes minat RIASEC, berikut {{ recs.programs.length }} jurusan yang paling sesuai dengan profil kamu<template v-if="hasUmb">, serta pilihan program studi relevan di Universitas Mercu Buana (UMB)</template>.</p>
            <small>Setiap jurusan mengarah ke karier utama yang berbeda, dan masing-masing punya satu kampus pilihan. {{ accNote }}</small>
        </header>

        <div class="rr-grid" :class="{ 'rr-grid--umb': hasUmb }">
            <article v-for="(p, i) in recs.programs" :key="p.id" class="rr-card">
                <div class="rr-top">
                    <span class="rr-badge">#{{ p.rank }} {{ p.rank === 1 ? 'Rekomendasi Utama' : 'Rekomendasi' }}</span>
                    <button type="button" class="rr-fav" :aria-pressed="isFav(p.id) ? 'true' : 'false'" :aria-label="isFav(p.id) ? 'Hapus dari favorit' : 'Simpan ke favorit'" @click="toggleFav(p.id)">
                        <svg width="20" height="20" viewBox="0 0 24 24" :fill="isFav(p.id) ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.7"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" /></svg>
                    </button>
                </div>

                <div class="rr-title">
                    <span class="rr-title-icon" :title="p.field || null"><LineIcon :paths="fieldIcon(p.field)" /></span>
                    <div>
                        <h3>{{ p.name }} <small>{{ p.level }}</small></h3>
                        <p>{{ p.campus.name || (p.field ? 'Bidang ' + p.field : 'Kampus belum tersedia') }}</p>
                        <span class="rr-badge rr-fit" :title="fitLabel(p.score).label">Kecocokan: {{ fitLabel(p.score).pct }}%</span>
                    </div>
                </div>

                <dl class="rr-facts">
                    <div v-if="p.because?.length" class="rr-fact">
                        <LineIcon :paths="GUIDE_ICON.target" />
                        <div><dt>Alasan Kecocokan</dt><dd>Sesuai dengan minat {{ becauseText(p.because) }}-mu.</dd></div>
                    </div>
                    <div v-if="careersOf(p, 4)" class="rr-fact">
                        <LineIcon :paths="KIND_ICON.careers" />
                        <div><dt>Peluang Karier</dt><dd>{{ careersOf(p, 4) }}.</dd></div>
                    </div>
                    <div class="rr-fact rr-fact--campus">
                        <LineIcon :paths="KIND_ICON.campuses" />
                        <div>
                            <dt>Kampus Pilihan</dt>
                            <template v-if="p.campus.name">
                                <dd class="rr-campus-name">{{ p.campus.name }}</dd>
                                <dd class="rr-campus-meta">{{ campusShort(p.campus) }}</dd>
                                <dd><span class="rr-badge">{{ p.campus.program_accreditation?.rating ? 'Akreditasi ' + p.campus.program_accreditation.rating : 'Akreditasi belum diverifikasi' }}</span></dd>
                            </template>
                            <dd v-else>Belum ada kampus yang memenuhi syarat</dd>
                        </div>
                    </div>
                </dl>

                <button type="button" class="rr-btn" @click="emit('open', 'jurusan', i)">Lihat Detail <span aria-hidden="true">→</span></button>
            </article>

            <!-- Kartu sorotan UMB: muncul hanya bila backend mengirim umb_promos -->
            <article v-if="hasUmb" class="rr-card rr-umb">
                <div class="rr-top">
                    <span class="rr-badge rr-badge--outline"><LineIcon :paths="GUIDE_ICON.star" />Tersedia di UMB</span>
                    <span class="rr-umb-brand">
                        <img src="/img/umb-logo.png" alt="Logo Universitas Mercu Buana" class="rr-umb-logo" width="32" height="32" loading="lazy" decoding="async">
                        <span class="rr-umb-name"><small>Universitas</small>Mercu Buana</span>
                    </span>
                </div>
                <h3 class="rr-umb-title">Program Studi yang Tersedia di UMB</h3>
                <p class="rr-umb-desc">Pilihan program studi di Universitas Mercu Buana yang selaras dengan minat dan rekomendasi kamu. Informasi ini tidak mengubah urutan rekomendasi.</p>

                <ul class="rr-umb-list">
                    <li v-for="u in recs.umb_promos" :key="u.name + u.level">
                        <span class="rr-umb-icon"><LineIcon :paths="umbIcon(u)" /></span>
                        <div class="rr-umb-text">
                            <b>{{ u.name }} ({{ u.level }})</b>
                            <small>
                                <template v-if="u.type === 'same_family'">Selaras dengan pilihan #{{ u.for_rank }}</template>
                                <template v-else>Terkait pilihan #{{ u.for_rank }}<template v-if="u.via"> lewat jabatan {{ u.via }}</template></template>
                            </small>
                        </div>
                        <span class="rr-badge rr-umb-acc">{{ u.accreditation?.rating ? 'Akreditasi ' + u.accreditation.rating : 'Akreditasi belum tersedia' }}</span>
                    </li>
                </ul>

                <Go href="/kampus?q=Mercu Buana" class="rr-btn rr-btn--umb">Lihat Program Studi UMB <span aria-hidden="true">→</span></Go>
            </article>
        </div>
    </section>

    <!-- ============ SMP: jalur per profesi ============ -->
    <section v-else-if="level === 'smp' && paths.length" class="star-catalog" id="rekomendasi-ringkas-sec">
        <span class="v2-label">JALUR MENUJU CITA-CITA</span>
        <h2>3 profesi yang paling cocok dan jalur sekolahnya</h2>
        <p class="star-notice">Dimulai dari profesi yang cocok dengan minatmu, lalu langkah mundurnya: arah SMA atau program SMK, kemudian jurusan kuliah.</p>
        <div class="star-grid">
            <article v-for="(c, i) in paths" :key="c.id" class="star-card rx-card">
                <div class="star-card-top"><span>Profesi #{{ c.rank }}</span></div>
                <h3 class="star-title-with-icon"><span class="star-title-icon"><LineIcon :paths="KIND_ICON.careers" /></span><span>{{ c.title }}</span></h3>
                <span class="fit-badge" :class="'fit-' + fitLabel(c.score).tone">{{ fitLabel(c.score).label }} · {{ fitLabel(c.score).pct }}%</span>
                <ol class="rec-path-steps">
                    <li><b>SMA</b> arah {{ c.sma.name }}<template v-if="smkName(c)"> · <b>SMK</b> {{ smkName(c) }}</template></li>
                    <li><b>Kuliah</b> {{ c.programs.map((p) => p.name).join(' / ') }}</li>
                    <li v-if="zoneText(c.zone_min, c.zone_max)"><b>Kerja</b> {{ zoneText(c.zone_min, c.zone_max) }}</li>
                </ol>
                <button type="button" class="star-detail" @click="emit('open', 'jurusan', i)">Lihat jalur lengkap →</button>
            </article>
        </div>
    </section>

    <!-- ============ Mahasiswa ============ -->
    <section v-else-if="level === 'mhs'" class="star-catalog" id="rekomendasi-ringkas-sec">
        <template v-if="cur">
            <span class="v2-label">{{ mismatch ? 'KARIER DULU, LALU JURUSAN' : 'PROFESI DARI JURUSANMU' }}</span>
            <h2>{{ mismatch ? 'Karier yang cocok dengan minatmu' : `Profesi yang cocok untukmu dari ${cur.name}` }}</h2>
            <div class="rx-answer">
                <div>
                    <small>Jurusanmu sekarang</small>
                    <b>{{ cur.name }} ({{ cur.level }})</b><span v-if="cur.campus?.name"> · {{ cur.campus.name }}</span>
                </div>
                <div>
                    <small>Merasa salah jurusan?</small>
                    <b>{{ mismatch ? 'Iya' : 'Tidak' }}</b> · <Go href="/mulai/prodi" class="v2-link rx-inline">ubah jawaban</Go>
                </div>
            </div>
            <p class="star-notice">
                <template v-if="mismatch">Kami cari dulu karier yang paling cocok dengan minatmu, lalu jurusan yang sejalan dengan karier itu. Jurusan ini dibuka di banyak kampus di Indonesia; pilih kampusnya nanti setelah kamu yakin dengan jurusannya.</template>
                <template v-else>Karena kamu sudah nyaman dengan jurusanmu, berikut profesi dari {{ cur.name }} yang paling cocok dengan minatmu.</template>
            </p>

            <div class="star-grid">
                <article v-for="c in recs.careers" :key="c.id" class="star-card rx-card">
                    <div class="star-card-top"><span>{{ mismatch ? 'Karier' : 'Profesi' }} #{{ c.rank }}</span></div>
                    <h3 class="star-title-with-icon"><span class="star-title-icon"><LineIcon :paths="KIND_ICON.careers" /></span><span>{{ c.title }}</span></h3>
                    <span class="fit-badge" :class="'fit-' + careerFit(c).tone">{{ careerFit(c).label }} · {{ careerFit(c).pct }}%</span>
                    <dl class="rx-facts">
                        <template v-if="c.because?.length"><dt>Kenapa cocok</dt><dd>Minat {{ becauseText(c.because) }}-mu</dd></template>
                        <template v-if="mismatch && c.programs?.length"><dt>Jurusan yang sejalan</dt><dd>{{ c.programs.map((p) => `${p.name} (${p.level})`).join(', ') }}</dd></template>
                        <template v-if="zoneText(c.zone_min, c.zone_max)"><dt>Pendidikan</dt><dd>{{ zoneText(c.zone_min, c.zone_max) }}</dd></template>
                    </dl>
                    <button type="button" class="star-detail" @click="emit('open', 'jurusan', c.rank - 1)">Lihat detail →</button>
                </article>
            </div>
            <p v-if="!recs.careers?.length" class="star-notice">Belum ada profesi yang terhubung dengan jurusan ini di katalog.</p>
        </template>
        <template v-else>
            <h2>Lengkapi kampus dan prodimu</h2>
            <p class="star-notice">Rekomendasi membutuhkan kampus dan prodi yang sedang kamu jalani.</p>
            <Go href="/mulai/prodi" class="btn btn-primary">Pilih kampus dan prodi</Go>
        </template>
    </section>

    <AiPathway :recs="recs" :code="code" :top3="top3" :norm="norm" @open="(t, i) => emit('open', t, i)" />
</template>
