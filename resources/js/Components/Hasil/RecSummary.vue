<script setup>
// Bagian rekomendasi di tab Ringkasan, per jenjang: kartu yang mudah dibaca + AI Pathway.
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Go from '../Go.vue';
import LineIcon from './LineIcon.vue';
import AiPathway from './AiPathway.vue';
import { KIND_ICON, tanggal, fitLabel, neutralFit, campusShort, zoneText } from '../../lib/format';
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
</script>

<template>
    <!-- Cara membaca hasil -->
    <section class="read-guide" aria-label="Cara membaca hasil">
        <div><span>1</span><p><b>Kecocokan</b> menunjukkan seberapa mirip profil minatmu dengan profil prodi atau karier. Bukan peluang diterima.</p></div>
        <div><span>2</span><p><b>Sangat cocok</b> ≥ 90%, <b>Cocok</b> 75–89%, <b>Cukup cocok</b> 60–74%.</p></div>
        <div><span>3</span><p>Klik <b>Lihat detail</b> untuk penjelasan lengkap, peluang karier, dan alasan kecocokannya.</p></div>
    </section>

    <!-- ============ SMA / SMK ============ -->
    <section v-if="level === 'sma_smk'" class="star-catalog" id="rekomendasi-ringkas-sec">
        <span class="v2-label">REKOMENDASI UNTUKMU</span>
        <h2>3 jurusan kuliah yang paling cocok</h2>
        <p class="star-notice">Setiap jurusan mengarah ke karier utama yang berbeda, dan masing-masing punya satu kampus pilihan. {{ accNote }}</p>
        <div class="star-grid">
            <article v-for="(p, i) in recs.programs" :key="p.id" class="star-card rx-card">
                <div class="star-card-top">
                    <span>Pilihan #{{ p.rank }} · {{ p.level }}<template v-if="p.field"> · {{ p.field }}</template></span>
                    <button type="button" class="star-favorite" :aria-pressed="isFav(p.id) ? 'true' : 'false'" :aria-label="isFav(p.id) ? 'Hapus dari favorit' : 'Simpan ke favorit'" @click="toggleFav(p.id)">
                        <svg width="20" height="20" viewBox="0 0 24 24" :fill="isFav(p.id) ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.6"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z" /></svg>
                    </button>
                </div>
                <h3 class="star-title-with-icon"><span class="star-title-icon"><LineIcon :paths="KIND_ICON.majors" /></span><span>{{ p.name }}</span></h3>
                <span class="fit-badge" :class="'fit-' + fitLabel(p.score).tone">{{ fitLabel(p.score).label }} · {{ fitLabel(p.score).pct }}%</span>
                <dl class="rx-facts">
                    <template v-if="p.because?.length"><dt>Kenapa cocok</dt><dd>Minat {{ becauseText(p.because) }}-mu</dd></template>
                    <template v-if="careersOf(p)"><dt>Peluang karier</dt><dd>{{ careersOf(p) }}</dd></template>
                    <dt>Kampus pilihan</dt>
                    <dd v-if="p.campus.name">{{ p.campus.name }}<small>{{ campusShort(p.campus) }}<template v-if="p.campus.program_accreditation?.rating"> · Prodi {{ p.campus.program_accreditation.rating }}</template><template v-else> · akreditasi belum diverifikasi</template></small></dd>
                    <dd v-else>Belum ada kampus yang memenuhi syarat</dd>
                </dl>
                <button type="button" class="star-detail" @click="emit('open', 'jurusan', i)">Lihat detail →</button>
            </article>
        </div>

        <section v-if="recs.umb_promos?.length" class="ad-conclusion umb-promo">
            <span class="v2-label">INFORMASI DARI UNIVERSITAS MERCU BUANA</span>
            <h2>Pilihanmu juga ada di UMB</h2>
            <p>Prodi di Universitas Mercu Buana (Jakarta) yang selaras dengan rekomendasimu. Informasi ini tidak mengubah urutan rekomendasi di atas.</p>
            <div class="ad-path-grid">
                <article v-for="u in recs.umb_promos" :key="u.name + u.level">
                    <span>{{ u.type === 'same_family' ? 'PRODI YANG SAMA TERSEDIA DI UMB' : 'PRODI TERKAIT DI UMB' }}</span>
                    <h3>{{ u.name }} ({{ u.level }})</h3>
                    <p>Selaras dengan pilihan #{{ u.for_rank }}: {{ u.for_name }}<template v-if="u.via">, lewat jabatan <b>{{ u.via }}</b></template>.</p>
                    <small>{{ u.accreditation?.rating ? 'Akreditasi ' + u.accreditation.rating : 'Akreditasi belum tersedia' }}</small>
                </article>
            </div>
        </section>
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
